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

class Cmd1 extends Command
{
    public static function getConfig() : array
    {
        return [
            'comment' => ['usage: cmd1 bbaba', 'bbaba'],
            'actions' => [
                'act1' => [
                    'comment' => 'act1 ggg',
                    'options' => [
                    ],
                ],
                'act2' => [
                    'comment' => 'act1 ggg',
                    'options' => [
                    ],
                ],
            ],
        ];
    }

    protected function doAct1($options)
    {
        echo json_encode($options) . "\n";
    }

    protected function doAct2($options)
    {
        echo json_encode($options) . "\n";
        echo $this->parser->getManual() . "\n";
    }
}
