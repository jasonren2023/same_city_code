<?php
defined('IN_IA') or exit('Access Denied');

class Func_loader {
    /**
     * static function函数应用
     *
     * @access public
     * @name core
     * @param $name  函数名称
     * @return true|false
     */
    static function core($name) {
        global $_W;
        if (isset($_W['wlfunc'][$name])) {
            return true;
        }
        $file = PATH_CORE . 'function/' . $name . '.func.php';
        if (file_exists($file)) {
            include_once $file;
            $_W['wlfunc'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Helper Function ' . PATH_CORE . 'function/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }

    /**
     * static function函数应用
     *
     * @access public
     * @name web
     * @param $name  函数名称
     * @return true|false
     */
    static function sys($name) {
        global $_W;
        if (isset($_W['wlsys'][$name])) {
            return true;
        }
        $file = PATH_SYS . 'common/' . $name . '.func.php';
        if (file_exists($file)) {
            include_once $file;
            $_W['wlsys'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Sys Helper ' . PATH_SYS . 'common/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }

    /**
     * static function函数应用
     *
     * @access public
     * @name web
     * @param $name  函数名称
     * @return true|false
     */
    static function web($name) {
        global $_W;
        if (isset($_W['wlweb'][$name])) {
            return true;
        }
        $file = PATH_WEB . 'common/' . $name . '.func.php';
        if (file_exists($file)) {
            include_once $file;
            $_W['wlweb'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Web Helper ' . PATH_WEB . 'common/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }

    /**
     * function函数应用
     *
     * @access public
     * @name app
     * @param $name  函数名称
     * @return true|false
     */
    static function app($name) {
        global $_W;
        if (isset($_W['wlapp'][$name])) {
            return true;
        }
        $file = PATH_APP . 'common/' . $name . '.func.php';
        if (file_exists($file)) {
            include_once $file;
            $_W['wlapp'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid App Function ' . PATH_APP . 'common/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }

}
