/**
 * Label paginasi Laravel berisi entity HTML (&laquo;/&raquo;). Label dirender
 * sebagai teks biasa, bukan markup, sesuai aturan escaping pada AGENTS.md.
 */
export function paginationLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll('&amp;', '&')
        .trim();
}
