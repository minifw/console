<?php
/*
 * Copyright (C) 2022 Yang Ming <yangming0116@163.com>.
 *
 * This library is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library.  If not, see <https://www.gnu.org/licenses/>.
 */
use Minifw\Console\Option;

return [
    'oppositePrefix' => 'no-', //对于bool类型的选项，添加该前缀会将其值设置为false，例如 --no-auto-redirect 或 -no-p
    'comment' => '程序说明', //程序说明
    'template' => [//公共选项定义，之后可以在操作定义中引用
        'id-list' => [ //选项1定义使用时用`--`作为前缀
            'alias' => 'id', //选项别名，使用时用`-`作为前缀，存在多个时使用数组代替
            'comment' => 'id列表', //选项说明
            'default' => [], //选项默认值,如果默认值不存在或者为null,则该选项为必须指定的选项
            'type' => Option::PARAM_ARRAY, //选项参数的类型,如果存在多个参数，则使用数组代替，如果是不定个数的参数，则使用`PARAM_ARRAY`
            'paramValues' => [], //当参数类型是Optoin::PARAM_ENUM时，这里指定一系列合法的参数值
            'dataType' => Option::PARAM_INT, //参数不定个数时，在这里指定参数的类型
            'filter' => null, //当参数类型是Optoin::PARAM_CUSTOM时，这里指定一个函数 `callback(array &$argv):$output`,以该函数返回的内容作为结果
        ],
        'password' => [ //选项2定义
            //...
        ]
    ],
    'global' => [ //全局选项
        'url' => [ //选项1定义
            //...
        ],
        'username', //使用公共选项
        'email' => [
            'use' => 'username', //使用公共选项
        ],
        'password' => [
            'use' => 'password',
            'comment' => 'xxxxx', //使用公共选项时可以在这里覆盖公共选项的某些属性
            'alias' => ['p', 'pwd'],
        ],
    ],
    'actions' => [ //操作列表
        'download' => [ //操作1的定义
            'comment' => '下载文件', //操作说明
            'input' => [//操作数据
                'comment' => 'id列表', //数据说明
                'type' => Option::PARAM_ARRAY, //数据的类型，如果存在多个数据，则使用数组代替，如果是不定个数的数据，则使用 `PARAM_ARRAY`
                'dataType' => Option::PARAM_INT, //数据不定个数时，在这里指定数据的类型
                'filter' => null, //当数据类型是 Optoin::PARAM_CUSTOM 时，这里指定一个函数 `callback(array &$argv):$output`,以该函数返回的内容作为结果
                'paramValues' => [], //当参数类型是Optoin::PARAM_ENUM时，这里指定一系列合法的参数值
                'default' => [], //选项默认值,如果默认值不存在或者为null,则该选项为必须指定的选项
            ],
            'options' => [ //选项列表
                'url' => [ //选项1定义
                    //...
                ],
                'username', //使用公共选项
                'email' => [
                    'use' => 'username', //使用公共选项
                ],
                'password' => [
                    'use' => 'password',
                    'comment' => 'xxxxx', //使用公共选项时可以在这里覆盖公共选项的某些属性
                    'alias' => ['p', 'pwd'],
                ],
            ],
        ],
        'upload' => [ //操作2的定义
            //...
        ]
    ]
];
