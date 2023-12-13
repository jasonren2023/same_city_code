<?php
class House_WeliamController {

    protected $type_arr = [
//        1 => "销售状态",
//        2 => "购买时间",
        3 => "建筑类型",
        4 => "装修状态",
        5 => "小区特色",
//        6 => "物业类型",
//        7 => "价格类别",
//        8 => "户型标签",
        9 => "配置家具",
        10 => "租房标签",
        11 => "顾问标签",
        12 => "二手房标签",
        13 => "新房标签"
    ];
    protected $orientation_arr = [
        0 => "未知",
        1 => "朝南",
        2 => "东南",
        3 => "朝东",
        4 => "西南",
        5 => "朝北",
        6 => "朝西",
        7 => "东北",
        8 => "西北",
    ];
    protected $status_arr = [
        1 => "未上架",
        2 => "销售中",
        3 => "已售出",
        4 => "待审核",
        5 => "未通过",
    ];
    protected $renting_status_arr = [
        1 => "未上架",
        2 => "待出租",
        3 => "已出租",
        4 => "待审核",
        5 => "未通过",
    ];

    /**
     * Comment: 地区列表（已废弃）
     */
    public function areaIndex() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if($_W['aid'] > 0) $data['aid'] = $_W['aid'];

        if($_GPC['name']) $data['name'] = $_GPC['name'];

        $info = Util::getNumData('*', PDO_NAME . 'house_area', $data, 'weigh DESC', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];

        $all = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . "house_area") . " WHERE pid = 0 ");
        $pid_name_arr = array_column($all,'name','id');


        include wl_template('area/index');
    }


    public function areaDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."house_area",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function areaEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."house_area",['id'=>$id]);

        $AreaTab = tablename(PDO_NAME . "house_area");
        $orderBy = " ORDER BY id ASC ";
        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid = 0 ".  $orderBy);
        array_unshift($allgroup,'');


        if ($_W['ispost']) {
            $service = $_GPC['info'];

            if(empty($service['name'])){
                wl_message('请输入名称');
            }

            if($id > 0){
                $res = pdo_update(PDO_NAME."house_area",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."house_area",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/areaIndex'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('area/edit');
    }


    public function labelList() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if($_W['aid'] > 0) $data['aid'] = $_W['aid'];

        if($_GPC['name']) $data['name'] = $_GPC['name'];
        if($_GPC['type']) $data['type'] = $_GPC['type'];

        $info = Util::getNumData('*', PDO_NAME . 'house_label', $data, 'ID DESC', $pindex, $psize, 1);
        $list = $info[0];
        foreach($list as $key => &$val){
            $val['image'] = tomedia($val['image']);
        }
        $pager = $info[1];

        $type_arr = $this->type_arr;


        include wl_template('label/index');
    }


    public function labelDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."house_label",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function labelEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."house_label",['id'=>$id]);
        $type_arr = $this->type_arr;



        if ($_W['ispost']) {
            $service = $_GPC['info'];

            if(empty($service['title'])){
                wl_message('请输入标题');
            }

            if($id > 0){
                $res = pdo_update(PDO_NAME."house_label",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $res = pdo_insert(PDO_NAME."house_label",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/labelList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('label/edit');
    }



    public function apartmentList() {
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
        if($_GPC['name']) $where .= " AND a.name LIKE '%{$_GPC['name']}%' ";
        //sql语句生成
        $field = "a.id,a.aid,a.name,a.cover_image,a.averageprice,a.shop_id,a.create_time,a.address,
        b.storename";
        $order = " ORDER BY a.id DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_apartment")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['cover_image'] = tomedia($val['cover_image']);
        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);



        $all = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . "house_area") );
        $pid_name_arr = array_column($all,'name','id');



        include wl_template('apartment/index');
    }


    public function apartmentDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."house_apartment",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function apartmentEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."house_apartment",['id'=>$id]);
        $info['cover_images'] = unserialize($info['cover_images']);

//        $AreaTab = tablename(PDO_NAME . "house_area");
        $orderBy = " ORDER BY weigh DESC,id DESC ";
