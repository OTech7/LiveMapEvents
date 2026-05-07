'use client';

import {useState} from 'react';
import Link from 'next/link';
import {keepPreviousData, useQuery} from '@tanstack/react-query';
import {api} from '@/lib/api/client';
import {ChevronLeft, ChevronRight, Search} from 'lucide-react';

interface AdminUser {
    id: number;
    phone: string | null;
    first_name: string | null;
    last_name: string | null;
    user_type: string | null;
    profile_complete: boolean;
    roles?: string[];
    created_at: string | null;
}

interface UsersPage {
    items: AdminUser[];
    meta: { page: number; per_page: number; total: number; total_pages: number };
}

export default function UsersListPage() {
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);

    const {data, isLoading, isFetching} = useQuery({
        queryKey: ['admin', 'users', {q, page}],
        queryFn: () => api<UsersPage>('/admin/v1/users', {
            query: {q, page, per_page: 25, sort: '-created_at'},
        }),
        placeholderData: keepPreviousData,
    });

    return (
        <div className="max-w-6xl">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-semibold">Users</h1>
                    <p className="text-slate-500 text-sm">
                        {data ? `${data.meta.total.toLocaleString()} total` : 'Loading…'}
                    </p>
                </div>

                <div className="relative">
                    <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                    <input
                        className="input pl-9 w-72"
                        placeholder="Search phone or name…"
                        value={q}
                        onChange={(e) => {
                            setQ(e.target.value);
                            setPage(1);
                        }}
                    />
                </div>
            </div>

            <div className="card overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th className="text-left px-4 py-3 font-medium">ID</th>
                        <th className="text-left px-4 py-3 font-medium">Phone</th>
                        <th className="text-left px-4 py-3 font-medium">Name</th>
                        <th className="text-left px-4 py-3 font-medium">Type</th>
                        <th className="text-left px-4 py-3 font-medium">Profile</th>
                        <th className="text-left px-4 py-3 font-medium">Roles</th>
                        <th className="text-left px-4 py-3 font-medium">Created</th>
                    </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                    {isLoading && (
                        <tr>
                            <td colSpan={7} className="px-4 py-8 text-center text-slate-500">Loading…</td>
                        </tr>
                    )}
                    {!isLoading && data?.items.length === 0 && (
                        <tr>
                            <td colSpan={7} className="px-4 py-8 text-center text-slate-500">No users.</td>
                        </tr>
                    )}
                    {data?.items.map((u) => (
                        <tr key={u.id} className="hover:bg-slate-50">
                            <td className="px-4 py-3 font-mono text-xs text-slate-500">{u.id}</td>
                            <td className="px-4 py-3">{u.phone ?? '—'}</td>
                            <td className="px-4 py-3">
                                <Link href={`/admin/users/${u.id}`} className="text-brand-600 hover:underline">
                                    {[u.first_name, u.last_name].filter(Boolean).join(' ') || '—'}
                                </Link>
                            </td>
                            <td className="px-4 py-3 text-slate-600">{u.user_type ?? '—'}</td>
                            <td className="px-4 py-3">
                                {u.profile_complete
                                    ? <span
                                        className="text-green-700 bg-green-50 px-2 py-0.5 rounded text-xs">complete</span>
                                    : <span
                                        className="text-slate-500 bg-slate-100 px-2 py-0.5 rounded text-xs">incomplete</span>}
                            </td>
                            <td className="px-4 py-3 text-xs">
                                {u.roles && u.roles.length > 0
                                    ? u.roles.map((r) => (
                                        <span key={r}
                                              className="bg-brand-50 text-brand-700 px-2 py-0.5 rounded mr-1">{r}</span>
                                    ))
                                    : <span className="text-slate-400">—</span>}
                            </td>
                            <td className="px-4 py-3 text-slate-500 text-xs">
                                {u.created_at ? new Date(u.created_at).toLocaleDateString() : '—'}
                            </td>
                        </tr>
                    ))}
                    </tbody>
                </table>

                {data && data.meta.total_pages > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50">
                        <div className="text-xs text-slate-500">
                            Page {data.meta.page} of {data.meta.total_pages}
                            {isFetching && ' · refreshing…'}
                        </div>
                        <div className="flex gap-2">
                            <button
                                className="btn btn-ghost"
                                disabled={page <= 1}
                                onClick={() => setPage((p) => Math.max(1, p - 1))}
                            >
                                <ChevronLeft size={14}/> Prev
                            </button>
                            <button
                                className="btn btn-ghost"
                                disabled={page >= data.meta.total_pages}
                                onClick={() => setPage((p) => p + 1)}
                            >
                                Next <ChevronRight size={14}/>
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
