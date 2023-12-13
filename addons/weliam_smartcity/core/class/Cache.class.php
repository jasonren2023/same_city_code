<?php
defined('IN_IA') or exit('Access Denied');

class Cache {
    /**
     *  使用缓存前先查询缓存数据
     *
     * @access static public
     * @name getDateByCacheFirst
     * @param $key 缓存键
     * @param $name 缓存名
     * @param $funcname 方法名
     * @param $valuearray 方法参数
     * @return array
     */
    static function getDateByCacheFirst($key, $name, $funcname, $valuearray) {
        $data = self::getCache($key, $name);
        if (empty($data)) {
            $data = call_user_func_array($funcname, $valuearray);
            self::setCache($key, $name, $data);
        }
        return $data;
    }

    /**
     * @param $key      int|string
     * @param $name     int|string
     * @return array|bool|false|Memcache|mixed|Redis|string
     */
    static function getCache($key, $name) {
        global $_W;
        if (empty($key) || empty($name)) return false;

        return cache_read(MODULE_NAME . ':' . $_W['uniacid'] . ':' . $key . ':' . $name);
    }

    /**
     * @param $key      int|string
     * @param $name     int|string
     * @param $value    int|string
     * @return array|bool|Memcache|Redis
     */
    static function setCache($key, $name, $value) {
        global $_W;
        if (empty($key) || empty($name)) return false;

        return cache_write(MODULE_NAME . ':' . $_W['uniacid'] . ':' . $key . ':' . $name, $value);
    }

    /**
     * 删除缓存
     *
     * @access static public
     * @name deleteCache
     * @param $key 缓存键
     * @param $name 缓存名
     * @return true|false
     */
    static function deleteCache($key, $name) {
        global $_W;
        if (empty($key) || empty($name)) return false;

        return cache_delete(MODULE_NAME . ':' . $_W['uniacid'] . ':' . $key . ':' . $name);
    }

    /**
     * 删除本模块所有缓存
     *
     * @access static public
     * @name deleteThisModuleCache
     * @return true|false
     */
    static function deleteThisModuleCache() {
        return cache_clean(MODULE_NAME);
    }

    /**
     * 写入数据cache锁
     *
     * @access static public
     * @name setSingleLockByCache
     * @param $arr [tablename][single]
     * @param $time 加锁时间
     * @return false|true
     */
    static function setSingleLockByCache($arr, $time = 15) {
        if ($arr == '' || empty($arr) || $arr['single'] == 'table') return false;
        $tableCache = self::getCache($arr['tablename'], 'table');
        if (!empty($tableCache) && $tableCache > time()) return false;
        $singleCache = self::getCache($arr['tablename'], $arr['single']);
        if (!empty($singleCache) && $singleCache > time()) return false;

        return self::setCache($arr['tablename'], $arr['single'], time() + $time);
    }

    /**
     * 写入表cache锁
     *
     * @access static public
     * @name setTableLockByCache
     * @param $arr [tablename]
     * @param $time 加锁时间
     * @return false|true
     */
    static function setTableLockByCache($arr, $time = 15) {
        if ($arr == '' || empty($arr) || $arr['single'] == 'table') return false;
        $tableCache = self::getCache($arr['tablename'], 'table');
        if (!empty($tableCache) && $tableCache > time()) return false;

        return self::setCache($arr['tablename'], 'table', time() + $time);
    }
}
