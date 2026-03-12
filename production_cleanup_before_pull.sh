#!/bin/bash
set -euo pipefail

# Clean up runtime-generated files that should never block git pull/merge on production.
# This is especially important for cPanel deployments where Vtiger regenerates
# user_privileges/*.php based on DB/user changes.

echo "[cleanup] resetting tracked generated privilege files (if any)..."

# If these files are tracked in the current checkout, discard local modifications
# so a pull/merge can proceed (older deployments may still have them tracked).
git checkout -- user_privileges/*.php 2>/dev/null || true

echo "[cleanup] removing generated privilege files (safe to regenerate)..."
rm -f user_privileges/user_privileges_*.php 2>/dev/null || true
rm -f user_privileges/sharing_privileges_*.php 2>/dev/null || true
rm -f user_privileges/menu_*.php 2>/dev/null || true

echo "[cleanup] optional: clear compiled templates and cache files..."
rm -f templates_c/**/*.php 2>/dev/null || true
find cache -type f -delete 2>/dev/null || true

echo "[cleanup] done. You can run: git pull origin huy-dev"

