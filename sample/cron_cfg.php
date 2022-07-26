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
    'lockfile' => '', //锁文件，防止重复执行
    'datafile' => '', //任务执行数据
    'logfile' => '', //任务日志文件
    'logdays' => 7, //日志文件保存天数
    'groups' => [//分组列表
        'group1' => [ //任务分组，同一组中的任务不会同时执行，不同组的任务会并发执行
            'task1' => [ //任务名称
                'cmd' => 'xxxx', //任务命令行
                'cwd' => null, //工作目录
                'env' => null, //环境变量
                'timeout' => 0, //任务时限
                'schedule' => [
                    '*', //分钟 (0 - 59)
                    '*', //小时 (0 - 23)
                    '*', //一个月中的第几天 (1 - 31)
                    '*', //月份 (1 - 12)
                    '*', //星期中星期几 (0 - 6) (星期天 为0)
                ],
            ],
            'task2' => [
                //...
            ],
        ],
        'group2' => [
            //...
        ]
    ]
];
