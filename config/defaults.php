<?php
declare(strict_types=1);
$root=dirname(__DIR__);
return ['root'=>$root,'state'=>$root.'/state','db'=>$root.'/state/wams.sqlite','apps'=>$root.'/state/apps','repository'=>$root.'/state/repository'];
