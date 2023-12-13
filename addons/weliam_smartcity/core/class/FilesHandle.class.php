<?php
defined('IN_IA') or exit('Access Denied');

class FilesHandle {

    static function file_copy($fromFile, $toFile) {
        self::file_create_folder($toFile);
        $folder1 = opendir($fromFile);
        while ($f1 = readdir($folder1)) {
            if ($f1 != "." && $f1 != "..") {
                $path2 = "{$fromFile}/{$f1}";
                if (is_file($path2)) {
                    $file = $path2;
                    $newfile = "{$toFile}/{$f1}";
                    copy($file, $newfile);
                } elseif (is_dir($path2)) {
                    $toFiles = $toFile . '/' . $f1;
                    self::file_copy($path2, $toFiles);
                }
            }
        }
    }

    /*
     * 递归创建文件夹
     */
    static function file_create_folder($dir, $mode = 0777) {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!self::file_create_folder(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }

    //遍历打包文件目录
    static function file_list_dir($dir) {
        $result = array();
        if (is_dir($dir)) {
            $file_dir = scandir($dir);
            foreach ($file_dir as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                } elseif (is_dir($dir . $file)) {
                    $result = array_merge($result, self::file_list_dir($dir . $file . '/'));
                } else {
                    array_push($result, $dir . $file);
                }
            }
        }
        return $result;
    }

    //遍历文件目录
    static function file_tree($path) {
        $files = array();
        $ds = glob($path . '/*');
        if (is_array($ds)) {
            foreach ($ds as $entry) {
                if (is_file($entry)) {
                    $files[] = $entry;
                }
                if (is_dir($entry)) {
                    $rs = self::file_tree($entry);
                    foreach ($rs as $f) {
                        $files[] = $f;
                    }
                }
            }
        }
        return $files;
    }

    //创建目录
    static function file_mkdirs($path) {
        if (!is_dir($path)) {
            self::file_mkdirs(dirname($path));
            mkdir($path);
        }
        return is_dir($path);
    }

    //删除文件夹下所有文件
    static function file_delete_all($path, $delall = '') {
        $op = dir($path);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . '/' . $item)) {
                self::file_delete_all($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                unlink($op->path . '/' . $item);
            }
        }
        if ($delall == 1) {
            rmdir($path);
        }
    }

    //查找目录下所有php文件
    static function file_findphp($path) {
        $up_filestree = self::file_tree($path);

        $upgrade = array();
        foreach ($up_filestree as $sf) {
            $file_bs = substr($sf, -3);
            if ($file_bs == 'php') {
                $upgrade[] = array('path' => str_replace($path . '/', "", $sf), 'fullpath' => $sf);
            }
        }

        return $upgrade;
    }

    //删除所有空目录
    static function file_rm_empty_dir($path) {
        if (is_dir($path) && ($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {// 遍历文件夹
                if ($file != '.' && $file != '..') {
                    $curfile = $path . '/' . $file;
                    // 当前目录
                    if (is_dir($curfile)) {// 目录
                        self::file_rm_empty_dir($curfile);
                        // 如果是目录则继续遍历
                        if (count(scandir($curfile)) == 2) {//目录为空,=2是因为.和..存在
                            rmdir($curfile);
                            // 删除空目录
                        }
                    }
                }
            }
            closedir($handle);
        }
    }
}