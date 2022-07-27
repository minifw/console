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
use Minifw\Common\FileUtils;

class CronRunner
{
    protected array $groups;
    protected string $datafile;
    protected string $lockfile;
    protected ?string $logfile = null;
    protected int $logdays = 7;
    protected $logFp = null;
    protected array $runData = [];

    public function __construct(array $config)
    {
        if (empty($config['datafile']) || !is_string($config['datafile'])) {
            throw new Exception('数据不合法:datafile');
        }
        $this->datafile = $config['datafile'];

        if (empty($config['lockfile']) || !is_string($config['lockfile'])) {
            throw new Exception('数据不合法:lockfile');
        }
        $this->lockfile = $config['lockfile'];

        if (!empty($config['logfile'])) {
            if (!is_string($config['logfile'])) {
                throw new Exception('数据不合法:logfile');
            }

            $this->logfile = $config['logfile'];
            if (substr($this->logfile, -4) !== '.log') {
                $this->logfile .= '.log';
            }

            if (!empty($this->logfile)) {
                $dir = dirname($this->logfile);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $this->logFp = fopen($this->logfile, 'w+');
            }
        }

        if (!empty($config['logdays'])) {
            if (!is_int($config['logdays'])) {
                throw new Exception('数据不合法:logdays');
            }

            $this->logdays = $config['logdays'];
        }

        if (empty($config['groups'])) {
            throw new Exception('数据不合法:groups');
        }

        $this->groups = [];
        $this->runData = [];

        $savedData = [];
        if (file_exists($this->datafile)) {
            $dataJson = file_get_contents($this->datafile);
            $savedData = json_decode($dataJson, true);
            if (!is_array($savedData)) {
                $savedData = [];
            }
        }

        foreach ($config['groups'] as $groupName => $groupCfg) {
            $group = [];
            $groupData = [];
            $savedGroup = $savedData[$groupName] ?? [];
            foreach ($groupCfg as $taskName => $taskCfg) {
                try {
                    if (!is_array($taskCfg)) {
                        throw new Exception('数据不合法');
                    }
                    $group[$taskName] = new CronTask($taskName, $taskCfg);
                    if (!empty($savedGroup[$taskName]) && is_array($savedGroup[$taskName])) {
                        $groupData[$taskName] = $savedGroup[$taskName];
                    } else {
                        $groupData[$taskName] = [];
                    }
                } catch (Exception $ex) {
                    throw new Exception('数据不合法:groups.' . $groupName . '.' . $taskName);
                }
            }

            $this->groups[$groupName] = $group;
            $this->runData[$groupName] = $groupData;
        }
    }

    public function run(array $argv = []) : void
    {
        $file = $this->lockfile;
        $dir = dirname($this->lockfile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $fp = fopen($file, 'wb+');

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            try {
                set_time_limit(0);

                $force = false;
                if (isset($argv[1]) && $argv[1] == '-f') {
                    $force = true;
                }

                $taskRunning = [];
                $group = new ProcessGroup(function ($name, $stream, $msg) use (&$taskRunning) {
                    if (isset($taskRunning[$name])) {
                        $name = $name . '.' . $taskRunning[$name];
                    }
                    $this->log($name, $msg);
                });
                $groupLeft = $this->groups;

                while (true) {
                    if (empty($groupLeft)) {
                        break;
                    }
                    foreach ($groupLeft as $gname => $groupCfg) {
                        if (isset($taskRunning[$gname])) {
                            continue;
                        }
                        foreach ($groupCfg as $taskName => $taskObj) {
                            $runData = $this->runData[$gname][$taskName];

                            $process = $taskObj->getProcess($runData, $force);
                            unset($groupLeft[$gname][$taskName]);
                            if ($process !== null) {
                                $taskRunning[$gname] = $taskName;
                                $process->setName($gname);
                                $group->addProcess($process);
                                break;
                            }
                        }
                        if (empty($groupLeft[$gname])) {
                            unset($groupLeft[$gname]);
                            continue;
                        }
                    }

                    while (true) {
                        $result = $this->doLoop($group, $taskRunning);
                        if ($result === null || $result === true) {
                            break;
                        }
                        usleep(10 * 1000);
                    }
                }

                while (true) {
                    $result = $this->doLoop($group, $taskRunning);
                    if ($result === null) {
                        break;
                    }
                    usleep(10 * 1000);
                }
            } catch (\Exception $ex) {
                Utils::printException($ex);
            }

            $this->log('main', 'SUCCEED');

            flock($fp, LOCK_UN);
        } else {
            $this->log('main', 'RUNNING');
        }
        if ($this->logFp !== null) {
            fclose($this->logFp);
        }
        fclose($fp);
    }

    protected function doLoop(ProcessGroup $group, array &$taskRunning)
    {
        $exitCode = $group->doLoop();
        if ($exitCode === null) {
            return null;
        }

        if (!empty($exitCode)) {
            foreach ($exitCode as $gname => $code) {
                $taskName = $taskRunning[$gname];
                unset($taskRunning[$gname]);
                if ($code == 0) {
                    $taskObj = $this->groups[$gname][$taskName];
                    $this->runData[$gname][$taskName] = $taskObj->nextRun();
                    $this->saveResult();
                    $this->log($gname . '.' . $taskName, '成功');
                } else {
                    $this->log($gname . '.' . $taskName, '失败: ' . $code);
                }
            }

            return true;
        }

        return false;
    }

    protected function saveResult()
    {
        if (empty($this->datafile)) {
            return;
        }
        $dir = dirname($this->datafile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $json = json_encode($this->runData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->datafile, $json);
    }

    public function log($name, $msg)
    {
        if ($this->logFp === null) {
            return;
        }
        $mtime = filemtime($this->logfile);
        $now = time();

        if (date('Y-m-d', $mtime) != date('Y-m-d', $now)) {
            FileUtils::rotateFile($this->logfile, '.log', $this->logdays, $this->logFp);
        }

        $data = date('Y-m-d H:i:s', $now) . ' [' . $name . '] ' . $msg;
        if (substr($data, -1) !== "\n") {
            $data .= "\n";
        }
        fwrite($this->logFp, $data);
    }
}
