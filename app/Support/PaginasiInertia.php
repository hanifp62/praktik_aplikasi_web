<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Bentuk paginasi yang dibaca komponen Pagination di React: baris yang sudah dipetakan,
 * total, dan tautan halaman tanpa markup Blade.
 */
class PaginasiInertia
{
    /**
     * @template T
     *
     * @param  LengthAwarePaginator<int, T>  $paginator
     * @param  callable(T): array<string, mixed>  $baris
     * @return array{data: array<int, array<string, mixed>>, total: int, links: array<int, array{url: string|null, label: string, active: bool}>}
     */
    public static function props(LengthAwarePaginator $paginator, callable $baris): array
    {
        return [
            'data' => collect($paginator->items())->map($baris)->values()->all(),
            'total' => $paginator->total(),
            'links' => $paginator->linkCollection()
                ->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])
                ->values()
                ->all(),
        ];
    }
}
