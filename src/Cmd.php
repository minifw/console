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

use Minifw\Common\Exception;
use Minifw\Common\FileUtils;

class Cmd
{
    public static function getFullPath(string $path) : string
    {
        $cwd = str_replace('\\', '/', getcwd());
        if ($cwd === false) {
            throw new Exception('获取路径信息失败');
        }

        return FileUtils::pathJoin($cwd, $path);
    }

    //////////////////////////////////////////////////////////

    public static function printTable(array $cols, array $body, array $footer = [])
    {
        $max_len = [];
        $header = [];

        foreach ($cols as $k => $v) {
            $max_len[$k] = strlen($cols[$k]['name']);
            $header[$k] = $v['name'];
        }

        foreach ($footer as $k => $v) {
            if ($max_len[$k] < strlen($footer[$k])) {
                $max_len[$k] = strlen($footer[$k]);
            }
        }

        foreach ($body as $line) {
            foreach ($line as $k => $v) {
                if ($max_len[$k] < strlen($line[$k])) {
                    $max_len[$k] = strlen($line[$k]);
                }
            }
        }

        $line_count = 1;
        foreach ($max_len as $v) {
            $line_count += $v + 3;
        }

        $line_sep = str_repeat('-', $line_count) . "\n";

        self::printLine($cols, $header, $max_len, 'header');

        echo $line_sep;

        foreach ($body as $line) {
            self::printLine($cols, $line, $max_len, 'body');
        }

        echo $line_sep;

        if (!empty($footer)) {
            self::printLine($cols, $footer, $max_len, 'footer');
        }
    }

    public static function printJson($data) : void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    }

    public static function printException(\Exception $ex) : void
    {
        echo '[' . $ex->getCode() . '] ' . $ex->getFile() . '[' . $ex->getLine() . ']: ' . $ex->getMessage() . "\n";
    }

    ///////////////////////////////////////////////////////////

    protected static function printLine(array $cols, array $line, array $maxLen, string $type) : void
    {
        $first = true;
        $str = '';

        foreach ($cols as $name => $col) {
            if (!$first) {
                $str .= ' ';
            }
            $first = false;

            $align = isset($col['align_' . $type]) ? strval($col['align_' . $type]) : $col['align'];

            if ($align == 'left') {
                $pad = STR_PAD_RIGHT;
            } elseif ($align == 'right') {
                $pad = STR_PAD_LEFT;
            } else {
                $pad = STR_PAD_BOTH;
            }

            $str .= '| ' . str_pad($line[$name], $maxLen[$name], ' ', $pad);
        }
        echo $str . " |\n";
    }
}
