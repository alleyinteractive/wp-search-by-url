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