//        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid = 0 ".  $orderBy);
////        array_unshift($allgroup,'');
//
//        $secondgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid =  {$info['area_one_id']}".  $orderBy);
//        array_unshift($secondgroup,'');


        $labelTab = tablename(PDO_NAME . "house_label");
        $characteristic = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  5".  $orderBy);
        $construction = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  3".  $orderBy);


        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";
        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['current_city']}'");
        $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['current_area']}'");
        $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);
        //富文本处理
        if(is_base64($info['describe'])) $info['describe']   = htmlspecialchars_decode(base64_decode($info['describe']));


        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['construction_time'] = strtotime($service['construction_time']);
            $service['cover_images']      = serialize($service['cover_images']);
            $service['characteristic_ids'] = implode(',',$service['characteristic_ids']);

            if(empty($service['name'])){
                wl_message('请输入名称');
            }
            if(empty($service['cover_image'])){
                wl_message('封面图片不能为空');
            }
            if(empty($service['describe'])){
                wl_message('小区描述不能为空');
            }
            $service['describe'] = base64_encode(htmlspecialchars_decode($service['describe']));

            if(empty($service['area_size'])){
                wl_message('占地面积(㎡)不能为空');
            }
            if(empty($service['architecture_size'])){
                wl_message('建筑面积(㎡)不能为空');
            }
            if(empty($service['volume_rate'])){
                wl_message('容积率不能为空');
            }

            if($id > 0){
                $res = pdo_update(PDO_NAME."house_apartment",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."house_apartment",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/apartmentList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('apartment/edit');
    }

    /**
     * Comment: 获取某个分类的所有下级分类
     */
    public function getSubClass() {
        global $_W, $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'];
        if (!$id) wl_json(0, '缺少参数：id不存在');


        $AreaTab = tablename(PDO_NAME . "house_area");
        $orderBy = " ORDER BY id ASC ";
        $secondgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid =  {$id}".  $orderBy);


        wl_json(0, '下级分类列表', $secondgroup);
    }





    public function newHouseList() {
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
        if($_GPC['name']) $where .= " AND (a.name LIKE '%{$_GPC['name']}%' OR c.name LIKE '%{$_GPC['name']}%') ";
        if($_GPC['status']) $where .= " AND a.status = {$_GPC['status']}";

        if(is_store()) $where .= " AND a.shop_id = {$_W['storeid']}";

        //sql语句生成
        $field = "a.id,a.aid,a.title,a.cover_image,a.orderid,a.status,a.weigh,a.start_time,a.delivery_time,a.average_price,a.developers,a.investor,
        b.storename,b.logo,
        c.name as apartment_name,c.cover_image as apartment_image";
        $order = " ORDER BY a.weigh DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."new_house")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id "
            ." LEFT JOIN ".tablename(PDO_NAME."house_apartment")
            ." as c ON a.apartment_id = c.id ";
        //列表获取
        $list = pdo_fetchall( $sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['cover_image'] = tomedia($val['cover_image']);
            $val['start_time'] = date('Y-m-d',$val['start_time']);
            $val['delivery_time'] = date('Y-m-d',$val['delivery_time']);
//            $val['status'] = ($val['status'] == 2 ? '已下架' : '销售中');
        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);




        include wl_template('newhouse/index');
    }


    public function newHouseDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."new_house",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function newHouseEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."new_house",['id'=>$id]);
        $info['matching_images'] = unserialize($info['matching_images']);
        $info['plan_images'] = unserialize($info['plan_images']);
        $info['outdoor_scene_images'] = unserialize($info['outdoor_scene_images']);
        $info['sand_table_images'] = unserialize($info['sand_table_images']);
        $info['example_images'] = unserialize($info['example_images']);

        $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$info['shop_id']]);

        $AreaTab = tablename(PDO_NAME . "house_apartment");
        $orderBy = " ORDER BY id ASC ";
        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab .  $orderBy);
