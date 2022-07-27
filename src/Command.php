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
    protected string $function;
    protected array $options = [];
    protected array $input = [];

    public function __construct(OptionParser $parser, array $argv = [])
    {
        $this->parser = $parser;

        $info = $this->parser->parse($argv);

        $action = $info['action'];
        $this->options = $info['options'];
        $this->input = $info['input'];

        $this->function = 'do' . ucfirst($action);
        if (!method_exists($this, $this->function)) {
            throw new Exception('操作不存在');
        }

        $this->init($info['global']);
    }

    protected function init(array $global)
    {
    }

    public function run() : void
    {
        call_user_func([$this, $this->function], $this->options, $this->input);
    }

    abstract public static function getConfig() : array;
}
