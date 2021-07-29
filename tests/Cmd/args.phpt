--TEST--
Cmd args test
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Minifw\Console\Cmd;
use Minifw\Common\Exception;

$actions = [
    'find' => '',
    'remove' => '',
    'add' => '',
    'act1' => '',
    'act2' => '',
];

$args_list = [
    ['find', '111', '222'],
    ['r', '111', '222'],
    ['ad', '111', '222'],
    ['act', '111', '222'],
    ['act2', '111', '222'],
];

foreach ($args_list as $args) {
    try {
        $action = Cmd::get_action($args, $actions);
        var_dump($action);
    }
    catch (Exception $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}


$test_list = [
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string'],
            ],
        ],
        'argv' => ['-id', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string'],
            ],
        ],
        'argv' => ['--name', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string'],
            ],
        ],
        'argv' => ['-s', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string'],
            ],
        ],
        'argv' => [],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'require' => true,
                'alias' => ['s'],
                'params' => ['string'],
            ],
        ],
        'argv' => [],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['int'],
            ],
        ],
        'argv' => ['--name', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => false,
                'alias' => ['s'],
                'params' => ['bool'],
            ],
        ],
        'argv' => ['--name', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['dir'],
            ],
        ],
        'argv' => ['--name', '/tmp/123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['file'],
            ],
        ],
        'argv' => ['--name', '/tmp/123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string', 'string'],
            ],
        ],
        'argv' => ['--name', '123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => ['string', 'string'],
            ],
        ],
        'argv' => ['--name', '123', '456'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'alias' => ['s'],
                'params' => [['type'=>'array', 'data_type'=>'int']],
            ],
             'id' => [
                'default' => null,
                'params' => ['int'],
            ],
        ],
        'argv' => ['--name', '123', '456', '789', '--id','123'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'require' => true,
                'alias' => ['s'],
                'params' => [['type'=>'string', 'default' => 'abc']],
            ],
        ],
        'argv' => ['-s'],
    ],
    [
        'cfg' => [
            'name' => [
                'default' => null,
                'require' => true,
                'alias' => ['s'],
                'params' => [['type'=>'string', 'default' => 'abc']],
            ],
            'id' => [
                'default' => null,
                'require' => true,
                'alias' => ['id'],
                'params' => ['string'],
            ],
        ],
        'argv' => ['-id','123','-s'],
    ],
];

foreach ($test_list as $test) {
    try {
        $result = Cmd::get_args($test['argv'], $test['cfg']);
        var_dump($result);
    }
    catch (Exception $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}
?>
--EXPECTF--
string(4) "find"
string(6) "remove"
string(3) "add"
操作不明确，您是否是要输入下列内容之一：act1、act2
string(4) "act2"
参数[id]不存在
array(1) {
  ["name"]=>
  string(3) "123"
}
array(1) {
  ["name"]=>
  string(3) "123"
}
array(1) {
  ["name"]=>
  NULL
}
缺少必要参数[name]
array(1) {
  ["name"]=>
  int(123)
}
array(1) {
  ["name"]=>
  bool(true)
}
array(1) {
  ["name"]=>
  string(8) "/tmp/123"
}
array(1) {
  ["name"]=>
  string(8) "/tmp/123"
}
缺少必要参数[name]
array(1) {
  ["name"]=>
  array(2) {
    [0]=>
    string(3) "123"
    [1]=>
    string(3) "456"
  }
}
array(2) {
  ["name"]=>
  array(3) {
    [0]=>
    int(123)
    [1]=>
    int(456)
    [2]=>
    int(789)
  }
  ["id"]=>
  int(123)
}
array(1) {
  ["name"]=>
  string(3) "abc"
}
array(2) {
  ["name"]=>
  string(3) "abc"
  ["id"]=>
  string(3) "123"
}
