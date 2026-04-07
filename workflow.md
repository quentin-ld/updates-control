# Updatronix — development workflow

Updatronix is a WordPress plugin that logs core, plugin, and theme updates with error handling, security, and optional email notifications. End-user documentation lives in **`readme.txt`**. This file is for contributors: tooling, order of checks, and how Local WP ties in.

## Getting started

### Prerequisites

- PHP **8.1+** (see `readme.txt`)
- WordPress **6.2+** (tested range in `readme.txt`)
- Composer
- Node.js **LTS** and npm (for `@wordpress/scripts`, ESLint, Stylelint, Prettier)

### Install dependencies

```bash
composer install
npm install
```

## Code quality (recommended order)

Run these before a commit or release, in order:

| Step | Command | What it does |
|------|---------|----------------|
| 1 | `composer run verify:php` | **PHP CS Fixer + PHPStan + PHPUnit unit tests** (`updatronix.php`, `inc/`, `tests/Unit/`) |
| 2 | `composer run lint:pcp` | Plugin Check via WP-CLI (requires **Local** — see below) |
| 3 | `npm run lint` | ESLint (WordPress preset + Prettier via `@wordpress/eslint-plugin`); `npm run lint:fix` to auto-fix |
| 4 | `npm run lint:css` | Stylelint on `assets/src/**/*.scss` (`npm run lint:css:fix` to auto-fix) |
| 5 | `npm run format` | Prettier check on `assets/src/**/*.{js,jsx}` (`npm run format:fix` to write) |
| 6 | `composer run make:pot` | Regenerate `languages/updatronix.pot` (requires **Local** — see below) |
| 7 | *(optional)* `bash .config/local-wp-cli.sh integration-test` | **PHPUnit integration tests** (full WordPress + DB; requires Local PHP/mysqli + one-time `bin/install-wp-tests.sh` — see `tests/README.md`) |

