--TEST--
Cmd args test
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Cmd;
use Minifw\Common\Exception;

Cmd::print_json([]);
Cmd::print_json(['test' => 'value']);

$ex = new Exception('msg', 123);
Cmd::print_exception($ex);

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

Cmd::print_table($cols, $body, $footer);
?>
--EXPECTF--
[]
{
    "test": "value"
}
[123] Standard input code[10]: msg
|  ID   | name           |
--------------------------
|   1   | name1          |
|   2   | 111 123 123123 |
|   3   | 333            |
--------------------------
| count | 3              |
