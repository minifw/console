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

class Option
{
    protected string $name = '';
    protected array $alias = [];
    protected ?array $comment = [];
    protected $default = null;
    protected $paramType = null;
    protected int $dataType = 0;
    protected ?Closure $filter = null;

    private static function getCfg(string $name, array $cfg, ?self $commonOptions = null)
    {
        if (array_key_exists($name, $cfg)) {
            return $cfg[$name];
        } elseif ($commonOptions !== null && isset($commonOptions->{$name})) {
            return $commonOptions->{$name};
        }

        return null;
    }

    private function getOne(array &$argv, int $type)
    {
        $value = array_shift($argv);
        if ($value === null) {
            return null;
        }

        if (strncmp('-', $value, 1) === 0) {
            array_unshift($argv, $value);

            return null;
        }

        switch ($type) {
            case self::PARAM_BOOL:
                if (strtolower($value) === 'true') {
                    return true;
                } elseif (strtolower($value) === 'false' || $value === '0') {
                    return false;
                }

                return (bool) $value;
            case self::PARAM_INT:
                if (!preg_match('/^\\d+$/', $value)) {
                    throw new Exception('参数不合法');
                }

                return (int) $value;
            case self::PARAM_NUMBER:
                if (!preg_match('/^\\d+(\\.(\\d*))?$/', $value)) {
                    throw new Exception('参数不合法');
                }

                return (double) $value;
            case self::PARAM_STRING:
                return $value;
            case self::PARAM_DIR:
                $path = Utils::getFullPath(rtrim(strval($value), '\\/'));
                if (!is_dir($path)) {
                    throw new Exception('目录不存在');
                }

                return $path;
            case self::PARAM_FILE:
                $path = Utils::getFullPath(strval($value));
                if (!is_file($path)) {
                    throw new Exception('文件不存在');
                }

                return $path;
            case self::PARAM_PATH:
                return Utils::getFullPath(strval($value));
            default:
                throw new Exception('参数不合法');
        }
    }

    private function getArray(array &$argv, int $type) : array
    {
        $ret = [];
        while (true) {
            $one = $this->getOne($argv, $type);
            if ($one === null) {
                break;
            }
            if (in_array($one, $ret)) {
                continue;
            }
            $ret[] = $one;
        }

        return $ret;
    }

    public function __construct(string $name, array $cfg, ?array $commonOptions = null)
    {
        $this->name = $name;

        $use = null;
        if (isset($cfg['use'])) {
            if ($commonOptions === null) {
                throw new Exception('公共参数不能使用use');
            }
            if (!is_string($cfg['use'])) {
                throw new Exception('use不合法');
            }
            if (!isset($commonOptions[$cfg['use']])) {
                throw new Exception('use对象不存在');
            }

            $use = $commonOptions[$cfg['use']];
        }

        $alias = self::getCfg('alias', $cfg, $use);
        if (is_string($alias)) {
            $this->alias[] = $alias;
        } elseif (is_array($alias)) {
            foreach ($alias as $v) {
                if (!is_string($v) || $v === '') {
                    throw new Exception('alias不合法');
                }
                $this->alias[] = $v;
            }
        }

        $comment = self::getCfg('comment', $cfg, $use);
        if (is_array($comment)) {
            $comment = implode("\n", $comment);
        } elseif (!is_string($comment)) {
            throw new Exception('comment不合法');
        }

        if ($comment !== '') {
            $this->comment = explode("\n", $comment);
        } else {
            $this->comment = [];
        }

        $this->default = self::getCfg('default', $cfg, $use);

        $this->paramType = self::getCfg('paramType', $cfg, $use);

        if (is_int($this->paramType)) {
            if (!isset(self::$paramTypeHash[$this->paramType])) {
                throw new Exception('paramType不合法');
            }

            if ($this->paramType === self::PARAM_ARRAY) {
                $this->dataType = self::getCfg('dataType', $cfg, $use);
                if (!is_int($this->dataType) || !isset(self::$paramTypeHash[$this->dataType])) {
                    throw new Exception('dataType不合法');
                }
            } elseif ($this->paramType === self::PARAM_CUSTOM) {
                $this->filter = self::getCfg('filter', $cfg, $use);
                if (!($this->filter instanceof Closure)) {
                    throw new Exception('filter不合法');
                }
            }
        } elseif (is_array($this->paramType)) {
            $count = count($this->paramType);
            if ($count <= 1) {
                throw new Exception('paramType不合法');
            }
            foreach ($this->paramType as $value) {
                if (!is_int($value) && !isset(self::$paramTypeHash[$value])) {
                    throw new Exception('paramType不合法');
                }
                if (($value == self::PARAM_ARRAY || $value == self::PARAM_CUSTOM)) {
                    throw new Exception('paramType不合法');
                }
            }
        } else {
            throw new Exception('paramType不合法');
        }
    }

