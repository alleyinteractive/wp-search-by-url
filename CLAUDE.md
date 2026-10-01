# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A small WordPress plugin (built from Alley Interactive's plugin skeleton) that lets the REST search endpoint (`/wp/v2/search`) accept a post's full URL in the `search` parameter and return that post. There is no front-end build: no `package.json`, webpack, blocks, or entries. It is PHP-only and tooling is Composer-based.

## Commands

```sh
composer test         # @lint (phpcs + phpstan + rector dry-run) then phpunit
composer phpunit      # PHPUnit only
composer phpcs        # alley-coding-standards (phpcbf to auto-fix: composer phpcbf)
composer phpstan      # level max over src/ and wp-search-by-url.php
composer rector       # dry-run; `composer rector:fix` applies
composer lint:fix     # rector:fix + phpcbf
composer serve        # start wp-env (config in .wp-env.json)
```

Run a single test: `vendor/bin/phpunit --filter RestSearchByUrlTest` (or a specific method name).

## Architecture

- `wp-search-by-url.php` loads `vendor/wordpress-autoload.php` (maps `Alley\WP\Search_By_URL\` → `src/`; tolerates a parent project's Composer autoloader, otherwise shows an admin notice), requires `src/main.php`, and calls `main()`.
- `src/main.php` builds an `Alley\WP\Features\Group` (from `alleyinteractive/wp-type-extensions`) of `Feature` implementations and calls `boot()`. **Add behavior by creating a `Feature` class in `src/features/` and registering it there**; don't add hooks in procedural files. `.scaffolder/plugin-feature/` generates a feature plus its test (`npx @alleyinteractive/scaffolder@latest feature`).
- The only feature, `Rest_Search_By_URL` (`src/features/class-rest-search-by-url.php`), hooks `rest_post_search_query`. Behavior to preserve:
  - Only acts on an absolute `http`/`https` URL in `search`; anything else falls through to normal text search.
  - Resolves via `wpcom_vip_url_to_postid()` when available, else `url_to_postid()`, then filters the result through `wp_search_by_url_url_to_post_id`.
  - On a match it unsets `s`, sets `post__in` to the post, and **forces `post_status` to `publish`** so drafts/private/scheduled posts never leak. An existing `post__in` (the REST `include` param) is respected: if the resolved post isn't already allowed, `post__in` becomes `[0]` to return nothing.
  - On no match it returns the query args untouched.

## Conventions

- PHP 8.2+, strict types, class files named `class-{slug}.php` (WordPress style; the autoloader handles it). Namespace `Alley\WP\Search_By_URL\...`, features under `...\Features\`.
- Must pass phpcs (WordPress-VIP flavored; use `phpcs:ignore` sparingly, as for `url_to_postid`) and PHPStan level max. `phpstan.neon` has `blocks/` and `entries/` commented out; uncomment them only if those directories are added.
- Tests extend `tests/TestCase.php` (Mantle Testkit `Test_Case` with `Prevent_Remote_Requests`) and live in `tests/Feature/`. PSR-4: `Alley\WP\Search_By_URL\Tests\` → `tests/`. `tests/bootstrap.php` rsyncs the plugin into a WordPress install before booting.
- Prefer [Mantle](https://mantle.alley.com/) APIs (testing helpers, factories, `mantle-framework/support`) over custom code; check `https://mantle.alley.com/llms.txt` to find the right one.
- Keep the plugin slug `wp-search-by-url`, the author, and the namespace as they are.
- Releases: `composer release` (create-release); GitHub Actions builds the tagged branches.
