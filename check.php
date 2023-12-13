<?php
ini_set('display_errors', '1');
error_reporting(E_ALL ^ E_NOTICE);
set_time_limit(0);

// 定义站点目录
define('APP_PATH', __DIR__ . '/');

$_GPC = array();
$_GPC = array_merge($_GET, $_POST);

$actions = array('files', 'download');
$action = in_array($_GPC['do'], $actions) ? $_GPC['do'] : 'files';
call_user_func(array('WeliamCheck', $action));

class WeliamCheck
{

    //获取所有文件路径和md5
    static function files()
    {
        //获取更新文件
        $my_scenfiles = self::files_tree(substr(APP_PATH, 0, -1));
        $files = array();
        foreach ($my_scenfiles as $sf) {
            if (self::files_filter($sf)) {
                $files[] = array('path' => str_replace(APP_PATH, "", $sf), 'md5' => md5_file($sf));
            }
        }
        self::message(0, '', $files);
    }

    //下载文件
    static function download()
    {
        global $_GPC;
        $entry = APP_PATH . trim($_GPC['path']);
        if (is_file($entry) && self::files_filter($entry)) {
            $content = file_get_contents($entry);
            self::message(0, '', array('path' => $_GPC['path'], 'content' => base64_encode($content)));
        }
        self::message(1, '路径错误，文件不存在');
    }

    //移除无需更新文件
    static function files_filter($file)
    {
        $file_type = [
            '.log',
            '.txt',
            '.zip',
            'check.php',
            'install.php',
            '.md',
            'LICENSE',
            APP_PATH . 'addons/weliam_smartcity/data/',
            'manifest.xml',
            APP_PATH . 'addons/weliam_smartcity/plugin/weliam_house/house/runtime/',
            '/attachment/',//临时附件文件
            '/data/tpl',//页面模板缓存文件
            '/data/log',//日志文件
        ];
        foreach ($file_type as $value) {
            if (strpos($file, $value) !== FALSE) {
                return FALSE;
            }
        }
        return TRUE;
    }

    //根据路径返回当前路径下所有文件
    static function files_tree($path)
    {
        $files = array();
        $ds = glob($path . '/*');
        if (is_array($ds)) {
            foreach ($ds as $entry) {
                if (is_file($entry)) {
                    $files[] = $entry;
                }
                if (is_dir($entry)) {
                    $rs = self::files_tree($entry);
                    foreach ($rs as $f) {
                        $files[] = $f;
                    }
                }
            }
        }
        return $files;
    }

    //调试函数
    static function wl_debug($array = array())
    {
        echo "<pre>";
        print_r($array);
        exit();
    }

    //数据输出函数
    static function message($code = 0, $message = array(), $data = array())
    {
        die(json_encode(array('code' => $code, 'message' => $message, 'data' => $data)));
    }
}

