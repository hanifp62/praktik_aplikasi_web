import { Icon } from './Icon';

/**
 * Satu pos beserta apa yang terjadi di ANTARA pos sebelumnya dan pos ini, semuanya
 * sudah dihitung dan diformat server (haversine lewat GpxTrack, tempo dari rekaman
 * pendaki). Klien hanya menggambar.
 */
export interface PosPerjalanan {
    id: number;
    sequence: number;
    name: string;
    typeLabel: string;
    elevasi: string | null;
    notes: string | null;
    antara: string | null;
    tempo: string | null;
}

export function CheckpointJourney({ checkpoints, className }: { checkpoints: PosPerjalanan[]; className?: string }) {
    return (
        <ol className={className}>
            {checkpoints.map((pos) => (
                <li key={pos.id} className="contents">
                    {pos.antara ? (
                        <div className="flex items-stretch gap-3" aria-hidden="true">
                            <div className="flex w-7 justify-center">
                                <span className="w-px bg-subtle" />
                            </div>
                            <div className="py-1 text-xs">
                                <p className="flex items-center gap-1 text-secondary">
                                    {pos.antara.includes('naik') ? <Icon name="arrow-up" size={16} /> : null}
                                    {pos.antara.includes('turun') ? <Icon name="arrow-down" size={16} /> : null}
                                    {pos.antara}
                                </p>
                                {pos.tempo ? <p className="mt-0.5 text-secondary">{pos.tempo}</p> : null}
                            </div>
                        </div>
                    ) : null}
                    <div className="flex items-start gap-3">
                        <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-xs font-medium text-secondary">{pos.sequence}</span>
                        <div className="min-w-0 text-sm">
                            <p>
                                <span className="font-medium text-primary">{pos.name}</span>
                                <span className="text-secondary"> &middot; {pos.typeLabel}</span>
                                {pos.elevasi ? <span className="text-secondary"> &middot; {pos.elevasi}</span> : null}
                            </p>
                            {pos.notes ? <p className="mt-0.5 text-secondary">{pos.notes}</p> : null}
                            {pos.antara ? <p className="sr-only">Dari pos sebelumnya: {pos.antara}.</p> : null}
                        </div>
                    </div>
                </li>
            ))}
        </ol>
    );
}
