/**
 * Menggabungkan nama kelas Tailwind, membuang yang kosong atau salah.
 * Tanpa pustaka: kebutuhan di sini hanya menyaring nilai falsy, dan clsx atau
 * tailwind-merge menagih bita untuk kasus yang tidak dipakai.
 */
export function kelas(...bagian: Array<string | false | null | undefined>): string {
    return bagian.filter(Boolean).join(' ');
}
