<?php
declare(strict_types=1);
$root=dirname(__DIR__);$errors=[];
$required=['install.sh','uninstall.sh','update-worker.sh','public/index.php','src/Core.php','migrations/001.sql','migrations/002.sql','migrations/003.sql','applications/csv-reporting/app.json','applications/csv-reporting/app-config.schema.json','applications/csv-reporting/public/index.php','applications/example-application/app.json'];
foreach($required as$file)if(!is_file($root.'/'.$file))$errors[]='Fehlt: '.$file;
$example=json_decode((string)file_get_contents($root.'/applications/example-application/app.json'),true);
foreach(['discoverable','installable','downloadable']as$key)if(($example['catalog'][$key]??null)!==false)$errors[]='Example Application muss '.$key.'=false verwenden.';
$csv=json_decode((string)file_get_contents($root.'/applications/csv-reporting/app.json'),true);
if(($csv['remoteData']['persistResponse']??true)!==false)$errors[]='CSV Reporting darf Remote-Antworten nicht speichern.';
$schema=json_decode((string)file_get_contents($root.'/applications/csv-reporting/app-config.schema.json'),true);
foreach(['remoteUrl','title','heading','showColumns','columnRenames','columnLinks','filters','rules']as$key)if(!isset($schema['properties'][$key]))$errors[]='AppConfig-Feld fehlt: '.$key;
if($errors){fwrite(STDERR,implode("\n",$errors)."\n");exit(1);}echo "Repository-Vertrag OK\n";
