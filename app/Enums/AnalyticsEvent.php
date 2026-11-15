<?php

namespace App\Enums;

enum AnalyticsEvent: string
{
    case PUBLIC_TRAIL_VIEWED = 'public_trail_viewed';
    case PROFILE_COMPLETED = 'profile_completed';
    case RECOMMENDATION_VIEWED = 'route_recommendation_viewed';
    case ROUTE_SELECTED = 'route_selected';
    case TRIP_CREATED = 'trip_created';
    case PREPARATION_STARTED = 'preparation_started';
    case PRE_DEPARTURE_CHECK_COMPLETED = 'pre_departure_check_completed';
    case TRIP_COMPLETED = 'trip_completed';
    case CONDITION_REPORT_SUBMITTED = 'condition_report_submitted';
    case RETURN_VISIT_AFTER_TRIP = 'return_visit_after_trip';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC_TRAIL_VIEWED => 'Halaman jalur publik dilihat',
            self::PROFILE_COMPLETED => 'Profil selesai',
            self::RECOMMENDATION_VIEWED => 'Rekomendasi dilihat',
            self::ROUTE_SELECTED => 'Jalur dipilih',
            self::TRIP_CREATED => 'Trip dibuat',
            self::PREPARATION_STARTED => 'Persiapan dimulai',
            self::PRE_DEPARTURE_CHECK_COMPLETED => 'Pre-departure check selesai',
            self::TRIP_COMPLETED => 'Trip selesai',
            self::CONDITION_REPORT_SUBMITTED => 'Laporan kondisi dikirim',
            self::RETURN_VISIT_AFTER_TRIP => 'Kunjungan kembali sesudah pendakian',
        };
    }
}
