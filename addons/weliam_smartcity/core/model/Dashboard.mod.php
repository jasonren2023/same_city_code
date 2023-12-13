<?php
defined('IN_IA') or exit('Access Denied');

class Dashboard {
    /////////////////////////////////////////////////////首页参数设置/////////////////////////////////////////////////////////////
    /*
     * 读取参数
     */
    static function readSetting($key) {
        global $_W;
        $settings = pdo_get(PDO_NAME . 'indexset', array('key' => $key, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('value'));
        if (is_array($settings)) {
            $settings = iunserializer($settings['value']);
        } else {
            $settings = array();
        }
        return $settings;
    }

    /*
     * 保存参数
     */
    static function saveSetting($data, $key) {
        global $_W;
        if (empty($key)) return FALSE;
        $record = array();
        $record['value'] = iserializer($data);

        $exists = pdo_getcolumn(PDO_NAME . 'indexset', array('key' => $key, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), 'id');
        if ($exists) {
            $return = pdo_update(PDO_NAME . 'indexset', $record, array('key' => $key, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        } else {
            $record['key'] = $key;
            $record['uniacid'] = $_W['uniacid'];
            $record['aid'] = $_W['aid'];
            $return = pdo_insert(PDO_NAME . 'indexset', $record);
        }
        return $return;
    }

    /////////////////////////////////////////////////////幻灯片/////////////////////////////////////////////////////////////
    /*
     * 查询所有幻灯片
     */
    static function getAllAdv($page = 0, $pagenum = 10, $enabled = '', $type = '', $namekey = '') {
        global $_W;
        $condition = '';
        if (!empty($enabled) && $enabled != '') $condition = " and enabled=" . $enabled;
        if ($type == -1) {
            $condition .= " and type = 0";
        } else if ($type) {
            $condition .= " and type=" . $type;
        }
        if ($namekey) $condition = " and advname like '%" . $namekey . "%'";
        $re['data'] = pdo_fetchall("select * from" . tablename(PDO_NAME . 'adv') . " where uniacid=:uniacid and aid=:aid " . $condition . " order by type asc,enabled desc,displayorder desc limit " . $page * $pagenum . "," . $pagenum, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'adv') . "where uniacid=:uniacid and aid=:aid " . $condition, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        return $re;
    }

    /*
     * 编辑幻灯片
     */
    static function editAdv($arr, $id = '') {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') return pdo_update(PDO_NAME . 'adv', $arr, array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        $arr['aid'] = $_W['aid'];
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME . 'adv', $arr);
    }

    /*
     * 获取单条幻灯片详情
     */
    static function getSingleAdv($id) {
        global $_W;
        if (empty($id)) return false;
        return pdo_get(PDO_NAME . 'adv', array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
    }


    /////////////////////////////////////////////////////导航栏/////////////////////////////////////////////////////////////
    /*
     * 查询所有导航栏
     */
    static function getAllNav($page = 0, $pagenum = 10, $enabled = '', $merchantid = '') {
        global $_W;
        $condition = '';
        if (!empty($enabled) && $enabled != '') $condition = " and enabled=" . $enabled;
        if (!empty($merchantid) && $merchantid != '') {
            $condition = " and merchantid=" . $merchantid;
        } else {
            $condition = " and merchantid= 0";
        }
        $re['data'] = pdo_fetchall("select * from" . tablename(PDO_NAME . 'nav') . "where uniacid=:uniacid and aid=:aid " . $condition . " order by enabled desc, displayorder desc limit " . $page * $pagenum . "," . $pagenum, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'nav') . "where uniacid=:uniacid and aid=:aid " . $condition, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        return $re;
    }

    /*
     * 编辑导航栏
     */
    static function editNav($arr, $id = '') {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') return pdo_update(PDO_NAME . 'nav', $arr, array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        $arr['aid'] = $_W['aid'];
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME . 'nav', $arr);
    }

    /*
     * 获取单条导航栏详情
     */
    static function getSingleNav($id) {
        global $_W;
        if (empty($id)) return false;
        return pdo_get(PDO_NAME . 'nav', array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
    }

    /////////////////////////////////////////////////////广告位/////////////////////////////////////////////////////////////
    /*
     * 查询所有广告位
     */
    static function getAllBanner($page = 0, $pagenum = 10, $enabled = '') {
        global $_W;
        $condition = '';
        if (!empty($enabled) && $enabled != '') $condition = " and enabled=" . $enabled;
        $re['data'] = pdo_fetchall("select * from" . tablename(PDO_NAME . 'banner') . "where uniacid=:uniacid and aid=:aid " . $condition . " order by enabled desc, displayorder desc limit " . $page * $pagenum . "," . $pagenum, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'banner') . "where uniacid=:uniacid and aid=:aid " . $condition, array(':uniacid' => $_W['uniacid'], ':aid' => $_W['aid']));
        return $re;
    }

    /*
     * 编辑广告位
     */
    static function editBanner($arr, $id = '') {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') return pdo_update(PDO_NAME . 'banner', $arr, array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        $arr['aid'] = $_W['aid'];
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME . 'banner', $arr);
    }

    /*
     * 获取单条广告位详情
     */
    static function getSingleBanner($id) {
        global $_W;
        if (empty($id)) return false;
        return pdo_get(PDO_NAME . 'banner', array('id' => $id, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
    }


    /*
     * 获取手机端首页数据
     */
    static function get_app_data() {
        global $_W;
        $default_page = array(
            array('on' => 1, 'sort' => 'search'),
            array('on' => 1, 'sort' => 'adv'),
            array('on' => 1, 'sort' => 'nav'),
            array('on' => 1, 'sort' => 'notice'),
            array('on' => 1, 'sort' => 'banner'),
            array('on' => 1, 'sort' => 'cube'),
            array('on' => 1, 'sort' => 'nearby')
        );
        $load_page = self::readSetting('sort');
        $page = !empty($load_page) ? $load_page : $default_page;
        $advs = pdo_getall(PDO_NAME . 'adv', array('enabled' => 1, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'type' => 0));
        $nav = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . 'nav') . " WHERE enabled = 1 and merchantid = 0 and uniacid = '{$_W['uniacid']}' and aid = {$_W['aid']} ORDER BY displayorder DESC");
        $banner = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . 'banner') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and aid = {$_W['aid']} ORDER BY displayorder DESC");
        $notice = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . 'notice') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and aid = {$_W['aid']} ORDER BY id DESC");
        $cubes = self::readSetting('cube');
        foreach ($cubes as $k => $v) {
            if (empty($v['thumb']) || $v['on'] == 0) {
                unset($cubes[$k]);
            }
        }
        $cubes = array_merge($cubes);
        return array('page' => $page, 'adv' => $advs, 'nav' => $nav, 'banner' => $banner, 'notice' => $notice, 'cubes' => $cubes);
    }

    static function set_agent_cookie($aid, $type = 'aid') {
        global $_W, $_GPC;
        $where = " a.uniacid = {$_W['uniacid']} AND a.status = 1 ";
        if ($type == 'aid') {
            $where .= " AND a.aid = " . $aid;
        } else {
            $where .= " AND a.areaid = " . $aid;
        }
        $oparea = pdo_fetch("SELECT a.areaid,a.aid,b.name,b.level,b.pid FROM " . tablename(PDO_NAME . "oparea") . " a LEFT JOIN " . tablename(PDO_NAME . "area") . " b ON a.areaid = b.id WHERE " . $where);
        if (empty($oparea)) {
            return error(1, '当前地区无代理');
        }

        //非总后台判断代理状态和时间
        if (!empty($oparea['aid'])) {
            $agent = pdo_get(PDO_NAME . 'agentusers', array('id' => $oparea['aid'], 'uniacid' => $_W['uniacid']), array('status', 'endtime'));
            if ($agent['endtime'] < time()) {
                return error(1, '当前地区代理已过期');
            }
            if ($agent['status'] != 1) {
                return error(1, '当前地区未启用');
            }
        }

        //如果存在详细地点则区域显示详细地点，否则显示区域城市信息
        $locateInfo = wl_getcookie('locate_information');
        if ($_W['wlsetting']['areaset']['location'] == 1 && is_array($locateInfo) && !empty($locateInfo)) {
            $_W['location']['lat'] = $locateInfo['lat'];
            $_W['location']['lng'] = $locateInfo['lng'];
        }

        $_W['aid'] = $oparea['aid'];
        $_W['areaid'] = $oparea['areaid'];
        $_W['citycode'] = $locateInfo['citycode'] ? $locateInfo['citycode'] : self::get_city_code($oparea['areaid'], $oparea['level'], $oparea['pid']);
        $_W['areaname'] = (!empty($locateInfo['title']) && $_W['wlsetting']['areaset']['location'] == 1) ? $locateInfo['title'] : $oparea['name'];

        wl_setcookie("agentareaid", $_W['areaid'], 30 * 86400);
        return TRUE;
    }

    static function get_city_code($areaid, $level, $pid) {
        switch ($level) {
            case 1:
                $city = (new AreaTable())->selectFields('id')->where('pid', $areaid)->get();
                $citycode = $city['id'];
                break;
            case 2:
                $citycode = $areaid;
                break;
            case 3:
                $citycode = $pid;
                break;
            default:
                $city = (new AreaTable())->selectFields('pid')->where('id', $pid)->get();
                $citycode = $city['id'];
        }
        return $citycode;
    }
}
