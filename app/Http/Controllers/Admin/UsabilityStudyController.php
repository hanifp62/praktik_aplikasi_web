<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExperienceLevel;
use App\Enums\TaskOutcome;
use App\Enums\UsabilitySessionKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsabilitySessionRequest;
use App\Models\UsabilitySession;
use App\Services\UsabilityStudyService;
use App\Support\PaginasiInertia;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tempat mencatat pengamatan terhadap orang yang memakai aplikasi ini, dan membaca
 * hasilnya sebagai angka.
 *
 * Yang dapat dilakukan sistem adalah membuat pengumpulannya tidak bergantung pada
 * ketelitian seseorang memegang kertas: tugasnya sudah terdaftar, kriteria berhasilnya
 * ikut tertulis di sebelah pilihannya, SUS dihitung begitu sepuluh jawabannya lengkap,
 * dan berapa peserta lagi yang dibutuhkan selalu terbaca. Ambang responden SUS milik
 * UsabilityStudyService dan tidak diubah di sini.
 */
class UsabilityStudyController extends Controller
{
    public function index(UsabilityStudyService $study): Response
    {
        $this->authorize('viewAny', UsabilitySession::class);

        $ringkasan = $study->summary();

        return Inertia::render('Admin/Usability', [
            'title' => 'Studi Kegunaan',
            'ringkasan' => [
                'pesertaTugas' => $ringkasan['peserta_tugas'],
                'cakupanMasalah' => round($ringkasan['cakupan_masalah'] * 100).'%',
                'pesertaKurang' => $ringkasan['peserta_kurang'],
                'susRata' => $ringkasan['sus_rata'] === null ? null : number_format($ringkasan['sus_rata'], 1, ',', '.'),
                'susGrade' => $ringkasan['sus_grade'],
                'susDapatDiandalkan' => $ringkasan['sus_dapat_diandalkan'],
                'respondenSus' => $ringkasan['responden_sus'],
                'respondenSusKurang' => $ringkasan['responden_sus_kurang'],
                'sesiPembacaLayar' => $ringkasan['sesi_pembaca_layar'],
                'tugas' => collect($ringkasan['tugas'])->map(fn (array $t, string $kode) => [
                    'kode' => $kode,
                    'judul' => $t['judul'],
                    'inti' => $t['inti'],
                    'diamati' => $t['diamati'],
                    'gagal' => $t['gagal'],
                    'tingkatBerhasil' => $t['tingkat_berhasil'] === null ? 'belum diamati' : round($t['tingkat_berhasil'] * 100).'%',
                ])->values()->all(),
            ],
            'daftarTugas' => collect(UsabilityStudyService::TUGAS)
                ->map(fn (array $t, string $kode) => ['kode' => $kode, 'judul' => $t['judul'], 'berhasil' => $t['berhasil']])
                ->values()
                ->all(),
            'pernyataanSus' => collect(UsabilityStudyService::PERNYATAAN_SUS)
                ->map(fn (string $p, int $nomor) => ['nomor' => $nomor, 'pernyataan' => $p])
                ->values()
                ->all(),
            'hasilTugas' => array_map(fn (TaskOutcome $o) => ['value' => $o->value, 'label' => $o->label()], TaskOutcome::cases()),
            'jenis' => array_map(fn (UsabilitySessionKind $k) => ['value' => $k->value, 'label' => $k->label()], UsabilitySessionKind::cases()),
            'tingkat' => array_map(fn (ExperienceLevel $t) => ['value' => $t->value, 'label' => $t->label()], ExperienceLevel::cases()),
            // Kode peserta berikutnya diusulkan, bukan dipaksakan: penomoran berurutan
            // paling sering dipakai, tetapi studi dua fasilitator sering memakai awalan sendiri.
            'kodeBerikutnya' => sprintf('P%02d', UsabilitySession::count() + 1),
            'sesi' => PaginasiInertia::props(
                UsabilitySession::query()->with('facilitator:id,name')->orderByDesc('conducted_at')->paginate(10)->withQueryString(),
                fn (UsabilitySession $s) => [
                    'id' => $s->id,
                    'kode' => $s->participant_code,
                    'jenis' => $s->kind->label(),
                    'fasilitator' => $s->facilitator?->name,
                    'sus' => $s->sus_score === null ? 'tanpa SUS' : 'SUS '.number_format($s->sus_score, 1, ',', '.'),
                    'waktu' => Timezone::display($s->conducted_at, Timezone::DEFAULT),
                ],
            ),
        ]);
    }

    public function store(StoreUsabilitySessionRequest $request, UsabilityStudyService $study): RedirectResponse
    {
        $data = $request->validated();
        $jawaban = $request->jawabanSus();
        $skor = $study->susScore($jawaban);

        UsabilitySession::create([
            'participant_code' => $data['participant_code'],
            'kind' => $data['kind'],
            'experience_level' => $data['experience_level'] ?? null,
            'facilitator_id' => $request->user()->id,
            'conducted_at' => now(),
            'task_results' => $request->hasilTugasTerisi(),
            'sus_answers' => $skor === null ? null : $jawaban,
            'sus_score' => $skor,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.usability')->with('status', $skor === null
            ? 'Sesi tersimpan. Jawaban SUS belum lengkap, jadi sesi ini belum menyumbang skor.'
            : sprintf('Sesi tersimpan. Skor SUS peserta ini %.1f.', $skor));
    }
}
