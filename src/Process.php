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
use Minifw\Common\Exception;

class Process
{
    protected string $name = '';
    protected string $cmd;
    protected ?array $env;
    protected ?string $cwd = null;
    protected int $timeout = 0;
    protected int $btime = 0;
    protected $process;
    protected ?array $pipes = null;
    protected ?Closure $callback = null;
    protected bool $running = false;
    protected ?int $exitCode = null;
    protected bool $inputEnd = false;
    const MAX_LOOP = 5;

    public function __construct(string $cmd, ?string $cwd = null, ?array $env = [])
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd;
        $this->env = $env;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function addInput(string $input, bool $finished = false) : self
    {
        if (!$this->running) {
            throw new Exception('程序未启动');
        }

        if ($this->inputEnd) {
            throw new Exception('输入已结束');
        }

        while (true) {
            $dataLen = strlen($input);
            $writeLen = fwrite($this->pipes[0], $input);
            if ($writeLen <= 0) {
                throw new Exception('输入出错');
            }

            if ($dataLen > $writeLen) {
                $input = substr($input, $dataLen - $writeLen);
                $dataLen = $dataLen - $writeLen;
            } else {
                break;
            }
        }

        if ($finished) {
            $this->inputEnd = true;
            fclose($this->pipes[0]);
        }

        return $this;
    }

    public function getStdout() : string
    {
        if ($this->pipes === null) {
            throw new Exception('程序未启动');
        }

        return stream_get_contents($this->pipes[1]);
    }

    public function getStderr() : string
    {
        if ($this->pipes === null) {
            throw new Exception('程序未启动');
        }

        return stream_get_contents($this->pipes[2]);
    }

    public function getExitCode() : int
    {
        if ($this->exitCode === null) {
            throw new Exception('程序未结束');
        }

        return $this->exitCode;
    }

    public function setTimeout(int $timeout) : self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function isRunning() : bool
    {
        return ($this->running == true);
    }

    public function start() : self
    {
        if ($this->running || $this->pipes !== null) {
            throw new Exception('程序执行中');
        }
        $this->running = true;

        $desc = [
            ['pipe', 'r'],
            ['pipe',  'w'],
            ['pipe',  'w'],
        ];

        $this->process = proc_open($this->cmd, $desc, $pipes, $this->cwd, $this->env);

        $this->pipes = $pipes;

        if (!is_resource($this->process)) {
            throw new Exception('进程创建失败');
        }

        stream_set_blocking($this->pipes[0], false);
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        $this->btime = time();
        $this->inputFinished = false;

        return $this;
    }

    public function finish() : self
    {
        if ($this->pipes === null) {
            throw new Exception('程序未启动');
        }

        if (!$this->inputEnd) {
            fclose($this->pipes[0]);
            $this->inputEnd = true;
        }
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);

        $closeCode = proc_close($this->process);

        if ($this->exitCode == -1) {
            $this->exitCode = $closeCode;
        }

        return $this;
    }

    public function doLoop() : self
    {
        if (!$this->running) {
            throw new Exception('程序未启动');
        }

        $status = proc_get_status($this->process);
        $isProcessing = false;

        if (!$isProcessing && !$status['running']) {
            $this->exitCode = $status['exitcode'] ?? -1;
            $this->running = false;
        } else {
            $now = time();
            if ($this->timeout > 0 && $now - $this->btime > $this->timeout) {
                proc_terminate($this->process, 9);
            } elseif ($this->callback !== null) {
                if ($this->streamToCallback(1, $this->pipes[1], $this->callback)) {
                    $isProcessing = true;
                }
                if ($this->streamToCallback(2, $this->pipes[2], $this->callback)) {
                    $isProcessing = true;
                }
            }
        }

        usleep(1);

        return $this;
    }

    public function setCallback(?Closure $callback) : self
    {
        $this->callback = $callback;

        return $this;
    }

    public function run() : self
    {
        $this->start();
        while (true) {
            $this->doLoop();
            if (!$this->running) {
                break;
            }
            usleep(100);
        }

        return $this;
    }

    public function exec($stream = 1) : string
    {
        $this->start();
        while (true) {
            $this->doLoop();
            if (!$this->running) {
                break;
            }
            usleep(100);
        }
        if ($stream == 1) {
            $result = $this->getStdout();
        } else {
            $result = $this->getStderr();
        }

        $this->finish();

        return $result;
    }

    ///////////////////////////////

    protected function streamToCallback(int $stream, $from, callable $callback)
    {
        for ($i = 0; $i < self::MAX_LOOP; $i++) {
            $msg = fread($from, 8192);
            if ($msg === false || $msg === '') {
                return;
            }
            call_user_func($callback, $this->name, $stream, $msg);
        }
    }
}
