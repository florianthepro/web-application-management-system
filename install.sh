#!/usr/bin/env bash
set -Eeuo pipefail
REPOSITORY="";PORT=8099
while [ "$#" -gt 0 ];do case "$1" in --repository)REPOSITORY="${2:-}";shift 2;;--port)PORT="${2:-}";shift 2;;*)echo "Unbekannter Parameter: $1" >&2;exit 2;;esac;done
[ "$(id -u)" -eq 0 ]||{ echo "Bitte mit sudo ausführen." >&2;exit 1; }
[ -n "$REPOSITORY" ]||read -r -p "Git-Repository: " REPOSITORY
command -v apt-get >/dev/null||{ echo "Debian oder Ubuntu mit APT erforderlich." >&2;exit 1; }
BOOT="$(mktemp -d /var/tmp/wams-bootstrap.XXXXXX)";PHP_PID="";TOKEN="$(openssl rand -hex 24 2>/dev/null||date +%s%N|sha256sum|cut -d' ' -f1)"
cleanup(){ [ -n "$PHP_PID" ]&&kill "$PHP_PID" 2>/dev/null||true;rm -rf "$BOOT";};trap cleanup EXIT INT TERM
port_free(){ ! ss -ltnH|awk '{print $4}'|grep -Eq "(:|])$1$"; }
while ! port_free "$PORT";do PORT=$((PORT+1));done
BEFORE_APACHE=false;BEFORE_PHP=false;command -v apache2 >/dev/null&&BEFORE_APACHE=true;command -v php >/dev/null&&BEFORE_PHP=true
MISSING=();command -v git >/dev/null||MISSING+=(git);command -v php >/dev/null||MISSING+=(php-cli);command -v apache2 >/dev/null||MISSING+=(apache2 libapache2-mod-php);php -m 2>/dev/null|grep -qi '^pdo_sqlite$'||MISSING+=(php-sqlite3);php -m 2>/dev/null|grep -qi '^openssl$'||MISSING+=(php-common);command -v openssl >/dev/null||MISSING+=(openssl)
INSTALLED=();if [ "${#MISSING[@]}" -gt 0 ];then apt-get update;apt-get install -y --no-install-recommends "${MISSING[@]}";INSTALLED=("${MISSING[@]}");fi
GIT_TERMINAL_PROMPT=0 git clone --depth 1 "$REPOSITORY" "$BOOT/repo"
mkdir -p "$BOOT/repo/runtime"
UUID="$(cat /proc/sys/kernel/random/uuid)";TARGET="/opt/wams-$UUID";while [ -e "$TARGET" ];do UUID="$(cat /proc/sys/kernel/random/uuid)";TARGET="/opt/wams-$UUID";done
LEGACY_JSON="$(find /opt /var/www -maxdepth 4 -type f -name database.sqlite -path '*/state/*' -printf '%h\n' 2>/dev/null|sed 's#/state$##'|php -r '$a=[];while(($l=fgets(STDIN))!==false){$l=trim($l);if($l!=="")$a[]=["path"=>$l,"status"=>"legacy"]; }echo json_encode($a,JSON_UNESCAPED_SLASHES);')"
export PROBE="$BOOT/repo/runtime/system-probe.json" TOKEN PORT
php -r '$x=["installToken"=>getenv("TOKEN"),"finalPort"=>(int)getenv("PORT")];file_put_contents(getenv("PROBE"),json_encode($x,JSON_PRETTY_PRINT));'
php -S "0.0.0.0:$PORT" -t "$BOOT/repo" "$BOOT/repo/bootstrap.php" >"$BOOT/setup.log" 2>&1 & PHP_PID=$!
sleep 1;curl -fsS --max-time 5 "http://127.0.0.1:$PORT/" >/dev/null||{ cat "$BOOT/setup.log";exit 1; }
IP="$(hostname -I|awk '{print $1}')";echo;echo "WAMS Einrichtung: http://$IP:$PORT/";echo "Terminal geöffnet lassen."
REQ="$BOOT/repo/runtime/request.json";while [ ! -f "$REQ" ];do kill -0 "$PHP_PID" 2>/dev/null||{ cat "$BOOT/setup.log";exit 1;};sleep 2;done
readarray -t V < <(php -r '$x=json_decode(file_get_contents($argv[1]),true);foreach(["token","username","email","passwordHash"] as $k)echo base64_encode($x[$k]??"")."\n";' "$REQ")
dec(){ printf '%s' "$1"|base64 -d;};[ "$(dec "${V[0]}")" = "$TOKEN" ]||exit 1;USERNAME="$(dec "${V[1]}")";EMAIL="$(dec "${V[2]}")";HASH="$(dec "${V[3]}")"
kill "$PHP_PID" 2>/dev/null||true;wait "$PHP_PID" 2>/dev/null||true;PHP_PID="";sleep 1
mkdir "$TARGET";cp -a "$BOOT/repo/." "$TARGET/";rm -f "$TARGET/bootstrap.php" "$TARGET/runtime/request.json" "$TARGET/runtime/system-probe.json";mkdir -p "$TARGET/state/tls" "$TARGET/runtime" "$TARGET/backups" "$TARGET/exports";printf '%s' "$LEGACY_JSON" > "$TARGET/state/legacy-installations.json"
chown -R root:www-data "$TARGET";find "$TARGET" -type d -exec chmod 0750 {} +;find "$TARGET" -type f -exec chmod 0640 {} +
for d in state runtime backups exports;do chown -R www-data:www-data "$TARGET/$d";find "$TARGET/$d" -type d -exec chmod 0770 {} +;find "$TARGET/$d" -type f -exec chmod 0660 {} +;done
php -r 'require $argv[1]."/src/Core.php";$c=new Wams\Core();$c->migrate();$q=$c->db->prepare("INSERT INTO users(id,username,email,password_hash,is_global,active,created_at) VALUES(?,?,?,?,1,1,?)");$q->execute([$c->id(),$argv[2],$argv[3],$argv[4],gmdate("c")]);' "$TARGET" "$USERNAME" "$EMAIL" "$HASH"
chown www-data:www-data "$TARGET/state/database.sqlite"*;chmod 0660 "$TARGET/state/database.sqlite"*
HOST="$(hostname -f 2>/dev/null||hostname)";openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 -subj "/CN=$HOST" -addext "subjectAltName=DNS:$HOST,IP:$IP" -keyout "$TARGET/state/tls/server.key" -out "$TARGET/state/tls/server.crt" >/dev/null 2>&1;chown www-data:www-data "$TARGET/state/tls/"*;chmod 0640 "$TARGET/state/tls/server.key"
SLUG="wams-${UUID%%-*}";CONF="/etc/apache2/sites-available/$SLUG.conf";printf 'Listen %s\n' "$PORT" > "/etc/apache2/conf-available/$SLUG-port.conf"
sed -e "s|__PORT__|$PORT|g" -e "s|__ROOT__|$TARGET|g" -e "s|__SLUG__|$SLUG|g" "$TARGET/resources/apache/site.conf.template" > "$CONF";sed -i "/ServerName/a SSLEngine on\nSSLCertificateFile $TARGET/state/tls/server.crt\nSSLCertificateKeyFile $TARGET/state/tls/server.key" "$CONF"
a2enmod ssl rewrite >/dev/null;a2enconf "$SLUG-port" >/dev/null;a2ensite "$SLUG" >/dev/null;apache2ctl configtest;systemctl reload apache2
LEDGER="$TARGET/state/installation-ledger.tsv";printf 'timestamp\taction\ttarget\townership\n' > "$LEDGER";for p in "${INSTALLED[@]}";do printf '%s\tpackage.install\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$p" >> "$LEDGER";done;printf '%s\tpath.create\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$TARGET" >> "$LEDGER";chown www-data:www-data "$LEDGER";chmod 0660 "$LEDGER"
for _ in 1 2 3 4 5;do curl -kfsS --max-time 5 "https://127.0.0.1:$PORT/login" >/dev/null&&break;sleep 1;done
curl -kfsS --max-time 5 "https://127.0.0.1:$PORT/login" >/dev/null||{ echo "Start fehlgeschlagen: /var/log/apache2/$SLUG-error.log" >&2;exit 1; }
echo "WAMS bereit: https://$IP:$PORT/login";echo "Installations-ID: $UUID"
