<?php
defined('IN_IA') or exit('Access Denied');

class Areadb_WeliamController {

    //同步设置
    public function copydata() {
        global $_W, $_GPC;
        $agents = pdo_fetchall("SELECT agentname,id FROM " . tablename('wlmerchant_agentusers') . "WHERE uniacid = {$_W['uniacid']} ORDER BY id ASC");
        if (checksubmit('submit')) {
            $expressid = $_GPC['expressid'];
            $synchr = $_GPC['synchr'];
            $passiveid = $_GPC['passiveid'];
            $copystatus = $_GPC['copystatus'];
            if (empty($synchr)) {
                wl_message('请选择需要同步的内容', 'referer', 'error');
            }
            if (in_array($expressid, $passiveid)) {
                wl_message('目标代理不能包含模板代理', 'referer', 'error');
            }
            //循环插入数据
            if(empty($passiveid)){
                $passiveid = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_agentusers')."WHERE uniacid = {$_W['uniacid']} AND id != {$expressid} ORDER BY id DESC");
                foreach ($passiveid as $item) {
                    $this->insert_data($synchr, $item['id'], $expressid, $copystatus);
                }
            }else{
                foreach ($passiveid as $item) {
                    $this->insert_data($synchr, $item, $expressid, $copystatus);
                }
            }
            wl_message('同步完成，请检查各项是否同步正确', 'referer', 'success');
        }

        include wl_template('area/copydata');
    }

    public function movedata() {
        global $_W, $_GPC;
        ini_set('display_errors', '1');
        error_reporting(E_ALL ^ E_NOTICE);
        $tablenames = pdo_fetchall("SHOW TABLES LIKE :tablename", array(":tablename" => "%wlmerchant%"));
        $hastable = array();
        foreach ($tablenames as $tablename) {
            $table = str_replace($_W['config']['db']['tablepre'], '', end($tablename));
            //查询表所有字段，判断是否有aid
            $result = pdo_fetchall("SHOW FULL COLUMNS FROM " . tablename($table));
            foreach ($result as $key => $index) {
                if ($index['Field'] == 'aid') {
                    $hastable[] = $table;
                    if ($table == 'wlmerchant_agentsetting') {

                    } else {
                        //pdo_update($table, array('aid' => $new), array('aid' => $old));
                    }
                    break;
                }
            }
        }


        if (checksubmit('submit')) {

        }
        $agents = pdo_fetchall("SELECT agentname,id FROM " . tablename('wlmerchant_agentusers') . "WHERE uniacid = {$_W['uniacid']} ORDER BY id ASC");
        include wl_template('area/movedata');
    }

    private function my_db_table_schema($db, $tablename = '') {
        $result = $db->fetch("SHOW TABLE STATUS LIKE '" . trim($db->tablename($tablename), "`") . "'");
        if (empty($result) || empty($result['Create_time'])) {
            return array();
        }
        $ret["tablename"] = $result["Name"];
        $ret["charset"] = $result["Collation"];
        $ret["engine"] = $result["Engine"];
        $ret["increment"] = $result["Auto_increment"];
        $result = $db->fetchall("SHOW FULL COLUMNS FROM " . $db->tablename($tablename));
        foreach ($result as $value) {
            $temp = array();
            $type = explode(" ", $value["Type"], 2);
            $temp["name"] = $value["Field"];
            $pieces = explode("(", $type[0], 2);
            $temp["type"] = $pieces[0];
            $temp["length"] = rtrim($pieces[1], ")");
            $temp["null"] = !($value["Null"] == "NO");
            $temp["signed"] = empty($type[1]);
            $temp["increment"] = $value["Extra"] == "auto_increment";
            if (!empty($value['Comment'])) {
                $temp["comment"] = $value["Comment"];
            }
            if ($value["Default"] != NULL) {
                $temp["default"] = $value["Default"];
            }
            $ret["fields"][$value["Field"]] = $temp;
        }
        $result = $db->fetchall("SHOW INDEX FROM " . $db->tablename($tablename));
        foreach ($result as $value) {
            $ret["indexes"][$value["Key_name"]]["name"] = $value["Key_name"];
            $ret["indexes"][$value["Key_name"]]["type"] = $value["Key_name"] == "PRIMARY" ? "primary" : ($value["Non_unique"] == 0 ? "unique" : "index");
            $ret["indexes"][$value["Key_name"]]["fields"][] = $value["Column_name"];
        }
        return $ret;
    }

