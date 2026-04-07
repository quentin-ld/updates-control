/**
 * Core auto-update mode section (minor / all / disabled).
 */

import { Icon, RadioControl } from '@wordpress/components';
import { wordpress as coreIcon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { ConstantNotices } from './ConstantNotices';

/**
 * Check whether a section is locked by a wp-config constant.
 *
 * @param {Object} constants Map from PHP.
 * @param {string} section   'core' | 'plugins' | 'themes' | 'translations'.
 * @return {boolean} True if the section is locked by a constant.
 */
function isSectionLocked(constants, section) {
	if (!constants) {
		return false;
	}
	return Object.values(constants).some(
		(info) => info.locks && info.value && info.affects.includes(section)
	);
}

/**
 * Render the core auto-update mode selector.
 *
 * @param {Object}   props             Component props.
 * @param {Object}   props.core        Core settings: { mode, overridden_by_constant }.
 * @param {Object}   props.constants   Constant info from API.
 * @param {Function} props.setCoreMode Callback to change the core update mode.
 * @param {boolean}  props.busy        Whether a request is in progress.
 * @return {JSX.Element}                The core auto-update section.
 */
export function CoreSection({ core, constants, setCoreMode, busy }) {
	const locked =
		core.overridden_by_constant || isSectionLocked(constants, 'core');

	const options = [
		{
			label: __(
				'Minor releases only (e.g. 6.4.1 to 6.4.2)',
				'updatronix'
			),
			value: 'minor',
		},
		{
			label: __(
				'All releases, including major (e.g. 6.4 to 6.5)',
				'updatronix'
			),
			value: 'all',
		},
		{
			label: __('Disabled (no core auto-updates)', 'updatronix'),
			value: 'disabled',
		},
	];

	return (
		<div className="updatronix-autoupdates-section">
			<h3 className="updatronix-autoupdates-section-title">
				<Icon icon={coreIcon} size={24} />
				{__('Core updates', 'updatronix')}
			</h3>
			<ConstantNotices
				constants={constants}
				sections={['core']}
				lockingOnly
			/>
			<RadioControl
				label={__('Core auto-update mode', 'updatronix')}
				selected={core.mode}
				options={options}
				onChange={(value) => setCoreMode(value)}
				disabled={locked || busy}
				help={
					locked
						? __(
								'A constant in your wp-config.php file controls this setting. To change it, edit that file directly.',
								'updatronix'
							)
						: ''
				}
			/>
		</div>
	);
}
