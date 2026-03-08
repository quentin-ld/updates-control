# Updatronix Plugin

Updatronix is a WordPress plugin that logs core, plugin, and theme updates with error handling, security, and optional email notifications.
This document describes the development workflow to keep code quality, consistency, and easy deployment.

## 🚀 Getting Started

### Prerequisites

- PHP **8.1 or higher** (tested up to PHP 8.4)
- Composer
- Node.js & npm (for WordPress Scripts)
- WordPress >= **6.0**

### Installation

Clone the repository and install dependencies:
```bash
composer install
npm install
```

## 🧹 Code Quality

Updatronix enforces strict coding standards and static analysis to avoid bugs and maintain clean code.

All configuration files are stored in the `.config` folder:
- `.config/.eslintrc.js` - ESLint configuration
- `.config/.php-cs-fixer.php` - PHP CS Fixer configuration
- `.config/phpstan.neon` - PHPStan configuration
- `.config/phpstan-bootstrap.php` - PHPStan bootstrap file

### PHP Linting

Run PHP linting with:
```bash
composer run lint:php
```

This will:

- Use **PHP CS Fixer** (configured in `.config/.php-cs-fixer.php`) to automatically fix code style issues.
- Use **PHPStan** (configured in `.config/phpstan.neon`) for static analysis and bug detection.

### JavaScript / React Linting

Run JavaScript/React linting with:
```bash
npm run lint
```
- ESLint (configured in `.config/.eslintrc.js`) checks for code quality, JSDoc alignment, and best practices.

To automatically fix fixable issues:
```bash
npm run lint -- --fix
```

## 🛠️ Development Workflow

### Building Assets

The plugin uses **@wordpress/scripts** for building JavaScript and SCSS assets:

```bash
# Development mode with watch (hot reload)
npm start

# Production build
npm run build
```

The build process:
- Compiles JavaScript/React code from `assets/src/index.js`
- Compiles SCSS from `assets/src/index.scss` (imported in the JS file)
- Outputs to `assets/build/` directory
- Automatically generates RTL CSS files
- Handles WordPress dependency extraction

### Code Structure

- PHP classes live in `/inc/classes/` (Bootstrap, Database, Logger, Cron, Settings, etc.).
- Admin UI (menu, links, enqueue) is in `/inc/admin/`.
- Settings and options are in `/inc/settings/`.
- All new code must pass linting before committing.

## License

Updatronix is licensed under the GPL-2.0-or-later license. See the [LICENSE](LICENSE) file for more details.
