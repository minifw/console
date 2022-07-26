--TEST--
CronTime test
--FILE--
<?php

use Minifw\Console\CronTask;
use Minifw\Console\CronTime;

require __DIR__ . '/../bootstrap.php';

$input = [
    '20',
    '2,18,22',
    '1-5,11-20/2,21-30/3,26',
    '1-11/2',
    '1-3,6',
];

$schedule = CronTask::parse($input);
echo json_encode($schedule) . "\n";
$now = mktime(14, 34, 5, 3, 20, 2022);
echo date('Y-m-d N H:i:s', $now) . "\n";
$time = new CronTime($now, $schedule);
$next = $time->findNext();
echo date('Y-m-d N H:i:s', $next) . "\n";
$next = $time->findNext();
echo date('Y-m-d N H:i:s', $next) . "\n";

$input = [
    '49',
    '1',
    '1,5,31',
    '*',
    '*',
];

$schedule = CronTask::parse($input);
echo json_encode($schedule) . "\n";
$now = mktime(23, 50, 5, 12, 31, 2022);
echo date('Y-m-d N H:i:s', $now) . "\n";
$time = new CronTime($now, $schedule);
$next = $time->findNext();
echo date('Y-m-d N H:i:s', $next) . "\n";
$next = $time->findNext();
echo date('Y-m-d N H:i:s', $next) . "\n";

?>
--EXPECTF--
{"min":[20],"hour":[2,18,22],"day":[1,2,3,4,5,11,13,15,17,19,21,24,26,27,30],"month":[1,3,5,7,9,11],"week":[1,2,3,6]}
2022-03-20 7 14:34:05
2022-03-21 1 02:20:00
2022-03-21 1 18:20:00
{"min":[49],"hour":[1],"day":[1,5,31],"month":[1,2,3,4,5,6,7,8,9,10,11,12],"week":[0,1,2,3,4,5,6]}
2022-12-31 6 23:50:05
2023-01-01 7 01:49:00
2023-01-05 4 01:49:00