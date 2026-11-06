# Snapshot skema Supabase sebelum 7 migrasi hardening

Diambil 2026-09-19 23:47:55 UTC dari proyek tixdhwimpajpclnicphx.

Jaring pengaman. Pemulihan sebenarnya adalah `php artisan migrate:rollback --step=7`;
berkas ini untuk membandingkan bila ada yang meleset.


## analytics_events

- id bigint NOT NULL DEFAULT nextval('analytics_events_id_seq'::regclass)
- user_id bigint
- event_name character varying NOT NULL
- payload json
- created_at timestamp without time zone
- updated_at timestamp without time zone

## audit_logs

- id bigint NOT NULL DEFAULT nextval('audit_logs_id_seq'::regclass)
- actor_id bigint
- action character varying NOT NULL
- entity_type character varying
- entity_id bigint
- before json
- after json
- ip_address character varying
- created_at timestamp without time zone
- updated_at timestamp without time zone

## cache

- key character varying NOT NULL
- value text NOT NULL
- expiration bigint NOT NULL

## cache_locks

- key character varying NOT NULL
- owner character varying NOT NULL
- expiration bigint NOT NULL

## checkpoints

- id bigint NOT NULL DEFAULT nextval('checkpoints_id_seq'::regclass)
- trail_id bigint NOT NULL
- trail_segment_id bigint
- sequence integer NOT NULL
- name character varying NOT NULL
- checkpoint_type character varying NOT NULL DEFAULT 'POS'::character varying
- elevation_m integer
- notes text
- created_at timestamp without time zone
- updated_at timestamp without time zone
- location USER-DEFINED

## data_sources

- id bigint NOT NULL DEFAULT nextval('data_sources_id_seq'::regclass)
- source_name character varying NOT NULL
- source_type character varying NOT NULL
- source_url character varying
- source_owner character varying
- retrieved_at timestamp without time zone
- verified_at timestamp without time zone
- freshness_policy character varying
- verification_status character varying NOT NULL DEFAULT 'UNVERIFIED'::character varying
- notes text
- created_at timestamp without time zone
- updated_at timestamp without time zone

## failed_jobs

- id bigint NOT NULL DEFAULT nextval('failed_jobs_id_seq'::regclass)
- uuid character varying NOT NULL
- connection character varying NOT NULL
- queue character varying NOT NULL
- payload text NOT NULL
- exception text NOT NULL
- failed_at timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP

## geography_columns

- f_table_catalog name
- f_table_schema name
- f_table_name name
- f_geography_column name
- coord_dimension integer
- srid integer
- type text

## geometry_columns

- f_table_catalog character varying
- f_table_schema name
- f_table_name name
- f_geometry_column name
- coord_dimension integer
- srid integer
- type character varying

## hiking_goals

- id bigint NOT NULL DEFAULT nextval('hiking_goals_id_seq'::regclass)
- user_id bigint NOT NULL
- target_date date
- region character varying
- trip_type character varying NOT NULL
- expected_duration_minutes integer
- preferred_challenge character varying
- max_elevation_gain_m integer
- notes text
- created_at timestamp without time zone
- updated_at timestamp without time zone

## hiking_history

- id bigint NOT NULL DEFAULT nextval('hiking_history_id_seq'::regclass)
- user_id bigint NOT NULL
- trip_plan_id bigint NOT NULL
- trail_id bigint NOT NULL
- trail_condition_report_id bigint
- trip_type character varying NOT NULL
- completion_state character varying NOT NULL
- preparation_completion_percent integer NOT NULL DEFAULT 0
- personal_notes text
- completed_at timestamp without time zone NOT NULL
- created_at timestamp without time zone
- updated_at timestamp without time zone

## hiking_sessions

- id bigint NOT NULL DEFAULT nextval('hiking_sessions_id_seq'::regclass)
- trip_plan_id bigint NOT NULL
- user_id bigint NOT NULL
- current_checkpoint_id bigint
- status character varying NOT NULL DEFAULT 'ACTIVE'::character varying
- started_at timestamp without time zone NOT NULL
- ended_at timestamp without time zone
- location_updated_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone
- last_known_location USER-DEFINED

