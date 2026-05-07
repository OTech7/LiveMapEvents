'use client';

import {useEffect, useState} from 'react';
import Link from 'next/link';
import {useParams, useRouter} from 'next/navigation';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {api, ApiError} from '@/lib/api/client';
import {ArrowLeft, Save, Trash2} from 'lucide-react';

interface AdminUser {
    id: number;
    phone: string | null;
    google_id: string | null;
    first_name: string | null;
    last_name: string | null;
    avatar_url: string | null;
    dob: string | null;
    gender: string | null;
    user_type: string | null;
    profile_complete: boolean;
    roles?: string[];
    created_at: string | null;
    updated_at: string | null;
}

const ALL_ROLES = ['admin', 'super_admin', 'editor', 'viewer'];

export default function UserDetailPage() {
    const params = useParams<{ id: string }>();
    const router = useRouter();
    const qc = useQueryClient();
    const id = params?.id;

    const {data, isLoading, error} = useQuery({
        queryKey: ['admin', 'user', id],
        queryFn: () => api<AdminUser>(`/admin/v1/users/${id}`),
        enabled: !!id,
    });

    const [form, setForm] = useState<Partial<AdminUser>>({});
    const [roles, setRoles] = useState<string[]>([]);
    const [flash, setFlash] = useState<string | null>(null);

    useEffect(() => {
        if (data) {
            setForm({
                first_name: data.first_name,
                last_name: data.last_name,
                phone: data.phone,
                gender: data.gender,
                dob: data.dob,
                user_type: data.user_type,
                profile_complete: data.profile_complete,
            });
            setRoles(data.roles ?? []);
        }
    }, [data]);

    const save = useMutation({
        mutationFn: () => api<AdminUser>(`/admin/v1/users/${id}`, {
            method: 'PUT',
            body: {...form, roles},
        }),
        onSuccess: () => {
            setFlash('Saved.');
            qc.invalidateQueries({queryKey: ['admin', 'user', id]});
            qc.invalidateQueries({queryKey: ['admin', 'users']});
            setTimeout(() => setFlash(null), 2000);
        },
    });

    const del = useMutation({
        mutationFn: () => api(`/admin/v1/users/${id}`, {method: 'DELETE'}),
        onSuccess: () => {
            qc.invalidateQueries({queryKey: ['admin', 'users']});
            router.replace('/admin/users');
        },
    });

    function update<K extends keyof AdminUser>(k: K, v: AdminUser[K]) {
        setForm((f) => ({...f, [k]: v}));
    }

    function toggleRole(role: string) {
        setRoles((rs) => rs.includes(role) ? rs.filter((r) => r !== role) : [...rs, role]);
    }

    if (isLoading) return <p className="text-slate-500">Loading user…</p>;
    if (error) return <p className="text-red-600">{(error as Error).message}</p>;
    if (!data) return null;

    return (
        <div className="max-w-3xl">
            <Link href="/admin/users"
                  className="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900 mb-4">
                <ArrowLeft size={14}/> All users
            </Link>

            <div className="flex items-start justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {[data.first_name, data.last_name].filter(Boolean).join(' ') || `User #${data.id}`}
                    </h1>
                    <p className="text-slate-500 text-sm">
                        {data.phone ?? data.google_id ?? '—'}
                        {' · '}created {data.created_at ? new Date(data.created_at).toLocaleString() : '—'}
                    </p>
                </div>

                <button
                    className="btn btn-danger"
                    onClick={() => {
                        if (confirm(`Delete user #${data.id}? This cannot be undone.`)) {
                            del.mutate();
                        }
                    }}
                    disabled={del.isPending}
                >
                    <Trash2 size={14}/> {del.isPending ? 'Deleting…' : 'Delete'}
                </button>
            </div>

            <form
                className="card p-6 space-y-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    save.mutate();
                }}
            >
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="label">First name</label>
                        <input className="input" value={form.first_name ?? ''}
                               onChange={(e) => update('first_name', e.target.value)}/>
                    </div>
                    <div>
                        <label className="label">Last name</label>
                        <input className="input" value={form.last_name ?? ''}
                               onChange={(e) => update('last_name', e.target.value)}/>
                    </div>
                    <div>
                        <label className="label">Phone</label>
                        <input className="input" value={form.phone ?? ''}
                               onChange={(e) => update('phone', e.target.value)}/>
                    </div>
                    <div>
                        <label className="label">Gender</label>
                        <select className="input" value={form.gender ?? ''}
                                onChange={(e) => update('gender', e.target.value as any)}>
                            <option value="">—</option>
                            <option value="male">male</option>
                            <option value="female">female</option>
                        </select>
                    </div>
                    <div>
                        <label className="label">Date of birth</label>
                        <input type="date" className="input" value={form.dob ?? ''}
                               onChange={(e) => update('dob', e.target.value)}/>
                    </div>
                    <div>
                        <label className="label">User type</label>
                        <select className="input" value={form.user_type ?? ''}
                                onChange={(e) => update('user_type', e.target.value as any)}>
                            <option value="">—</option>
                            <option value="attendee">attendee</option>
                            <option value="business">business</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={!!form.profile_complete}
                        onChange={(e) => update('profile_complete', e.target.checked)}
                    />
                    Profile complete
                </label>

                <div>
                    <label className="label">Roles</label>
                    <div className="flex flex-wrap gap-2">
                        {ALL_ROLES.map((r) => (
                            <button
                                key={r}
                                type="button"
                                onClick={() => toggleRole(r)}
                                className={
                                    'px-3 py-1 rounded-full text-xs border ' +
                                    (roles.includes(r)
                                        ? 'bg-brand-600 text-white border-brand-600'
                                        : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50')
                                }
                            >
                                {r}
                            </button>
                        ))}
                    </div>
                    <p className="text-xs text-slate-400 mt-2">
                        Assigning <code>admin</code> grants this user access to this panel.
                    </p>
                </div>

                {save.error && (
                    <p className="text-sm text-red-600">
                        {save.error instanceof ApiError ? save.error.message : 'Save failed'}
                    </p>
                )}
                {flash && <p className="text-sm text-green-600">{flash}</p>}

                <div className="flex justify-end">
                    <button className="btn btn-primary" disabled={save.isPending}>
                        <Save size={14}/> {save.isPending ? 'Saving…' : 'Save changes'}
                    </button>
                </div>
            </form>
        </div>
    );
}
