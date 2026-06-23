<?php

/**
 * CRUD operations for the update log table
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create, read, and delete update logs.
 */
final class Updatronix_Logger {
    /**
     * Cache group for individual log lookups.
     *
     * @var string
     */
    private const CACHE_GROUP = 'updatronix_logs';

    /**
     * Run a callback against the main-site logs table on Multisite.
     *
     * @template T
     * @param callable(): T $callback Callback.
     * @return T
     */
    private static function with_logs_table(callable $callback) {
        return updatronix_with_main_site($callback);
    }

    /**
     * Cache key storing the current logs cache version.
     *
     * @var string
     */
    private const CACHE_LAST_CHANGED_KEY = 'last_changed';

    /**
     * Insert a log entry.
     *
     * @param string $log_type       One of: core, plugin, theme, translation.
     * @param string $action_type    One of: update, downgrade, install, same_version, failed, uninstall.
     * @param string $item_name      Display name of the item.
     * @param string $item_slug      Slug/identifier.
     * @param string $version_before Previous version.
     * @param string $version_after  New version after update.
     * @param string $status         success, error, cancelled.
     * @param string $message        Optional message (e.g. process log, error details).
     * @param string $trace          Optional call stack trace.
     * @param string $performed_as   manual, automatic, or upload.
     * @param string $update_context bulk, single, or '' (for core/translation/legacy).
     * @param string $event_key      Optional canonical event key for dedupe.
     * @return int|false Log ID on success, false on failure.
     */
    public static function log(
        string $log_type = 'plugin',
        string $action_type = 'update',
        string $item_name = '',
        string $item_slug = '',
        string $version_before = '',
        string $version_after = '',
        string $status = 'success',
        string $message = '',
        string $trace = '',
        string $performed_as = 'manual',
        string $update_context = '',
        string $event_key = ''
    ): int|false {
        if (!Updatronix_Database::table_exists()) {
            return false;
        }

        $log_type = Updatronix_Security::sanitize_log_type($log_type);
        $action_type = Updatronix_Security::sanitize_action_type($action_type);
        $item_name = Updatronix_Security::sanitize_string($item_name, 255);
        $item_slug = Updatronix_Security::sanitize_string($item_slug, 255);
        $version_before = Updatronix_Security::sanitize_version($version_before);
        $version_after = Updatronix_Security::sanitize_version($version_after);
        $status = Updatronix_Security::sanitize_status($status);
        $message = Updatronix_Security::sanitize_message($message);
        $trace = Updatronix_Security::sanitize_trace($trace);
        $performed_as = Updatronix_Security::sanitize_performed_as($performed_as);
        $update_context = Updatronix_Security::sanitize_update_context($update_context);
        $event_key = Updatronix_Security::sanitize_string($event_key, 191);

        if ($event_key !== '') {
            $existing_id = self::get_log_id_by_event_key($event_key);
            if ($existing_id > 0) {
                return $existing_id;
            }
        }

        $site_id = (int) get_current_blog_id();
        $user_id = (int) get_current_user_id();
        $performed_by = $user_id > 0 ? 'user' : 'system';

        return self::with_logs_table(static function () use (
            $log_type,
            $action_type,
            $item_name,
            $item_slug,
            $version_before,
            $version_after,
            $status,
            $message,
            $trace,
            $performed_as,
            $update_context,
            $event_key,
            $site_id,
            $user_id,
            $performed_by
        ): int|false {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();
            $insert_data = [
                'site_id' => $site_id,
                'log_type' => $log_type,
                'action_type' => $action_type,
                'item_name' => $item_name,
                'item_slug' => $item_slug,
                'version_before' => $version_before,
                'version_after' => $version_after,
                'status' => $status,
                'message' => $message,
                'trace' => $trace,
                'user_id' => $user_id,
                'performed_by' => $performed_by,
                'performed_as' => $performed_as,
                'update_context' => $update_context,
                'created_at' => current_time('mysql'),
            ];
            $format = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'];

            if ($event_key !== '') {
                $insert_data['event_key'] = $event_key;
                $format[] = '%s';
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; $wpdb->insert is the correct API, prepared internally.
            $result = $wpdb->insert(
                $table,
                $insert_data,
                $format
            );

            if ($result === false) {
                if ($event_key !== '') {
                    $existing_id = self::get_log_id_by_event_key($event_key);
                    if ($existing_id > 0) {
                        return $existing_id;
                    }
                }

                return false;
            }

            $log_id = (int) $wpdb->insert_id;
            $data = [
                'log_type' => $log_type,
                'action_type' => $action_type,
                'item_name' => $item_name,
                'item_slug' => $item_slug,
                'version_before' => $version_before,
                'version_after' => $version_after,
                'status' => $status,
                'message' => $message,
                'trace' => $trace,
                'update_context' => $update_context,
                'event_key' => $event_key,
                'created_at' => current_time('mysql'),
            ];
            self::bump_logs_cache_last_changed();

            /**
             * Fires after a log entry is stored in the custom audit log table.
             *
             * Hook for integrations that should react when a new row is persisted.
             *
             * @since 1.0.0
             *
             * @param int                    $log_id New log row ID.
             * @param array<string, mixed>   $data   Snapshot of stored fields (log_type, action_type, item_name, item_slug, version_before, version_after, status, message, trace, update_context, event_key, created_at).
             */
            do_action('updatronix_after_log', $log_id, $data);

            return $log_id;
        });
    }

    /**
     * Build WHERE clause and placeholder values for log queries.
     *
     * @param array<string, mixed> $args Keys: site_id, log_type, status, performed_as.
     * @return array{where_sql: string, values: array<int, int|string>}
     */
    private static function build_logs_where(array $args): array {
        $where = ['1=1'];
        $values = [];
        $site_id = $args['site_id'] ?? null;
        // A site_id of 0 (or negative) is the network-global sentinel: no scope filter,
        // so a Super Admin sees every originating site's rows. See Updatronix_Settings::resolve_site_id().
        if ($site_id !== null && (int) $site_id > 0) {
            $where[] = 'site_id = %d';
            $values[] = (int) $site_id;
        }
        $log_type = $args['log_type'] ?? null;
        if ($log_type !== null && $log_type !== '') {
            $where[] = 'log_type = %s';
            $values[] = Updatronix_Security::sanitize_log_type((string) $log_type);
        }
        $status = $args['status'] ?? null;
        if ($status !== null && $status !== '') {
            $where[] = 'status = %s';
            $values[] = Updatronix_Security::sanitize_status((string) $status);
        }
        $performed_as = $args['performed_as'] ?? null;
        if ($performed_as !== null && $performed_as !== '') {
            $where[] = 'performed_as = %s';
            $values[] = Updatronix_Security::sanitize_performed_as((string) $performed_as);
        }

        return ['where_sql' => implode(' AND ', $where), 'values' => $values];
    }

    /**
     * Get logs with optional filters and pagination.
     *
     * @param array<string, mixed> $args            Optional. site_id, log_type, status, performed_as, per_page, page, orderby, order.
     * @param bool                 $include_details Whether to include message and trace columns.
     * @return array<int, object> Array of log row objects.
     */
    public static function get_logs(array $args = [], bool $include_details = true): array {
        if (!Updatronix_Database::table_exists()) {
            return [];
        }

        return self::with_logs_table(static function () use ($args, $include_details): array {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();

            $defaults = [
                'site_id' => null,
                'log_type' => null,
                'status' => null,
                'performed_as' => null,
                'per_page' => 50,
                'page' => 1,
                'orderby' => 'created_at',
                'order' => 'DESC',
            ];
            $args = wp_parse_args($args, $defaults);

            $built = self::build_logs_where($args);
            $orderby = in_array($args['orderby'], ['id', 'created_at', 'log_type', 'status', 'item_name', 'performed_as'], true)
                ? $args['orderby']
                : 'created_at';
            $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
            $per_page = max(1, min(200, (int) $args['per_page']));
            $offset = max(0, ((int) $args['page']) - 1) * $per_page;
            $select = $include_details
                ? '*'
                : "id, site_id, log_type, action_type, item_name, item_slug, version_before, version_after, status, user_id, performed_by, performed_as, update_context, created_at, event_key, CASE WHEN COALESCE(message, '') <> '' OR COALESCE(trace, '') <> '' THEN 1 ELSE 0 END AS detail_available";

            $values = array_merge([$table], $built['values'], [$per_page, $offset]);

            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Table/orderby via %i; user input only in $values.
            $prepared = $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_sql and $order are whitelisted (literal fragments and ASC/DESC).
                "SELECT {$select} FROM %i WHERE {$built['where_sql']} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $values
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $prepared from prepare() above; user input only in bound $values.
            $results = $wpdb->get_results($prepared);

            return is_array($results) ? $results : [];
        });
    }

    /**
     * Get total count of logs matching filters.
     *
     * @param array<string, mixed> $args Optional. site_id, log_type, status, performed_as.
     * @return int
     */
    public static function get_logs_count(array $args = []): int {
        if (!Updatronix_Database::table_exists()) {
            return 0;
        }

        return self::with_logs_table(static function () use ($args): int {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();

            $built = self::build_logs_where($args);
            $values = array_merge([$table], $built['values']);

            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Table via %i; user input only in $values.
            $prepared = $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- where_sql is whitelisted literal fragments only.
                "SELECT COUNT(*) FROM %i WHERE {$built['where_sql']}",
                $values
            );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $prepared from prepare() above; user input only in bound $values.
            return (int) $wpdb->get_var($prepared);
        });
    }

