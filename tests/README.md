# Tests

**Verification stack:** `composer run verify:php` runs PHP CS Fixer, PHPStan, and the **unit** suite; `composer run verify:all` adds the **integration** suite. **`npm run test:all`** runs every linter + unit + integration tests; **`npm run build:all`** adds POT regeneration + `npm run build` — see **`workflow.md`**.

**First-time setup:** `composer install && npm install && bash bin/setup-dev.sh`. The setup script writes `.config/wp-tests.env` (from your site's DB credentials) and installs `wordpress-tests-lib`, after which integration tests run with no further configuration.

## Unit tests (no WordPress)

```bash
composer test
```

- **Config:** `.config/phpunit.xml.dist`
- **Suite:** `tests/Unit/`
- **Bootstrap:** `tests/bootstrap.php` (default: minimal stubs, no WordPress)

Uses **PHPUnit 9.6** (same major version as the WordPress integration suite — see below).

## Integration tests (full WordPress)

Uses the same **`tests/bootstrap.php`** as unit tests; **`.config/phpunit.integration.xml.dist`** sets `UPDATRONIX_INTEGRATION_TESTS=1` so the bootstrap loads **wordpress-tests-lib** and the plugin instead of stubs.

Requires the official **wordpress-tests-lib**, a MySQL/MariaDB server, and PHP with **mysqli** (Local’s PHP satisfies this).

### One-time: install WordPress core + test library

Run the setup script (idempotent; safe to re-run):

```bash
bash bin/setup-dev.sh
```

This calls `bash .config/local-wp-cli.sh setup`, which:

- Reads your site's DB credentials with `wp config get` and writes **`.config/wp-tests.env`**.
- Installs WordPress core + `wordpress-tests-lib` under **`$HOME/.cache/updatronix-wp-tests/`**, where paths have **no spaces** (the stock `install-wp-tests.sh` breaks when `TMPDIR` contains spaces, e.g. `Local Sites`).
- Uses your existing database (the harness uses table prefix **`wptests_`**, so your site tables stay under the normal **`wp_`** prefix in the same database).

To regenerate the env file (e.g. after the site's DB credentials change):

```bash
bash bin/setup-dev.sh --force
```

To reinstall the test library only (e.g. after a failed run): delete
`$HOME/.cache/updatronix-wp-tests/wordpress-tests-lib` and re-run `bash bin/setup-dev.sh`.

### Run integration tests

`composer run test:integration` routes through `.config/local-wp-cli.sh`, so it uses
Local's PHP + mysqli automatically (the same environment as `composer run lint:pcp`):

```bash
composer run test:integration
```

Optional PHPUnit args (after `--`):

```bash
composer run test:integration -- --filter RestSettingsAuthTest
```

### Run everything

```bash
composer run test:all   # unit, then integration
npm run test:all        # all linters + unit + integration tests
```

### PHPUnit version note

WordPress’s `WP_UnitTestCase` is compatible with **PHPUnit 9.x**. The project pins **`phpunit/phpunit:^9.6`** so unit and integration tests share one runner. Integration tests that expect a specific REST status for anonymous users accept **401 or 403** (core may return either depending on context).

## Manual regression

See `tests/MANUAL_REGRESSION.md`.
