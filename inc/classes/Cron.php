<?php
/**
 * Scheduled tasks for log retention and optional unified background update cycles.
 *
 * When the Schedule tab uses a non-empty recurrence, this plugin reschedules Core's
 * `wp_version_check` WP-Cron event (same hook WordPress admin UI reads) and primes
 * plugin/theme checks before the core version check runs — see prime_unified_discovery_before_core().
 *
 * @package updatronix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin-owned WP-Cron jobs.
 */
final class Updatronix_Cron {
	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	public const HOOK_CLEANUP = 'updatronix_cleanup_logs';

	/**
	 * Core hook scheduled for cron-time checks; used by wp-admin messaging ({@see wp_get_auto_update_message()}).
	 *
	 * @var string
	 */
	public const HOOK_WP_CRON_CORE_VERSION_CHECK = 'wp_version_check';

	/**
	 * Transient key: throttle self-healing schedule checks (not autoloaded).
	 *
	 * @var string
	 */
	private const SELF_HEAL_TRANSIENT = 'updatronix_cron_self_heal_throttle';

	/**
	 * Transient key: throttle rediscovery cron self-heal (not autoloaded).
	 *
	 * @var string
	 */
	private const UPDATE_CHECK_HEAL_TRANSIENT = 'updatronix_update_check_heal_throttle';

	/**
	 * Register cron schedule and hook.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::HOOK_CLEANUP, array( self::class, 'run_cleanup' ) );
		add_action( self::HOOK_WP_CRON_CORE_VERSION_CHECK, array( self::class, 'prime_unified_discovery_before_core' ), 9 );
		add_action( 'init', array( self::class, 'sync_core_update_crons_with_schedule' ), 11 );
		add_action( 'shutdown', array( self::class, 'maybe_schedule_if_needed' ), 999 );
		add_action( 'shutdown', array( self::class, 'maybe_heal_update_check_schedule' ), 998 );
		add_action( 'updatronix_after_save_network_schedule', array( self::class, 'apply_update_check_schedule_from_settings' ) );
	}

	/**
	 * Whether self-heal paths may run on this request.
	 *
	 * Front-end requests don't trigger cron events, so the transient throttle is the only
	 * thing standing between an attacker who can clear plugin transients and the cron table.
	 * Restricting heal work to cron, admin, and CLI contexts removes that lever.
	 *
	 * @return bool
	 */
	private static function self_heal_allowed_in_context(): bool {
		if ( wp_doing_cron() ) {
			if ( is_multisite() ) {
				return (int) get_current_blog_id() === (int) get_main_site_id();
			}

			return true;
		}
		if ( is_multisite() ) {
			if ( is_network_admin() ) {
				return true;
			}
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return true;
			}

			return false;
		}
		if ( is_admin() ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		return false;
	}

	/**
	 * Re-schedule cleanup if the event was lost (e.g. manual cron table clear), at most once per day.
	 *
	 * Activation still calls {@see Updatronix_Cron::schedule_if_needed()} directly; this path avoids wp_next_scheduled
	 * on every init request.
	 *
	 * @return void
	 */
	public static function maybe_schedule_if_needed(): void {
		if ( ! self::self_heal_allowed_in_context() ) {
			return;
		}

		if ( updatronix_get_plugin_transient( self::SELF_HEAL_TRANSIENT ) ) {
			return;
		}

		updatronix_set_plugin_transient( self::SELF_HEAL_TRANSIENT, '1', DAY_IN_SECONDS );
		self::schedule_if_needed();
	}

	/**
	 * Schedule daily cleanup if not already scheduled.
	 *
	 * @return void
	 */
	public static function schedule_if_needed(): void {
		if ( wp_next_scheduled( self::HOOK_CLEANUP ) ) {
			return;
		}

		wp_schedule_event( time(), 'daily', self::HOOK_CLEANUP );
	}

