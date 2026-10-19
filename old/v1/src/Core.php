<?php
declare(strict_types=1);
namespace Wams;
use PDO;
use RuntimeException;
final class Core {
    public array $cfg; public PDO $db;
    public function __construct() {
        $this->cfg=require dirname(__DIR__).'/config/defaults.php';
        @mkdir(dirname($this->cfg['db']),0770,true); @mkdir($this->cfg['state'].'/instances',0770,true); @mkdir($this->cfg['state'].'/audit',0770,true);
        $this->db=new PDO('sqlite:'.$this->cfg['db'],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $this->db->exec('PRAGMA foreign_keys=ON; PRAGMA journal_mode=WAL;');
    }
    public function installed(): bool {return (bool)$this->db->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();}
    public function migrate(): void { $sql=file_get_contents(dirname(__DIR__).'/migrations/001.sql'); if($sql===false) throw new RuntimeException('Migration fehlt'); $this->db->exec($sql); }
    public function id(): string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',[...str_split(bin2hex($b),4)]);}
    public function csrf(): string {if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
    public function checkCsrf(): void {if(!hash_equals($_SESSION['csrf']??'',(string)($_POST['csrf']??'')))throw new RuntimeException('Ungültiges CSRF-Token.');}
    public function user(): ?array {if(empty($_SESSION['uid']))return null;$s=$this->db->prepare('SELECT id,username,email,is_global,totp_enabled FROM users WHERE id=? AND active=1');$s->execute([$_SESSION['uid']]);return $s->fetch()?:null;}
    public function login(string $login,string $password): bool {$s=$this->db->prepare('SELECT * FROM users WHERE active=1 AND (username=? OR email=?)');$s->execute([$login,$login]);$u=$s->fetch();if(!$u||!password_verify($password,$u['password_hash'])){usleep(250000);return false;}session_regenerate_id(true);$_SESSION['uid']=$u['id'];$this->audit('auth.login',['user'=>$u['id']]);return true;}
    public function requireLogin(): array {$u=$this->user();if(!$u){header('Location: ?p=login');exit;}return $u;}
    public function audit(string $event,array $data=[]): void {$id=$this->id();$j=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$s=$this->db->prepare('INSERT INTO audit(id,created_at,user_id,event,data) VALUES(?,?,?,?,?)');$s->execute([$id,gmdate('c'),$_SESSION['uid']??null,$event,$j]);}
    public function tenants(string $uid): array {$u=$this->user();if($u&&$u['is_global'])return $this->db->query('SELECT * FROM tenants ORDER BY name')->fetchAll();$s=$this->db->prepare('SELECT t.* FROM tenants t JOIN user_tenants ut ON ut.tenant_id=t.id WHERE ut.user_id=? ORDER BY t.name');$s->execute([$uid]);return $s->fetchAll();}
    public function canTenant(string $uid,string $tid): bool {$u=$this->user();if($u&&$u['is_global'])return true;$s=$this->db->prepare('SELECT 1 FROM user_tenants WHERE user_id=? AND tenant_id=?');$s->execute([$uid,$tid]);return (bool)$s->fetchColumn();}
    public function catalog(): array {$out=[];foreach(glob($this->cfg['apps'].'/*/app.json')?:[] as $f){$a=json_decode((string)file_get_contents($f),true);if(is_array($a)&&($a['catalog']['discoverable']??true))$out[]=$a;}return $out;}
    public function appManifest(string $id): array {$f=$this->cfg['apps'].'/'.basename($id).'/app.json';$a=is_file($f)?json_decode((string)file_get_contents($f),true):null;if(!is_array($a))throw new RuntimeException('App-Manifest nicht gefunden.');return $a;}
    public function installApp(string $tid,string $appId,string $name,array $config): string {$app=$this->appManifest($appId);$iid=$this->id();$base=$this->cfg['state'].'/instances/'.$iid;mkdir($base.'/config',0770,true);mkdir($base.'/data',0770,true);file_put_contents($base.'/config/current.json',json_encode(['appId'=>$appId,'version'=>$app['version'],'instanceId'=>$iid,'tenantId'=>$tid,'values'=>$config],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));$s=$this->db->prepare('INSERT INTO app_instances(id,tenant_id,app_id,name,version,style_id,created_at) VALUES(?,?,?,?,?,?,?)');$s->execute([$iid,$tid,$appId,$name,$app['version'],'default',gmdate('c')]);$this->audit('app.install',['instance'=>$iid,'app'=>$appId,'tenant'=>$tid]);return $iid;}
}
