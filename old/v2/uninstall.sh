#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="${1:-/opt/web-application-management-system}"
[ "$(id -u)" -eq 0 ] || { echo "Bitte mit sudo ausführen." >&2; exit 1; }
LEDGER="$ROOT/installation-ledger.tsv"
[ -f "$LEDGER" ] || { echo "Kein Installationsjournal gefunden; Abbruch." >&2; exit 1; }
if grep -q $'file.create\t/etc/apache2/sites-available/wams.conf\tcreated-by-wams' "$LEDGER"; then a2dissite wams >/dev/null 2>&1 || true; rm -f /etc/apache2/sites-available/wams.conf; apache2ctl configtest && systemctl reload apache2; fi
if grep -q $'file.create\t/etc/apache2/conf-available/wams-port.conf\tcreated-by-wams' "$LEDGER"; then a2disconf wams-port >/dev/null 2>&1 || true; rm -f /etc/apache2/conf-available/wams-port.conf; fi
while IFS=$'\t' read -r _ action target owned; do [ "$action" = package.install ] && [ "$owned" = created-by-wams ] && apt-get remove -y "$target" || true; done < "$LEDGER"
rm -rf --one-file-system "$ROOT"
echo "WAMS wurde entfernt. Bereits vorhandene Pakete wurden nicht entfernt."
