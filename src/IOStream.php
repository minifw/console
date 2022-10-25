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

class IOStream
{
    protected array $queue = [];
    protected bool $closed = false;

    public function __construct()
    {
    }

    public function write(string $data) : bool
    {
        if ($this->closed) {
            return false;
        }
        $this->queue[] = $data;

        return true;
    }

    public function push(string $data) : int
    {
        return array_unshift($this->queue, $data);
    }

    public function read() : string
    {
        $msg = implode('', $this->queue);
        $this->queue = [];

        return $msg;
    }

    public function close() : void
    {
        $this->closed = true;
    }

    public function isClosed() : bool
    {
        if (!empty($this->queue)) {
            return false;
        }

        return ($this->closed == true);
    }
}
