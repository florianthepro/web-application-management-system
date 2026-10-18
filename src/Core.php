<?php
declare(strict_types=1);
namespace Wams;
use PDO;use RuntimeException;
final class Core{
 public array $cfg;public PDO $db;
 public function __construct(){
  $this->cfg=require dirname(__DIR__).'/config/defaults.php';
  if(!is_dir($this->cfg['state'])&&!mkdir($this->cfg['state'],0770,true))throw new RuntimeException('State-Verzeichnis kann nicht angelegt werden.');
  if(!is_writable($this->cfg['state']))throw new RuntimeException('State-Verzeichnis ist nicht beschreibbar.');
  $this->db=new PDO('sqlite:'.$this->cfg['db'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $this->db->exec('PRAGMA foreign_keys=ON;PRAGMA journal_mode=WAL;PRAGMA busy_timeout=5000');
  foreach(glob(dirname(__DIR__).'/migrations/*.sql')?:[] as$file)$this->db->exec((string)file_get_contents($file));
 }
 public function id():string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
 public function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return(string)$_SESSION['csrf'];}
 public function check():void{if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Ungültige Anfrage.');}
 public function user():?array{if(empty($_SESSION['uid']))return null;$q=$this->db->prepare('SELECT * FROM users WHERE id=? AND active=1');$q->execute([$_SESSION['uid']]);return$q->fetch()?:null;}
 public function requireUser():array{$u=$this->user();if(!$u){header('Location:/login');exit;}return$u;}
 public function login(string$name,string$password):string{$q=$this->db->prepare('SELECT * FROM users WHERE username=? AND active=1');$q->execute([$name]);$u=$q->fetch();if(!$u||!password_verify($password,$u['password_hash']))return'invalid';if($u['totp_enabled']&&$u['totp_secret']){$_SESSION['pending_uid']=$u['id'];return'totp';}session_regenerate_id(true);$_SESSION['uid']=$u['id'];return'ok';}
 public function finishTotp(string$code):bool{if(empty($_SESSION['pending_uid']))return false;$q=$this->db->prepare('SELECT * FROM users WHERE id=?');$q->execute([$_SESSION['pending_uid']]);$u=$q->fetch();if(!$u||!$this->verifyTotp((string)$u['totp_secret'],$code))return false;session_regenerate_id(true);$_SESSION['uid']=$u['id'];unset($_SESSION['pending_uid']);return true;}
 private function verifyTotp(string$secret,string$code):bool{$abc='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$secret=strtoupper(preg_replace('/[^A-Z2-7]/','',$secret));$bits='';foreach(str_split($secret)as$ch){$v=strpos($abc,$ch);if($v===false)return false;$bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT);}$key='';for($i=0;$i+8<=strlen($bits);$i+=8)$key.=chr(bindec(substr($bits,$i,8)));$counter=(int)floor(time()/30);for($d=-1;$d<=1;$d++){$hash=hash_hmac('sha1',pack('N*',0,$counter+$d),$key,true);$o=ord($hash[19])&15;$n=((ord($hash[$o])&127)<<24)|((ord($hash[$o+1])&255)<<16)|((ord($hash[$o+2])&255)<<8)|(ord($hash[$o+3])&255);if(hash_equals(str_pad((string)($n%1000000),6,'0',STR_PAD_LEFT),preg_replace('/\D/','',$code)))return true;}return false;}
 public function tenants(array$u):array{if($u['is_global'])return$this->db->query('SELECT * FROM tenants ORDER BY name')->fetchAll();$q=$this->db->prepare("SELECT DISTINCT t.* FROM tenants t JOIN tenant_grants x ON x.tenant_id=t.id LEFT JOIN group_users gu ON gu.group_id=x.principal_id WHERE (x.principal_type='user' AND x.principal_id=?) OR (x.principal_type='group' AND gu.user_id=?) ORDER BY t.name");$q->execute([$u['id'],$u['id']]);return$q->fetchAll();}
 public function canTenant(array$u,?string$id):bool{if($id===null||$id==='')return true;if($u['is_global'])return true;foreach($this->tenants($u)as$t)if($t['id']===$id)return true;return false;}
 public function instance(string$id):array{$q=$this->db->prepare('SELECT * FROM app_instances WHERE id=?');$q->execute([$id]);$i=$q->fetch();if(!$i)throw new RuntimeException('App-Instanz nicht gefunden.');return$i;}
 public function hasGrant(array$u,string$instance,string$permission):bool{if($u['is_global'])return true;$q=$this->db->prepare("SELECT 1 FROM app_grants x LEFT JOIN group_users gu ON gu.group_id=x.principal_id WHERE x.instance_id=? AND x.permission=? AND ((x.principal_type='user' AND x.principal_id=?) OR (x.principal_type='group' AND gu.user_id=?)) LIMIT 1");$q->execute([$instance,$permission,$u['id'],$u['id']]);return(bool)$q->fetchColumn();}
 public function config(string$id):array{$f=$this->cfg['state'].'/instances/'.$id.'/config.json';$v=is_file($f)?json_decode((string)file_get_contents($f),true):[];return is_array($v)?$v:[];}
 public function saveConfig(string$id,array$config):void{$dir=$this->cfg['state'].'/instances/'.$id;if(!is_dir($dir.'/history'))mkdir($dir.'/history',0770,true);if(is_file($dir.'/config.json'))copy($dir.'/config.json',$dir.'/history/'.gmdate('YmdHis').'.json');file_put_contents($dir.'/config.json',json_encode($config,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),LOCK_EX);}
 public function catalog():array{$out=[];foreach(glob($this->cfg['apps'].'/*/app.json')?:[]as$f){$a=json_decode((string)file_get_contents($f),true);if(($a['catalog']['discoverable']??false)&&($a['catalog']['installable']??false))$out[]=$a;}return$out;}
 public function audit(?string$user,string$action,string$type,?string$id,array$details=[]):void{$q=$this->db->prepare('INSERT INTO audit_log VALUES(?,?,?,?,?,?,?)');$q->execute([$this->id(),$user,$action,$type,$id,gmdate('c'),json_encode($details,JSON_UNESCAPED_SLASHES)]);}
}
