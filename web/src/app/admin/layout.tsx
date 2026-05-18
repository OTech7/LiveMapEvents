'use client';

import {useEffect, useState} from 'react';
import {useRouter} from 'next/navigation';
import {useQuery} from '@tanstack/react-query';
import {api, ApiError} from '@/lib/api/client';
import {clearToken, isAuthed} from '@/lib/auth/session';
import {Sidebar} from '@/components/sidebar';
import {AccessibleResource} from '@/lib/admin/resource';

interface MeResponse {
    user: {
        id: number;
        first_name?: string | null;
        last_name?: string | null;
        phone?: string | null;
    };
    roles: string[];
    permissions: string[];
    is_admin: boolean;
    has_panel_access: boolean;
    resources: AccessibleResource[];
}

export default function AdminLayout({children}: { children: React.ReactNode }) {
    const router = useRouter();
    const [ready, setReady] = useState(false);

    // Bounce to /login if there's no token at all (avoids showing the
    // shell for a frame before /me 401s).
    useEffect(() => {
        if (!isAuthed()) {
            router.replace('/login');
        } else {
            setReady(true);
        }
    }, [router]);

    const {data, isLoading, error} = useQuery({
        queryKey: ['admin', 'me'],
        queryFn: () => api<MeResponse>('/admin/v1/me'),
        enabled: ready,
    });

    // If /me 401'd, the api client cleared the token — kick to /login.
    useEffect(() => {
        if (error instanceof ApiError && error.status === 401) {
            router.replace('/login');
        }
    }, [error, router]);

    if (!ready || isLoading) {
        return (
            <div className="min-h-screen grid place-items-center text-slate-500">
                Loading admin…
            </div>
        );
    }

    // Logged in but no panel-access role — show a clean denial page.
    // (has_panel_access covers admin/super_admin/editor/viewer; we keep
    // is_admin around for future "only admins can do X" UI gates.)
    if (data && !data.has_panel_access) {
        return (
            <div className="min-h-screen grid place-items-center px-4">
                <div className="card max-w-md w-full p-8 text-center">
                    <h1 className="text-lg font-semibold mb-2">Not authorized</h1>
                    <p className="text-sm text-slate-600 mb-6">
                        Your account exists but doesn&apos;t have a panel role. Ask an
                        admin to assign you <code>admin</code>, <code>editor</code>, or{' '}
                        <code>viewer</code>.
                    </p>
                    <button
                        className="btn btn-ghost mx-auto"
                        onClick={() => {
                            clearToken();
                            router.replace('/login');
                        }}
                    >
                        Sign out
                    </button>
                </div>
            </div>
        );
    }

    const name = data?.user
        ? [data.user.first_name, data.user.last_name].filter(Boolean).join(' ')
        || data.user.phone || `User #${data.user.id}`
        : undefined;

    return (
        <div className="min-h-screen flex">
            <Sidebar adminName={name} resources={data?.resources ?? []}/>
            <main className="flex-1 p-8 overflow-y-auto">{children}</main>
        </div>
    );
}
