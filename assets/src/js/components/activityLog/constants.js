/**
 * Activity log DataView constants.
 *
 * Activity layout options follow the view API:
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/
 */
import { __ } from '@wordpress/i18n';

export const LAYOUT_ACTIVITY = 'activity';

export const LOG_TYPE_PREFIX = {
	core: __('Core:', 'updatronix'),
	plugin: __('Plugin:', 'updatronix'),
	theme: __('Theme:', 'updatronix'),
	translation: __('Translation:', 'updatronix'),
};

export const ACTION_LABELS = {
	update: __('Update', 'updatronix'),
	downgrade: __('Rollback', 'updatronix'),
	install: __('Install', 'updatronix'),
	same_version: __('Reinstall', 'updatronix'),
	failed: __('Failed', 'updatronix'),
	uninstall: __('Uninstall', 'updatronix'),
	delete: __('Delete', 'updatronix'),
};