	/**
	 * Run cleanup: delete logs older than retention days.
	 *
	 * @return void
	 */
	public static function run_cleanup(): void {
		$days = updatronix_get_settings()['retention_days'];
		if ( 1 > $days ) {
			return;
		}

		Updatronix_Logger::delete_older_than( $days );
	}

	/**
	 * Whether the Schedule tab assigns Updatronix as the cron-time update pipeline (non-empty recurrence).
	 *
	 * @return bool
	 */
	public static function is_unified_schedule_active(): bool {
		$settings = updatronix_get_settings();
		$recurrence = $settings['schedule']['update_check']['recurrence'];

		return '' !== $recurrence && in_array( $recurrence, updatronix_allowed_update_check_recurrence_slugs(), true );
	}

	/**
	 * Align Core's recurring plugin/theme update-check hooks with schedule settings.
	 *
	 * When unified, `wp_version_check` remains scheduled (this plugin sets its recurrence) so
	 * {@see wp_get_auto_update_message()} matches the chosen window. Only the separate
	 * `wp_update_plugins` and `wp_update_themes` recurring events are cleared to avoid a second
	 * cron-time pipeline. When using WordPress default scheduling, restore missing Core events via
	 * {@see wp_schedule_update_checks()}.
	 *
	 * Deactivation calls {@see unschedule()} which clears the version-check event and restores Core.
	 *
	 * @return void
	 */
	public static function sync_core_update_crons_with_schedule(): void {
		if ( wp_installing() ) {
			return;
		}

		if ( self::is_unified_schedule_active() ) {
			self::suppress_redundant_core_update_crons();

			return;
		}

		self::restore_core_update_check_crons_if_needed();
	}

	/**
	 * Drop Core's plugin/theme recurring checks when the unified `wp_version_check` run already refreshes them.
	 *
	 * @return void
	 */
	private static function suppress_redundant_core_update_crons(): void {
		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_themes' );
	}

