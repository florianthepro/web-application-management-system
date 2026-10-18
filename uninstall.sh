#!/usr/bin/env bash
set -Eeuo pipefail
[ "$(id -u)" -eq 0 ]||{ echo "Bitte mit sudo ausführen." >&2;exit 1; }
ROOT="$(cd "$(dirname "$0")"&&pwd)";SLUG="$(grep -Rsl "DocumentRoot $ROOT/public" /etc/apache2/sites-available/wams-*.conf 2>/dev/null|head -1|xargs -r basename|sed 's/.conf$//')"
[ -n "$SLUG" ]||{ echo "Apache-Site nicht gefunden." >&2;exit 1; }
read -r -p "WAMS vollständig entfernen? [ja/NEIN] " A;[ "$A" = ja ]||exit 0
systemctl disable --now "$SLUG-update.timer" >/dev/null 2>&1||true;rm -f "/etc/systemd/system/$SLUG-update.timer" "/etc/systemd/system/$SLUG-update.service";systemctl daemon-reload
a2dissite "$SLUG" >/dev/null;a2disconf "$SLUG-port" >/dev/null;rm -f "/etc/apache2/sites-available/$SLUG.conf" "/etc/apache2/conf-available/$SLUG-port.conf";apache2ctl configtest;systemctl reload apache2;rm -rf --one-file-system "$ROOT"