    public function getManual(string $prefix, string $oppositePrefix) : string
    {
        $lines = $this->getNameLine($oppositePrefix);

        if (!empty($this->comment)) {
            $lines[] = '    ' . implode("\n" . $prefix . '    ', $this->comment);
        }

        return $prefix . implode("\n" . $prefix, $lines);
    }

    protected function getNameLine(string $oppositePrefix) : array
    {
        $lines = [];
        $params = $this->getParam();
        $names = '--' . $this->name;
        if (!empty($this->alias)) {
            $names .= ' | -' . implode(' | -', $this->alias);
        }

        $lines[] = $names . $params;

        if (is_int($this->paramType) && $this->paramType === self::PARAM_BOOL && $oppositePrefix !== '') {
            $names = '--' . $oppositePrefix . $this->name;
            if (!empty($this->alias)) {
                $names .= ' | -' . $oppositePrefix . implode(' | -' . $oppositePrefix, $this->alias);
            }
            $lines[] = $names . $params;
        }

        return $lines;
    }

    protected function getParam()
    {
        if (is_array($this->paramType)) {
            $ret = [];
            foreach ($this->paramType as $type) {
                $ret[] = self::$paramTypeHash[$type];
            }

            return ': ' . implode(', ', $ret);
        } elseif ($this->paramType === self::PARAM_ARRAY) {
            return ': array(' . self::$paramTypeHash[$this->dataType] . ', ...)';
        } elseif ($this->paramType !== self::PARAM_CUSTOM && $this->paramType !== self::PARAM_BOOL) {
            return ': ' . self::$paramTypeHash[$this->paramType];
        }
    }

    public function getValue(array &$argv, bool $opposite = false)
    {
        if ($opposite) {
            if (!is_int($this->paramType) || $this->paramType != self::PARAM_BOOL) {
                throw new Exception('参数不合法');
            }
        }

        if (is_array($this->paramType)) {
            $ret = [];
            foreach ($this->paramType as $type) {
                $one = $this->getOne($argv, $type);
                if ($one === null && $this->default === null) {
                    throw new Exception('选项缺少参数: --' . $this->name);
                }
                $ret[] = $one;
            }

            return $ret;
        } elseif ($this->paramType == self::PARAM_ARRAY) {
            return $this->getArray($argv, $this->dataType);
        } elseif ($this->paramType == self::PARAM_CUSTOM) {
            return ($this->filter)($argv);
        } elseif ($this->paramType == self::PARAM_BOOL) {
            if ($opposite) {
                return false;
            } else {
                return true;
            }
        } else {
            $value = $this->getOne($argv, $this->paramType);
            if ($value === null && $this->default === null) {
                throw new Exception('选项缺少参数: --' . $this->name);
            }

            return $value;
        }
    }

    public function getAlias() : array
    {
        return $this->alias;
    }

    public function getDefault()
    {
        return $this->default;
    }
    private static $paramTypeHash = [
        self::PARAM_STRING => 'string', //字符串.
        self::PARAM_INT => 'int', //整数.
        self::PARAM_DIR => 'dir', //目录，必须存在.
        self::PARAM_FILE => 'file', //文件，必须存在.
        self::PARAM_PATH => 'path', //路径，可以不存在.
        self::PARAM_ARRAY => 'array', //不定长数组.
        self::PARAM_NUMBER => 'number', //整数或小数.
        self::PARAM_BOOL => 'bool', //bool值
        self::PARAM_CUSTOM => 'custom', //自定义.
    ];
    public const PARAM_STRING = 1;
    public const PARAM_INT = 2;
    public const PARAM_DIR = 3;
    public const PARAM_FILE = 4;
    public const PARAM_PATH = 5;
    public const PARAM_ARRAY = 6;
    public const PARAM_NUMBER = 7;
    public const PARAM_BOOL = 8;
    public const PARAM_CUSTOM = 9;
}
