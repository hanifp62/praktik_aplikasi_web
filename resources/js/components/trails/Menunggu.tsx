import type { JalurMenunggu } from './types';

/**
 * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang. Bagian
 * terpisah, tidak pernah dicampur ke hasil: layar kosong membuat pendaki mengira
 * gunungnya tidak ada, padahal yang belum ada adalah keterangannya.
 */
export function Menunggu({ daftar }: { daftar: JalurMenunggu[] }) {
    return (
        <section className="mt-10 border-t border-subtle pt-6">
            <h2 className="text-lg font-semibold text-primary">Jalur yang datanya belum tersedia</h2>
            <p className="mt-1 text-sm text-secondary">
                Jalur berikut sudah dikenali sistem, tetapi keterangannya belum dimasukkan pihak yang berwenang. Belum dapat direncanakan dari sini.
            </p>
            <ul className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                {daftar.map((j) => (
                    <li key={j.id}>
                        <a href={j.url} className="block rounded-lg border border-subtle p-4 transition hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                            <span className="block font-medium text-primary">{j.name}</span>
                            <span className="mt-0.5 block text-sm text-secondary">{j.mountain} &middot; {j.province}</span>
                            <span className="mt-2 inline-block rounded-control bg-warn-100 px-2 py-0.5 text-xs font-medium text-warn-900">Menunggu masukan pengelola</span>
                        </a>
                    </li>
                ))}
            </ul>
        </section>
    );
}
