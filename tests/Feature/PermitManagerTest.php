<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Aturan perizinan sebelumnya hanya dapat dimasukkan lewat seeder atau tinker,
 * sehingga fiturnya tidak dapat dipakai kurator data.
 *
 * Aturan izin adalah data yang berubah dan menentukan apakah sebuah pendakian dapat
 * terjadi sama sekali, jadi perubahannya masuk jejak audit (PRD §61) dan membawa
 * sumber serta waktu verifikasi (PRD §60). Halamannya kini React; tulisnya POST/PUT/DELETE.
 */
class PermitManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_reach_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.permits'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.permits'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Permits')->where('title', 'Kelola Perizinan'));
    }

    public function test_a_hiker_cannot_write_rules_directly(): void
    {
        $rule = PermitRequirement::factory()->create(['mountain_id' => Mountain::factory()->create()->id]);
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->post(route('admin.permits.store'), $this->isian())->assertForbidden();
        $this->actingAs($pendaki)->put(route('admin.permits.update', $rule), $this->isian())->assertForbidden();
        $this->actingAs($pendaki)->delete(route('admin.permits.destroy', $rule))->assertForbidden();

        $this->assertSame(1, PermitRequirement::count());
    }

    public function test_an_admin_can_record_a_mountain_level_rule(): void
    {
        $mountain = Mountain::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.permits.store'), $this->isian([
                'mountain_id' => $mountain->id,
                'authority' => 'TN Bromo Tengger Semeru',
                'booking_url' => 'https://bookingsemeru.bromotenggersemeru.org',
                'daily_quota' => 200,
                'booking_closes_days_before' => 2,
                'guide_required' => true,
                'source' => 'Situs resmi TNBTS',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.permits'));

        $rule = PermitRequirement::firstOrFail();

        $this->assertSame($mountain->id, $rule->mountain_id);
        $this->assertNull($rule->trail_id, 'Aturan tingkat gunung tidak terikat satu jalur.');
        $this->assertTrue($rule->guide_required);
        $this->assertNotNull($rule->verified_at, 'PRD §60: data penting membawa waktu verifikasi.');
    }

    public function test_an_admin_can_record_a_trail_level_rule(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.permits.store'), $this->isian([
                'scope' => 'trail',
                'trail_id' => $trail->id,
                // Sasaran lain yang ikut terkirim diabaikan: hanya satu sasaran yang terisi.
                'mountain_id' => Mountain::factory()->create()->id,
                'authority' => 'Pengelola jalur',
                'source' => 'Papan pengumuman basecamp',
            ]))
            ->assertSessionHasNoErrors();

        $rule = PermitRequirement::firstOrFail();

        $this->assertSame($trail->id, $rule->trail_id);
        $this->assertNull($rule->mountain_id);
    }

    public function test_a_rule_must_target_something(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.permits.store'), $this->isian(['scope' => 'trail', 'trail_id' => null, 'authority' => 'Tanpa sasaran']))
            ->assertSessionHasErrors(['trail_id' => 'Pilih jalur yang aturannya dicatat.']);

        $this->assertSame(0, PermitRequirement::count());
    }

    public function test_a_closing_window_cannot_be_wider_than_the_opening_window(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.permits.store'), $this->isian([
                'mountain_id' => Mountain::factory()->create()->id,
                'booking_opens_days_before' => 2,
                'booking_closes_days_before' => 30,
            ]))
            ->assertSessionHasErrors('booking_closes_days_before');
    }

    public function test_editing_a_rule_keeps_it_as_one_record(): void
    {
        $mountain = Mountain::factory()->create();
        $rule = PermitRequirement::factory()->create(['mountain_id' => $mountain->id, 'authority' => 'Nama lama']);

        $this->actingAs($this->admin())
            ->put(route('admin.permits.update', $rule), $this->isian(['mountain_id' => $mountain->id, 'authority' => 'Nama baru']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PermitRequirement::count());
        $this->assertSame('Nama baru', $rule->fresh()->authority);
        $this->assertTrue(AuditLog::where('action', 'permit_requirement.updated')->exists());
    }

    public function test_changes_are_written_to_the_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.permits.store'), $this->isian([
                'mountain_id' => Mountain::factory()->create()->id,
                'authority' => 'TN Gunung Rinjani',
            ]));

        $this->assertTrue(
            AuditLog::where('action', 'permit_requirement.created')->exists(),
            'PRD §61: perubahan aturan izin adalah tindakan kritis.'
        );
    }

    public function test_an_admin_can_delete_a_rule(): void
    {
        $rule = PermitRequirement::factory()->create([
            'mountain_id' => Mountain::factory()->create()->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.permits.destroy', $rule))
            ->assertRedirect(route('admin.permits'))
            ->assertSessionHas('status', 'Aturan perizinan dihapus.');

        $this->assertSame(0, PermitRequirement::count());
        $this->assertTrue(AuditLog::where('action', 'permit_requirement.deleted')->exists());
    }

    /**
     * Ruling EF-6 (tinjauan ulang TB-m6, kelas I-2): baris yang dihapus lepas bersama tombol Hapus
     * yang difokus. Tujuan fokus dihitung dari urutan halaman daftar sebelum hapus, lewat id stabil
     * per baris: tombol Hapus baris sesudahnya, lalu baris sebelumnya, lalu judul daftar. Daftar
     * kosong tidak punya kartu berjudul, jadi id judul dipasang pada pembungkus keadaan kosongnya.
     * Hapus selalu kembali ke halaman pertama (test_an_admin_can_delete_a_rule), jadi tujuan dari
     * halaman lain tidak ditemukan dan fokus berakhir di judul. Pemindahan hanya terjadi bila
     * barisnya sudah hilang dan fokus di body, tidak ada, atau #konten (pola CC-3). Asersi sumber
     * karena halaman React dirender di klien.
     */
    public function test_deleting_a_rule_moves_focus_to_a_neighbouring_delete_button_or_the_list_heading(): void
    {
        $isi = File::get(resource_path('js/pages/Admin/Permits.tsx'));

        $this->assertStringContainsString('const idHapus = (id: number): string => `hapus-izin-${id}`;', $isi);
        $this->assertStringContainsString("const ID_JUDUL_DAFTAR = 'judul-daftar-izin';", $isi);
        $this->assertStringContainsString('<Button id={idHapus(r.id)} variant="danger" size="sm" onClick={() => hapus(r.id)}', $isi);
        $this->assertMatchesRegularExpression(
            "/<h2 id=\\{ID_JUDUL_DAFTAR\\} tabIndex=\\{-1\\} className=\\{kelas\\('rounded-kecil text-bagian text-primary', fokusTerlihat\\)\\}>\\s*Aturan tercatat\\s*<\\/h2>/",
            $isi,
        );
        $this->assertMatchesRegularExpression(
            "/<div id=\\{ID_JUDUL_DAFTAR\\} tabIndex=\\{-1\\} className=\\{kelas\\('rounded-lg', fokusTerlihat\\)\\}>\\s*<EmptyState\\s+jenis=\"pertama\"\\s+title=\"Belum ada aturan perizinan tercatat\"/",
            $isi,
            'Daftar kosong tetap punya tujuan fokus terakhir.',
        );
        $this->assertMatchesRegularExpression(
            '/const posisi = rules\.data\.findIndex\(\(r\) => r\.id === id\);\s*const tujuan = posisi < 0 \? \[\] : \[rules\.data\[posisi \+ 1\], rules\.data\[posisi - 1\]\]\.flatMap\(\(r\) => \(r \? \[idHapus\(r\.id\)\] : \[\]\)\);/',
            $isi,
            'Baris sesudahnya lebih dulu, lalu baris sebelumnya, dihitung sebelum hapus.',
        );
        $this->assertMatchesRegularExpression(
            '/const aktif = document\.activeElement;\s*if \(document\.getElementById\(idHapus\(id\)\) !== null \|\| \(aktif !== null && aktif !== document\.body && aktif\.id !== \'konten\'\)\) \{\s*return;\s*\}\s*\[\.\.\.tujuan, ID_JUDUL_DAFTAR\]\.map\(\(t\) => document\.getElementById\(t\)\)\.find\(\(unsur\) => unsur !== null\)\?\.focus\(\);/',
            $isi,
            'Fokus dipindah hanya bila barisnya hilang dan fokus belum dipindah pengguna; judul daftar tujuan terakhir.',
        );
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return array_merge([
            'scope' => 'mountain',
            'trail_id' => null,
            'mountain_id' => null,
            'authority' => 'Pengelola',
            'guide_required' => false,
        ], $ganti);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
