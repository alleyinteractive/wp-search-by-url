# Search By URL

Contributors: jessicamgoddard

Tags: alleyinteractive, wp-search-by-url

Stable tag: 0.0.0

Requires at least: 6.5

Tested up to: 7.1

Requires PHP: 8.2

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/wp-search-by-url/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/wp-search-by-url/actions/workflows/all-pr-tests.yml)

Support searching for content by URL in the WordPress REST search endpoint.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-search-by-url
```

## Usage

Activate the plugin. Anywhere the WordPress REST search endpoint
(`/wp/v2/search`) is queried for posts, such as the block editor's link and
post pickers, pasting a post's full URL into the search box returns that post.

Only published posts are returned. A URL that doesn't resolve to a published
post falls back to a normal text search.

### Filters

`wp_search_by_url_url_to_post_id` filters the post ID resolved from a URL, which
is useful for URLs WordPress can't resolve on its own (custom rewrites, etc.).

```php
add_filter(
	'wp_search_by_url_url_to_post_id',
	function ( int $post_id, string $url ): int {
		return $post_id ?: my_custom_url_to_post_id( $url );
	},
	10,
	2
);
```

## Development

```sh
composer install
composer phpunit
composer phpcs
composer phpstan
```
