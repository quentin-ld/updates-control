<?php

/**
 * Redirect native WordPress update notification emails to a custom recipient
 *
 * When email notifications are enabled, the native admin update emails
 * (core, plugin, theme) are sent to the configured recipient instead of admin_email.
 * No custom emails are sent; only WordPress core email behaviour is redirected.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirect native WordPress update emails to the plugin's recipient.
 *
 * When the detailed (debug) email is sent by WordPress, the standard summary emails
 * (core, plugin, theme) are suppressed so the user receives only one email per run.
 * The standard email is used as fallback when the detailed email is not sent.
 */
final class Updatronix_Notifications {
    /**
     * Set to true when we allow the debug (detailed) email this run; used to suppress standard emails.
     *
     * @var bool
     */
    private static bool $detailed_email_sent_this_run = false;

    /**
     * Register filters for native WordPress update notification emails.
     *
     * @return void
     */
    public static function register(): void {
        add_filter('send_core_update_notification_email', [self::class, 'filter_send_core_update_notification_email'], 10, 2);
        add_filter('auto_core_update_send_email', [self::class, 'filter_core_send_email'], 10, 4);
        add_filter('auto_core_update_email', [self::class, 'filter_core_email_to'], 10, 4);
        add_filter('auto_plugin_update_send_email', [self::class, 'filter_plugin_send_email'], 10, 2);
        add_filter('auto_theme_update_send_email', [self::class, 'filter_theme_send_email'], 10, 2);
        add_filter('auto_plugin_theme_update_email', [self::class, 'filter_plugin_theme_email_to'], 10, 4);
        add_filter('automatic_updates_send_debug_email', [self::class, 'filter_should_send_debug_email'], 10, 1);
        add_filter('automatic_updates_debug_email', [self::class, 'filter_debug_email_to'], 10, 3);
        add_filter('recovery_mode_email', [self::class, 'filter_recovery_mode_email_to'], 10, 2);
    }

    /**
     * Check whether notifications are enabled and a valid recipient is set.
     *
     * @return bool
     */
    private static function should_redirect(): bool {
        $s = updatronix_get_settings();
        if (!$s['notify_enabled']) {
            return false;
        }
        if ($s['notify_emails'] === '') {
            return false;
        }
        $recipients = array_filter(array_map('sanitize_email', explode(',', $s['notify_emails'])));

        return $recipients !== [];
    }

    /**
     * Get recipient email(s). First address only if single, else array for wp_mail().
     *
     * @return string|array<string>
     */
    private static function get_recipient(): string|array {
        $emails = updatronix_get_settings()['notify_emails'];
        $recipients = array_values(array_filter(array_map('sanitize_email', explode(',', $emails))));
        if ($recipients === []) {
            return '';
        }

        return count($recipients) === 1 ? $recipients[0] : $recipients;
    }

    /**
     * Get notify_on option.
     *
     * @return array<string>
     */
    private static function get_notify_on(): array {
        return updatronix_get_settings()['notify_on'];
    }

    /**
     * Check whether notify_on includes a key.
     *
     * @param string $key Setting key.
     * @return bool
     */
    private static function has_notify(string $key): bool {
        return in_array($key, self::get_notify_on(), true);
    }

    /**
     * Only send core update email when "core" is in notify_on.
     * Suppress standard core email when the detailed (debug) email was already sent this run.
     *
     * @param bool   $send        Whether to send. Default true.
     * @param string $type        success, fail, manual, critical.
     * @param object $core_update The update offer.
     * @param mixed  $result      The result.
     * @return bool
     */
    public static function filter_core_send_email(bool $send, string $type, mixed $core_update, mixed $result): bool {
        if (!updatronix_get_settings()['notify_enabled']) {
            return $send;
        }

        if (!$send) {
            return false;
        }

        if (self::$detailed_email_sent_this_run) {
            return false;
        }

        return self::has_notify('core');
    }

    /**
     * Decide whether to send native "WordPress update available" manual notifications.
     *
     * @param bool  $notify Whether WordPress would send this notification.
     * @param mixed $item   Core update item.
     * @return bool
     */
    public static function filter_send_core_update_notification_email(bool $notify, mixed $item): bool {
        if (!updatronix_get_settings()['notify_enabled']) {
            return $notify;
        }

        if (!$notify) {
            return false;
        }

        return self::has_notify('core');
    }

