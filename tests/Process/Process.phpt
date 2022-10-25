--TEST--
Process test
--FILE--
<?php
use Minifw\Console\Process;

require __DIR__ . '/../bootstrap.php';

chdir(__DIR__ . '/..');

$proc = new Process('ls Process/Process.phpt');
echo $proc->run()->getStdout();
var_dump($proc->getExitCode());

$proc = new Process('ls Process/Process.phpt');
echo $proc->exec(1);

$proc = new Process('ls Process.phpt', __DIR__);
echo $proc->run()->getStdout();
var_dump($proc->getExitCode());

$proc = new Process('cat ' . '.gitignore', __DIR__);
echo $proc->run()->getStderr();
var_dump($proc->getExitCode());

$proc = new Process('cat ' . '.gitignore', __DIR__);
echo $proc->exec(2);

$proc = new Process('cat', __DIR__);
$proc->setTimeout(3)->addInput('process test', true)->run();
var_dump($proc->getStdout());
var_dump($proc->getExitCode());

$proc = new Process('cat', __DIR__);

$msgList = [];
$proc->setCallback(function ($name, $type, $msg) {
    global $msgList;
    if (!isset($msgList[$type])) {
        $msgList[$type] = '';
    }
    $msgList[$type] .= $msg;
})->setTimeout(3)->addInput("process test\n111\n222", true)->run();

echo json_encode($msgList) . "\n";
var_dump($proc->getExitCode());

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
var_dump($proc->getExitCode());
?>
--EXPECTF--
Process/Process.phpt
int(0)
Process/Process.phpt
Process.phpt
int(0)
cat: .gitignore: No such file or directory
int(1)
cat: .gitignore: No such file or directory
string(12) "process test"
int(0)
{"1":"process test\n111\n222"}
int(0)
123
int(0)