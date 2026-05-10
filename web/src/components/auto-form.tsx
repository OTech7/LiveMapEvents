'use client';

import {X} from 'lucide-react';
import {ResourceField} from '@/lib/admin/resource';

interface AutoFormProps {
    fields: ResourceField[];
    values: Record<string, unknown>;
    onChange: (name: string, value: unknown) => void;
    disabled?: boolean;
}

/**
 * Generic form renderer driven by a resource's `fields` array.
 * One file per supported field type — extend by adding a case here and
 * the matching FieldType union in @/lib/admin/resource.ts.
 */
export function AutoForm({fields, values, onChange, disabled}: AutoFormProps) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {fields.map((f) => (
                <div key={f.name} className={
                    f.type === 'textarea' || f.type === 'multi-select' || f.type === 'tag-picker' || f.fullWidth
                        ? 'md:col-span-2' : ''
                }>
                    <FieldRenderer field={f} value={values[f.name]} onChange={(v) => onChange(f.name, v)}
                                   disabled={disabled}/>
                    {f.helperText && (
                        <p className="text-xs text-slate-400 mt-1">{f.helperText}</p>
                    )}
                </div>
            ))}
        </div>
    );
}

function FieldRenderer({
                           field,
                           value,
                           onChange,
                           disabled,
                       }: {
    field: ResourceField;
    value: unknown;
    onChange: (v: unknown) => void;
    disabled?: boolean;
}) {
    const {name, label, type, options = [], required, readonly} = field;
    const isLocked = disabled || readonly;

    switch (type) {
        case 'text':
            return (
                <>
                    <label className="label" htmlFor={name}>{label}{required && ' *'}</label>
                    <input
                        id={name}
                        className="input"
                        type="text"
                        value={(value as string | null | undefined) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        disabled={isLocked}
                        required={required}
                    />
                </>
            );

        case 'textarea':
            return (
                <>
                    <label className="label" htmlFor={name}>{label}{required && ' *'}</label>
                    <textarea
                        id={name}
                        className="input min-h-24"
                        value={(value as string | null | undefined) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        disabled={isLocked}
                        required={required}
                    />
                </>
            );

        case 'date':
            return (
                <>
                    <label className="label" htmlFor={name}>{label}{required && ' *'}</label>
                    <input
                        id={name}
                        className="input"
                        type="date"
                        value={((value as string | null | undefined) ?? '').slice(0, 10)}
                        onChange={(e) => onChange(e.target.value || null)}
                        disabled={isLocked}
                        required={required}
                    />
                </>
            );

        case 'time':
            return (
                <>
                    <label className="label" htmlFor={name}>{label}{required && ' *'}</label>
                    <input
                        id={name}
                        className="input"
                        type="time"
                        // Postgres stores TIME as HH:MM:SS — slice to HH:MM so the
                        // browser input is happy and the backend date_format:H:i passes.
                        value={((value as string | null | undefined) ?? '').slice(0, 5)}
                        onChange={(e) => onChange(e.target.value || null)}
                        disabled={isLocked}
                        required={required}
                    />
                </>
            );

        case 'select': {
            const selectVal = (value as string | null | undefined) ?? '';
            return (
                <>
                    <label className="label" htmlFor={name}>{label}{required && ' *'}</label>
                    <select
                        id={name}
                        className="input"
                        value={selectVal}
                        onChange={(e) => onChange(e.target.value || null)}
                        disabled={isLocked}
                        required={required}
                    >
                        {/* Placeholder shown when nothing is selected yet */}
                        {!selectVal && (
                            <option value="" disabled>— choose —</option>
                        )}
                        {options.map((o) => (
                            <option key={o.value} value={o.value}>{o.label}</option>
                        ))}
                    </select>
                </>
            );
        }

        case 'checkbox':
            return (
                <label className="flex items-center gap-2 text-sm pt-5">
                    <input
                        type="checkbox"
                        checked={!!value}
                        onChange={(e) => onChange(e.target.checked)}
                        disabled={isLocked}
                    />
                    {label}
                </label>
            );

        case 'multi-select': {
            const selected = Array.isArray(value) ? (value as string[]) : [];
            const toggle = (v: string) =>
                selected.includes(v)
                    ? onChange(selected.filter((x) => x !== v))
                    : onChange([...selected, v]);
            return (
                <>
                    <label className="label">{label}{required && ' *'}</label>
                    <div className="flex flex-wrap gap-2">
                        {options.map((o) => (
                            <button
                                key={o.value}
                                type="button"
                                onClick={() => !isLocked && toggle(o.value)}
                                disabled={isLocked}
                                className={
                                    'px-3 py-1 rounded-full text-xs border transition-colors ' +
                                    (selected.includes(o.value)
                                        ? 'bg-brand-600 text-white border-brand-600'
                                        : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50')
                                }
                            >
                                {o.label}
                            </button>
                        ))}
                    </div>
                </>
            );
        }

        case 'tag-picker': {
            // Pivot-style picker — only shows assigned chips with a × remove
            // button. To add, the user picks from a dropdown of unassigned
            // options. Same value shape as 'multi-select' (string[] of ids).
            const selected = Array.isArray(value) ? (value as string[]) : [];
            const selectedSet = new Set(selected);
            const selectedOpts = options.filter((o) => selectedSet.has(o.value));
            const availableOpts = options.filter((o) => !selectedSet.has(o.value));

            const remove = (v: string) =>
                onChange(selected.filter((x) => x !== v));
            const add = (v: string) => {
                if (v && !selectedSet.has(v)) onChange([...selected, v]);
            };

            return (
                <>
                    <label className="label">{label}{required && ' *'}</label>

                    {selectedOpts.length === 0 ? (
                        <p className="text-xs text-slate-400 italic mb-2">
                            None assigned yet.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2 mb-3">
                            {selectedOpts.map((o) => (
                                <span
                                    key={o.value}
                                    className="inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-xs bg-brand-50 text-brand-700 border border-brand-200"
                                >
                                    {o.label}
                                    <button
                                        type="button"
                                        onClick={() => !isLocked && remove(o.value)}
                                        disabled={isLocked}
                                        aria-label={`Remove ${o.label}`}
                                        title={`Remove ${o.label}`}
                                        className="ml-1 p-0.5 rounded-full hover:bg-brand-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <X size={12}/>
                                    </button>
                                </span>
                            ))}
                        </div>
                    )}

                    {availableOpts.length > 0 && !isLocked && (
                        <select
                            className="input text-sm"
                            value=""
                            onChange={(e) => {
                                if (e.target.value) {
                                    add(e.target.value);
                                    // reset so re-selecting the same option works
                                    e.target.value = '';
                                }
                            }}
                        >
                            <option value="">+ Add {label.toLowerCase()}…</option>
                            {availableOpts.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>
                    )}

                    {availableOpts.length === 0 && selectedOpts.length > 0 && (
                        <p className="text-xs text-slate-400 italic">
                            All available {label.toLowerCase()} assigned.
                        </p>
                    )}
                </>
            );
        }

        default: {
            // Exhaustiveness sanity — surfaces if a new FieldType is added.
            const _exhaustive: never = type;
            return <div className="text-xs text-red-600">Unsupported field type: {String(_exhaustive)}</div>;
        }
    }
}
