# Tests

**Verification stack:** `composer run verify:php` runs PHP CS Fixer, PHPStan, and the **unit** suite. **`npm run build:all`** starts with `verify:php`, then Plugin Check, JS/CSS, POT, and `npm run build` — see **`workflow.md`**. **Integration** tests are optional and not part of `build:all`.

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

1. Copy and adjust env (DB matches Local / `wp-config.php`):

   ```bash
   cp .config/wp-tests-env.example .config/wp-tests.env
   ```

   - **`WP_TEST_DB_*`** — same database as your Local site if you want (e.g. `local` / `root` / `root` / `localhost`).
   - **Install paths** (`WP_CORE_DIR`, `WP_TESTS_DIR`, `TMPDIR`) live under **`$HOME/.cache/updatronix-wp-tests/`** so paths have **no spaces** (the stock `install-wp-tests.sh` breaks when `TMPDIR` contains spaces, e.g. `Local Sites`).

2. **Use your existing database** (no second DB) — last argument `true` skips `CREATE DATABASE`:

   ```bash
   source .config/wp-tests.env
   bash bin/install-wp-tests.sh "$WP_TEST_DB_NAME" "$WP_TEST_DB_USER" "$WP_TEST_DB_PASSWORD" "$WP_TEST_DB_HOST" trunk true
   ```

   The PHPUnit harness uses table prefix **`wptests_`** in `wp-tests-config.php`. Your site tables stay under the normal **`wp_`** prefix in the same database.

3. **Reinstall test library only** (e.g. after a failed run): delete  
   `$HOME/.cache/updatronix-wp-tests/wordpress-tests-lib` and run the command again.

### Run integration tests

**Recommended (Local PHP + mysqli):** same environment as `composer run lint:pcp`:

```bash
bash .config/local-wp-cli.sh integration-test
```

Optional PHPUnit args:

```bash
bash .config/local-wp-cli.sh integration-test --filter RestSettingsAuthTest
```

**From the host shell** (requires PHP CLI **with mysqli** — many default CLIs do not):

```bash
source .config/wp-tests.env
composer run test:integration
```

### Run everything

```bash
composer run test:all
```

(`test:all` runs unit tests, then integration — integration needs the stack installed and mysqli.)

### PHPUnit version note

WordPress’s `WP_UnitTestCase` is compatible with **PHPUnit 9.x**. The project pins **`phpunit/phpunit:^9.6`** so unit and integration tests share one runner. Integration tests that expect a specific REST status for anonymous users accept **401 or 403** (core may return either depending on context).

## Manual regression

See `tests/MANUAL_REGRESSION.md`.
