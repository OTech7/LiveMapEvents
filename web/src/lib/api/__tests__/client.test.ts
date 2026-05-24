import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {api, ApiError} from '../client';

/**
 * These tests exercise the fetch wrapper directly. We stub `globalThis.fetch`
 * per-test so we don't talk to a real server, and stub `window.localStorage`
 * so the Authorization-header path is deterministic.
 */
describe('api() helper', () => {
    const realFetch = globalThis.fetch;

    beforeEach(() => {
        // Make sure each test starts with a clean token store
        try {
            window.localStorage.removeItem('livemap.admin.token');
        } catch {
            /* jsdom may not expose storage in some configs — ignore */
        }
    });

    afterEach(() => {
        globalThis.fetch = realFetch;
        vi.restoreAllMocks();
    });

    it('returns parsed JSON data on a 2xx response', async () => {
        const mockResponse = {
            success: true,
            message: 'ok',
            data: {id: 42, name: 'Test User'},
        };
        globalThis.fetch = vi.fn().mockResolvedValue(
            new Response(JSON.stringify(mockResponse), {
                status: 200,
                headers: {'Content-Type': 'application/json'},
            }),
        );

        const result = await api<{ id: number; name: string }>('/users/42');

        expect(result).toEqual({id: 42, name: 'Test User'});
        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        const [url, init] = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls[0];
        expect(url).toBe('/api/users/42');
        expect((init as RequestInit).headers).toMatchObject({Accept: 'application/json'});
    });

    it('throws ApiError with status and errors on a non-2xx response', async () => {
        const errorBody = {
            message: 'The given data was invalid.',
            errors: {email: ['The email field is required.']},
        };
        globalThis.fetch = vi.fn().mockResolvedValue(
            new Response(JSON.stringify(errorBody), {
                status: 422,
                headers: {'Content-Type': 'application/json'},
            }),
        );

        let thrown: unknown;
        try {
            await api('/users', {method: 'POST', body: {}});
        } catch (e) {
            thrown = e;
        }

        expect(thrown).toBeInstanceOf(ApiError);
        const err = thrown as ApiError;
        expect(err.status).toBe(422);
        expect(err.message).toBe('The given data was invalid.');
        expect(err.errors).toEqual({email: ['The email field is required.']});
    });

    it('includes Authorization header when token is in localStorage', async () => {
        window.localStorage.setItem('livemap.admin.token', 'tok_abc123');

        globalThis.fetch = vi.fn().mockResolvedValue(
            new Response(JSON.stringify({data: null}), {
                status: 200,
                headers: {'Content-Type': 'application/json'},
            }),
        );

        await api('/me');

        const [, init] = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls[0];
        const headers = (init as RequestInit).headers as Record<string, string>;
        expect(headers.Authorization).toBe('Bearer tok_abc123');
    });

    it('omits Authorization header when no token is stored', async () => {
        globalThis.fetch = vi.fn().mockResolvedValue(
            new Response(JSON.stringify({data: null}), {
                status: 200,
                headers: {'Content-Type': 'application/json'},
            }),
        );

        await api('/public');

        const [, init] = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls[0];
        const headers = (init as RequestInit).headers as Record<string, string>;
        expect(headers.Authorization).toBeUndefined();
    });
});
