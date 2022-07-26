--TEST--
CronRunner test
--FILE--
<?php

use Minifw\Common\File;
use Minifw\Console\CronRunner;

require __DIR__ . '/../bootstrap.php';

$files = [
    APP_ROOT . '/tmp/tests/cron/crondata.json',
    APP_ROOT . '/tmp/tests/cron/cronlock',
    APP_ROOT . '/tmp/tests/cron/cronlog.log',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

$dir = APP_ROOT . '/tmp/tests/cron';
if (file_exists($dir)) {
    $obj = new File($dir);
    $obj->clearDir(true);
}

$config = require(__DIR__ . '/cron_cfg.php');
$runner = new CronRunner($config);

$runner->run(['-f']);

$succeed = true;
foreach ($files as $file) {
    if (!file_exists($file)) {
        $succeed = false;
    }
}
var_dump($succeed);

?>
--EXPECTF--
bool(true)