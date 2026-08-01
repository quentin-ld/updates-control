=== Updatronix ===
Contributors: quentinldd, alkesh7
Donate link: https://buymeacoffee.com/quentinld
Tags: updates, auto-update, maintenance, security, audit-log
Requires at least: 6.2
Tested up to: 7.0.2
Stable tag: 1.1.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enhanced Update Manager for WordPress. Monitor every change, control all updates, and fine-tune your website maintenance flow.

== Description ==

Core, plugins, and themes need regular updates, but WordPress forgets they ever happened. Updatronix remembers. It keeps a running record of every update on your website, and hands you more control over the update process: what updates, when, and how.

Updatronix is a precision plugin built on the native WordPress update engine instead of swapping it out. Your settings are written to WordPress's own options, your auto-update choices keep working even if you remove the plugin one day. I built it for the people who look after WordPress sites for a living, it fits the way you already work.

= Built for every user in their diversity of needs =

* **Solo site owners:** Keep your site up to date and sleep at night. You'll always know what changed.
* **Freelancers:** When a client asks what you've been up to, the answer is right there.
* **Developers:** Real control over auto-updates and scheduling, plus the full detail behind every event.
* **Agencies:** The same update policy you trust, running on every client site, each with its own log.

== Features ==

Four tabs, one per concern.

= Update logs =

Every core, plugin, theme, and translation update is logged with full details. If something breaks after updates, you have a trusty starting point instead of a guess.

* The before and after version, what set it off, and how it ended.
* Filter by category, date, action, or user when the list gets long.
* Open any entry to read the details, including the exact error WordPress threw.

Need to hand it off? Export the log just as you've filtered it. Handy for briefing your team when something breaks, sending a maintenance report for a client, or sharing context with someone you've called in to help.

= Auto-updates =

WordPress 5.5 made auto-updates available across the dashboard. The controls then got scattered across half a dozen screens. This tab pulls them onto one page: how core updates itself, which plugins and themes update on their own, whether translations come along.

* Set core to every release, security and minor only, or fully manual.
* A switch for each plugin and theme, and one for translations.
* If a `wp-config.php` constant is overriding something, you'll see which one.

= Schedule =

WordPress checks for updates twice a day. Usually that's fine. When it isn't, this feature hands you the timing, and lets a new release age a little before it reaches you.

* Pick how often WordPress checks (hourly, twice daily, daily, or weekly) and the time of day.
* Hold automatic installs after a release shows up.
* Skip the bad ones. If a plugin ships a broken update and then a quick fix, a short hold means you get the fix and miss the mess.
* A notice appears on the Updates, Plugins, and Themes screens whenever a hold is active.

= Settings =

How long the log sticks around, who gets the update emails WordPress sends, and the master switch for those emails when you'd rather not see them at all.

* Set up log retention policy.
* Send WordPress update emails to the desired recipient.
* Per-event filters: core, plugin and theme, debug summary, technical alerts.
* One switch silences every WordPress update notification email, recovery mode excepted, to prevent self-lockout.

== Updatronix 3000 ==

Somewhere in a neon-lit server room, the next version is booting.

**Updatronix 3000** is the Pro edition coming soon. Extending Updatronix, with additional features for developers, power users, and agencies.

* **Developer tools.** Hooks, functions, and a REST API to plug Updatronix into your own pipeline. Push events to your own dashboards, trigger backups, automate maintenance reports, imagine whatever you want, and improve your workflow with a solid foundation. 
* **Update Shield.** Checks PHP compatibility, flags abandoned plugins, version control protection.
* **Update Flow.** You are the expert, you know your stack: set the exact order your plugins update in.
* **White Label.** Your name on it, not mine. Clients see your agency, and the engine stays out of sight.

**Coming soon.** One payment, one license, one site, with updates for life. I'm not a fan of subscriptions, so there won't be one. You buy it, you own it.

== Privacy ==

Nothing leaves your site. No analytics, no telemetry, no third-party calls. Updatronix reads from WordPress, writes to your site's storage, and that's the whole network footprint. Delete the plugin and the log table goes with it, along with the settings.

== Accessibility ==

