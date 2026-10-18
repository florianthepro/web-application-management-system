#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")"&&pwd)";DB="$ROOT/state/database.sqlite";[ -f "$DB" ]||exit 0
while IFS=$'\t' read -r ID;do
 php -r '$d=new PDO("sqlite:".$argv[1]);$d->prepare("UPDATE update_jobs SET status=? WHERE id=?")->execute(["running",$argv[2]]);' "$DB" "$ID"
 TMP="$(mktemp -d)";STATUS=done;MESSAGE=""
 if git clone --depth 1 https://github.com/florianthepro/web-application-management-system "$TMP/repo" >"$TMP/log" 2>&1;then
  while IFS= read -r -d '' f;do php -l "$f" >/dev/null;done < <(find "$TMP/repo" -name '*.php' -print0)
  rsync -a --delete --exclude state --exclude runtime --exclude backups --exclude exports "$TMP/repo/" "$ROOT/"
  chown -R root:www-data "$ROOT";for d in state runtime backups exports;do chown -R www-data:www-data "$ROOT/$d";done
 else STATUS=failed;MESSAGE="$(tail -c 1000 "$TMP/log")";fi
 rm -rf "$TMP";php -r '$d=new PDO("sqlite:".$argv[1]);$d->prepare("UPDATE update_jobs SET status=?,finished_at=?,message=? WHERE id=?")->execute([$argv[2],gmdate("c"),$argv[3],$argv[4]]);' "$DB" "$STATUS" "$MESSAGE" "$ID"
done < <(php -r '$d=new PDO("sqlite:".$argv[1]);$d->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_NUM);foreach($d->query("SELECT id FROM update_jobs WHERE status=\"queued\"")as$r)echo $r[0]."\n";' "$DB")