## job_batches

- id character varying NOT NULL
- name character varying NOT NULL
- total_jobs integer NOT NULL
- pending_jobs integer NOT NULL
- failed_jobs integer NOT NULL
- failed_job_ids text NOT NULL
- options text
- cancelled_at integer
- created_at integer NOT NULL
- finished_at integer

## jobs

- id bigint NOT NULL DEFAULT nextval('jobs_id_seq'::regclass)
- queue character varying NOT NULL
- payload text NOT NULL
- attempts smallint NOT NULL
- reserved_at integer
- available_at integer NOT NULL
- created_at integer NOT NULL

## migrations

- id integer NOT NULL DEFAULT nextval('migrations_id_seq'::regclass)
- migration character varying NOT NULL
- batch integer NOT NULL

## moderation_actions

- id bigint NOT NULL DEFAULT nextval('moderation_actions_id_seq'::regclass)
- moderator_id bigint NOT NULL
- moderatable_type character varying NOT NULL
- moderatable_id bigint NOT NULL
- action character varying NOT NULL
- reason text
- created_at timestamp without time zone
- updated_at timestamp without time zone

## mountains

- id bigint NOT NULL DEFAULT nextval('mountains_id_seq'::regclass)
- name character varying NOT NULL
- slug character varying NOT NULL
- province character varying
- region character varying
- elevation_mdpl integer
- description text
- data_source_id bigint
- archived_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone
- location USER-DEFINED

## official_statuses

- id bigint NOT NULL DEFAULT nextval('official_statuses_id_seq'::regclass)
- statusable_type character varying NOT NULL
- statusable_id bigint NOT NULL
- scope character varying NOT NULL
- status character varying NOT NULL DEFAULT 'UNKNOWN'::character varying
- data_source_id bigint
- source character varying
- source_url character varying
- published_at timestamp without time zone
- fetched_at timestamp without time zone
- verified_at timestamp without time zone
- effective_at timestamp without time zone
- expires_at timestamp without time zone
- reason text
- notes text
- recorded_by bigint
- created_at timestamp without time zone
- updated_at timestamp without time zone

## password_reset_tokens

- email character varying NOT NULL
- token character varying NOT NULL
- created_at timestamp without time zone

## preparation_items

- id bigint NOT NULL DEFAULT nextval('preparation_items_id_seq'::regclass)
- preparation_template_id bigint NOT NULL
- category character varying NOT NULL
- label character varying NOT NULL
- description text
- is_critical boolean NOT NULL DEFAULT false
- sort_order integer NOT NULL DEFAULT 0
- applies_when json
- created_at timestamp without time zone
- updated_at timestamp without time zone

## preparation_templates

- id bigint NOT NULL DEFAULT nextval('preparation_templates_id_seq'::regclass)
- trail_id bigint
- name character varying NOT NULL
- description text
- is_default boolean NOT NULL DEFAULT false
- created_at timestamp without time zone
- updated_at timestamp without time zone

## profiles

- id bigint NOT NULL DEFAULT nextval('profiles_id_seq'::regclass)
- user_id bigint NOT NULL
- experience_level character varying
- region_preference character varying
- bio text
- avatar_path character varying
- completed_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone

## readiness_checks

- id bigint NOT NULL DEFAULT nextval('readiness_checks_id_seq'::regclass)
- trip_plan_id bigint NOT NULL
- recommendation_result_id bigint
- computed_state character varying NOT NULL
- route_fit_snapshot json
- preparation_state json
- official_status_snapshot json
- condition_snapshot json
- explanation json
- pre_departure_confirmed boolean NOT NULL DEFAULT false
- computed_at timestamp without time zone NOT NULL
- created_at timestamp without time zone
- updated_at timestamp without time zone

## recommendation_results

