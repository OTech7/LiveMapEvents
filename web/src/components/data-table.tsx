'use client';

import {useRouter} from 'next/navigation';
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
    const router = useRouter();
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
                        const href = `/admin/${resource}/${key}`;
                        return (
                            <tr
                                key={String(key)}
                                role="link"
                                tabIndex={0}
                                onClick={(e) => {
                                    // Don't hijack the click if the user is selecting text
                                    // or interacting with a button/link inside the row.
                                    const sel = window.getSelection?.();
                                    if (sel && sel.toString().length > 0) return;
                                    if ((e.target as HTMLElement).closest('a,button,input,select,textarea')) return;
                                    // Cmd/Ctrl-click → open in new tab
                                    if (e.metaKey || e.ctrlKey) {
                                        window.open(href, '_blank', 'noopener,noreferrer');
                                        return;
                                    }
                                    router.push(href);
                                }}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') router.push(href);
                                }}
                                className="hover:bg-slate-50 cursor-pointer focus:outline-none focus:bg-slate-50"
                            >
                                {columns.map((c, idx) => {
                                    const cell = formatCellValue(row[c], c);
                                    // Style the first column distinctly so it still reads as
                                    // the row's identifier, but the click handler is on the
                                    // whole row — clicking anywhere navigates.
                                    if (idx === 0) {
                                        return (
                                            <td key={c} className="px-4 py-3 font-mono text-xs text-brand-600">
                                                {cell}
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
