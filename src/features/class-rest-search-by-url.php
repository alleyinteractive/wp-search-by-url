<?php
/**
 * Rest_Search_By_URL class file
 *
 * @package wp-search-by-url
 */

declare(strict_types=1);

namespace Alley\WP\Search_By_URL\Features;

use Alley\WP\Types\Feature;
use WP_REST_Request;

/**
 * Allow the REST post search endpoint (`/wp/v2/search`) to accept a post's URL
 * in place of a search term.
 */
class Rest_Search_By_URL implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'rest_post_search_query', [ $this, 'add_url_search_support' ], 10, 2 );
	}

	/**
	 * Resolve a URL in the `search` parameter to a post.
	 *
	 * The resolved post ID is passed through `post__in`, and `post_status` is
	 * forced to `publish` so a URL for a draft, private, or scheduled post never
	 * leaks that post through search, even if another filter has widened
	 * `post_status`.
	 *
	 * @param mixed[]         $query_args Key-value array of query var to query value.
	 * @param WP_REST_Request $request    The request used.
	 * @return mixed[] Filtered query arguments.
	 */
	public function add_url_search_support( $query_args, $request ) {
		$search = $request->get_param( 'search' );

		if ( empty( $search ) || ! is_string( $search ) ) {
			return $query_args;
		}

		// Confirm this looks like an absolute URL without resolving its host.
		$parsed_url = wp_parse_url( $search );

		if (
			! is_array( $parsed_url )
			|| empty( $parsed_url['host'] )
			|| empty( $parsed_url['scheme'] )
			|| ! in_array( strtolower( $parsed_url['scheme'] ), [ 'http', 'https' ], true )
		) {
			return $query_args;
		}

		// `url_to_postid()` is uncached and matches against every rewrite rule, so it
		// is costly on sites with many rules. VIP's cached wrapper is preferred when
		// available. This only runs when the search term is an absolute URL.
		$resolved = function_exists( 'wpcom_vip_url_to_postid' )
			? wpcom_vip_url_to_postid( $search )
			: url_to_postid( $search ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid

		$post_id = is_numeric( $resolved ) ? (int) $resolved : 0;

		/**
		 * Filters the post ID resolved from a URL used as a post search term.
		 *
		 * @param int    $post_id The resolved post ID, or 0 if none was found.
		 * @param string $search  The searched URL.
		 */
		$post_id = (int) apply_filters( 'wp_search_by_url_url_to_post_id', $post_id, $search );

		if ( empty( $post_id ) ) {
			return $query_args;
		}

		unset( $query_args['s'] );

		// WP_Query ignores `post__not_in` when `post__in` is set, so honor `exclude` here.
		if ( ! empty( $query_args['post__not_in'] ) && is_array( $query_args['post__not_in'] ) ) {
			$excluded_ids = array_map( 'intval', array_filter( $query_args['post__not_in'], 'is_numeric' ) );

			if ( in_array( $post_id, $excluded_ids, true ) ) {
				$query_args['post__in']    = [ 0 ];
				$query_args['post_status'] = 'publish';

				return $query_args;
			}
		}

		// Respect an existing `include` constraint: only keep the resolved post if it was already allowed.
		if ( ! empty( $query_args['post__in'] ) && is_array( $query_args['post__in'] ) ) {
			$allowed_ids = [];
			foreach ( $query_args['post__in'] as $allowed_id ) {
				if ( is_numeric( $allowed_id ) ) {
					$allowed_ids[] = (int) $allowed_id;
				}
			}

			// An empty `post__in` is ignored by WP_Query, so use 0 to force no results.
			$query_args['post__in'] = in_array( $post_id, $allowed_ids, true ) ? [ $post_id ] : [ 0 ];
		} else {
			$query_args['post__in'] = [ $post_id ];
		}

		// Deliberately published-only, even for users who could normally see other
		// statuses, so a pasted URL can never expose an unpublished post.
		$query_args['post_status'] = 'publish';

		return $query_args;
	}
}
