#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="${1:?Installationspfad fehlt}"
for d in state runtime backups exports;do
  [ "$(stat -c '%U:%G' "$ROOT/$d")" = "www-data:www-data" ]
  [ "$(stat -c '%a' "$ROOT/$d")" = "770" ]
done
[ "$(stat -c '%U:%G' "$ROOT/state/database.sqlite")" = "www-data:www-data" ]
[ "$(stat -c '%a' "$ROOT/state/database.sqlite")" = "660" ]
