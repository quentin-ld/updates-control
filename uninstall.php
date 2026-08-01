<?php
/**
 * Runs when the plugin is deleted from WordPress, not on deactivation.
 *
 * Loads constants and delegates cleanup to {@see Updatronix_Uninstall::run()}.
 *
 * @package updatronix
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$updatronix_main = __DIR__ . '/updatronix.php';
if ( ! is_readable( $updatronix_main ) ) {
	exit;
}

$updatronix_plugin_meta = get_file_data( $updatronix_main, array( 'Version' => 'Version' ), 'plugin' );
$updatronix_version = isset( $updatronix_plugin_meta['Version'] ) ? trim( (string) $updatronix_plugin_meta['Version'] ) : '';
if ( '' === $updatronix_version ) {
	$updatronix_version = '0';
}

define( 'UPDATRONIX_VERSION', $updatronix_version );

if ( ! defined( 'updatronix_PLUGIN_FILE' ) ) {
	define( 'updatronix_PLUGIN_FILE', $updatronix_main ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- intentional lowercase legacy alias, kept for backward compatibility.
}

if ( ! defined( 'updatronix_PLUGIN_DIR' ) ) {
	define( 'updatronix_PLUGIN_DIR', plugin_dir_path( updatronix_PLUGIN_FILE ) ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- intentional lowercase legacy alias, kept for backward compatibility.
}

require_once __DIR__ . '/inc/core/constants.php';
require_once __DIR__ . '/inc/core/context.php';
require_once __DIR__ . '/inc/core/storage.php';
require_once __DIR__ . '/inc/classes/Uninstall.php';
Updatronix_Uninstall::run();