//        array_unshift($allgroup,'');
        //富文本处理
        if(is_base64($info['describe'])) $info['describe']   = htmlspecialchars_decode(base64_decode($info['describe']));

        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";
        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['current_city']}'");
        $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['current_area']}'");
        $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);



        $labelTab = tablename(PDO_NAME . "house_label");
        $decoration = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  4 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);
        $new_label = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  13 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);

        $house_type_arr =  pdo_fetchall("SELECT id,title FROM " . tablename(PDO_NAME . "house_label") . " WHERE type =  3 ORDER BY weigh DESC,id DESC ");
        $orientation_arr = $this->orientation_arr;

        //房型
        //房型
        if($id > 0){
            $_modelInfo = pdo_getall(PDO_NAME."house_model",['house_id'=>$id]);
        }else{
            $_modelInfo = [];
        }


        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['start_time'] = strtotime($service['start_time']);
            $service['delivery_time'] = strtotime($service['delivery_time']);
            $service['use_year'] = (int)($service['use_year']);
            $service['new_ids'] = implode(',',$service['new_ids']);
            if (is_store()){
                $service['shop_id'] = $_W['storeid'];
            }
            $modelInfo = $_GPC['modelInfo'];

            if(empty($service['title'])){
                wl_message('请输入楼盘名称');
            }
            if(empty($service['cover_image'])){
                wl_message('请选择封面图片');
            }
            if(empty($service['describe'])){
                wl_message('请输入描述信息');
            }
            $service['describe'] = base64_encode(htmlspecialchars_decode($service['describe']));
            $service['example_images']      = serialize($service['example_images']);
            $service['sand_table_images']      = serialize($service['sand_table_images']);
            $service['outdoor_scene_images']      = serialize($service['outdoor_scene_images']);
            $service['plan_images']      = serialize($service['plan_images']);
            $service['matching_images']      = serialize($service['matching_images']);
            if($id > 0){
                $res = pdo_update(PDO_NAME."new_house",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."new_house",$service);
                $id = pdo_insertid();
            }

            $sql = " DELETE FROM ".tablename(PDO_NAME."house_model")." WHERE house_id = {$id} ";
            $res = pdo_query($sql);
            if (!empty($modelInfo)){
                foreach ($modelInfo as $value){
                    if ($value['room']){
                        $insert_arr = [
                            'uniacid' => $_W['uniacid'],
                            'aid' => $_W['aid'],
                            'house_id' => $id,
                            'status' => $value['status'],
                            'room' => $value['room'],
                            'office' => $value['office'],
                            'wei' => $value['wei'],
                            'kitchen' => $value['kitchen'],
                            'architecture_size' => $value['architecture_size'],
                            'orientation' => $value['orientation'],
                            'average_price' => $value['average_price'],
                            'cover_image' => $value['cover_image'],
                        ];
                        $res = pdo_insert(PDO_NAME."house_model",$insert_arr);
                    }
                }

            }


//            if($res){
                wl_message('信息保存成功',web_url('house/House/newHouseList'), 'success');
