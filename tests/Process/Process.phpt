--TEST--
Process test
--FILE--
<?php
use Minifw\Console\Process;

require __DIR__ . '/../../vendor/autoload.php';

chdir(__DIR__ . '/..');

$proc = new Process('ls Process/');
echo $proc->exec(1, $code);
var_dump($code);

$proc = new Process('ls', __DIR__);
echo $proc->exec(1, $code);
var_dump($code);

$proc = new Process('cat ' . '.gitignore', __DIR__);
echo $proc->exec(2, $code);
var_dump($code);

$proc = new Process('cat', __DIR__);

$fpIn = fopen('php://temp', 'r+');
fwrite($fpIn, 'process test');
rewind($fpIn);

$fpOut = fopen('php://temp', 'r+');

$code = $proc->run($fpIn, $fpOut);

rewind($fpOut);
var_dump(stream_get_contents($fpOut));
var_dump($code);
fclose($fpIn);
fclose($fpOut);

$proc = new Process('cat', __DIR__);

$fpIn = fopen('php://temp', 'r+');
fwrite($fpIn, "process test\n111\n222");
rewind($fpIn);

$msgList = [];
$code = $proc->run($fpIn, null, null, function ($type, $msg) {
    global $msgList;
    if (!isset($msgList[$type])) {
        $msgList[$type] = '';
    }
    $msgList[$type] .= $msg;
});

echo json_encode($msgList) . "\n";

?>
--EXPECTF--
Process.phpt
int(0)
Process.phpt
int(0)
cat: .gitignore: No such file or directory
int(1)
string(12) "process test"
int(0)
{"1":"process test\n111\n222"}