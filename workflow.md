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
| 1 | `composer run lint:php` | PHP CS Fixer + PHPStan on `updatronix.php` and `inc/` |
| 2 | `composer run lint:pcp` | Plugin Check via WP-CLI (requires **Local** — see below) |
| 3 | `npm run lint` | ESLint (WordPress preset + Prettier via `@wordpress/eslint-plugin`); `npm run lint:fix` to auto-fix |
| 4 | `npm run lint:css` | Stylelint on `assets/src/**/*.scss` (`npm run lint:css:fix` to auto-fix) |
| 5 | `npm run format` | Prettier check on `assets/src/**/*.{js,jsx}` (`npm run format:fix` to write) |
| 6 | `composer run make:pot` | Regenerate `languages/updatronix.pot` (requires **Local** — see below) |

WordPress.org suggests using coding standards / static analysis together with [Plugin Check](https://make.wordpress.org/plugins/developers/). This repo uses **PHP CS Fixer** and **PHPStan** for PHP, then Plugin Check for WordPress.org-oriented rules. Front-end JS follows **`@wordpress/eslint-plugin`**; SCSS follows **`@wordpress/stylelint-config/scss-stylistic`**; Prettier uses **`@wordpress/prettier-config`** (see `package.json`). SCSS is linted with Stylelint, not Prettier, so formatter commands target JS/JSX only.

### Composer scripts (reference)

| Script | Definition |
|--------|------------|
| `lint:php` | PHP CS Fixer then PHPStan (see `composer.json`) |
| `lint:pcp` | `bash .config/local-wp-cli.sh pcp` |
| `make:pot` | `bash .config/local-wp-cli.sh pot` |

### npm scripts (front-end, reference)

| Script | What it does |
|--------|----------------|
| `lint` / `lint:fix` | ESLint on `assets/src/**/*.js` |
| `lint:css` / `lint:css:fix` | Stylelint on `assets/src/**/*.scss` |
| `format` / `format:fix` | Prettier on `assets/src/**/*.{js,jsx}` |
| `start` / `build` | `@wordpress/scripts` bundle |

### Configuration files

| Path | Role |
|------|------|
| `.config/.php-cs-fixer.php` | PHP code style |
| `.config/phpstan.neon` | Static analysis |
| `.config/phpstan-bootstrap.php` | PHPStan bootstrap |
| `.config/.eslintrc.js` | ESLint (`plugin:@wordpress/eslint-plugin/recommended`) |
| `.config/stylelintrc.json` | Stylelint (`@wordpress/stylelint-config/scss-stylistic` + project overrides) |
| `package.json` | `"prettier": "@wordpress/prettier-config"` for ESLint / editor Prettier |
| `.editorconfig` | Tabs for source; spaces for `package.json` / YAML |
| `.config/local-wp-cli.sh` | Local WP shell + `wp` for `lint:pcp` / `make:pot` |
| `.config/pcp-setup.php` | Loaded by `wp plugin check --require` (CLI only) |

### PHP — `composer run lint:php`

- **PHP CS Fixer** — `.config/.php-cs-fixer.php`
- **PHPStan** — `.config/phpstan.neon`

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

- **Excluded directories:** `.config`, `.github`, `.cursor`
- **Excluded files:** `workflow.md`, `.distignore`, `.gitignore`, `.gitattributes`, `.editorconfig`
- **Ignored result codes:** `plugin_updater_detected`, `update_modification_detected` (expected for an updates-management plugin using core update APIs)

```bash
composer run lint:pcp
composer run make:pot
```

## Development workflow

### Build assets (`@wordpress/scripts`)

```bash
npm start    # watch
npm run build
```

- Entry: `assets/src/index.js` (imports `assets/src/index.scss`)
- Output: `assets/build/`, RTL CSS, dependency extraction

### Code layout

- `inc/classes/` — core services (bootstrap, DB, logger, cron, settings, …)
- `inc/admin/` — admin UI, menus, enqueue
- `inc/settings/` — options and settings

## License

GPL-2.0-or-later — see **`LICENSE`**.
