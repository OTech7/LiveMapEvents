'use client';

import Link from 'next/link';
import {usePathname, useRouter} from 'next/navigation';
import {Calendar, LayoutDashboard, LogOut, MapPin, Tag, Users} from 'lucide-react';
import {clearToken} from '@/lib/auth/session';
import clsx from 'clsx';

const NAV = [
    {href: '/admin', label: 'Dashboard', icon: LayoutDashboard},
    {href: '/admin/users', label: 'Users', icon: Users},
    {href: '/admin/events', label: 'Events', icon: Calendar, disabled: true},
    {href: '/admin/pins', label: 'Pins', icon: MapPin, disabled: true},
    {href: '/admin/interests', label: 'Interests', icon: Tag, disabled: true},
];

export function Sidebar({adminName}: { adminName?: string }) {
    const pathname = usePathname();
    const router = useRouter();

    function logout() {
        clearToken();
        router.replace('/login');
    }

    return (
        <aside className="w-60 shrink-0 border-r border-slate-200 bg-white flex flex-col">
            <div className="px-5 py-4 border-b border-slate-200">
                <div className="text-sm font-semibold text-slate-900">LiveMapEvents</div>
                <div className="text-xs text-slate-500">Admin console</div>
            </div>

            <nav className="flex-1 p-3 space-y-1">
                {NAV.map((item) => {
                    const active =
                        item.href === '/admin'
                            ? pathname === '/admin'
                            : pathname?.startsWith(item.href);
                    const Icon = item.icon;
                    const inner = (
                        <div
                            className={clsx(
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm',
                                active
                                    ? 'bg-brand-50 text-brand-700 font-medium'
                                    : 'text-slate-600 hover:bg-slate-100',
                                item.disabled && 'opacity-40 cursor-not-allowed',
                            )}
                        >
                            <Icon size={16}/>
                            <span>{item.label}</span>
                            {item.disabled && (
                                <span className="ml-auto text-[10px] uppercase text-slate-400">soon</span>
                            )}
                        </div>
                    );
                    return item.disabled ? (
                        <div key={item.href}>{inner}</div>
                    ) : (
                        <Link key={item.href} href={item.href}>
                            {inner}
                        </Link>
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
