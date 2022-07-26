--TEST--
Process test
--FILE--
<?php
use Minifw\Console\Process;

require __DIR__ . '/../bootstrap.php';

chdir(__DIR__ . '/..');

$proc = new Process('ls Process/Process.phpt');
echo $proc->exec(1, $code);
var_dump($code);

$proc = new Process('ls Process.phpt', __DIR__);
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

$code = $proc->setStdin($fpIn)->setStdout($fpOut)->run();

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
$code = $proc->setStdin($fpIn)->setCallback(function ($name, $type, $msg) {
    global $msgList;
    if (!isset($msgList[$type])) {
        $msgList[$type] = '';
    }
    $msgList[$type] .= $msg;
})->run();

echo json_encode($msgList) . "\n";
var_dump($code);

$dir = APP_ROOT . '/tmp/tests';
$file = 'test.txt';

if (file_exists($dir . '/' . $file)) {
    unlink($dir . '/' . $file);
}

$proc = new Process('echo \'123\' >> ' . $file, $dir);
$code = $proc->run();
if (!file_exists($dir . '/' . $file)) {
    echo 'faild';
} else {
    echo file_get_contents($dir . '/' . $file);
}
var_dump($code);
?>
--EXPECTF--
Process/Process.phpt
int(0)
Process.phpt
int(0)
cat: .gitignore: No such file or directory
int(1)
string(12) "process test"
int(0)
{"1":"process test\n111\n222"}
int(0)
123
int(0)