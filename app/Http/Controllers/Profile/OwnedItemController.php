<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\SaveOwnedItemRequest;
use App\Models\UserOwnedItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Perlengkapan pribadi di Profil (spec v3 bagian 6.15; rencana induk W7 butir 4; ruling W7-R8).
 * Pemilik dari sesi, barang dari rute dan Policy. Daftar ini mengisi awal persiapan trip;
 * mengubah atau menghapus barang tidak mengubah daftar persiapan trip yang sudah ada.
 */
class OwnedItemController extends Controller
{
    public function store(SaveOwnedItemRequest $request): RedirectResponse
    {
        $nama = $request->nama();

        $this->tanpaKembar($nama, fn () => $request->user()->ownedItems()->create(['name' => $nama]));

        return $this->keProfil('"'.$nama.'" ditambahkan ke perlengkapan pribadi.');
    }

    public function update(SaveOwnedItemRequest $request, UserOwnedItem $item): RedirectResponse
    {
        $nama = $request->nama();

        $this->tanpaKembar($nama, fn () => $item->update(['name' => $nama]));

        return $this->keProfil('Nama barang diganti menjadi "'.$nama.'".');
    }

    public function destroy(UserOwnedItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return $this->keProfil('"'.$item->name.'" dihapus dari perlengkapan pribadi. Daftar persiapan trip yang sudah ada tidak berubah.');
    }

    /**
     * Dua kiriman bersamaan dengan nama yang sama lolos validasi berdua; kunci unik basis data
     * menolak yang kedua, dan penolakannya dikembalikan sebagai galat isian, bukan galat server.
     */
    private function tanpaKembar(string $nama, callable $tulis): void
    {
        try {
            $tulis();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nama' => '"'.$nama.'" sudah ada di perlengkapan pribadi Anda.']);
        }
    }

    private function keProfil(string $kabar): RedirectResponse
    {
        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => $kabar]);
    }
}
