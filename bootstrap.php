<?php
declare(strict_types=1);
$root=__DIR__;
$probe=json_decode((string)@file_get_contents($root.'/runtime/system-probe.json'),true)?:[];
$error='';$submitted=false;
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
 if($username==='')$error='Benutzername fehlt.';
 elseif(strlen($password)<12)$error='Das Passwort muss mindestens 12 Zeichen lang sein.';
 else{
  $request=['token'=>(string)($probe['installToken']??''),'username'=>$username,'email'=>$username.'@local.invalid','passwordHash'=>password_hash($password,PASSWORD_ARGON2ID),'createdAt'=>gmdate('c')];
  file_put_contents($root.'/runtime/request.json',json_encode($request,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);$submitted=true;
 }
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WAMS Einrichtung</title><style>:root{color-scheme:dark;--bg:#080c11;--panel:#111923;--line:#293747;--text:#e7edf4;--muted:#8c9bad;--accent:#56a5ff;--bad:#ff6677}*{box-sizing:border-box}html,body{margin:0;min-height:100%;font:13px system-ui;background:var(--bg);color:var(--text)}main{min-height:100vh;display:grid;place-items:center;padding:20px}.box{width:380px;max-width:100%;background:var(--panel);border:1px solid var(--line);border-radius:5px;padding:24px}h1{text-align:center;font-size:20px;margin:0 0 4px}.sub{text-align:center;color:var(--muted);margin:0 0 22px}label{display:block;margin:12px 0 5px;font-weight:600}input{width:100%;background:#0c1219;border:1px solid var(--line);color:var(--text);padding:10px;border-radius:4px}button{width:100%;margin-top:16px;border:0;border-radius:4px;background:var(--accent);color:#07101a;padding:10px;font-weight:700;cursor:pointer}.error{border-left:3px solid var(--bad);background:#2a1419;padding:10px}.spinner{width:28px;height:28px;border:3px solid #293747;border-top-color:var(--accent);border-radius:50%;animation:s 1s linear infinite;margin:20px auto}@keyframes s{to{transform:rotate(360deg)}}</style></head><body><main><section class="box"><h1>WAMS</h1><p class="sub">Ersten globalen Administrator anlegen</p><?php if($error):?><div class="error"><?=h($error)?></div><?php endif?><?php if($submitted):?><div class="spinner"></div><p style="text-align:center">WAMS wird eingerichtet und anschließend neu gestartet.</p><script>const target='https://'+location.hostname+':<?=h($probe['finalPort']??8099)?>/login';setTimeout(()=>location.replace(target),5000);</script><?php else:?><form method="post"><label>Benutzername</label><input name="username" autocomplete="username" required autofocus><label>Passwort</label><input type="password" name="password" minlength="12" autocomplete="new-password" required><button>Einrichtung abschließen</button></form><?php endif?></section></main></body></html>
