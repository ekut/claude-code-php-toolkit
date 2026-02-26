#!/usr/bin/env bash
# install.sh — Install PHP rules to ~/.claude/rules/
#
# Usage:
#   ./install.sh           # Install common + php rules
#   ./install.sh --help    # Show usage
#
# Claude Code plugins distribute agents, skills, commands, and hooks
# automatically via /plugin install. However, rules must be installed
# manually. This script handles that.

set -euo pipefail

SCRIPT_PATH="$0"
while [ -L "$SCRIPT_PATH" ]; do
    link_dir="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"
    SCRIPT_PATH="$(readlink "$SCRIPT_PATH")"
    [[ "$SCRIPT_PATH" != /* ]] && SCRIPT_PATH="$link_dir/$SCRIPT_PATH"
done
SCRIPT_DIR="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"
RULES_DIR="$SCRIPT_DIR/rules"

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
    echo "Usage: $0"
    echo ""
    echo "Installs PHP rules to ~/.claude/rules/"
    echo ""
    echo "Installed rule sets:"
    echo "  common/  — git workflow, development workflow"
    echo "  php/     — coding style, testing, security, performance"
    exit 0
fi

DEST_DIR="${CLAUDE_RULES_DIR:-$HOME/.claude/rules}"

if [[ -d "$DEST_DIR" ]] && [[ "$(ls -A "$DEST_DIR" 2>/dev/null)" ]]; then
    echo "Note: $DEST_DIR/ already exists. Existing files will be overwritten."
    echo "      Back up any local customizations before proceeding."
fi

echo "Installing common rules -> $DEST_DIR/common/"
mkdir -p "$DEST_DIR/common"
cp -r "$RULES_DIR/common/." "$DEST_DIR/common/"

echo "Installing php rules -> $DEST_DIR/php/"
mkdir -p "$DEST_DIR/php"
cp -r "$RULES_DIR/php/." "$DEST_DIR/php/"

echo ""
echo "Done. Rules installed to $DEST_DIR/"
echo ""
echo "Installed files:"
find "$DEST_DIR/common" "$DEST_DIR/php" -name '*.md' | sort | while read -r f; do
    echo "  $f"
done
