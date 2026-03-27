#!/usr/bin/env bash
# Runs WP-CLI with Local by Flywheel’s site environment (same as “Open Site Shell”).
# Requires: site created in Local, ssh-entry scripts under ~/.config/Local/ssh-entry/
# Usage: bash .config/local-wp-cli.sh pcp|pot

set -euo pipefail

MODE="${1:-}"
if [[ "$MODE" != "pcp" && "$MODE" != "pot" ]]; then
	echo "Usage: bash .config/local-wp-cli.sh pcp|pot" >&2
	exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# WordPress root: walk up from this plugin until wp-load.php
DIR="$PLUGIN_ROOT"
WP_ROOT=""
while [[ "$DIR" != "/" ]]; do
	if [[ -f "${DIR}/wp-load.php" ]]; then
		WP_ROOT="$(cd "$DIR" && pwd -P)"
		break
	fi
	DIR="$(dirname "$DIR")"
done

if [[ -z "$WP_ROOT" ]]; then
	echo "Could not find wp-load.php above the plugin directory." >&2
	exit 1
fi

SSH_DIR="${HOME}/.config/Local/ssh-entry"
if [[ ! -d "$SSH_DIR" ]]; then
	echo "Local WP not detected (~/.config/Local/ssh-entry missing). composer run lint:pcp / make:pot only work with Local by Flywheel." >&2
	exit 1
fi

SSH_ENTRY=""
for f in "${SSH_DIR}"/*.sh; do
	[[ -f "$f" ]] || continue
	# Match the cd line Local writes for this site root
	CD_LINE="$(grep -E '^cd "' "$f" | head -1 || true)"
	if [[ -z "$CD_LINE" ]]; then
		continue
	fi
	CD_PATH="${CD_LINE#cd \"}"
	CD_PATH="${CD_PATH%\"}"
	if [[ "$(cd "$CD_PATH" 2>/dev/null && pwd -P)" == "$WP_ROOT" ]]; then
		SSH_ENTRY="$f"
		break
	fi
done

if [[ -z "$SSH_ENTRY" ]]; then
	echo "No Local ssh-entry script matches this WordPress path:" >&2
	echo "  ${WP_ROOT}" >&2
	echo "Open the site in Local once (Site Shell) or ensure the site lives under Local Sites." >&2
	exit 1
fi

# Load Local’s PATH, PHPRC, LD_LIBRARY_PATH, etc. (only export/cd/unset — no interactive echoes)
# shellcheck disable=SC1090
source <(awk '/^export / { print } /^cd "/ { print } /^unset NODE_ENV$/ { print }' "$SSH_ENTRY")

REQUIRE_FILE="${PLUGIN_ROOT}/.config/pcp-setup.php"

# Paths and files not part of the distributable plugin (dev / repo tooling).
PCP_EXCLUDE_DIRS=".config,.github,.cursor"
PCP_EXCLUDE_FILES="workflow.md,.distignore,.gitignore,.gitattributes,.editorconfig"

# Legitimate use of core update APIs for an updates-management plugin (not a bundled updater).
PCP_IGNORE_CODES="plugin_updater_detected,update_modification_detected"

case "$MODE" in
	pcp)
		wp plugin check updatronix \
			--require="$REQUIRE_FILE" \
			--exclude-directories="$PCP_EXCLUDE_DIRS" \
			--exclude-files="$PCP_EXCLUDE_FILES" \
			--ignore-codes="$PCP_IGNORE_CODES"
		;;
	pot)
		wp i18n make-pot "$PLUGIN_ROOT" "${PLUGIN_ROOT}/languages/updatronix.pot" --exclude=assets/build
		;;
esac
