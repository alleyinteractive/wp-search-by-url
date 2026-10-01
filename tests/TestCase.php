<?php
/**
 * Search By URL Tests: Base Test Class
 *
 * @package wp-search-by-url
 */

declare(strict_types=1);

namespace Alley\WP\Search_By_URL\Tests;

use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testkit\Test_Case as TestkitTest_Case;

/**
 * Search By URL Base Test Case
 */
abstract class TestCase extends TestkitTest_Case {
	use Prevent_Remote_Requests;
}
