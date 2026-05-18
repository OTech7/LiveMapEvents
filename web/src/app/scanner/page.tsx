'use client';

import {useEffect, useRef, useState} from 'react';
import {api, ApiError} from '@/lib/api/client';
import {getToken} from '@/lib/auth/session';
import {CheckCircle, Loader2, QrCode, XCircle} from 'lucide-react';

// ── Types ────────────────────────────────────────────────────────────────────

type ScanState =
    | { phase: 'idle' }
    | { phase: 'scanning' }
    | { phase: 'loading' }
    | { phase: 'success'; promotionTitle: string; discountType: string; discountValue: number; userName: string }
    | { phase: 'error'; message: string };

interface RedeemResponse {
    voucher_code: string;
    status: string;
    promotion: { title: string; discount_type: string; discount_value: number };
    user: { name: string };
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function ScannerPage() {
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const detectRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const [state, setState] = useState<ScanState>({phase: 'idle'});
    const [manualCode, setManualCode] = useState('');
    const [cameraSupported, setCameraSupported] = useState(true);
    const [authed, setAuthed] = useState(true);

    // Check auth on mount
    useEffect(() => {
        if (!getToken()) setAuthed(false);
    }, []);

    // Auto-start camera on mount
    useEffect(() => {
        startCamera();
        return () => stopCamera();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {facingMode: 'environment'},
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.play();
            }
            setState({phase: 'scanning'});
            startDetection();
        } catch {
            setCameraSupported(false);
            setState({phase: 'idle'});
        }
    }

    function stopCamera() {
        if (detectRef.current) clearInterval(detectRef.current);
        streamRef.current?.getTracks().forEach(t => t.stop());
        streamRef.current = null;
    }

    function startDetection() {
        // BarcodeDetector is a native Chrome/Android API — no library needed.
        if (typeof (window as any).BarcodeDetector === 'undefined') {
            setCameraSupported(false);
            return;
        }

        const detector = new (window as any).BarcodeDetector({formats: ['qr_code']});

        detectRef.current = setInterval(async () => {
            if (!videoRef.current || videoRef.current.readyState < 2) return;
            try {
                const barcodes = await detector.detect(videoRef.current);
                if (barcodes.length > 0) {
                    const code = barcodes[0].rawValue as string;
                    if (detectRef.current) clearInterval(detectRef.current);
                    await redeem(code);
                }
            } catch {
                // detection frame failed — keep trying
            }
        }, 300);
    }

    async function redeem(code: string) {
        setState({phase: 'loading'});
        try {
            const data = await api<RedeemResponse>('/v1/business/scanner/redeem', {
                method: 'POST',
                body: {voucher_code: code.trim().toUpperCase()},
            });
            setState({
                phase: 'success',
                promotionTitle: data.promotion.title,
                discountType: data.promotion.discount_type,
                discountValue: data.promotion.discount_value,
                userName: data.user.name,
            });
        } catch (err) {
            const msg = err instanceof ApiError ? err.message : 'Something went wrong.';
            setState({phase: 'error', message: msg});
        }

        // Auto-reset after 4 seconds so staff can scan the next customer
        setTimeout(() => {
            setState({phase: 'scanning'});
            startDetection();
        }, 4000);
    }

    async function submitManual(e: React.FormEvent) {
        e.preventDefault();
        if (!manualCode.trim()) return;
        const code = manualCode.trim();
        setManualCode('');
        await redeem(code);
    }

