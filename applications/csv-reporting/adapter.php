<?php
declare(strict_types=1);
if(empty($wams['authorized'])){http_response_code(403);exit;}
$view=(string)($_GET['view']??'index');$allowed=['index','map'];if(!empty($wams['canManage']))$allowed=array_merge($allowed,['rules','check']);if(!in_array($view,$allowed,true)){http_response_code(403);exit;}
$dataDir=(string)($wams['dataDir']??'');$runtime=$dataDir.'/runtime';if(!is_dir($runtime))mkdir($runtime,0770,true);
$upstream=__DIR__.'/upstream';foreach(['index.php','map.php','rules.php','check.php','auto-download.php']as$file)if(!is_file($runtime.'/'.$file)&&is_file($upstream.'/'.$file))copy($upstream.'/'.$file,$runtime.'/'.$file);
if(!is_file($runtime.'/rules.json'))file_put_contents($runtime.'/rules.json','[]');if(!is_file($runtime.'/pw.txt'))file_put_contents($runtime.'/pw.txt',bin2hex(random_bytes(24)));$target=$runtime.'/'.$view.'.php';if(!is_file($target)){http_response_code(503);echo 'CSV Reporting Upstream ist nicht installiert.';exit;}
$config=is_array($wams['config']??null)?$wams['config']:[];$url=trim((string)($config['remoteUrl']??''));if(!filter_var($url,FILTER_VALIDATE_URL)){http_response_code(502);echo 'CSV-URL ist ungültig.';exit;}
$ctx=stream_context_create(['http'=>['timeout'=>max(2,min(60,(int)($config['timeoutSeconds']??20))),'ignore_errors'=>true,'header'=>"Accept: text/csv,text/plain\r\nUser-Agent: WAMS-CSV-Upstream/1.0\r\n"]]);$csv=@file_get_contents($url,false,$ctx);if($csv===false||$csv===''){http_response_code(502);echo 'CSV-Quelle ist nicht erreichbar oder leer.';exit;}
$csvFile=$runtime.'/csv.csv';file_put_contents($csvFile,$csv,LOCK_EX);$cwd=getcwd();chdir($runtime);require __DIR__.'/overlay/launcher-button.php';try{require $target;}finally{if($cwd!==false)chdir($cwd);@unlink($csvFile);}
