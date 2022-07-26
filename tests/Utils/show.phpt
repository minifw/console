--TEST--
Cmd args test
--FILE--
<?php
require __DIR__ . '/../bootstrap.php';

use Minifw\Common\Exception;
use Minifw\Console\Utils;

Utils::printJson([]);
Utils::printJson(['test' => 'value']);

$ex = new Exception('msg', 123);
Utils::printException($ex);

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

Utils::printTable($cols, $body, $footer);
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
