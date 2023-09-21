--TEST--
OptionParser test
--FILE--
<?php
use Minifw\Common\Exception;
use Minifw\Console\OptionParser;

require __DIR__ . '/../bootstrap.php';

$cfg = require(__DIR__ . '/multi_action.php');

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
    ['download', '-sa', 'multi_action.php'],
    ['download', '-sa', 'multi_action.php', '-l', '0'],
    ['-l', '0', 'download', '-sa', 'multi_action.php', '--retry', '3', '-c', '--', '123', '456'],
    ['-l', '0', 'download', '-sa', 'multi_action.php1'],
    ['-l', '0', 'download', '--retry', '3', '-c', '123', '456'],
    ['-l', '0', 'upload', '--username', '111', '-p', '333', '--save-to', __DIR__, '--src', __DIR__ . '/333', '--', '123', '456'],
    ['-l', '0', 'upload', '--username', '111', '-p', '333', '--save-to', __DIR__ . '/444', '--src', __DIR__ . '/333', '--', '123', '456'],
    ['-l', '0', 'sync', '--custom', '--retry',  '111', '222', 'ffff', '--rate-limit', '50.34', '--', '123', '456'],
    ['-l', '0', 'sync', '--retry',  '111', '222', 'ffff', '--rate-limit', '50.34', '--', '123', '456'],
    ['-l', '0', 'sync', '--custom', '-no-c'],
    ['-l', '0', 'sync', '--custom', '-c'],
    ['-l', '0', 'sync', '-ul', '1', '2', '3', '4', '--custom', '-c', '--type', 'two'],
    ['-l', '0', 'sync', '--custom', '-c', '-ul', '1', '2', '3', '4', '--type', 'three'],
    ['-l', '0', 'help', '--custom', '-c'],
    ['-l', '0', 'help'],
    ['-l', '0', 'help2'],
    ['-l', '0', 'sync', '--rate-limit', '-50.34', '--retry',  '-3', '1', '-5', '--custom'],
];

foreach ($input as $value) {
    try {
        echo json_encode($parser->parse($value), JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Exception $ex) {
        echo $ex->getMessage() . "\n";
    }
}

echo "\n";

echo $parser->getManual() . "\n";

$cfg = require(__DIR__ . '/single_action.php');
$parser = new OptionParser($cfg);

$input = [
    ['download', '-sa', 'multi_action.php', '-l', '0'],
    ['-sa', 'multi_action.php'],
];

foreach ($input as $value) {
    try {
        echo json_encode($parser->parse($value), JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Exception $ex) {
        echo $ex->getMessage() . "\n";
    }
}

echo "\n";

echo $parser->getManual() . "\n";
?>
--EXPECTF--
download
download
操作不明确，您是否是要输入下列内容之一：help2、help
help
help2
操作不存在

缺少必要选项: --rate-limit
{"action":"download","options":{"save-as":"%s\/tests\/OptionParser\/multi_action.php","retry":0},"global":{"continue":false,"rate-limit":0},"input":[]}
{"action":"download","options":{"save-as":"%s\/tests\/OptionParser\/multi_action.php","retry":3},"global":{"continue":true,"rate-limit":0},"input":["123","456"]}
文件不存在
缺少必要选项: --save-as
{"action":"upload","options":{"username":"111","password":"333","save-to":"%s\/tests\/OptionParser","src":"%s\/tests\/OptionParser\/333"},"global":{"continue":false,"rate-limit":0},"input":["123","456"]}
目录不存在
{"action":"sync","options":{"user-list":[],"retry":[111,222,"ffff"],"custom":"custom_value","type":"one"},"global":{"continue":false,"rate-limit":50.34},"input":["123","456"]}
缺少必要选项: --custom
{"action":"sync","options":{"user-list":[],"retry":0,"custom":"custom_value","type":"one"},"global":{"continue":false,"rate-limit":0},"input":[]}
{"action":"sync","options":{"user-list":[],"retry":0,"custom":"custom_value","type":"one"},"global":{"continue":true,"rate-limit":0},"input":[]}
{"action":"sync","options":{"user-list":["1","2","3","4"],"retry":0,"custom":"custom_value","type":"two"},"global":{"continue":true,"rate-limit":0},"input":[]}
{"action":"sync","options":{"user-list":["1","2","3","4"],"retry":0,"custom":"custom_value","type":"three"},"global":{"continue":true,"rate-limit":0},"input":[]}
选项不存在: --custom
{"action":"help","options":[],"global":{"continue":false,"rate-limit":0},"input":[]}
{"action":"help2","options":[],"global":{"continue":false,"rate-limit":0},"input":[]}
{"action":"sync","options":{"user-list":[],"retry":[-3,1,"-5"],"custom":"custom_value","type":"one"},"global":{"continue":false,"rate-limit":-50.34},"input":[]}

usage: tool [action] [options] urls ...
网络工具

全局选项:
--continue | -c: false
--no-continue | -no-c
    断点续传
    如果指定则会续传
--rate-limit | -l: number => null
    带宽限制

download:
    下载
    可以指定多个URL

    input: [string, ...] => null
    要下载的URL

    --save-as | -sa: file => null
        保存文件名
    --retry | -r: int => 0
        重试次数

upload:
    上传

    --username | -u: string => null
    --password | -p: string => null
        密码
    --save-to | -s | -w: dir => ""
        保存目录
    --src | -f: path => ""
        源路径

sync:

    --user-list | -ul: [string, ...] => []
        用户列表
    --retry | -r: [int, int, string] => [0]
        重试次数
    --custom | -tom
        同步方式
    --type: one | two | three => one
        同步逻辑

help2:
    帮助

help:
    帮助

{"action":"download","options":{"user-list":[],"username":"","password":"","continue":false,"retry":0,"save-to":"","save-as":"","src":"","rate-limit":0},"global":[],"input":["download","-sa","multi_action.php","-l","0"]}
{"action":"download","options":{"user-list":[],"username":"","password":"","continue":false,"retry":0,"save-to":"","save-as":"\/mnt\/hdd\/proj\/tool\/minifw\/console\/tests\/OptionParser\/multi_action.php","src":"","rate-limit":0},"global":[],"input":[]}

usage: tool [action] [options] urls ...
网络工具

--user-list | -ul: [string, ...] => []
    用户列表
--username | -u: string => ""
    用户名
--password | -p: string => ""
    密码
--continue | -c: false
--no-continue | -no-c
    断点续传
    如果指定则会续传
--retry | -r: int => 0
    重试次数
--save-to | -s | -w: dir => ""
    保存目录
--save-as | -sa: file => ""
    保存文件名
--src | -f: path => ""
    源路径
--rate-limit | -l: number => 0
    带宽限制