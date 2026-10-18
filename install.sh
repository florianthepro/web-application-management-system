#!/usr/bin/env bash
set -Eeuo pipefail
REPOSITORY="";BOOT_PORT=8099
while [ "$#" -gt 0 ]; do case "$1" in --repository) REPOSITORY="${2:-}";shift 2;;--bootstrap-port) BOOT_PORT="${2:-}";shift 2;;*) echo "Unbekannter Parameter: $1" >&2;exit 2;;esac;done
[ "$(id -u)" -eq 0 ]||{ echo "Bitte mit sudo ausführen." >&2;exit 1; }
[ -n "$REPOSITORY" ]||read -r -p "Git-Repository: " REPOSITORY
command -v apt-get >/dev/null||{ echo "Nur Debian/Ubuntu mit apt wird unterstützt." >&2;exit 1; }
BOOT="$(mktemp -d /var/tmp/wams-bootstrap.XXXXXX)";TOKEN="$(openssl rand -hex 24 2>/dev/null||head -c 48 /dev/urandom|od -An -tx1|tr -d ' \n')";PHP_PID=""
cleanup(){ [ -n "$PHP_PID" ]&&kill "$PHP_PID" 2>/dev/null||true;rm -rf "$BOOT"; };trap cleanup EXIT INT TERM
pkg_installed(){ dpkg-query -W -f='${Status}' "$1" 2>/dev/null|grep -q 'ok installed'; }
BEFORE_APACHE=false;BEFORE_PHP=false;command -v apache2 >/dev/null&&BEFORE_APACHE=true;command -v php >/dev/null&&BEFORE_PHP=true
MISSING=();command -v git >/dev/null||MISSING+=(git);command -v php >/dev/null||MISSING+=(php-cli);command -v apache2 >/dev/null||MISSING+=(apache2 libapache2-mod-php);php -m 2>/dev/null|grep -qi '^pdo_sqlite$'||MISSING+=(php-sqlite3);php -m 2>/dev/null|grep -qi '^openssl$'||MISSING+=(php-common)
INSTALLED=();if [ "${#MISSING[@]}" -gt 0 ];then echo "Fehlende Komponenten: ${MISSING[*]}";apt-get update;apt-get install -y --no-install-recommends "${MISSING[@]}";INSTALLED=("${MISSING[@]}");else echo "Apache, PHP, Git und SQLite-Unterstützung sind vollständig vorhanden.";fi
GIT_TERMINAL_PROMPT=0 git clone --depth 1 "$REPOSITORY" "$BOOT/repo"
mkdir -p "$BOOT/repo/runtime"
APACHE_VERSION="$(apache2 -v 2>/dev/null|head -n1||true)";PHP_VERSION="$(php -r 'echo PHP_VERSION;' 2>/dev/null||true)"
DEFAULT_PATH="/opt/web-application-management-system";PATH_REQUIRED=false;SUGGESTED="$DEFAULT_PATH"
if [ -e "$DEFAULT_PATH" ];then PATH_REQUIRED=true;BASE="/opt";SUGGESTED="$BASE/web-application-management-system-2";i=3;while [ -e "$SUGGESTED" ];do SUGGESTED="$BASE/web-application-management-system-$i";i=$((i+1));done;fi
port_free(){ ! ss -ltnH|awk '{print $4}'|grep -Eq "(:|])$1$"; }
FREE80=false;FREE443=false;port_free 80&&FREE80=true;port_free 443&&FREE443=true
export PROBE_FILE="$BOOT/repo/runtime/system-probe.json" BEFORE_APACHE BEFORE_PHP APACHE_VERSION PHP_VERSION DEFAULT_PATH PATH_REQUIRED SUGGESTED FREE80 FREE443
export INSTALLED_LINES="$(printf '%s\n' "${INSTALLED[@]}")"
php -r '$installed=array_values(array_filter(preg_split("/\\R/",getenv("INSTALLED_LINES"))));$x=["apache"=>["installed"=>getenv("BEFORE_APACHE")==="true","version"=>getenv("APACHE_VERSION")],"php"=>["installed"=>getenv("BEFORE_PHP")==="true","version"=>getenv("PHP_VERSION")],"installedByWams"=>$installed,"defaultPath"=>getenv("DEFAULT_PATH"),"pathChoiceRequired"=>getenv("PATH_REQUIRED")==="true","suggestedPath"=>getenv("SUGGESTED"),"ports"=>["80"=>getenv("FREE80")==="true","443"=>getenv("FREE443")==="true"]];file_put_contents(getenv("PROBE_FILE"),json_encode($x,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));'
while ! port_free "$BOOT_PORT";do BOOT_PORT=$((BOOT_PORT+1));done
php -S "0.0.0.0:$BOOT_PORT" -t "$BOOT/repo" "$BOOT/repo/bootstrap.php" >"$BOOT/php-server.log" 2>&1 & PHP_PID=$!
sleep 1;kill -0 "$PHP_PID" 2>/dev/null||{ cat "$BOOT/php-server.log";exit 1; }
curl -fsS --max-time 5 "http://127.0.0.1:$BOOT_PORT/?token=$TOKEN" >/dev/null||{ echo "Bootstrap-Selbsttest fehlgeschlagen:";cat "$BOOT/php-server.log";exit 1; }
IP="$(hostname -I|awk '{print $1}')";echo;echo "System erkannt und protokolliert. Browserinstallation: http://$IP:$BOOT_PORT/?token=$TOKEN";echo "Terminal bitte geöffnet lassen."
REQ="$BOOT/repo/runtime/request.json";while [ ! -f "$REQ" ];do kill -0 "$PHP_PID" 2>/dev/null||{ cat "$BOOT/php-server.log";exit 1;};sleep 2;done
readarray -t V < <(php -r '$x=json_decode(file_get_contents($argv[1]),true);foreach(["token","path","portMode","username","email","passwordHash"] as $k)echo base64_encode($x[$k]??"")."\n";' "$REQ")
dec(){ printf '%s' "$1"|base64 -d; };RTOKEN="$(dec "${V[0]}")";TARGET="$(dec "${V[1]}")";PORT_MODE="$(dec "${V[2]}")";USER_NAME="$(dec "${V[3]}")";EMAIL="$(dec "${V[4]}")";HASH="$(dec "${V[5]}")"
[ "$RTOKEN" = "$TOKEN" ]||{ echo "Ungültiger Installationstoken." >&2;exit 1; };[[ "$TARGET" = /* ]]||exit 1;[ ! -e "$TARGET" ]||{ echo "Ziel existiert inzwischen." >&2;exit 1; };[ -d "$(dirname "$TARGET")" ]||exit 1
mkdir "$TARGET";cp -a "$BOOT/repo/." "$TARGET/";rm -f "$TARGET/bootstrap.php" "$TARGET/runtime/request.json";mkdir -p "$TARGET/state" "$TARGET/runtime" "$TARGET/backups" "$TARGET/exports"
chown -R root:www-data "$TARGET"
find "$TARGET" -type d -exec chmod 0750 {} +
find "$TARGET" -type f -exec chmod 0640 {} +
for writable in state runtime backups exports;do chown -R www-data:www-data "$TARGET/$writable";find "$TARGET/$writable" -type d -exec chmod 0770 {} +;find "$TARGET/$writable" -type f -exec chmod 0660 {} +;done
php -r 'require $argv[1]."/src/Core.php";$c=new Wams\Core();$c->migrate();$q=$c->db->prepare("INSERT INTO users VALUES(?,?,?,?,1,1,?)");$q->execute([$c->id(),$argv[2],$argv[3],$argv[4],gmdate("c")]);' "$TARGET" "$USER_NAME" "$EMAIL" "$HASH"
chown www-data:www-data "$TARGET/state/database.sqlite" "$TARGET/state/database.sqlite-wal" "$TARGET/state/database.sqlite-shm" 2>/dev/null||true
chmod 0660 "$TARGET/state/database.sqlite" "$TARGET/state/database.sqlite-wal" "$TARGET/state/database.sqlite-shm" 2>/dev/null||true
LEDGER="$TARGET/state/installation-ledger.tsv";printf 'timestamp\taction\ttarget\townership\n' > "$LEDGER";for p in "${INSTALLED[@]}";do printf '%s\tpackage.install\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$p" >> "$LEDGER";done;printf '%s\tpath.create\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$TARGET" >> "$LEDGER"
SLUG="wams-$(printf '%s' "$TARGET"|sha256sum|cut -c1-10)";CONF="/etc/apache2/sites-available/$SLUG.conf";SCHEME=http;SSL_LINES=""
if [ "$PORT_MODE" = 80 ];then PORT=80;elif [ "$PORT_MODE" = 443 ];then PORT=443;SCHEME=https;mkdir -p "$TARGET/state/tls";openssl req -x509 -newkey rsa:2048 -nodes -days 30 -subj "/CN=$(hostname -f 2>/dev/null||hostname)" -keyout "$TARGET/state/tls/bootstrap.key" -out "$TARGET/state/tls/bootstrap.crt" >/dev/null 2>&1;chmod 0640 "$TARGET/state/tls/bootstrap.key";a2enmod ssl >/dev/null;SSL_LINES="SSLEngine on
SSLCertificateFile $TARGET/state/tls/bootstrap.crt
SSLCertificateKeyFile $TARGET/state/tls/bootstrap.key";else PORT=8088;while ! port_free "$PORT";do PORT=$((PORT+1));done;fi
port_free "$PORT"||{ echo "Der gewählte Port ist inzwischen belegt." >&2;exit 1; }
printf 'Listen %s
' "$PORT" > "/etc/apache2/conf-available/$SLUG-port.conf"
sed -e "s|__PORT__|$PORT|g" -e "s|__ROOT__|$TARGET|g" -e "s|__SLUG__|$SLUG|g" "$TARGET/resources/apache/site.conf.template" > "$CONF"
if [ -n "$SSL_LINES" ];then sed -i "/ServerName/a $SSL_LINES" "$CONF";fi
a2enmod rewrite >/dev/null;a2enconf "$SLUG-port" >/dev/null;a2ensite "$SLUG" >/dev/null;apache2ctl configtest;systemctl reload apache2
printf '%s\tfile.create\t%s\tcreated-by-wams\n' "$(date -u +%FT%TZ)" "$CONF" >> "$LEDGER"
FINAL="$SCHEME://127.0.0.1:$PORT/?p=login";CURL_TLS="";[ "$SCHEME" = https ]&&CURL_TLS="-k";for _ in 1 2 3 4 5;do curl $CURL_TLS -fsS --max-time 5 "$FINAL" >/dev/null&&break;sleep 1;done;curl $CURL_TLS -fsS --max-time 5 "$FINAL" >/dev/null||{ echo "Finaler HTTP-Test fehlgeschlagen. Log: /var/log/apache2/$SLUG-error.log" >&2;exit 1; }
echo "Installation abgeschlossen: $SCHEME://$IP:$PORT/?p=login";echo "Ziel: $TARGET";echo "Vorhandener Apache/PHP-Zustand wurde als preexisting erfasst; nur fehlende Komponenten wurden installiert."
