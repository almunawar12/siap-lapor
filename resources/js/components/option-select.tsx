import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

// Radix Select tidak menerima item bernilai string kosong, padahal opsi
// "Semua …" / "Catatan umum" memakai '' sebagai nilai. Dipetakan ke sentinel.
const EMPTY = '__empty__';

/**
 * Dropdown shadcn untuk daftar opsi sederhana. Nilai tetap string seperti
 * <select> native: opsi bernilai '' boleh ada di `options`; tanpa opsi itu,
 * nilai '' menampilkan `placeholder`.
 */
export function OptionSelect({
    id,
    value,
    onValueChange,
    options,
    placeholder,
    tall,
    className,
    ...aria
}: {
    id?: string;
    value: string;
    onValueChange: (value: string) => void;
    // Id numerik dari server diterima langsung; nilai keluar selalu string.
    options: { value: string | number; label: string }[];
    placeholder?: string;
    tall?: boolean;
    className?: string;
    'aria-invalid'?: boolean;
    'aria-required'?: boolean;
    'aria-describedby'?: string;
    'aria-label'?: string;
}) {
    const hasEmpty = options.some((option) => String(option.value) === '');

    return (
        <Select
            value={value === '' && hasEmpty ? EMPTY : value}
            onValueChange={(next) => onValueChange(next === EMPTY ? '' : next)}
        >
            <SelectTrigger
                id={id}
                className={cn(
                    'w-full bg-background',
                    tall && 'data-[size=default]:h-11',
                    className,
                )}
                {...aria}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent position="popper" className="max-h-72">
                {options.map((option) => {
                    const v = String(option.value);

                    return (
                        <SelectItem key={v} value={v === '' ? EMPTY : v}>
                            {option.label}
                        </SelectItem>
                    );
                })}
            </SelectContent>
        </Select>
    );
}
