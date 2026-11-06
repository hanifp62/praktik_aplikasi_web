<?php

namespace App\Enums;

/**
 * Tiga jenis sesi dari protokol uji kegunaan, masing-masing menjawab pertanyaan yang
 * berbeda dan karena itu punya ambang jumlah peserta yang berbeda pula.
 */
enum UsabilitySessionKind: string
{
    /** Bagian 2: peserta mengerjakan delapan tugas sambil diamati. */
    case TASK = 'TASK';

    /** Bagian 3: responden yang hanya mengisi SUS setelah mencoba aplikasinya. */
    case SUS_ONLY = 'SUS_ONLY';

    /** Bagian 4: penelusuran dengan pembaca layar. */
    case SCREEN_READER = 'SCREEN_READER';

    public function label(): string
    {
        return match ($this) {
            self::TASK => 'Uji tugas',
            self::SUS_ONLY => 'Responden SUS saja',
            self::SCREEN_READER => 'Penelusuran pembaca layar',
        };
    }
}