    // ── Not authenticated ────────────────────────────────────────────────────
    if (!authed) {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center p-6">
                <div className="text-center text-white space-y-3">
                    <XCircle size={48} className="mx-auto text-red-400"/>
                    <p className="text-lg font-semibold">Not signed in</p>
                    <p className="text-slate-400 text-sm">Please log into the admin panel first, then come back to this
                        page.</p>
                    <a href="/login"
                       className="inline-block mt-4 px-6 py-2 bg-white text-slate-900 rounded-lg text-sm font-medium">
                        Go to login
                    </a>
                </div>
            </div>
        );
    }

    // ── Result overlay (success) ─────────────────────────────────────────────
    if (state.phase === 'success') {
        const discount = state.discountType === 'percentage'
            ? `${state.discountValue}% off`
            : `${state.discountValue} off`;
        return (
            <div
                className="min-h-screen bg-green-500 flex flex-col items-center justify-center p-8 text-white text-center">
                <CheckCircle size={96} className="mb-6"/>
                <p className="text-3xl font-bold mb-2">Valid ✓</p>
                <p className="text-xl font-semibold mb-1">{discount}</p>
                <p className="text-lg mb-1">{state.promotionTitle}</p>
                <p className="text-green-100 text-sm">Customer: {state.userName}</p>
                <p className="mt-8 text-green-200 text-xs">Resetting in 4 seconds…</p>
            </div>
        );
    }

    // ── Result overlay (error) ───────────────────────────────────────────────
    if (state.phase === 'error') {
        return (
            <div
                className="min-h-screen bg-red-500 flex flex-col items-center justify-center p-8 text-white text-center">
                <XCircle size={96} className="mb-6"/>
                <p className="text-3xl font-bold mb-4">Invalid ✗</p>
                <p className="text-lg">{state.message}</p>
                <p className="mt-8 text-red-200 text-xs">Resetting in 4 seconds…</p>
            </div>
        );
    }

    // ── Loading ──────────────────────────────────────────────────────────────
    if (state.phase === 'loading') {
        return (
            <div className="min-h-screen bg-slate-900 flex flex-col items-center justify-center text-white">
                <Loader2 size={64} className="animate-spin mb-4"/>
                <p className="text-lg">Checking voucher…</p>
            </div>
        );
    }

    // ── Scanner / idle ───────────────────────────────────────────────────────
    return (
        <div className="min-h-screen bg-slate-900 flex flex-col">

            {/* Header */}
            <div className="px-5 py-4 flex items-center gap-3 border-b border-slate-700">
                <QrCode size={22} className="text-white"/>
                <span className="text-white font-semibold">Voucher Scanner</span>
            </div>

            {/* Camera viewfinder */}
            {cameraSupported ? (
                <div className="relative flex-1 bg-black">
                    <video
                        ref={videoRef}
                        className="w-full h-full object-cover"
                        playsInline
                        muted
                    />
                    {/* Targeting reticle */}
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div className="w-56 h-56 border-4 border-white rounded-2xl opacity-70"/>
                    </div>
                    <p className="absolute bottom-6 left-0 right-0 text-center text-white text-sm opacity-80">
                        Point at the customer&apos;s QR code
                    </p>
                </div>
            ) : (
                <div className="flex-1 flex items-center justify-center bg-slate-800">
                    <div className="text-center text-slate-400 px-6">
                        <QrCode size={48} className="mx-auto mb-3 opacity-40"/>
                        <p className="text-sm">Camera not available on this browser.</p>
                        <p className="text-xs mt-1">Use the code entry below.</p>
                    </div>
                </div>
            )}

            {/* Manual code entry fallback */}
            <div className="bg-slate-800 p-5 border-t border-slate-700">
                <p className="text-slate-400 text-xs mb-3 text-center uppercase tracking-wider">
                    Or enter code manually
                </p>
                <form onSubmit={submitManual} className="flex gap-3">
                    <input
                        type="text"
                        value={manualCode}
                        onChange={e => setManualCode(e.target.value.toUpperCase())}
                        placeholder="ABCD1234"
                        maxLength={12}
                        className="flex-1 bg-slate-700 text-white placeholder-slate-500 rounded-lg px-4 py-3 text-sm font-mono tracking-widest uppercase outline-none focus:ring-2 focus:ring-white/30"
                    />
                    <button
                        type="submit"
                        disabled={!manualCode.trim()}
                        className="px-5 py-3 bg-white text-slate-900 rounded-lg text-sm font-semibold disabled:opacity-40"
                    >
                        Check
                    </button>
                </form>
            </div>
        </div>
    );
}
