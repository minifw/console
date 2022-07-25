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

class OptionParser
{
    protected array $alias = [];
    protected array $actions = [];
    protected array $comment = [];
    protected string $oppositePrefix = '';

    public function __construct(array $cfg)
    {
        $this->init($cfg);
    }

    public function getAction(string $action) : string
    {
        $find = [];
        $len = strlen($action);

        foreach ($this->actions as $k => $v) {
            if ($k == $action) {
                return $action;
            }
            if ($len <= 0 || strncmp($action, $k, $len) === 0) {
                $find[] = $k;
            }
        }

        if (count($find) <= 0) {
            throw new Exception('操作不存在');
        }

        if (count($find) > 1) {
            throw new Exception('操作不明确，您是否是要输入下列内容之一：' . implode('、', $find));
        }

        return $find[0];
    }

    public function getOptions(string $action, array $argv) : array
    {
        if (!isset($this->actions[$action])) {
            throw new Exception('操作不存在:' . $action);
        }

        $cfg = $this->actions[$action];

        $result = [];
        foreach ($cfg['options'] as $name => $optObj) {
            $result[$name] = $optObj->getDefault();
        }

        while (true) {
            if (empty($argv)) {
                break;
            }

            $optinfo = $this->getOptName($argv, $cfg);
            if ($optinfo === null) {
                break;
            }

            $opposite = $optinfo[1];
            $optName = $optinfo[0];

            $optObj = $cfg['options'][$optName];

            $result[$optName] = $optObj->getValue($argv, $opposite);
        }

        foreach ($result as $optName => $value) {
            if ($value === null) {
                throw new Exception('缺少必要参数:' . $optName);
            }
        }

        return [
            'options' => $result,
            'input' => $argv,
        ];
    }

    public function getManual() : string
    {
        $lines = [];
        if (!empty($this->comment)) {
            $lines[] = implode("\n", $this->comment);
        }

        $lines[] = '';
        $count = count($this->actions);
        foreach ($this->actions as $name => $action) {
            $prefix = '    ';
            if ($count > 1) {
                $lines[] = $name . ':';
                if (!empty($action['comment'])) {
                    $lines[] = '    ' . implode("\n    ", $action['comment']);
                }
                $lines[] = '';
            } else {
                $prefix = '';
            }

            $empty = true;
            foreach ($action['options'] as $name => $option) {
                $optComment = $option->getManual($prefix, $this->oppositePrefix);
                if (!empty($optComment)) {
                    $lines[] = $optComment;
                    $empty = false;
                }
            }
            if (!$empty) {
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    //////////////////////////////////////////

    protected function getOptName(array &$argv, $cfg) : ?array
    {
        $str = array_shift($argv);
        if ($str === '--') {
            return null;
        }

        if (strncmp('-', $str, 1) !== 0) {
            throw new Exception('参数[' . $str . ']不存在');
        }

        $opposite = false;

        if (strncmp('--', $str, 2) !== 0) {
            $alia = substr($str, 1);

            if ($this->oppositePrefix !== '' && strncmp($this->oppositePrefix, $alia, strlen($this->oppositePrefix)) === 0) {
                $opposite = true;
                $alia = substr($alia, strlen($this->oppositePrefix));
            }

            if (!isset($cfg['alias'][$alia])) {
                throw new Exception('参数[' . $str . ']不存在');
            }
            $optName = $cfg['alias'][$alia];
        } else {
            $optName = substr($str, 2);

            if ($this->oppositePrefix !== '' && strncmp($this->oppositePrefix, $optName, strlen($this->oppositePrefix)) === 0) {
                $opposite = true;
                $optName = substr($optName, strlen($this->oppositePrefix));
            }

            if (!isset($cfg['options'][$optName])) {
                throw new Exception('参数[' . $str . ']不存在');
            }
        }

        return [$optName, $opposite];
    }

    protected function init(array $cfg) : void
    {
        $this->oppositePrefix = $cfg['oppositePrefix'] ?? '';
        if (!is_string($this->oppositePrefix)) {
            throw new Exception('oppositePrefix不合法');
        }

        $comment = $cfg['comment'] ?? '';
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

        $commonOptions = [];
        if (!empty($cfg['options'])) {
            foreach ($cfg['options'] as $name => $option) {
                try {
                    $commonOptions[$name] = new Option($name, $option);
                } catch (Exception $ex) {
                    throw new Exception('options.' . $name . '不合法:' . $ex->getMessage());
                }
            }
        }

        if (empty($cfg['actions']) || !is_array($cfg['actions'])) {
            throw new Exception('actions不能为空');
        }

        foreach ($cfg['actions'] as $actName => $action) {
            $one = [];
            $one['comment'] = $action['comment'] ?? '';
            if (is_array($one['comment'])) {
                $one['comment'] = implode("\n", $one['comment']);
            } elseif (!is_string($one['comment'])) {
                throw new Exception('actions.' . $actName . '.comment不合法');
            }
            if ($one['comment'] !== '') {
                $one['comment'] = explode("\n", $one['comment']);
            } else {
                $one['comment'] = [];
            }

            $one['options'] = [];
            $one['alias'] = [];

            if (!empty($action['options'])) {
                if (!is_array($action['options'])) {
                    throw new Exception('actions.' . $actName . '.options不合法');
                }

                foreach ($action['options'] as $optName => $option) {
                    if (is_int($optName)) {
                        if (is_string($option)) {
                            $optName = $option;
                            $option = [
                                'use' => $optName
                            ];
                        } else {
                            throw new Exception('actions.' . $actName . '.options.' . $optName . '不合法');
                        }
                    }

                    try {
                        $optObj = new Option($optName, $option, $commonOptions);
                    } catch (Exception $ex) {
                        throw new Exception('actions.' . $actName . '.options.' . $optName . '不合法:' . $ex->getMessage());
                    }

                    $alias = $optObj->getAlias();
                    foreach ($alias as $str) {
                        if (isset($one['alias'][$str])) {
                            throw new Exception('别名存在冲突: ' . $str);
                        }
                        $one['alias'][$str] = $optName;
                    }

                    $one['options'][$optName] = $optObj;
                }
            }

            $this->actions[$actName] = $one;
        }
    }
}
