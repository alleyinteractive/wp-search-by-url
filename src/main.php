<?php
/**
 * The main plugin function
 *
 * @package wp-search-by-url
 */

namespace Alley\WP\Search_By_URL;

use Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	$plugin = new Group(
		new Features\Rest_Search_By_URL(),
	);

	/*
	 * Add additional features here.
	 *
	 * Example:
	 *
	 *   $plugin->include( new Features\My_New_Feature() );
	 *
	 * You can generate a new feature using `npx @alleyinteractive/scaffolder@latest feature`.
	 *
	 * @see https://github.com/alleyinteractive/wp-type-extensions
	 */

	$plugin->boot();
}
