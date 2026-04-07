# Manual regression gate (Updatronix)

Use this checklist before tagging a release. Adjust environment placeholders as needed.

## Environment

- WordPress version: ___
- PHP version: ___
- Plugin version: ___
- Single site / multisite: ___

## Roles and admin UI

1. **Administrator** — Log in; confirm **Tools → Updatronix** and **Dashboard → Updates log** appear; open the page and confirm the React shell loads without console errors.
2. **Editor (or Subscriber)** — Confirm Updatronix **does not** appear under Tools or Dashboard; direct URL to `tools.php?page=updatronix` returns **access denied** or equivalent.

## REST API (cookie auth + nonce)

Use the browser devtools Network tab on the settings page, or `curl` with a valid logged-in cookie and `X-WP-Nonce` from `wpApiSettings.nonce` (or the REST nonce endpoint).

1. **GET** `/wp-json/updatronix/v1/settings` as Administrator — **200**, JSON body with `options`.
2. Same request **unauthenticated** (no cookies) — **401** or **403** (WordPress may return either).
3. **POST/PATCH** `/wp-json/updatronix/v1/settings` with valid body as Administrator and **X-WP-Nonce** — **200**; settings persist after reload.
4. Mutating request **without** `X-WP-Nonce` (cookie session) — expect REST **cookie check** failure (typically **403**).
5. Low-privilege user with cookies — **403** on read/write routes.

## Update logging

1. **Happy path** — Update a test plugin (staging); confirm a new activity log row with sensible From/To versions and action type.
2. **Failure path** — If reproducible (e.g. invalid package), confirm log or error handling matches expectations and no PHP fatals.

## Cron

1. Confirm **Updatronix** log retention cron is scheduled (Tools → Site Health → scheduled events, or WP-CLI `wp cron event list | grep updatronix`).

## Quality bar (automated)

From the plugin root, the full stack is documented in **`workflow.md`**. Typical order:

- `composer run verify:php` (PHP CS Fixer + PHPStan + unit tests — same as CI)
- `composer run lint:pcp` (Local / WP-CLI)
- `npm run lint` / `npm run lint:css` / `npm run format` when JS/CSS changed
- Optional: `bash .config/local-wp-cli.sh integration-test` (see `tests/README.md`)

Or run everything that does not require integration tests: **`npm run build:all`** (includes `verify:php` and the rest of the pipeline).
