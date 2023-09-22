<?php

/*
 * Copyright (C) 2022 Yang Ming <yangming0116@163.com>
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
 * along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Minifw\Console\Tests\Command;

use Minifw\Console\Command;
use Minifw\Console\Option;

class Cmd2 extends Command
{
    public static function getConfig() : array
    {
        return [
            'comment' => ['usage: cmd2 bbaba', 'bbaba'],
            'global' => [
                'config' => [
                    'comment' => 'config file',
                    'type' => Option::PARAM_STRING,
                    'default' => '',
                ],
            ],
            'actions' => [
                'act1' => [
                    'comment' => 'act1 ggg',
                    'options' => [
                        'range' => [
                            'default' => [0, 0],
                            'type' => [Option::PARAM_INT, Option::PARAM_INT],
                        ],
                    ],
                ],
                'act2' => [
                    'comment' => 'act1 ggg',
                    'options' => [
                    ],
                ],
                'act-test' => [
                    'comment' => 'act test ttt',
                    'options' => [
                    ],
                ],
            ],
        ];
    }

    protected function init(array $glboal)
    {
        parent::init($glboal);
        echo json_encode($glboal) . "\n";
    }

    protected function doAct1(array $options, array $input)
    {
        echo json_encode($options) . "\n";
        echo json_encode($input) . "\n";
    }

    protected function doAct2(array $options, array $input)
    {
        echo json_encode($options) . "\n";
        echo json_encode($input) . "\n";
        echo $this->parser->getManual() . "\n";
    }

    protected function doActTest(array $options, array $input)
    {
        echo json_encode($options) . "\n";
        echo json_encode($input) . "\n";
    }
}
