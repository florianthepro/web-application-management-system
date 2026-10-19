#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
DB="$ROOT/state/database.sqlite"
[ -f "$DB" ] || exit 0
PHP_BIN="$(command -v php)"
while IFS=$'\t' read -r ID TYPE TARGET ACTION;do
  "$PHP_BIN" -r '$db=new PDO("sqlite:".$argv[1]);$q=$db->prepare("UPDATE update_jobs SET status=? WHERE id=?");$q->execute(["running",$argv[2]]);' "$DB" "$ID"
  STATUS=done;MESSAGE=""
  TMP="$(mktemp -d)"
  if [ "$TYPE" = system ];then
    REPO="$($PHP_BIN -r '$db=new PDO("sqlite:".$argv[1]);echo $db->query("SELECT repository FROM update_sources WHERE target_type=\"system\" AND target_id=\"wams\"")->fetchColumn();' "$DB")"
    if ! git clone --depth 1 "$REPO" "$TMP/repo" >"$TMP/log" 2>&1;then STATUS=failed;MESSAGE="$(tail -c 1000 "$TMP/log")";else
      rsync -a --delete --exclude state --exclude runtime --exclude backups --exclude exports "$TMP/repo/" "$ROOT/"
      chown -R root:www-data "$ROOT";for d in state runtime backups exports;do chown -R www-data:www-data "$ROOT/$d";done
    fi
  elif [ "$TYPE" = app ];then
    STATUS=failed;MESSAGE="Für diese integrierte App ist keine externe Updatequelle hinterlegt."
  fi
  rm -rf "$TMP"
  "$PHP_BIN" -r '$db=new PDO("sqlite:".$argv[1]);$q=$db->prepare("UPDATE update_jobs SET status=?,finished_at=?,message=? WHERE id=?");$q->execute([$argv[2],gmdate("c"),$argv[3],$argv[4]]);' "$DB" "$STATUS" "$MESSAGE" "$ID"
done < <("$PHP_BIN" -r '$db=new PDO("sqlite:".$argv[1]);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_NUM);foreach($db->query("SELECT id,target_type,target_id,action FROM update_jobs WHERE status=\"queued\" ORDER BY requested_at") as $r)echo implode("\t",$r)."\n";' "$DB")
