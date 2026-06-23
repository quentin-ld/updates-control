<?php

/**
 * REST controller and orchestrator for update-log plain-text exports.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers POST /updatronix/v1/logs/export and coordinates export sub-services.
 *
 * @since 1.1.0
 */
final class Updatronix_Export {
    public const MAX_ROWS_PER_CHUNK = 5000;
    public const MAX_ROWS_TOTAL = 250000;
    public const MAX_BYTES_PER_CHUNK = 1048576;
    public const MAX_BYTES_TOTAL = 8388608;
    public const HARD_TIME_SECONDS = 15;
    public const TRANSIENT_TTL = 900;

    /**
     * Default export starts allowed per rolling minute, per user and site.
     *
     * Overridable via the `updatronix_export_rate_limit_per_minute` filter
     * (see {@see Updatronix_Export_Rate_Limiter::consume()}).
     *
     * @since 1.1.0
     */
    public const RATE_LIMIT_PER_MINUTE = 30;

    /**
     * Default export starts allowed per rolling hour, per user and site.
     *
     * Overridable via the `updatronix_export_rate_limit_per_hour` filter
     * (see {@see Updatronix_Export_Rate_Limiter::consume()}).
     *
     * @since 1.1.0
     */
    public const RATE_LIMIT_PER_HOUR = 300;
    public const MAX_SEARCH_LENGTH = 200;
    public const PAYLOAD_VERSION = 1;

    /** @var list<string> */
    public const SORT_FIELDS = ['date', 'created_at', 'log_type', 'item_name', 'status', 'performed_as'];

    /** @var list<string> */
    public const SORT_DIRECTIONS = ['asc', 'desc'];

    /** @var list<string> */
    public const FILTER_FIELDS = ['category', 'actionType', 'status', 'triggeredBy', 'runType', 'user', 'date'];

    /** @var list<string> */
    public const CATEGORICAL_OPERATORS = ['is', 'isNot', 'isAny', 'isNone'];

    /** @var list<string> */
    public const DATE_OPERATORS = ['on', 'before', 'after', 'beforeInc', 'afterInc', 'inThePast', 'over', 'between'];

    /** @var list<string> */
    public const COLUMN_KEYS = [
        'table_heading',
        'action_type',
        'run_context',
        'user',
        'status',
        'category',
    ];

    /**
     * Register hooks.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function register(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    /**
     * Register REST routes.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function register_routes(): void {
        register_rest_route(
            Updatronix_Settings::REST_NAMESPACE,
            '/logs/export',
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [self::class, 'rest_export'],
                'permission_callback' => [Updatronix_Security::class, 'user_can_manage_logs'],
                'args' => [
                    'view' => [
                        'required' => true,
                        'type' => 'object',
                    ],
                    'merge' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'columns' => [
                        'type' => 'object',
                    ],
                    'cursor' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ]
        );
    }

    /**
     * REST handler: build plain-text export aligned with DataViews filters.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public static function rest_export(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        /** @var mixed $raw_params */
        $raw_params = $request->get_json_params();
        if (!is_array($raw_params)) {
            return new WP_Error('view_invalid', '', ['status' => 400]);
        }

        /** @var array<string, mixed> $params */
        $params = $raw_params;

        $cursor_raw = isset($params['cursor']) ? trim((string) $params['cursor']) : '';

        $user_id = (int) get_current_user_id();
        $blog_id = (int) get_current_blog_id();

        $site_for_cursor = Updatronix_Export_Request_Schema::resolve_site_id_from_params($params);

        $started = microtime(true);

        $issued_cursor_tokens = 0;
        /** @var array<string, mixed> $validated */
        $validated = [];
        $transient_key = '';
        $offset = 0;
        $accumulated_body = '';
        $total_rows_scanned = 0;
        $merged_lines_total = 0;

        if ($cursor_raw === '') {
            $rate = Updatronix_Export_Rate_Limiter::consume($blog_id, $user_id);
            if (is_wp_error($rate)) {
                return self::decorate_rate_limit_response($rate);
            }

            $validated = Updatronix_Export_Request_Schema::validate($request);
            if (is_wp_error($validated)) {
                return $validated;
            }

            $site_id_new = (int) ($validated['site_id'] ?? $blog_id);
            $transient_key = Updatronix_Export_Transient_Manager::generate_key($site_id_new, $user_id);
            $offset = (int) ($validated['slice_sql_offset'] ?? 0);
        } else {
            $decoded = Updatronix_Export_Cursor::verify($cursor_raw, $site_for_cursor, $user_id);
            if (is_wp_error($decoded)) {
                return $decoded;
            }

            $transient_key = (string) $decoded['k'];
            $offset = (int) $decoded['o'];

            $stored = updatronix_get_plugin_transient($transient_key);
            if (!is_array($stored) || empty($stored['validated_export']) || !is_array($stored['validated_export'])) {
                return new WP_Error('cursor_expired', '', ['status' => 410]);
            }

            /** @var array<string, mixed> $validated */
            $validated = $stored['validated_export'];
            $accumulated_body = isset($stored['body']) ? (string) $stored['body'] : '';
            $total_rows_scanned = (int) ($stored['rows_scanned'] ?? 0);
            $merged_lines_total = (int) ($stored['merged_lines_total'] ?? 0);
            $issued_cursor_tokens = (int) ($stored['issued_cursor_tokens'] ?? 0);
        }

