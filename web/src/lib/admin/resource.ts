/**
 * Type definitions and helpers for the generic admin resource engine.
 * Mirrors what App\Modules\Admin\AdminResource::schema() returns.
 */
import {api} from '@/lib/api/client';

export type FieldType =
    | 'text'
    | 'textarea'
    | 'select'
    | 'multi-select'   // toggle-grid: every option visible as a chip, click to toggle
    | 'tag-picker'     // pivot-style: shows only the currently-assigned options as
    // removable chips, plus a dropdown of unassigned options to add
    | 'date'
    | 'checkbox';

export interface FieldOption {
    value: string;
    label: string;
}

export interface ResourceField {
    name: string;
    label: string;
    type: FieldType;
    options?: FieldOption[];
    helperText?: string;
    required?: boolean;
    readonly?: boolean;
}

export interface ResourceSchema {
    route: string;
    label: string;
    label_plural: string;
    permission: string;
    route_key: string;
    list_columns: string[];
    searchable: string[];
    sortable: string[];
    default_sort: string;
    fields: ResourceField[];
    can_create: boolean;
    can_delete: boolean;
}

export interface ResourcePerms {
    view: boolean;
    create: boolean;
    update: boolean;
    delete: boolean;
}

export interface AccessibleResource {
    route: string;
    label: string;
    label_plural: string;
    permissions: ResourcePerms;
}

export interface ListMeta {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
}

export interface ListResponse<T = Record<string, unknown>> {
    items: T[];
    meta: ListMeta;
}

export async function fetchSchema(route: string): Promise<ResourceSchema> {
    return api<ResourceSchema>(`/admin/v1/${route}/schema`);
}

export async function fetchList<T = Record<string, unknown>>(
    route: string,
    query: { q?: string; page?: number; per_page?: number; sort?: string },
): Promise<ListResponse<T>> {
    return api<ListResponse<T>>(`/admin/v1/${route}`, {query});
}

export async function fetchOne<T = Record<string, unknown>>(
    route: string,
    key: string | number,
): Promise<T> {
    return api<T>(`/admin/v1/${route}/${key}`);
}

export async function saveOne<T = Record<string, unknown>>(
    route: string,
    key: string | number,
    body: Record<string, unknown>,
): Promise<T> {
    return api<T>(`/admin/v1/${route}/${key}`, {method: 'PUT', body});
}

export async function createOne<T = Record<string, unknown>>(
    route: string,
    body: Record<string, unknown>,
): Promise<T> {
    return api<T>(`/admin/v1/${route}`, {method: 'POST', body});
}

export async function deleteOne(
    route: string,
    key: string | number,
): Promise<void> {
    await api(`/admin/v1/${route}/${key}`, {method: 'DELETE'});
}

/**
 * Best-effort default for an empty form value, given the field type.
 * Used when navigating to /new — prevents React "switching from
 * uncontrolled to controlled" warnings.
 */
export function defaultValueFor(field: ResourceField): unknown {
    switch (field.type) {
        case 'checkbox':
            return false;
        case 'multi-select':
            return [];
        default:
            return '';
    }
}

/** Format a value for the list view based on the column name and type guess. */
export function formatCellValue(value: unknown, column: string): string {
    if (value === null || value === undefined || value === '') return '—';

    if (Array.isArray(value)) {
        if (value.length === 0) return '—';
        return value.join(', ');
    }

    // Dates — both ISO strings and Date-parseable strings
    if (typeof value === 'string' && (column.endsWith('_at') || column === 'dob')) {
        const d = new Date(value);
        if (!isNaN(d.valueOf())) {
            return column === 'dob' ? d.toLocaleDateString() : d.toLocaleString();
        }
    }

    if (typeof value === 'boolean') return value ? '✓' : '—';

    return String(value);
}
