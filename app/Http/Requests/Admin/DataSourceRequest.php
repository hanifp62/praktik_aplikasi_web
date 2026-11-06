<?php

namespace App\Http\Requests\Admin;

use App\Enums\SourceType;
use App\Models\DataSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Pendaftaran dan perubahan sumber data (PRD §58-60), dengan aturan yang sama seperti
 * komponen Livewire yang digantikannya.
 */
class DataSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = $this->route('source');

        return $source instanceof DataSource
            ? $this->user()->can('update', $source)
            : $this->user()->can('create', DataSource::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_name' => ['required', 'string', 'max:160'],
            'source_type' => ['required', new Enum(SourceType::class)],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_owner' => ['nullable', 'string', 'max:160'],
            'freshness_policy' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
