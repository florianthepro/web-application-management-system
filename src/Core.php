<?php
declare(strict_types=1);
namespace Wams;
use PDO;
use RuntimeException;
final class Core{
 public array $cfg;
 public PDO $db;
 public function __construct(){
  $this->cfg=require dirname(__DIR__).'/config/defaults.php';
  foreach([$this->cfg['state'],$this->cfg['apps']] as $dir){if(!is_dir($dir)&&!mkdir($dir,0770,true))throw new RuntimeException('Verzeichnis konnte nicht angelegt werden.');}
  $this->db=new PDO('sqlite:'.$this->cfg['db'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $this->db->exec('PRAGMA foreign_keys=ON;PRAGMA journal_mode=WAL;PRAGMA busy_timeout=5000');
  foreach(glob(dirname(__DIR__).'/migrations/*.sql')?:[] as $file)$this->db->exec((string)file_get_contents($file));
 }
 public function id():string{return bin2hex(random_bytes(16));}
 public function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return(string)$_SESSION['csrf'];}
 public function verifyCsrf():void{if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Ungueltige Anfrage.');}
 public function setting(string $key,string $default=''):string{$q=$this->db->prepare('SELECT value FROM settings WHERE key=?');$q->execute([$key]);$v=$q->fetchColumn();return $v===false?$default:(string)$v;}
 public function setSetting(string $key,string $value):void{$this->db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute([$key,$value]);}
 public function user():?array{if(empty($_SESSION['uid']))return null;$q=$this->db->prepare('SELECT * FROM users WHERE id=? AND active=1');$q->execute([$_SESSION['uid']]);return $q->fetch()?:null;}
 public function requireUser():array{$u=$this->user();if(!$u){header('Location: /login');exit;}return $u;}
 public function login(string $username,string $password):bool{$q=$this->db->prepare('SELECT * FROM users WHERE username=? AND active=1');$q->execute([$username]);$u=$q->fetch();if(!$u||!password_verify($password,$u['password_hash']))return false;session_regenerate_id(true);$_SESSION['uid']=$u['id'];return true;}
 public function app(string $id):array{$q=$this->db->prepare('SELECT * FROM apps WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)throw new RuntimeException('App nicht gefunden.');return $a;}
 public function canUse(array $u,string $id):bool{if($u['is_global_admin'])return true;$q=$this->db->prepare('SELECT 1 FROM user_apps WHERE user_id=? AND app_id=?');$q->execute([$u['id'],$id]);return(bool)$q->fetchColumn();}
 public function canAdmin(array $u,string $id):bool{if($u['is_global_admin'])return true;$q=$this->db->prepare('SELECT is_admin FROM user_apps WHERE user_id=? AND app_id=?');$q->execute([$u['id'],$id]);return(bool)$q->fetchColumn();}
 public function refreshRepository():void{
  $url=$this->setting('app_repository');
  if(!preg_match('~^https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\.git)?$~',$url))throw new RuntimeException('App-Repository ist ungueltig.');
  $tmp=$this->cfg['state'].'/repository-new-'.bin2hex(random_bytes(4));$out=[];$code=0;exec('GIT_TERMINAL_PROMPT=0 git clone --depth 1 '.escapeshellarg($url).' '.escapeshellarg($tmp).' 2>&1',$out,$code);if($code!==0)throw new RuntimeException('App-Repository konnte nicht geladen werden: '.implode("\n",$out));
  $catalog=json_decode((string)@file_get_contents($tmp.'/catalog.json'),true);if(!is_array($catalog['apps']??null)){$this->remove($tmp);throw new RuntimeException('catalog.json ist ungueltig.');}
  foreach($catalog['apps'] as $item){$id=(string)($item['id']??'');$name=(string)($item['name']??'');$dir=(string)($item['directory']??'');if(!preg_match('/^[a-z0-9-]+$/',$id)||$name===''||$dir===''||str_contains($dir,'..')||str_starts_with($dir,'/')||!is_file($tmp.'/'.$dir.'/index.php')||!is_file($tmp.'/'.$dir.'/admin.php')){$this->remove($tmp);throw new RuntimeException('Ungueltiger App-Eintrag in catalog.json.');}}
  $this->remove($this->cfg['repository']);rename($tmp,$this->cfg['repository']);
 }
 public function catalog():array{$file=$this->cfg['repository'].'/catalog.json';$data=is_file($file)?json_decode((string)file_get_contents($file),true):null;return is_array($data['apps']??null)?$data['apps']:[];}
 public function install(array $item):string{
  $source=realpath($this->cfg['repository'].'/'.(string)$item['directory']);$base=realpath($this->cfg['repository']);if(!$source||!$base||!str_starts_with($source,$base.'/'))throw new RuntimeException('App-Pfad ist ungueltig.');
  $id=$this->id();$target=$this->cfg['apps'].'/'.$id;if(!mkdir($target,0770,true))throw new RuntimeException('App konnte nicht angelegt werden.');$this->copy($source,$target);
  $this->db->prepare('INSERT INTO apps VALUES(?,?,?,?,?)')->execute([$id,$item['id'],$item['name'],$item['directory'],gmdate('c')]);return $id;
 }
 private function copy(string $from,string $to):void{foreach(scandir($from)?:[] as $n){if($n==='.'||$n==='..'||$n==='.git')continue;$a=$from.'/'.$n;$b=$to.'/'.$n;if(is_dir($a)){mkdir($b,0770,true);$this->copy($a,$b);}elseif(!copy($a,$b))throw new RuntimeException('App-Datei konnte nicht kopiert werden.');}}
 private function remove(string $path):void{if(!is_dir($path))return;foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST)as$f){$f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());}rmdir($path);}
}
