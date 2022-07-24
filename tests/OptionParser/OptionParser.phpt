--TEST--
Cmd args test
--FILE--
<?php
use Minifw\Common\Exception;
use Minifw\Console\OptionParser;

require __DIR__ . '/../../vendor/autoload.php';

$cfg = require(__DIR__ . '/option_cfg.php');

$parser = new OptionParser($cfg);

$input = ['down', 'd', 'he', 'help', 'help2', 'upddd'];
foreach ($input as $value) {
    try {
        echo $parser->getAction($value) . "\n";
    } catch (Exception $ex) {
        echo $ex->getMessage() . "\n";
    }
}
echo "\n";

chdir(__DIR__);
$input = [
    [
        'action' => 'download',
        'argv' => ['-sa', 'option_cfg.php'],
    ],
    [
        'action' => 'download',
        'argv' => ['-sa', 'option_cfg.php', '--retry', '3', '-c', '--', '123', '456'],
    ],
    [
        'action' => 'download',
        'argv' => ['-sa', 'option_cfg.php1'],
    ],
    [
        'action' => 'download',
        'argv' => ['--retry', '3', '-c', '--', '123', '456'],
    ],
    [
        'action' => 'upload',
        'argv' => ['--username', '111', '-p', '333', '--save-to', __DIR__, '--src', __DIR__ . '/333', '--', '123', '456'],
    ],
    [
        'action' => 'upload',
        'argv' => ['--username', '111', '-p', '333', '--save-to', __DIR__ . '/444', '--src', __DIR__ . '/333', '--', '123', '456'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '--retry',  '111', '222', 'ffff', '--rate-limit', '50.34', '--', '123', '456'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--retry',  '111', '222', 'ffff', '--rate-limit', '50.34', '--', '123', '456'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-no-c'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-no-c', '1'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-no-c', '0'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-c'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-c', '1'],
    ],
    [
        'action' => 'sync',
        'argv' => ['--custom', '-c', '0'],
    ],
    [
        'action' => 'help',
        'argv' => ['--custom', '-c', '0'],
    ],
    [
        'action' => 'help',
        'argv' => [],
    ],
    [
        'action' => 'help2',
        'argv' => [],
    ],
];

foreach ($input as $value) {
    try {
        echo json_encode($parser->getOptions($value['action'], $value['argv'], JSON_UNESCAPED_UNICODE)) . "\n";
    } catch (Exception $ex) {
        echo $ex->getMessage() . "\n";
    }
}
?>
--EXPECTF--
download
download
操作不明确，您是否是要输入下列内容之一：help2、help
help
help2
操作不存在

{"options":{"continue":false,"save-as":"\/mnt\/hdd\/proj\/tool\/minifw\/console\/tests\/OptionParser\/option_cfg.php","retry":0},"input":[]}
{"options":{"continue":true,"save-as":"\/mnt\/hdd\/proj\/tool\/minifw\/console\/tests\/OptionParser\/option_cfg.php","retry":3},"input":["123","456"]}
文件不存在
缺少必要参数:save-as
{"options":{"continue":false,"username":"111","password":"333","save-to":"\/mnt\/hdd\/proj\/tool\/minifw\/console\/tests\/OptionParser","src":"\/mnt\/hdd\/proj\/tool\/minifw\/console\/tests\/OptionParser\/333"},"input":["123","456"]}
目录不存在
{"options":{"continue":false,"rate-limit":50.34,"retry":[111,222,"ffff"],"custom":"custom_value"},"input":["123","456"]}
缺少必要参数:custom
{"options":{"continue":false,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
{"options":{"continue":false,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
{"options":{"continue":true,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
{"options":{"continue":true,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
{"options":{"continue":true,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
{"options":{"continue":false,"rate-limit":0,"retry":0,"custom":"custom_value"},"input":[]}
参数[--custom]不存在
{"options":[],"input":[]}
{"options":[],"input":[]}