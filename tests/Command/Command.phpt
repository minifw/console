--TEST--
Command test
--CAPTURE_STDIO--
STDOUT
--FILE--
<?php

use Minifw\Console\CommandRunner;

require __DIR__ . '/../bootstrap.php';

include(__DIR__ . '/Cmd1.php');

$runner = new CommandRunner('Minifw\\Console\\Tests\\Command');

$runner->run(['cmd1', 'act1', '111', '222']);
$runner->run(['cmd1', 'act2']);
?>
--EXPECT--
{"options":[],"input":["111","222"]}
{"options":[],"input":[]}
usage: cmd1 bbaba
bbaba

act1:
    act1 ggg

act2:
    act1 ggg