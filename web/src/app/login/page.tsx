'use client';

import {useEffect, useState} from 'react';
import {useRouter} from 'next/navigation';
import {api, ApiError} from '@/lib/api/client';
import {isAuthed, setToken} from '@/lib/auth/session';
import {LogIn} from 'lucide-react';

type Step = 'phone' | 'otp';

interface VerifyResponse {
    token: string;
    profile_complete: boolean;
    user: { id: number; phone: string };
}

export default function LoginPage() {
    const router = useRouter();
    const [step, setStep] = useState<Step>('phone');
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    // Already logged in? Skip the form.
    useEffect(() => {
        if (isAuthed()) router.replace('/admin');
    }, [router]);

    async function requestOtp(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            await api('/v1/auth/phone/request-otp', {
                method: 'POST',
                body: {phone},
            });
            setStep('otp');
        } catch (err) {
            setError(err instanceof ApiError ? err.message : 'Could not send OTP');
        } finally {
            setBusy(false);
        }
    }

    async function verifyOtp(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            const data = await api<VerifyResponse>('/v1/auth/phone/verify-otp', {
                method: 'POST',
                body: {phone, otp},
            });
            setToken(data.token);
            router.replace('/admin');
        } catch (err) {
            setError(err instanceof ApiError ? err.message : 'Invalid OTP');
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="min-h-screen grid place-items-center px-4">
            <div className="card w-full max-w-md p-8">
                <div className="flex items-center gap-3 mb-6">
                    <div className="h-10 w-10 rounded-lg bg-brand-600 grid place-items-center text-white">
                        <LogIn size={20}/>
                    </div>
                    <div>
                        <h1 className="text-lg font-semibold">LiveMapEvents Admin</h1>
                        <p className="text-xs text-slate-500">Sign in with your phone</p>
                    </div>
                </div>

                {step === 'phone' && (
                    <form onSubmit={requestOtp} className="space-y-4">
                        <div>
                            <label className="label" htmlFor="phone">Phone number</label>
                            <input
                                id="phone"
                                className="input"
                                type="tel"
                                placeholder="+9477xxxxxxx"
                                value={phone}
                                onChange={(e) => setPhone(e.target.value)}
                                required
                                autoFocus
                            />
                        </div>

                        {error && <p className="text-sm text-red-600">{error}</p>}

                        <button className="btn btn-primary w-full" disabled={busy || !phone}>
                            {busy ? 'Sending…' : 'Send OTP'}
                        </button>
                    </form>
                )}

                {step === 'otp' && (
                    <form onSubmit={verifyOtp} className="space-y-4">
                        <div>
                            <label className="label" htmlFor="otp">OTP code</label>
                            <input
                                id="otp"
                                className="input tracking-widest text-center"
                                inputMode="numeric"
                                maxLength={6}
                                placeholder="000000"
                                value={otp}
                                onChange={(e) => setOtp(e.target.value)}
                                required
                                autoFocus
                            />
                            <p className="text-xs text-slate-500 mt-1">
                                Sent to {phone}.{' '}
                                <button
                                    type="button"
                                    className="underline hover:text-slate-700"
                                    onClick={() => {
                                        setStep('phone');
                                        setOtp('');
                                        setError(null);
                                    }}
                                >
                                    Change number
                                </button>
                            </p>
                        </div>

                        {error && <p className="text-sm text-red-600">{error}</p>}

                        <button className="btn btn-primary w-full" disabled={busy || otp.length < 4}>
                            {busy ? 'Verifying…' : 'Sign in'}
                        </button>
                    </form>
                )}

                <p className="text-xs text-slate-400 mt-6 text-center">
                    You must be granted the <code className="bg-slate-100 px-1 rounded">admin</code> role
                    to access this panel.
                </p>
            </div>
        </div>
    );
}