        if (microtime(true) > $started + self::HARD_TIME_SECONDS) {
            return new WP_Error('internal', '', ['status' => 500]);
        }

        $site_id = (int) ($validated['site_id'] ?? $blog_id);
        $merge = isset($validated['merge']) ? (bool) $validated['merge'] : true;
        /** @var array<string, bool> $columns */
        $columns = isset($validated['columns']) && is_array($validated['columns']) ? $validated['columns'] : [];

        $max_rows_attempt = min(
            self::MAX_ROWS_TOTAL,
            (int) apply_filters('updatronix_export_max_rows', self::MAX_ROWS_TOTAL)
        );
        $max_rows_attempt = max(1, min(10_000_000, $max_rows_attempt));

        $slice_max_rows = (int) ($validated['slice_max_rows'] ?? self::MAX_ROWS_TOTAL);
        $slice_max_rows = max(1, min(self::MAX_ROWS_TOTAL, $slice_max_rows));
        $row_cap_job = max(1, min($max_rows_attempt, $slice_max_rows));

        $truncated = false;
        $truncation_reason = '';

        if ($total_rows_scanned >= $row_cap_job) {
            $truncated = true;
            $truncation_reason = 'row_cap';
        }

        $remaining_budget = $row_cap_job - $total_rows_scanned;
        $rows = [];
        $more_in_db = false;
        $chunk_delta = '';
        $rows_emitted = 0;
        $merged_lines_added = 0;
        $partial_batch = false;

        if (!$truncated && $remaining_budget > 0) {
            $per_query = min(self::MAX_ROWS_PER_CHUNK, $remaining_budget);

            $rows = Updatronix_Export_Query_Builder::fetch_rows($validated, $offset, $per_query + 1);
            $more_in_db = count($rows) > $per_query;
            if ($more_in_db) {
                $rows = array_slice($rows, 0, $per_query);
            }

            $render = Updatronix_Export_Body_Builder::render(
                $rows,
                $merge,
                $columns,
                '',
                self::MAX_BYTES_PER_CHUNK
            );

            $chunk_delta = $render['body'];
            $rows_emitted = $render['rows_emitted'];
            $merged_lines_added = $render['merged_lines_added'];

            $total_rows_scanned += $rows_emitted;
            $merged_lines_total += $merged_lines_added;
            $offset += $rows_emitted;

            $row_count_fetched = count($rows);
            $partial_batch = $row_count_fetched > $rows_emitted;
        }

        $glue = ($accumulated_body !== '' && $chunk_delta !== '') ? "\n" : '';
        $new_accumulated = $accumulated_body . $glue . $chunk_delta;

        if (!$truncated && strlen($new_accumulated) > self::MAX_BYTES_TOTAL) {
            $allow = max(0, self::MAX_BYTES_TOTAL - strlen($accumulated_body));
            $chunk_delta = substr($chunk_delta, 0, $allow);
            $glue = ($accumulated_body !== '' && $chunk_delta !== '') ? "\n" : '';
            $new_accumulated = $accumulated_body . $glue . $chunk_delta;
            $truncated = true;
            $truncation_reason = 'byte_cap';
        }

        $needs_continue =
            !$truncated
            && $total_rows_scanned < $row_cap_job
            && strlen($new_accumulated) < self::MAX_BYTES_TOTAL
            && ($more_in_db || $partial_batch);

        $filters_fp = hash('sha256', wp_json_encode($validated['fingerprint_source'] ?? []) ?: '');
        $generated_at = time();

        $payload_meta = [
            'validated_export' => $validated,
            'generated_at' => $generated_at,
            'locale' => determine_locale(),
            'filters_fingerprint' => $filters_fp,
            'merge' => $merge,
            'columns' => $columns,
            'truncated' => $truncated,
            'truncation_reason' => $truncation_reason,
            'version' => self::PAYLOAD_VERSION,
            'issued_cursor_tokens' => $issued_cursor_tokens,
        ];

