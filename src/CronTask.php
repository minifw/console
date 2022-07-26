<?php

/*
 * Copyright (C) 2022 Yang Ming <yangming0116@163.com>.
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

use Exception;

class CronTask
{
    protected string $name;
    protected string $cmd;
    protected ?string $cwd = null;
    protected ?array $env = null;
    protected int $timeout = 0;
    protected static $range = [
        'min' => ['min' => 0, 'max' => 59],
        'hour' => ['min' => 0, 'max' => 23],
        'day' => ['min' => 1, 'max' => 31],
        'month' => ['min' => 1, 'max' => 12],
        'week' => ['min' => 0, 'max' => 6],
    ];

    /**
     * 可能的格式  (*|((n|m-n)(/step)?)(,(n|m-n)(/step)?)?
     */
    protected array $schedule;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;

        if (!empty($config['cmd']) && is_string($config['cmd'])) {
            $this->cmd = $config['cmd'];
        } else {
            throw new Exception('数据不合法');
        }

        if (!empty($config['cwd']) && is_string($config['cwd'])) {
            $this->cwd = $config['cwd'];
        }

        if (!empty($config['env']) && is_array($config['env'])) {
            $this->cenvd = $config['env'];
        }

        if (!empty($config['timeout']) && is_int($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }

        if (empty($config['schedule']) || !is_array($config['schedule'])) {
            throw new Exception('数据不合法');
        }

        $this->schedule = self::parse($config['schedule']);
    }

    public function needRun(array $runData) : bool
    {
        if (empty($runData['nextRun']) || !is_int($runData['nextRun'])) {
            return true;
        }
        $now = time();
        $nextRun = (int) $runData['nextRun'];

        if ($nextRun <= $now) {
            return true;
        }

        return false;
    }

    public function getProcess(array $runData, bool $force) : ?Process
    {
        if (!$force && !$this->needRun($runData)) {
            return null;
        }

        $process = new Process($this->cmd, $this->cwd, $this->env);
        $process->setTimeout($this->timeout);

        return $process;
    }

    public function nextRun() : array
    {
        $now = time();

        $time = new CronTime($now, $this->schedule);
        $next = $time->findNext();
        if ($next === null) {
            throw new Exception('数据不合法');
        }

        return [
            'nextRun' => $next,
        ];
    }

    public static function parse(array $input) : array
    {
        $hash = ['min', 'hour', 'day', 'month', 'week'];
        $ret = [];

        if (count($input) != 5) {
            throw new Exception('数据不合法');
        }

        for ($i = 0; $i < 5; $i++) {
            $type = $hash[$i];
            $ret[$type] = self::expand($input[$i], self::$range[$type]);
        }

        return $ret;
    }

    public static function expand(string $input, array $cfg) : array
    {
        $min = $cfg['min'];
        $max = $cfg['max'];

        if ($min >= $max) {
            throw new Exception('数据不合法');
        }

        $input = explode(',', $input);
        $result = [];
        foreach ($input as $value) {
            $value = explode('/', $value);
            $count = count($value);
            $step = 1;
            if ($count == 2) {
                $step = (int) $value[1];
                if ($step < 1) {
                    throw new Exception('格式不合法');
                }
            } elseif ($count > 2 || $count < 1) {
                throw new Exception('格式不合法');
            }

            $value = $value[0];
            $begin = $min;
            $end = $max;

            if ($value != '*') {
                if (!preg_match('/^(\\d+)(-(\\d+))?$/', $value, $matches)) {
                    throw new Exception('格式不合法');
                }
                $begin = $matches[1];
                if ($begin < $min) {
                    throw new Exception('数值小于最小值');
                }
                $end = $begin;

                if (isset($matches[3])) {
                    $end = $matches[3];
                    if ($end <= $begin || $end > $max) {
                        throw new Exception('数值大于最大值');
                    }
                }
            }

            for ($i = $begin;$i <= $end;$i += $step) {
                $result[$i] = 1;
            }
        }

        $ret = [];
        foreach ($result as $key => $value) {
            $ret[] = $key;
        }

        if (empty($ret)) {
            throw new Exception('数据不合法');
        }

        sort($ret, SORT_NUMERIC);

        return $ret;
    }
}
