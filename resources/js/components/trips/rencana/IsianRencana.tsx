import type { Opsi } from '@/components/trips/types';
import { FormField, InputError, Label, Select, Textarea } from '@/components/ui/Field';

/** Isian rencana yang sama untuk membuat dan mengubah trip. Kosong dikirim sebagai '', server menjadikannya null. */
export interface IsianRencanaData {
    name: string;
    planned_date: string;
    end_date: string;
    start_time: string;
    participants_count: string;
    trip_type: string;
    notes: string;
}

/** Batas dari config/hiking.php, dikirim server supaya petunjuk dan validasi menyebut angka yang sama. */
export interface BatasRencana {
    peserta: number;
    rentangHari: number;
}

interface Props {
    data: IsianRencanaData;
    errors: Partial<Record<keyof IsianRencanaData, string>>;
    setField: (kunci: keyof IsianRencanaData, nilai: string) => void;
    tripTypes: Opsi[];
    tanggalTerawal: string;
    batas: BatasRencana;
}

/**
 * Tanggal mulai dan selesai, jam mulai, peserta, tipe, dan catatan. Setiap isian punya
 * label terikat; petunjuk dan galat ditautkan lewat aria-describedby oleh FormField.
 * Tanggal selesai tidak boleh sebelum tanggal mulai: batas min di peramban hanya
 * bantuan, penolakannya tetap di server.
 */
export function IsianRencana({ data, errors, setField, tripTypes, tanggalTerawal, batas }: Props) {
    return (
        <>
            <FormField
                name="name"
                label="Nama trip"
                placeholder="Pendakian akhir pekan"
                required
                maxLength={120}
                value={data.name}
                onChange={(e) => setField('name', e.target.value)}
                error={errors.name}
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    name="planned_date"
                    type="date"
                    min={tanggalTerawal}
                    required
                    label="Tanggal mulai"
                    value={data.planned_date}
                    onChange={(e) => setField('planned_date', e.target.value)}
                    error={errors.planned_date}
                />
                <FormField
                    name="end_date"
                    type="date"
                    min={data.planned_date || tanggalTerawal}
                    label="Tanggal selesai (opsional)"
                    hint={`Kosongkan untuk pendakian satu hari. Paling lambat ${batas.rentangHari} hari setelah tanggal mulai.`}
                    value={data.end_date}
                    onChange={(e) => setField('end_date', e.target.value)}
                    error={errors.end_date}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    name="start_time"
                    type="time"
                    label="Jam mulai (opsional)"
                    value={data.start_time}
                    onChange={(e) => setField('start_time', e.target.value)}
                    error={errors.start_time}
                />
                <FormField
                    name="participants_count"
                    type="number"
                    inputMode="numeric"
                    min={1}
                    max={batas.peserta}
                    label="Jumlah peserta (opsional)"
                    hint={`Termasuk Anda, 1 sampai ${batas.peserta} orang.`}
                    value={data.participants_count}
                    onChange={(e) => setField('participants_count', e.target.value)}
                    error={errors.participants_count}
                />
            </div>

            <div>
                <Label htmlFor="trip_type">Tipe perjalanan</Label>
                <Select
                    id="trip_type"
                    name="trip_type"
                    required
                    value={data.trip_type}
                    onChange={(e) => setField('trip_type', e.target.value)}
                    aria-invalid={errors.trip_type ? true : undefined}
                    aria-describedby={errors.trip_type ? 'trip_type-error' : undefined}
                >
                    <option value="">Pilih tipe perjalanan</option>
                    {tripTypes.map((o) => (
                        <option key={o.value} value={o.value}>{o.label}</option>
                    ))}
                </Select>
                <InputError id="trip_type-error" messages={errors.trip_type} className="mt-2" />
            </div>

            <div>
                <Label htmlFor="notes">Catatan (opsional)</Label>
                <Textarea
                    id="notes"
                    name="notes"
                    rows={3}
                    maxLength={1000}
                    value={data.notes}
                    onChange={(e) => setField('notes', e.target.value)}
                    aria-invalid={errors.notes ? true : undefined}
                    aria-describedby={errors.notes ? 'notes-error' : undefined}
                />
                <InputError id="notes-error" messages={errors.notes} className="mt-2" />
            </div>
        </>
    );
}
