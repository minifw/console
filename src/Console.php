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

namespace Minifw\Console;

class Console
{
    protected string $statusLine = '';
    protected int $lastLen = 0;
    protected $stream;
    protected int $width;
    protected bool $isTty;
    protected string $encoding = 'utf-8';

    public function strWidth(string $str) : int
    {
        $raw_len = strlen($str);
        $char_len = mb_strlen($str, $this->encoding);

        $wchars = ($raw_len - $char_len) / 2;

        return $char_len + $wchars;
    }

    public function strTruncate(string $str, int $maxWidth) : string
    {
        while (true) {
            $width = $this->strWidth($str);
            if ($width <= $maxWidth) {
                break;
            }

            $c = ceil(($width - $maxWidth) / 2);
            if ($c <= 0) {
                $c = 1;
            }

            $str = mb_substr($str, 0, -1 * $c);
        }

        return $str;
    }

    public function __construct($stream = null, string $encoding = 'utf-8')
    {
        if ($stream === null) {
            $this->stream = fopen('php://stdout', 'wb');
        } else {
            $this->stream = $stream;
        }
        $this->width = exec('tput cols');
        $this->isTty = (function_exists('posix_isatty') && @posix_isatty($this->stream));
        $this->encoding = $encoding;
    }

    public function reset() : self
    {
        if ($this->isTty) {
            $this->clearStatus();

            $this->statusLine = '';
            $this->lastLen = 0;
        }

        return $this;
    }

    public function setStatus(string $line) : self
    {
        if ($this->isTty) {
            $line = $this->strTruncate($line, $this->width);
            $this->statusLine = $line;

            $this->clearStatus();
            $this->showStatus();

            $this->lastLen = $this->strWidth($this->statusLine);
        }

        return $this;
    }

    public function clearStatus() : self
    {
        if ($this->isTty) {
            if ($this->lastLen > 0) {
                $tmp = str_repeat(chr(0x08), $this->lastLen) . str_repeat(' ', $this->lastLen) . str_repeat(chr(0x08), $this->lastLen);
                fwrite($this->stream, $tmp);
            }
        }

        return $this;
    }

    public function showStatus() : self
    {
        if ($this->isTty) {
            fwrite($this->stream, "\033[32m" . $this->statusLine . "\033[0m");
            $this->lastLen = $this->strWidth($this->statusLine);
        }

        return $this;
    }

    public function print(string $str) : self
    {
        $this->clearStatus();

        if (!$this->isTty) {
            $str = preg_replace("/\033\\[\\d+m/", '', $str);
        }

        fwrite($this->stream, $str . "\n");
        $this->showStatus();

        return $this;
    }
}