Updatronix aims to be fully accessible to all of its users. If you run into a problem open a support thread on the plugin page and it'll get fixed.

== Multisite Support ==

Updatronix supports multisite networks. Network-activate it once, and a Super Admin manages settings, update history, schedule, and email controls from the Network Admin dashboard: everything shared across every site, with one unified update log.

== Screenshots ==

1. Update logs: The chronological view, with status, trigger, and version change for every entry.
2. Update logs: Filtering controls in action, with active filter tags and a reset option.
3. Update logs: The export modal — filter summary, merge option, column selection, and generated report.
4. Update logs: The export modal — plain-text report output with copy options.
5. Update logs: A single entry expanded to show its full detail.
6. Update logs: The delete confirmation dialog for a single log entry.
7. Auto-updates: Core update mode, translation toggle, and per-theme controls with status, version, and description columns.
8. Auto-updates: Per-plugin controls with status, version, and description columns.
9. Schedule: Recurrence, preferred time of day, next run preview, and the delay duration setting.
10. Settings: Logging toggle, retention period, and email notification routing.
11. Settings: Disable-all-emails option.

== Installation ==

1. Search for "Updatronix" in **Plugins → Add New**, or upload the plugin files to `/wp-content/plugins/updatronix/`.
2. Activate the plugin from the Plugins screen.
3. Open **Tools → Updatronix** (or **Dashboard → Update logs**) to see the history and adjust the settings.

Activation creates the log table and schedules a daily cleanup. Deactivation cancels the cleanup but leaves your data alone. Deletion removes everything: the log table and the settings. On multisite, network-activate the plugin from the Network Admin Plugins screen; its data lives at the network level, and deletion clears it once for the whole network (including any leftover per-site data from earlier versions).

== Frequently Asked Questions ==

= Where do I see the history of updates on my site? =

Open **Tools → Updatronix**. The first tab is the log: date, item, version change, outcome. Click any row to drill into a single entry. Logging is on by default after activation; if you've turned it off in the past, only events recorded while it was on will show up.

= Can I export my update log? =

Yes. Filter the log how you like, then click the **Export logs** button. You get a clean report you can drop into an email to your team, a maintenance summary for a client, or a note to whoever you've called in to help.

= How do I send WordPress update emails to a different address? =

In **Settings**, turn on **Manage update notifications** and put the address in the recipient field. A comma-separated list works if you want to send the emails to several inboxes. Pick which event types should trigger an email (core, plugin and theme, debug summary, technical alert) then save. WordPress keeps sending the same emails it always sends; they just go to the address you picked instead of the site admin.

= Can I turn off WordPress update notification emails completely? =

Yes. In **Settings**, turn on **Disable all update notification emails**. That suppresses the core, plugin, theme, and debug summary emails WordPress would normally send. Recovery mode emails, the ones that arrive after a fatal error so you can log back in, are deliberately exempt. Disabling those would lock you out of your own site, which is the opposite of helpful.

= Can I delay automatic updates? =

Yes. In **Schedule**, enable **Delay Updates** and set the number of days WordPress should wait after a release appears. The countdown is per release, not per check, so a 7-day hold means an offer is at least 7 days old before it installs. While anything is on hold, the Updates, Plugins, and Themes screens display a notice explaining what's happening.

= Does Updatronix work with my plugins, themes, and host? =

It hooks into the same update pipeline WordPress already runs, so anything that updates through **Dashboard → Updates** or the automatic update system gets logged, whether the package comes from WordPress.org, a private source, or your host's mirror. If your host or `wp-config.php` locks a setting from outside the dashboard, Updatronix surfaces a notice explaining what's locked, so you don't waste time wondering why a toggle isn't responding.

= Can Updatronix undo a failed update? =

No. Rolling updates back is a different problem with different tradeoffs and Updatronix deliberately stays out of it. What it does instead: when an update fails, the plugin captures the WordPress error and the version snapshot before WordPress moves on. That's the data you need to recover by hand, or hand to your host's support so they can.

= Where does my data go? =

Nowhere. Logs and settings live on your site. The plugin makes zero outbound network calls of its own: every API it touches is one WordPress was already going to call without it.

= How long are log entries kept? =

