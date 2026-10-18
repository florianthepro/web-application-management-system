<?php
declare(strict_types=1);
namespace Wams;
use PDO;
use RuntimeException;
final class Core{
 public array $cfg;public PDO $db;
 public function __construct(){
  $this->cfg=require dirname(__DIR__).'/config/defaults.php';
  if(!is_dir($this->cfg['state'])&&!mkdir($this->cfg['state'],0770,true))throw new RuntimeException('State-Verzeichnis kann nicht angelegt werden.');
  if(!is_writable($this->cfg['state']))throw new RuntimeException('State-Verzeichnis ist nicht beschreibbar.');
  $this->db=new PDO('sqlite:'.$this->cfg['db'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $this->db->exec('PRAGMA foreign_keys=ON;PRAGMA journal_mode=WAL;PRAGMA busy_timeout=5000');$this->migrate();
 }
 public function migrate():void{
  foreach(glob(dirname(__DIR__).'/migrations/*.sql')?:[] as $file){$sql=file_get_contents($file);if($sql===false)throw new RuntimeException('Migration fehlt.');$this->db->exec($sql);}
  $cols=array_column($this->db->query('PRAGMA table_info(users)')->fetchAll(),'name');
  if(!in_array('totp_secret',$cols,true))$this->db->exec('ALTER TABLE users ADD COLUMN totp_secret TEXT');
  if(!in_array('totp_enabled',$cols,true))$this->db->exec('ALTER TABLE users ADD COLUMN totp_enabled INTEGER NOT NULL DEFAULT 0');
  $this->db->exec("INSERT OR IGNORE INTO update_sources(target_type,target_id,repository,branch) VALUES('system','wams','https://github.com/florianthepro/web-application-management-system','main')");
 }
 public function installed():bool{return (bool)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();}
 public function id():string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',[...str_split(bin2hex($b),4)]);}
 public function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
 public function check():void{if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))throw new RuntimeException('Ungültige Anfrage.');}
 public function user():?array{if(empty($_SESSION['uid']))return null;$q=$this->db->prepare('SELECT * FROM users WHERE id=? AND active=1');$q->execute([$_SESSION['uid']]);return $q->fetch()?:null;}
 public function requireUser():array{$u=$this->user();if(!$u){header('Location:/login');exit;}return $u;}
 public function beginLogin(string $name,string $password):string{$q=$this->db->prepare('SELECT * FROM users WHERE active=1 AND username=?');$q->execute([$name]);$u=$q->fetch();if(!$u||!password_verify($password,$u['password_hash']))return'invalid';if(!empty($u['totp_enabled'])&&!empty($u['totp_secret'])){$_SESSION['pending_uid']=$u['id'];return'totp';}session_regenerate_id(true);$_SESSION['uid']=$u['id'];return'ok';}
 public function finishTotp(string $code):bool{if(empty($_SESSION['pending_uid']))return false;$q=$this->db->prepare('SELECT * FROM users WHERE id=? AND active=1');$q->execute([$_SESSION['pending_uid']]);$u=$q->fetch();if(!$u||!$this->verifyTotp((string)$u['totp_secret'],$code))return false;session_regenerate_id(true);$_SESSION['uid']=$u['id'];unset($_SESSION['pending_uid']);return true;}
 private function verifyTotp(string $secret,string $code):bool{$abc='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$secret=strtoupper(preg_replace('/[^A-Z2-7]/','',$secret));$bits='';foreach(str_split($secret)as$ch){$v=strpos($abc,$ch);if($v===false)return false;$bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT);}$key='';for($i=0;$i+8<=strlen($bits);$i+=8)$key.=chr(bindec(substr($bits,$i,8)));$counter=(int)floor(time()/30);for($d=-1;$d<=1;$d++){$hash=hash_hmac('sha1',pack('N*',0,$counter+$d),$key,true);$o=ord($hash[19])&15;$n=((ord($hash[$o])&127)<<24)|((ord($hash[$o+1])&255)<<16)|((ord($hash[$o+2])&255)<<8)|(ord($hash[$o+3])&255);if(hash_equals(str_pad((string)($n%1000000),6,'0',STR_PAD_LEFT),preg_replace('/\D/','',$code)))return true;}return false;}
 public function isAdmin(array $u):bool{return (bool)$u['is_global'];}
 public function readableTenants(array $u):array{if($this->isAdmin($u))return$this->db->query('SELECT * FROM tenants ORDER BY name')->fetchAll();$q=$this->db->prepare("SELECT DISTINCT t.* FROM tenants t LEFT JOIN tenant_access a ON a.tenant_id=t.id AND a.user_id=? LEFT JOIN group_tenants gt ON gt.tenant_id=t.id LEFT JOIN group_users gu ON gu.group_id=gt.group_id AND gu.user_id=? WHERE a.can_read=1 OR (gt.can_read=1 AND gu.user_id IS NOT NULL) ORDER BY t.name");$q->execute([$u['id'],$u['id']]);return$q->fetchAll();}
 public function canReadTenant(array $u,string $tenant):bool{if($this->isAdmin($u))return true;foreach($this->readableTenants($u)as$t)if($t['id']===$tenant)return true;return false;}
 public function catalog():array{$out=[];foreach(glob($this->cfg['apps'].'/*/app.json')?:[]as$file){$a=json_decode((string)file_get_contents($file),true);if(is_array($a)&&($a['catalog']['discoverable']??false)&&($a['catalog']['installable']??false))$out[]=$a;}return$out;}
 public function manifest(string $id):array{$f=$this->cfg['apps'].'/'.basename($id).'/app.json';$a=is_file($f)?json_decode((string)file_get_contents($f),true):null;if(!is_array($a))throw new RuntimeException('App nicht gefunden.');return$a;}
 public function instance(string $id):array{$q=$this->db->prepare('SELECT * FROM app_instances WHERE id=?');$q->execute([$id]);$i=$q->fetch();if(!$i)throw new RuntimeException('App-Instanz nicht gefunden.');return$i;}
 public function config(string $id):array{$f=$this->cfg['state'].'/instances/'.$id.'/config.json';$v=is_file($f)?json_decode((string)file_get_contents($f),true):[];return is_array($v)?$v:[];}
 public function remoteCheck(array $c):array{$url=(string)($c['remoteUrl']??'');if(!filter_var($url,FILTER_VALIDATE_URL))return[false,'Remote-URL ist ungültig.'];$ctx=stream_context_create(['http'=>['timeout'=>max(2,min(60,(int)($c['timeoutSeconds']??20))),'ignore_errors'=>true,'header'=>"Accept: text/csv,text/plain\r\nUser-Agent: WAMS-CSV/2.0\r\n"]]);$data=@file_get_contents($url,false,$ctx);if($data===false)return[false,'Remote-Daten sind nicht erreichbar. Möglicherweise blockiert DNS den Host. Versuche, sofern zulässig, die IP-Adresse.'];if($data==='')return[false,'Remote-Quelle liefert keine Daten.'];return[true,'OK'];}
 public function installApp(string $tenant,string $appId,string $name,array $cfg):string{$m=$this->manifest($appId);if($appId==='csv-reporting'){[$ok,$msg]=$this->remoteCheck($cfg);if(!$ok)throw new RuntimeException($msg);}$id=$this->id();$dir=$this->cfg['state'].'/instances/'.$id;mkdir($dir.'/data',0770,true);mkdir($dir.'/history',0770,true);file_put_contents($dir.'/config.json',json_encode($cfg,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));$q=$this->db->prepare('INSERT INTO app_instances VALUES(?,?,?,?,?,?)');$q->execute([$id,$tenant,$appId,$name,$m['version'],gmdate('c')]);return$id;}
 public function saveConfig(string $id,array $cfg):void{$i=$this->instance($id);if($i['app_id']==='csv-reporting'){[$ok,$msg]=$this->remoteCheck($cfg);if(!$ok)throw new RuntimeException($msg);}$dir=$this->cfg['state'].'/instances/'.$id;mkdir($dir.'/history',0770,true);if(is_file($dir.'/config.json'))copy($dir.'/config.json',$dir.'/history/'.gmdate('YmdHis').'.json');file_put_contents($dir.'/config.json',json_encode($cfg,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));}
 public function hasGrant(array $u,string $instance,string $permission):bool{if($this->isAdmin($u))return true;$q=$this->db->prepare("SELECT 1 FROM app_grants g WHERE g.instance_id=? AND g.permission=? AND ((g.principal_type='user' AND g.principal_id=?) OR (g.principal_type='group' AND EXISTS(SELECT 1 FROM group_users gu WHERE gu.group_id=g.principal_id AND gu.user_id=?))) LIMIT 1");$q->execute([$instance,$permission,$u['id'],$u['id']]);return(bool)$q->fetchColumn();}
 public function tenants(array $u):array{return $this->readableTenants($u);}
 public function allowed(array $u,array $i,string $permission):bool{$kind=str_ends_with($permission,'.manage')?'edit':'use';return $this->canReadTenant($u,$i['tenant_id'])&&$this->hasGrant($u,$i['id'],$kind);}
 public function install(string $tenant,string $app,string $name,array $cfg,array $u,string $mode):string{if(!$this->isAdmin($u))throw new RuntimeException('Nur der Administrator darf Apps installieren.');return $this->installApp($tenant,$app,$name,$cfg);}
 public function save(string $id,array $cfg):void{$this->saveConfig($id,$cfg);}
 public function queueUpdate(string $type,string $id,string $action):void{$q=$this->db->prepare('INSERT INTO update_jobs VALUES(?,?,?,?,?,?,?)');$q->execute([$this->id(),$type,$id,$action,'queued',gmdate('c'),null,'']);}
}
