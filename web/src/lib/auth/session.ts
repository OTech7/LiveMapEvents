/**
 * Tiny session helper. Stores the Sanctum bearer token in localStorage
 * (admin panel is not a public-facing app — XSS surface is small and we
 * use Bearer auth, not cookies, so CSRF is a non-issue).
 *
 * If we ever want stronger isolation, switch to httpOnly cookies via
 * Laravel Sanctum's SPA mode and remove this file.
 */

const TOKEN_KEY = 'livemap.admin.token';

export function getToken(): string | null {
    if (typeof window === 'undefined') return null;
    try {
        return window.localStorage.getItem(TOKEN_KEY);
    } catch {
        return null;
    }
}

export function setToken(token: string): void {
    if (typeof window === 'undefined') return;
    window.localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(TOKEN_KEY);
}

export function isAuthed(): boolean {
    return !!getToken();
}
