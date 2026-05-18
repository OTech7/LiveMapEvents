'use client';

import {useEffect, useState} from 'react';
import Link from 'next/link';
import {useParams} from 'next/navigation';
import {keepPreviousData, useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {Plus} from 'lucide-react';
import {fetchList, fetchSchema, saveOne} from '@/lib/admin/resource';
import {useResourcePerms} from '@/lib/admin/use-me';
import {DataTable} from '@/components/data-table';

/**
 * Generic list page for any registered admin resource.
 * Drives itself entirely off /admin/v1/<resource>/schema — no per-resource
 * code lives here. Customisation belongs in the AdminResource subclass on
 * the backend (or as an explicit override file in this folder later).
 */
export default function ResourceListPage() {
    const params = useParams<{ resource: string }>();
    const resource = params?.resource ?? '';
    const perms = useResourcePerms(resource);
    const queryClient = useQueryClient();

    const [q, setQ] = useState('');
    const [debouncedQ, setDebouncedQ] = useState('');
    const [page, setPage] = useState(1);

    // One-field PATCH — fires when a toggle switch is clicked in the table.
    const toggleMutation = useMutation({
        mutationFn: ({key, column, value}: { key: string | number; column: string; value: boolean }) =>
            saveOne(resource, key, {[column]: value}),
        onSuccess: () => {
            // Refresh the current list page so the switch reflects the saved state.
            queryClient.invalidateQueries({queryKey: ['admin', 'list', resource]});
        },
    });

    // Debounce typing so each keystroke doesn't fire a network call.
    useEffect(() => {
        const t = setTimeout(() => {
            setDebouncedQ(q);
            setPage(1);
        }, 250);
        return () => clearTimeout(t);
    }, [q]);

    const schemaQ = useQuery({
        queryKey: ['admin', 'schema', resource],
        queryFn: () => fetchSchema(resource),
        enabled: !!resource,
    });

    const listQ = useQuery({
        queryKey: ['admin', 'list', resource, {q: debouncedQ, page}],
        queryFn: () => fetchList(resource, {q: debouncedQ, page, per_page: 25}),
        enabled: !!resource,
        placeholderData: keepPreviousData,
    });

    if (!resource) return null;

    if (schemaQ.error) {
        return (
            <p className="text-red-600">
                Couldn&apos;t load the <code>{resource}</code> resource: {(schemaQ.error as Error).message}
            </p>
        );
    }

    const schema = schemaQ.data;

    return (
        <div className="max-w-6xl">
            <div className="flex items-start justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {schema?.label_plural ?? resource}
                    </h1>
                    <p className="text-slate-500 text-sm">
                        {listQ.data
                            ? `${listQ.data.meta.total.toLocaleString()} total`
                            : (schemaQ.isLoading ? 'Loading schema…' : 'Loading…')}
                    </p>
                </div>

                {schema?.can_create && perms.create && (
                    <Link href={`/admin/${resource}/new`} className="btn btn-primary">
                        <Plus size={14}/> New {schema.label}
                    </Link>
                )}
            </div>

            {schema && (
                <DataTable
                    columns={schema.list_columns}
                    rows={listQ.data?.items}
                    meta={listQ.data?.meta}
                    resource={schema.route}
                    routeKey={schema.route_key}
                    isLoading={listQ.isLoading || schemaQ.isLoading}
                    isFetching={listQ.isFetching}
                    q={q}
                    onQ={setQ}
                    page={page}
                    onPage={setPage}
                    toggleColumns={['is_active']}
                    onToggle={(key, column, value) =>
                        toggleMutation.mutate({key, column, value})
                    }
                />
            )}
        </div>
    );
}
