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

class Console {

    protected $status_line = '';
    protected $last_len = 0;
    protected $stream;
    protected $width;
    protected $is_tty;
    protected $encoding = 'utf-8';

    public function str_width($str) {
        $raw_len = strlen($str);
        $char_len = mb_strlen($str, $this->encoding);

        $wchars = ($raw_len - $char_len) / 2;

        return $char_len + $wchars;
    }

    public function str_truncate($str, $max_width) {
        while (true) {
            $width = $this->str_width($str);
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

    public function __construct($stream = null, $encoding = 'utf-8') {
        if ($stream === null) {
            $this->stream = fopen('php://stdout', 'wb');
        }
        else {
            $this->stream = $stream;
        }
        $this->width = exec('tput cols');
        $this->is_tty = (function_exists('posix_isatty') && @posix_isatty($this->stream));
        $this->encoding = $encoding;
    }

    public function reset() {
        if (!$this->is_tty) {
            return $this;
        }

        $this->clear_status();

        $this->status_line = '';
        $this->last_len = 0;

        return $this;
    }

    public function set_status($line) {
        if (!$this->is_tty) {
            return $this;
        }

        $line = $this->str_truncate($line, $this->width);
        $this->status_line = $line;

        $this->clear_status();
        $this->show_status();

        $this->last_len = $this->str_width($this->status_line);

        return $this;
    }

    public function clear_status() {
        if (!$this->is_tty) {
            return $this;
        }

        if ($this->last_len > 0) {
            $tmp = str_repeat(chr(0x08), $this->last_len) . str_repeat(" ", $this->last_len) . str_repeat(chr(0x08), $this->last_len);
            fwrite($this->stream, $tmp);
        }

        return $this;
    }

    public function show_status() {
        if (!$this->is_tty) {
            return $this;
        }

        fwrite($this->stream, "\033[32m" . $this->status_line . "\033[0m");
        $this->last_len = $this->str_width($this->status_line);
    }

    public function print($str) {
        $this->clear_status();

        if (!$this->is_tty) {
            $str = preg_replace("/\033\[\d+m/", '', $str);
        }

        fwrite($this->stream, $str . PHP_EOL);
        $this->show_status();

        return $this;
    }

}
