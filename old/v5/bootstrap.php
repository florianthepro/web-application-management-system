<?php
declare(strict_types=1);
$root=__DIR__;
$probe=json_decode((string)@file_get_contents($root.'/runtime/system-probe.json'),true)?:[];
$error='';
$done=is_file($root.'/runtime/request.json');
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function validatePath(string $path,bool $choiceRequired,string $defaultPath):array{
 if(!$choiceRequired&&$path!==$defaultPath)return[false,'Der Installationspfad wurde unerwartet verändert.'];
 if($path===''||$path[0]!=='/')return[false,'Der Pfad muss absolut sein.'];
 if(file_exists($path)||is_link($path))return[false,'Der Zielordner muss frei sein und darf noch nicht existieren.'];
 $parent=dirname($path);
 if(!is_dir($parent)||!is_writable($parent))return[false,'Der vorhandene Elternordner ist nicht beschreibbar.'];
 if(in_array($path,['/','/etc','/usr','/var','/var/www','/var/www/html'],true))return[false,'Dieser System- oder DocumentRoot-Pfad ist nicht zulässig.'];
 return[true,'OK'];
}
if($_SERVER['REQUEST_METHOD']==='POST'&&!$done){
 $choiceRequired=(bool)($probe['pathChoiceRequired']??false);
 $defaultPath=(string)($probe['defaultPath']??'/opt/web-application-management-system');
 $path=rtrim((string)($_POST['path']??$defaultPath),'/');
 [$ok,$message]=validatePath($path,$choiceRequired,$defaultPath);
 $portMode=(string)($_POST['portMode']??'custom');
 $allowed=['custom'];
 if(!empty($probe['ports']['80']))$allowed[]='80';
 if(!empty($probe['ports']['443']))$allowed[]='443';
 if(!$ok)$error=$message;
 elseif(!in_array($portMode,$allowed,true))$error='Der gewählte Port ist nicht verfügbar.';
 elseif(strlen((string)($_POST['password']??''))<12)$error='Das Passwort muss mindestens 12 Zeichen besitzen.';
 else{
  $request=['token'=>(string)($_POST['token']??''),'path'=>$path,'portMode'=>$portMode,'username'=>trim((string)$_POST['username']),'email'=>trim((string)$_POST['email']),'passwordHash'=>password_hash((string)$_POST['password'],PASSWORD_ARGON2ID),'createdAt'=>gmdate('c')];
  file_put_contents($root.'/runtime/request.json',json_encode($request,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
  $done=true;
 }
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WAMS Installation</title><style>body{font:15px system-ui;background:#f3f5f7;color:#15202b;margin:0}.w{max-width:900px;margin:40px auto;padding:20px}.c{background:#fff;border:1px solid #dce3e8;border-radius:14px;padding:22px;margin:16px 0}input,select,button{width:100%;padding:11px;margin:6px 0 14px;box-sizing:border-box}button{background:#1769aa;color:#fff;border:0;border-radius:8px}.ok{color:#18794e}.bad{background:#ffe8e5;padding:12px}.g{display:grid;grid-template-columns:1fr 1fr;gap:12px}code{word-break:break-all}.note{background:#eaf4ff;padding:12px;border-radius:9px}</style></head><body><main class="w"><h1>WAMS installieren</h1>
<?php if($done):?><section class="c"><h2 class="ok">Installationsauftrag angenommen</h2><p>Der Terminalprozess richtet WAMS jetzt fertig ein, prüft Apache und testet die endgültige URL. Das Terminal beendet sich danach automatisch.</p></section>
<?php else:?><?php if($error):?><div class="bad"><?=h($error)?></div><?php endif?>
<section class="c"><h2>Erkannter Systemzustand</h2><div class="g"><div><strong>Apache</strong><p><?=h(($probe['apache']['installed']??false)?'vorhanden':'wurde bereitgestellt')?>, <?=h($probe['apache']['version']??'-')?></p></div><div><strong>PHP</strong><p><?=h(($probe['php']['installed']??false)?'vorhanden':'wurde bereitgestellt')?>, <?=h($probe['php']['version']??'-')?></p></div></div><p><strong>Von WAMS ergänzte Komponenten:</strong> <?=h(implode(', ',$probe['installedByWams']??[])?:'keine')?></p></section>
<section class="c"><h2>Installation</h2><form method="post"><input type="hidden" name="token" value="<?=h($_GET['token']??'')?>">
<?php if(!empty($probe['pathChoiceRequired'])):?><label>Installationspfad</label><input name="path" value="<?=h($probe['suggestedPath']??'')?>" required><p class="note">Der Standardpfad ist bereits belegt. Wähle einen anderen absoluten Pfad, dessen Zielordner noch nicht existiert.</p><?php else:?><input type="hidden" name="path" value="<?=h($probe['defaultPath']??'/opt/web-application-management-system')?>"><p class="note">Installationspfad: <code><?=h($probe['defaultPath']??'/opt/web-application-management-system')?></code>. Die Pfadauswahl wird nur angezeigt, wenn dieser Standardpfad nicht frei ist.</p><?php endif?>
<label>Web-Port</label><select name="portMode"><option value="custom">Eigener freier WAMS-Port, empfohlen</option><?php if(!empty($probe['ports']['80'])):?><option value="80">Port 80, derzeit frei</option><?php endif?><?php if(!empty($probe['ports']['443'])):?><option value="443">Port 443, derzeit frei, lokales Startzertifikat</option><?php endif?></select><p class="note">Standardmäßig verwendet WAMS einen freien eigenen Port ab 8088 und bleibt damit von bestehenden Websites unabhängig. 80 oder 443 werden nur angeboten, wenn sie frei sind.</p>
<label>Benutzername des ersten globalen Administrators</label><input name="username" required><label>E-Mail-Adresse</label><input type="email" name="email" required><label>Passwort</label><input type="password" name="password" minlength="12" required><button>Installieren</button></form></section><?php endif?></main></body></html>
