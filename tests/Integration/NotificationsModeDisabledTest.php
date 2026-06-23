<?php
/**
 * Integration tests for `notifications_mode === 'disabled'` and the recovery-mode safety net.
 *
 * Companion to `.cursor/notes/2026-05-09-test-plan-opus-notifications-schedule-features.md`
 * (scenarios N-01 and N-04). Asserts the contract: disabling all update notification emails
 * suppresses every WordPress automatic-update mail filter return value, but does **not** touch
 * the recovery-mode email — that channel is the safety net the site administrator relies on
 * after a fatal error.
 *
 * @package updatronix
 */

declare(strict_types=1);

/**
 * @coversNothing
 */
final class NotificationsModeDisabledTest extends WP_UnitTestCase {
    /**
     * Restore default settings between tests so disabled-mode state never leaks.
     */
    protected function tearDown(): void {
        $defaults = UPDATRONIX_SETTINGS_DEFAULTS;
        updatronix_save_settings_array($defaults);
        parent::tearDown();
    }

    private function enable_disabled_mode(): void {
        $current = updatronix_get_settings();
        $current['notifications_mode'] = 'disabled';
        updatronix_save_settings_array($current);
    }

    public function test_disabled_mode_returns_false_for_core_send_email(): void {
        $this->enable_disabled_mode();

        $core_update = (object) [
            'current' => '6.7',
            'response' => 'autoupdate',
            'package' => 'https://example.com/wp.zip',
        ];

        $send = apply_filters('auto_core_update_send_email', true, 'success', $core_update, null);
        self::assertFalse($send, 'Disabled mode must suppress the core update success email.');

        $send_fail = apply_filters('auto_core_update_send_email', true, 'fail', $core_update, null);
        self::assertFalse($send_fail, 'Disabled mode must suppress the core update fail email.');

        $send_critical = apply_filters('auto_core_update_send_email', true, 'critical', $core_update, null);
        self::assertFalse($send_critical, 'Disabled mode must suppress the critical core update email.');
    }

    public function test_disabled_mode_returns_false_for_core_manual_notification_email(): void {
        $this->enable_disabled_mode();

        $send = apply_filters('send_core_update_notification_email', true, []);
        self::assertFalse($send, 'Disabled mode must suppress the manual core update available email.');
    }

    public function test_disabled_mode_returns_false_for_plugin_and_theme_send_email(): void {
        $this->enable_disabled_mode();

        // Core fires these with the update-results array as the second argument
        // (see WP_Automatic_Updater::send_plugin_theme_email()), not a $type string.
        $send_plugin = apply_filters('auto_plugin_update_send_email', true, []);
        self::assertFalse($send_plugin, 'Disabled mode must suppress the plugin update batch email.');

        $send_theme = apply_filters('auto_theme_update_send_email', true, []);
        self::assertFalse($send_theme, 'Disabled mode must suppress the theme update batch email.');
    }

    public function test_disabled_mode_returns_false_for_debug_email(): void {
        $this->enable_disabled_mode();

        $send = apply_filters('automatic_updates_send_debug_email', true);
        self::assertFalse($send, 'Disabled mode must suppress the debug summary email.');
    }

    public function test_disabled_mode_preserves_recovery_mode_email_payload(): void {
        $this->enable_disabled_mode();

        $email = [
            'to' => 'site-admin@example.com',
            'subject' => 'Recovery',
            'message' => 'A site error has occurred...',
            'headers' => [],
        ];

        $filtered = apply_filters('recovery_mode_email', $email, 'https://example.com/?action=enter_recovery_mode');

        self::assertSame(
            $email,
            $filtered,
            'Recovery-mode email is the safety net — disabled mode must not redirect, drop, or alter it.'
        );
    }
}
