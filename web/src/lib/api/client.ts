/**
 * Thin fetch wrapper for the Laravel API.
 *
 * All paths are relative to `/api` — Next.js rewrites in next.config.js
 * proxy that to the actual backend (NEXT_PUBLIC_API_BASE_URL). That keeps
 * the browser origin-locked to localhost:3000 in dev and avoids CORS.
 */
import {clearToken, getToken} from '@/lib/auth/session';

export class ApiError extends Error {
    status: number;
    errors: unknown;

    constructor(message: string, status: number, errors: unknown = null) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

type Options = Omit<RequestInit, 'body'> & {
    body?: unknown;
    query?: Record<string, string | number | boolean | undefined | null>;
};

function buildQuery(query?: Options['query']): string {
    if (!query) return '';
    const params = new URLSearchParams();
    Object.entries(query).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') params.set(k, String(v));
    });
    const s = params.toString();
    return s ? `?${s}` : '';
}

export async function api<T = unknown>(path: string, opts: Options = {}): Promise<T> {
    const token = getToken();

    const headers: Record<string, string> = {
        Accept: 'application/json',
        ...(opts.body ? {'Content-Type': 'application/json'} : {}),
        ...(token ? {Authorization: `Bearer ${token}`} : {}),
        ...((opts.headers as Record<string, string>) ?? {}),
    };

    const url = `/api${path}${buildQuery(opts.query)}`;

    const res = await fetch(url, {
        ...opts,
        headers,
        body: opts.body ? JSON.stringify(opts.body) : undefined,
        cache: 'no-store',
    });

    // 401 → token is bad; nuke it and let the page redirect to /login.
    if (res.status === 401) {
        clearToken();
    }

    let payload: any = null;
    const text = await res.text();
    try {
        payload = text ? JSON.parse(text) : null;
    } catch {
        /* non-JSON response — leave payload null */
    }

    if (!res.ok) {
        throw new ApiError(
            payload?.message ?? `Request failed (${res.status})`,
            res.status,
            payload?.errors ?? null,
        );
    }

    // Laravel ApiResponse envelope: { success, message, data, errors }
    return (payload?.data ?? payload) as T;
}
