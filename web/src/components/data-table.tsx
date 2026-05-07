'use client';

import Link from 'next/link';
import {ChevronLeft, ChevronRight, Search} from 'lucide-react';
import {formatCellValue, ListMeta} from '@/lib/admin/resource';

interface DataTableProps<T extends Record<string, unknown>> {
    /** Schema list_columns — the column slugs to render. */
    columns: string[];
    /** Page payload from the API. */
    rows: T[] | undefined;
    meta: ListMeta | undefined;
    /** route slug — used to build /admin/<route>/<key> detail links */
    resource: string;
    /** which row column is the route key (id, slug, …) */
    routeKey: string;
    /** Header label override per column. */
    labels?: Record<string, string>;
    isLoading: boolean;
    isFetching: boolean;
    /** controls */
    q: string;
    onQ: (q: string) => void;
    page: number;
    onPage: (p: number) => void;
}

export function DataTable<T extends Record<string, unknown>>({
                                                                 columns, rows, meta, resource, routeKey, labels = {},
                                                                 isLoading, isFetching, q, onQ, page, onPage,
                                                             }: DataTableProps<T>) {
    return (
        <>
            <div className="flex items-center justify-end mb-4">
                <div className="relative">
                    <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                    <input
                        className="input pl-9 w-72"
                        placeholder="Search…"
                        value={q}
                        onChange={(e) => onQ(e.target.value)}
                    />
                </div>
            </div>

            <div className="card overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <tr>
                        {columns.map((c) => (
                            <th key={c} className="text-left px-4 py-3 font-medium">
                                {labels[c] ?? c.replace(/_/g, ' ')}
                            </th>
                        ))}
                    </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                    {isLoading && (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-8 text-center text-slate-500">Loading…</td>
                        </tr>
                    )}
                    {!isLoading && rows && rows.length === 0 && (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-8 text-center text-slate-500">No records.
                            </td>
                        </tr>
                    )}
                    {rows?.map((row, i) => {
                        const key = (row[routeKey] ?? row['id'] ?? i) as string | number;
                        return (
                            <tr key={String(key)} className="hover:bg-slate-50">
                                {columns.map((c, idx) => {
                                    const cell = formatCellValue(row[c], c);
                                    // Make the first column a link to the detail page so the
                                    // table is keyboard-navigable.
                                    if (idx === 0) {
                                        return (
                                            <td key={c} className="px-4 py-3">
                                                <Link
                                                    href={`/admin/${resource}/${key}`}
                                                    className="text-brand-600 hover:underline font-mono text-xs"
                                                >
                                                    {cell}
                                                </Link>
                                            </td>
                                        );
                                    }
                                    return (
                                        <td key={c} className="px-4 py-3 text-slate-700">{cell}</td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                    </tbody>
                </table>

                {meta && meta.total_pages > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50">
                        <div className="text-xs text-slate-500">
                            Page {meta.page} of {meta.total_pages} · {meta.total.toLocaleString()} total
                            {isFetching && ' · refreshing…'}
                        </div>
                        <div className="flex gap-2">
                            <button
                                className="btn btn-ghost"
                                disabled={page <= 1}
                                onClick={() => onPage(Math.max(1, page - 1))}
                            >
                                <ChevronLeft size={14}/> Prev
                            </button>
                            <button
                                className="btn btn-ghost"
                                disabled={page >= meta.total_pages}
                                onClick={() => onPage(page + 1)}
                            >
                                Next <ChevronRight size={14}/>
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
