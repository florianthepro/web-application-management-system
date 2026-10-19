#!/usr/bin/env bash
set -Eeuo pipefail
REPOSITORY="https://github.com/florianthepro/web-application-management-system";PORT=8443
while [ "$#" -gt 0 ];do case "$1" in --repository)REPOSITORY="$2";shift 2;;--port)PORT="$2";shift 2;;*)echo "Unbekannte Option: $1" >&2;exit 2;;esac;done
[ "$(id -u)" -eq 0 ]||{ echo 'Bitte mit sudo ausfuehren.' >&2;exit 1; }
need=0;for x in git apache2 php openssl rsync ss;do command -v "$x" >/dev/null||need=1;done
php -m 2>/dev/null|grep -qi '^pdo_sqlite$'||need=1
if [ "$need" -eq 1 ];then apt-get update;apt-get install -y git apache2 libapache2-mod-php php-cli php-sqlite3 openssl rsync iproute2;fi
while ss -ltnH|awk '{print $4}'|grep -Eq "(:|])$PORT$";do PORT=$((PORT+1));done
TMP="$(mktemp -d /var/tmp/wams-install.XXXXXX)";ROOT="";SITE="";cleanup(){ rm -rf "$TMP";if [ "${1:-0}" -ne 0 ]&&[ -n "$SITE" ];then a2dissite "$SITE" >/dev/null 2>&1||true;rm -f "/etc/apache2/sites-available/$SITE.conf";apache2ctl configtest >/dev/null 2>&1&&systemctl reload apache2||true;fi;};trap 'code=$?;cleanup "$code"' EXIT
GIT_TERMINAL_PROMPT=0 git clone --depth 1 "$REPOSITORY" "$TMP/repo"
required=(VERSION LICENSE README.md config/defaults.php migrations/001.sql src/Core.php public/index.php public/setup.php public/assets/style.css resources/apache.conf.template)
for f in "${required[@]}";do [ -f "$TMP/repo/$f" ]||{ echo "Erforderliche Datei fehlt: $f" >&2;exit 1; };done
for f in config/defaults.php src/Core.php public/index.php public/setup.php;do php -l "$TMP/repo/$f" >/dev/null||{ echo "PHP-Syntaxpruefung fehlgeschlagen: $f" >&2;exit 1; };done
ID="$(cat /proc/sys/kernel/random/uuid)";ROOT="/opt/wams-$ID";mkdir -p "$ROOT"
for f in VERSION LICENSE README.md install.sh;do cp "$TMP/repo/$f" "$ROOT/";done
for d in config migrations src public resources;do cp -a "$TMP/repo/$d" "$ROOT/";done
mkdir -p "$ROOT/state/tls" "$ROOT/state/apps"
IP="$(hostname -I|awk '{print $1}')";HOST="$(hostname -f 2>/dev/null||hostname)"
openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 -subj "/CN=$HOST" -addext "subjectAltName=DNS:$HOST,IP:$IP" -keyout "$ROOT/state/tls/server.key" -out "$ROOT/state/tls/server.crt" >/dev/null 2>&1
chown -R root:www-data "$ROOT";find "$ROOT" -type d -exec chmod 0750 {} +;find "$ROOT" -type f -exec chmod 0640 {} +;chown -R www-data:www-data "$ROOT/state";find "$ROOT/state" -type d -exec chmod 0770 {} +;find "$ROOT/state" -type f -exec chmod 0660 {} +
SITE="wams-${ID%%-*}";sed -e "s|__PORT__|$PORT|g" -e "s|__ROOT__|$ROOT|g" "$ROOT/resources/apache.conf.template" > "/etc/apache2/sites-available/$SITE.conf"
a2enmod ssl rewrite >/dev/null;a2ensite "$SITE" >/dev/null;apache2ctl configtest;systemctl reload apache2
curl -kfsS "https://127.0.0.1:$PORT/setup.php" >/dev/null
echo "WAMS installiert."
echo "Setup: https://$IP:$PORT/setup.php"
