#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="${1:-}";[ "$(id -u)" -eq 0 ]||exit 1;[ -n "$ROOT" ]||{ echo "Pfad angeben.";exit 1; };LEDGER="$ROOT/state/installation-ledger.tsv";[ -f "$LEDGER" ]||{ echo "Journal fehlt; Abbruch.";exit 1; }
while IFS=$'\t' read -r _ action target owner;do [ "$owner" = created-by-wams ]||continue;if [ "$action" = file.create ];then case "$target" in /etc/apache2/sites-available/wams-*.conf) slug="$(basename "$target" .conf)";a2dissite "$slug" >/dev/null 2>&1||true;rm -f "$target" "/etc/apache2/conf-available/$slug-port.conf";;esac;fi;done < "$LEDGER"
for unit in /etc/systemd/system/wams-*-update.timer;do [ -e "$unit" ]||continue;name="$(basename "$unit" .timer)";systemctl disable --now "$name.timer" >/dev/null 2>&1||true;rm -f "/etc/systemd/system/$name.timer" "/etc/systemd/system/$name.service";done
systemctl daemon-reload
apache2ctl configtest&&systemctl reload apache2;rm -rf --one-file-system "$ROOT";echo "WAMS entfernt. Vorhandene Apache/PHP-Komponenten blieben erhalten."
