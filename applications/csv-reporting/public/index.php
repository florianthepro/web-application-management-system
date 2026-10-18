<?php
declare(strict_types=1);
if(empty($wams['authorized'])){http_response_code(403);exit;}
$config=is_array($wams['config']??null)?$wams['config']:[];
function cr_h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function cr_fetch(array $config):string{
 $url=trim((string)($config['remoteUrl']??''));
 if(!filter_var($url,FILTER_VALIDATE_URL))throw new RuntimeException('Die konfigurierte CSV-URL ist ungültig.');
 $scheme=(string)parse_url($url,PHP_URL_SCHEME);
 if(!in_array($scheme,['http','https'],true))throw new RuntimeException('Nur HTTP- und HTTPS-Quellen sind zulässig.');
 $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>max(2,min(60,(int)($config['timeoutSeconds']??20))),'ignore_errors'=>true,'header'=>"Accept: text/csv,text/plain;q=0.9,*/*;q=0.1\r\nUser-Agent: WAMS-CSV-Reporting/2.2\r\n"]]);
 $data=@file_get_contents($url,false,$ctx);
 if($data===false)throw new RuntimeException('Die CSV-Quelle ist nicht erreichbar. Falls DNS blockiert wird, kann eine zulässige IP-Adresse verwendet werden.');
 $status=0;foreach($http_response_header??[]as$line)if(preg_match('~^HTTP/\\S+\\s+(\\d{3})~',$line,$m))$status=(int)$m[1];
 if($status&&($status<200||$status>=300))throw new RuntimeException('Die CSV-Quelle antwortet mit HTTP '.$status.'.');
 if(trim($data)==='')throw new RuntimeException('Die CSV-Quelle liefert keine Daten.');
 return$data;
}
$error='';$rows=[];$headers=[];$bytes=0;
try{
 $raw=cr_fetch($config);$bytes=strlen($raw);$delimiter=(string)($config['delimiter']??',');if($delimiter==='\\t')$delimiter="\t";
 $stream=fopen('php://temp','r+');fwrite($stream,$raw);rewind($stream);
 while(($row=fgetcsv($stream,0,$delimiter))!==false){if(count($row)===1&&$row[0]===null)continue;$rows[]=array_map(fn($v)=>trim((string)$v),$row);}fclose($stream);
 if(($config['hasHeader']??true)&&$rows)$headers=array_shift($rows);
 $width=0;foreach($rows as$row)$width=max($width,count($row));if(!$headers)for($i=0;$i<$width;$i++)$headers[]='Spalte '.($i+1);
 foreach($rows as&$row)$row=array_pad($row,count($headers),'');unset($row);
 $show=array_values(array_filter(array_map('strval',is_array($config['showColumns']??null)?$config['showColumns']:[])));
 if($show){$indexes=[];foreach($show as$name){$index=array_search($name,$headers,true);if($index!==false)$indexes[]=$index;}if($indexes){$headers=array_map(fn($i)=>$headers[$i],$indexes);$rows=array_map(fn($row)=>array_map(fn($i)=>$row[$i]??'',$indexes),$rows);}}
}catch(Throwable $e){$error=$e->getMessage();}
$renames=is_array($config['columnRenames']??null)?$config['columnRenames']:[];$links=is_array($config['columnLinks']??null)?$config['columnLinks']:[];$display=array_map(fn($h)=>(string)($renames[$h]??$h),$headers);$refresh=max(0,(int)($config['refreshSeconds']??0));
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=cr_h($config['title']??'CSV Reporting')?></title><?php if($refresh>0):?><meta http-equiv="refresh" content="<?=$refresh?>"><?php endif?><link rel="stylesheet" href="<?=cr_h($wams['styleUrl'])?>"></head><body><main class="cr-page"><header class="cr-header"><div><h1><?=cr_h($config['heading']??'CSV Reporting')?></h1><p><?=count($rows)?> Zeilen · <?=number_format($bytes/1024,1,',','.')?> KiB · Live-Daten</p></div><?php if(!empty($wams['canManage'])):?><a class="cr-button" href="<?=cr_h($wams['manageUrl'])?>">Einstellungen</a><?php endif?></header><?php if($error):?><section class="cr-error"><strong>Daten konnten nicht geladen werden.</strong><span><?=cr_h($error)?></span></section><?php else:?><section class="cr-panel"><div class="cr-toolbar"><input id="crSearch" type="search" placeholder="Suchen"><select id="crPageSize" aria-label="Zeilen pro Seite"><option value="25">25 Zeilen</option><option value="50" selected>50 Zeilen</option><option value="100">100 Zeilen</option><option value="250">250 Zeilen</option></select><button id="crReset" type="button">Zurücksetzen</button><span id="crCount"></span></div><div id="crFilters" class="cr-filters"><?php foreach($display as$i=>$h):?><label><span><?=cr_h($h)?></span><input data-column="<?=$i?>" type="search" placeholder="Filtern"></label><?php endforeach?></div><div class="cr-table-wrap"><table id="crTable"><thead><tr><?php foreach($display as$i=>$h):?><th data-sort="<?=$i?>"><button type="button"><?=cr_h($h)?> <span>↕</span></button></th><?php endforeach?></tr></thead><tbody><?php foreach($rows as$row):?><tr><?php foreach($row as$i=>$value):$original=$headers[$i]??'';?><td><?php if(isset($links[$original])):$href=str_replace('{value}',rawurlencode((string)$value),(string)$links[$original]);?><a href="<?=cr_h($href)?>" target="_blank" rel="noopener noreferrer"><?=cr_h($value)?></a><?php else:?><?=cr_h($value)?><?php endif?></td><?php endforeach?></tr><?php endforeach?></tbody></table></div><footer class="cr-footer"><button id="crPrev" type="button">Zurück</button><span id="crPage"></span><button id="crNext" type="button">Weiter</button></footer></section><?php endif?></main><script src="<?=cr_h($wams['scriptUrl'])?>"></script></body></html>
