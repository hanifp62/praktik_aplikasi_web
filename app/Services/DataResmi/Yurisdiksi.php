<?php

namespace App\Services\DataResmi;

use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Yurisdiksi meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 2; ruling W8-R7): kawasan
 * badan resmi tempat seorang kurator menjadi anggota aktif, dan hanya badan yang berwenang
 * menyatakan status jalur (AuthorityType::mayDeclareTrailStatus()). Satu-satunya tempat aturan
 * ini ditulis; OfficialStatusPolicy, Form Request meja kurator, dan pemeriksaan konsistensi
 * memakainya. Peran dan badan selalu dibaca dari basis data untuk pengguna sesi, tidak pernah
 * dari isian.
 *
 * Keanggotaan aktif dimuat sekali per instans pengguna per permintaan.
 */
final class Yurisdiksi
{
    /** @var array<int, Collection<int, AuthorityMember>> */
    private array $muatan = [];

    /**
     * Keanggotaan aktif pengguna di badan yang berwenang menyatakan status, beserta kawasannya.
     * Kosong untuk pengguna yang bukan kurator.
     *
     * @return Collection<int, AuthorityMember>
     */
    public function keanggotaan(User $pengguna): Collection
    {
        if (! $pengguna->isCurator()) {
            return collect();
        }

        return $this->muatan[$pengguna->id] ??= AuthorityMember::query()
            ->aktif()
            ->where('user_id', $pengguna->id)
            ->with('authority.mountains:mountains.id')
            ->get()
            ->filter(fn (AuthorityMember $a) => $a->authority?->type?->mayDeclareTrailStatus() === true)
            ->values();
    }

    public function kuratorAktif(User $pengguna): bool
    {
        return $this->keanggotaan($pengguna)->isNotEmpty();
    }

    /**
     * Id gunung yang berada di bawah badan-badan pengguna.
     *
     * @return Collection<int, int>
     */
    public function gunung(User $pengguna): Collection
    {
        return $this->keanggotaan($pengguna)
            ->flatMap(fn (AuthorityMember $a) => $a->authority->mountains->pluck('id'))
            ->unique()
            ->values();
    }

    /**
     * Gunung tempat sebuah sasaran status berada: gunung itu sendiri, gunung jalur, atau gunung
     * jalur pemilik segmen.
     */
    public static function gunungSasaran(Model $sasaran): ?int
    {
        return match (true) {
            $sasaran instanceof Mountain => $sasaran->id,
            $sasaran instanceof Trail => $sasaran->mountain_id,
            $sasaran instanceof TrailSegment => Trail::whereKey($sasaran->trail_id)->value('mountain_id'),
            default => null,
        };
    }

    /**
     * Badan pengguna yang berwenang atas kawasan sasaran: daftar penerbit yang boleh dipilih di
     * formulir, dan satu-satunya daftar yang diterima server.
     *
     * @return Collection<int, Authority>
     */
    public function otoritasUntuk(User $pengguna, Model $sasaran): Collection
    {
        $gunung = self::gunungSasaran($sasaran);

        if ($gunung === null) {
            return collect();
        }

        return $this->keanggotaan($pengguna)
            ->filter(fn (AuthorityMember $a) => $a->authority->mountains->contains('id', $gunung))
            ->map(fn (AuthorityMember $a) => $a->authority)
            ->unique('id')
            ->values();
    }

    public function berwenang(User $pengguna, Model $sasaran, ?Authority $otoritas = null): bool
    {
        $badan = $this->otoritasUntuk($pengguna, $sasaran);

        return $otoritas === null ? $badan->isNotEmpty() : $badan->contains('id', $otoritas->id);
    }
}