        if ($needs_continue && ($chunk_delta !== '' || $partial_batch || $more_in_db)) {
            $issued_cursor_tokens++;

            $payload_continue = array_merge(
                $payload_meta,
                [
                    'body' => $new_accumulated,
                    'rows_scanned' => $total_rows_scanned,
                    'merged_lines_total' => $merged_lines_total,
                    'issued_cursor_tokens' => $issued_cursor_tokens,
                ]
            );

            $written = Updatronix_Export_Transient_Manager::replace($user_id, $transient_key, $payload_continue);
            if (is_wp_error($written)) {
                return $written;
            }

            $next_cursor = Updatronix_Export_Cursor::mint($transient_key, $offset, $site_id, $user_id);

            return new WP_REST_Response(
                [
                    'status' => 'ready',
                    'body' => $chunk_delta,
                    'transient_key' => $transient_key,
                    'truncated' => false,
                    'truncation_reason' => '',
                    'next_cursor' => $next_cursor,
                    'truncated_included' => $total_rows_scanned,
                    'truncated_total' => null,
                    'meta' => [
                        'generated_at' => $generated_at,
                        'rows_in_chunk' => $rows_emitted,
                    ],
                ],
                200
            );
        }

        // Terminal response -------------------------------------------------
        $matched_estimate = max(
            $total_rows_scanned,
            $total_rows_scanned + (($more_in_db || $partial_batch) ? 1 : 0)
        );

        $response_body = $chunk_delta;

        if ($truncated) {
            $footer = Updatronix_Export_Body_Builder::truncation_footer($total_rows_scanned, $matched_estimate);
            $response_body .= ($response_body !== '' ? "\n" : '') . $footer;
            $glue_f = ($new_accumulated !== '') ? "\n" : '';
            $terminal_store_body = $new_accumulated . $glue_f . $footer;
        } else {
            $terminal_store_body = $new_accumulated;
        }

        $payload_final = array_merge(
            $payload_meta,
            [
                'body' => $terminal_store_body,
                'rows_scanned' => $total_rows_scanned,
                'merged_lines_total' => $merged_lines_total,
                'truncated' => $truncated,
                'truncation_reason' => $truncation_reason,
            ]
        );

        $written_final = Updatronix_Export_Transient_Manager::replace($user_id, $transient_key, $payload_final);
        if (is_wp_error($written_final)) {
            return $written_final;
        }

        $cursor_count = max(1, $issued_cursor_tokens + 1);

        Updatronix_Export_Audit::append([
            'created_at' => gmdate('c'),
            'user_id' => $user_id,
            'site_id' => $site_id,
            'filters_fingerprint' => $filters_fp,
            'row_count' => $total_rows_scanned,
            'merged_count' => $merged_lines_total,
            'truncated' => $truncated,
            'truncation_reason' => $truncation_reason,
            'cursor_count' => $cursor_count,
        ]);

        try {
            /**
             * Fires after a successful update-log export is stored.
             *
             * @since 1.1.0
             *
             * @param array<string, mixed> $data Hook payload (no transient key, raw view, or body).
             */
            do_action(
                'updatronix_after_export_logs',
                [
                    'user_id' => $user_id,
                    'site_id' => $site_id,
                    'row_count' => $total_rows_scanned,
                    'merged_count' => $merged_lines_total,
                    'truncated' => $truncated,
                    'truncation_reason' => $truncation_reason,
                    'columns' => $columns,
                    'merge' => $merge,
                    'filters_fingerprint' => $filters_fp,
                    'generated_at' => $generated_at,
                    'locale' => determine_locale(),
                    'cursor_count' => $cursor_count,
                    'version' => self::PAYLOAD_VERSION,
                ]
            );
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated diagnostic only.
                error_log('[updatronix] updatronix_after_export_logs listener threw');
            }
        }

        return new WP_REST_Response(
            [
                'status' => 'ready',
                'body' => $response_body,
                'transient_key' => $transient_key,
                'truncated' => $truncated,
                'truncation_reason' => $truncation_reason,
                'next_cursor' => null,
                'truncated_included' => $total_rows_scanned,
                'truncated_total' => $truncated ? $matched_estimate : $total_rows_scanned,
                'view_applied' => $validated['view_applied'] ?? [],
                'meta' => [
                    'generated_at' => $generated_at,
                    'rows_in_chunk' => $rows_emitted,
                ],
            ],
            200
        );
    }

    /**
     * Attach a `Retry-After` header to a rate-limit error response.
     *
     * @since 1.1.0
     *
     * @param \WP_Error $error Error from the rate limiter.
     * @return \WP_REST_Response
     */
    private static function decorate_rate_limit_response(\WP_Error $error): \WP_REST_Response {
        $response = rest_convert_error_to_response($error);
        $response->header('Retry-After', '60');

        return $response;
    }
}
