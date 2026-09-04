/**
 * Shared utility functions for auto-update components.
 */

/**
 * Check whether a section is locked by a wp-config constant.
 *
 * @param {Object} constants Map from PHP (constant name → { defined, value, affects, locks }).
 * @param {string} section   'core' | 'plugins' | 'themes' | 'translations'.
 * @return {boolean} True if the section is locked by a constant.
 */
export function isSectionLocked( constants, section ) {
	if ( ! constants ) {
		return false;
	}
	return Object.values( constants ).some(
		( info ) => info.locks && info.value && info.affects.includes( section )
	);
}
