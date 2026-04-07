import { useState, useCallback, useMemo } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Read and save plugin settings via localized data and REST.
 *
 * Dispatch a notice (success, warning, or error) after each save attempt.
 *
 * @return {Object} Settings state and save action.
 */
export function usePluginSettings() {
	const { createSuccessNotice, createErrorNotice, createWarningNotice } =
		useDispatch(noticesStore);

	const initial = useMemo(() => {
		const opts =
			typeof window !== 'undefined' && window.updatronixSettings?.options;
		const allowedNotifyOn = ['core', 'plugin_theme', 'debug', 'technical'];
		let notifyOn = [];
		if (opts && Array.isArray(opts.notify_on)) {
			notifyOn = opts.notify_on.includes('all')
				? [...allowedNotifyOn]
				: opts.notify_on.filter((x) => allowedNotifyOn.includes(x));
			// Legacy: if server sent plugin/theme, treat as plugin_theme
			if (
				notifyOn.length === 0 &&
				opts.notify_on.some((x) => x === 'plugin' || x === 'theme')
			) {
				notifyOn = ['plugin_theme'];
			}
		}
		return opts
			? {
					logging_enabled: !!opts.logging_enabled,
					retention_days: Number(opts.retention_days) || 90,
					notify_enabled: !!opts.notify_enabled,
					notify_emails: String(opts.notify_emails || ''),
					notifyOn,
				}
			: {
					logging_enabled: true,
					retention_days: 90,
					notify_enabled: false,
					notify_emails: '',
					notifyOn,
				};
	}, []);

	const [settings, setSettings] = useState(initial);
	const [saving, setSaving] = useState(false);

	const saveSettings = useCallback(async () => {
		setSaving(true);
		try {
			const payload = {
				logging_enabled: settings.logging_enabled,
				retention_days: settings.retention_days,
				notify_enabled: settings.notify_enabled,
				notify_emails: settings.notify_emails,
				notify_on: settings.notifyOn,
			};
			const response = await apiFetch({
				path: 'updatronix/v1/settings',
				method: 'PUT',
				data: payload,
			});
			if (response?.options) {
				const { notify_on: notifyOnFromApi, ...rest } =
					response.options;
				setSettings({ ...rest, notifyOn: notifyOnFromApi });
				createSuccessNotice(__('Settings saved.', 'updatronix'));
			} else {
				createWarningNotice(
					__(
						'Your settings were saved, but the server did not return the updated values. Refresh the page to confirm.',
						'updatronix'
					)
				);
			}
		} catch (e) {
			const message =
				e?.message ||
				__(
					'Your settings could not be saved. Check your connection and try again.',
					'updatronix'
				);
			createErrorNotice(message);
		} finally {
			setSaving(false);
		}
	}, [settings, createSuccessNotice, createErrorNotice, createWarningNotice]);

	return {
		settings,
		setSettings,
		saveSettings,
		saving,
	};
}
