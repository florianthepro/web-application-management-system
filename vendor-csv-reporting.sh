#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
TARGET="$ROOT/applications/csv-reporting/upstream"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT INT TERM
GIT_TERMINAL_PROMPT=0 git clone --depth 1 https://github.com/florianthepro/csv-reporting.git "$TMP/repo"
for file in LICENSE README.md index.php map.php rules.php check.php auto-download.php;do
  [ -f "$TMP/repo/$file" ] || { echo "Upstream-Datei fehlt: $file" >&2; exit 1; }
done
find "$TARGET" -mindepth 1 -maxdepth 1 -type f ! -name README.md -delete
cp "$TMP/repo/LICENSE" "$TMP/repo/README.md" "$TMP/repo/index.php" "$TMP/repo/map.php" "$TMP/repo/rules.php" "$TMP/repo/check.php" "$TMP/repo/auto-download.php" "$TARGET/"
printf '%s\n' "$(git -C "$TMP/repo" rev-parse HEAD)" > "$TARGET/UPSTREAM_COMMIT"
echo "CSV Reporting Upstream aktualisiert: $(cat "$TARGET/UPSTREAM_COMMIT")"
