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

require __DIR__ . '/../vendor/autoload.php';

use Minifw\Console\Cmd;
use Minifw\Common\Exception;
use Minifw\Console\Console;

$ret = Cmd::exec_cmd('ls', null, false);
var_dump($ret);

$ret = Cmd::exec_cmd('cat ' . dirname(__DIR__) . '/.gitignore', 1, false);
var_dump($ret);

class Parse {

    protected $msg_cache;
    protected $duration;

    /**
     *
     * @var Console
     */
    protected $console;
    protected $crop;

    public function __construct($console) {
        $this->console = $console;
    }

    public function run() {
        $this->do_parse_dir(dirname(__DIR__) . '/tmp/video');
    }

    /////////////////////////////////

    protected function do_parse_dir($src, $show = '') {
        if (!is_dir($src)) {
            throw new Exception($src . ':必须是一个目录');
        }

        if ($show != '') {
            $show .= '/';
        }

        $list = scandir($src);
        foreach ($list as $v) {
            if ($v[0] === '.') {
                continue;
            }
            $new_src = $src . '/' . $v;
            if (is_file($new_src)) {
                $this->do_parse_file($new_src, $show . $v);
            }
            elseif (is_dir($new_src)) {
                $this->do_parse_dir($new_src, $show . $v);
            }
        }
    }

    protected function do_parse_file($src, $show) {
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        $ext_list = [
            'mp4' => 1,
            'avi' => 1,
            'rmvb' => 1,
            'rm' => 1,
            'mkv' => 1,
            'wmv' => 1,
        ];

        if (!isset($ext_list[$ext]) || $ext_list[$ext] == 0) {
            $this->console->print($show . " \033[32mskip\033[0m")->reset();
            return;
        }

        $size = filesize($src);

        $this->console->print($show . " \033[32m" . Cmd::show_size($size) . "\033[0m");

        $cmd = 'ffprobe -v quiet -print_format json -show_streams "' . $src . '"';

        $ret = Cmd::exec_cmd($cmd, 1);

        $info = json_decode($ret, true);
        if (empty($info || empty($info['streams']))) {
            $this->console->print('不是视频文件：' . $show)->reset();
            return;
        }

        $duration = 0;

        foreach ($info['streams'] as $v) {
            if (isset($v['duration'])) {
                $new_duration = intval($v['duration']);
                if ($new_duration > $duration) {
                    $duration = $new_duration;
                }
            }
        }

        if ($duration <= 1) {
            $this->console->print('不是视频文件：' . $show)->reset();
            return;
        }
        $this->duration = $duration;

        $offset = intval(($duration / 10));
        if ($offset <= 0) {
            $offset = 1;
        }

        $ss_list = [$offset];
        for ($i = 0; $i < 9; $i++) {
            $tmp = $ss_list[$i] + $offset;
            if ($tmp > $duration) {
                break;
            }
            $ss_list[] = $tmp;
        }

        $crop = null;
        $this->console->set_status('获取视频信息...');

        foreach ($ss_list as $ss) {
            $cmd = 'ffmpeg -ss ' . $ss . ' -i "' . $src . '" -vframes 10 -vf cropdetect -f null - 2>&1 | grep \'cropdetect\'';
            $result = Cmd::exec_cmd($cmd, 1, true);
            foreach ($result as $v) {
                if (preg_match('/x1:(\d+) x2:(\d+) y1:(\d+) y2:(\d+) w:(\d+) h:(\d+)/', $v, $matches)) {
                    if ($crop === null) {
                        $crop = [$matches[1], $matches[2], $matches[3], $matches[4]];
                    }
                    else {
                        $crop[0] = $crop[0] <= $matches[1] ? $crop[0] : $matches[1];
                        $crop[1] = $crop[1] >= $matches[2] ? $crop[1] : $matches[2];
                        $crop[2] = $crop[2] <= $matches[3] ? $crop[2] : $matches[3];
                        $crop[3] = $crop[3] >= $matches[4] ? $crop[3] : $matches[4];
                    }
                }
            }
        }

        if ($crop === null) {
            throw new Exception('获取视频信息失败:' . $show);
        }

        $crop_str = ($crop[1] - $crop[0] + 1) . ':' . ($crop[3] - $crop[2] + 1) . ':' . $crop[0] . ':' . $crop[2];
        $this->crop = $crop_str;

        $cmd = 'ffmpeg -i "' . $src . '" -vf crop=' . $crop_str . ' -s 8x8 -pix_fmt gray -f image2pipe -vcodec rawvideo - > /dev/null';
        $this->msg_cache = '';
        Cmd::exec_cmd_callback($cmd, [$this, 'show_progress'], 2);

        $this->console->reset();
    }

    public function show_progress($msg) {
        $this->msg_cache .= $msg;

        $arr = explode("frame=", $this->msg_cache);
        $count = count($arr);

        if (!preg_match('/^(\d+) fps=(\d+) .*? time=(\d+):(\d+):(\d+).\d+ .*$/', $arr[$count - 1])) {
            $this->msg_cache = array_pop($arr);
        }

        $total = Cmd::show_duration($this->duration);

        foreach ($arr as $line) {
            if (preg_match('/^\s*(\d+) fps=(\d+) .*? time=(\d+):(\d+):(\d+)\.\d+ .*/', $line, $matches)) {
                $sec = $matches[3] * 3600 + $matches[4] * 60 + $matches[5];
                $pecent = round($sec * 100 / $this->duration, 2);

                $line = 'crop=' . $this->crop . ' frame=' . $matches[1] . ' fps=' . $matches[2] . ' time=' . $matches[3] . ':' . $matches[4] . ':' . $matches[5] . '/' . $total . ' ' . $pecent . '%';
                $this->console->set_status($line);
            }
        }
    }

}

$console = new Console();
$app = new Parse($console);
$app->run();