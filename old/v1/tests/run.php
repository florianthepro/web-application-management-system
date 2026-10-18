<?php
declare(strict_types=1);
$root=dirname(__DIR__);$errors=[];
foreach(['README.md','install.sh','public/index.php','src/Core.php','migrations/001.sql','applications/example-application/app.json'] as $f)if(!is_file($root.'/'.$f))$errors[]="missing $f";
$app=json_decode((string)file_get_contents($root.'/applications/example-application/app.json'),true);
foreach(['discoverable','installable','downloadable'] as $k)if(($app['catalog'][$k]??null)!==false)$errors[]="example catalog.$k must be false";
foreach(['template/public/.gitkeep'=>'index.php','template/assets/.gitkeep'=>'style.css'] as $f=>$needle)if(!str_contains((string)file_get_contents($root.'/applications/example-application/'.$f),$needle))$errors[]="$f lacks $needle";
if($errors){fwrite(STDERR,implode("\n",$errors)."\n");exit(1);}echo "OK repository contract\n";
