'use client';

import {useQuery} from '@tanstack/react-query';
import {api} from '@/lib/api/client';
import {AccessibleResource, ResourcePerms} from '@/lib/admin/resource';

export interface MeResponse {
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

const DENY: ResourcePerms = {view: false, create: false, update: false, delete: false};

/**
 * Read the cached /me payload. The AdminLayout fetches this on mount with
 * the same query key, so this is essentially free for any descendant
 * component — react-query dedupes.
 */
export function useMe() {
    return useQuery({
        queryKey: ['admin', 'me'],
        queryFn: () => api<MeResponse>('/admin/v1/me'),
    });
}

/** Per-resource permissions for the current user, default-deny if /me hasn't loaded. */
export function useResourcePerms(route: string): ResourcePerms {
    const me = useMe();
    if (!me.data) return DENY;
    const r = me.data.resources.find((x) => x.route === route);
    return r?.permissions ?? DENY;
}
