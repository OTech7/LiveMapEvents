'use client';

import {useEffect, useMemo, useState} from 'react';
import Link from 'next/link';
import {useParams, useRouter} from 'next/navigation';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {ArrowLeft, Save, Trash2} from 'lucide-react';
import {createOne, defaultValueFor, deleteOne, fetchOne, fetchSchema, saveOne,} from '@/lib/admin/resource';
import {useResourcePerms} from '@/lib/admin/use-me';
import {ApiError} from '@/lib/api/client';
import {AutoForm} from '@/components/auto-form';

/**
 * Generic detail/edit page for any registered admin resource.
 * Path: /admin/<resource>/<id>   — edits an existing record
 *       /admin/<resource>/new    — creates a new one
 */
export default function ResourceDetailPage() {
    const params = useParams<{ resource: string; id: string }>();
    const router = useRouter();
    const qc = useQueryClient();

    const resource = params?.resource ?? '';
    const id = params?.id ?? '';
    const isNew = id === 'new';
    const perms = useResourcePerms(resource);
    const canSave = isNew ? perms.create : perms.update;

    const [form, setForm] = useState<Record<string, unknown>>({});
    const [flash, setFlash] = useState<string | null>(null);

    // 1. Schema — drives the form fields, labels, etc.
    const schemaQ = useQuery({
        queryKey: ['admin', 'schema', resource],
        queryFn: () => fetchSchema(resource),
        enabled: !!resource,
    });

    // 2. Existing record — only when editing.
    const recordQ = useQuery({
        queryKey: ['admin', 'one', resource, id],
        queryFn: () => fetchOne<Record<string, unknown>>(resource, id),
        enabled: !!resource && !!id && !isNew,
    });

    // Sync the form with whichever data source is authoritative right now.
    useEffect(() => {
        if (!schemaQ.data) return;
        if (isNew) {
            const blanks: Record<string, unknown> = {};
            schemaQ.data.fields.forEach((f) => {
                blanks[f.name] = defaultValueFor(f);
            });
            setForm(blanks);
        } else if (recordQ.data) {
            // Pull only the fields declared in the schema so we don't ship
            // server-only fields back on save.
            const next: Record<string, unknown> = {};
            schemaQ.data.fields.forEach((f) => {
                next[f.name] = (recordQ.data as Record<string, unknown>)[f.name] ?? defaultValueFor(f);
            });
            setForm(next);
        }
    }, [schemaQ.data, recordQ.data, isNew]);

    const save = useMutation({
        mutationFn: () =>
            isNew
                ? createOne(resource, form)
                : saveOne(resource, id, form),
        onSuccess: (data: any) => {
            setFlash(isNew ? 'Created.' : 'Saved.');
            qc.invalidateQueries({queryKey: ['admin', 'list', resource]});
            qc.invalidateQueries({queryKey: ['admin', 'one', resource]});
            setTimeout(() => setFlash(null), 2000);

            // After create, jump to the real id so refresh works.
            if (isNew && data && schemaQ.data) {
                const key = data[schemaQ.data.route_key];
                if (key !== undefined && key !== null) {
                    router.replace(`/admin/${resource}/${key}`);
                }
            }
        },
    });

    const del = useMutation({
        mutationFn: () => deleteOne(resource, id),
        onSuccess: () => {
            qc.invalidateQueries({queryKey: ['admin', 'list', resource]});
            router.replace(`/admin/${resource}`);
        },
    });

    // Display title — try common name-ish fields first, fall back to id.
    const title = useMemo(() => {
        if (isNew) return `New ${schemaQ.data?.label ?? resource}`;
        const r = recordQ.data as Record<string, unknown> | undefined;
        if (!r) return '…';
        const composed = [r.first_name, r.last_name].filter(Boolean).join(' ');
        return (composed
            || (r.name as string)
            || (r.title as string)
            || (r.phone as string)
            || `${schemaQ.data?.label ?? resource} #${id}`);
    }, [recordQ.data, schemaQ.data, isNew, id, resource]);

    if (schemaQ.error) {
        return <p className="text-red-600">Couldn&apos;t load resource: {(schemaQ.error as Error).message}</p>;
    }
    if (schemaQ.isLoading || (!isNew && recordQ.isLoading)) {
        return <p className="text-slate-500">Loading…</p>;
    }
    if (recordQ.error) {
        return <p className="text-red-600">{(recordQ.error as Error).message}</p>;
    }
    if (!schemaQ.data) return null;
    if (!isNew && !recordQ.data) return null;

    return (
        <div className="max-w-3xl">
            <Link
                href={`/admin/${resource}`}
                className="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900 mb-4"
            >
                <ArrowLeft size={14}/> All {schemaQ.data.label_plural.toLowerCase()}
            </Link>

            <div className="flex items-start justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    {!isNew && recordQ.data && (
                        <p className="text-slate-500 text-sm">
                            {(recordQ.data as any).created_at &&
                                <>created {new Date((recordQ.data as any).created_at).toLocaleString()}</>}
                        </p>
                    )}
                </div>

                {!isNew && schemaQ.data.can_delete && perms.delete && (
                    <button
                        className="btn btn-danger"
                        onClick={() => {
                            if (confirm(`Delete this ${schemaQ.data!.label.toLowerCase()}? This cannot be undone.`)) {
                                del.mutate();
                            }
                        }}
                        disabled={del.isPending}
                    >
                        <Trash2 size={14}/> {del.isPending ? 'Deleting…' : 'Delete'}
                    </button>
                )}
            </div>

            <form
                className="card p-6 space-y-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    save.mutate();
                }}
            >
                <AutoForm
                    fields={schemaQ.data.fields}
                    values={form}
                    onChange={(name, value) => setForm((f) => ({...f, [name]: value}))}
                    disabled={save.isPending || !canSave}
                />

                {save.error && <ErrorBlock err={save.error} fallback="Save failed"/>}
                {del.error && <ErrorBlock err={del.error} fallback="Delete failed"/>}
                {flash && <p className="text-sm text-green-600">{flash}</p>}

                {canSave && (
                    <div className="flex justify-end">
                        <button className="btn btn-primary" disabled={save.isPending} type="submit">
                            <Save size={14}/> {save.isPending ? 'Saving…' : (isNew ? 'Create' : 'Save changes')}
                        </button>
                    </div>
                )}
                {!canSave && !isNew && (
                    <p className="text-xs text-slate-400 italic">
                        You have read-only access to this resource.
                    </p>
                )}
            </form>
        </div>
    );
}

function ErrorBlock({err, fallback}: { err: unknown; fallback: string }) {
    const isApi = err instanceof ApiError;
    return (
        <div className="text-sm text-red-600 space-y-1">
            <p>{isApi ? err.message : fallback}</p>
            {isApi && err.errors && typeof err.errors === 'object' && (
                <ul className="list-disc list-inside text-xs">
                    {Object.entries(err.errors as Record<string, string[] | string>).map(([field, msgs]) => (
                        <li key={field}>
                            <code>{field}</code>: {Array.isArray(msgs) ? msgs.join(', ') : String(msgs)}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
