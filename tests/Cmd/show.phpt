--TEST--
Cmd args test
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Cmd;
use Minifw\Common\Exception;

echo Cmd::show_duration(30) . PHP_EOL;
echo Cmd::show_duration(90) . PHP_EOL;
echo Cmd::show_duration(3600) . PHP_EOL;
echo Cmd::show_duration(3661) . PHP_EOL;
echo Cmd::show_size(500) . PHP_EOL;
echo Cmd::show_size(1024) . PHP_EOL;
echo Cmd::show_size(10240) . PHP_EOL;
echo Cmd::show_size(102400) . PHP_EOL;
echo Cmd::show_size(1048576) . PHP_EOL;
echo Cmd::show_size(10485760) . PHP_EOL;
echo Cmd::show_size(104857600) . PHP_EOL;

Cmd::echo_json([]);
Cmd::echo_json(['test' => 'value']);

$ex = new Exception('msg', 123);
Cmd::echo_exception($ex);

$cols = [
    [
        'name' => 'ID',
        'align' => 'center',
    ],
    [
        'name' => 'name',
        'align' => 'left',
    ],
];
$body = [
    [1, 'name1'],
    [2, '111 123 123123'],
    [3, '333'],
];
$footer = ['count', 3];

Cmd::echo_table($cols, $body, $footer);
?>
--EXPECTF--
00:00:30
00:01:30
01:00:00
01:01:01
500
1.00 K
10.0 K
100 K
1.00 M
10.0 M
100 M
[]
{
    "test": "value"
}
[123] Standard input code[22]: msg
|  ID   | name           |
--------------------------
|   1   | name1          |
|   2   | 111 123 123123 |
|   3   | 333            |
--------------------------
| count | 3              |
