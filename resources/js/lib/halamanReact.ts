import daftar from './bukanHalamanReact.json';

/**
 * Seluruh halaman aplikasi dilayani React (Inertia). Yang dikecualikan hanya path yang
 * dilayani Blade atau berupa berkas: halaman publik untuk mesin pencari, sitemap,
 * halaman luring, service worker, manifest, dan foto laporan. Kunjungan Inertia ke
 * path seperti itu menerima HTML biasa dan menampilkannya sebagai galat, jadi tautannya
 * memuat halaman penuh.
 *
 * Daftarnya berkas JSON supaya InertiaLinkRegistryTest dapat mencocokkannya dengan rute
 * GET yang sungguh tidak merender Inertia, termasuk rute baru yang lupa didaftarkan.
 */
const pengecualian = daftar.pola.map((pola) => new RegExp(pola));

export function adalahHalamanReact(href: string): boolean {
    const asal = typeof window === 'undefined' ? 'http://localhost' : window.location.origin;
    let url: URL;

    try {
        url = new URL(href, asal);
    } catch {
        return false;
    }

    // Situs lain (sumber data, BMKG, OpenStreetMap) selalu dimuat penuh: kunjungan
    // Inertia lintas asal gagal karena CORS dan tautannya tampak mati.
    if (url.origin !== asal) {
        return false;
    }

    return !pengecualian.some((pola) => pola.test(url.pathname));
}