WordPress.org suggests using coding standards / static analysis together with [Plugin Check](https://make.wordpress.org/plugins/developers/). This repo uses **PHP CS Fixer** and **PHPStan** for PHP, then Plugin Check for WordPress.org-oriented rules. **PHPUnit** covers pure helpers in `tests/Unit/`; **integration** tests live in `tests/Integration/` and are run locally when the WordPress test library is installed.

Front-end JS follows **`@wordpress/eslint-plugin`**; SCSS follows **`@wordpress/stylelint-config/scss-stylistic`**; Prettier uses **`@wordpress/prettier-config`** (see `package.json`). SCSS is linted with Stylelint, not Prettier, so formatter commands target JS/JSX only.

### Composer scripts (reference)

| Script | Definition |
|--------|------------|
| `lint:php` | PHP CS Fixer then PHPStan (see `composer.json`) |
| `test` | PHPUnit **unit** suite only (`.config/phpunit.xml.dist` → `tests/Unit/`) |
| `verify:php` | `lint:php` then `test` — use this before commits and in CI |
| `test:integration` | PHPUnit **integration** suite (`.config/phpunit.integration.xml.dist` → `tests/Integration/`); needs `WP_TESTS_DIR` + MySQL + mysqli |
| `test:all` | `test` then `test:integration` |
| `lint:pcp` | `bash .config/local-wp-cli.sh pcp` |
| `make:pot` | `bash .config/local-wp-cli.sh pot` |

### npm scripts (front-end, reference)

| Script | What it does |
|--------|----------------|
| `lint` / `lint:fix` | ESLint on `assets/src/**/*.js` |
| `lint:css` / `lint:css:fix` | Stylelint on `assets/src/**/*.scss` |
| `format` / `format:fix` | Prettier on `assets/src/**/*.{js,jsx}` |
| `start` / `build` | `@wordpress/scripts` bundle |
| `build:all` | Full verification + build (see **Build** below) |
| `zip` | Build distributable zip via `.config/zip.js` (uses `archiver`; respects `.distignore`-style exclusions) |

### Configuration files

| Path | Role |
|------|------|
| `.config/.php-cs-fixer.php` | PHP code style |
| `.config/phpstan.neon` | Static analysis |
| `.config/phpstan-bootstrap.php` | PHPStan bootstrap |
| `.config/phpunit.xml.dist` | PHPUnit **unit** tests |
| `.config/phpunit.integration.xml.dist` | PHPUnit **integration** tests |
| `.config/.eslintrc.js` | ESLint (`plugin:@wordpress/eslint-plugin/recommended`) |
| `.config/stylelintrc.json` | Stylelint (`@wordpress/stylelint-config/scss-stylistic` + project overrides) |
| `package.json` | `"prettier": "@wordpress/prettier-config"` for ESLint / editor Prettier |
| `.editorconfig` | Tabs for source; spaces for `package.json` / YAML |
| `.config/local-wp-cli.sh` | Local WP shell + `wp` for `lint:pcp` / `make:pot` / `integration-test` |
| `.config/pcp-setup.php` | Loaded by `wp plugin check --require` (CLI only) |
| `.config/zip.js` | Distributable zip builder (`npm run zip`); excludes dev files via `archiver` globs |
| `.config/wp-tests-env.example` | Template for integration test DB / path variables (copy to `wp-tests.env`) |

### PHP — `composer run verify:php`

Runs, in order:

1. **PHP CS Fixer** — `.config/.php-cs-fixer.php`
2. **PHPStan** — `.config/phpstan.neon`
3. **PHPUnit (unit)** — `.config/phpunit.xml.dist` (`tests/Unit/`)

For integration tests only, see **`tests/README.md`** and `bash .config/local-wp-cli.sh integration-test`.

### Front-end — ESLint, Stylelint, Prettier

- **ESLint** — `.config/.eslintrc.js` extends the WordPress `recommended` preset (Prettier runs inside ESLint when `prettier` is installed; do not duplicate `prettier/prettier` rules locally).
- **Stylelint** — `.config/stylelintrc.json`; lints SCSS under `assets/src/` (scripts pass `--config`).
- **Prettier** — configured via `package.json` and `@wordpress/prettier-config`; `format` / `format:fix` apply to JavaScript/JSX only so SCSS stays aligned with Stylelint stylistic rules.

### Plugin Check and POT — Local by Flywheel only

`lint:pcp` and `make:pot` are **not** plain Composer binaries: they run **`.config/local-wp-cli.sh`**, which:

1. Resolves the WordPress root (walks up from this plugin until `wp-load.php`).
2. Finds the matching Local **`~/.config/Local/ssh-entry/*.sh`** entry (same `cd` target as that root).
3. Sources Local’s `export` / `cd` / `unset` lines so `PATH`, PHP, and WP-CLI match **Open Site Shell**.
4. Runs `wp plugin check` or `wp i18n make-pot`.

**Requirements**

- Site created in **Local**; plugin under `wp-content/plugins/updatronix` as usual.
- Local has generated **ssh-entry** scripts (open **Site Shell** once or start the site if needed).
- **`bash`** available (Git Bash or WSL on Windows).

No `.env` or extra config files are required for these two commands.

**Plugin Check options** (defined as variables in `.config/local-wp-cli.sh`, edit there to change):

- **Excluded directories:** `.config`, `.github`, `.cursor`, **`bin`** (dev install script), **`tests`** (PHPUnit — not in release zip per `.distignore`)
- **Excluded files:** `workflow.md`, `.distignore`, `.gitignore`, `.gitattributes`, `.editorconfig`, **`updatronix.zip`** (artifact from `npm run zip` if present)
- **Ignored result codes:** `plugin_updater_detected`, `update_modification_detected` (expected for an updates-management plugin using core update APIs)

```bash
composer run lint:pcp
composer run make:pot
```

## Build

Run the complete build + verification pipeline in one shot:

```bash
npm run build:all
```

This command executes, in order:

1. `composer run verify:php` (PHP CS Fixer + PHPStan + **unit** tests)
2. `composer run lint:pcp`
3. `npm run lint`
4. `npm run lint:css`
5. `npm run format`
6. `composer run make:pot`
7. `npm run build`

Notes:

- `verify:php` replaces a separate `lint:php` + manual `composer test`: it is the canonical PHP gate before front-end checks.
- `lint:pcp` and `make:pot` rely on Local by Flywheel (see `workflow.md` / `.config/local-wp-cli.sh`).
- **Integration tests** are **not** part of `build:all` (they need DB + `wordpress-tests-lib`). Run them separately when needed: `bash .config/local-wp-cli.sh integration-test` (see `tests/README.md`).
- `npm run build` uses `@wordpress/scripts` to bundle JS (and compile SCSS imports via the entry `assets/src/index.js`) into `assets/build/`.

## Development workflow

### Build assets (`@wordpress/scripts`)

```bash
npm start    # watch
npm run build
```

- Entry: `assets/src/index.js` (imports `assets/src/index.scss`)
- Output: `assets/build/`, RTL CSS, dependency extraction

### Code layout

- `inc/core/` — plugin constants (`UPDATRONIX_PLUGIN_FILE`, `UPDATRONIX_CAP_MANAGE`, legacy aliases)
- `inc/classes/` — core services (bootstrap, DB, logger, cron, settings, …)
- `inc/admin/` — admin UI, menus, enqueue
- `inc/settings/` — options and settings

## License

GPL-2.0-or-later — see **`LICENSE`**.
