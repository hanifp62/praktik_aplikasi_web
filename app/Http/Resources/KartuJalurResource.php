<?php

namespace App\Http\Resources;

use App\Models\Trail;
use App\Services\RouteFit\TrailFitSummary;
use Illuminate\Http\Request;

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15): baris jalur ditambah yang
 * hanya dibutuhkan kartunya, yaitu tautan ke halaman gunung, status akses beserta asalnya,
 * garis profil pada skala yang sama dengan kartu lain di halaman itu, penanda catatan
 * pribadi (supaya melepas simpanan yang bercatatan bertanya dulu), dan saringan yang
 * menyembunyikannya bila kartu itu tampil lewat "Tampilkan juga".
 *
 * Skor internal tetap tidak ikut (BR-09): kecocokan datang dari TrailFitSummary.
 *
 * @mixin Trail
 */
class KartuJalurResource extends TrailRowResource
{
    /**
     * @param  array<string, mixed>  $status  bentuk StatusJalurRingkas
     * @param  array<int, array{0: float, 1: float}>|null  $garis  titik SkalaProfil::naikBersama
     */
    public function __construct(
        Trail $trail,
        ?TrailFitSummary $fit,
        bool $ditimbang,
        private readonly array $status,
        private readonly ?array $garis,
        private readonly bool $adaCatatan,
        private readonly ?string $tersembunyiOleh,
    ) {
        parent::__construct($trail, $fit, $ditimbang);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'gunungUrl' => $this->mountain ? route('mountains.show', $this->mountain) : null,
            'status' => $this->status,
            'garis' => $this->garis,
            'adaCatatan' => $this->adaCatatan,
            'tersembunyiOleh' => $this->tersembunyiOleh,
        ];
    }
}
