'use client';

import Link from 'next/link';
import {usePathname, useRouter} from 'next/navigation';
import {Box, Calendar, LayoutDashboard, LogOut, MapPin, Tag, Ticket, Users,} from 'lucide-react';
import {clearToken} from '@/lib/auth/session';
import clsx from 'clsx';
import {AccessibleResource} from '@/lib/admin/resource';

interface SidebarProps {
    adminName?: string;
    resources: AccessibleResource[];
}

/** Resource route → icon. New resources just need a row added. */
const ICONS: Record<string, React.ComponentType<{ size?: number }>> = {
    users: Users,
    events: Calendar,
    pins: MapPin,
    interests: Tag,
    vouchers: Ticket,
};

const FALLBACK_ICON = Box;

export function Sidebar({adminName, resources}: SidebarProps) {
    const pathname = usePathname();
    const router = useRouter();

    function logout() {
        clearToken();
        router.replace('/login');
    }

    // Filter to resources the user can at least view, then sort by the order
    // they were registered (the API returns them in registration order).
    const visible = resources.filter((r) => r.permissions.view);

    return (
        <aside className="w-60 shrink-0 border-r border-slate-200 bg-white flex flex-col">
            <div className="px-5 py-4 border-b border-slate-200">
                <div className="text-sm font-semibold text-slate-900">LiveMapEvents</div>
                <div className="text-xs text-slate-500">Admin console</div>
            </div>

            <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
                <NavLink
                    href="/admin"
                    label="Dashboard"
                    Icon={LayoutDashboard}
                    active={pathname === '/admin'}
                />

                {visible.length > 0 && (
                    <div className="pt-3 pb-1 px-3 text-[10px] uppercase tracking-wider text-slate-400">
                        Resources
                    </div>
                )}

                {visible.map((r) => {
                    const Icon = ICONS[r.route] ?? FALLBACK_ICON;
                    const href = `/admin/${r.route}`;
                    const active = pathname?.startsWith(href);
                    return (
                        <NavLink
                            key={r.route}
                            href={href}
                            label={r.label_plural}
                            Icon={Icon}
                            active={!!active}
                        />
                    );
                })}
            </nav>

            <div className="p-3 border-t border-slate-200">
                {adminName && (
                    <div className="text-xs text-slate-500 px-3 mb-2 truncate">
                        Signed in as <span className="text-slate-800">{adminName}</span>
                    </div>
                )}
                <button
                    onClick={logout}
                    className="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100"
                >
                    <LogOut size={16}/>
                    Log out
                </button>
            </div>
        </aside>
    );
}

function NavLink({
                     href, label, Icon, active,
                 }: {
    href: string;
    label: string;
    Icon: React.ComponentType<{ size?: number }>;
    active: boolean;
}) {
    return (
        <Link href={href}>
            <div
                className={clsx(
                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm',
                    active
                        ? 'bg-brand-50 text-brand-700 font-medium'
                        : 'text-slate-600 hover:bg-slate-100',
                )}
            >
                <Icon size={16}/>
                <span>{label}</span>
            </div>
        </Link>
    );
}