//            }else{
//                wl_message('信息保存失败，请重试');
//            }

        }


        include wl_template('newhouse/edit');
    }

    public function housemodel() {
        global $_W, $_GPC;
        $j = $_GPC['kw'];
        $cover_image_key = "modelInfo[$j][cover_image]";
        $orientation_arr = $this->orientation_arr;
        include wl_template('newhouse/housemodel');
    }

    public function oldHouseList() {
        global $_W, $_GPC;

        $orientation_arr = $this->orientation_arr;
        $status_arr = $this->status_arr;

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
        if($_GPC['name']) $where .= " AND (a.title LIKE '%{$_GPC['name']}%' OR a.apartment_name LIKE '%{$_GPC['name']}%' OR b.storename LIKE '%{$_GPC['name']}%') ";
        if($_GPC['status']) $where .= " AND a.status = {$_GPC['status']}";
        if(is_store()) $where .= " AND a.shop_id = {$_W['storeid']}";
        //sql语句生成
        $field = "a.id,a.aid,a.apartment_name,a.orderid,a.staff_id,a.house_type,a.releasetype,a.title,a.apartment_id,a.cover_image,a.weigh,a.orientation,a.price,a.address,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.status,a.user_id,a.shop_id,
        b.storename";
        $order = " ORDER BY a.weigh DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."old_house")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id ";
        //列表获取
        $list = pdo_fetchall( $sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['cover_image'] = tomedia($val['cover_image']);
            //朝向
            $val['orientation_text'] = $orientation_arr[$val['orientation']];
            //建筑类型
            $val['house_text'] =  pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$val['house_type']),'title');
            //所属小区
            if($val['apartment_id'] > 0){
                $apartment = pdo_get('wlmerchant_house_apartment',array('id' => $val['apartment_id']),array('cover_image','name'));
                $val['apartment_name'] = $apartment['name'];
                $val['apartment_image'] = $apartment['cover_image'];
            }
            //发布方
            if($val['releasetype'] == 1){
                $member = pdo_get('wlmerchant_member',array('id' => $val['user_id']),array('nickname','encodename','mobile','avatar'));
                $val['releasename'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
                $val['releaselogo'] = $member['avatar'];
                $val['releasemobile'] = $member['mobile'];
            }else{
                $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $val['shop_id']),array('storename','mobile','logo'));
                $val['releasename'] = $merchant['storename'];
                $val['releaselogo'] = $merchant['logo'];
                $val['releasemobile'] = $merchant['mobile'];
            }
        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);


        include wl_template('oldhouse/index');
    }


    public function oldHouseDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."old_house",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function oldHouseEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."old_house",['id'=>$id]);
        $info['images'] = unserialize($info['images']);
        if (!$info['status']){
            $info['status'] = 1;
        }
        $info['room'] ?: $info['room'] = 1;
        $info['office'] ?: $info['office'] = 1;
        $info['wei'] ?: $info['wei'] = 1;
        $info['kitchen'] ?: $info['kitchen'] = 1;
        //富文本处理
        if(is_base64($info['describe'])) $info['describe']   = htmlspecialchars_decode(base64_decode($info['describe']));


        $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$info['shop_id']]);

        $orientation_arr = $this->orientation_arr;

        $AreaTab = tablename(PDO_NAME . "house_apartment");
        $orderBy = " ORDER BY id ASC ";
        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab .  $orderBy);



        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";
        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['current_city']}'");
        $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['current_area']}'");
        $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);


        $labelTab = tablename(PDO_NAME . "house_label");
        $decoration = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  4 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);
        $facilities = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  9 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);
        $old = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  12 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);

        if($info['shop_id'] > 0){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $info['shop_id']),array('storename','logo'));
            $info['storename'] = $merchant['storename'];
            $info['storelogo'] = tomedia($merchant['logo']);
        }

        $house_type_arr = pdo_fetchall("SELECT id,title FROM " . tablename(PDO_NAME . "house_label") . " WHERE type =  3 ORDER BY weigh DESC,id DESC ");


        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['images']      = serialize($service['images']);
            $service['facilities_ids'] = implode(',',$service['facilities_ids']);
            $service['old_ids'] = implode(',',$service['old_ids']);
            $service['apartment_name'] = pdo_getcolumn(PDO_NAME.'house_apartment',array('id'=>$service['apartment_id']),'name');
            if (is_store()){
                $service['shop_id'] = $_W['storeid'];
                $service['releasetype'] = 2;
            }

            if(empty($service['title'])){
                wl_message('请输入标题');
            }
            if(empty($service['cover_image'])){
                wl_message('请选择封面图片');
            }
            if(empty($service['architecture_size'])){
                wl_message('建筑面积(㎡)不能为空');
            }
            $service['describe'] = base64_encode(htmlspecialchars_decode($service['describe']));

            if($id > 0){
                $res = pdo_update(PDO_NAME."old_house",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."old_house",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/oldHouseList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('oldhouse/edit');
    }


    public function rentingList() {
        global $_W, $_GPC;

        $orientation_arr = $this->orientation_arr;
        $renting_status_arr = $this->renting_status_arr;
        $house_type_arr =  pdo_fetchall("SELECT id,title FROM " . tablename(PDO_NAME . "house_label") . " WHERE type =  3 ORDER BY weigh DESC,id DESC ");

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
        if($_GPC['name']) $where .= " AND (a.title LIKE '%{$_GPC['name']}%' OR a.apartment_name LIKE '%{$_GPC['name']}%' OR b.storename LIKE '%{$_GPC['name']}%') ";
        if($_GPC['status']) $where .= " AND a.status = {$_GPC['status']}";
        if($_GPC['releasetype']) $where .= " AND a.releasetype = {$_GPC['releasetype']}";

        if(is_store()) $where .= " AND a.releasetype = 2 AND a.shop_id = {$_W['storeid']}";
        //sql语句生成
        $field = "a.id,a.aid,a.apartment_name,a.name,a.orderid,a.title,a.cover_image,a.releasetype,a.weigh,a.apartment_id,a.orientation,a.house_type,a.type,a.room_number,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.rent,a.deposit,a.pay,a.status,a.user_id,a.shop_id,
        b.storename";
        $order = " ORDER BY a.weigh DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."renting")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id ";
        //列表获取
        $list = pdo_fetchall( $sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['cover_image'] = tomedia($val['cover_image']);
            //朝向
            $val['orientation_text'] = $orientation_arr[$val['orientation']];
            //建筑类型
            $val['house_text'] =  pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$val['house_type']),'title');
            //所属小区
            if($val['apartment_id'] > 0){
                $apartment = pdo_get('wlmerchant_house_apartment',array('id' => $val['apartment_id']),array('cover_image','name'));
                $val['apartment_name'] = $apartment['name'];
                $val['apartment_image'] = $apartment['cover_image'];
            }
            //发布方
            if($val['releasetype'] == 1){
                $member = pdo_get('wlmerchant_member',array('id' => $val['user_id']),array('nickname','encodename','mobile','avatar'));
                $val['releasename'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
                $val['releaselogo'] = $member['avatar'];
                $val['releasemobile'] = $member['mobile'];
            }else{
                $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $val['shop_id']),array('storename','mobile','logo'));
                $val['releasename'] = $merchant['storename'];
                $val['releaselogo'] = $merchant['logo'];
                $val['releasemobile'] = $merchant['mobile'];
            }


        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);



        include wl_template('renting/index');
    }


    public function rentingDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."renting",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function rentingEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."renting",['id'=>$id]);
        $info['images'] = unserialize($info['images']);
        if (!$info['status']){
            $info['status'] = 1;
        }
        $info['room'] ?: $info['room'] = 1;
        $info['office'] ?: $info['office'] = 1;
        $info['wei'] ?: $info['wei'] = 1;
        $info['kitchen'] ?: $info['kitchen'] = 1;

        //富文本处理
        if(is_base64($info['describe'])) $info['describe']   = htmlspecialchars_decode(base64_decode($info['describe']));

        $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$info['shop_id']]);

        $orientation_arr = $this->orientation_arr;
        $house_type_arr = pdo_fetchall("SELECT id,title FROM " . tablename(PDO_NAME . "house_label") . " WHERE type =  3 ORDER BY weigh DESC,id DESC ");

        $AreaTab = tablename(PDO_NAME . "house_apartment");
        $orderBy = " ORDER BY id ASC ";
        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab .  $orderBy);

