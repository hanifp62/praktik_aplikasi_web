<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CredentialDecisionRequest;
use App\Http\Resources\Admin\CredentialResource;
use App\Models\ExpertCredential;
use App\Services\AuditLogService;
use App\Support\PaginasiInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Memverifikasi sertifikat kompetensi yang diklaim pemandu (PRD §43, §61).
 *
 * Yang diverifikasi di sini bukan kompetensinya, melainkan keberadaan sertifikatnya.
 * Verifikasi memberi seseorang hak menyunting data yang dibaca pendaki, jadi setiap
 * perubahannya masuk jejak audit.
 */
class CredentialController extends Controller
{
    /**
     * Keputusan yang tersedia beserta status tujuannya dan pertanyaan konfirmasi.
     * Mencabut dan menyengketakan membuang hak seseorang, jadi keduanya bertanya dulu.
     *
     * @var array<string, array{status: VerificationStatus, label: string, konfirmasi: string|null}>
     */
    private const KEPUTUSAN = [
        'verifikasi' => ['status' => VerificationStatus::VERIFIED, 'label' => 'Verifikasi', 'konfirmasi' => null],
        'sengketakan' => ['status' => VerificationStatus::DISPUTED, 'label' => 'Tandai bersengketa', 'konfirmasi' => 'Tandai kredensial ini bersengketa? Haknya menyunting data jalur dicabut sampai persoalannya selesai.'],
        'cabut' => ['status' => VerificationStatus::UNVERIFIED, 'label' => 'Cabut verifikasi', 'konfirmasi' => 'Cabut verifikasi kredensial ini? Haknya menyunting data jalur berhenti berlaku.'],
    ];

    public function index(Request $request): Response
    {
        $this->authorize('verifyCredentials', ExpertCredential::class);

        // Tanpa parameter: yang belum diverifikasi, pekerjaan yang menunggu. `?filter=`
        // kosong berarti semua, sama seperti pilihan "Semua" di daftar.
        // query() memakai `?? $default`, dan ConvertEmptyStringsToNull sudah mengubah
        // `?filter=` menjadi null; keberadaan kuncinya yang membedakan "semua" dari default.
        $filter = $request->query->has('filter')
            ? (string) $request->query('filter')
            : VerificationStatus::UNVERIFIED->value;

        return Inertia::render('Admin/Credentials', [
            'title' => 'Verifikasi Kredensial Ahli',
            'filter' => $filter,
            'statuses' => array_map(fn (VerificationStatus $s) => ['value' => $s->value, 'label' => $s->label()], VerificationStatus::cases()),
            'keputusan' => collect(self::KEPUTUSAN)
                ->map(fn (array $k, string $kunci) => ['value' => $kunci, 'label' => $k['label'], 'konfirmasi' => $k['konfirmasi']])
                ->values()
                ->all(),
            'kredensial' => PaginasiInertia::props(
                ExpertCredential::query()
                    ->when($filter, fn ($q, $f) => $q->where('verification_status', $f))
                    ->with(['user:id,name,email', 'issuingAuthority', 'endorsingAuthority', 'mountains:id,name'])
                    ->orderByDesc('created_at')
                    ->paginate(10)
                    ->withQueryString(),
                fn (ExpertCredential $k) => (new CredentialResource($k))->resolve($request),
            ),
        ]);
    }

    /**
     * Mencabut verifikasi mengembalikannya ke belum diverifikasi, bukan menghapusnya.
     * Riwayat klaimnya tetap perlu terbaca.
     */
    public function keputusan(CredentialDecisionRequest $request, ExpertCredential $credential, string $keputusan, AuditLogService $audit): RedirectResponse
    {
        $status = self::KEPUTUSAN[$keputusan]['status'];
        $sebelum = ['verification_status' => $credential->verification_status->value];
        $catatan = $request->validated('catatan');

        $credential->update([
            'verification_status' => $status->value,
            'verified_by' => $request->user()->id,
            'verified_at' => $status === VerificationStatus::VERIFIED ? now() : null,
            'notes' => filled($catatan) ? $catatan : $credential->notes,
        ]);

        $audit->record($request->user(), 'credential.'.strtolower($status->value), $credential, $sebelum, [
            'verification_status' => $status->value,
        ]);

        return back()->with('status', match ($status) {
            VerificationStatus::VERIFIED => 'Kredensial diverifikasi. Pemegangnya kini dapat menyunting data jalur di kawasan yang tercantum.',
            VerificationStatus::DISPUTED => 'Kredensial ditandai bersengketa. Haknya dicabut sampai persoalannya selesai.',
            VerificationStatus::UNVERIFIED => 'Verifikasi dicabut. Haknya berhenti berlaku.',
        });
    }

    /**
     * @return array<int, string>
     */
    public static function keputusanTersedia(): array
    {
        return array_keys(self::KEPUTUSAN);
    }
}
