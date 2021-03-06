--TEST--
ProcessGroup test
--FILE--
<?php

use Minifw\Console\Process;
use Minifw\Console\ProcessGroup;

require __DIR__ . '/../bootstrap.php';

chdir(__DIR__ . '/php');

$group = new ProcessGroup(function ($name, $stream, $msg) {
    //echo "{$name}:{$stream}:{$msg}\n";
});

$process1 = new Process('"' . PHP_BINARY . '"' . ' test1.php');
$process1->setName('test1');
$process2 = new Process('"' . PHP_BINARY . '"' . ' test2.php');
$process2->setName('test2')->setTimeout(2);
$process3 = new Process('"' . PHP_BINARY . '"' . ' test3.php');
$process3->setName('test3');

$group->addProcess($process1);
$group->addProcess($process2);
$group->addProcess($process3);

$result = [];

while (true) {
    $ret = $group->doLoop();
    if ($ret === null) {
        break;
    } elseif (!empty($ret)) {
        foreach ($ret as $name => $code) {
            $result[$name] = $code;
        }
    }
    usleep(10 * 1000);
}

ksort($result);
echo json_encode($result) . "\n";

?>
--EXPECTF--
{"test1":0,"test2":-1,"test3":20}