import { useEffect } from 'react';
import { kunciPerekam, kunciTersimpan, kunciTertutup } from '@/luring/simpan';

interface ModulJejak {
    aturPengguna: (kunci: string) => void;
    mulaiMerekam: (sesi: number) => () => void;
}

/**
 * Perekaman jejak berjalan sendiri selama mode lapangan terbuka, dan hanya bila pendaki
 * menyalakannya di profilnya (penjagaan yang sama ada di server). Posisi mode lapangan sendiri
 * diminta di components/mendaki/usePosisiLapangan.ts dan tidak dikirim ke mana pun.
 *
 * Hook ini hanya merekam (ruling W6-P2, W6-P2a). Selama pendakian tidak ada titik yang dikirim,
 * juga saat sinyal kembali: titiknya menumpuk di perangkat (jejak.js), lalu pengirim di halaman
 * aplikasi biasa (resources/js/luring/kirimJejak.ts, dipasang AppLayout) mengirimnya sesudah
 * pendakian selesai atau dibatalkan, sesudah server memastikan sesi itu boleh menerima. Mode
 * lapangan memakai TataLetakPendakian, bukan AppLayout, dan shell luring juga, jadi keduanya
 * tidak pernah memasang pengirim.
 *
 * Basis data jejak diikat ke pengguna perangkat (W6, ruling W6-R10), jadi tanpa kunci perangkat
 * tidak ada yang direkam. Shell luring merakit props tanpa perangkat, jadi di sana kuncinya kunci
 * tersimpan, yaitu pemilik paket yang dibuka shell (kunciPerekam, ruling W6-11f). Kunci yang sudah
 * ditutup di tab ini sesudah keluar atau ganti akun (ruling W6-11d) tidak diikat lagi sampai muat
 * penuh: halaman yang dipulihkan tombol Kembali tidak menyalakan perekam jejak dan tidak membuat
 * ulang basis data yang baru dihapus. aturPengguna yang melempar untuk basis data yang sudah
 * dihapus (tinjauan Tugas 11 m-7) berakhir di catch, jadi perekam tidak dimulai. Posisi untuk
 * tampilan mode lapangan tetap diminta (usePosisiLapangan) dan tidak disimpan.
 */
export function useJejakPendakian(aktif: boolean, sesiId: number | null, kunciHalaman: string | null) {
    const kunci = kunciPerekam(kunciHalaman, kunciTersimpan());

    useEffect(() => {
        if (!aktif || sesiId === null || kunci === null || kunciTertutup(kunci) || typeof window.muatJejak !== 'function') {
            return;
        }

        let batal = false;
        let berhenti: (() => void) | null = null;

        window
            .muatJejak()
            .then((modul) => {
                if (batal || kunciTertutup(kunci)) {
                    return;
                }

                const jejak = modul as ModulJejak;
                jejak.aturPengguna(kunci);
                berhenti = jejak.mulaiMerekam(sesiId);
            })
            .catch(() => {});

        return () => {
            batal = true;
            berhenti?.();
        };
    }, [aktif, sesiId, kunci]);
}
