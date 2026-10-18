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
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WAMS Einrichtung</title><style>:root{--bg:#070d18;--panel:#0f1724;--line:#26354a;--text:#e6eef8;--muted:#91a4bd;--accent:#0ea5a4}*{box-sizing:border-box}html,body{margin:0;min-height:100%;font:14px Inter,system-ui;background:var(--bg);color:var(--text)}main{min-height:100vh;display:grid;place-items:center;padding:20px}.box{width:min(410px,100%);background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:28px;box-shadow:0 18px 48px #0008}h1{text-align:center;margin:0 0 8px}.sub{text-align:center;color:var(--muted);margin-bottom:24px}label{display:block;margin:13px 0 6px;font-weight:650}input{width:100%;background:#09111e;border:1px solid var(--line);color:var(--text);padding:12px;border-radius:10px}button{width:100%;margin-top:18px;border:0;border-radius:10px;background:var(--accent);color:#fff;padding:12px;font-weight:750;cursor:pointer}.error{background:#3b1117;border:1px solid #7f1d1d;color:#fecaca;padding:12px;border-radius:10px}.spinner{width:34px;height:34px;border:3px solid #ffffff22;border-top-color:var(--accent);border-radius:50%;animation:s 1s linear infinite;margin:22px auto}@keyframes s{to{transform:rotate(360deg)}}</style></head><body><main><section class="box"><h1>WAMS</h1><p class="sub">Ersten globalen Administrator anlegen</p><?php if($error):?><div class="error"><?=h($error)?></div><?php endif?><?php if($submitted):?><div class="spinner"></div><p style="text-align:center">WAMS wird eingerichtet und anschließend neu gestartet.</p><script>const target='https://'+location.hostname+':<?=h($probe['finalPort']??8099)?>/login';setTimeout(()=>location.replace(target),5000);</script><?php else:?><form method="post"><label>Benutzername</label><input name="username" autocomplete="username" required autofocus><label>Passwort</label><input type="password" name="password" minlength="12" autocomplete="new-password" required><button>Einrichtung abschließen</button></form><?php endif?></section></main></body></html>
