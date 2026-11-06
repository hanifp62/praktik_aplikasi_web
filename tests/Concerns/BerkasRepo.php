<?php

namespace Tests\Concerns;

/**
 * Keberadaan berkas menurut isi repo, bukan menurut disk.
 *
 * Berkas yang diabaikan git, misalnya keluaran NgodingPakeAI di features/, dapat ada di
 * disk mesin pengembang tetapi tidak pernah ada di checkout CI. Pemeriksaan File::exists
 * lolos di lokal lalu merah di CI. Di sini sebuah berkas dihitung ada hanya bila ia ikut
 * commit dan memang ada di disk.
 */
trait BerkasRepo
{
    /**
     * Daftar berkas yang akan ikut commit, disimpan per instance test untuk adaDiRepo().
     *
     * @var array<int, string>|null
     */
    private ?array $berkasRepoTersimpan = null;

    /**
     * Berkas yang dilacak ditambah berkas baru yang tidak diabaikan: persis yang akan masuk
     * bila seseorang menjalankan `git add -A`. Jalurnya relatif terhadap root repo dan
     * memakai garis miring maju.
     *
     * @return array<int, string>
     */
    protected function berkasYangAkanDicommit(): array
    {
        exec('git -C '.escapeshellarg(base_path()).' ls-files -z --cached --others --exclude-standard', $keluaran, $kode);

        if ($kode !== 0) {
            $this->markTestSkipped('Bukan repositori git.');
        }

        return array_values(array_filter(explode("\0", implode("\n", $keluaran))));
    }

    /**
     * Benar bila jalur itu berkas yang akan ikut commit dan ada di disk, atau, untuk jalur
     * yang berakhir "/", folder yang memuat minimal satu berkas seperti itu.
     */
    protected function adaDiRepo(string $jalur): bool
    {
        $jalur = $this->normalkanJalurRepo($jalur);
        $this->berkasRepoTersimpan ??= $this->berkasYangAkanDicommit();

        if (! str_ends_with($jalur, '/')) {
            return in_array($jalur, $this->berkasRepoTersimpan, true) && is_file(base_path($jalur));
        }

        foreach ($this->berkasRepoTersimpan as $berkas) {
            if (str_starts_with($berkas, $jalur) && is_file(base_path($berkas))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Garis miring terbalik menjadi garis miring maju, segmen "." dibuang, dan segmen ".."
     * diselesaikan; garis miring penutup dipertahankan sebagai tanda folder.
     */
    private function normalkanJalurRepo(string $jalur): string
    {
        $jalur = str_replace('\\', '/', $jalur);
        $segmen = [];

        foreach (explode('/', $jalur) as $satu) {
            if ($satu === '' || $satu === '.') {
                continue;
            }

            if ($satu === '..' && $segmen !== [] && end($segmen) !== '..') {
                array_pop($segmen);

                continue;
            }

            $segmen[] = $satu;
        }

        $normal = implode('/', $segmen);

        return $normal !== '' && str_ends_with($jalur, '/') ? $normal.'/' : $normal;
    }
}
