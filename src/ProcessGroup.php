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

namespace Minifw\Console;

use Closure;
use Exception;

class ProcessGroup
{
    /**
     * @var array<Process>
     */
    protected array $list = [];
    protected ?Closure $callback = null;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function addProcess(Process $process)
    {
        $name = $process->getName();
        if ($name === '') {
            throw new Exception('必须指定进程名称');
        }
        $this->list[$name] = $process;

        $process->setCallback($this->callback)->start();
    }

    public function doLoop() : ?array
    {
        if (empty($this->list)) {
            return null;
        }

        $exitCode = [];

        foreach ($this->list as $name => $process) {
            $process->doLoop();
            if (!$process->isRunning()) {
                $exitCode[$name] = $process->getExitCode();
                unset($this->list[$name]);
            }
        }

        return $exitCode;
    }
}
