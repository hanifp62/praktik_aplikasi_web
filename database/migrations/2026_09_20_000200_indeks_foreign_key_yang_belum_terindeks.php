<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk foreign key yang belum punya index penutup.
 *
 * Laravel membuat constraint foreign key tanpa index, dan Postgres tidak membuatkannya
 * sendiri seperti pada primary key. Selama tabelnya kecil hal ini tidak terasa, tetapi
 * setiap join dan setiap penghapusan induk berubah menjadi seq scan begitu datanya
 * bertumbuh. Yang paling sering dilewati adalah kolom yang dipakai di halaman panas:
 * trip_plans.user_id, trail_condition_reports.trail_id, recommendation_results.*.
 *
 * Daftarnya berasal dari advisor Supabase, bukan dari tebakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->kolom() as $tabel => $kolomKolom) {
            Schema::table($tabel, function (Blueprint $table) use ($kolomKolom) {
                foreach ($kolomKolom as $kolom) {
                    $table->index($kolom);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->kolom() as $tabel => $kolomKolom) {
            Schema::table($tabel, function (Blueprint $table) use ($tabel, $kolomKolom) {
                foreach ($kolomKolom as $kolom) {
                    $table->dropIndex("{$tabel}_{$kolom}_index");
                }
            });
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function kolom(): array
    {
        return [
            'analytics_events' => ['user_id'],
            'audit_logs' => ['actor_id'],
            'checkpoints' => ['trail_segment_id'],
            'hiking_goals' => ['user_id'],
            'hiking_history' => ['user_id', 'trip_plan_id', 'trail_id', 'trail_condition_report_id'],
            'hiking_sessions' => ['trip_plan_id', 'user_id', 'current_checkpoint_id'],
            'moderation_actions' => ['moderator_id'],
            'mountains' => ['data_source_id'],
            'official_statuses' => ['data_source_id', 'recorded_by'],
            'permit_requirements' => ['data_source_id'],
            'preparation_items' => ['preparation_template_id'],
            'preparation_templates' => ['trail_id'],
            'readiness_checks' => ['trip_plan_id', 'recommendation_result_id'],
            'recommendation_results' => ['recommendation_run_id', 'trail_id'],
            'recommendation_runs' => ['user_id', 'hiking_goal_id'],
            'restricted_areas' => ['mountain_id', 'trail_id', 'data_source_id'],
            'trail_condition_reports' => ['trail_id', 'trail_segment_id', 'user_id', 'moderated_by'],
            'trails' => ['mountain_id', 'data_source_id'],
            'trip_plans' => ['user_id', 'trail_id', 'hiking_goal_id'],
            'trip_preparation_items' => ['trip_plan_id', 'preparation_item_id'],
        ];
    }
};