- id bigint NOT NULL DEFAULT nextval('recommendation_results_id_seq'::regclass)
- recommendation_run_id bigint NOT NULL
- trail_id bigint NOT NULL
- eligible boolean NOT NULL DEFAULT true
- label character varying
- internal_score numeric
- matched_factors json
- failed_rules json
- warnings json
- explanation json
- rank integer
- created_at timestamp without time zone
- updated_at timestamp without time zone

## recommendation_rules

- id bigint NOT NULL DEFAULT nextval('recommendation_rules_id_seq'::regclass)
- key character varying NOT NULL
- category character varying NOT NULL
- description text
- weight numeric NOT NULL DEFAULT '0'::numeric
- active boolean NOT NULL DEFAULT true
- engine_version character varying NOT NULL DEFAULT 'v1'::character varying
- created_at timestamp without time zone
- updated_at timestamp without time zone

## recommendation_runs

- id bigint NOT NULL DEFAULT nextval('recommendation_runs_id_seq'::regclass)
- user_id bigint NOT NULL
- hiking_goal_id bigint
- engine_version character varying NOT NULL
- input_snapshot json NOT NULL
- rules_evaluated json
- warnings json
- generated_at timestamp without time zone NOT NULL
- created_at timestamp without time zone
- updated_at timestamp without time zone

## restricted_areas

- id bigint NOT NULL DEFAULT nextval('restricted_areas_id_seq'::regclass)
- mountain_id bigint
- trail_id bigint
- name character varying NOT NULL
- reason text
- data_source_id bigint
- effective_at timestamp without time zone
- expires_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone
- geometry USER-DEFINED

## sessions

- id character varying NOT NULL
- user_id bigint
- ip_address character varying
- user_agent text
- payload text NOT NULL
- last_activity integer NOT NULL

## spatial_ref_sys

- srid integer NOT NULL
- auth_name character varying
- auth_srid integer
- srtext character varying
- proj4text character varying

## trail_condition_reports

- id bigint NOT NULL DEFAULT nextval('trail_condition_reports_id_seq'::regclass)
- trail_id bigint NOT NULL
- trail_segment_id bigint
- user_id bigint NOT NULL
- hike_date date NOT NULL
- condition_tags json NOT NULL
- photo_path character varying
- note text
- moderation_status character varying NOT NULL DEFAULT 'PENDING'::character varying
- moderated_by bigint
- moderated_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone

## trail_segments

- id bigint NOT NULL DEFAULT nextval('trail_segments_id_seq'::regclass)
- trail_id bigint NOT NULL
- sequence integer NOT NULL
- name character varying NOT NULL
- description text
- distance_km numeric
- elevation_gain_m integer
- technical_demand character varying NOT NULL DEFAULT 'MODERATE'::character varying
- terrain_character json
- created_at timestamp without time zone
- updated_at timestamp without time zone
- geometry USER-DEFINED

## trails

- id bigint NOT NULL DEFAULT nextval('trails_id_seq'::regclass)
- mountain_id bigint NOT NULL
- name character varying NOT NULL
- slug character varying NOT NULL
- description text
- distance_km numeric
- elevation_gain_m integer
- elevation_loss_m integer
- estimated_duration_minutes integer
- technical_demand character varying NOT NULL DEFAULT 'MODERATE'::character varying
- terrain_character json
- navigation_complexity character varying NOT NULL DEFAULT 'MODERATE'::character varying
- water_availability character varying NOT NULL DEFAULT 'UNKNOWN'::character varying
- camping_available boolean NOT NULL DEFAULT false
- starting_point character varying
- weather_adm4_code character varying
- weather_reference_area character varying
- data_source_id bigint
- is_published boolean NOT NULL DEFAULT false
- archived_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone
- geometry USER-DEFINED

## trip_plans

- id bigint NOT NULL DEFAULT nextval('trip_plans_id_seq'::regclass)
- user_id bigint NOT NULL
- trail_id bigint NOT NULL
- hiking_goal_id bigint
- name character varying NOT NULL
- planned_date date NOT NULL
- start_time time without time zone
- trip_type character varying NOT NULL
- notes text
- status character varying NOT NULL DEFAULT 'DRAFT'::character varying
- completed_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone

