<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Log keputusan PRD memuat setiap keputusan DK (GR-04: keputusan DK-30 sampai DK-58 spec v3
 * masuk log di gelombang W9; GR-09).
 *
 * Keputusan yang tidak tercatat di log tidak dapat dibatalkan pemilik dengan jelas, dan nomor
 * yang tercatat dua kali membuat rujukan "DK-nn" bermakna ganda.
 */
class DecisionLogTest extends TestCase
{
    public function test_every_decision_from_dk01_to_dk58_has_exactly_one_row(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        $log = substr($prd, (int) strpos($prd, '### Log keputusan'));
        $salah = [];

        foreach (range(1, 58) as $nomor) {
            $baris = preg_match_all('/^\| DK-'.sprintf('%02d', $nomor).'( \([^)]*\))? \|/m', $log);

            if ($baris !== 1) {
                $salah[] = sprintf('DK-%02d: %d baris', $nomor, $baris);
            }
        }

        $this->assertSame([], $salah);
    }

    public function test_the_next_governance_record_number_follows_the_last_one(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        preg_match_all('/^### GR-(\d{2}):/m', $prd, $catatan);
        $terakhir = max(array_map('intval', $catatan[1]));

        $this->assertStringContainsString(sprintf('governance record berikutnya GR-%02d', $terakhir + 1), $prd);
    }
}
