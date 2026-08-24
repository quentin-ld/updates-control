/**
 * Build a distribution ZIP archive, excluding development files listed in the ignore array.
 */
const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const startTime = Date.now();
const currentDir = path.basename(process.cwd());
const zipFileName = `${currentDir}.zip`;
const output = fs.createWriteStream(path.join('.', zipFileName));
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
	const duration = Date.now() - startTime;
	const size = archive.pointer();
	const sizeStr =
		size < 1024 ? `${size} bytes` : `${(size / 1024).toFixed(2)} KB`;
	console.log(`Zipped: ${zipFileName} (${sizeStr}) in ${duration}ms`);
});

archive.on('error', (err) => {
	throw err;
});

archive.pipe(output);

const ignore = [
	'.agents/**',
	'.cache/**',
	'.claude/**',
	'.config/**',
	'.cursor/**',
	'.github/**',
	'.git/**',
	'.phpunit.cache/**',
	'.reasonix/**',
	'.sublime/**',
	'.vscode/**',
	'.wordpress-org/**',
	'assets/src/**',
	'bin/**',
	'docs/**',
	'graft/**',
	'node_modules/**',
	'tests/**',
	'vendor/**',
	'*.sql',
	'*.tar.gz',
	'*.zip',
	'.distignore',
	'.DS_Store',
	'.editorconfig',
	'.eslintrc',
	'.eslintrc.js',
	'.gitattributes',
	'.gitignore',
	'.gitlab-ci.yml',
	'.gitmodules',
	'.phpunit.result.cache',
	'.prettierrc',
	'.prettierrc.js',
	'.travis.yml',
	'behat.yml',
	'circle.yml',
	'composer.json',
	'composer.lock',
	'eslintrc',
	'eslintrc.js',
	'FEATURE_REQUEST.md',
	'Gruntfile.js',
	'multisite.xml',
	'multisite.xml.dist',
	'package-lock.json',
	'package.json',
	'phpcs.ruleset.xml',
	'phpcs.xml',
	'phpcs.xml.dist',
	'phpstan-bootstrap.php',
	'phpstan.neon',
	'phpunit.xml',
	'phpunit.xml.dist',
	'postcss.config.sort.js',
	'prettierrc',
	'prettierrc.js',
	'README.md',
	'Thumbs.db',
	'workflow.md',
	'wp-cli.local.yml',
	'yarn.lock',
	'.ignore',
	'.mcp.json',
	'.phpactor.json',
	'AGENTS.md',
	'.ignore',
	'reasonix.toml'
];

function isIgnored(filePath, ignorePatterns) {
	for (const pattern of ignorePatterns) {
		if (pattern.endsWith('/**')) {
			const prefix = pattern.slice(0, -3);
			if (filePath === prefix || filePath.startsWith(prefix + '/')) {
				return true;
			}
		} else if (pattern.startsWith('*')) {
			const ext = pattern.slice(1);
			if (filePath.endsWith(ext)) {
				return true;
			}
		} else if (filePath === pattern) {
			return true;
		}
	}
	return false;
}

function walkDir(dir, baseDir, results) {
	const entries = fs.readdirSync(dir, { withFileTypes: true });
	for (const entry of entries) {
		const fullPath = path.join(dir, entry.name);
		const relativePath = path.relative(baseDir, fullPath);

		if (isIgnored(relativePath, ignore)) {
			continue;
		}

		if (entry.isDirectory()) {
			walkDir(fullPath, baseDir, results);
		} else {
			results.push(fullPath);
		}
	}
}

const files = [];
walkDir(process.cwd(), process.cwd(), files);

for (const filePath of files) {
	const relativePath = path.relative(process.cwd(), filePath);
	archive.file(filePath, {
		name: path.join(currentDir, relativePath),
	});
}

archive.finalize();
