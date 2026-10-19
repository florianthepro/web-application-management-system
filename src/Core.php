<?php
declare(strict_types=1);
namespace Wams;
use PDO;use RuntimeException;
final class Core{
 public array $cfg;public PDO $db;
 public function __construct(){
  $this->cfg=require dirname(__DIR__).'/config/defaults.php';
  foreach([$this->cfg['state'],$this->cfg['instances']]as$d)if(!is_dir($d)&&!mkdir($d,0770,true))throw new RuntimeException('Verzeichnis kann nicht angelegt werden: '.$d);
  $this->db=new PDO('sqlite:'.$this->cfg['db'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $this->db->exec('PRAGMA foreign_keys=ON;PRAGMA journal_mode=WAL;PRAGMA busy_timeout=5000');
  foreach(glob(dirname(__DIR__).'/migrations/*.sql')?:[]as$f)$this->db->exec((string)file_get_contents($f));
 }
 public function id():string{return bin2hex(random_bytes(16));}
 public function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return(string)$_SESSION['csrf'];}
 public function checkCsrf():void{if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Ungültige Anfrage.');}
 public function setting(string$key,string$default=''):string{$q=$this->db->prepare('SELECT value FROM settings WHERE key=?');$q->execute([$key]);$v=$q->fetchColumn();return$v===false?$default:(string)$v;}
 public function setSetting(string$key,string$value):void{$this->db->prepare('INSERT INTO settings VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute([$key,$value]);}
 public function user():?array{if(empty($_SESSION['uid']))return null;$q=$this->db->prepare('SELECT * FROM users WHERE id=? AND active=1');$q->execute([$_SESSION['uid']]);return$q->fetch()?:null;}
 public function requireUser():array{$u=$this->user();if(!$u){header('Location:/login');exit;}return$u;}
 public function login(string$name,string$password):bool{$q=$this->db->prepare('SELECT * FROM users WHERE username=? AND active=1');$q->execute([$name]);$u=$q->fetch();if(!$u||!password_verify($password,$u['password_hash']))return false;session_regenerate_id(true);$_SESSION['uid']=$u['id'];return true;}
 public function canUse(array$u,string$app):bool{if($u['is_global_admin'])return true;$q=$this->db->prepare('SELECT 1 FROM user_apps WHERE user_id=? AND installed_app_id=?');$q->execute([$u['id'],$app]);return(bool)$q->fetchColumn();}
 public function canAdmin(array$u,string$app):bool{if($u['is_global_admin'])return true;$q=$this->db->prepare('SELECT is_admin FROM user_apps WHERE user_id=? AND installed_app_id=?');$q->execute([$u['id'],$app]);return(bool)$q->fetchColumn();}
 public function installed(string$id):array{$q=$this->db->prepare('SELECT * FROM installed_apps WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)throw new RuntimeException('App nicht gefunden.');return$a;}
 public function catalog():array{$f=$this->cfg['appRepo'].'/catalog.json';if(!is_file($f))return[];$j=json_decode((string)file_get_contents($f),true);return is_array($j['apps']??null)?$j['apps']:[];}
 public function refreshRepo():void{$url=$this->setting('app_repository');if(!preg_match('~^https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\.git)?$~',$url))throw new RuntimeException('Ungültiges App-Repository.');$tmp=$this->cfg['state'].'/app-repository-new';exec('rm -rf '.escapeshellarg($tmp));$out=[];$code=0;exec('git clone --depth 1 '.escapeshellarg($url).' '.escapeshellarg($tmp).' 2>&1',$out,$code);if($code!==0)throw new RuntimeException('App-Repository konnte nicht geladen werden: '.implode("\n",$out));$catalog=$tmp.'/catalog.json';$j=json_decode((string)@file_get_contents($catalog),true);if(!is_array($j['apps']??null))throw new RuntimeException('catalog.json ist ungültig.');foreach($j['apps']as$a){if(!isset($a['id'],$a['name'],$a['directory'])||!preg_match('/^[a-z0-9-]+$/',(string)$a['id'])||str_contains((string)$a['directory'],'..')||!is_dir($tmp.'/'.ltrim((string)$a['directory'],'/')))throw new RuntimeException('Ungültiger App-Eintrag im Katalog.');}exec('rm -rf '.escapeshellarg($this->cfg['appRepo']));rename($tmp,$this->cfg['appRepo']);}
 public function install(array$a):string{$source=realpath($this->cfg['appRepo'].'/'.ltrim((string)$a['directory'],'/'));if(!$source||!str_starts_with($source,realpath($this->cfg['appRepo']).'/'))throw new RuntimeException('App-Verzeichnis ungültig.');foreach(['index.php','admin.php']as$f)if(!is_file($source.'/'.$f))throw new RuntimeException($f.' fehlt.');$id=$this->id();$target=$this->cfg['instances'].'/'.$id.'/app';mkdir($target,0770,true);$this->copy($source,$target);if(!is_file($target.'/csv-reporting.json'))file_put_contents($target.'/csv-reporting.json',"{}\n");$this->db->prepare('INSERT INTO installed_apps VALUES(?,?,?,?,?)')->execute([$id,$a['id'],$a['name'],$a['directory'],gmdate('c')]);return$id;}
 private function copy(string$from,string$to):void{foreach(scandir($from)?:[]as$n){if($n==='.'||$n==='..'||$n==='.git')continue;$a=$from.'/'.$n;$b=$to.'/'.$n;if(is_dir($a)){if(!is_dir($b))mkdir($b,0770,true);$this->copy($a,$b);}else copy($a,$b);}}
}
