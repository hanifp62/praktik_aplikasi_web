<?php

namespace App\Http\Requests\Admin;

use App\Enums\AuthorityMemberRole;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Menambah anggota meja kurator ke sebuah badan resmi (ruling W8-R7). Hanya admin; badan dari
 * rute, pengguna dicari dari surel, dan peran pengguna tidak pernah diambil dari isian.
 */
class StoreAuthorityMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAuthorities', Authority::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'peran' => ['required', new Enum(AuthorityMemberRole::class)],
            'surat' => ['required', 'string', 'max:160'],
            'berlaku_mulai' => ['required', 'date_format:Y-m-d'],
            'berlaku_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:berlaku_mulai'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'Belum ada akun dengan surel ini. Minta orangnya mendaftar lebih dulu.',
            'surat.required' => 'Tulis nomor atau keterangan surat penunjukannya.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $badan = $this->route('authority');
            $pengguna = $this->pengguna();

            if (! $badan instanceof Authority || ! $badan->type->mayDeclareTrailStatus()) {
                $validator->errors()->add('email', 'Badan ini tidak menyatakan status jalur, jadi tidak menaungi kurator.');

                return;
            }

            if (! in_array($pengguna->role, [UserRole::HIKER, UserRole::CURATOR], true)) {
                $validator->errors()->add('email', 'Kurator terpisah dari moderator dan admin. Pakai akun pendaki lain untuk peran ini.');

                return;
            }

            if (AuthorityMember::query()->where('user_id', $pengguna->id)->where('authority_id', $badan->id)->whereNull('revoked_at')->exists()) {
                $validator->errors()->add('email', 'Orang ini sudah menjadi anggota badan ini.');
            }
        }];
    }

    public function pengguna(): User
    {
        return User::where('email', $this->validated('email'))->firstOrFail();
    }
}
