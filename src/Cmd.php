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
    /**
     * @var mixed
     */
    public static $cwd;

    /**
     * @param $array
     * @param $cfg
     * @return mixed
     */
    public static function getOneParam(&$array, $cfg = '')
    {
        $val = array_shift($array);

        $default = null;
        if (is_array($cfg)) {
            $type = isset($cfg['type']) ? $cfg['type'] : '';
            $default = isset($cfg['default']) ? $cfg['default'] : null;
        } else {
            $type = $cfg;
        }

        if ($val === null) {
            return $default;
        }

        if (strncmp('-', $val, 1) == 0) {
            array_unshift($array, $val);

            return $default;
        }

        if ($type == 'int') {
            return intval($val);
        } elseif ($type == 'bool') {
            return boolval($val);
        } elseif ($type == 'dir') {
            return self::getFullPath(rtrim(strval($val), '\\/'));
        } elseif ($type == 'file') {
            return self::getFullPath(strval($val));
        } else {
            return strval($val);
        }
    }

    /**
     * @param $array
     * @param $cfg
     * @return mixed
     */
    public static function getParam(&$array, $cfg = '')
    {
        if (is_array($cfg)) {
            $type = isset($cfg['type']) ? $cfg['type'] : '';
        } else {
            $type = $cfg;
        }

        if ($type == 'array') {
            $ret = [];
            $data_type = isset($cfg['data_type']) ? $cfg['data_type'] : '';

            while (true) {
                $param = self::getOneParam($array, $data_type);
                if ($param !== null) {
                    $ret[] = $param;
                } else {
                    break;
                }
            }

            return $ret;
        } else {
            return self::getOneParam($array, $cfg);
        }
    }

    /**
     * @param $argv
     * @param $actions
     * @return mixed
     */
    public static function getAction($argv, $actions)
    {
        $action = array_shift($argv);

        $find = [];
        $len = strlen($action);

        foreach ($actions as $k => $v) {
            if (strncmp($action, $k, $len) === 0) {
                $find[] = $k;
            }
        }

        if (count($find) <= 0) {
            throw new Exception('操作不存在');
        }

        if (count($find) > 1) {
            throw new Exception('操作不明确，您是否是要输入下列内容之一：' . implode('、', $find));
        }

        $action = $find[0];
        if (!isset($actions[$action])) {
            throw new Exception('参数不合法');
        }

        return $action;
    }

    /**
     * @param $argv
     * @param $cfg
     * @return mixed
     */
    public static function getArgs($argv, $cfg)
    {
        $alias = [];
        $names = [];
        $result = [];

        foreach ($cfg as $name => $info) {
            $result[$name]['value'] = $info['default'];
            $result[$name]['default'] = $info['default'];
            $result[$name]['require'] = !empty($info['require']) ? true : false;

            if (!empty($info['alias'])) {
                if (is_array($info['alias'])) {
                    foreach ($info['alias'] as $v) {
                        $alias[$v] = $name;
                    }
                } else {
                    $alias[$info['alias']] = $name;
                }
            }
            if (isset($info['name'])) {
                $names[$info['name']] = $name;
            } else {
                $names[$name] = $name;
            }
        }
        while (!empty($argv)) {
            $arg_name = array_shift($argv);

            if (strncmp('--', $arg_name, 2) == 0) {
                $name = substr($arg_name, 2);
                if (!isset($names[$name])) {
                    throw new Exception('参数[' . $name . ']不存在');
                }
                $cfg_name = $names[$name];
            } else if (strncmp('-', $arg_name, 1) == 0) {
                $alia = substr($arg_name, 1);
                if (!isset($alias[$alia])) {
                    throw new Exception('参数[' . $alia . ']不存在');
                }
                $cfg_name = $alias[$alia];
            } else {
                throw new Exception('参数[' . $arg_name . ']不存在');
            }

            if (!isset($cfg[$cfg_name])) {
                throw new Exception('参数[' . $cfg_name . ']不合法');
            }

            if (isset($cfg[$cfg_name]['params']) && !is_array($cfg[$cfg_name]['params'])) {
                throw new Exception('参数[' . $cfg_name . ']不合法');
            }

            if (!isset($cfg[$cfg_name]['params']) || empty($cfg[$cfg_name]['params'])) {
                $result[$cfg_name]['value'] = !$result[$cfg_name]['default'];
            } elseif (count($cfg[$cfg_name]['params']) == 1) {
                $tmp = self::getParam($argv, $cfg[$cfg_name]['params'][0]);
                if ($tmp === null) {
                    throw new Exception('缺少必要参数[' . $cfg_name . ']');
                }
                $result[$cfg_name]['value'] = $tmp;
            } else {
                $result[$cfg_name]['value'] = [];
                foreach ($cfg[$cfg_name]['params'] as $type) {
                    $tmp = self::getParam($argv, $type);
                    if ($tmp === null) {
                        throw new Exception('缺少必要参数[' . $cfg_name . ']');
                    }
                    $result[$cfg_name]['value'][] = $tmp;
                }
            }
        }

        $ret = [];
        foreach ($result as $cfg_name => $info) {
            if ($info['require'] && $info['value'] === $info['default']) {
                throw new Exception('缺少必要参数[' . $cfg_name . ']');
            }
            $ret[$cfg_name] = $info['value'];
        }

        return $ret;
    }

    ///////////////////////////////////////////

    /**
     * @param $path
     */
    public static function getFullPath($path)
    {
        if (self::$cwd === null) {
            self::$cwd = str_replace('\\', '/', getcwd());
            if (self::$cwd === false) {
                throw new Exception('获取路径信息失败');
            }
        }

        return FileUtils::pathJoin(self::$cwd, $path);
    }

    /////////////////////////////////////////

    /**
     * @param $cmd
     * @param $stream
     * @param null $mline
     * @return mixed
     */
    public static function execCmd($cmd, $stream = null, $mline = false)
    {
        if ($stream !== 1 && $stream !== 2) {
            $stream = false;
        }

        $descriptorspec = [
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']
        ];
        if ($stream !== null) {
            $descriptorspec[$stream] = ['pipe', 'w'];
        }

        $process = proc_open($cmd, $descriptorspec, $pipes);
        $output = [];
        $return_value = -1;

        if (is_resource($process)) {
            if ($stream !== null) {
                $output = stream_get_contents($pipes[$stream]);
                fclose($pipes[$stream]);
            }

            $return_value = proc_close($process);
        }

        if ($stream != null) {
            if ($mline) {
                return explode("\n", $output);
            } else {
                return $output;
            }
        }

        return $return_value;
    }

    /**
     * @param $cmd
     * @param $callback
     * @param $stream
     * @return mixed
     */
    public static function execCmdCallback($cmd, $callback, $stream = 1)
    {
        if ($stream !== 1 && $stream !== 2) {
            throw new Exception('Unknown stream');
        }

        $descriptorspec = [
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']
        ];
        $descriptorspec[$stream] = ['pipe', 'w'];

        $process = proc_open($cmd, $descriptorspec, $pipes);
        $return_value = -1;

        if (is_resource($process)) {
            stream_set_blocking($pipes[$stream], false);

            while (true) {
                $status = proc_get_status($process);

                while (true) {
                    $return_message = fread($pipes[$stream], 1024);
                    if ($return_message === false || $return_message === '') {
                        break;
                    }
                    call_user_func($callback, $return_message);
                }

                if (!$status['running']) {
                    break;
                }

                usleep(100 * 1000);
            }

            fclose($pipes[$stream]);

            $return_value = proc_close($process);
        }

        return $return_value;
    }

    //////////////////////////////////////////////////////////

    /**
     * @param $cols
     * @param $body
     * @param array $footer
     */
    public static function printTable($cols, $body, $footer = [])
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

    /**
     * @param $data
     */
    public static function printJson($data)
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    }

    /**
     * @param $ex
     */
    public static function printException($ex)
    {
        echo '[' . $ex->getCode() . '] ' . $ex->getFile() . '[' . $ex->getLine() . ']: ' . $ex->getMessage() . "\n";
    }

    ///////////////////////////////////////////////////////////

    /**
     * @param $cols
     * @param $line
     * @param $max_len
     * @param $type
     */
    protected static function printLine($cols, $line, $max_len, $type)
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

            $str .= '| ' . str_pad($line[$name], $max_len[$name], ' ', $pad);
        }
        echo $str . " |\n";
    }
}
