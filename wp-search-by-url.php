<?php
/**
 * Plugin Name: Search By URL
 * Plugin URI: https://github.com/alleyinteractive/wp-search-by-url
 * Description: Support searching for content by URL in the WordPress REST search endpoint.
 * Version: 0.0.0
 * Author: Jessica Goddard
 * Author URI: https://github.com/alleyinteractive/wp-search-by-url
 * Requires at least: 7.1
 * Requires PHP: 8.2
 * Tested up to: 7.1
 *
 * Text Domain: wp-search-by-url
 * Domain Path: /languages/
 *
 * @package wp-search-by-url
 */

namespace Alley\WP\Search_By_URL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_SEARCH_BY_URL_DIR', __DIR__ );

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-search-by-url cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-search-by-url' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/main.php';

main();
