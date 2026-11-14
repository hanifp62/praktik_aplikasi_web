<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Models\Trail;
use App\Services\AnalyticsRecorder;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Halaman jalur untuk orang yang belum masuk, dan sitemap-nya.
 *
 * Seluruh halaman jalur berada di balik login, sehingga orang yang mencari nama gunung
 * di mesin pencari tidak pernah menemukan apa pun dari aplikasi ini. Itu mesin
 * pertumbuhan yang dipakai AllTrails dan di sini nilainya nol.
 *
 * Yang membentuk halaman ini satu batasan: cuplikan mesin pencari berumur lebih panjang
 * daripada isinya. Salinannya disimpan berhari-hari dan tidak dapat ditarik kembali,
 * sehingga status yang benar hari ini tetap terbaca lama sesudah jalurnya ditutup.
 *
 * §94 dan §95 melarang menyajikan status basi sebagai keadaan kini, dan pengindeksan
 * melakukannya tanpa ada yang sengaja melakukannya. Jawabannya bukan menambah
 * peringatan pada status, melainkan tidak menampilkan yang berumur pendek sama sekali.
 * Status resmi dan prakiraan cuaca hanya ada di dalam aplikasi, tempat kesegarannya
 * dapat dijaga.
 *
 * Lewat controller, bukan komponen Livewire: halaman ini statis, dibaca robot lebih
 * sering daripada manusia, dan tidak perlu satu bita pun JavaScript.
 */
class PublicTrailController extends Controller
{
    public function show(Trail $trail, AnalyticsRecorder $analytics): View
    {
        abort_unless($trail->terbit(), 404);

        $analytics->record(AnalyticsEvent::PUBLIC_TRAIL_VIEWED, null, ['trail_id' => $trail->id]);

        return view('public.trail', [
            'trail' => $trail->load('mountain', 'checkpoints'),
        ]);
    }

    public function sitemap(): Response
    {
        $jalur = Trail::query()
            ->published()
            ->with('mountain:id,name')
            ->orderBy('name')
            ->get();

        return response()
            ->view('public.sitemap', ['jalur' => $jalur])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $isi = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /moderation',
            'Disallow: /b/',
            '',
            'Sitemap: '.route('public.sitemap'),
            '',
        ]);

        return response($isi, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
