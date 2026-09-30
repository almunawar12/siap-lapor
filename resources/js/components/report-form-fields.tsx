import { FieldError } from '@/components/field-error';
import { OptionSelect } from '@/components/option-select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

/**
 * Setiap input punya label dan tempat pesan kesalahan sendiri. Komponen ini
 * dipakai bersama oleh form bertahap agar penomoran bagian Model A konsisten.
 */
export function FormField({
    id,
    label,
    error,
    hint,
    required,
    children,
    className,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required ? (
                    <span className="text-destructive" aria-hidden="true">
                        *
                    </span>
                ) : null}
            </Label>
            {children}
            {hint ? (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            ) : null}
            <FieldError id={`${id}-error`} message={error} />
        </div>
    );
}

export function TextField({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required,
    type = 'text',
    maxLength,
    className,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    required?: boolean;
    type?: 'text' | 'date' | 'time';
    maxLength?: number;
    className?: string;
}) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            hint={hint}
            required={required}
            className={className}
        >
            <Input
                id={id}
                type={type}
                value={value}
                maxLength={maxLength}
                aria-invalid={Boolean(error)}
                aria-required={required}
                aria-describedby={describedBy(id, error, hint)}
                onChange={(event) => onChange(event.target.value)}
            />
        </FormField>
    );
}

export function TextAreaField({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required,
    rows = 4,
    maxLength,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    required?: boolean;
    rows?: number;
    maxLength?: number;
}) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            hint={hint}
            required={required}
        >
            <textarea
                id={id}
                rows={rows}
                value={value}
                maxLength={maxLength}
                aria-invalid={Boolean(error)}
                aria-required={required}
                aria-describedby={describedBy(id, error, hint)}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    'min-h-24 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                    'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
                )}
            />
            {maxLength ? (
                <p className="text-xs text-muted-foreground tabular-nums">
                    {value.length.toLocaleString('id-ID')} /{' '}
                    {maxLength.toLocaleString('id-ID')} karakter
                </p>
            ) : null}
        </FormField>
    );
}

export function SelectField({
    id,
    label,
    value,
    onChange,
    options,
    error,
    hint,
    required,
    placeholder = 'Pilih salah satu',
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
    error?: string;
    hint?: string;
    required?: boolean;
    placeholder?: string;
}) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            hint={hint}
            required={required}
        >
            <OptionSelect
                id={id}
                value={value}
                onValueChange={onChange}
                options={options}
                placeholder={placeholder}
                tall
                aria-invalid={Boolean(error)}
                aria-required={required}
                aria-describedby={describedBy(id, error, hint)}
            />
        </FormField>
    );
}

/** Menautkan petunjuk dan pesan galat ke kontrol agar dibacakan pembaca layar. */
function describedBy(id: string, error?: string, hint?: string) {
    return (
        [hint && `${id}-hint`, error && `${id}-error`]
            .filter(Boolean)
            .join(' ') || undefined
    );
}
