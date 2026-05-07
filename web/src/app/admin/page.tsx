'use client';

import {useQuery} from '@tanstack/react-query';
import {api} from '@/lib/api/client';

interface Health {
    service: string;
    time: string;
}

export default function DashboardPage() {
    const health = useQuery({
        queryKey: ['admin', 'health'],
        queryFn: () => api<Health>('/admin/v1/health'),
    });

    return (
        <div className="max-w-5xl">
            <h1 className="text-2xl font-semibold mb-2">Dashboard</h1>
            <p className="text-slate-500 mb-8">
                Overview of the LiveMapEvents data plane. Counts and live metrics will
                land here as resources come online.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="card p-5">
                    <div className="text-xs uppercase tracking-wide text-slate-500">API status</div>
                    <div className="text-lg font-semibold mt-1">
                        {health.isLoading
                            ? 'Checking…'
                            : health.error
                                ? <span className="text-red-600">Down</span>
                                : <span className="text-green-600">Online</span>}
                    </div>
                    {health.data && (
                        <div className="text-xs text-slate-500 mt-2">
                            Server time: {new Date(health.data.time).toLocaleString()}
                        </div>
                    )}
                </div>

                <div className="card p-5">
                    <div className="text-xs uppercase tracking-wide text-slate-500">Build</div>
                    <div className="text-lg font-semibold mt-1">Phase 1 + 2</div>
                    <div className="text-xs text-slate-500 mt-2">
                        Login + Users CRUD wired. More resources coming.
                    </div>
                </div>

                <div className="card p-5">
                    <div className="text-xs uppercase tracking-wide text-slate-500">Quick links</div>
                    <ul className="text-sm mt-1 space-y-1">
                        <li><a className="text-brand-600 hover:underline" href="/admin/users">Manage users →</a></li>
                        <li>
                            <a className="text-brand-600 hover:underline" href="/api/documentation" target="_blank"
                               rel="noreferrer">
                                Open Swagger →
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
