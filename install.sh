#!/usr/bin/env bash
set -Eeuo pipefail
REPOSITORY="";SETUP_PORT=8099;APP_PORT=8443
while [ "$#" -gt 0 ];do case "$1" in --repository)REPOSITORY="${2:-}";shift 2;;--setup-port)SETUP_PORT="${2:-}";shift 2;;--app-port|--port)APP_PORT="${2:-}";shift 2;;*)echo "Unbekannter Parameter: $1" >&2;exit 2;;esac;done
[ "$(id -u)" -eq 0 ]||{ echo "Bitte mit sudo ausführen." >&2;exit 1; }
[ -n "$REPOSITORY" ]||read -r -p "Git-Repository: " REPOSITORY
command -v apt-get >/dev/null||{ echo "Debian oder Ubuntu mit APT erforderlich." >&2;exit 1; }
BOOT="$(mktemp -d /var/tmp/wams-bootstrap.XXXXXX)";PHP_PID="";SLUG="";COMMITTED=0
cleanup(){ rc=$?;[ -n "$PHP_PID" ]&&kill "$PHP_PID" 2>/dev/null||true;if [ "$rc" -ne 0 ]&&[ -n "$SLUG" ]&&[ "$COMMITTED" -eq 0 ];then a2dissite "$SLUG" >/dev/null 2>&1||true;a2disconf "$SLUG-port" >/dev/null 2>&1||true;rm -f "/etc/apache2/sites-available/$SLUG.conf" "/etc/apache2/conf-available/$SLUG-port.conf";fi;rm -rf "$BOOT";exit "$rc";};trap cleanup EXIT INT TERM
MISSING=();command -v git >/dev/null||MISSING+=(git);command -v php >/dev/null||MISSING+=(php-cli);command -v apache2 >/dev/null||MISSING+=(apache2 libapache2-mod-php);php -m 2>/dev/null|grep -qi '^pdo_sqlite$'||MISSING+=(php-sqlite3);php -m 2>/dev/null|grep -qi '^zip$'||MISSING+=(php-zip);command -v openssl >/dev/null||MISSING+=(openssl);command -v rsync >/dev/null||MISSING+=(rsync)
if [ "${#MISSING[@]}" -gt 0 ];then apt-get update;apt-get install -y --no-install-recommends "${MISSING[@]}";fi
# remove only orphaned WAMS listeners
for l in /etc/apache2/conf-enabled/wams-*-port.conf;do [ -L "$l" ]||continue;n="$(basename "$l" -port.conf)";[ -e "/etc/apache2/sites-enabled/$n.conf" ]||a2disconf "$n-port" >/dev/null 2>&1||true;done
apache2ctl configtest >/dev/null
port_socket_free(){ ! ss -ltnH|awk '{print $4}'|grep -Eq "(:|])$1$"; }
apache_port_free(){ ! grep -RhsE '^[[:space:]]*Listen[[:space:]]+' /etc/apache2 2>/dev/null|awk '{print $2}'|sed 's/.*://'|grep -qx "$1"; }
while ! port_socket_free "$SETUP_PORT";do SETUP_PORT=$((SETUP_PORT+1));done
while ! port_socket_free "$APP_PORT"||! apache_port_free "$APP_PORT"||[ "$APP_PORT" -eq "$SETUP_PORT" ];do APP_PORT=$((APP_PORT+1));done
GIT_TERMINAL_PROMPT=0 git clone --depth 1 "$REPOSITORY" "$BOOT/repo"
while IFS= read -r -d '' f;do php -l "$f" >/dev/null;done < <(find "$BOOT/repo" -type f -name '*.php' -print0)
bash -n "$BOOT/repo/install.sh";bash -n "$BOOT/repo/uninstall.sh"
TOKEN="$(openssl rand -hex 24)";mkdir -p "$BOOT/repo/runtime";printf '{"token":"%s","appPort":%s}\n' "$TOKEN" "$APP_PORT" > "$BOOT/repo/runtime/probe.json"
php -S "0.0.0.0:$SETUP_PORT" -t "$BOOT/repo" "$BOOT/repo/bootstrap.php" >"$BOOT/setup.log" 2>&1 & PHP_PID=$!
sleep 1;curl -fsS "http://127.0.0.1:$SETUP_PORT/" >/dev/null
IP="$(hostname -I|awk '{print $1}')";echo;echo "WAMS Einrichtung: http://$IP:$SETUP_PORT/";echo "Terminal geöffnet lassen."
REQ="$BOOT/repo/runtime/request.json";while [ ! -f "$REQ" ];do kill -0 "$PHP_PID" 2>/dev/null||{ cat "$BOOT/setup.log";exit 1;};sleep 2;done
readarray -t V < <(php -r '$x=json_decode(file_get_contents($argv[1]),true);foreach(["token","username","hash"]as$k)echo base64_encode($x[$k]??"")."\n";' "$REQ")
dec(){ printf %s "$1"|base64 -d;};[ "$(dec "${V[0]}")" = "$TOKEN" ]||exit 1;USER_NAME="$(dec "${V[1]}")";HASH="$(dec "${V[2]}")"
kill "$PHP_PID";wait "$PHP_PID" 2>/dev/null||true;PHP_PID=""
UUID="$(cat /proc/sys/kernel/random/uuid)";TARGET="/opt/wams-$UUID";mkdir "$TARGET";rsync -a --exclude runtime/probe.json --exclude runtime/request.json "$BOOT/repo/" "$TARGET/"
mkdir -p "$TARGET/state/tls" "$TARGET/state/instances" "$TARGET/state/icons" "$TARGET/runtime" "$TARGET/backups" "$TARGET/exports"
head -c 32 /dev/urandom > "$TARGET/state/secret.key"
chown -R root:www-data "$TARGET";find "$TARGET" -type d -exec chmod 0750 {} +;find "$TARGET" -type f -exec chmod 0640 {} +;for d in state runtime backups exports;do chown -R www-data:www-data "$TARGET/$d";find "$TARGET/$d" -type d -exec chmod 0770 {} +;done
php -r 'require $argv[1]."/src/Core.php";$c=new Wams\Core();$q=$c->db->prepare("INSERT INTO users VALUES(?,?,?,?,1,NULL,0,?)");$q->execute([$c->id(),$argv[2],$argv[3],1,gmdate("c")]);' "$TARGET" "$USER_NAME" "$HASH"
chown www-data:www-data "$TARGET/state/database.sqlite"*;chmod 0660 "$TARGET/state/database.sqlite"*;chmod 0640 "$TARGET/state/secret.key"
find /opt /var/www -maxdepth 4 -type f -path '*/state/database.sqlite' ! -path "$TARGET/*" -printf '%h\n' 2>/dev/null|sed 's#/state$##'|php -r '$a=[];while(($l=fgets(STDIN))!==false){$l=trim($l);if($l!=="")$a[]=["path"=>$l,"detectedAt"=>gmdate("c")];}file_put_contents($argv[1],json_encode($a,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));' "$TARGET/state/legacy-installations.json"
chown www-data:www-data "$TARGET/state/legacy-installations.json"
HOST="$(hostname -f 2>/dev/null||hostname)";openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 -subj "/CN=$HOST" -addext "subjectAltName=DNS:$HOST,IP:$IP" -keyout "$TARGET/state/tls/server.key" -out "$TARGET/state/tls/server.crt" >/dev/null 2>&1
[ -s "$TARGET/state/tls/server.crt" ]&&[ -s "$TARGET/state/tls/server.key" ];chown root:www-data "$TARGET/state/tls/"*;chmod 0640 "$TARGET/state/tls/server.key"
SLUG="wams-${UUID%%-*}";printf 'Listen %s\n' "$APP_PORT" > "/etc/apache2/conf-available/$SLUG-port.conf";sed -e "s|__PORT__|$APP_PORT|g" -e "s|__ROOT__|$TARGET|g" -e "s|__SLUG__|$SLUG|g" "$TARGET/resources/apache/site.conf.template" > "/etc/apache2/sites-available/$SLUG.conf"
a2enmod ssl rewrite >/dev/null;a2enconf "$SLUG-port" >/dev/null;a2ensite "$SLUG" >/dev/null;apache2ctl configtest;systemctl reload apache2;COMMITTED=1
cat > "/etc/systemd/system/$SLUG-update.service" <<UNIT
[Service]
Type=oneshot
ExecStart=$TARGET/update-worker.sh
UNIT
cat > "/etc/systemd/system/$SLUG-update.timer" <<UNIT
[Timer]
OnBootSec=2min
OnUnitActiveSec=1min
Unit=$SLUG-update.service
[Install]
WantedBy=timers.target
UNIT
systemctl daemon-reload;systemctl enable --now "$SLUG-update.timer" >/dev/null
cat > "/etc/systemd/system/$SLUG-repository-sync.service" <<UNIT
[Service]
Type=oneshot
User=www-data
Group=www-data
ExecStart=/usr/bin/php $TARGET/repository-sync-worker.php
UNIT
cat > "/etc/systemd/system/$SLUG-repository-sync.timer" <<UNIT
[Timer]
OnBootSec=2min
OnUnitActiveSec=1min
Unit=$SLUG-repository-sync.service
[Install]
WantedBy=timers.target
UNIT
systemctl daemon-reload;systemctl enable --now "$SLUG-repository-sync.timer" >/dev/null
curl -kfsS --retry 5 --retry-delay 1 "https://127.0.0.1:$APP_PORT/login" >/dev/null
echo "WAMS bereit: https://$IP:$APP_PORT/login"