## trip_preparation_items

- id bigint NOT NULL DEFAULT nextval('trip_preparation_items_id_seq'::regclass)
- trip_plan_id bigint NOT NULL
- preparation_item_id bigint
- category character varying NOT NULL
- label character varying NOT NULL
- description text
- is_critical boolean NOT NULL DEFAULT false
- status character varying NOT NULL DEFAULT 'NOT_CONFIRMED'::character varying
- status_updated_at timestamp without time zone
- created_at timestamp without time zone
- updated_at timestamp without time zone

## user_experience

- id bigint NOT NULL DEFAULT nextval('user_experience_id_seq'::regclass)
- user_id bigint NOT NULL
- completed_hikes_count integer NOT NULL DEFAULT 0
- terrain_experience json
- navigation_experience character varying
- longest_hike_duration_minutes integer
- highest_elevation_gain_m integer
- notes text
- created_at timestamp without time zone
- updated_at timestamp without time zone

## user_preferences

- id bigint NOT NULL DEFAULT nextval('user_preferences_id_seq'::regclass)
- user_id bigint NOT NULL
- preferred_duration character varying
- preferred_trip_type character varying
- preferred_challenge character varying
- max_elevation_gain_preference_m integer
- region_preference character varying
- created_at timestamp without time zone
- updated_at timestamp without time zone

## users

- id bigint NOT NULL DEFAULT nextval('users_id_seq'::regclass)
- name character varying NOT NULL
- email character varying NOT NULL
- email_verified_at timestamp without time zone
- password character varying NOT NULL
- remember_token character varying
- created_at timestamp without time zone
- updated_at timestamp without time zone
- role character varying NOT NULL DEFAULT 'hiker'::character varying

## weather_snapshots

- id bigint NOT NULL DEFAULT nextval('weather_snapshots_id_seq'::regclass)
- trail_id bigint
- adm4_code character varying NOT NULL
- reference_area character varying
- local_datetime timestamp without time zone NOT NULL
- weather_description character varying
- temperature_c numeric
- humidity_percent integer
- wind_speed_kmh numeric
- wind_direction character varying
- cloud_cover_percent integer
- visibility_m integer
- analysis_date timestamp without time zone
- source character varying NOT NULL DEFAULT 'BMKG'::character varying
- fetched_at timestamp without time zone NOT NULL
- created_at timestamp without time zone
- updated_at timestamp without time zone

## Constraint

