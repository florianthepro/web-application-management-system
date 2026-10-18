<?php
declare(strict_types=1);
require __DIR__.'/src/Core.php';
use Wams\Core;
$c=new Core();$job=$c->db->query("SELECT * FROM repository_sync_jobs WHERE status='queued' ORDER BY created_at LIMIT 1")->fetch();if(!$job)exit(0);
$c->db->prepare('UPDATE repository_sync_jobs SET status=? WHERE id=?')->execute(['running',$job['id']]);$tmp=sys_get_temp_dir().'/wams-sync-'.$c->id();
try{
 $repo=trim((string)$c->setting('repo.url',''));$branch=trim((string)$c->setting('repo.branch','main'));$mode=(string)$c->setting('repo.mode','config');$token=$c->decryptSecret((string)$c->setting('repo.token',''));
 if(!preg_match('~^https://([^/]+)/(.+?)(?:\.git)?$~',$repo,$m))throw new RuntimeException('Repository-URL muss eine HTTPS-Git-URL sein.');if($token==='')throw new RuntimeException('Repository-Schlüssel fehlt.');
 $auth='https://'.$m[1].'/'.$m[2].'.git';$askpass=$tmp.'-askpass.sh';file_put_contents($askpass,"#!/bin/sh\ncase \"$1\" in *Username*) echo x-access-token;; *) cat \"$WAMS_TOKEN_FILE\";; esac\n");chmod($askpass,0700);$tokenFile=$tmp.'-token';file_put_contents($tokenFile,$token);chmod($tokenFile,0600);putenv('GIT_ASKPASS='.$askpass);putenv('GIT_TERMINAL_PROMPT=0');putenv('WAMS_TOKEN_FILE='.$tokenFile);$run=function(array$args,?string$cwd=null):string{$cmd=array_map('escapeshellarg',$args);$prefix=$cwd?'cd '.escapeshellarg($cwd).' && ':'';$out=[];$code=0;exec($prefix.implode(' ',$cmd).' 2>&1',$out,$code);if($code!==0)throw new RuntimeException(implode("\n",$out));return implode("\n",$out);};
 mkdir($tmp,0700,true);try{$run(['git','clone','--depth','1','--branch',$branch,$auth,$tmp]);}catch(Throwable){$run(['git','init',$tmp]);$run(['git','-C',$tmp,'checkout','-b',$branch]);$run(['git','-C',$tmp,'remote','add','origin',$auth]);}
 $target=$tmp.'/wams';if(!is_dir($target))mkdir($target,0700,true);$copy=function(string$from,string$to)use(&$copy){if(!is_dir($to))mkdir($to,0700,true);foreach(scandir($from)?:[]as$n){if($n==='.'||$n==='..')continue;$a=$from.'/'.$n;$b=$to.'/'.$n;if(is_dir($a))$copy($a,$b);else copy($a,$b);}};
 if(in_array($mode,['config','both'],true)){if(is_dir($target.'/config'))$run(['rm','-rf',$target.'/config']);mkdir($target.'/config',0700,true);foreach(glob(__DIR__.'/state/instances/*/config.json')?:[]as$f){$id=basename(dirname($f));mkdir($target.'/config/'.$id,0700,true);copy($f,$target.'/config/'.$id.'/config.json');}}
 if(in_array($mode,['appdata','both'],true)){if(is_dir($target.'/appdata'))$run(['rm','-rf',$target.'/appdata']);mkdir($target.'/appdata',0700,true);foreach(glob(__DIR__.'/state/instances/*/data')?:[]as$d)$copy($d,$target.'/appdata/'.basename(dirname($d)));}
 file_put_contents($target.'/manifest.json',json_encode(['version'=>trim((string)file_get_contents(__DIR__.'/VERSION')),'mode'=>$mode,'generatedAt'=>gmdate('c')],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 $run(['git','-C',$tmp,'config','user.name','WAMS']);$run(['git','-C',$tmp,'config','user.email','wams@localhost']);$run(['git','-C',$tmp,'add','wams']);$status=$run(['git','-C',$tmp,'status','--porcelain']);if($status!==''){$run(['git','-C',$tmp,'commit','-m','WAMS sync '.gmdate('c')]);$run(['git','-C',$tmp,'push','-u','origin',$branch]);}
 $c->db->prepare('UPDATE repository_sync_jobs SET status=?,finished_at=?,message=? WHERE id=?')->execute(['done',gmdate('c'),'Synchronisiert',$job['id']]);
}catch(Throwable$e){$c->db->prepare('UPDATE repository_sync_jobs SET status=?,finished_at=?,message=? WHERE id=?')->execute(['failed',gmdate('c'),substr($e->getMessage(),0,2000),$job['id']]);}
@unlink($tmp.'-askpass.sh');@unlink($tmp.'-token');if(is_dir($tmp)){exec('rm -rf '.escapeshellarg($tmp));}
