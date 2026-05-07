'use client';

import {useEffect} from 'react';
import {useRouter} from 'next/navigation';
import {isAuthed} from '@/lib/auth/session';

// Root route — bounce to /admin if logged in, /login otherwise.
export default function HomePage() {
    const router = useRouter();

    useEffect(() => {
        router.replace(isAuthed() ? '/admin' : '/login');
    }, [router]);

    return (
        <div className="min-h-screen grid place-items-center text-slate-500">
            Loading…
        </div>
    );
}
