<?php

namespace App\Http\Controllers;

use App\Models\Trail;
use App\Support\Durasi;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Halaman depan publik: menjelaskan tujuan aplikasi, sumber data, dan menyajikan hingga
 * tiga contoh jalur terbit (R-022) tanpa status resmi atau cuaca yang cepat basi (§94, §95).
 */
class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $contohJalur = [];

        try {
            if (Schema::hasTable('trails')) {
                $contohJalur = Trail::query()
                    ->published()
                    ->tanpaGeometri()
                    ->with('mountain:id,name,province')
                    ->orderBy('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (Trail $jalur): array => [
                        'slug' => $jalur->slug,
                        'nama' => $jalur->name,
                        'gunung' => $jalur->mountain?->name ?? '',
                        'provinsi' => $jalur->mountain?->province ?? '',
                        'jarakKm' => (float) $jalur->distance_km > 0
                            ? number_format((float) $jalur->distance_km, 1, ',', '.')."\u{00A0}km"
                            : null,
                        'tanjakanM' => $jalur->elevation_gain_m !== null && $jalur->elevation_gain_m > 0
                            ? number_format($jalur->elevation_gain_m, 0, ',', '.')."\u{00A0}m"
                            : null,
                        'durasi' => $jalur->estimated_duration_minutes !== null && $jalur->estimated_duration_minutes > 0
                            ? Durasi::panjang($jalur->estimated_duration_minutes)
                            : null,
                        'url' => route('public.trail', $jalur, false),
                    ])
                    ->values()
                    ->all();
            }
        } catch (Throwable) {
            $contohJalur = [];
        }

        return Inertia::render('Welcome', [
            'title' => config('app.name').': '.config('brand.tagline'),
            'description' => config('brand.description'),
            'contohJalur' => $contohJalur,
        ]);
    }
}
