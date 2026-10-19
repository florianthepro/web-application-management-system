<?php
declare(strict_types=1);
$root=dirname(__DIR__);
return ['root'=>$root,'state'=>$root.'/state','db'=>$root.'/state/wams.sqlite','instances'=>$root.'/state/instances','appRepo'=>$root.'/state/app-repository'];
