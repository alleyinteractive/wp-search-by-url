<?php
/**
 * Search By URL Tests: Search By URL Test
 *
 * @package wp-search-by-url
 */

namespace Alley\WP\Search_By_URL\Tests\Feature;

use Alley\WP\Search_By_URL\Tests\TestCase;

/**
 * Test that the REST post search endpoint can
 * resolve a pasted post URL to that post.
 */
class RestSearchByUrlTest extends TestCase {
	/**
	 * Test that searching by a post's permalink returns that post.
	 */
	public function test_it_can_search_by_post_url(): void {
		$post = static::factory()->post->create_and_get( [
			'post_title' => 'A Very Specific Title',
		] );

		static::factory()->post->create_ordered_set( 3 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => get_permalink( $post ) ] ) )
			->assertOk()
			->assertJsonCount( 1 )
			->assertJsonPath( '0.id', $post->ID );
	}

	/**
	 * Test that an uppercase URL scheme is still recognized as a URL.
	 */
	public function test_it_accepts_uppercase_url_scheme(): void {
		$post = static::factory()->post->create_and_get();

		$url = preg_replace( '/^https?/i', 'HTTPS', get_permalink( $post ) );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => $url ] ) )
			->assertOk()
			->assertJsonCount( 1 )
			->assertJsonPath( '0.id', $post->ID );
	}

	/**
	 * Test that the `include` parameter still constrains URL search results.
	 */
	public function test_url_search_respects_include_constraint(): void {
		$post  = static::factory()->post->create_and_get();
		$other = static::factory()->post->create_and_get();

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [
			'search'  => get_permalink( $post ),
			'include' => [ $other->ID ],
		] ) )
			->assertOk()
			->assertJsonCount( 0 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [
			'search'  => get_permalink( $post ),
			'include' => [ $post->ID, $other->ID ],
		] ) )
			->assertOk()
			->assertJsonCount( 1 )
			->assertJsonPath( '0.id', $post->ID );
	}

	/**
	 * Test that a URL that doesn't resolve to a post falls back to a normal (empty) search.
	 */
	public function test_unresolvable_url_returns_no_results(): void {
		static::factory()->post->create_ordered_set( 3 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => 'https://example.com/not-a-real-post/' ] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that a URL resolving to a draft post does not leak that post through search.
	 */
	public function test_draft_post_url_returns_no_results(): void {
		$post = static::factory()->post->create_and_get( [
			'post_title'  => 'A Very Specific Title',
			'post_status' => 'draft',
		] );

		static::factory()->post->create_ordered_set( 3 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => get_permalink( $post ) ] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that a scheduled post's permalink does not leak that post through
	 * search, even for a logged-in user. URL-based search always forces
	 * `post_status` back to `publish`.
	 *
	 * @see \Alley\WP\Search_By_URL\Features\Rest_Search_By_URL::add_url_search_support()
	 */
	public function test_scheduled_post_url_returns_no_results(): void {
		$this->acting_as( 'administrator' );

		$post = static::factory()->post->create_and_get( [
			'post_title'  => 'A Very Specific Title',
			'post_status' => 'future',
			'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
		] );

		static::factory()->post->create_ordered_set( 3 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => get_permalink( $post ) ] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that the `wp_search_by_url_url_to_post_id` filter can supply the post ID.
	 */
	public function test_url_to_post_id_filter_can_resolve_url(): void {
		$post = static::factory()->post->create_and_get();

		add_filter(
			'wp_search_by_url_url_to_post_id',
			fn ( $post_id, $url ) => 'https://example.com/custom-route/' === $url ? $post->ID : $post_id,
			10,
			2
		);

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => 'https://example.com/custom-route/' ] ) )
			->assertOk()
			->assertJsonCount( 1 )
			->assertJsonPath( '0.id', $post->ID );
	}

	/**
	 * Test that the `exclude` parameter still removes a post found by URL.
	 */
	public function test_url_search_respects_exclude_constraint(): void {
		$post = static::factory()->post->create_and_get();

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [
			'search'  => get_permalink( $post ),
			'exclude' => [ $post->ID ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that non-http(s) schemes are not treated as URLs.
	 */
	public function test_non_http_scheme_is_not_resolved(): void {
		$post = static::factory()->post->create_and_get();

		$url = preg_replace( '/^https?/i', 'ftp', get_permalink( $post ) );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => $url ] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that a URL for a post of another type is not returned when searching a different subtype.
	 */
	public function test_url_for_other_post_type_is_not_returned(): void {
		$page = static::factory()->post->create_and_get( [ 'post_type' => 'page' ] );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [
			'search'  => get_permalink( $page ),
			'subtype' => 'post',
		] ) )
			->assertOk()
			->assertJsonCount( 0 );
	}

	/**
	 * Test that a plain text search still works as before.
	 */
	public function test_text_search_still_works(): void {
		$post = static::factory()->post->create_and_get( [
			'post_title' => 'A Very Specific Title',
		] );

		static::factory()->post->create_ordered_set( 3 );

		$this->get_json( '/wp-json/wp/v2/search?' . http_build_query( [ 'search' => 'Very Specific' ] ) )
			->assertOk()
			->assertJsonCount( 1 )
			->assertJsonPath( '0.id', $post->ID );
	}
}