//        $AreaTab = tablename(PDO_NAME . "house_area");
//        $orderBy = " ORDER BY id ASC ";
//        $allgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid = 0 ".  $orderBy);
//
//        $secondgroup = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid =  {$info['area_one_id']}".  $orderBy);
//        array_unshift($secondgroup,'');


        $labelTab = tablename(PDO_NAME . "house_label");
        $decoration = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  4 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);
        $facilities = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  9 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);
        $renting = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type =  10 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}".  $orderBy);


        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";
        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['current_city']}'");
        $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['current_area']}'");
        $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);

        if($info['shop_id'] > 0){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $info['shop_id']),array('storename','logo'));
            $info['storename'] = $merchant['storename'];
            $info['storelogo'] = tomedia($merchant['logo']);
        }

        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['images']      = serialize($service['images']);
            $service['facilities_ids'] = implode(',',$service['facilities_ids']);
            $service['renting_ids'] = implode(',',$service['renting_ids']);
            $service['apartment_name'] = pdo_getcolumn(PDO_NAME.'house_apartment',array('id'=>$service['apartment_id']),'name');
            if ($service['type'] == 1){
                $service['room_number'] = null;
            }
            if (is_store()){
                $service['shop_id'] = $_W['storeid'];
                $service['releasetype'] = 2;
            }

            if(empty($service['title'])){
                wl_message('请输入标题');
            }
            if(empty($service['cover_image'])){
                wl_message('请选择封面图片');
            }
             if(empty($service['architecture_size'])){
                wl_message('建筑面积(㎡)不能为空');
            }

            $service['describe'] = base64_encode(htmlspecialchars_decode($service['describe']));
            if($id > 0){
                $res = pdo_update(PDO_NAME."renting",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."renting",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/rentingList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('renting/edit');
    }



    public function reportList() {
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
        if($_GPC['name']) {
            if ($_GPC['keywordtype'] == 1){
                $where .= " AND b.nickname LIKE '%{$_GPC['name']}%' ";
            }elseif ($_GPC['keywordtype'] == 2){
                $where .= " AND a.title LIKE '%{$_GPC['name']}%' ";
            }
        }
        if($_GPC['status']) $where .= " AND a.status = {$_GPC['status']}";
        if($_GPC['house_type'] > 0){
            if($_GPC['house_type'] == 6){
                $where .= " AND a.house_type = 0";
            }else{
                $where .= " AND a.house_type = {$_GPC['house_type']}";
            }
        }

        //sql语句生成
        $field = "a.id,a.aid,a.title,a.user_id,a.house_type,a.house_id,a.describe,a.createtime,a.status,a.result,a.images,a.videos,
        b.nickname,b.avatar,b.mobile,b.encodename,
        c.title as new_house_name,c.cover_image as new_house_image,
        d.title as renting_name,d.cover_image as renting_image,
        e.title as old_house_name,e.cover_image as old_house_image,
        f.name as aqa_name,e.cover_image as aqa_image";
        $order = " ORDER BY a.id DESC ";
        $limit = " LIMIT {$pageStart},{$psize} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_report")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.user_id = b.id  LEFT JOIN ".tablename(PDO_NAME."new_house")
            ." as c ON a.house_id = c.id LEFT JOIN ".tablename(PDO_NAME."renting")
            ." as d ON a.house_id = d.id LEFT JOIN ".tablename(PDO_NAME."old_house")
            ." as e ON a.house_id = e.id LEFT JOIN ".tablename(PDO_NAME."house_apartment")
            ." as f ON a.house_id = f.id ";
        //列表获取
        $list = pdo_fetchall( $sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            $val['status_text'] = ($val['status'] == 1 ? '待处理' : ($val['status'] == 2 ? '处理中' : '已处理'));
            switch ($val['house_type']){
                case 1:
                    $val['house_title'] = $val['new_house_name'];
                    $val['house_image'] = $val['new_house_image'];
                    $val['houseurl'] = web_url('house/House/newHouseEdit',['id' => $val['house_id']]);
                    break;
                case 2:
                    $val['house_title'] = $val['old_house_name'];
                    $val['house_image'] = $val['old_house_image'];
                    $val['houseurl'] = web_url('house/House/oldHouseEdit',['id' => $val['house_id']]);
                    break;
                case 3:
                    $val['house_title'] = $val['renting_name'];
                    $val['house_image'] = $val['renting_image'];
                    $val['houseurl'] = web_url('house/House/rentingEdit',['id' => $val['house_id']]);
                    break;
                case 4:
                    $val['house_title'] = $val['aqa_name'];
                    $val['house_image'] = $val['aqa_image'];
                    $val['houseurl'] = web_url('house/House/apartmentEdit',['id' => $val['house_id']]);
                    break;
                default:
                    $val['house_title'] = '';
            }
            $val['nickname'] = !empty($val['encodename']) ? base64_decode($val['encodename']) : $val['nickname'];
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $pindex, $psize);



        include wl_template('report/index');
    }


    public function reportDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."house_report",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    public function reportShow() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."house_report",['id'=>$id]);
        $info['images'] = unserialize($info['images']);
        $info['videos'] = tomedia($info['videos']);

        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['images'] = serialize($service['images']);

            if(empty($service['result'])){
                wl_message('请输入处理结果');
            }

            if($id > 0){
                $service['status'] = 3;
                $res = pdo_update(PDO_NAME."house_report",$service,array('id' => $id));

            }

            if($res){
                wl_message('信息保存成功',web_url('house/House/reportList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }

        include wl_template('report/show');
    }


    public function reportEditStatus() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $res = pdo_update(PDO_NAME."house_report",['status'=>2],array('id' => $id));


        show_json(1, "操作成功");
//        http_redirect(web_url('house/House/rentingList'));
//        wl_message('信息保存成功',web_url('house/House/rentingList'), 'success');
    }


    /**
     * 导入标签
     */
    public function labelImport(){
        global $_W,$_GPC;

        $arr = [
            [
                'type'=>10,
                'title_arr'=>[
                    '免中介费','精装套房','随时看房','独立卫生间','新出租'
                ]
            ],
            [
                'type'=>11,
                'title_arr'=>[
                    '王牌顾问','精英顾问','态度良好','配车接送'
                ]
            ],
            [
                'type'=>12,
                'title_arr'=>[
                    '满两年','满五年','交通便捷','绿化率高','学区房'
                ]
            ],
            [
                'type'=>13,
                'title_arr'=>[
                    '车位充足','低密度','现房','花园洋房','学区房','品牌房企'
                ]
            ],
            [
                'type'=>5,
                'title_arr'=>[
                    '必看好房','满五年','满两年','近地铁','VR房源','7日新以上','随时看房','拎包入住'
                ]
            ],
            [
                'type'=>4,
                'title_arr'=>[
                    '精装修','普通装修','毛坯房','清水房'
                ]
            ],
            [//物业类型 /用途
                'type'=>3,
                'title_arr'=>[
                    '住宅','写字楼','别墅区','四合院','商业区'
                ]
            ],
            [
                'type'=>9,
                'title_arr'=>[
                    '洗衣机','空调','衣柜','电视','冰箱','热水器','床','暖气','宽带','天然气'
                ]//,'地铁','学校','医院'
            ],
        ];

        foreach ($arr as $item){
            $type = $item['type'];
            foreach ($item['title_arr'] as $k=>$v){
                $add_arr = [
                    'title' => $v,
                    'type' => $type,
                    'uniacid' => $_W['uniacid'],
                    'aid' => $_W['aid'],
                ];
                if ($type == 9){
                    $add_arr['image'] = 'addons/weliam_smartcity/h5/resource/wxapp/house/'.($k+1).'.png';
                }

                $res = pdo_insert(PDO_NAME."house_label",$add_arr);
            }

        }
        show_json(1, "操作成功");
    }


    /**
     * 清空标签
     */
    public function labelEmpty(){
        global $_W,$_GPC;

        $res = pdo_delete(PDO_NAME.'house_label', ['uniacid' => $_W['uniacid'], 'aid' => $_W['aid']]);

        show_json(1, "操作成功");
    }

    /**
     * Comment: 搜索商户
     * Author: wlf
     * Date: 2022/08/04 15:17
     */
    function selectMerchant() {
        global $_W, $_GPC;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $where['enabled'] = 1;
        $where['housestatus'] = 1;
        if ($_GPC['keyword'])
            $where['@storename@'] = $_GPC['keyword'];
        $merchants = Rush::getNumMerchant('id,storename,logo', $where, 'ID DESC', 0, 0, 0);
        $merchants = $merchants[0];

        include  wl_template('goodshouse/selectMerchant');
    }

    /**
     * Comment: 修改状态
     * Author: wlf
     * Date: 2022/08/06 18:08
     */
    function statusChange(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        $type = $_GPC['type'];
        if($type == 1){
            $res = pdo_update('wlmerchant_new_house',array('status' => $status),array('id' => $id));
        }else if($type == 2){
            $res = pdo_update('wlmerchant_old_house',array('status' => $status),array('id' => $id));
        }else{
            $res = pdo_update('wlmerchant_renting',array('status' => $status),array('id' => $id));
        }
        if($res > 0){
            show_json(1, "操作成功");
        }else{
            show_json(0, "操作失败,请重试");
        }
    }

    /**
     * Comment: 驳回申请
     * Author: wlf
     * Date: 2022/08/06 18:14
     */
    function applyRefuse(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];

        if($_W['ispost']){
            $reason = trim($_GPC['reason']);
            if($type == 1){
                $res = pdo_update('wlmerchant_new_house',array('reason' => $reason,'status' => 5),array('id' => $id));
            }else if($type == 2){
                $res = pdo_update('wlmerchant_old_house',array('reason' => $reason,'status' => 5),array('id' => $id));
            }else{
                $res = pdo_update('wlmerchant_renting',array('reason' => $reason,'status' => 5),array('id' => $id));
            }
            if($res){
                show_json(1);
            }else{
                show_json(0,'驳回失败，请刷新重试');
            }
        }
        include wl_template('setting/applyRefuse');
    }


}
