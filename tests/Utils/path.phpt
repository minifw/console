--TEST--
Cmd path test
--FILE--
<?php
require __DIR__ . '/../bootstrap.php';

use Minifw\Console\Utils;

chdir(__DIR__);

$path = Utils::getFullPath('.');
var_dump($path);

$path = Utils::getFullPath('/tmp/123');
var_dump($path);

$path = Utils::getFullPath('D:\\111\\444');
var_dump($path);
?>
--EXPECTF--
string(%d) "%s"
string(8) "/tmp/123"
string(10) "D:/111/444"