Up to you. In **Settings**, set the retention window between 1 and 365 days. A daily cleanup task drops anything older. The default is 90 days, which works for most sites; raise it if you need a longer audit trail, or lower it if your hosting is tight on storage.

= Does Updatronix work on multisite? =

Yes. Network-activate Updatronix for the whole network and manage everything as a Super Admin from the Network Admin dashboard. Settings, schedule, email controls, and update history are shared across every site in one unified log. Single-site installs work the same as before.

= How to support Updatronix developpment ? =

You can buy the pro version if it fit your needs.

Also, [I am accepting sponsorships via the GitHub Sponsors program](https://github.com/sponsors/quentin-ld/dashboard). If you work at an agency that develops with WordPress, ask your company to provide sponsorship in order to invest in its supply chain. The tools that I maintain probably save your company time and money, and GitHub sponsorship can now be done at the organisation level.

In addition, if you like the plugin then I'd love for you to [leave a review](https://wordpress.org/plugins/updatronix/#reviews). Tell all your friends about it too!

= I have suggestion =

I welcome your ideas! If you have a suggestion for the roadmap, please visit the official support forum. If you are a developer, you can also contribute directly to the project on GitHub.

== Changelog ==

= 1.1.1 =
* Confirmed compatibility with WordPress 7.0.2 (final release; previously tested against 7.0-RC2).
* Change: Adopted the WordPress Coding Standards (WPCS) PHPCS ruleset alongside the existing PHP CS Fixer setup; codebase reformatted to match (no functional changes).
* Fix: Aligned the License URI in the plugin header with readme.txt.
* i18n: Audited every translation string; no issues found.
* Security: Reviewed REST routes, database queries, exports, and email handling; no issues found.

= 1.1 =
* New: Schedule tab, set how often WordPress checks for updates (hourly, twice daily, daily, or weekly), pick a preferred time of day, and hold automatic installs for a chosen number of days. Active holds show a notice on the Updates, Plugins, and Themes screens, and WordPress schedule messaging stays in sync with your settings.
* New: Export update logs, generate merged or flat reports from your current filters, copy formatted or plain-text output to the clipboard, and access export from the activity log toolbar.
* New: One-click switch in Settings to turn off all WordPress update notification emails (recovery mode emails stay on).
* New: Multisite support, network-activate Updatronix and manage settings, update history, schedule, and email controls as a Super Admin from the Network Admin dashboard. Everything is shared across every site on the network, with one unified log. Single-site installs are unchanged.
* Improvement: Cleaner copy, smoother flow, and accessibility improvements across every tab, including keyboard focus in activity log modals.
* i18n: Save buttons use "Save Changes" to match native WordPress settings screens.

= 1.0.6.1 =
* Fix: Readme.txt text is now naturally wrapped.

= 1.0.6 =
* Change: Align plugin lifecycle with WordPress uninstall expectations.
* Fix: Nested document landmarks for accessibility.
* Fix: Automatic update failures now record the real WP_Error (e.g. filesystem unavailable) in log details, not only generic upgrader messages.
* Change: Update component dependencies and remove hardcoded values in style to use design-token.css instead.
* Change: Update readme.txt content, tone, and voice.
* i18n: Align plugin interface with WordPress interface tone and voice, improving accessibility.

= 1.0.5 =
* Fix: Wire js script translations for bundled files.
* Add: Load theme/plugin descriptions translated into the current admin language for the auto-update panel.
* Add: Translation for "Icon", "Success", "Error", and "Warning" labels.
* Change: Cache the merged Jed/JSON translation inline payload.

= 1.0.4 =
* Fix: Wire js script translations.
* Fix: Wrong logging behavior for minor core auto-update. Was logged as "Reinstall" instead of "Update".
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

= 1.1 =
Adds the Schedule tab, update log export, a switch to silence WordPress update emails, and multisite support, plus accessibility improvements across every tab.

= 1.0.6 =
Improves uninstall cleanup, accessibility, and error logging for failed auto-updates.

= 1.0.5 =
Fixes script translations and adds translation caching for the admin interface.

= 1.0.4 =
Fixes core auto-update logging and aligns the interface with WordPress 7.0.
