--TEST--
Cmd path test
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Cmd;

chdir(__DIR__);

$path = Cmd::get_full_path('.');
var_dump($path);

$path = Cmd::get_full_path('/tmp/123');
var_dump($path);

$path = Cmd::get_full_path('D:\\111\\444');
var_dump($path);
?>
--EXPECTF--
string(%d) "%s"
string(8) "/tmp/123"
string(10) "D:/111/444"