<?php

namespace App\Livewire\Admin;

use App\Enums\ExperienceLevel;
use App\Enums\TaskOutcome;
use App\Enums\UsabilitySessionKind;
use App\Models\UsabilitySession;
use App\Services\UsabilityStudyService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tempat mencatat pengamatan terhadap orang yang memakai aplikasi ini, dan membaca
 * hasilnya sebagai angka.
 *
 * Bukti dari manusia adalah satu-satunya yang tidak dapat dihasilkan sistem sendiri.
 * Yang dapat dilakukan sistem adalah membuat pengumpulannya tidak bergantung pada
 * ketelitian seseorang memegang kertas: tugasnya sudah terdaftar, kriteria berhasilnya
 * ikut tertulis di sebelah pilihannya, SUS dihitung begitu sepuluh jawabannya lengkap,
 * dan berapa peserta lagi yang dibutuhkan selalu terbaca.
 */
#[Layout('layouts.app')]
#[Title('Studi Kegunaan')]
class UsabilityStudy extends Component
{
    use WithPagination;

    public string $participant_code = '';

    public string $kind = 'TASK';

    public ?string $experience_level = null;

    public ?string $notes = null;

    /** @var array<string, array{outcome: ?string, seconds: ?string, note: ?string}> */
    public array $tasks = [];

    /** @var array<int, ?string> */
    public array $sus = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'participant_code' => ['required', 'string', 'max:20'],
            'kind' => ['required', new Enum(UsabilitySessionKind::class)],
            'experience_level' => ['nullable', new Enum(ExperienceLevel::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tasks.*.outcome' => ['nullable', Rule::enum(TaskOutcome::class)],
            'tasks.*.seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'tasks.*.note' => ['nullable', 'string', 'max:500'],
            // Jawaban SUS boleh kosong seluruhnya, tetapi yang diisi harus 1-5.
            // Lembar setengah terisi tidak menghasilkan skor, dan itu ditangani
            // susScore(), bukan dengan menolak simpanannya: catatan tugasnya tetap
            // berharga meski respondennya berhenti di tengah kuesioner.
            'sus.*' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function save(UsabilityStudyService $study): void
    {
        $this->authorize('create', UsabilitySession::class);
        $this->validate();

        $skor = $study->susScore($this->sus);

        UsabilitySession::create([
            'participant_code' => $this->participant_code,
            'kind' => $this->kind,
            'experience_level' => $this->experience_level,
            'facilitator_id' => auth()->id(),
            'conducted_at' => now(),
            'task_results' => $this->hasilTugasTerisi(),
            'sus_answers' => $skor === null ? null : $this->sus,
            'sus_score' => $skor,
            'notes' => $this->notes,
        ]);

        $this->resetForm();
        session()->flash('status', $skor === null
            ? 'Sesi tersimpan. Jawaban SUS belum lengkap, jadi sesi ini belum menyumbang skor.'
            : sprintf('Sesi tersimpan. Skor SUS peserta ini %.1f.', $skor));
    }

    /**
     * Hanya tugas yang benar-benar diamati yang disimpan.
     *
     * Tugas tanpa hasil bukan tugas yang gagal, melainkan tugas yang tidak sempat
     * dijalankan, dan menyimpannya sebagai baris kosong akan membuatnya ikut terhitung
     * sebagai pengamatan.
     *
     * @return array<string, array<string, mixed>>
     */
    private function hasilTugasTerisi(): array
    {
        return collect($this->tasks)
            ->filter(fn (array $tugas) => ! empty($tugas['outcome']))
            ->map(fn (array $tugas) => array_filter([
                'outcome' => $tugas['outcome'],
                'seconds' => $tugas['seconds'] !== null && $tugas['seconds'] !== '' ? (int) $tugas['seconds'] : null,
                'note' => $tugas['note'] ?: null,
            ], fn ($nilai) => $nilai !== null))
            ->all();
    }

    private function resetForm(): void
    {
        $this->participant_code = $this->kodeBerikutnya();
        $this->experience_level = null;
        $this->notes = null;
        $this->tasks = collect(UsabilityStudyService::TUGAS)
            ->map(fn () => ['outcome' => null, 'seconds' => null, 'note' => null])
            ->all();
        $this->sus = array_fill_keys(range(1, 10), null);
    }

    /**
     * Kode peserta berikutnya diusulkan, bukan dipaksakan: penomoran berurutan adalah
     * yang paling sering dipakai, tetapi studi yang dijalankan dua fasilitator sering
     * memakai awalan sendiri.
     */
    private function kodeBerikutnya(): string
    {
        return sprintf('P%02d', UsabilitySession::count() + 1);
    }

    public function render(UsabilityStudyService $study)
    {
        $this->authorize('viewAny', UsabilitySession::class);

        return view('livewire.admin.usability-study', [
            'ringkasan' => $study->summary(),
            'daftarTugas' => UsabilityStudyService::TUGAS,
            'pernyataanSus' => UsabilityStudyService::PERNYATAAN_SUS,
            'hasilTugas' => TaskOutcome::cases(),
            'jenis' => UsabilitySessionKind::cases(),
            'tingkat' => ExperienceLevel::cases(),
            'sesi' => UsabilitySession::query()
                ->with('facilitator:id,name')
                ->orderByDesc('conducted_at')
                ->paginate(10),
        ]);
    }
}
