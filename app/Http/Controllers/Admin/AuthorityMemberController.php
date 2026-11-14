<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAuthorityMemberRequest;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pemberian dan pencabutan peran kurator lewat keanggotaan badan resmi (spec v3 bagian 10.7, Q11;
 * ruling W8-R7). Hanya admin. Menambah keanggotaan pertama menjadikan pendaki kurator; mencabut
 * keanggotaan aktif terakhir mengembalikannya menjadi pendaki. Setiap perubahan peran dan
 * keanggotaan tercatat di jejak audit (PRD §61). Tidak ada akun kurator yang dibuat otomatis.
 */
class AuthorityMemberController extends Controller
{
    public function store(StoreAuthorityMemberRequest $request, Authority $authority, AuditLogService $audit): RedirectResponse
    {
        $data = $request->validated();
        $pengguna = $request->pengguna();

        DB::transaction(function () use ($request, $authority, $audit, $data, $pengguna): void {
            $keanggotaan = (new AuthorityMember)->fill([
                'role' => $data['peran'],
                'appointment_reference' => $data['surat'],
                'valid_from' => $data['berlaku_mulai'],
                'valid_until' => $data['berlaku_sampai'] ?? null,
            ])->forceFill([
                'user_id' => $pengguna->id,
                'authority_id' => $authority->id,
                'granted_by' => $request->user()->id,
            ]);
            $keanggotaan->save();
            $audit->record($request->user(), 'authority_member.granted', $keanggotaan, null, [
                'user_id' => $pengguna->id,
                'authority_id' => $authority->id,
                'role' => $data['peran'],
                'appointment_reference' => $data['surat'],
                'valid_from' => $data['berlaku_mulai'],
                'valid_until' => $data['berlaku_sampai'] ?? null,
            ]);

            if ($pengguna->role === UserRole::HIKER) {
                $pengguna->forceFill(['role' => UserRole::CURATOR])->save();
                $audit->record($request->user(), 'user.role_changed', $pengguna, ['role' => UserRole::HIKER->value], ['role' => UserRole::CURATOR->value]);
            }
        });

        return redirect()->route('admin.authorities')->with('status', $pengguna->email.' kini kurator '.$authority->displayName().'.');
    }

    public function destroy(Request $request, Authority $authority, AuthorityMember $member, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('manageAuthorities', Authority::class);

        DB::transaction(function () use ($request, $member, $audit): void {
            $member->forceFill(['revoked_at' => now(), 'revoked_by' => $request->user()->id])->save();
            $audit->recordChange($request->user(), 'authority_member.revoked', $member);

            $pengguna = $member->user;
            $masihAnggota = $pengguna->authorityMemberships()->whereNull('revoked_at')->exists();

            if ($pengguna->role === UserRole::CURATOR && ! $masihAnggota) {
                $pengguna->forceFill(['role' => UserRole::HIKER])->save();
                $audit->record($request->user(), 'user.role_changed', $pengguna, ['role' => UserRole::CURATOR->value], ['role' => UserRole::HIKER->value]);
            }
        });

        return redirect()->route('admin.authorities')->with('status', 'Keanggotaan '.$member->user->email.' di '.$authority->displayName().' dicabut.');
    }
}
