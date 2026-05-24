import {describe, expect, it, vi} from 'vitest';
import {render, screen} from '@testing-library/react';
import {Sidebar} from '../sidebar';
import type {AccessibleResource} from '@/lib/admin/resource';

// next/navigation isn't wired up in the test env — stub the hooks the
// Sidebar (and its NavLink children) reach for so render() can complete.
vi.mock('next/navigation', () => ({
    usePathname: () => '/admin',
    useRouter: () => ({
        replace: vi.fn(),
        push: vi.fn(),
        back: vi.fn(),
        forward: vi.fn(),
        refresh: vi.fn(),
        prefetch: vi.fn(),
    }),
}));

describe('<Sidebar />', () => {
    it('renders the app name and dashboard link', () => {
        const resources: AccessibleResource[] = [];
        render(<Sidebar adminName="Test Admin" resources={resources}/>);

        expect(screen.getByText('LiveMapEvents')).toBeInTheDocument();
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.getByText(/Signed in as/)).toBeInTheDocument();
    });

    it('renders only resources the user can view', () => {
        const resources: AccessibleResource[] = [
            {
                route: 'users',
                label: 'User',
                label_plural: 'Users',
                permissions: {view: true, create: false, update: false, delete: false},
            },
            {
                route: 'events',
                label: 'Event',
                label_plural: 'Events',
                permissions: {view: false, create: false, update: false, delete: false},
            },
        ];

        render(<Sidebar resources={resources}/>);

        expect(screen.getByText('Users')).toBeInTheDocument();
        expect(screen.queryByText('Events')).not.toBeInTheDocument();
    });
});
