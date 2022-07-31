<?php
use Minifw\Console\Option;

return [
    'oppositePrefix' => 'no-',
    'comment' => ['usage: tool [action] [options] urls ...', '网络工具'],
    'template' => [
        'user-list' => ['alias' => 'ul', 'comment' => '用户列表', 'default' => [], 'type' => Option::PARAM_ARRAY, 'dataType' => Option::PARAM_STRING],
        'username' => ['alias' => 'u', 'default' => '', 'type' => Option::PARAM_STRING],
        'password' => ['alias' => 'p', 'comment' => '密码', 'default' => '', 'type' => Option::PARAM_STRING],
        'continue' => ['alias' => 'c', 'comment' => ['断点续传', '如果指定则会续传'], 'default' => false, 'type' => Option::PARAM_BOOL],
        'retry' => ['alias' => 'r', 'comment' => '重试次数', 'default' => 0, 'type' => Option::PARAM_INT],
        'save-to' => ['alias' => ['s', 'w'], 'comment' => '保存目录', 'default' => '', 'type' => Option::PARAM_DIR],
        'save-as' => ['alias' => 'sa', 'comment' => '保存文件名', 'default' => '', 'type' => Option::PARAM_FILE],
        'src' => ['alias' => 'f', 'comment' => '源路径', 'default' => '', 'type' => Option::PARAM_PATH],
    ],
    'global' => [
        'continue',
        'rate-limit' => ['alias' => 'l', 'comment' => '带宽限制', 'default' => null, 'type' => Option::PARAM_NUMBER],
    ],
    'actions' => [
        'download' => ['comment' => ['下载', '可以指定多个URL'], 'options' => [
            'save-as' => ['use' => 'save-as', 'default' => null],
            'retry' => ['use' => 'retry'],
        ]],
        'upload' => ['comment' => '上传', 'options' => [
            'username' => ['use' => 'username', 'default' => null],
            'password' => ['use' => 'password', 'default' => null],
            'save-to',
            'src',
        ]],
        'sync' => ['options' => [
            'user-list',
            'retry' => ['use' => 'retry', 'type' => [Option::PARAM_INT, Option::PARAM_INT, Option::PARAM_STRING]],
            'custom' => ['alias' => 'tom', 'comment' => '同步方式', 'type' => Option::PARAM_CUSTOM, 'filter' => function (array &$argv) {
                return 'custom_value';
            }],
        ]],
        'help2' => [
            'comment' => '帮助',
        ],
        'help' => [
            'comment' => '帮助',
        ],
    ]
];
