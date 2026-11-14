<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Hapus semua jejak saya" di Profil (spec v3 bagian 6.15; PRD §84, BR-13): seluruh titik GPS
 * dari sesi pendakian milik pengguna yang masuk dihapus sekaligus. Pendakian, riwayat, dan
 * catatannya tetap ada; yang dicabut hanya lokasi presisinya. Pemilik dari sesi, bukan dari
 * permintaan.
 */
class TrackDataController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $jumlah = HikeTrackPoint::query()
            ->whereIn('hiking_session_id', HikingSession::query()->where('user_id', $request->user()->id)->select('id'))
            ->delete();

        $teks = $jumlah > 0
            ? number_format($jumlah, 0, ',', '.').' titik jejak dihapus. Pendakian dan catatannya tetap ada.'
            : 'Tidak ada jejak tersimpan untuk dihapus.';

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'privasi', 'teks' => $teks]);
    }
}
