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

class CronTime
{
    protected int $year;
    protected int $month;
    protected int $day;
    protected int $hour;
    protected int $min;
    protected array $schedule;

    public function __construct(int $time, array $schedule)
    {
        $this->year = (int) date('Y', $time);
        $this->month = (int) date('m', $time);
        $this->day = (int) date('d', $time);
        $this->hour = (int) date('H', $time);
        $this->min = (int) date('i', $time);

        $this->schedule = $schedule;
    }

    public function findNext() : ?int
    {
        $curYear = $this->year;
        $curMonth = $this->month;
        $curDay = $this->day;
        $this->min++;
        while (true) {
            if ($this->year - $curYear > 2) {
                return null;
            }

            $this->nextDay();
            $time = mktime(0, 0, 0, $this->month, $this->day, $this->year);

            $week = (int) date('N', $time);
            if ($week == 7) {
                $week = 0;
            }
            if (!in_array($week, $this->schedule['week'])) {
                $this->day++;
                continue;
            }

            if ($this->year != $curYear || $this->month != $curMonth || $this->day != $curDay) {
                $this->hour = 0;
                $this->min = 0;
            }

            if (!$this->nextTime()) {
                $this->day++;
                continue;
            }

            return mktime($this->hour, $this->min, 0, $this->month, $this->day, $this->year);
        }

        return null;
    }

    public function nextTime()
    {
        while (true) {
            $hour = self::find($this->hour, $this->schedule['hour']);
            if ($hour < $this->hour) {
                return false;
            } elseif ($hour != $this->hour) {
                $this->min = 0;
            }
            $this->hour = $hour;

            $min = self::find($this->min, $this->schedule['min']);
            if ($min != $this->min) {
                if ($min < $this->min) {
                    $this->hour++;
                    $this->min = 0;
                    continue;
                }
            }
            $this->min = $min;

            return true;
        }
    }

    public function nextDay()
    {
        while (true) {
            $month = self::find($this->month, $this->schedule['month']);
            if ($month === null) {
                throw new Exception('数据不合法');
            }

            if ($month != $this->month) {
                if ($month < $this->month) {
                    $this->year++;
                }
                $this->day = 1;
            }
            $this->month = $month;

            $day = self::find($this->day, $this->schedule['day']);
            if ($day != $this->day) {
                if ($day < $this->day) {
                    $this->month++;
                    $this->day = 1;
                    continue;
                }
            }
            $this->day = $day;

            $time = mktime(0, 0, 0, $this->month, $this->day, $this->year);
            $dayStr = date('Y-n-j', $time);
            if ($dayStr !== $this->year . '-' . $this->month . '-' . $this->day) {
                $this->month++;
                $this->day = 1;
                continue;
            }
            break;
        }
    }

    public static function find(int $begin, array $list)
    {
        if (empty($list)) {
            return null;
        }

        foreach ($list as $value) {
            if ($value >= $begin) {
                return $value;
            }
        }

        return reset($list);
    }
}
