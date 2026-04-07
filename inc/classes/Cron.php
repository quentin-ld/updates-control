<?php

/**
 * Manages scheduled tasks for log cleanup.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cron jobs for log retention cleanup.
 */
final class Updatronix_Cron {
    /**
     * Cron hook name.
     *
     * @var string
     */
    public const HOOK_CLEANUP = 'updatronix_cleanup_logs';

    /**
     * Transient key: throttle self-healing schedule checks (not autoloaded).
     */
    private const SELF_HEAL_TRANSIENT = 'updatronix_cron_self_heal_throttle';

    /**
     * Register cron schedule and hook.
     *
     * @return void
     */
    public static function register(): void {
        add_action(self::HOOK_CLEANUP, [self::class, 'run_cleanup']);
        add_action('shutdown', [self::class, 'maybe_schedule_if_needed'], 999);
    }

    /**
     * Re-schedule cleanup if the event was lost (e.g. manual cron table clear), at most once per day.
     *
     * Activation still calls {@see schedule_if_needed()} directly; this path avoids wp_next_scheduled
     * on every init request.
     *
     * @return void
     */
    public static function maybe_schedule_if_needed(): void {
        if (get_transient(self::SELF_HEAL_TRANSIENT)) {
            return;
        }

        set_transient(self::SELF_HEAL_TRANSIENT, '1', DAY_IN_SECONDS);
        self::schedule_if_needed();
    }

    /**
     * Schedule daily cleanup if not already scheduled.
     *
     * @return void
     */
    public static function schedule_if_needed(): void {
        if (wp_next_scheduled(self::HOOK_CLEANUP)) {
            return;
        }

        wp_schedule_event(time(), 'daily', self::HOOK_CLEANUP);
    }

    /**
     * Run cleanup: delete logs older than retention days.
     *
     * @return void
     */
    public static function run_cleanup(): void {
        $days = updatronix_get_settings()['retention_days'];
        if ($days < 1) {
            return;
        }

        Updatronix_Logger::delete_older_than($days);
    }

    /**
     * Unschedule the cleanup event (e.g. on deactivation).
     *
     * @return void
     */
    public static function unschedule(): void {
        wp_clear_scheduled_hook(self::HOOK_CLEANUP);
    }

    /**
     * Remove plugin transients (e.g. on uninstall).
     *
     * @return void
     */
    public static function delete_plugin_transients(): void {
        delete_transient(self::SELF_HEAL_TRANSIENT);
    }
}
