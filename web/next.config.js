/** @type {import('next').NextConfig} */
const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000';

const nextConfig = {
    reactStrictMode: true,

    // Proxy /api/* in dev so the browser only ever talks to localhost:3000.
    // This sidesteps CORS in dev and lets us drop the API_BASE env into a
    // single place at build time. In production we'd hit the API directly
    // (https://api.live-events-map.tech) and CORS is configured server-side.
    async rewrites() {
        return [
            {
                source: '/api/:path*',
                destination: `${API_BASE}/api/:path*`,
            },
        ];
    },
};

module.exports = nextConfig;