    private function insert_data($synchr, $passiveid, $expressid, $copystatus) {
        global $_W;
        if (in_array('base', $synchr)) {
            $bases = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_agentsetting') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid}  ORDER BY id ASC");
            foreach ($bases as $key => $base) {
                //基本设置同步中不包括 底部菜单&选项卡内容
                if ($base['key'] != 'pluginlist' && $base['key'] != 'foot') {
                    unset($base['id']);
                    $base['aid'] = $passiveid;
                    $flag = pdo_get('wlmerchant_agentsetting', array('aid' => $passiveid, 'key' => $base['key']), array('id'));
                    if ($flag) {
                        pdo_update('wlmerchant_agentsetting', $base, array('id' => $flag['id']));
                    } else {
                        pdo_insert('wlmerchant_agentsetting', $base);
                    }
                }
            }
        }
        if (in_array('merce', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_category_store', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $categorys = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_category_store') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND parentid = 0 ORDER BY id ASC");
            foreach ($categorys as $key => $category) {
                $childs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_category_store') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND parentid = {$category['id']} ORDER BY id ASC");
                unset($category['id']);
                $category['aid'] = $passiveid;
                pdo_insert('wlmerchant_category_store', $category);
                if ($childs) {
                    $parentid = pdo_insertid();
                    foreach ($childs as $key => $child) {
                        unset($child['id']);
                        $child['aid'] = $passiveid;
                        $child['parentid'] = $parentid;
                        pdo_insert('wlmerchant_category_store', $child);
                    }
                }
            }
        }
        if(in_array('yellowpage', $synchr)){
            if ($copystatus) {
                pdo_delete('wlmerchant_yellowpage_cates', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $categorys = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_yellowpage_cates') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND parentid = 0 ORDER BY id ASC");
            foreach ($categorys as $key => $category) {
                $childs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_yellowpage_cates') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND parentid = {$category['id']} ORDER BY id ASC");
                unset($category['id']);
                $category['aid'] = $passiveid;
                pdo_insert('wlmerchant_yellowpage_cates', $category);
                if ($childs) {
                    $parentid = pdo_insertid();
                    foreach ($childs as $key => $child) {
                        unset($child['id']);
                        $child['aid'] = $passiveid;
                        $child['parentid'] = $parentid;
                        pdo_insert('wlmerchant_yellowpage_cates', $child);
                    }
                }
            }
        }
        if (in_array('mergr', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_chargelist', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $mergrs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            if ($mergrs) {
                foreach ($mergrs as $key => $mergr) {
                    unset($mergr['id']);
                    $mergr['aid'] = $passiveid;
                    pdo_insert('wlmerchant_chargelist', $mergr);
                }
            }
        }
        if (in_array('fight', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_fightgroup_category', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $fights = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_fightgroup_category') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($fights as $key => $fight) {
                unset($fight['id']);
                $fight['aid'] = $passiveid;
                pdo_insert('wlmerchant_fightgroup_category', $fight);
            }
        }
        if (in_array('pocket', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_pocket_type', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $pockets = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_type') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND type = 0 ORDER BY id ASC");
            foreach ($pockets as $key => $pocket) {
                $childs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_type') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND type = {$pocket['id']} ORDER BY id ASC");
                unset($pocket['id']);
                $pocket['aid'] = $passiveid;
                pdo_insert('wlmerchant_pocket_type', $pocket);
                if ($childs) {
                    $parentid = pdo_insertid();
                    foreach ($childs as $key => $child) {
                        unset($child['id']);
                        $child['aid'] = $passiveid;
                        $child['type'] = $parentid;
                        pdo_insert('wlmerchant_pocket_type', $child);
                    }
                }
            }
        }
        if (in_array('notice', $synchr)) {

            if ($copystatus) {
                pdo_delete('wlmerchant_notice', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $notices = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_notice') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($notices as $key => $notice) {
                unset($notice['id']);
                $notice['aid'] = $passiveid;
                pdo_insert('wlmerchant_notice', $notice);
            }
        }
        if (in_array('adv', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_adv', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
                pdo_delete('wlmerchant_pocket_slide', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $advs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_adv') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($advs as $key => $adv) {
                unset($adv['id']);
                $adv['aid'] = $passiveid;
                pdo_insert('wlmerchant_adv', $adv);
            }
            //掌上信息
            $advs2 = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_slide') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($advs2 as $key => $ad) {
                unset($ad['id']);
                $ad['aid'] = $passiveid;
                pdo_insert('wlmerchant_pocket_slide', $ad);
            }
        }
        if (in_array('nav', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_nav', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $navs = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_nav') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($navs as $key => $nav) {
                unset($nav['id']);
                $nav['aid'] = $passiveid;
                pdo_insert('wlmerchant_nav', $nav);
            }
        }
        if (in_array('banner', $synchr)) {
            if ($copystatus) {
                pdo_delete('wlmerchant_banner', array('aid' => $passiveid, 'uniacid' => $_W['uniacid']));
            }
            $banners = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_banner') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} ORDER BY id ASC");
            foreach ($banners as $key => $banner) {
                unset($banner['id']);
                $banner['aid'] = $passiveid;
                pdo_insert('wlmerchant_banner', $banner);
            }
        }
        if (in_array('cube', $synchr)) {
            pdo_delete('wlmerchant_indexset', array('aid' => $passiveid, 'key' => 'cube', 'uniacid' => $_W['uniacid']));
            $cube = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_indexset') . "WHERE uniacid = {$_W['uniacid']} AND `key` = 'cube' AND aid = {$expressid} ORDER BY id ASC");
            unset($cube['id']);
            $cube['aid'] = $passiveid;
            pdo_insert('wlmerchant_indexset', $cube);
        }
        //选项卡同步
        if (in_array('selectCard', $synchr)) {
            $base = pdo_fetch("SELECT * FROM " . tablename(PDO_NAME . 'agentsetting') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND `key` = 'pluginlist'");
            unset($base['id']);
            $base['aid'] = $passiveid;
            $flag = pdo_get(PDO_NAME . 'agentsetting', array('aid' => $passiveid, 'key' => $base['key']), array('id'));
            if ($flag) {
                pdo_update(PDO_NAME . 'agentsetting', $base, array('id' => $flag['id']));
            } else {
                pdo_insert(PDO_NAME . 'agentsetting', $base);
            }
        }
        //底部菜单同步
        if (in_array('flootMenu', $synchr)) {
            $base = pdo_fetch("SELECT * FROM " . tablename(PDO_NAME . 'agentsetting') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$expressid} AND `key` = 'foot'");
            unset($base['id']);
            $base['aid'] = $passiveid;
            $flag = pdo_get(PDO_NAME . 'agentsetting', array('aid' => $passiveid, 'key' => $base['key']), array('id'));
            if ($flag) {
                pdo_update(PDO_NAME . 'agentsetting', $base, array('id' => $flag['id']));
            } else {
                pdo_insert(PDO_NAME . 'agentsetting', $base);
            }
            //清楚该代理商底部菜单的缓存信息
            Cache::deleteCache('setting', 'allagentset' . $passiveid);
        }
        //抢购分类信息同步
        if(in_array('rush_class', $synchr)){
            if ($copystatus) pdo_delete(PDO_NAME . "rush_category" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
            $list = pdo_getall(PDO_NAME."rush_category",['aid'=>$expressid,'uniacid'=>$_W['uniacid']]
                ,['uniacid','name','sort','thumb','is_show'],'','id ASC');
            foreach ($list as $key => $fight) {
                $fight['aid'] = $passiveid;
                pdo_insert(PDO_NAME."rush_category", $fight);
            }
        }
        //团购分类信息同步
        if(in_array('group_class', $synchr)){
            if ($copystatus) pdo_delete(PDO_NAME . "groupon_category" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
            $list = pdo_getall(PDO_NAME."groupon_category",['aid'=>$expressid,'uniacid'=>$_W['uniacid']]
                    ,['uniacid','name','sort','thumb','is_show'],'','id ASC');
            foreach ($list as $key => $fight) {
                $fight['aid'] = $passiveid;
                pdo_insert(PDO_NAME."groupon_category", $fight);
            }
        }
        //砍价分类信息同步
        if(in_array('bargain_class', $synchr)){
            if ($copystatus) pdo_delete(PDO_NAME . "bargain_category" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
            $list = pdo_getall(PDO_NAME."bargain_category",['aid'=>$expressid,'uniacid'=>$_W['uniacid']]
                ,['uniacid','name','sort','thumb','is_show'],'','id ASC');
            foreach ($list as $key => $fight) {
                $fight['aid'] = $passiveid;
                pdo_insert(PDO_NAME."bargain_category", $fight);
            }
        }
        //同城名片设置同步
        if(in_array('citycard', $synchr)) {
            if ($copystatus){
                pdo_delete(PDO_NAME . "citycard_cates" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
                pdo_delete(PDO_NAME . "citycard_meals" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
                pdo_delete(PDO_NAME . "citycard_tops" , ['aid' => $passiveid , 'uniacid' => $_W['uniacid']]);
            }
            //分类
            $parentCate = pdo_getall('wlmerchant_citycard_cates',array('aid' => $expressid,'uniacid'=>$_W['uniacid'],'parentid' =>0),[],'','id ASC');
            if(!empty($parentCate)){
                foreach ($parentCate as $par){
                    $childrenCate = pdo_getall('wlmerchant_citycard_cates',array('aid' => $expressid,'uniacid'=>$_W['uniacid'],'parentid' =>$par['id']),array('uniacid','name','sort','enabled'),'','id ASC');
                    unset($par['id']);
                    $par['aid'] = $passiveid;
                    pdo_insert(PDO_NAME."citycard_cates", $par);
                    $newparentid = pdo_insertid();
                    if(!empty($childrenCate)){
                        foreach ($childrenCate as $child){
                            $child['aid'] = $passiveid;
                            $child['parentid'] = $newparentid;
                            pdo_insert(PDO_NAME."citycard_cates", $child);
                        }
                    }
                }
            }
            //套餐与置顶
            $list = pdo_getall(PDO_NAME."citycard_meals",['aid'=>$expressid,'uniacid'=>$_W['uniacid']]
                ,['uniacid','name','sort','is_free','price','day','check','status','isdistri','onedismoney','twodismoney'],'','id ASC');
            foreach ($list as $key => $meal) {
                $meal['aid'] = $passiveid;
                pdo_insert(PDO_NAME."citycard_meals", $meal);
            }
            $list = pdo_getall(PDO_NAME."citycard_tops",['aid'=>$expressid,'uniacid'=>$_W['uniacid']]
                ,['uniacid','name','sort','price','day','status','isdistri','onedismoney','twodismoney'],'','id ASC');
            foreach ($list as $key => $top) {
                $top['aid'] = $passiveid;
                pdo_insert(PDO_NAME."citycard_tops", $top);
            }
        }








        }
}