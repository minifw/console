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

return [
    'lockfile' => APP_ROOT . '/tmp/tests/cron/cronlock',
    'datafile' => APP_ROOT . '/tmp/tests/cron/crondata.json',
    'logfile' => APP_ROOT . '/tmp/tests/cron/cronlog.log',
    'logdays' => 7,
    'groups' => [
        'group1' => [
            'task1' => [
                'cmd' => '"' . PHP_BINARY . '" test1.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 0,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
        ],
        'group2' => [
            'task1' => [
                'cmd' => '"' . PHP_BINARY . '" test2.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 2,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
        ],
        'group3' => [
            'task1' => [
                'cmd' => '"' . PHP_BINARY . '" test3.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 0,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
        ],
        'group4' => [
            'task1' => [
                'cmd' => '"' . PHP_BINARY . '" test4.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 0,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
            'task2' => [
                'cmd' => '"' . PHP_BINARY . '" test5.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 0,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
            'task3' => [
                'cmd' => '"' . PHP_BINARY . '" test6.php',
                'cwd' => __DIR__ . '/php',
                'timeout' => 0,
                'schedule' => ['*', '*', '*', '*', '*'],
            ],
        ],
    ]
];