	/**
	 * Restores WordPress core update-check cron hooks when this plugin is not driving the unified schedule.
	 *
	 * @return void
	 */
	private static function restore_core_update_check_crons_if_needed(): void {
		if ( ! function_exists( 'wp_schedule_update_checks' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}
		wp_schedule_update_checks();
	}

	/**
	 * Runs before Core's callback on `wp_version_check`: refresh plugin/theme transients so the
	 * priority-10 listener ({@see wp_version_check()}) sees primed data and triggers
	 * `wp_maybe_auto_update` against the freshest available offers.
	 *
	 * Earlier versions of this method removed Core's priority-10 callback, called
	 * {@see wp_version_check()} inline, then re-added Core's callback. `WP_Hook::do_action()`
	 * resorts active iterations on add, so re-adding mid-iteration caused Core's callback to
	 * run a second time in the same tick. Letting Core's callback run untouched at priority 10
	 * delivers the same behaviour without the double-call.
	 *
	 * @return void
	 */
	public static function prime_unified_discovery_before_core(): void {
		if ( ! self::is_unified_schedule_active() ) {
			return;
		}

		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		wp_update_plugins();
		wp_update_themes();
	}

	/**
	 * Reschedule unified background-update runs from stored settings (after save).
	 *
	 * @return void
	 */
	public static function apply_update_check_schedule_from_settings(): void {
		wp_clear_scheduled_hook( self::HOOK_WP_CRON_CORE_VERSION_CHECK );
		$settings = updatronix_get_settings();
		$schedule = $settings['schedule'];
		$recurrence = $schedule['update_check']['recurrence'];
		if ( '' === $recurrence || ! in_array( $recurrence, updatronix_allowed_update_check_recurrence_slugs(), true ) ) {
			self::sync_core_update_crons_with_schedule();

			return;
		}

		$time = $schedule['update_check']['time'];
		$timestamp = updatronix_next_update_check_timestamp( $recurrence, $time );
		wp_schedule_event( (int) $timestamp, $recurrence, self::HOOK_WP_CRON_CORE_VERSION_CHECK );
		self::sync_core_update_crons_with_schedule();
	}

	/**
	 * If settings require a recurring unified run but WP-Cron lost the hook, reschedule (throttled).
	 *
	 * @return void
	 */
	public static function maybe_heal_update_check_schedule(): void {
		if ( ! self::self_heal_allowed_in_context() ) {
			return;
		}

		if ( updatronix_get_plugin_transient( self::UPDATE_CHECK_HEAL_TRANSIENT ) ) {
			return;
		}

		updatronix_set_plugin_transient( self::UPDATE_CHECK_HEAL_TRANSIENT, '1', HOUR_IN_SECONDS );

		$settings = updatronix_get_settings();
		$schedule = $settings['schedule'];
		$recurrence = $schedule['update_check']['recurrence'];
		if ( '' === $recurrence || ! in_array( $recurrence, updatronix_allowed_update_check_recurrence_slugs(), true ) ) {
			self::sync_core_update_crons_with_schedule();

			return;
		}

		if ( wp_next_scheduled( self::HOOK_WP_CRON_CORE_VERSION_CHECK ) ) {
			return;
		}

		$time = $schedule['update_check']['time'];
		$timestamp = updatronix_next_update_check_timestamp( $recurrence, $time );
		wp_schedule_event( (int) $timestamp, $recurrence, self::HOOK_WP_CRON_CORE_VERSION_CHECK );
		self::sync_core_update_crons_with_schedule();
	}

	/**
	 * Unschedule plugin events (e.g. on deactivation) and restore Core update checks if they were suppressed.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK_CLEANUP );
		wp_clear_scheduled_hook( self::HOOK_WP_CRON_CORE_VERSION_CHECK );
		self::restore_core_update_check_crons_if_needed();
	}

	/**
	 * Remove plugin transients (e.g. on uninstall).
	 *
	 * @return void
	 */
	public static function delete_plugin_transients(): void {
		updatronix_delete_plugin_transient( self::SELF_HEAL_TRANSIENT );
		updatronix_delete_plugin_transient( self::UPDATE_CHECK_HEAL_TRANSIENT );
	}

	/**
	 * Remove duplicate cron events from subsites after network-only upgrade.
	 *
	 * @return void
	 */
	public static function clear_subsite_cron_artifacts(): void {
		if ( ! is_multisite() ) {
			return;
		}

		$main_id = (int) get_main_site_id();
		$batch = 200;
		$offset = 0;
		do {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => $batch,
					'offset' => $offset,
				)
			);
			foreach ( $site_ids as $blog_id ) {
				$blog_id = (int) $blog_id;
				if ( $blog_id === $main_id ) {
					continue;
				}
				switch_to_blog( $blog_id );
				try {
					wp_clear_scheduled_hook( self::HOOK_CLEANUP );
					wp_clear_scheduled_hook( self::HOOK_WP_CRON_CORE_VERSION_CHECK );
				} finally {
					restore_current_blog();
				}
			}
			$offset += $batch;
			$fetched_count = count( $site_ids );
		} while ( $fetched_count === $batch );
	}

	/**
	 * Read-only Schedule tab cron diagnostics (localized + REST payload).
	 *
	 * @return array{
	 *     cron_schedule_labels: list<array{slug: string, label: string}>,
	 *     update_check_next_scheduled: int|false,
	 *     wp_cron_disabled: bool,
	 *     timezone_string: string,
	 *     schedule_driver: 'wordpress'|'updatronix',
	 *     unified_schedule_active: bool,
	 * }
	 */
	public static function get_schedule_rest_meta(): array {
		$next = wp_next_scheduled( self::HOOK_WP_CRON_CORE_VERSION_CHECK );
		$unified = self::is_unified_schedule_active();

		return array(
			'cron_schedule_labels' => updatronix_get_allowed_cron_schedule_labels(),
			'update_check_next_scheduled' => ( false !== $next ) ? $next : false,
			'wp_cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'timezone_string' => (string) wp_timezone_string(),
			'schedule_driver' => $unified ? 'updatronix' : 'wordpress',
			'unified_schedule_active' => $unified,
		);
	}
}
