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

require __DIR__ . '/../vendor/autoload.php';

use Minifw\Console\Console;

$console = new Console();
$console->print('123456');
usleep(200 * 1000);
$console->setStatus('000000');
usleep(200 * 1000);
$console->print("\033[32m123456\033[0m");
usleep(200 * 1000);
$console->reset();
