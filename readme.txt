=== Updatronix ===
Contributors: quentinldd
Donate link: https://buymeacoffee.com/quentinld
Tags: updates, auto-update, maintenance, security, audit-log
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.0.6
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Log every WordPress update with version details, manage auto-updates through native settings, and route notification emails to chosen recipients.

== Description ==

Updatronix logs every core, plugin, theme, and translation update
that WordPress processes. It records version snapshots, trigger types,
and technical details, and stores everything in your site's database.

The plugin also provides auto-update controls that save directly to
native WordPress options, email routing for built-in notification
emails, and detection of `wp-config.php` constants that override
update behavior. It does not replace the WordPress update engine,
perform rollbacks, or connect to external services.

= Update logging =

* Record the name, slug, type, and status of every update.
* Store version-before and version-after snapshots for each event.
* See what triggered each update — a manual action, the automatic
  update system, or a file upload.
* Review the technical process messages that WordPress generates
  during the upgrade.

= Auto-update controls =

* Set core updates to apply all versions, minor releases only, or
  manual-only mode.
* Toggle auto-updates for individual plugins and themes using the
  same options WordPress reads natively.
* View `wp-config.php` constants (such as `WP_AUTO_UPDATE_CORE`)
  that override your settings, so you can spot configuration
  conflicts.

= Email routing =

Updatronix filters the notification emails WordPress sends — it does
not create a separate notification system.

* Redirect core update alerts, auto-update notices, and recovery
  mode emails to the recipients you choose.
* Select which event types trigger a notification.

= Failure tracking =

* View log entries even when an update fails due to a server
  timeout or fatal error.
* Review error details that the shutdown handler captures for
  manual recovery.

== Privacy Statement ==

Updatronix does not collect, store, or transmit personal data to
third parties. There are no external service dependencies or
telemetry. All update logs stay in your site's own WordPress
database.

== Accessibility Statement ==

Updatronix aims to be fully accessible to all of its users.

== Screenshots ==

1. Update log list showing change history, trigger types, and statuses.
2. Log filtering controls on the update log panel.
3. Detailed view of a single log entry.
4. Log deletion actions on the update log panel.
5. Auto-update controls for core, plugins, themes, and translations.
6. Settings for log retention, cleanup scheduling, and email routing.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/updatronix/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the Plugins screen.
3. Go to **Tools → Updatronix** (or **Dashboard → Updates log**) to view logs and configure settings.

**On activation**, Updatronix creates a dedicated database table for
logs and schedules a daily cleanup task through WP-Cron.

**On deactivation**, Updatronix removes the cleanup task. Your log
data and settings remain in the database.

**On deletion**, Updatronix removes the log table, plugin settings,
and all related options. On multisite installations, this cleanup
runs for each site.

== Frequently Asked Questions ==

= How do I enable logging? =

Logging starts automatically when you activate the plugin. To change
this, go to **Tools → Updatronix** (or **Dashboard → Updates log**),
open the **Settings** tab, and toggle "Enable update logging." When
enabled, the plugin records every core, plugin, theme, and
translation update with version-before and version-after values,
trigger type, and technical process messages.

= How do I change where WordPress sends update emails? =

Go to **Tools → Updatronix** → **Settings**, enable "Email
notifications," enter one or more recipient addresses separated by
commas, and select which event types trigger an email: core updates,
plugin and theme auto-updates, debug emails, and recovery mode.
Updatronix filters the same emails WordPress sends — it does not
create a separate notification system.

= Does Updatronix work with third-party plugins and themes? =

Yes. Updatronix hooks into the native WordPress update process. The
plugin logs any item that updates through **Dashboard → Updates**,
WP-CLI, or the automatic update flow, whether it comes from the
WordPress.org directory or a third-party source.

= Can Updatronix roll back a failed update? =

No. Updatronix records updates but does not perform rollbacks or
repairs. If an update fails, the shutdown handler attempts to capture
the error. This gives you diagnostic data — such as version snapshots
and error traces — to help you recover manually.

= Does this plugin work with older WordPress versions? =

Updatronix requires WordPress 6.2 or newer and PHP 8.1 or newer.
The plugin does not support or test against older versions.

= Does this plugin send data to external servers? =

No. All logs and settings stay in your site's own database.
Updatronix does not connect to external services.

= Is Updatronix compatible with managed hosting? =

Yes. Updatronix reads the constants and restrictions that managed
hosts set. If the hosting provider locks a setting at the server
level, the plugin detects it and displays the active value.

= How does log cleanup work? =

You set a retention period from one to 365 days in the Settings tab.
A daily WP-Cron task removes entries older than that limit.

= Does this plugin work on multisite? =

Yes. Updatronix is multisite-aware and tracks logs on a per-site
basis. A future release will add network-wide management features.

== Changelog ==

= 1.0.6 =
* Change: Align plugin lifecycle with WordPress uninstall expectations.
* Fix: Nested document landmarks for accessibility.
* Fix: Automatic update failures now record the real WP_Error (e.g. filesystem unavailable) in log details, not only generic upgrader messages.
* Change: Update component dependencies and remove hardcoded values in style to use design-token.css instead.
* Change: Update readme.txt content, ton and voice.
* i18n: Align plugin interface to WordPress interface ton and voice improving accessibility.

= 1.0.5 =
* Fix: Wire js script translations for bundled files.
* Add: Load theme/plugin descriptions translated into the current admin language for the auto-update panel.
* Add: Translation for "Icon", "Sucess", "Error", "Warning" labels.
* Change: Cache the merged Jed/JSON translation inline payload.

= 1.0.4 =
* Fix: Wire js script translations.
* Fix: Wrong logging behaviour for minor core auto-update. Was logged as "Reinstall" instead of "Update".
* Change: Code-split the admin JavaScript bundle with lazy-loaded tab modules to keep all emitted chunks below Webpack's recommended size limit and improve wp-admin load performance.
* Change: Align user interface standards to WP 7.0.
* Change: Tested up to WP 7.0-RC2.
* Change: Update screenshots for WordPress.org.
* Change: Update of the logo and banners.

= 1.0.3 =
* Add: Updatronix release on WordPress.org.

= 1.0.2 =
* Fix: Close `ob_start()` buffers safely.

= 1.0.1 =
* Fix: Better handling of updates logging.
* Change: Improve responsive, and plugin global UX.
* Change: Improve the readme.txt.

= 1.0 =
* Add: Initial release of Updatronix.

== Upgrade Notice ==

= 1.0.6 =
Improves uninstall cleanup, accessibility, and error logging for failed auto-updates.

= 1.0.5 =
Fixes script translations and adds translation caching for the admin interface.

= 1.0.4 =
Fixes core auto-update logging and aligns the interface with WordPress 7.0.
