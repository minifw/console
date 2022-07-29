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
    protected string $buffer = '';
    protected bool $running = false;
    protected $process;
    protected array $pipes;
    protected ?Closure $callback = null;
    protected $stdin = null;
    protected $stdout = null;
    protected $stderr = null;
    protected $btime = 0;
    protected $inputFinished = false;

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

    public function setStdin($stdin) : self
    {
        $this->stdin = $stdin;

        return $this;
    }

    public function setStdout($stdout) : self
    {
        $this->stdout = $stdout;

        return $this;
    }

    public function setStderr($stderr) : self
    {
        $this->stderr = $stderr;

        return $this;
    }

    public function setCallback(?Closure $callback = null) : self
    {
        if ($callback !== null) {
            $this->callback = $callback;
        } else {
            $this->callback = null;
        }

        return $this;
    }

    public function setTimeout(int $timeout) : self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function start() : void
    {
        if ($this->running) {
            throw new Exception('程序执行中');
        }
        $this->running = true;

        if ($this->stdin !== null) {
            $desc[0] = ['pipe', 'r'];
        }

        if ($this->callback !== null) {
            if ($this->stdout !== null || $this->stderr !== null) {
                throw new Exception('参数不合法');
            }
            $desc[1] = ['pipe',  'w'];
            $desc[2] = ['pipe',  'w'];
        } else {
            if ($this->stdout !== null) {
                $desc[1] = ['pipe',  'w'];
            } else {
                $desc[1] = ['file', '/dev/null', 'w'];
            }

            if ($this->stderr !== null) {
                $desc[2] = ['pipe',  'w'];
            } else {
                $desc[2] = ['file', '/dev/null', 'w'];
            }
        }

        $this->process = proc_open($this->cmd, $desc, $pipes, $this->cwd, $this->env);

        $this->pipes = $pipes;

        if (!is_resource($this->process)) {
            throw new Exception('进程创建失败');
        }

        if ($this->stdin !== null) {
            stream_set_blocking($this->stdin, false);
            stream_set_blocking($this->pipes[0], false);
        }
        if ($this->callback !== null) {
            stream_set_blocking($this->pipes[1], false);
            stream_set_blocking($this->pipes[2], false);
        } else {
            if ($this->stdout !== null) {
                stream_set_blocking($this->pipes[1], false);
                stream_set_blocking($this->stdout, true);
            }

            if ($this->stderr !== null) {
                stream_set_blocking($this->pipes[2], false);
                stream_set_blocking($this->stderr, true);
            }
        }

        $this->btime = time();
        $this->inputFinished = false;
    }

    public function doLoop() : ?int
    {
        if (!$this->running) {
            throw new Exception('程序未启动');
        }

        $status = proc_get_status($this->process);

        if ($this->stdin !== null && !$this->inputFinished) {
            $this->inputFinished = $this->streamCopyBuffered($this->stdin, $this->pipes[0]);
        }

        if ($this->callback === null) {
            if ($this->stdout !== null) {
                $this->streamCopy($this->pipes[1], $this->stdout);
            }
            if ($this->stderr !== null) {
                $this->streamCopy($this->pipes[2], $this->stderr);
            }
        } else {
            $this->streamToCallback(1, $this->pipes[1], $this->callback);
            $this->streamToCallback(2, $this->pipes[2], $this->callback);
        }

        if (!$status['running']) {
            $exitCode = $status['exitcode'] ?? -1;

            if ($this->stdin !== null && !$this->inputFinished) {
                fclose($this->pipes[0]);
            }
            if ($this->callback !== null || $this->stdout !== null) {
                fclose($this->pipes[1]);
            }
            if ($this->callback !== null || $this->stderr !== null) {
                fclose($this->pipes[2]);
            }

            if ($exitCode == -1) {
                $exitCode = proc_close($this->process);
            }

            $this->running = false;

            return $exitCode;
        }

        $now = time();
        if ($this->timeout > 0 && $now - $this->btime > $this->timeout) {
            proc_terminate($this->process, 9);
        }

        return null;
    }

    public function run() : int
    {
        $this->start();
        while (true) {
            $code = $this->doLoop();
            if ($code !== null) {
                return $code;
            }
            usleep(100);
        }
    }

    public function exec(int $stream = 1, &$exitCode) : ?string
    {
        $tmpFp = fopen('php://temp', 'r+');

        try {
            $stdout = null;
            $stderr = null;

            if ($stream == 1) {
                $stdout = $tmpFp;
            } elseif ($stream == 2) {
                $stderr = $tmpFp;
            } else {
                throw new Exception('参数不合法');
            }

            $this->setStdout($stdout)
                ->setStderr($stderr)
                ->setCallback(null)
                ->setStdin(null);

            $exitCode = $this->run();

            rewind($tmpFp);
            $string = stream_get_contents($tmpFp);

            return $string;
        } finally {
            fclose($tmpFp);
        }
    }

    ///////////////////////////////

    protected function streamCopyBuffered($from, $to)
    {
        if (!$this->writeBuffer($to)) {
            return false;
        }

        if (feof($from)) {
            fclose($to);

            return true;
        }

        $msg = fread($from, 8192);

        if ($msg === false) {
            fclose($to);

            return true;
        } elseif ($msg === '') {
            return false;
        }

        $this->buffer = $msg;
    }

    protected function writeBuffer($to) : bool
    {
        $len = strlen($this->buffer);
        if ($len <= 0) {
            return true;
        }

        while (true) {
            $writeLen = fwrite($to, $this->buffer);
            if ($writeLen <= 0) {
                return false;
            }
            if ($len > $writeLen) {
                $this->buffer = substr($this->buffer, $len - $writeLen);
                $len = $len - $writeLen;
            } else {
                $this->buffer = '';

                return true;
            }
        }
    }

    protected function streamCopy($from, $to)
    {
        $msg = fread($from, 8192);
        if ($msg === false || $msg === '') {
            return;
        }
        fwrite($to, $msg);
    }

    protected function streamToCallback(int $stream, $from, callable $callback)
    {
        $msg = fread($from, 8192);
        if ($msg === false || $msg === '') {
            return;
        }
        call_user_func($callback, $this->name, $stream, $msg);
    }
}
