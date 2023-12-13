<?php
class Adviser_WeliamController {


    public function adviserList() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if($_W['aid'] > 0) $data['aid'] = $_W['aid'];

        if($_GPC['name']) $data['name'] = $_GPC['name'];


        $pageStart = $pindex * $psize - $psize;
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($_W['aid'] > 0) $where .= " AND a.aid = {$_W['aid']} ";
        if($_GPC['name']) $where .= " AND b.nickname LIKE '%{$_GPC['name']}%'";
        if($_GPC['status']) $where .= " AND a.status = {$_GPC['status']}";
        if(is_store()) $where .= " AND a.shop_id = {$_W['storeid']}";
        //sql语句生成
        $field = "a.id,a.aid,a.weigh,a.phone,a.status,a.corporate_name,a.user_id,a.shop_id,
        b.nickname,b.avatar,b.mobile,
        c.storename,c.logo";
        $order = " ORDER BY a.weigh DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_adviser")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.user_id = b.id LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as c ON a.shop_id = c.id ";
        //列表获取
        $list = pdo_fetchall( $sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['avatar'] = tomedia($val['avatar']);
            $val['storelogo'] = tomedia($val['logo']);
        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);




        include wl_template('adviser/index');
    }


    public function adviserDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."house_adviser",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function adviserEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;


        $orderBy = " ORDER BY id DESC ";
        $labelTab = tablename(PDO_NAME . "house_label");
        $adviser = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  11".  $orderBy);

        if($id > 0){
            //获取信息
            $info = pdo_get(PDO_NAME."house_adviser",['id'=>$id]);
            $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$info['shop_id']]);

            $houseAdviserHouseTab = tablename(PDO_NAME . "house_adviser_house");
            $houseAdviserHouse = pdo_fetchall("SELECT id,adviser_id,house_id,`type` FROM " . $houseAdviserHouseTab ." WHERE adviser_id =  {$id}".  $orderBy);
            $newHouse_ids = [];
            $renting_ids = [];
            $oldHouse_ids = [];
            foreach ($houseAdviserHouse as $value){
                if ($value['type'] == 1){
                    $newHouse_ids[] = $value['house_id'];
                }elseif ($value['type'] == 3) {
                    $renting_ids[] = $value['house_id'];
                }elseif ($value['type'] == 2) {
                    $oldHouse_ids[] = $value['house_id'];
                }
            }
            $newHouseTab = tablename(PDO_NAME . "new_house");
            $newHouse = pdo_fetchall("SELECT id,title FROM " . $newHouseTab ." WHERE shop_id = {$info['shop_id']}  " .$orderBy);
            $rentingTab = tablename(PDO_NAME . "renting");
            $renting = pdo_fetchall("SELECT id,title FROM " . $rentingTab ." WHERE releasetype = 2 AND  shop_id = {$info['shop_id']}  " .  $orderBy);
            $oldHouseTab = tablename(PDO_NAME . "old_house");
            $oldHouse = pdo_fetchall("SELECT id,title FROM " . $oldHouseTab ." WHERE releasetype = 2  AND shop_id = {$info['shop_id']}  " .  $orderBy);
        }


        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['adviser_ids'] = implode(',',$service['adviser_ids']);
            $newHouse_ids = $service['newHouse_ids'];
            $renting_ids = $service['renting_ids'];
            $oldHouse_ids = $service['oldHouse_ids'];
            if (is_store()){
                $service['shop_id'] = $_W['storeid'];
            }
            unset($service['newHouse_ids']);
            unset($service['renting_ids']);
            unset($service['oldHouse_ids']);

            if(empty($service['user_id'])){
                wl_message('请选择用户');
            }
            if(empty($service['shop_id'])){
                wl_message('请选择所属商户');
            }

            $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$service['shop_id']]);

            $service['current_province'] = $shop['provinceid'];
            $service['current_city'] = $shop['areaid'];
            $service['current_area'] = $shop['distid'];
            if($id > 0){
                $res = pdo_update(PDO_NAME."house_adviser",$service,array('id' => $id));
            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $res = pdo_insert(PDO_NAME."house_adviser",$service);
                $id = pdo_insertid();
            }

            $type_arr = [];
            $arr = [];
            if (!empty($newHouse_ids)){
                $type_arr[] = 1;
                $arr[1] = $newHouse_ids;
            }
            if (!empty($renting_ids)){
                $type_arr[] = 3;
                $arr[3] = $renting_ids;
            }
            if (!empty($oldHouse_ids)){
                $type_arr[] = 2;
                $arr[2] = $oldHouse_ids;
            }
            if (!empty($type_arr)){
                $type_str = implode(',',$type_arr);
                $sql = " DELETE FROM ".tablename(PDO_NAME."house_adviser_house")." WHERE adviser_id = {$id} AND `type` in ($type_str)";
                pdo_query($sql);
                $insert_arr = [];
                foreach ($arr as $k=>$v){
                    foreach ($v as $j){
                        $insert_arr = [
                            'uniacid' => $_W['uniacid'],
                            'aid' => $_W['aid'],
                            'adviser_id' => $id,
                            'house_id' => $j,
                            'type' => $k,
                        ];
                        $res = pdo_insert(PDO_NAME."house_adviser_house",$insert_arr);
                    }
                }
            }

            if($res){
                wl_message('信息保存成功',web_url('house/Adviser/adviserList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('adviser/edit');
    }

    function storehouseinfo(){
        global $_W,$_GPC;
        $sid = $_GPC['storeid'];

        $orderBy = " ORDER BY id DESC ";
        $newHouseTab = tablename(PDO_NAME . "new_house");
        $newHouse = pdo_fetchall("SELECT id,title FROM " . $newHouseTab ." WHERE shop_id = {$sid}  " .$orderBy);
        $rentingTab = tablename(PDO_NAME . "renting");
        $renting = pdo_fetchall("SELECT id,title FROM " . $rentingTab ." WHERE releasetype = 2 AND  shop_id = {$sid}  " .  $orderBy);
        $oldHouseTab = tablename(PDO_NAME . "old_house");
        $oldHouse = pdo_fetchall("SELECT id,title FROM " . $oldHouseTab ." WHERE releasetype = 2  AND shop_id = {$sid}  " .  $orderBy);


        include wl_template('adviser/storehouseinfo');
    }



}
