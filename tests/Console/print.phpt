--TEST--
print test
--CAPTURE_STDIO--
STDOUT
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Console;

$console = new Console();
$console->print('123456');
$console->setStatus('000000');
$console->print("\033[32m123456\033[0m");
?>
--EXPECT--
123456
123456