    /**
     * Get a single log entry by ID.
     *
     * @param int  $id Log ID.
     * @param bool $include_details Whether to include message and trace.
     * @return object|null
     */
    public static function get_log(int $id, bool $include_details = true): ?object {
        if (!Updatronix_Database::table_exists() || $id < 1) {
            return null;
        }

        return self::with_logs_table(static function () use ($id, $include_details): ?object {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();
            $id = absint($id);
            $cache_key = self::get_log_cache_key($id, $include_details);
            $found = false;
            $cached = wp_cache_get($cache_key, self::CACHE_GROUP, false, $found);

            if ($found) {
                return is_object($cached) ? $cached : null;
            }

            if ($include_details) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Prepared below; custom table read for plugin-owned table, cached via wp_cache_*.
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT * FROM %i WHERE id = %d LIMIT 1',
                        $table,
                        $id
                    )
                );
            } else {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Prepared below; custom table read for plugin-owned table, cached via wp_cache_*.
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id, site_id, log_type, action_type, item_name, item_slug, version_before, version_after, status, user_id, performed_by, performed_as, update_context, created_at, event_key, CASE WHEN COALESCE(message, '') <> '' OR COALESCE(trace, '') <> '' THEN 1 ELSE 0 END AS detail_available FROM %i WHERE id = %d LIMIT 1",
                        $table,
                        $id
                    )
                );
            }

            wp_cache_set($cache_key, is_object($row) ? $row : null, self::CACHE_GROUP, MINUTE_IN_SECONDS * 5);

            return is_object($row) ? $row : null;
        });
    }

    /**
     * Check whether a canonical event has already been written.
     *
     * @param string $event_key Canonical event key.
     * @return bool
     */
    public static function has_event(string $event_key): bool {
        return self::get_log_id_by_event_key($event_key) > 0;
    }

    /**
     * Delete a single log by ID.
     *
     * @param int $id Log ID.
     * @return bool
     */
    public static function delete_log(int $id): bool {
        if (!Updatronix_Database::table_exists()) {
            return false;
        }

        return self::with_logs_table(static function () use ($id): bool {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; $wpdb->delete is the correct API, prepared.
            $result = $wpdb->delete($table, ['id' => $id], ['%d']);

            if ($result !== false) {
                self::delete_log_cache(absint($id));
                self::bump_logs_cache_last_changed();
            }

            return $result !== false;
        });
    }

    /**
     * Delete logs older than the given number of days.
     *
     * @param int $days Delete logs older than this many days.
     * @return int Number of rows deleted.
     */
    public static function delete_older_than(int $days): int {
        if (!Updatronix_Database::table_exists() || $days < 1) {
            return 0;
        }

        return self::with_logs_table(static function () use ($days): int {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();
            $date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; table and date via prepare(); no WP API for bulk delete by date.
            $result = $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE created_at < %s', $table, $date));

            if (is_numeric($result) && (int) $result > 0) {
                self::bump_logs_cache_last_changed();
            }

            return is_numeric($result) ? (int) $result : 0;
        });
    }

    /**
     * Drop every log row that originated on a now-deleted subsite.
     *
     * Hooked to `wp_delete_site` on Multisite so the network-global table does not accumulate
     * rows tagged with a `site_id` that no longer maps to a real site.
     *
     * @param \WP_Site $old_site The site being deleted.
     * @return void
     */
    public static function on_delete_site(\WP_Site $old_site): void {
        self::delete_logs_for_site((int) $old_site->blog_id);
    }

    /**
     * Delete all log rows for a given originating site.
     *
     * @param int $site_id Originating site ID.
     * @return int Number of rows deleted.
     */
    public static function delete_logs_for_site(int $site_id): int {
        if ($site_id < 1 || !Updatronix_Database::table_exists()) {
            return 0;
        }

        return self::with_logs_table(static function () use ($site_id): int {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; $wpdb->delete is the correct API, prepared.
            $result = $wpdb->delete($table, ['site_id' => $site_id], ['%d']);

            if (is_numeric($result) && (int) $result > 0) {
                self::bump_logs_cache_last_changed();
            }

            return is_numeric($result) ? (int) $result : 0;
        });
    }

    /**
     * Resolve an existing log row ID for a canonical event key.
     *
     * @param string $event_key Canonical event key.
     * @return int
     */
    private static function get_log_id_by_event_key(string $event_key): int {
        if ($event_key === '' || !Updatronix_Database::table_exists()) {
            return 0;
        }

        return self::with_logs_table(static function () use ($event_key): int {
            global $wpdb;
            $table = Updatronix_Database::get_table_name();

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared below; custom table access is required here.
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT id FROM %i WHERE event_key = %s LIMIT 1',
                    $table,
                    $event_key
                )
            );

            return (int) $existing_id;
        });
    }

    /**
     * Build a cache key for a single log lookup.
     *
     * @param int  $id Log ID.
     * @param bool $include_details Whether the cached row includes detail fields.
     * @return string
     */
    private static function get_log_cache_key(int $id, bool $include_details): string {
        return sprintf(
            'log:%d:%s:%s',
            $id,
            $include_details ? 'full' : 'summary',
            self::get_logs_cache_last_changed()
        );
    }

    /**
     * Delete cache entries for a specific log ID.
     *
     * @param int $id Log ID.
     * @return void
     */
    private static function delete_log_cache(int $id): void {
        wp_cache_delete(self::get_log_cache_key($id, true), self::CACHE_GROUP);
        wp_cache_delete(self::get_log_cache_key($id, false), self::CACHE_GROUP);
    }

    /**
     * Get the current logs cache version.
     *
     * @return string
     */
    private static function get_logs_cache_last_changed(): string {
        $last_changed = wp_cache_get(self::CACHE_LAST_CHANGED_KEY, self::CACHE_GROUP);

        if (!is_string($last_changed) || $last_changed === '') {
            $last_changed = microtime();
            wp_cache_set(self::CACHE_LAST_CHANGED_KEY, $last_changed, self::CACHE_GROUP);
        }

        return $last_changed;
    }

    /**
     * Bump the logs cache version so stale rows are ignored.
     *
     * @return void
     */
    private static function bump_logs_cache_last_changed(): void {
        wp_cache_set(self::CACHE_LAST_CHANGED_KEY, microtime(), self::CACHE_GROUP);
    }
}
