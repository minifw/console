--TEST--
Command test
--CAPTURE_STDIO--
STDOUT
--FILE--
<?php

use Minifw\Console\CommandRunner;

require __DIR__ . '/../bootstrap.php';

include(__DIR__ . '/Cmd1.php');
include(__DIR__ . '/Cmd2.php');

$runner = new CommandRunner('Minifw\\Console\\Tests\\Command');

$runner->run(['cmd1', 'act1', '111', '222']);
$runner->run(['cmd1', 'act2']);

$runner->run(['cmd2', 'act2', '--config', 'config file', '111', '222']);
$runner->run(['cmd2', '--config', 'config file', 'act2',  '111', '222']);
?>
--EXPECT--
[]
[]
["111","222"]
[]
[]
[]
usage: cmd1 bbaba
bbaba

act1:
    act1 ggg

act2:
    act1 ggg

{"config":"config file"}
[]
["111","222"]
usage: cmd2 bbaba
bbaba

全局选项:
--config: string
    config file

act1:
    act1 ggg

    --range: int, int

act2:
    act1 ggg

{"config":"config file"}
[]
["111","222"]
usage: cmd2 bbaba
bbaba

全局选项:
--config: string
    config file

act1:
    act1 ggg

    --range: int, int

act2:
    act1 ggg
