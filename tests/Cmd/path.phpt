--TEST--
Cmd path test
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Cmd;

chdir(__DIR__);

$path = Cmd::getFullPath('.');
var_dump($path);

$path = Cmd::getFullPath('/tmp/123');
var_dump($path);

$path = Cmd::getFullPath('D:\\111\\444');
var_dump($path);
?>
--EXPECTF--
string(%d) "%s"
string(8) "/tmp/123"
string(10) "D:/111/444"