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

namespace Minifw\Console;

use Exception;

abstract class Command
{
    protected OptionParser $parser;

    public function __construct(OptionParser $parser)
    {
        $this->parser = $parser;
    }

    public function run($argv)
    {
        $action = array_shift($argv);

        $action = $this->parser->getAction($action);
        $options = $this->parser->getOptions($action, $argv);

        $function = 'do' . ucfirst($action);
        if (!method_exists($this, $function)) {
            throw new Exception('操作不存在');
        }

        call_user_func([$this, $function], $options);
    }

    abstract public static function getConfig() : array;
}
