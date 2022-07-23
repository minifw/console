<?php

/*
 * Copyright (C) 2021 Yang Ming <yangming0116@163.com>.
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

const ARGS_CFG = [
    'name' => [
        'default' => null,
        'alias' => ['s'],
        'params' => ['string'],
    ],
    'id' => [
        'default' => null,
        'require' => true,
        'alias' => ['id'],
        'params' => ['int'],
    ],
    'path' => [
        'default' => null,
        'alias' => ['p'],
        'params' => ['dir'],
    ],
    'cn' => [
        'default' => null,
        'alias' => ['cn'],
        'params' => [['type' => 'bool', 'default' => true]],
    ],
    'add' => [
        'default' => [],
        'alias' => ['a'],
        'params' => [['type' => 'array', 'data_type' => 'int']],
    ],
    'lock' => [
        'default' => [],
        'alias' => ['l'],
        'params' => ['int', 'int'],
    ],
];
