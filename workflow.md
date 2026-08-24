# Updatronix — development workflow

Essentials. Full command/config/test reference: `.agents/docs/BUILD.md`.

## Prerequisites

- PHP **8.1+** (see `readme.txt`) · WordPress **6.2+** · Composer · Node.js **LTS** + npm · Python **3**
- **Local by Flywheel** required for `lint:pcp`, `make:pot`, and integration tests.

## Setup once

```bash
composer install
npm install
bash bin/setup-dev.sh   # one-time: writes .config/wp-tests.env + installs the WP test stack
```

`bin/setup-dev.sh` is safe to re-run; regenerate the env after DB credentials change with `--force`.

## Essential commands

| Command | What it does |
|---------|--------------|
| `composer run verify:php` | WordPress Coding Standards (WPCS) + PHPStan + PHPUnit **unit** tests |
| `npm run lint` / `npm run lint:css` / `npm run format` | ESLint / Stylelint / Prettier (`:fix` variants auto-fix) |
| `composer run lint:pcp` | Plugin Check via WP-CLI (**Local only**) |
| `composer run make:pot` | Regenerate `languages/updatronix.pot` (**Local only**) |
| `composer run test:integration` | PHPUnit **integration** suite (uses Local's PHP/mysqli) |
| `WP_MULTISITE=1 bash .config/local-wp-cli.sh integration-test --filter Multisite` | Multisite integration tests (self-skip otherwise) |

## Full gates

| Command | What it runs |
|---------|--------------|
| `npm run test:all` | All linters + unit + integration (no build) |
| `npm run build:all` | `test:all` + `make:pot` + `build` |
| `npm run zip` | Build distributable zip via `.config/zip.js` |

Integration tests skip gracefully (exit 0) when the WP test environment is not installed.

## Build assets

- `npm start` — watch; `npm run build` — one-shot bundle via `@wordpress/scripts`.
- Entry: `assets/src/index.js` (imports `assets/src/index.scss`) → `assets/build/` + RTL CSS + dependency extraction.
- Full build pipeline order: see `build:all` in `.agents/docs/BUILD.md`.

## Golden rules

- **Version** — never bump `UPDATRONIX_VERSION`, headers, `Stable tag:`, or package versions without explicit owner authorization.
- **i18n** — never reword strings inside `__()`, `_e()`, `_n()`, `_x()`, `esc_*()`. Text domain stays `updatronix`.
- Run most checks in **Local** (reuses Local's PHP, MySQL, WP-CLI — same environment for `lint:pcp` / `make:pot` / integration). On a fresh machine: `composer install` + `npm install` + `bash bin/setup-dev.sh`.
- Skip irrelevant commands: no `make:pot` unless i18n strings changed, no `build` unless `assets/src/` changed, no `lint:css` unless SCSS changed, no `verify:php` unless PHP changed.
- State "Lint skipped per economy rules (no PHP/JS surface changed)" when skipping.
- Full command reference: `.agents/docs/BUILD.md`. Test suite details: `tests/README.md`.

## Code layout

- `inc/core/` — plugin constants (`UPDATRONIX_PLUGIN_FILE`, `UPDATRONIX_CAP_MANAGE`, legacy aliases)
- `inc/classes/` — core services · `inc/admin/` — admin UI · `inc/settings/` — options and settings

## License

GPL-2.0-or-later — see **`LICENSE`**.
