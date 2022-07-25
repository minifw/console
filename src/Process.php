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

use Minifw\Common\Exception;

class Process
{
    protected string $cmd;
    protected ?array $env;
    protected ?string $cwd = null;
    protected int $timeout = 0;
    protected string $buffer = '';

    public function __construct(string $cmd, ?string $cwd = null, ?array $env = [])
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd;
        $this->env = $env;
    }

    public function run($stdin = null, $stdout = null, $stderr = null, ?callable $callback = null) : int
    {
        $desc = [];

        if ($stdin !== null) {
            $desc[0] = ['pipe', 'r'];
        }

        if ($callback !== null) {
            if ($stdout !== null || $stderr !== null) {
                throw new Exception('参数不合法');
            }
            $desc[1] = ['pipe',  'w'];
            $desc[2] = ['pipe',  'w'];
        } else {
            if ($stdout !== null) {
                $desc[1] = ['pipe',  'w'];
            } else {
                $desc[1] = ['file', '/dev/null', 'w'];
            }

            if ($stderr !== null) {
                $desc[2] = ['pipe',  'w'];
            } else {
                $desc[2] = ['file', '/dev/null', 'w'];
            }
        }

        $process = proc_open($this->cmd, $desc, $pipes, $this->cwd, $this->env);

        $exitCode = -1;

        if (!is_resource($process)) {
            throw new Exception('进程创建失败');
        }

        if ($stdin !== null) {
            stream_set_blocking($pipes[0], false);
        }
        if ($callback !== null || $stdout !== null) {
            stream_set_blocking($pipes[1], false);
        }
        if ($callback !== null || $stderr !== null) {
            stream_set_blocking($pipes[2], false);
        }

        $btime = time();

        $ended = false;

        while (true) {
            $status = proc_get_status($process);

            if ($stdin !== null && !$ended) {
                $ended = $this->streamCopyBuffered($stdin, $pipes[0]);
            }

            if ($callback === null) {
                if ($stdout !== null) {
                    self::streamCopy($pipes[1], $stdout);
                }
                if ($stderr !== null) {
                    self::streamCopy($pipes[2], $stderr);
                }
            } else {
                self::streamToCallback(1, $pipes[1], $callback);
                self::streamToCallback(2, $pipes[2], $callback);
            }

            if (!$status['running']) {
                $exitCode = $status['exitcode'] ?? -1;
                break;
            }

            $now = time();
            if ($this->timeout > 0 && $now - $btime > $this->timeout) {
                proc_terminate($process, 9);
            }

            usleep(100 * 1000);
        }

        if ($stdin !== null && !$ended) {
            fclose($pipes[0]);
        }
        if ($callback !== null || $stdout !== null) {
            fclose($pipes[1]);
        }
        if ($callback !== null || $stderr !== null) {
            fclose($pipes[2]);
        }

        if ($exitCode == -1) {
            $exitCode = proc_close($process);
        }

        return $exitCode;
    }

    public function exec(int $stream = 1, &$exitCode) : ?string
    {
        $tmpFp = fopen('php://temp', 'r+');

        $stdout = null;
        $stderr = null;

        if ($stream == 1) {
            $stdout = $tmpFp;
        } elseif ($stream == 2) {
            $stderr = $tmpFp;
        } else {
            throw new Exception('参数不合法');
        }

        $exitCode = $this->run(null, $stdout, $stderr, null);

        rewind($tmpFp);
        $string = stream_get_contents($tmpFp);
        fclose($tmpFp);

        return $string;
    }

    ///////////////////////////////

    protected function streamCopyBuffered($from, $to)
    {
        while (true) {
            if (!$this->writeBuffer($to)) {
                return false;
            }

            if (feof($from)) {
                fclose($to);

                return true;
            }

            $msg = fread($from, 1024);

            if ($msg === false) {
                fclose($to);

                return true;
            } elseif ($msg === '') {
                return false;
            }

            $this->buffer = $msg;
        }
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

    protected static function streamCopy($from, $to)
    {
        while (true) {
            $msg = fread($from, 1024);
            if ($msg === false || $msg === '') {
                break;
            }
            fwrite($to, $msg);
        }
    }

    protected static function streamToCallback(int $name, $from, callable $callback)
    {
        while (true) {
            $msg = fread($from, 1024);
            if ($msg === false || $msg === '') {
                break;
            }
            call_user_func($callback, $name, $msg);
        }
    }
}
