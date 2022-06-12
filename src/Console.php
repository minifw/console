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
    /**
     * @var string
     */
    protected $statusLine = '';
    /**
     * @var int
     */
    protected $lastLen = 0;
    /**
     * @var mixed
     */
    protected $stream;
    /**
     * @var mixed
     */
    protected $width;
    /**
     * @var mixed
     */
    protected $isTty;
    /**
     * @var string
     */
    protected $encoding = 'utf-8';

    /**
     * @param $str
     * @return mixed
     */
    public function strWidth($str)
    {
        $raw_len = strlen($str);
        $char_len = mb_strlen($str, $this->encoding);

        $wchars = ($raw_len - $char_len) / 2;

        return $char_len + $wchars;
    }

    /**
     * @param $str
     * @param $max_width
     * @return mixed
     */
    public function strTruncate($str, $max_width)
    {
        while (true) {
            $width = $this->strWidth($str);
            if ($width <= $max_width) {
                break;
            }

            $c = ceil(($width - $max_width) / 2);
            if ($c <= 0) {
                $c = 1;
            }

            $str = mb_substr($str, 0, -1 * $c);
        }

        return $str;
    }

    /**
     * @param $stream
     * @param null $encoding
     */
    public function __construct($stream = null, $encoding = 'utf-8')
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

    /**
     * @return mixed
     */
    public function reset()
    {
        if (!$this->isTty) {
            return $this;
        }

        $this->clearStatus();

        $this->statusLine = '';
        $this->lastLen = 0;

        return $this;
    }

    /**
     * @param $line
     * @return mixed
     */
    public function setStatus($line)
    {
        if (!$this->isTty) {
            return $this;
        }

        $line = $this->strTruncate($line, $this->width);
        $this->statusLine = $line;

        $this->clearStatus();
        $this->showStatus();

        $this->lastLen = $this->strWidth($this->statusLine);

        return $this;
    }

    /**
     * @return mixed
     */
    public function clearStatus()
    {
        if (!$this->isTty) {
            return $this;
        }

        if ($this->lastLen > 0) {
            $tmp = str_repeat(chr(0x08), $this->lastLen) . str_repeat(" ", $this->lastLen) . str_repeat(chr(0x08), $this->lastLen);
            fwrite($this->stream, $tmp);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function showStatus()
    {
        if (!$this->isTty) {
            return $this;
        }

        fwrite($this->stream, "\033[32m" . $this->statusLine . "\033[0m");
        $this->lastLen = $this->strWidth($this->statusLine);
    }

    function print($str) {
        $this->clearStatus();

        if (!$this->isTty) {
            $str = preg_replace("/\033\[\d+m/", '', $str);
        }

        fwrite($this->stream, $str . PHP_EOL);
        $this->showStatus();

        return $this;
    }
}
