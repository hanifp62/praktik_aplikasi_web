<?php

namespace App\Http\Requests\Admin;

use App\Enums\CheckpointType;
use App\Models\Checkpoint;
use App\Models\Trail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Isian checkpoint. Yang diotorisasi adalah jalurnya, sama seperti komponen Livewire
 * lama: authorize('update', $trail), sehingga pemandu bersertifikat tetap terbatas pada
 * kawasannya sendiri (TrailPolicy).
 */
class CheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trail = $this->route('trail');

        return $trail instanceof Trail && $this->user()->can('update', $trail);
    }

    /**
     * Urutan unik per jalur di basis data. Aturannya ditulis di sini supaya urutan yang
     * sudah terpakai dijawab di samping isiannya, bukan sebagai galat 500.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $trail = $this->route('trail');
        $checkpoint = $this->route('checkpoint');

        return [
            'name' => ['required', 'string', 'max:120'],
            'sequence' => [
                'required', 'integer', 'min:1', 'max:200',
                Rule::unique('checkpoints', 'sequence')
                    ->where('trail_id', $trail instanceof Trail ? $trail->getKey() : null)
                    ->ignore($checkpoint instanceof Checkpoint ? $checkpoint->getKey() : null),
            ],
            'checkpoint_type' => ['required', new Enum(CheckpointType::class)],
            'elevation_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
