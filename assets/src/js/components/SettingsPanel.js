/**
 * Settings panel — logging and notification configuration.
 * Uses Gutenberg components: NumberControl, TextControl, ToggleControl, CheckboxControl.
 */

import { memo } from '@wordpress/element';
import {
	Button,
	Icon,
	ToggleControl,
	TextControl,
	CheckboxControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
} from '@wordpress/components';
import { bellUnread, seen } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

const NOTIFY_TYPES = [
	{
		key: 'core',
		label: __('Core updates', 'updatronix'),
		help: __(
			'WordPress core update emails: success, failure, manual, and critical.',
			'updatronix'
		),
	},
	{
		key: 'plugin_theme',
		label: __('Plugin and theme updates', 'updatronix'),
		help: __(
			'WordPress sends one combined email for plugin and theme auto-updates (success, fail, or mixed).',
			'updatronix'
		),
	},
	{
		key: 'debug',
		label: __('Debug email (detailed report)', 'updatronix'),
		help: __(
			'Development-style report with core, plugin, theme, and translation results. WordPress normally sends this only on development versions; enable here to receive it on any site.',
			'updatronix'
		),
	},
	{
		key: 'technical',
		label: __('Recovery mode (technical email)', 'updatronix'),
		help: __(
			'WordPress recovery mode technical email when the site enters recovery mode.',
			'updatronix'
		),
	},
];

/**
 * @param {Object}   props
 * @param {Object}   props.settings     Current settings.
 * @param {Function} props.setSettings  Setter for settings.
 * @param {Function} props.saveSettings Async save to REST API.
 * @param {boolean}  props.saving       Whether save is in progress.
 * @return {JSX.Element}
 */
export const SettingsPanel = memo(function SettingsPanel({
	settings,
	setSettings,
	saveSettings,
	saving,
}) {
	const handleNotifyChange = (key, checked) => {
		setSettings((prev) => ({
			...prev,
			notifyOn: checked
				? [...prev.notifyOn.filter((x) => x !== key), key]
				: prev.notifyOn.filter((x) => x !== key),
		}));
	};

	return (
		<div className="updatronix-settings-form">
			<h2 className="updatronix-panel-title">
				{__('Settings', 'updatronix')}
			</h2>
			<Text variant="muted">
				{__(
					'Set up logging, choose how long to keep records, and manage email notifications.',
					'updatronix'
				)}
			</Text>
			<div className="updatronix-settings-section">
				<h3 className="updatronix-settings-section-title">
					<Icon icon={seen} size={24} />
					{__('Update logging', 'updatronix')}
				</h3>
				<ToggleControl
					__nextHasNoMarginBottom
					label={__('Record all updates', 'updatronix')}
					help={__(
						'Keep a record of all core, plugin, theme, and translation updates.',
						'updatronix'
					)}
					checked={settings.logging_enabled}
					onChange={(value) =>
						setSettings((prev) => ({
							...prev,
							logging_enabled: value,
						}))
					}
				/>
				<fieldset
					disabled={!settings.logging_enabled}
					className="updatronix-settings-fieldset"
				>
					<NumberControl
						__next40pxDefaultSize
						label={__('Keep logs for (days)', 'updatronix')}
						help={__(
							'Logs older than this number of days are automatically removed once a day.',
							'updatronix'
						)}
						min={1}
						max={365}
						value={settings.retention_days}
						onChange={(value) =>
							setSettings((prev) => ({
								...prev,
								retention_days: Math.max(
									1,
									Math.min(365, Number(value) ?? 90)
								),
							}))
						}
					/>
				</fieldset>
			</div>
			<div className="updatronix-settings-section">
				<h3 className="updatronix-settings-section-title">
					<Icon icon={bellUnread} size={24} />
					{__('Update notifications', 'updatronix')}
				</h3>
				<ToggleControl
					__nextHasNoMarginBottom
					label={__('Manage update notifications', 'updatronix')}
					help={__(
						'When on, WordPress built-in update emails are sent to the address you choose below. Use the checkboxes to pick which types of updates you want to hear about.',
						'updatronix'
					)}
					checked={settings.notify_enabled}
					onChange={(value) =>
						setSettings((prev) => ({
							...prev,
							notify_enabled: value,
						}))
					}
				/>
				<fieldset
					disabled={!settings.notify_enabled}
					className="updatronix-settings-fieldset"
				>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Send notifications to', 'updatronix')}
						help={__(
							'Update emails will go to this address instead of the default admin email.',
							'updatronix'
						)}
						type="email"
						value={settings.notify_emails}
						onChange={(value) =>
							setSettings((prev) => ({
								...prev,
								notify_emails: value || '',
							}))
						}
						placeholder={__('admin@example.com', 'updatronix')}
					/>
					<div className="updatronix-settings-checkboxes">
						<h4 className="updatronix-settings-label">
							{__('Notification types', 'updatronix')}
						</h4>
						<p className="updatronix-settings-help">
							{__(
								'Choose which update types trigger emails. You receive one email per run: the detailed report when available, otherwise the standard WordPress summary. Options match WordPress’s own update email behaviour.',
								'updatronix'
							)}
						</p>
						{NOTIFY_TYPES.map(({ key, label, help: itemHelp }) => (
							<CheckboxControl
								key={key}
								__nextHasNoMarginBottom
								label={label}
								help={itemHelp}
								checked={settings.notifyOn.includes(key)}
								onChange={(checked) =>
									handleNotifyChange(key, checked)
								}
							/>
						))}
					</div>
				</fieldset>
			</div>
			<div className="updatronix-actions">
				<Button
					variant="primary"
					onClick={saveSettings}
					isBusy={saving}
					disabled={saving}
					__next40pxDefaultSize
				>
					{saving
						? __('Saving…', 'updatronix')
						: __('Save settings', 'updatronix')}
				</Button>
			</div>
		</div>
	);
});
