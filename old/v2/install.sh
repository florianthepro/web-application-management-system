#!/usr/bin/env bash
set -Eeuo pipefail
REPOSITORY=""
INSTALL_ROOT="/opt/web-application-management-system"
PORT="8088"
while [ "$#" -gt 0 ]; do
  case "$1" in
    --repository) REPOSITORY="${2:-}"; shift 2 ;;
    --install-root) INSTALL_ROOT="${2:-}"; shift 2 ;;
    --port) PORT="${2:-}"; shift 2 ;;
    *) echo "Unbekannter Parameter: $1" >&2; exit 2 ;;
  esac
done
if [ -z "$REPOSITORY" ]; then read -r -p "Git-Repository: " REPOSITORY; fi
case "$REPOSITORY" in https://github.com/*|git@github.com:*) ;; *) echo "Nur explizite GitHub-Repository-URLs sind in diesem Installer erlaubt." >&2; exit 2;; esac
if [ "$(id -u)" -ne 0 ]; then echo "Bitte mit sudo ausführen." >&2; exit 1; fi
if [ -e "$INSTALL_ROOT" ] && [ -n "$(find "$INSTALL_ROOT" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" ]; then echo "Zielordner ist nicht leer: $INSTALL_ROOT" >&2; exit 1; fi
for p in git apache2 php php-cli php-sqlite3 php-zip php-mbstring libapache2-mod-php; do
  if dpkg-query -W -f='${Status}' "$p" 2>/dev/null | grep -q 'ok installed'; then echo "Paket bereits vorhanden: $p"; else MISSING="${MISSING:-} $p"; fi
done
mkdir -p "$INSTALL_ROOT"
LEDGER="$INSTALL_ROOT/installation-ledger.tsv"
printf 'timestamp\taction\ttarget\townership\n' > "$LEDGER"
if [ -n "${MISSING:-}" ]; then
  apt-get update
  apt-get install -y $MISSING
  for p in $MISSING; do printf '%s\tpackage.install\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$p" >> "$LEDGER"; done
fi
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
git clone --depth 1 "$REPOSITORY" "$TMP/repo"
cp -a "$TMP/repo/." "$INSTALL_ROOT/"
chown -R root:www-data "$INSTALL_ROOT"
find "$INSTALL_ROOT" -type d -exec chmod 0750 {} +
find "$INSTALL_ROOT" -type f -exec chmod 0640 {} +
chmod 0770 "$INSTALL_ROOT/state" "$INSTALL_ROOT/runtime" "$INSTALL_ROOT/backups" "$INSTALL_ROOT/exports"
PORTS_CONF=/etc/apache2/conf-available/wams-port.conf
if ! ss -ltnH | awk '{print $4}' | grep -Eq "(:|\])$PORT$"; then printf 'Listen %s\n' "$PORT" > "$PORTS_CONF"; a2enconf wams-port >/dev/null; printf '%s\tfile.create\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$PORTS_CONF" >> "$LEDGER"; fi
CONF=/etc/apache2/sites-available/wams.conf
if [ -e "$CONF" ]; then echo "Apache-Datei existiert bereits: $CONF" >&2; exit 1; fi
sed -e "s|__PORT__|$PORT|g" -e 's|__SERVER_NAME__|localhost|g' -e "s|__INSTALL_ROOT__|$INSTALL_ROOT|g" "$INSTALL_ROOT/resources/apache/wams.conf.template" > "$CONF"
printf '%s\tfile.create\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$CONF" >> "$LEDGER"
a2enmod rewrite >/dev/null
a2ensite wams >/dev/null
apache2ctl configtest
systemctl reload apache2
IP="$(hostname -I 2>/dev/null | awk '{print $1}')"; [ -n "$IP" ] || IP=127.0.0.1
echo
echo "WAMS-Browserinstallation ist bereit: http://$IP:$PORT/?p=setup"
echo "Das Terminal kann nach Abschluss im Browser geschlossen werden."
while [ ! -f "$INSTALL_ROOT/state/.installed" ]; do sleep 3; if [ -f "$INSTALL_ROOT/state/database/management.sqlite" ]; then touch "$INSTALL_ROOT/state/.installed"; fi; done
echo "Installation abgeschlossen."
