<?php
defined('IN_IA') or exit('Access Denied');

class Cloud {

    //获取授权信息
    static function auth_info() {
        global $_W;
        //获取授权信息
        $authinfo = self::wl_syssetting_read('authinfo');
        $auth = self::api_post(['url' => $_W['siteroot'], 'code' => $authinfo['code'] ? $authinfo['code'] : '']);
        //写入授权信息
        if ($auth['code'] == 0 && !empty($auth['data'])) {
            self::wl_syssetting_save($auth['data'], 'authinfo');
            return $auth['data'];
        }
        return error(1, $auth['message']);
    }

    static function auth_db_update() {
        $dbfile = PATH_CORE . "common/dbfile.php";
        if (!file_exists($dbfile)) {
            return error(1, 'dbfile文件不存在，请检查后再试');
        }

        $diff_sqls = [];
        $tables = json_decode(base64_decode(file_get_contents($dbfile)), true);
        foreach ($tables as $table) {
            if (!strexists($table['table'], 'wlmerchant') && IMS_FAMILY != 'wl') {
                continue;
            }
            $diff_sqls = array_merge($diff_sqls, WeliamDb::table_upgrade($table));
        }

        return $diff_sqls;
    }

    static function auth_up_data() {
        //数据库更新
        $sqls = Cloud::auth_db_update();

        //商家分类更新
        $cate_count = pdo_getcolumn('wlmerchant_merchant_cate', [], 'COUNT(sid)');
        if (empty($cate_count)) {
            $stores = pdo_getall('wlmerchant_merchantdata', array(), array('id'));
            foreach ($stores as $store) {
                $sqls[] = ['type' => 'store_cate', 'sid' => $store['id']];
            }
        }

        return $sqls;
    }

    static function data_store_cate($sid) {
        $store = pdo_get('wlmerchant_merchantdata', array('id' => $sid), array('onelevel', 'twolevel'));
        if (!empty($store)) {
            $data = array('sid' => $sid, 'onelevel' => $store['onelevel'], 'twolevel' => $store['twolevel']);
            $cate = pdo_get('wlmerchant_merchant_cate', $data);
            if (empty($cate)) {
                pdo_insert('wlmerchant_merchant_cate', $data);
            }
        }
    }

    /**
     * 系统设置保存
     * @param $data 值
     * @param $key 键
     * @return bool
     */
    static function wl_syssetting_save($data, $key) {
        if (empty($key)) {
            return FALSE;
        }
        $record = array();
        $record['value'] = iserializer($data);
        if ($key == 'taskcover') {
            $record['v4flag'] = 1;
            $exists = pdo_getcolumn(PDO_NAME . 'setting', array('key' => $key, 'uniacid' => -1, 'v4flag' => 1), 'id');
        } else {
            $exists = pdo_getcolumn(PDO_NAME . 'setting', array('key' => $key, 'uniacid' => -1), 'id');
        }
        if ($exists) {
            $return = pdo_update(PDO_NAME . 'setting', $record, array('id' => $exists));
        } else {
            $record['key'] = $key;
            $record['uniacid'] = -1;
            $return = pdo_insert(PDO_NAME . 'setting', $record);
        }
        Cache::deleteCache('syssetting', $key);
        return $return;
    }

    /**
     * 系统设置读取
     * @param $key
     * @return array|string
     */
    static function wl_syssetting_read($key) {
        $settings = Cache::getCache('syssetting', $key);
        if (empty($settings)) {
            if ($key == 'taskcover') {
                $settings = pdo_get(PDO_NAME . 'setting', array('key' => $key, 'uniacid' => -1, 'v4flag' => 1), array('value'));
            } else {
                $settings = pdo_get(PDO_NAME . 'setting', array('key' => $key, 'uniacid' => -1), array('value'));
            }
            if (is_array($settings)) {
                $settings = iunserializer($settings['value']);
            } else {
                $settings = '';
            }
            Cache::setCache('syssetting', $key, $settings);
        }
        return $settings;
    }

    static function files_exit() {
        $file = PATH_MODULE . 'temp/upgrade_file.txt';
        if (!file_exists($file)) {
            return FALSE;
        }
        $upgrade_files = json_decode(file_get_contents($file), true);
        $upgrade_files = array_column($upgrade_files, 'path');

        $local_files = FilesHandle::file_tree(substr(PATH_MODULE, 0, -1));
        foreach ($local_files as $sk => &$sf) {
            if (strexists($sf, '.log') || strexists($sf, MODULE_NAME . '/data/') || strexists($sf, MODULE_NAME . '/temp/') || (strexists($sf, '/view/') && !strexists($sf, '/view/default/')) || strexists($sf, MODULE_NAME . '/icon.png') || strexists($sf, MODULE_NAME . '/icon-custom.jpg')) {
                unset($local_files[$sk]);
                continue;
            }
            $sf = str_replace(PATH_MODULE, "", $sf);
        }

        $diff_files = array_diff($local_files, $upgrade_files);
        foreach ($diff_files as $key => $value) {
            unlink(PATH_MODULE . $value);
        }
        FilesHandle::file_rm_empty_dir(PATH_MODULE);

        //异常文件需要删除
        $abnormal = array(PATH_CORE . '/common/func.php', IA_ROOT . '/app/func.php', IA_ROOT . '/web/func.php');
        foreach ($abnormal as $k => $val) {
            unlink($val);
        }
    }

    /**
     * 删除客户未购买的插件
     * @param $plugin
     */
    static function files_plugin_exit($plugin) {
        $dirs = scandir(PATH_PLUGIN);
        $delplugin = array_diff($dirs, $plugin, array('.', '..'));

        foreach ($delplugin as $key => $value) {
            if (!strexists($value, '.')) {
                Util::deleteAll(PATH_PLUGIN . $value, 1);
            }
        }
    }

    static function api_post($data = array()) {
        global $_W;
        if (empty($data['pd'])) {
            $data['pd'] = 'weliam_smartcity';
        }
        if (IMS_FAMILY == 'wl') {
            $data['ind'] = 'true';
        }
        $resp = Util::httpPost(WELIAM_API, $data);
        if (!is_error($resp)) {
            $resp = @json_decode($resp, true);
        }
        return $resp;
    }

}