    /**
     * Redirect core update email to plugin recipient when notifications enabled.
     *
     * @param array<string, string> $email       to, subject, body, headers.
     * @param string                $type        success, fail, manual, critical.
     * @param object                $core_update The update offer.
     * @param mixed                 $result      The result.
     * @return array<string, string>
     */
    public static function filter_core_email_to(array $email, string $type, mixed $core_update, mixed $result): array {
        if (self::should_redirect()) {
            $email['to'] = self::get_recipient();
        }

        return $email;
    }

    /**
     * Only send plugin update email when "plugin_theme" is in notify_on.
     * WordPress sends one combined plugin/theme email; both filters use the same setting.
     * Suppress when the detailed (debug) email was already sent this run.
     *
     * @param bool               $enabled        Default true.
     * @param array<int, object> $update_results Plugin update results.
     * @return bool
     */
    public static function filter_plugin_send_email(bool $enabled, array $update_results): bool {
        if (!updatronix_get_settings()['notify_enabled']) {
            return $enabled;
        }

        if (!$enabled) {
            return false;
        }

        if (self::$detailed_email_sent_this_run) {
            return false;
        }

        return self::has_notify('plugin_theme');
    }

    /**
     * Only send theme update email when "plugin_theme" is in notify_on.
     * WordPress sends one combined plugin/theme email; both filters use the same setting.
     * Suppress when the detailed (debug) email was already sent this run.
     *
     * @param bool               $enabled        Default true.
     * @param array<int, object> $update_results Theme update results.
     * @return bool
     */
    public static function filter_theme_send_email(bool $enabled, array $update_results): bool {
        if (!updatronix_get_settings()['notify_enabled']) {
            return $enabled;
        }

        if (!$enabled) {
            return false;
        }

        if (self::$detailed_email_sent_this_run) {
            return false;
        }

        return self::has_notify('plugin_theme');
    }

    /**
     * Redirect plugin/theme update email to plugin recipient when notifications enabled.
     *
     * @param array<string, string>              $email             to, subject, body, headers.
     * @param string                             $type              success, fail, mixed.
     * @param array<string, array<int, object>> $successful_updates Successful updates.
     * @param array<string, array<int, object>> $failed_updates     Failed updates.
     * @return array<string, string>
     */
    public static function filter_plugin_theme_email_to(array $email, string $type, array $successful_updates, array $failed_updates): array {
        if (self::should_redirect()) {
            $email['to'] = self::get_recipient();
        }

        return $email;
    }

    /**
     * Enable debug email when "debug" is in notify_on, or when WordPress would send it (development version).
     * When we allow the debug email, mark so standard (core/plugin/theme) emails are suppressed this run.
     *
     * @param bool $development_version WordPress default for debug mail sending (e.g. version string contains '-').
     * @return bool
     */
    public static function filter_should_send_debug_email(bool $development_version): bool {
        if (!self::should_redirect()) {
            return $development_version;
        }

        if (self::has_notify('debug')) {
            self::$detailed_email_sent_this_run = true;

            return true;
        }

        if ($development_version) {
            self::$detailed_email_sent_this_run = true;

            return true;
        }

        return false;
    }

    /**
     * Redirect debug email to plugin recipient when notifications enabled.
     * The debug email includes core, plugin, theme, and translation results.
     *
     * @param array<string, string>              $email   to, subject, body, headers.
     * @param int                                $failures Number of failures.
     * @param array<string, array<int, object>> $results  All update results.
     * @return array<string, string>
     */
    public static function filter_debug_email_to(array $email, int $failures, array $results): array {
        if (self::should_redirect()) {
            $email['to'] = self::get_recipient();
        }

        return $email;
    }

    /**
     * Redirect WordPress recovery-mode technical email to plugin recipient.
     *
     * @param array<string, mixed> $email Recovery-mode email payload.
     * @param string               $url   Recovery URL.
     * @return array<string, mixed>
     */
    public static function filter_recovery_mode_email_to(array $email, string $url): array {
        if (!self::should_redirect()) {
            return $email;
        }
        if (!self::has_notify('technical')) {
            return $email;
        }
        $email['to'] = self::get_recipient();

        return $email;
    }
}
