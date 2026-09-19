<?php

namespace App\Livewire\Reports;

use App\Enums\AnalyticsEvent;
use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Services\AnalyticsRecorder;
use App\Support\ImageSanitizer;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * FR-13 community trail condition report. Every submission enters moderation and is never
 * treated as official status (PRD §49, §118).
 */
#[Layout('layouts.app')]
#[Title('Laporan Kondisi Jalur')]
class ConditionReportForm extends Component
{
    use WithFileUploads;

    public ?int $trail_id = null;

    public ?int $trail_segment_id = null;

    public ?int $trip_plan_id = null;

    public ?string $hike_date = null;

    /** @var array<int, string> */
    public array $condition_tags = [];

    public ?string $note = null;

    public $photo = null;

    public function mount(?int $trail = null, ?int $trip = null): void
    {
        $this->trail_id = $trail;
        $this->trip_plan_id = $trip;
        $this->hike_date = now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'trail_id' => ['required', 'exists:trails,id'],
            'trail_segment_id' => ['nullable', Rule::exists('trail_segments', 'id')->where('trail_id', $this->trail_id)],
            'hike_date' => ['required', 'date', 'before_or_equal:today'],
            'condition_tags' => ['required', 'array', 'min:1'],
            'condition_tags.*' => [Rule::enum(ConditionTag::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            // PRD §81: MIME, extension and size are all constrained.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function save(AnalyticsRecorder $analytics): void
    {
        $this->validate();

        // PRD §100. Laporan menulis ke basis data dan masuk antrean moderasi manusia,
        // jadi batasnya menjaga moderator sekaligus penyimpanan.
        if (! $this->withinRateLimit()) {
            return;
        }

        $photoPath = null;

        if ($this->photo) {
            // Randomised filename keeps user-supplied names out of the storage path.
            $disk = config('filesystems.report_photos_disk');
            $photoPath = $this->photo->store('condition-reports', $disk);

            // PRD §82: foto ponsel membawa koordinat GPS pada EXIF-nya. Menerbitkannya
            // berarti mempublikasikan lokasi presisi pendaki tanpa ia memilihnya.
            // Kegagalan pembersihan tidak boleh menggagalkan laporan yang isinya tetap
            // berguna, tetapi fotonya dibuang karena tidak dapat dipastikan bersih.
            $absolute = Storage::disk($disk)->path($photoPath);

            if (! ImageSanitizer::trySanitize($absolute, (int) config('hiking.uploads.report_photo_max_dimension'))) {
                Storage::disk($disk)->delete($photoPath);
                $photoPath = null;

                session()->flash('status', 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.');
            }
        }

        $report = TrailConditionReport::create([
            'trail_id' => $this->trail_id,
            'trail_segment_id' => $this->trail_segment_id,
            'user_id' => auth()->id(),
            'hike_date' => $this->hike_date,
            'condition_tags' => $this->condition_tags,
            'photo_path' => $photoPath,
            'note' => $this->note,
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);

        if ($this->trip_plan_id) {
            HikingHistory::where('trip_plan_id', $this->trip_plan_id)
                ->where('user_id', auth()->id())
                ->update(['trail_condition_report_id' => $report->id]);
        }

        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, auth()->user(), ['trail_id' => $this->trail_id]);

        session()->flash('status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.');
        $this->redirectRoute('history', navigate: true);
    }

    /**
     * Batas per pengguna, bukan global, supaya satu pengguna yang berlebihan tidak
     * membungkam pengguna lain.
     */
    private function withinRateLimit(): bool
    {
        $key = 'report-submit:'.auth()->id();
        $limit = (int) config('hiking.rate_limits.report_submissions_per_hour');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->addError('form', sprintf(
                'Anda sudah mengirim %d laporan dalam satu jam terakhir. Coba lagi dalam %d menit.',
                $limit,
                (int) ceil(RateLimiter::availableIn($key) / 60)
            ));

            return false;
        }

        RateLimiter::hit($key, 3600);

        return true;
    }

    public function render()
    {
        $trail = $this->trail_id ? Trail::with('segments')->find($this->trail_id) : null;

        return view('livewire.reports.condition-report-form', [
            'trails' => Trail::published()->with('mountain')->orderBy('name')->get(),
            'segments' => $trail?->segments ?? collect(),
            'tags' => ConditionTag::cases(),
        ]);
    }
}