- analytics_events: analytics_events_pkey (p)
- analytics_events: analytics_events_user_id_foreign (f)
- audit_logs: audit_logs_actor_id_foreign (f)
- audit_logs: audit_logs_pkey (p)
- cache: cache_pkey (p)
- cache_locks: cache_locks_pkey (p)
- checkpoints: checkpoints_pkey (p)
- checkpoints: checkpoints_trail_id_foreign (f)
- checkpoints: checkpoints_trail_id_sequence_unique (u)
- checkpoints: checkpoints_trail_segment_id_foreign (f)
- data_sources: data_sources_pkey (p)
- failed_jobs: failed_jobs_pkey (p)
- failed_jobs: failed_jobs_uuid_unique (u)
- hiking_goals: hiking_goals_pkey (p)
- hiking_goals: hiking_goals_user_id_foreign (f)
- hiking_history: hiking_history_pkey (p)
- hiking_history: hiking_history_trail_condition_report_id_foreign (f)
- hiking_history: hiking_history_trail_id_foreign (f)
- hiking_history: hiking_history_trip_plan_id_foreign (f)
- hiking_history: hiking_history_user_id_foreign (f)
- hiking_sessions: hiking_sessions_current_checkpoint_id_foreign (f)
- hiking_sessions: hiking_sessions_pkey (p)
- hiking_sessions: hiking_sessions_trip_plan_id_foreign (f)
- hiking_sessions: hiking_sessions_user_id_foreign (f)
- job_batches: job_batches_pkey (p)
- jobs: jobs_pkey (p)
- migrations: migrations_pkey (p)
- moderation_actions: moderation_actions_moderator_id_foreign (f)
- moderation_actions: moderation_actions_pkey (p)
- mountains: mountains_data_source_id_foreign (f)
- mountains: mountains_pkey (p)
- mountains: mountains_slug_unique (u)
- official_statuses: official_statuses_data_source_id_foreign (f)
- official_statuses: official_statuses_pkey (p)
- official_statuses: official_statuses_recorded_by_foreign (f)
- password_reset_tokens: password_reset_tokens_pkey (p)
- preparation_items: preparation_items_pkey (p)
- preparation_items: preparation_items_preparation_template_id_foreign (f)
- preparation_templates: preparation_templates_pkey (p)
- preparation_templates: preparation_templates_trail_id_foreign (f)
- profiles: profiles_pkey (p)
- profiles: profiles_user_id_foreign (f)
- profiles: profiles_user_id_unique (u)
- readiness_checks: readiness_checks_pkey (p)
- readiness_checks: readiness_checks_recommendation_result_id_foreign (f)
- readiness_checks: readiness_checks_trip_plan_id_foreign (f)
- recommendation_results: recommendation_results_pkey (p)
- recommendation_results: recommendation_results_recommendation_run_id_foreign (f)
- recommendation_results: recommendation_results_trail_id_foreign (f)
- recommendation_rules: recommendation_rules_key_unique (u)
- recommendation_rules: recommendation_rules_pkey (p)
- recommendation_runs: recommendation_runs_hiking_goal_id_foreign (f)
- recommendation_runs: recommendation_runs_pkey (p)
- recommendation_runs: recommendation_runs_user_id_foreign (f)
- restricted_areas: restricted_areas_data_source_id_foreign (f)
- restricted_areas: restricted_areas_mountain_id_foreign (f)
- restricted_areas: restricted_areas_pkey (p)
- restricted_areas: restricted_areas_trail_id_foreign (f)
- sessions: sessions_pkey (p)
- spatial_ref_sys: spatial_ref_sys_pkey (p)
- trail_condition_reports: trail_condition_reports_moderated_by_foreign (f)
- trail_condition_reports: trail_condition_reports_pkey (p)
- trail_condition_reports: trail_condition_reports_trail_id_foreign (f)
- trail_condition_reports: trail_condition_reports_trail_segment_id_foreign (f)
- trail_condition_reports: trail_condition_reports_user_id_foreign (f)
- trail_segments: trail_segments_pkey (p)
- trail_segments: trail_segments_trail_id_foreign (f)
- trail_segments: trail_segments_trail_id_sequence_unique (u)
- trails: trails_data_source_id_foreign (f)
- trails: trails_mountain_id_foreign (f)
- trails: trails_pkey (p)
- trails: trails_slug_unique (u)
- trip_plans: trip_plans_hiking_goal_id_foreign (f)
- trip_plans: trip_plans_pkey (p)
- trip_plans: trip_plans_trail_id_foreign (f)
- trip_plans: trip_plans_user_id_foreign (f)
- trip_preparation_items: trip_preparation_items_pkey (p)
- trip_preparation_items: trip_preparation_items_preparation_item_id_foreign (f)
- trip_preparation_items: trip_preparation_items_trip_plan_id_foreign (f)
- user_experience: user_experience_pkey (p)
- user_experience: user_experience_user_id_foreign (f)
- user_experience: user_experience_user_id_unique (u)
- user_preferences: user_preferences_pkey (p)
- user_preferences: user_preferences_user_id_foreign (f)
- user_preferences: user_preferences_user_id_unique (u)
- users: users_email_unique (u)
- users: users_pkey (p)
- weather_snapshots: weather_snapshots_adm4_code_local_datetime_unique (u)
- weather_snapshots: weather_snapshots_pkey (p)
- weather_snapshots: weather_snapshots_trail_id_foreign (f)
