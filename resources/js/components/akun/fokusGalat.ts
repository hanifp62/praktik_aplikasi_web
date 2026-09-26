/**
 * Memindahkan fokus ke isian pertama yang ditolak server. Pengguna keyboard dan pembaca
 * layar lalu langsung mendengar label dan galatnya (terikat lewat aria-describedby),
 * bukan tertinggal di tombol kirim tanpa tahu apa yang salah (WCAG 3.3.1).
 *
 * `urutan` berisi nama isian sesuai urutan tampilnya; pasangan [nama, id] untuk isian
 * yang id-nya berbeda dari namanya.
 */
export function fokusGalat(galat: Partial<Record<string, string>>, urutan: Array<string | [string, string]>): void {
    for (const u of urutan) {
        const [nama, id] = typeof u === 'string' ? [u, u] : u;

        if (galat[nama]) {
            document.getElementById(id)?.focus();
            return;
        }
    }
}
