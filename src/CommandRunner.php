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

class CommandRunner
{
    protected string $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    public function run($argv) : void
    {
        try {
            $classname = array_shift($argv);

            if ($classname === null) {
                throw new Exception('缺少必要参数');
            }
            $classname = $this->namespace . '\\' . ucfirst($classname);
            if (!class_exists($classname)) {
                throw new Exception('对象不存在');
            }

            if (!is_subclass_of($classname, __NAMESPACE__ . '\\Command')) {
                throw new Exception('对象不合法');
            }

            $cfg = $classname::getConfig();
            $parser = new OptionParser($cfg);
            $obj = new $classname($parser, $argv);

            set_time_limit(0);
            $obj->run();
        } catch (Exception $ex) {
            Utils::printException($ex);
            exit($ex->getCode());
        }
    }
}
