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
    const MAX_LOOP = 5;
    protected IOStream $stdin;
    protected IOStream $stdout;
    protected IOStream $stderr;

    public function __construct(string $cmd, ?string $cwd = null, ?array $env = [])
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->stdin = new IOStream();
        $this->stdout = new IOStream();
        $this->stderr = new IOStream();
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
        $this->stdin->write($input);
        if ($finished) {
            $this->stdin->close();
        }

        return $this;
    }

    public function getStdout() : string
    {
        return $this->stdout->read();
    }

    public function getStderr() : string
    {
        return $this->stderr->read();
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

    public function setCallback(?Closure $callback) : self
    {
        $this->callback = $callback;

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

        return $this;
    }

    public function doLoop() : self
    {
        if (!$this->running) {
            throw new Exception('程序未启动');
        }

        $status = proc_get_status($this->process);
        $isProcessing = false;

        if (!$this->stdin->isClosed()) {
            $this->streamToIn($this->pipes[0], $this->stdin);
        }

        if ($this->callback !== null) {
            if ($this->streamToCallback(1, $this->pipes[1], $this->callback)) {
                $isProcessing = true;
            }
            if ($this->streamToCallback(2, $this->pipes[2], $this->callback)) {
                $isProcessing = true;
            }
        } else {
            if ($this->outToStream($this->pipes[1], $this->stdout)) {
                $isProcessing = true;
            }
            if ($this->outToStream($this->pipes[2], $this->stderr)) {
                $isProcessing = true;
            }
        }

        if (!$status['running']) {
            if (!$isProcessing) {
                $this->exitCode = $status['exitcode'] ?? -1;

                if (!$this->stdin->isClosed()) {
                    fclose($this->pipes[0]);
                    $this->stdin->close();
                }

                fclose($this->pipes[1]);
                fclose($this->pipes[2]);

                $closeCode = proc_close($this->process);

                if ($this->exitCode == -1) {
                    $this->exitCode = $closeCode;
                }

                $this->running = false;
            }
        } else {
            $now = time();
            if ($this->timeout > 0 && $now - $this->btime > $this->timeout) {
                proc_terminate($this->process, 9);
            }
        }

        usleep(1);

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
            usleep(1);
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

        return $result;
    }

    ///////////////////////////////

    protected function streamToCallback(int $stream, $from, callable $callback) : bool
    {
        for ($i = 0; $i < self::MAX_LOOP; $i++) {
            $msg = fread($from, 8192);
            if ($msg === false || $msg === '') {
                return false;
            }
            call_user_func($callback, $this->name, $stream, $msg);
        }

        return true;
    }

    protected function streamToIn($pipe, IOStream $in) : bool
    {
        $input = $in->read();
        $dataLen = strlen($input);

        for ($i = 0; $i < self::MAX_LOOP; $i++) {
            $writeLen = fwrite($this->pipes[0], $input);

            if ($writeLen <= 0) {
                return true;
            }

            if ($dataLen > $writeLen) {
                $input = substr($input, $dataLen - $writeLen);
                $dataLen = $dataLen - $writeLen;
            } else {
                $dataLen = 0;
                $input = '';
                break;
            }
        }

        if ($dataLen > 0) {
            $in->push($input);

            return true;
        }

        if ($in->isClosed()) {
            fclose($pipe);
        }

        return false;
    }

    protected function outToStream($pipe, IOStream $out)
    {
        for ($i = 0; $i < self::MAX_LOOP; $i++) {
            $msg = fread($pipe, 8192);
            if ($msg === false || $msg === '') {
                return;
            }
            $out->write($msg);
        }
    }
}
