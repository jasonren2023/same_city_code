<?php

/**
 * 自动加载类方法
 *
 * @access public
 * @name Wlmore_AutoLoad()
 * @param $class_name  方法名;
 * @since 1.0
 * @return $file 引入方法
 */
function Weliam_AutoLoad($class_name) {
    if (strexists($class_name, 'Table')) {
        $file = PATH_CORE . 'table/' . lcfirst(str_replace("Table", "", $class_name)) . '.table.php';
    } else {
        $file = PATH_CORE . 'model/' . $class_name . '.mod.php';
        if (!file_exists($file)) {
            $file = PATH_CORE . 'class/' . $class_name . '.class.php';
        }
        if (!file_exists($file)) {
            $file = PATH_PLUGIN . strtolower($class_name) . '/' . $class_name . '.mod.php';
        }
    }
    if (file_exists($file)) {
        require_once $file;
        return true;
    } else {
        return false;
    }
}

spl_autoload_register('Weliam_AutoLoad');