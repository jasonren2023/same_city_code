<?php
defined('IN_IA') or exit('Access Denied');

class HouseModuleUniapp extends Uniapp {

    public function __construct () {
        parent::__construct();
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('house');
        if(empty($set['status'])){
            $this->renderError('房产已关闭');
        }

    }


    /**
     * 首页
     */
    public function homePage(){
        global $_GPC, $_W;
        //顾问
        //sql语句生成
        $where = " WHERE a.uniacid = {$_W['uniacid']}  AND a.aid = {$_W['aid']} AND a.status = 1 ";

        $field = "a.id,a.weigh,a.phone,a.status,a.corporate_name,
        b.nickname,b.avatar";
        $order = " ORDER BY a.id,a.weigh DESC ";
        $limit = " LIMIT 3 ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_adviser")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.user_id = b.id ";
        //列表获取
        $advisers = pdo_fetchall( $sql.$where.$order.$limit);

        $type = $_GPC['type'] ?? 1;


        $where = [
            'uniacid' => $_W['uniacid'],
            'aid' => $_W['aid'],
            'title' => $_GPC['title']
        ];
        $house = $this->getHouse($type,$where);

        $labelTab = tablename(PDO_NAME . "house_label");
        $label_obj = pdo_fetchall("SELECT id,title FROM " . $labelTab . " WHERE type in  (4,12)");

        $house_arr = [];
        foreach ($house as $item){
            $house_item['title'] = $item['title'];
            $house_item['cover_image'] = $item['cover_image'];
            $house_item['apartment_name'] = $item['apartment_name'];
            $house_item['architecture_size'] = $item['architecture_size'] ?? null;//建筑面积
            $house_item['layout'] = ($item['room'] ? $item['room'].'室'.$item['office'].'厅' : '');//建筑规格
            $house_item['price'] = $item['price'] ?? null;//售价(万)
            $house_item['unit_price'] = ($item['architecture_size'] ? number_format($item['price']/$item['architecture_size'],2, ".", "") : $item['average_price']);//单位价格
            $label_arr = [];
            $label_ids = explode(',',$item['old_ids']);
            if (!empty($label_ids)){
                if (!empty($label_obj)){
                    foreach ($label_obj as $value){
                        if (in_array($value['id'],$label_ids)){
                            $label_arr[] =$value['title'];
                        }
                    }
                }
            }
            $house_item['label'] = $label_arr;
            $house_arr[] = $house_item;
            $house_item = [];
        }

        $adv = pdo_getall(PDO_NAME."adv",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'enabled'=>1,'type'=>20]);
        foreach ($adv as &$item){
            $item['thumb'] = tomedia($item['thumb']);
        }

        $data = [
            "advisers"  => $advisers,
            "house"  => $house_arr,
            "adv"  => $adv,
        ];
        $this->renderSuccess('首页',$data);
    }


    /**
     * 获取房产列表
     * @param $type
     * @param array $whereArr
     * @return array
     */
    public function getHouse($type,$whereArr=[]){
        //房产
        if ($type == 1){
            //sql语句生成
            $where = " WHERE a.uniacid = {$whereArr['uniacid']} AND a.aid = {$whereArr['aid']} AND a.status = 1 ";
            if($whereArr['title']) $where .= " AND a.title like '%{$whereArr['title']}%'";
            if ($whereArr['adviser_id']){
                $where .= " AND c.adviser_id = {$whereArr['adviser_id']} AND c.type = {$type}";
            }

            $field = "a.id,a.title,a.cover_image,a.status,a.weigh,a.start_time,a.delivery_time,a.average_price,a.developers,a.investor,a.decoration_id,a.architecture_size,a.house_type,a.address,
        b.name as apartment_name";
            $order = " ORDER BY a.id DESC ";
            $limit = " LIMIT 4 ";
            $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."new_house")
                ." as a LEFT JOIN ".tablename(PDO_NAME."house_apartment")
                ." as b ON a.apartment_id = b.id";
            if ($whereArr['adviser_id']){
                $sql .= " LEFT JOIN ".tablename(PDO_NAME."house_adviser_house")." as c ON a.id = c.house_id";
            }
            //列表获取
            $house = pdo_fetchall( $sql.$where.$order.$limit);
        }elseif ($type == 2){
            //sql语句生成
            $where = " WHERE a.uniacid = {$whereArr['uniacid']} AND a.aid = {$whereArr['aid']} AND a.status in (2,3) ";
            if($whereArr['title']) $where .= " AND a.title = {$whereArr['title']}";
            if ($whereArr['adviser_id']){
                $where .= " AND c.adviser_id = {$whereArr['adviser_id']} AND c.type = {$type}";
            }

            $field = "a.id,a.aid,a.apartment_name,a.staff_id,a.title,a.cover_image,a.weigh,a.orientation,a.price,a.address,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.status,a.architecture_size,a.old_ids,
        b.storename";
            $order = " ORDER BY a.id DESC ";
            $limit = " LIMIT 4 ";
            $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."old_house")
                ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
                ." as b ON a.shop_id = b.id ";
            if ($whereArr['adviser_id']){
                $sql .= " LEFT JOIN ".tablename(PDO_NAME."house_adviser_house")." as c ON a.id = c.house_id";
            }
            //列表获取
            $house = pdo_fetchall( $sql.$where.$order.$limit);
        }elseif ($type == 3){
            //sql语句生成
            $where = " WHERE a.uniacid = {$whereArr['uniacid']} AND a.aid = {$whereArr['aid']} AND a.status in (2,3) ";
            if($whereArr['title']) $where .= " AND a.title = {$whereArr['title']}";
            if ($whereArr['adviser_id']){
                $where .= " AND c.adviser_id = {$whereArr['adviser_id']} AND c.type = {$type}";
            }

            $field = "a.id,a.aid,a.apartment_name,a.name,a.title,a.cover_image,a.weigh,a.orientation,a.house_type,a.type,a.room_number,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.rent,a.deposit,a.pay,a.status,a.architecture_size,a.decoration_id,
        b.storename";
            $order = " ORDER BY a.id DESC ";
            $limit = " LIMIT 4 ";
            $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."renting")
                ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
                ." as b ON a.shop_id = b.id ";
            if ($whereArr['adviser_id']){
                $sql .= " LEFT JOIN ".tablename(PDO_NAME."house_adviser_house")." as c ON a.id = c.house_id";
            }
            //列表获取
            $house = pdo_fetchall( $sql.$where.$order.$limit);
        }

        foreach ($house as &$item) {
            $item['cover_image'] = tomedia($item['cover_image']);
            if ($type == 1){
                $item['label_list'] = [];
                $labelId = [];
                if($item['decoration_ids']){
                    $labelId = array_merge(explode(',',$item['decoration_ids']),$labelId);
                }
                if($item['new_ids']){
                    $labelId = array_merge(explode(',',$item['new_ids']),$labelId);
                }
                $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                $item['label_list'] = array_column($labelList,'title');
            }elseif ($type == 2){
                $item['label_list'] = [];
                if($item['old_ids']){
                    $labelId = explode(',',$item['old_ids']);
                    $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                    $item['label_list'] = array_column($labelList,'title');
                }
            }elseif ($type == 3){
                //标签
                $item['label_list'] = [];
                $labelId = [];
                if($item['decoration_id']){
                    $labelId[] = $item['decoration_id'];
                }
                if($item['renting_ids']){
                    $labelId = array_merge(explode(',',$item['renting_ids']),$labelId);
                }
                if($item['facilities_ids']){
                    $labelId = array_merge(explode(',',$item['facilities_ids']),$labelId);
                }
                $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                $item['label_list'] = array_column($labelList,'title');
            }
        }
        return $house;
    }


    /**
     * 顾问列表
     */
    public function adviserList(){
        global $_GPC, $_W;
        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        //顾问

        //成交量搜索
        $where = " WHERE a.uniacid = {$_W['uniacid']}  AND a.aid = {$_W['aid']} AND a.status = 1 ";
        if($_GPC['name']) $where .= " AND b.nickname like '%{$_GPC['name']}%'";
        if($_GPC['current_province']) $where .= " AND a.current_province = {$_GPC['current_province']}";
        if($_GPC['current_city']) $where .= " AND a.current_city = {$_GPC['current_city']}";
        if($_GPC['current_area']) $where .= " AND a.current_area = {$_GPC['current_area']}";
        //我的收藏
        if ($_GPC['is_collect']){
            $where .= " AND c.mid = {$_W['mid']} AND c.type = 4";
        }
        //我的历史
        if ($_GPC['is_history']){
            $where .= " AND d.mid = {$_W['mid']} AND d.type = 4";
        }
        //sql语句生成
        $field = "a.id,a.weigh,a.phone,a.status,a.corporate_name,a.current_province,a.current_city,a.current_area,a.adviser_ids,a.weigh,
        b.nickname,b.avatar";

//        //排序： 1.默认，2最新发布，3总价从低到高，4总价从高到低，5单价从低到高，6面积从大到小
//        switch ($_GPC['order_type']){
//            case 2:
//                $order = " ORDER BY a.id DESC ";
//                break;
//            case 3:
//                $order = " ORDER BY a.average_price ASC ";
//                break;
//            case 4:
//                $order = " ORDER BY a.average_price DESC ";
//                break;
//            case 5:
//                $order = " ORDER BY a.average_price ASC ";
//                break;
////            case 6:
////                $order = " ORDER BY a.architecture_size DESC ";
////                break;
//            default:
//                $order = " ORDER BY a.id,a.weigh DESC ";
//                break;
//        }

        $group = '';
        $order = " ORDER BY a.id,a.weigh DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_adviser")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.user_id = b.id ";
        if ($_GPC['is_collect']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_collection").' as c ON a.id = c.cid';
        }
        if ($_GPC['is_history']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_history").' as d ON a.id = d.cid';
            $group = ' GROUP BY a.id';
        }
        //列表获取
        $advisers = pdo_fetchall( $sql.$where.$group.$order.$limit);
        foreach ($advisers as &$item) {
            //标签
            $item['label_list'] = [];
            if($item['adviser_ids']){
                $labelId = explode(',',$item['adviser_ids']);
                $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                $item['label_list'] = array_column($labelList,'title');
            }

            $AreaTab = tablename(PDO_NAME . "area");
            $item['province'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 1 AND id = '{$item['current_province']}'");
            $item['city'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 2 AND id = '{$item['current_city']}'");
            $item['district'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 3 AND id = '{$item['current_area']}'");

        }

        //获取总页数
        if ($_GPC['is_history']){
            $total = count($advisers);
        }else{
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where.$group);
        }

        $data = [
            "list"  => $advisers,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('顾问列表',$data);
    }


    /**
     * 顾问详情
     */
    public function adviserInfo(){
        global $_GPC, $_W;

        //顾问
        $adviser_id = $_GPC['adviser_id'];
        $adviser = pdo_get(PDO_NAME."house_adviser",['id'=>$adviser_id]);
        //标签
        $adviser['label_list'] = [];
        if($adviser['adviser_ids']){
            $labelId = explode(',',$adviser['adviser_ids']);
            $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
            $adviser['label_list'] = array_column($labelList,'title');
        }
        //区域
        $area_one = pdo_get(PDO_NAME."house_area",['id'=>$adviser['area_one_id']]);
        $adviser['area_one'] = $area_one['name'] ?? '';
        $area_two = pdo_get(PDO_NAME."house_area",['id'=>$adviser['area_two_id']]);
        $adviser['area_two'] = $area_two['name'] ?? '';
        //用户信息
        $member = pdo_get(PDO_NAME."member",['id'=>$adviser['user_id']]);
        $adviser['nickname'] = $member['nickname'] ?? '';
        $adviser['avatar'] = $member['avatar'] ?? '';

//        $type = $_GPC['type'] ?? 1;
        $where = [
            'uniacid' => $_W['uniacid'],
            'aid' => $_W['aid'],
            'adviser_id' => $adviser_id,
        ];
        //房产

        $adviser['new_house'] = $this->getHouse(1,$where);
        $adviser['old_house'] = $this->getHouse(2,$where);
        $adviser['renting'] = $this->getHouse(3,$where);
        $adviser['describe'] = htmlspecialchars_decode($adviser['describe']);;

        $AreaTab = tablename(PDO_NAME . "area");
        $adviser['province'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 1 AND id = '{$adviser['current_province']}'") ?? '';
        $adviser['city'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 2 AND id = '{$adviser['current_city']}'") ?? '';
        $adviser['district'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 3 AND id = '{$adviser['current_area']}'") ?? '';
        $adviser['qq_qrcode_image'] = tomedia($adviser['qq_qrcode_image']);
        $adviser['wechat_qrcode_image'] = tomedia($adviser['wechat_qrcode_image']);

        $is_collection = pdo_getcolumn(PDO_NAME.'house_collection',array('cid'=>$adviser_id,'type'=>4,'mid'=>$_W['mid']),'id') ? 1 : 0;

        //添加浏览历史
        $history = [
            'cid' => $adviser_id,
            'type' => 4,
            'mid' => $_W['mid'],
        ];
        House::addHistory($history);


        $data = [
            "adviser"  => $adviser,
//            "house"  => $house,
            "is_collection"  => $is_collection,
        ];
        $this->renderSuccess('顾问详情',$data);
    }


    /**
     * 新房列表
     */
    public function newHouseList(){
        global $_GPC, $_W;

        $where = " WHERE a.uniacid = {$_W['uniacid']}  AND a.aid = {$_W['aid']} ";
        //标题搜索
        if($_GPC['title']) $where .= " AND a.title like '%{$_GPC['title']}%'";
        //区域搜索
        if($_GPC['current_province']) $where .= " AND a.current_province = {$_GPC['current_province']}";
        if($_GPC['current_city']) $where .= " AND a.current_city = {$_GPC['current_city']}";
        if($_GPC['current_area']) $where .= " AND a.current_area = {$_GPC['current_area']}";
        //价格搜索
        if($_GPC['min_price']) $where .= " AND a.min_price*a.architecture_size  >= {$_GPC['min_price']}";
        if($_GPC['max_price']) $where .= " AND a.max_price*a.architecture_size <= {$_GPC['max_price']}";
        //房型搜索
        if($_GPC['house_type']) $where .= " AND a.house_type = {$_GPC['house_type']}";
        //建筑面积
        switch ($_GPC['architecture_size_type']){
            case 1:
                $where .= " AND a.architecture_size = 20";
                break;
            case 2:
                $where .= " AND a.architecture_size between 50 and 70";
                break;
            case 3:
                $where .= " AND a.architecture_size between 70 and 90";
                break;
            case 4:
                $where .= " AND a.architecture_size between 90 and 110";
                break;
            case 5:
                $where .= " AND a.architecture_size between 110 and 130";
                break;
            case 6:
                $where .= " AND a.architecture_size between 130 and 150";
                break;
            case 7:
                $where .= " AND a.architecture_size between 150 and 200";
                break;
            case 8:
                $where .= " AND a.architecture_size > 200";
                break;
        }
        //朝向
        //房源特色
        //楼龄
        //楼层
        //装修
        if($_GPC['decoration_ids']) {
            $where .= " AND a.decoration_id = {$_GPC['decoration_ids']}";
        }
        //新房标签
        if($_GPC['new_ids']) {
            $new_ids = implode(',',$_GPC['new_ids']);
            $where .= " AND a.new_ids in ({$new_ids})";
        }
        //电梯
        if($_GPC['elevator']) $where .= " AND a.elevator = {$_GPC['elevator']}";
        //用途
        //权属
        //热门
        if($_GPC['hot_status']) $where .= " AND a.hot_status = {$_GPC['hot_status']}";
        //我的收藏
        if ($_GPC['is_collect']){
            $where .= " AND c.mid = {$_W['mid']} AND c.type = 1";
        }
        //我的历史
        if ($_GPC['is_history']){
            $where .= " AND d.mid = {$_W['mid']} AND d.type = 1";
        }
        //房型
        if ($_GPC['model_room']){
            if ($_GPC['model_room'] == 5){
                $where .= " AND e.room >= {$_GPC['model_room']} AND e.status = 1";
            }else{
                $where .= " AND e.room = {$_GPC['model_room']} AND e.status = 1";
            }
        }
        //我发布
        if ($_GPC['is_mine']){
            $shop_id = $_GPC['storeid'];
            $where .= " AND a.shop_id = {$shop_id} ";
        }else{
            $where .= "  AND a.status = 1 ";
        }

        //小区
        if($_GPC['apartment_id'] > 0){
            $where .= "  AND a.apartment_id = {$_GPC['apartment_id']}";
        }



        $field = "a.id,a.title,a.cover_image,a.status,a.orderid,a.weigh,a.start_time,a.delivery_time,a.average_price,a.developers,a.investor,a.decoration_id,a.house_type,a.architecture_size,a.address,a.new_ids,
        b.name as apartment_name";
        if ($_GPC['is_history']){
            $field .= ',d.create_time';
        }

        //排序： 1.默认，2最新发布，3总价从低到高，4总价从高到低，5单价从低到高，6面积从大到小
        switch ($_GPC['order_type']){
            case 2:
                $order = " ORDER BY a.id DESC ";
                break;
            case 3:
                $order = " ORDER BY a.average_price ASC ";
                break;
            case 4:
                $order = " ORDER BY a.average_price DESC ";
                break;
            case 5:
                $order = " ORDER BY a.average_price ASC ";
                break;
//            case 6:
//                $order = " ORDER BY a.architecture_size DESC ";
//                break;
            default:
                $order = " ORDER BY a.weigh DESC ";
                break;
        }
        if ($_GPC['is_history']){
            $order = " ORDER BY d.create_time DESC ";
        }


        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $group = '';

        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."new_house")
            ." as a LEFT JOIN ".tablename(PDO_NAME."house_apartment")
            ." as b ON a.apartment_id = b.id";
        if ($_GPC['is_collect']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_collection").' as c ON a.id = c.cid';
        }
        if ($_GPC['is_history']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_history").' as d ON a.id = d.cid';
            $group = ' GROUP BY a.id';
        }
        if ($_GPC['model_room']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_model").' as e ON a.id = e.house_id';
            $group = ' GROUP BY a.id';
        }
        //列表获取
        $house = pdo_fetchall( $sql.$where.$group.$order.$limit);

        foreach ($house as &$item) {
            //标签
            $item['label_list'] = [];
            if($item['decoration_ids'] || $item['new_ids']){
                $labelId = explode(',',$item['new_ids']);
                $labelId = array_merge(explode(',',$item['decoration_ids']),$labelId);
                $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                $item['label_list'] = array_column($labelList,'title');
            }
            $item['cover_image'] = tomedia($item['cover_image']);
            if ($_GPC['is_history']) {
                $item['create_time'] = date('Y-m-d',$item['create_time']);
            }
        }

        //获取总页数
        if ($_GPC['is_history']){
            $total = count($house);
        }else{
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where.$group);
        }

        $data = [
            "list"  => $house,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('房产列表',$data);
    }


    /**
     * 旧房列表
     */
    public function oldHouseList(){
        global $_GPC, $_W;

        $where = " WHERE a.uniacid = {$_W['uniacid']}";
        //标题搜索
        if($_GPC['title']) $where .= " AND a.title like '%{$_GPC['title']}%'";
        //区域搜索
        if($_GPC['current_province']) $where .= " AND a.current_province = {$_GPC['current_province']}";
        if($_GPC['current_city']) $where .= " AND a.current_city = {$_GPC['current_city']}";
        if($_GPC['current_area']) $where .= " AND a.current_area = {$_GPC['current_area']}";
        //价格搜索
        if($_GPC['min_price']) $where .= " AND a.price  >= {$_GPC['min_price']}";
        if($_GPC['max_price']) $where .= " AND a.price <= {$_GPC['max_price']}";
        //房型搜索
        //建筑面积
        switch ($_GPC['architecture_size_type']){
            case 1:
                $where .= " AND a.architecture_size = 20";
                break;
            case 2:
                $where .= " AND a.architecture_size between 50 and 70";
                break;
            case 3:
                $where .= " AND a.architecture_size between 70 and 90";
                break;
            case 4:
                $where .= " AND a.architecture_size between 90 and 110";
                break;
            case 5:
                $where .= " AND a.architecture_size between 110 and 130";
                break;
            case 6:
                $where .= " AND a.architecture_size between 130 and 150";
                break;
            case 7:
                $where .= " AND a.architecture_size between 150 and 200";
                break;
            case 8:
                $where .= " AND a.architecture_size > 200";
                break;
        }
        //朝向
        if($_GPC['orientation']) $where .= " AND a.orientation like {$_GPC['orientation']}";//1=朝南,2=东南,3=朝东,4=西南,5=朝北,6=朝西,7=东北,8=西北
        //房源特色

        //楼龄
        //楼层
        //装修
        if($_GPC['decoration_id']) {
            $where .= " AND a.decoration_id = ({$_GPC['decoration_id']})";
        }
        //电梯
        if($_GPC['elevator']) $where .= " AND a.elevator = {$_GPC['elevator']}";
        //用途
        //权属
        //我的收藏
        if ($_GPC['is_collect']){
            $where .= " AND c.mid = {$_W['mid']} AND c.type = 2";
        }
        //我的历史
        if ($_GPC['is_history']){
            $where .= " AND d.mid = {$_W['mid']} AND d.type = 2";
        }
        //我发布
        if ($_GPC['is_mine']){
            if(empty($_GPC['newtype'])){
                $where .= " AND a.releasetype = 1 AND a.user_id = {$_W['mid']} ";
            }else{
                $where .= " AND a.releasetype = 2 AND a.shop_id = {$_GPC['storeid']} ";
            }
        }else{
            $where .= "  AND a.status in (2,3) ";
        }

        //小区
        if($_GPC['apartment_id'] > 0){
            $where .= "  AND a.apartment_id = {$_GPC['apartment_id']}";
        }


        //sql语句生成
        $field = "a.id,a.aid,a.apartment_name,a.staff_id,a.orderid,a.releasetype,a.elevator,a.title,a.cover_image,a.weigh,a.decoration_id,a.orientation,a.price,a.address,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.status,a.architecture_size,a.old_ids,a.address,
        b.storename";
        if ($_GPC['is_history']){
            $field .= ',d.create_time';
        }

        //排序： 1.默认，2最新发布，3总价从低到高，4总价从高到低，5单价从低到高，6面积从大到小
        switch ($_GPC['order_type']){
            case 2:
                $order = " ORDER BY a.id DESC ";
                break;
            case 3:
                $order = " ORDER BY a.price ASC ";
                break;
            case 4:
                $order = " ORDER BY a.price DESC ";
                break;
            case 5:
                $order = " ORDER BY a.price/a.architecture_size ASC ";
                break;
            case 6:
                $order = " ORDER BY a.architecture_size DESC ";
                break;
            default:
                $order = " ORDER BY a.weigh DESC ";
                break;
        }
        if ($_GPC['is_history']){
            $order = " ORDER BY d.create_time DESC ";
        }


        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $group = '';

        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."old_house")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id ";
        if ($_GPC['is_collect']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_collection").' as c ON a.id = c.cid';
        }
        if ($_GPC['is_history']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_history").' as d ON a.id = d.cid';
            $group = ' GROUP BY a.id';
        }
        //列表获取
        $house = pdo_fetchall( $sql.$where.$group.$order.$limit);

        foreach ($house as &$item) {
            //标签
            $item['label_list'] = [];
            if($item['old_ids']){
                $labelId = explode(',',$item['old_ids']);
                $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
                $item['label_list'] = array_column($labelList,'title');
            }
            if($item['decoration_id'] > 0){
                $decoration = pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$item['decoration_id']),'title');
                $deca[] = $decoration;
                $item['label_list'] = array_merge($deca,$item['label_list']);
            }


            $item['cover_image'] = tomedia($item['cover_image']);
            if ($_GPC['is_history']) {
                $item['create_time'] = date('Y-m-d',$item['create_time']);
            }
        }

        //获取总页数
        if ($_GPC['is_history']){
            $total = count($house);
        }else{
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where.$group);
        }

        $data = [
            "list"  => $house,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('房产列表',$data);
    }

    /**
     * 租房列表
     */
    public function rentingList(){
        global $_GPC, $_W;

        $where = " WHERE a.uniacid = {$_W['uniacid']}  AND a.aid = {$_W['aid']}  ";
        //出租方式:1=整租,2=合租
        if($_GPC['type']) $where .= " AND a.type = {$_GPC['type']}";
        //类型:1=住宅,2=商铺,3=写字楼,4=公寓
        if($_GPC['house_type']) $where .= " AND a.house_type = {$_GPC['house_type']}";
        //室
        if($_GPC['room']) $where .= " AND a.room = {$_GPC['room']}";
        //标题搜索
        if($_GPC['title']) $where .= " AND a.title like '%{$_GPC['title']}%'";
        //区域搜索
        if($_GPC['current_province']) $where .= " AND a.current_province = {$_GPC['current_province']}";
        if($_GPC['current_city']) $where .= " AND a.current_city = {$_GPC['current_city']}";
        if($_GPC['current_area']) $where .= " AND a.current_area = {$_GPC['current_area']}";
        //价格搜索
        if($_GPC['min_price']) $where .= " AND a.rent  >= {$_GPC['min_price']}";
        if($_GPC['max_price']) $where .= " AND a.rent <= {$_GPC['max_price']}";
        //租金搜索
        switch ($_GPC['rent']){
            case 1:
                $where .= " AND a.rent <= 1000";
                break;
            case 2:
                $where .= " AND a.rent between 1000 and 1500";
                break;
            case 3:
                $where .= " AND a.rent between 1500 and 2000";
                break;
            case 4:
                $where .= " AND a.rent between 2000 and 2500";
                break;
            case 5:
                $where .= " AND a.rent between 2500 and 3000";
                break;
            case 6:
                $where .= " AND a.rent between 3000 and 5000";
                break;
            case 7:
                $where .= " AND a.rent >= 5000";
                break;
        }
        //房源亮点
        //朝向
        if($_GPC['orientation']) $where .= " AND a.orientation like {$_GPC['orientation']}";
        //租期
        //楼层
        //电梯
        if($_GPC['elevator']) $where .= " AND a.elevator = {$_GPC['elevator']}";
        //我的收藏
        if ($_GPC['is_collect']){
            $where .= " AND c.mid = {$_W['mid']} AND c.type = 3";
        }
        //我的历史
        if ($_GPC['is_history']){
            $where .= " AND d.mid = {$_W['mid']} AND d.type = 3";
        }
        //我发布
        if ($_GPC['is_mine']){
            if(empty($_GPC['newtype'])){
                $where .= " AND a.releasetype = 1 AND a.user_id = {$_W['mid']} ";
            }else{
                $where .= " AND a.releasetype = 2 AND a.shop_id = {$_GPC['storeid']} ";
            }
        }else{
            $where .= " AND a.status in (2,3) ";
        }

        //小区
        if($_GPC['apartment_id'] > 0){
            $where .= "  AND a.apartment_id = {$_GPC['apartment_id']}";
        }

        //sql语句生成
        $field = "a.id,a.aid,a.apartment_name,a.name,a.orderid,a.type,a.releasetype,a.title,a.cover_image,a.weigh,a.orientation,a.house_type,a.type,a.room_number,a.current_floor,a.total_floor,a.room,a.office,a.wei,a.kitchen,a.rent,a.deposit,a.pay,a.status,a.architecture_size,a.decoration_id,a.facilities_ids,a.renting_ids,a.address,
        b.storename";
        if ($_GPC['is_history']){
            $field .= ',d.create_time';
        }

        //排序： 1.默认，2最新发布，3总价从低到高，4总价从高到低，5单价从低到高，6面积从大到小
        switch ($_GPC['order_type']){
            case 2:
                $order = " ORDER BY a.id DESC ";
                break;
            case 3:
                $order = " ORDER BY a.rent ASC ";
                break;
            case 4:
                $order = " ORDER BY a.rent DESC ";
                break;
            case 5:
                $order = " ORDER BY a.rent/a.architecture_size ASC ";
                break;
            case 6:
                $order = " ORDER BY a.architecture_size DESC ";
                break;
            default:
                $order = " ORDER BY a.weigh DESC ";
                break;
        }
        if ($_GPC['is_history']){
            $order = " ORDER BY d.create_time DESC ";
        }


        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $group = '';

        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."renting")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.shop_id = b.id ";
        if ($_GPC['is_collect']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_collection").' as c ON a.id = c.cid';
        }
        if ($_GPC['is_history']){
            $sql .= ' LEFT JOIN '.tablename(PDO_NAME."house_history").' as d ON a.id = d.cid';
            $group = ' GROUP BY a.id';
        }
        //列表获取
        $house = pdo_fetchall( $sql.$where.$group.$order.$limit);

        foreach ($house as &$item) {
            //标签
            $item['label_list'] = [];
            $labelId = [];
            if($item['decoration_id']){
                $labelId[] = $item['decoration_id'];
            }
            if($item['renting_ids']){
                $labelId = array_merge(explode(',',$item['renting_ids']),$labelId);
            }

            $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['title']);
            $item['label_list'] = array_column($labelList,'title');
            $item['cover_image'] = tomedia($item['cover_image']);
            if ($_GPC['is_history']) {
                $item['create_time'] = date('Y-m-d',$item['create_time']);
            }
        }

        //获取总页数
        if ($_GPC['is_history']){
            $total = count($house);
        }else{
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where.$group);
        }

        $data = [
            "list"  => $house,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('房产列表',$data);
    }


    /**
     * 浏览历史
     */
    public function browse(){

    }


    /**
     * 标签
     */
    public function labelList(){
        global $_GPC, $_W;
        $data = [];
        $labelTab = tablename(PDO_NAME . "house_label");
        $orderBy = " ORDER BY weigh DESC ";
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if($_W['aid'] > 0) $where .= " AND aid = {$_W['aid']} ";
        if($_GPC['type']) $where1 = $where ." AND type = {$_GPC['type']} ";
        $label = pdo_fetchall("SELECT id,title,image FROM " . $labelTab . $where1.  $orderBy);
        foreach ($label as &$item) {
            $item['image'] = tomedia($item['image']);
        }
        $data['label'] = $label;
        //建筑类型
        $where2 = $where ." AND type =  3";
        $construction = pdo_fetchall("SELECT id,title,image FROM " . $labelTab . $where2 .  $orderBy);
        $data['construction'] = $construction;
        //装修类型
        $where3 = $where ." AND type =  4";
        $decoration = pdo_fetchall("SELECT id,title,image FROM " . $labelTab . $where3 .  $orderBy);
        $data['decoration'] = $decoration;

        $this->renderSuccess('标签列表',$data);
    }


    /**
     * 区域
     */
    public function areaList(){
        global $_GPC, $_W;

        $AreaTab = tablename(PDO_NAME . "house_area");
        $orderBy = " ORDER BY weigh DESC ";
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if($_W['aid'] > 0) $where .= " AND aid = {$_W['aid']} ";

//        if ($_GPC['area_one_id']){
//            $area = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid =  {$_GPC['area_one_id']}".  $orderBy);
//            //        array_unshift($secondgroup,'');
//        }else{
//            $area = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE pid = 0 ".  $orderBy);
//        }

        $area = pdo_fetchall("SELECT id,name FROM " . $AreaTab . $where . " and pid = 0 ".  $orderBy);

        $ret = [];
        foreach ($area as $k=>$item){
            if ($item['pid'] == 0){
                $ret[$k] = $item;
                $pid = $item['id'];
                $ret[$k]['cta'] = pdo_fetchall("SELECT id,name FROM " . $AreaTab . $where . " and pid =  {$pid}".  $orderBy);
            }
        }

        $this->renderSuccess('区域列表',$ret);
    }

    /**
     * 房产详情
     */
    public function houseInfo(){
        global $_GPC, $_W;
        $house_id = $_GPC['house_id'];
        $type = $_GPC['type'];
        if ($type == 1){//新房
            $table_name = "new_house";
            $lable_id_name = "new_ids";
        }elseif ($type == 2){//二手房
            $table_name = "old_house";
            $lable_id_name = "old_ids";
        }elseif ($type == 3){//租房
            $table_name = "renting";
            $lable_id_name = "renting_ids";
        }else{
            $this->renderError('请求错误，请重试');
        }

        //判断会员
        $set = Setting::agentsetting_read('house');
        if($set['vipsee'] > 0 && empty($_GPC['editflag'])){
            $halfcardflag = WeliamWeChat::VipVerification($_W['mid'] , true); //会员状态
            if(empty($halfcardflag)){
                $this->renderError('房源详细信息仅会员可查看,请开通会员',['tocard' => 1]);
            }
        }
        $house = pdo_get(PDO_NAME.$table_name,['id'=>$house_id]);

        //标签
        $house['label_list'] = [];
        if($house[$lable_id_name]){
            $labelId = explode(',',$house[$lable_id_name]);
            $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$labelId],['id','title']);
            $house['label_list'] = $labelList;
        }

        if($house['decoration_id'] > 0){
            $decoration = pdo_getall(PDO_NAME.'house_label',array('id'=>$house['decoration_id']),['id','title']);
            $house['label_list'] = array_merge($decoration,$house['label_list']);
            $house['decoration'] = $decoration[0]['title'];
        }

        if($house['house_type'] > 0){
            $house['house_type_text'] = pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$house['house_type']),'title');
        }
        if(empty($house['house_type_text'])){
            $house['house_type_text'] = '其他';
        }

        $house['cover_image'] = tomedia($house['cover_image']);
        $house['cover_video'] = tomedia($house['cover_video']);

        $house['share_logo'] = tomedia($house['share_logo']);
        $house['images'] = empty(unserialize($house['images'])) ? [] : unserialize($house['images']);

        if (!empty($house['images'])){
            foreach ($house['images'] as &$image){
                $image = tomedia($image);
            }
        }

        if ($type == 1){
            $house['house_model'] = pdo_getall(PDO_NAME."house_model",['house_id'=>$house_id],'','',['room ASC']);
            $data = [];
            foreach ($house['house_model'] as &$model_item){
                $model_item['cover_image'] = tomedia($model_item['cover_image']);
                switch ($model_item['room']){
                    case 1:
                        $data['一'][] = $model_item;
                        break;
                    case 2:
                        $data['二'][] = $model_item;
                        break;
                    case 3:
                        $data['三'][] = $model_item;
                        break;
                    case 4:
                        $data['四'][] = $model_item;
                        break;
                    case 5:
                        $data['五'][] = $model_item;
                        break;
                    case 6:
                        $data['六'][] = $model_item;
                        break;
                    case 7:
                        $data['七'][] = $model_item;
                        break;
                    case 8:
                        $data['八'][] = $model_item;
                        break;
                    default:
                        $data[$model_item['room']][] = $model_item;
                        break;
                }
            }
            $house['house_model'] = $data;
            $house['start_time'] = date('Y-m-d',$house['start_time']);
            //图片整合
            $house['example_images'] = beautifyImgInfo($house['example_images']);
            $house['matching_images'] = beautifyImgInfo($house['matching_images']);
            $house['outdoor_scene_images'] = beautifyImgInfo($house['outdoor_scene_images']);
            $house['plan_images'] = beautifyImgInfo($house['plan_images']);
            $house['sand_table_images'] = beautifyImgInfo($house['sand_table_images']);
            $house['images'] = array_merge($house['example_images'],$house['matching_images'],$house['outdoor_scene_images'],$house['plan_images'],$house['sand_table_images']);
            $releasetype = 2;
            $releaseid = $house['shop_id'];
        }else if($type == 2){
            $house['averageprice'] = ceil($house['price']*10000/$house['architecture_size']);
            $house['start_time'] = date('Y-m-d',$house['create_time']);
            $house['mobile'] = $house['phone'];
            unset($house['phone']);
            $releasetype = $house['releasetype'];
            if($releasetype == 1){
                $releaseid = $house['user_id'];
            }else{
                $releaseid = $house['shop_id'];
            }

        }else if($type == 3){
            $house['start_time'] = date('Y-m-d',$house['create_time']);
            $house['mobile'] = $house['phone'];
            unset($house['phone']);
            $releasetype = $house['releasetype'];
            if($releasetype == 1){
                $releaseid = $house['user_id'];
            }else{
                $releaseid = $house['shop_id'];
            }
        }



        $house['delivery_time'] = date('Y-m-d',$house['delivery_time']);

        $where = " WHERE b.house_id = {$house_id} AND type = ".$type."  AND a.status = 1 ";
        $field = "a.id,a.aid,a.weigh,a.phone,a.status,a.corporate_name,a.current_province,a.current_city,a.current_area,
        c.nickname,c.avatar";
        $order = " ORDER BY a.weigh DESC ";
        $limit = " LIMIT 0,2 ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_adviser")
            ." as a LEFT JOIN ".tablename(PDO_NAME."house_adviser_house")
            ." as b ON a.id = b.adviser_id LEFT JOIN ".tablename(PDO_NAME."member")
            ." as c ON a.user_id = c.id";
        //顾问列表信息
        $adviser = pdo_fetchall( $sql.$where.$order.$limit);
        foreach ($adviser as &$item){
//            //区域
//            $area_one = pdo_get(PDO_NAME."house_area",['id'=>$item['area_one_id']]);
//            $item['area_one'] = $area_one['name'] ?? '';
//            $area_two = pdo_get(PDO_NAME."house_area",['id'=>$item['area_two_id']]);
//            $item['area_two'] = $area_two['name'] ?? '';

            $AreaTab = tablename(PDO_NAME . "area");
            $item['province'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 1 AND id = '{$item['current_province']}'") ?? '';
            $item['city'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 2 AND id = '{$item['current_city']}'") ?? '';
            $item['district'] = pdo_fetchcolumn("SELECT name FROM " . $AreaTab . " WHERE level = 3 AND id = '{$item['current_area']}'") ?? '';
        }

        //是否收藏
        $is_collection = pdo_getcolumn(PDO_NAME.'house_collection',array('cid'=>$house_id,'type'=>$type,'mid'=>$_W['mid']),'id') ? 1 : 0;
        //富文本处理
        if(is_base64($house['describe'])) $house['describe']   = htmlspecialchars_decode(base64_decode($house['describe']));

        //小区信息
        $apartment =  pdo_get(PDO_NAME."house_apartment",['id'=>$house['apartment_id']],['id','name','green_rate','cover_image','volume_rate','property_fee']);
        if (isset($apartment['cover_image'])){
            $apartment['cover_image'] = tomedia($apartment['cover_image']);
        }

        //添加浏览历史
        $history = [
            'cid' => $house_id,
            'type' => $type,
            'mid' => $_W['mid'],
            'releaseid' => $releaseid,
            'releasetype' => $releasetype

        ];
        House::addHistory($history);

        $data = [
            "house" => $house,
            "adviser"  => $adviser,
            "is_collection"  => $is_collection,
            "apartment"  => $apartment,
        ];
        $this->renderSuccess('房产详情',$data);
    }


    /**
     * 获取房型
     */
    public function getHouseModel(){
        global $_GPC, $_W;

        $house_id = $_GPC['house_id'];
        $house_model = pdo_getall(PDO_NAME."house_model",['house_id'=>$house_id]);
        $data = [];
        foreach ($house_model as &$model_item){
            $model_item['cover_image'] = tomedia($model_item['cover_image']);
            $data[$model_item['room']][] = $model_item;
        }


        $this->renderSuccess('获取房型',$data);
    }


    /**
     * 提交反馈
     */
    public function submitFeedback(){
        global $_GPC, $_W;

//        $_GPC = $_GPC['__input'];
        $service = [];

        if(!emoty($service['images'])){
            $service['images'] = explode(',',$_GPC['images']);
            $service['images'] = serialize($service['images']);
        }

        if(empty($_GPC['title'])){
            wl_message('请输入标题');
        }
        if(empty($_GPC['describe'])){
            wl_message('请输入描述');
        }


        $service['uniacid'] = $_W['uniacid'];
        $service['aid'] = $_W['aid'];
        $service['createtime'] = time();
        $service['title'] = $_GPC['title'];
        $service['describe'] = $_GPC['describe'];
        $service['house_type'] = $_GPC['house_type'];
        $service['house_id'] = $_GPC['house_id'];
        $service['user_id'] = $_W['mid'];
        $res = pdo_insert(PDO_NAME."house_report",$service);

        if($res){
            $this->renderSuccess('提交反馈成功');
        }else{
            $this->renderError('提交反馈失败，请重试');
        }

    }


    /**
     * 反馈列表
     */
    public function feedbackList(){
        global $_GPC, $_W;

        $tab = tablename(PDO_NAME . "house_report");
        $orderBy = " ORDER BY a.id DESC ";
        $where = " WHERE a.uniacid = {$_W['uniacid']} and a.user_id = ".$_W['mid'];
        if($_W['aid'] > 0) $where .= " AND a.aid = {$_W['aid']} ";

        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $field = 'a.id,a.title,a.user_id,a.house_type,a.house_id,a.describe,a.createtime,a.status,a.result,a.images,a.videos,
        b.nickname,b.avatar';
        $sql = "SELECT ".$field." FROM " . $tab .' as a'
            ." LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.user_id = b.id";

        $report = pdo_fetchall($sql. $where.  $orderBy. $limit);
        foreach ($report as &$item) {
            $images = empty(unserialize($item['images'])) ? [] : unserialize($item['images']);
            if (!empty($images)){
                foreach ($images as &$value){
                    $value = tomedia($value);
                }
             }
            $item['images'] = $images;
            $item['videos'] = tomedia($item['videos']);
            //房源信息
            if($item['house_type'] == 1){
                $house = pdo_get('wlmerchant_new_house',array('id' => $item['house_id']),array('title','cover_image'));
            }else if($item['house_type'] == 2){
                $house = pdo_get('wlmerchant_old_house',array('id' => $item['house_id']),array('title','cover_image'));
            }else if($item['house_type'] == 3){
                $house = pdo_get('wlmerchant_renting',array('id' => $item['house_id']),array('title','cover_image'));
            }

            $item['house_title'] = $house['title'];
            $item['house_image'] = tomedia($house['cover_image']);
        }

        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);

        $data = [
            "list"  => $report,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('反馈列表',$data);
    }




    /**
     * 发布租房
     */
    public function releaseRenting(){
        global $_GPC, $_W;
        $id = $_GPC['id'] ?? 0;
        $set = Setting::agentsetting_read('house');
        $service = [
            'address' => $_GPC['address'],
            'apartment_name' => $_GPC['apartment_name'],
            'title' => $_GPC['title'],
            'apartment_id' => $_GPC['apartment_id'],
            'room' => $_GPC['room'],
            'office' => $_GPC['office'],
            'wei' => $_GPC['wei'],
            'kitchen' => $_GPC['kitchen'],
            'deposit' => $_GPC['deposit'],
            'pay' => $_GPC['pay'],
            'architecture_size' => $_GPC['architecture_size'],
            'rent' => $_GPC['rent'],
            'total_floor' => $_GPC['total_floor'],
            'current_floor' => $_GPC['current_floor'],
            'cover_image' => $_GPC['cover_image'],
            'name' => $_GPC['name'],
            'renting_ids' => $_GPC['renting_ids'],
            'facilities_ids' => $_GPC['facilities_ids'],
            'cover_video' => $_GPC['cover_video'],
            'lat' => $_GPC['lat'],
            'lng' => $_GPC['lng'],
            'current_province' => $_GPC['current_province'],
            'current_city' => $_GPC['current_city'],
            'current_area' => $_GPC['current_area'],
            'house_type' => $_GPC['house_type'],
            'decoration_id' => $_GPC['decoration_id'],
            'phone' => $_GPC['mobile'],
            'orientation' => $_GPC['orientation'],
            'elevator' => $_GPC['elevator'],
            'type' => $_GPC['type'],
            'room_number' => $_GPC['room_number'],
            'describe' => base64_encode(htmlspecialchars_decode($_GPC['describe']))
        ];

        $images = explode(',',$_GPC['images']);
        $service['images'] = serialize($images);

        if(empty($_GPC['title'])){
            $this->renderError('请输入标题');
        }
        if(empty($_GPC['cover_image'])){
            $this->renderError('请选择封面图片');
        }
        if(empty($_GPC['architecture_size'])){
            $this->renderError('请输入房屋面积');
        }

        if(empty($service['current_floor'])){
            $this->renderError('当前楼层不能为0');
        }
        if(empty($service['total_floor'])){
            $this->renderError('总楼层数不能为0');
        }
        if($service['current_floor'] > $service['total_floor']){
            $this->renderError('当前楼层不能大于粽楼层');
        }

        if($id > 0){
            $res = pdo_update(PDO_NAME."renting",$service,array('id' => $id));
        }else{
            $service['uniacid'] = $_W['uniacid'];
            $service['aid'] = $_W['aid'];
            $service['create_time'] = time();
            $service['releasetype'] = $_GPC['releasetype'];
            if($service['releasetype'] == 1){
                $service['user_id'] = $_W['mid'];
            }else{
                $service['shop_id'] = $_GPC['storeid'];
            }
            $refmoney = $this->calculatePrice(3,$service['releasetype']);
            if($refmoney > 0){
                $service['status'] = 6;
            }else{
                if($set['passstatus'] > 0){
                    $service['status'] = 1;
                }else{
                    $service['status'] = 4;
                }
            }
            $res = pdo_insert(PDO_NAME."renting",$service);
            $id = pdo_insertid();
        }
        if($res){
            if($service['status'] == 5){
                //发送消息
                $first   = '您好,[' . $service['name'] . ']发布了一条出租房源信息';
                $type    = '出租房源信息发布';
                $content = '房源标题:[' . $service['title'] . ']';
                $status  = '待审核';
                $remark  = '请尽快前往系统后台审核信息';
                News::noticeAgent('house' , $_W['aid'] , $first , $type , $content , $status , $remark , time());
            }
            $this->renderSuccess('发布租房成功',['id'=>$id]);
        }else{
            $this->renderError('发布租房失败，请重试');
        }

    }

    /**
     * 发布二手房
     */
    public function releaseOldHouse(){
        global $_GPC, $_W;

        $id = $_GPC['id'] ?? 0;
        $set = Setting::agentsetting_read('house');
        $service = [
            'address' => $_GPC['address'],
            'apartment_name' => $_GPC['apartment_name'],
            'title' => $_GPC['title'],
            'price' => $_GPC['price'],
            'apartment_id' => $_GPC['apartment_id'],
            'room' => $_GPC['room'],
            'office' => $_GPC['office'],
            'wei' => $_GPC['wei'],
            'kitchen' => $_GPC['kitchen'],
            'architecture_size' => $_GPC['architecture_size'],
            'total_floor' => $_GPC['total_floor'],
            'current_floor' => $_GPC['current_floor'],
            'cover_image' => $_GPC['cover_image'],
            'name' => $_GPC['name'],
            'old_ids' => $_GPC['old_ids'],
            'facilities_ids' => $_GPC['facilities_ids'],
            'cover_video' => $_GPC['cover_video'],
            'lat' => $_GPC['lat'],
            'lng' => $_GPC['lng'],
            'current_province' => $_GPC['current_province'],
            'current_city' => $_GPC['current_city'],
            'current_area' => $_GPC['current_area'],
            'house_type' => $_GPC['house_type'],
            'decoration_id' => $_GPC['decoration_id'],
            'phone' => $_GPC['mobile'],
            'orientation' => $_GPC['orientation'],
            'elevator' => $_GPC['elevator'],
            'describe' => base64_encode(htmlspecialchars_decode($_GPC['describe']))
        ];

        $service['images'] = explode(',',$_GPC['images']);
        $service['images'] = serialize($service['images']);


        if(empty($service['title'])){
            wl_message('请输入标题');
        }
        if(empty($service['cover_image'])){
            wl_message('请选择封面图片');
        }
        if(empty($_GPC['architecture_size'])){
            $this->renderError('请输入房屋面积');
        }
        if(empty($service['current_floor'])){
            $this->renderError('当前楼层不能为0');
        }
        if(empty($service['total_floor'])){
            $this->renderError('总楼层数不能为0');
        }

        if($service['current_floor'] > $service['total_floor']){
            $this->renderError('当前楼层不能大于粽楼层');
        }

        if($id > 0){
            $res = pdo_update(PDO_NAME."old_house",$service,array('id' => $id));
        }else{
            $service['uniacid'] = $_W['uniacid'];
            $service['aid'] = $_W['aid'];
            $service['create_time'] = time();
            $service['releasetype'] = $_GPC['releasetype'];
            if($service['releasetype'] == 1){
                $service['user_id'] = $_W['mid'];
            }else{
                $service['shop_id'] = $_GPC['storeid'];
            }
            $refmoney = $this->calculatePrice(2,$service['releasetype']);
            if($refmoney > 0){
                $service['status'] = 6;
            }else{
                if($set['passstatus'] > 0){
                    $service['status'] = 1;
                }else{
                    $service['status'] = 4;
                }
            }
            $res = pdo_insert(PDO_NAME."old_house",$service);
            $id = pdo_insertid();
        }
        if($res){
            if($service['status'] == 5){
                //发送消息
                $first   = '您好,[' . $service['name'] . ']发布了一条二手房出售信息';
                $type    = '二手房房源信息发布';
                $content = '房源标题:[' . $service['title'] . ']';
                $status  = '待审核';
                $remark  = '请尽快前往系统后台审核信息';
                News::noticeAgent('house' , $_W['aid'] , $first , $type , $content , $status , $remark , time());
            }
            $this->renderSuccess('发布二手房成功',['id'=>$id]);
        }else{
            $this->renderError('发布二手房失败，请重试');
        }
    }


    /**
     * 发布新房
     */
    public function releaseNewHouse(){
        global $_GPC, $_W;

        $id = $_GPC['id'] ?? 0;
        $set = Setting::agentsetting_read('house');
        $service = [
            'address' => $_GPC['address'],
            'apartment_name' => $_GPC['apartment_name'],
            'title' => $_GPC['title'],
            'average_price' => $_GPC['average_price'],
            'apartment_id' => $_GPC['apartment_id'],
            'start_time' => $_GPC['start_time'],
            'delivery_time' => $_GPC['delivery_time'],
            'min_price' => $_GPC['min_price'],
            'max_price' => $_GPC['max_price'],
            'developers' => $_GPC['developers'],
            'investor' => $_GPC['investor'],
            'brand_dealer' => $_GPC['brand_dealer'],
            'agent_company' => $_GPC['agent_company'],
            'year' => $_GPC['year'],
            'use_year' => $_GPC['use_year'],
            'architecture_size' => $_GPC['architecture_size'],
            'architecture_max_size' => $_GPC['architecture_max_size'],
            'cover_image' => $_GPC['cover_image'],
            'cover_video' => $_GPC['cover_video'],
            'lat' => $_GPC['lat'],
            'lng' => $_GPC['lng'],
            'current_province' => $_GPC['current_province'],
            'current_city' => $_GPC['current_city'],
            'current_area' => $_GPC['current_area'],
            'house_type' => $_GPC['house_type'],
            'decoration_id' => $_GPC['decoration_id'],
            'mobile' => $_GPC['mobile'],
            'new_ids' => $_GPC['new_ids'],
            'elevator' => $_GPC['elevator'],
            'describe' => base64_encode(htmlspecialchars_decode($_GPC['describe']))
        ];

        $service['matching_images'] = explode(',',$_GPC['matching_images']);
        $service['plan_images'] = explode(',',$_GPC['plan_images']);
        $service['outdoor_scene_images'] = explode(',',$_GPC['outdoor_scene_images']);
        $service['sand_table_images'] = explode(',',$_GPC['sand_table_images']);
        $service['example_images'] = explode(',',$_GPC['example_images']);
        $service['matching_images'] = serialize($service['matching_images']);
        $service['plan_images'] = serialize($service['plan_images']);
        $service['outdoor_scene_images'] = serialize($service['outdoor_scene_images']);
        $service['sand_table_images'] = serialize($service['sand_table_images']);
        $service['example_images'] = serialize($service['example_images']);


        if(empty($service['title'])){
            wl_message('请输入标题');
        }
        if(empty($service['cover_image'])){
            wl_message('请选择封面图片');
        }

        $modelInfo = $_GPC['house_model'];
        $modelInfo = json_decode(base64_decode($modelInfo) , true);

        if($id > 0){
            $res = pdo_update(PDO_NAME."new_house",$service,array('id' => $id));

        }else{
            $service['uniacid'] = $_W['uniacid'];
            $service['aid'] = $_W['aid'];
            $service['create_time'] = time();

            // $service['releasetype'] = $_GPC['releasetype'];
            $service['shop_id'] = $_GPC['storeid'];
            $refmoney = $this->calculatePrice(1,2);
            if($refmoney > 0){
                $service['status'] = 6;
            }else{
                if($set['passstatus'] > 0){
                    $service['status'] = 1;
                }else{
                    $service['status'] = 4;
                }
            }
            $res = pdo_insert(PDO_NAME."new_house",$service);
            $id = pdo_insertid();
        }


        $sql = " DELETE FROM ".tablename(PDO_NAME."house_model")." WHERE house_id = {$id} ";
        $res12 = pdo_query($sql);
        if (!empty($modelInfo)){
            foreach ($modelInfo as $value){
                if ($value['room']){
                    $insert_arr = [
                        'uniacid' => $_W['uniacid'],
                        'aid' => $_W['aid'],
                        'house_id' => $id,
                        'status' => 1,
                        'room' => $value['room'],
                        'office' => $value['office'],
                        'wei' => $value['wei'],
                        'kitchen' => $value['kitchen'],
                        'architecture_size' => $value['architecture_size'],
                        'orientation' => $value['orientation'],
                        'average_price' => $value['average_price'],
                        'cover_image' => $value['cover_image'],
                    ];
                    $res1 = pdo_insert(PDO_NAME."house_model",$insert_arr);
                }
            }

        }

        if($res || $res1){
            if($service['status'] == 5){
                $storename =  pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=> $_GPC['storeid'] ),'storename');
                //发送消息
                $first   = '您好,[' . $storename . ']发布了一条新房房源信息';
                $type    = '新房房源信息发布';
                $content = '房源标题:[' . $service['title'] . ']';
                $status  = '待审核';
                $remark  = '请尽快前往系统后台审核信息';
                News::noticeAgent('house' , $_W['aid'] , $first , $type , $content , $status , $remark , time());
            }
            $this->renderSuccess('发布新房成功',['id'=>$id]);
        }else{
            $this->renderError('发布新房失败，请重试');
        }
    }




    /**
     * Comment: 收藏
     */
    public function collection(){
        global $_GPC, $_W;
        $cid = $_GPC['cid'];
        $type = $_GPC['type'];
        $praiseid = pdo_getcolumn(PDO_NAME.'house_collection',array('cid'=>$cid,'type'=>$type,'mid'=>$_W['mid']),'id');
        if($praiseid){
            $res = pdo_delete(PDO_NAME.'house_collection',array('id'=>$praiseid));
            if($res){
                $this->renderSuccess('取消收藏成功');
            }else{
                $this->renderError('取消收藏失败，请刷新重试');
            }
        }else{
            $res = pdo_insert(PDO_NAME . 'house_collection', ['cid' => $cid,'type'=>$type,'mid' => $_W['mid']]);
            if($res){
                $this->renderSuccess('收藏成功');
            }else{
                $this->renderError('收藏失败，请刷新重试');
            }
        }
    }

    /**
     * Comment: 我的
     */
    public function my(){
        global $_W;
        $data             = [];
        $data['nickname'] = $_W['wlmember']['encodename'] ? base64_decode($_W['wlmember']['encodename']) : $_W['wlmember']['nickname'];  //用户昵称
        $data['bgimg']    = $_W['wlsetting']['userset']['userbg'] ? tomedia($_W['wlsetting']['userset']['userbg']) : URL_MODULE . 'h5/resource/image/userCenterImg.png';//背景图
        $data['avatar']   = tomedia($_W['wlmember']['avatar']);  //用户头像
//        if ($_W['wlmember']['mobile'] > 0) {
//            $data['truemobile'] = $_W['wlmember']['mobile'];
//            $data['mobile'] = substr($_W['wlmember']['mobile'] , 0 , 3) . '****' . substr($_W['wlmember']['mobile'] , -4 , 4); //用户手机号
//        }else if(Customized::init('integral074') > 0  && empty($_W['wlsetting']['wxappset']['examineing']) && !empty($_W['mid']) ){
//            $this->renderError('请先绑定手机号');
//        }

        //我的工具 开关显示 （1=开启；0=关闭）
        $userSet                    = User::getDefaultHouseUserMenuList();
//        //循环处理信息
        foreach ($userSet as $key => &$val) {
//            //判断是否开启
            if ($val['switch'] != 1) {
                unset($userSet[$key]);
                continue;
            }
            //处理图片路径
            $val['icon'] = tomedia($val['icon']);
            //mid参数替换
            $paramsMid = "mid={$_W['mid']}&";
            $val['link'] = str_replace('mid=&', $paramsMid, $val['link']);
        }
        $data['user_set'] = $userSet ? array_values($userSet) : [];
        $data['mid']            = $_W['mid'] ? : '';


        //是否是商户
//        $storeadmin = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND ismain IN (1,3)");
//        if (!empty($storeadmin)) {
//            $data['storeStatus'] = 1;
//        }else{
//            $data['storeStatus'] = 0;
//        }

        //浏览数量
//        $tab = tablename(PDO_NAME . "house_history");
//        $where = " WHERE mid =  {$_W['mid']}";
//        $data['new_house_history_num'] = pdo_fetchcolumn("SELECT count(*) FROM " . $tab . $where . " AND type = 1") ?? 0;
//        $data['renting_history_num'] = pdo_fetchcolumn("SELECT count(*) FROM " . $tab . $where . " AND type = 2") ?? 0;
//        $data['old_house_history_num'] = pdo_fetchcolumn("SELECT count(*) FROM " . $tab . $where . " AND type = 3") ?? 0;

        $this->renderSuccess('用户信息' , $data);
    }


    /**
     * 获取支付金额
     */
    public function getPrice(){
        global $_W,$_GPC;
        $type = $_GPC['type'];
        $release = $_GPC['releasetype'];
        $price = $this->calculatePrice($type,$release);


        $this->renderSuccess('支付金额',['price' => $price]);
    }


    /**
     * 计算价格
     * @param $type
     * @param $_W
     * @return float
     */
    public function calculatePrice($type,$release){
        global $_W;
        $price = 0.00;

        if ($release == 2){
            //商户
            $price = 0;
//            $storeid = $_GPC['storeid'];//1407
//            $tab = tablename(PDO_NAME . "chargelist");
//            $chargelist = pdo_fetch("SELECT a.id,a.new_house_price,a.renting_price,a.old_house_price FROM " . $tab . " as a left join ".tablename(PDO_NAME . "merchantdata") ." as b on a.id = b.groupid where b.id = ".$storeid);
//            $price = $chargelist[$field_key];

        }else{
            //用户
            $data = Setting::agentsetting_read('house');
            $price = 0.00;
            if ($type == 1){
                $viparray = unserialize($data['viparray1']);
            }elseif($type == 2){
                $viparray = unserialize($data['viparray2']);
            }elseif($type == 3){
                $viparray = unserialize($data['viparray3']);
            }
            $vip = WeliamWeChat::VipVerification($_W['mid']); //会员状态
            if(empty($vip)){
                $key = 'no';
            }else{
                $key = $vip['levelid'];
            }
            $price = $viparray[$key];
            if(empty($price) || $price < 0.01){
                $price = 0;
            }
        }
        return $price;
    }


    /**
     * 下单
     */
    public function createOrder(){
        global $_W,$_GPC;
        $type = $_GPC['type'];
        $id = $_GPC['id'];
        if($type == 1){
            $release = 2;
        }else if($type == 2){
            $release = pdo_getcolumn(PDO_NAME.'old_house',array('id'=>$id),'releasetype');
        }else{
            $release = pdo_getcolumn(PDO_NAME.'renting',array('id'=>$id),'releasetype');
        }

        $refmoney = $this->calculatePrice($type,$release);
        if($refmoney < 0.01){
            $this->renderError('无可支付项目');
        }
        $orderData = array(
            'uniacid'    => $_W['uniacid'],
            'mid'        => $_W['mid'],
            'aid'        => $_W['aid'],
            'fkid'       => $id,
            'createtime' => time(),
            'orderno'    => createUniontid(),
            'price'      => $refmoney,
            'plugin'     => 'house',
            'payfor'     => 'houseorder',
            'fightstatus'=> $type
        );
        pdo_insert(PDO_NAME.'order', $orderData);
        $orderid = pdo_insertid();

        if($type == 1){
            pdo_update('wlmerchant_new_house',array('orderid' => $orderid),array('id' => $id));
        }else if($type == 2){
            pdo_update('wlmerchant_old_house',array('orderid' => $orderid),array('id' => $id));
        }else{
            pdo_update('renting',array('orderid' => $orderid),array('id' => $id));
        }

        if($orderid > 0){
            $this->renderSuccess('需要支付',['orderid' => $orderid]);
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }


    /**
     * 订单列表
     */
    public function orderList(){
        global $_GPC, $_W;

        $labelTab = tablename(PDO_NAME . "order");
        $orderBy = " ORDER BY id DESC ";
        $where = " WHERE uniacid = {$_W['uniacid']} AND plugin = 'house'";
        if($_W['aid'] > 0) $where .= " AND aid = {$_W['aid']} ";
//        if($_GPC['type']) $where .= " AND type = {$_GPC['type']} ";

        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $field = 'a.id,a.status,a.fkid,a.fightstatus,a.orderno,a.createtime,a.price';
        $sql = "SELECT ".$field." FROM " . $labelTab .' as a';
//        ." LEFT JOIN ".tablename(PDO_NAME."house_adviser_house")
//        ." as b ON a.id = b.adviser_id"

        $order = pdo_fetchall($sql. $where.  $orderBy. $limit);
        foreach ($order as &$item) {
//            $item['image'] = tomedia($item['image']);
            $house_id = $item['fkid'];
            if ($item['fightstatus'] == 1){
                $house = pdo_get(PDO_NAME."new_house",['id'=>$house_id]);
                $house['cover_image'] = tomedia($house['cover_image']);
            }elseif ($item['fightstatus'] == 2){
                $house = pdo_get(PDO_NAME."old_house",['id'=>$house_id]);
                $house['cover_image'] = tomedia($house['cover_image']);
            }elseif ($item['fightstatus'] == 3){
                $house = pdo_get(PDO_NAME."renting",['id'=>$house_id]);
                $house['cover_image'] = tomedia($house['cover_image']);
            }
            $item['house'] = $house;
        }

        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);

        $data = [
            "list"  => $order,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('订单列表',$data);
    }


    /**
     * 取消订单
     */
    public function cancelOrder(){
        global $_GPC, $_W;

        $id = $_GPC['id'];


        $res = pdo_update(PDO_NAME.'order', ['status' => 5], ['id'=>$id]);
        if($res){
            $this->renderSuccess('取消成功');
        }else{
            $this->renderError('取消失败，请刷新重试');
        }
    }


    /**
     * 提交预约
     */
    public function submitMakeAppointment(){
        global $_GPC, $_W;

        $username = $_GPC['username'];
        $mobile = $_GPC['mobile'];
        $remake = $_GPC['remake'];
        $appointment_time = $_GPC['appointment_time']/1000;
        $house_id = $_GPC['house_id'];
        $type = $_GPC['type'];


        if($type == 1){
            $house = pdo_get(PDO_NAME.'new_house',array('id'=>$house_id),['shop_id','title']);
        }else if($type == 2){
            $house = pdo_get(PDO_NAME.'old_house',array('id'=>$house_id),['shop_id','title']);
        }else{
            $house = pdo_get(PDO_NAME.'renting',array('id'=>$house_id),['shop_id','title']);
        }
        $shop_id = $house['shop_id'];


        $data = array(
            'uniacid'    => $_W['uniacid'],
            'aid'        => $_W['aid'],
            'user_id'        => $_W['mid'],
            'create_time' => time(),
            'username' => $username,
            'mobile' => $mobile,
            'remake' => $remake,
            'appointment_time' => $appointment_time,
            'house_id' => $house_id,
            'type' => $type,
            'shop_id' => $shop_id,
        );
        $res = pdo_insert(PDO_NAME.'house_make_appointment', $data);
        if($res){
            //给顾问发消息
            $first = '客户[' . $_W['wlmember']['nickname'] . ']提交看房预约申请';
            $type = '用户预约提醒';
            $content = '房源标题:[' . $house['title'] . ']';
            $newStatus = '预约中';
            $remark = '点击进入预约列表联系用户';
            $url = h5_url('pages/subPages2/houseproperty/mymake/mymake' , ['sid' => $data['shop_id']]);
            //获取顾问名单
            $alladviser = pdo_getall('wlmerchant_house_adviser_house',array('house_id' => $data['house_id'],'type' =>$data['type']),array('adviser_id'));
            if(empty($alladviser)){
                News::noticeShopAdmin($data['releaseid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            }else{
                foreach ($alladviser as $adviser){
                    $mid = pdo_getcolumn(PDO_NAME.'house_adviser',array('id'=>$adviser['adviser_id']),'user_id');
                    News::jobNotice($mid,$first,$type,$content,$newStatus,$remark,time(),$url);
                }
            }

            $this->renderSuccess('提交成功');
        }else{
            $this->renderError('提交失败，请刷新重试');
        }
    }


    /**
     * 取消预约
     */
    public function cancelMakeAppointment(){
        global $_GPC, $_W;

        $id = $_GPC['id'];
        $res = pdo_delete(PDO_NAME.'house_make_appointment', ['id'=>$id]);
        if($res){
            $this->renderSuccess('取消成功');
        }else{
            $this->renderError('取消失败，请刷新重试');
        }
    }


    /**
     * 我的预约
     */
    public function myMakeAppointment(){
        global $_GPC, $_W;

        $labelTab = tablename(PDO_NAME . "house_make_appointment");

        $orderBy = " ORDER BY id DESC ";
        $where = " WHERE uniacid = {$_W['uniacid']}";

        $shop_id = $_GPC['storeid'];

        if($shop_id > 0) {
            $where .= " AND a.shop_id = {$shop_id}";
        }else{
            $where .= " AND a.user_id = {$_W['mid']}";
        }


        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $field = 'a.id,a.user_id,a.username,a.mobile,a.remake,a.appointment_time,a.create_time,a.status,a.reason,a.reject_time,a.adopt_time,a.house_id,a.type,a.shop_id';
        $sql = "SELECT ".$field." FROM " . $labelTab .' as a';

        $data = pdo_fetchall($sql. $where.  $orderBy. $limit);
        foreach ($data as &$item) {

            $house_id = $item['house_id'];
            if ($item['type'] == 1){
                $house = pdo_get(PDO_NAME."new_house",['id'=>$house_id]);
            }elseif ($item['type'] == 2){
                $house = pdo_get(PDO_NAME."old_house",['id'=>$house_id]);
            }elseif ($item['type'] == 3){
                $house = pdo_get(PDO_NAME."renting",['id'=>$house_id]);
            }

            $house['cover_image'] = tomedia($house['cover_image']);
            $item['house'] = $house;
            //信息存在
            $user = pdo_get(PDO_NAME."member",['id'=>$item['user_id']],['nickname','avatar','realname','encodename']);
            $item['nickname'] = !empty($user['encodename']) ? base64_decode($user['encodename']) : $user['nickname'];
            $item['avatar'] = tomedia($user['avatar']);
        }

        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data = [
            "list"  => $data,
            'total' => ceil($total / $pageIndex),
        ];
        $this->renderSuccess('预约列表',$data);
    }

    /**
     * 通过驳回
     */
    public function pass(){
        global $_GPC, $_W;

        $status = $_GPC['status'];
        $id = $_GPC['id'];
        if(empty($id) || empty($status)){
            $this->renderError('缺少关键参数，请返回重试');
        }
        $updateDate = [
            'status'=> $status,
            'reason'=>$_GPC['reason'],
        ];

        if ($status == 2){
            $updateDate['adopt_time'] = time();
            $first = '恭喜，您的看房预约申请已通过';
            $newStatus = '已通过';
            $remark = '请按预约时间前往看房。';


        }elseif ($status == 3){
            $updateDate['reject_time'] = time();
            $first = '很遗憾，您的看房预约申请被商家驳回';
            $newStatus = '被驳回';
            $remark = '驳回原因：'.$updateDate['reason'];
        }

        $res = pdo_update(PDO_NAME.'house_make_appointment',  $updateDate, ['id'=>$id]);
        if($res){
            //给顾问发消息
            $appinfo = pdo_get(PDO_NAME."house_make_appointment",['id'=>$id]);
            if ($appinfo['type'] == 1){
                $house = pdo_get(PDO_NAME."new_house",['id'=>$appinfo['house_id']],['title']);
            }elseif ($appinfo['type'] == 2){
                $house = pdo_get(PDO_NAME."old_house",['id'=>$appinfo['house_id']],['title']);
            }elseif ($appinfo['type'] == 3){
                $house = pdo_get(PDO_NAME."renting",['id'=>$appinfo['house_id']],['title']);
            }
            $type = '预约结果提醒';
            $content = '房源标题:[' . $house['title'] . ']';
            News::jobNotice($appinfo['user_id'],$first,$type,$content,$newStatus,$remark,time());
            $this->renderSuccess('提交成功');
        }else{
            $this->renderError('提交失败，请刷新重试');
        }
    }


    /**
     * 上架、下架
     */
    public function setStatus(){
        global $_GPC, $_W;

        $status = $_GPC['status'];//1上架，0下架
        $type = $_GPC['type'];//类型:1=新房,2=二手房,3=租房

        if ($type == 1){
            if ($status == 1){
                $status = 1;
            }elseif ($status == 0){
                $status = 2;
            }
            $table_name = 'new_house';
        }elseif ($type == 2){
            if ($status == 1){
                $status = 2;
            }elseif ($status == 0){
                $status = 1;
            }
            $table_name = 'old_house';
        }elseif ($type == 3){
            if ($status == 1){
                $status = 2;
            }elseif ($status == 0){
                $status = 1;
            }
            $table_name = 'renting';
        }else{
            $this->renderError('类型错误');
        }
        $updateDate = [
            'status'=> $status,
        ];

        $res = pdo_update(PDO_NAME.$table_name,  $updateDate, ['id'=>$_GPC['id']]);
        if($res){
            $this->renderSuccess('提交成功');
        }else{
            $this->renderError('提交失败，请刷新重试');
        }
    }


    /**
     * 删除房源
     */
    public function del(){
        global $_GPC, $_W;

        $id = $_GPC['id'];
        $type = $_GPC['type'];//类型:1=新房,2=二手房,3=租房

        if ($type == 1){
            $table_name = 'new_house';

            $sql = " DELETE FROM ".tablename(PDO_NAME."house_model")." WHERE house_id = {$id} ";
            $res = pdo_query($sql);
        }elseif ($type == 2){
            $table_name = 'old_house';
        }elseif ($type == 3){
            $table_name = 'renting';
        }else{
            $this->renderError('类型错误');
        }

        $res = pdo_delete(PDO_NAME.$table_name, ['id'=>$id]);
        if($res){
            $this->renderSuccess('删除成功');
        }else{
            $this->renderError('删除失败，请刷新重试');
        }
    }


    /**
     * 地图定位
     */
    public function map(){
        global $_GPC, $_W;
//        $where = ['uniacid' => $_W['uniacid'] , 'aid' => $_W['aid']];
        $where = "uniacid = {$_W['uniacid']} and aid = {$_W['aid']}";
        if($_GPC['latitude']) {
            list($min_latitude,$max_latitude) = explode(',',$_GPC['latitude']);
//            $where['lat'] = [$min_latitude,$max_latitude];
            $where .= " and lat between {$min_latitude} and {$max_latitude}";
        }
        if($_GPC['longitude']) {
            list($min_longitude,$max_longitude) = explode(',',$_GPC['longitude']);
//            $where['lng'] = [$min_longitude,$max_longitude];
            $where .= " and lng between {$min_longitude} and {$max_longitude}";
        }

//        $where_1 = array_merge($where,['status' => 1]);
//        $where_2 = array_merge($where,['status' => [2,3]]);
//        $where_3 = array_merge($where,['status' => [2,3]]);
        $where_1 = $where.' and status = 1';
        $where_2 = $where.' and status in (2,3)';
        $where_3 = $where.' and status in (2,3)';
        $new_house = pdo_getall(PDO_NAME."new_house",$where_1,['id','title','lng','lat','average_price']);
        $old_house = pdo_getall(PDO_NAME."old_house",$where_2,['id','title','lng','lat','price']);
        $renting = pdo_getall(PDO_NAME."renting",$where_3,['id','title','lng','lat','rent']);

        $data =  array_merge($new_house,$old_house,$renting);
        if (!empty($data)){
            foreach ($data as $k => &$item){
                $item['num'] = $item['id'];
                $item['id'] = $k+1;
                $item['iconPath'] = '/static/icon_position.png';
                $item['longitude'] = $item['lng'];
                $item['latitude'] = $item['lat'];
                unset($item['lng'],$item['lat']);
                $prcie = 0.00;
                $type_name = '';
                $type = 0;
                if (isset($item['average_price'])){
                    $prcie = $item['average_price'].'元/㎡';
                    $type = 1;
                    $type_name = '新房';
                }elseif (isset($item['price'])){
                    $prcie = $item['price'].'万元';
                    $type = 2;
                    $type_name = '二手房';
                }elseif (isset($item['rent'])){
                    $prcie = $item['rent'].'元/月';
                    $type = 3;
                    $type_name = '租房';
                }
                $item['type'] = $type;
                $item['callout'] = [
                    'content' => $type_name."\n".$item['title']."\n 价格：".$prcie,
                    'color' => "#333",
                    'borderRadius' => 10,
                    'borderColor' => '',
                    'bgColor' => "#fff",
                    'display' => "ALWAYS",
                    'textAlign' => "center",
                ];
            }
        }
        $this->renderSuccess('地图列表',$data);
    }


    /**
     * 创建商户和店长
     */
    public function createStore()//logo  商家名   电话  省市区   详细地址  经纬度  店铺图片
    {
        global $_W , $_GPC;
//        $set               = Setting::wlsetting_read('register');
        $data              = [];
        $data['storename'] = $_GPC['storename'];
//        $sale_id = intval($_GPC['sale_id']);
        if (empty($data['storename'])) {
            $this->renderError('请输入商户名');
        }
        $textRes = Filter::init($data['storename'],$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError('商户名'.$textRes['message']);
        }
//        $storeid = $_GPC['storeid'];
        //获取商户数据
        $data['realname'] = trim($_GPC['name']);
        $data['mobile']   = $_GPC['mobile'];
        $data['logo']     = $_GPC['logo'];
//        $adv              = $_GPC['adv'];
//        if (!empty($adv)) {
//            $data['adv'] = serialize(explode(',' , $adv));
//        }
//        else {
//            $data['adv'] = '';
//        }
        $album = $_GPC['album'];
        if (!empty($album)) {
            $data['album'] = serialize(explode(',' , $album));
        }
        else {
            $data['album'] = '';
        }
//        $examineimg = $_GPC['examine'];
//        if (!empty($examineimg)) {
//            $data['examineimg'] = serialize(explode(',' , $examineimg));
//        }
//        else {
//            $data['examineimg'] = '';
//        }
        $data['location']   = serialize(['lng' => $_GPC['lng'] , 'lat' => $_GPC['lat']]);
        $data['lng'] = $_GPC['lng'];
        $data['lat'] = $_GPC['lat'];
        $data['provinceid'] = $_GPC['provinceid'];
        $data['areaid']     = $_GPC['areaid'];
        $data['distid']     = $_GPC['distid'];
        $data['address']    = $_GPC['address'];
//        $data['introduction'] = $_GPC['detail'];//店铺简介

//        if ($storeid) {
//            Tools::clearwxapp();
//            Tools::clearposter();
//            $storestatus = pdo_get(PDO_NAME . 'merchantdata' , ['id' => $storeid] , ['status' , 'aid']);
//            $alflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$storestatus['aid']} AND storename = {$data['storename']} AND id != {$storeid}");
//            if ($alflag) {
//                $this->renderError('该商户已入驻，请更换商户名');
//            }
//            if ($storestatus['status'] == 3) {
//                $data['status']     = 1;
//                $userdata['status'] = 1;
//                //发送消息
//                $first   = '您好,' . $_GPC['name'] . '修改了新商家入驻信息';
//                $type    = '店铺入驻资料修改';
//                $content = '店铺名:[' . $data['storename'] . ']';
//                $status  = '待审核';
//                $remark  = '请尽快前往系统后台审核商家资料';
//                News::noticeAgent('storesettled' , $storestatus['aid'] , $first , $type , $content , $status , $remark , time());
//            }
//            pdo_update(PDO_NAME . 'merchantdata' , $data , ['id' => $storeid]);
////            //插入分类
////            if(!empty($_GPC['cateidArray'])){
////                pdo_delete('wlmerchant_merchant_cate' , ['sid' => $storeid]);
////                $cateidArray = json_decode(base64_decode($_GPC['cateidArray']),true);
////                foreach ($cateidArray as $cate){
////                    $resss = pdo_insert('wlmerchant_merchant_cate' , [
////                        'sid'      => $storeid ,
////                        'onelevel' => intval($cate['onelevel']) ,
////                        'twolevel' => intval($cate['twolevel'])
////                    ]);
////                }
////            }
//            $userdata['name']   = $_GPC['name'];
//            $userdata['mobile'] = $_GPC['mobile'];
//            pdo_update(PDO_NAME . 'merchantuser' , $userdata , ['storeid' => $storeid , 'ismain' => 1]);
//
//            $this->renderSuccess('修改商户成功' , $storeid);
//        } else {
        //详情
//            $data['introduction'] = $_GPC['introduction'];
//            $storethumb           = $_GPC['thumbimages'];
//            if ($storethumb) {
//                $storethumb = explode(',' , $storethumb);
//                foreach ($storethumb as $key => $th) {
//                    $th                   = tomedia($th);
//                    $data['introduction'] .= '<img src= "' . $th . '" />';
//                }
//            }
//            if (!empty($data['introduction'])) {
//                $data['introduction'] = htmlspecialchars_decode($data['introduction']);
//            }
        $data['uniacid'] = $_W['uniacid'];
        $aid             = pdo_getcolumn(PDO_NAME . 'oparea' , [
            'uniacid' => $_W['uniacid'] ,
            'areaid'  => $data['distid'] ,
            'status'  => 1
        ] , 'aid');
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $data['areaid'] ,
                'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $data['provinceid'] ,
                'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = 0;
        }
        $data['aid']        = $_W['aid'] ? $_W['aid'] : $aid;
        $alflag = pdo_get('wlmerchant_merchantdata' , [
            'storename' => $data['storename'] ,
            'uniacid'   => $_W['uniacid'],
            'aid'       => $data['aid']
        ] , ['id']);
        if ($alflag) {
            $this->renderError('该商户已入驻，请更换商户名');
        }
        $data['createtime'] = time();
        //设置默认商户状态为 未支付
        $data['status']  = 0;
        $data['endtime'] = time();
        $data['enabled'] = 0;
        $data['score']   = 5;
        $data['groupid'] = 0;
        $storeid         = Store::registerEditData($data , 0);
        if (empty($storeid)) {
            $this->renderError('商户创建失败，请重试！' , $data);
        }
//            //插入分类
//            $cateidArray = json_decode(base64_decode($_GPC['cateidArray']),true);
//            foreach ($cateidArray as $cate){
//                $resss = pdo_insert('wlmerchant_merchant_cate' , [
//                    'sid'      => $storeid ,
//                    'onelevel' => intval($cate['onelevel']) ,
//                    'twolevel' => intval($cate['twolevel'])
//                ]);
//            }
        $arr['storeid']    = $storeid;
        $arr['name']       = trim($_GPC['name']);
        $arr['mobile']     = $_GPC['mobile'];
        $arr['createtime'] = time();
        if (empty($data['areaid'])) {
            $arr['areaid'] = $_W['areaid'];
        }
        else {
            $arr['areaid'] = $data['areaid'];
        }
        $arr['status']      = 1;
        $arr['enabled']     = 1;
        $arr['ismain']      = 1;
        $arr['uniacid']     = $_W['uniacid'];
        $arr['aid']         = $_W['aid'] ? $_W['aid'] : $aid;
        $re                 = Store::saveSingleRegister($arr , 0);
        $data['storeid']    = $storeid;
        $data['chargeflag'] = 1;
        if (empty($re)) {
            $this->renderError('店铺管理员创建失败，请重试！');
        }
//            //添加业务员
//            if(!empty($sale_id > 0 && $_GPC['sale_id'] != $_W['mid'])){
//                $_W['aid'] = $aid;
//                $saleset = Setting::agentsetting_read('salesman');
//                if($saleset['isopen']>0){
//                    $salemember = pdo_get('wlmerchant_member',array('id' => intval($sale_id)),array('realname','nickname','mobile'));
//                    $saledata = array(
//                        'mid'          => $sale_id,
//                        'storeid'      => $storeid,
//                        'name'         => $salemember['realname'] ? $salemember['realname'] : $salemember['nickname'],
//                        'mobile'       => $salemember['mobile'],
//                        'enabled'      => 1,
//                        'uniacid'      => $_W['uniacid'],
//                        'aid'          => $aid,
//                        'ismain'       => 4,
//                        'createtime'   => time()
//                    );
//                    pdo_insert('wlmerchant_merchantuser', $saledata);
//                }
//            }
        $this->renderSuccess('创建商户成功' , $data);
//        }
    }


    /**
     * 分享
     */
    public function share(){
        global $_GPC, $_W;

        $house_id = $_GPC['house_id'];
        if ($_GPC['type'] == 1){
            $house = pdo_get(PDO_NAME."new_house",['id'=>$house_id],['share_title','share_logo','share_describe']);
        }elseif ($_GPC['type'] == 2){
            $house = pdo_get(PDO_NAME."old_house",['id'=>$house_id],['share_title','share_logo','share_describe']);
        }elseif ($_GPC['type'] == 3){
            $house = pdo_get(PDO_NAME."renting",['id'=>$house_id],['share_title','share_logo','share_describe']);
        }


        $data = [
            'title' => $house['share_title'],
            'logo' => tomedia($house['share_logo']),
            'share_describe' => htmlspecialchars_decode($house['share_describe']),
            'url' => $_GPC['url']
        ];
        $this->renderSuccess('分享',$data);
    }


    /**
     * Comment: 小区列表筛选项目
     * Author: wlf
     * Date: 2022/08/04 9:48
     */
    public function apaScreen(){
        global $_GPC, $_W;
        $price[] = ['min' => 0,'max' => 3000];
        $price[] = ['min' => 3000,'max' => 5000];
        $price[] = ['min' => 5000,'max' => 7000];
        $price[] = ['min' => 7000,'max' => 10000];
        $price[] = ['min' => 10000,'max' => 15000];
        $price[] = ['min' => 15000,'max' => 30000];
        $price[] = ['min' => 30000,'max' => 0];

        $constructions = pdo_getall('wlmerchant_house_label',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 3),array('id','title'),'',['weigh DESC']);

        $data = [
            'price' => $price,
            'constructions' => $constructions
        ];

        $this->renderSuccess('小区列表筛选项目',$data);
    }

    /**
     * Comment: 小区列表
     * Author: wlf
     * Date: 2022/08/03 18:07
     */
    public function apartmentList(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;

        $areaid = $_GPC['areaid'];
        $min = $_GPC['min'];
        $max = $_GPC['max'];
        $name = trim($_GPC['name']);
        $constructions = $_GPC['constructions'];

        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $AreaTab = tablename(PDO_NAME . "house_apartment");
        $orderBy = " ORDER BY sort DESC,id DESC ";
        $where = " WHERE uniacid = {$_W['uniacid']} ";

        if($areaid > 0){
            $where .= " AND (current_province = {$areaid} OR current_city = {$areaid} OR 	current_area = {$areaid} )";
        }
        if($min > 0){
            $where .= " AND averageprice > {$min}";
        }
        if($max > 0){
            $where .= " AND averageprice < {$max}";
        }
        if(!empty($name)){
            $where .= " AND name LIKE '%{$name}%'";
        }
        if($constructions > 0){
            $where .= " AND construction_id  = {$constructions}";
        }
        if ($_GPC['is_collect']){
            $field = 'a.id,a.name,a.averageprice,a.current_city,a.current_area,a.characteristic_ids,a.construction_id,a.cover_image,a.construction_time';

            $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."house_apartment")
                ." as a LEFT JOIN ".tablename(PDO_NAME."house_collection")
                ." as b ON a.id = b.cid";

            $wheres =  " WHERE a.uniacid = {$_W['uniacid']} AND b.type = 5 AND b.mid = {$_W['mid']}";
            $orderBy = " ORDER BY a.sort DESC,a.id DESC ";

            $area = pdo_fetchall($sql.$wheres.$orderBy.$limit);
        }else{
            $area = pdo_fetchall("SELECT id,name,averageprice,current_city,current_area,characteristic_ids,construction_id,cover_image,construction_time FROM " . $AreaTab . $where .  $orderBy.$limit);
        }


        if(!empty($area)){
            foreach($area as &$arr){
                $labelids = [];
                $arr['cover_image'] = tomedia($arr['cover_image']);
                //位置
                $cityname = pdo_getcolumn(PDO_NAME.'area',array('id'=>$arr['current_city']),'name');
                $areaname = pdo_getcolumn(PDO_NAME.'area',array('id'=>$arr['current_area']),'name');
                $arr['address'] = $cityname.$areaname;

                //建造时间
                $arr['construction_time'] = date('Y',$arr['construction_time']);
                if($arr['construction_id'] > 0){
                    $labelids[] = $arr['construction_id'];
                }
                if(!empty($arr['characteristic_ids'])){
                    $labelids = array_merge($labelids,explode(',',$arr['characteristic_ids']));
                }
                if(!empty($labelids)){
                    foreach ($labelids as $lid){
                        $arr['label_list'][] = pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$lid),'title');
                    }
                }
                unset($arr['current_city']);
                unset($arr['current_area']);
                unset($arr['construction_id']);
                unset($arr['characteristic_ids']);
            }
        }
        $allnum = count(pdo_fetchall("SELECT id FROM " . $AreaTab . $where));
        $allpage = ceil($allnum/10);
        $data = [
            'total' => $allpage,
            'list' => $area
        ];

        $this->renderSuccess('小区列表',$data);
    }


    /**
     * Comment: 小区详情
     * Author: wlf
     * Date: 2022/08/04 10:48
     */
    public function apartmentInfo(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        if(empty($id)){
            $this->renderError('参数错误，请返回重试');
        }
        $info = pdo_get('wlmerchant_house_apartment',array('id' => $id));
        if(empty($info)){
            $this->renderError('小区已下线，无法访问');
        }
        //图集
        $info['cover_images'] = beautifyImgInfo($info['cover_images']);
        //视频
        $info['cover_video'] = tomedia($info['cover_video']);
        //地区信息
        $cityname = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['current_city']),'name');
        $areaname = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['current_area']),'name');
        $info['areainfo'] = $cityname.' - '.$areaname;
        //更新时间
        $info['create_time'] = date('Y-m-d',$info['create_time']);
        //收藏人数
        $info['collectionnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_house_collection')." WHERE cid = {$id} AND type = 5");
        $info['collectionflag'] = pdo_getcolumn(PDO_NAME.'house_collection',array('cid'=>$id,'mid'=>$_W['mid'],'type' => 5),'id');
        $info['collectionnum'] = $info['collectionnum'] + $info['falsecon'];
        //统计出售信息
        $info['newnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_new_house')." WHERE apartment_id = {$id} AND status = 1");
        $info['oldnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_old_house')." WHERE apartment_id = {$id} AND status = 2");
        $info['rennum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_renting')." WHERE apartment_id = {$id} AND status = 2");
        //建成年代
        $info['construction_time'] = date('Y',$info['construction_time']);
        //建筑类型
        if($info['construction_id'] >0){
            $info['construction'] = pdo_getcolumn(PDO_NAME.'house_label',array('id'=>$info['construction_id']),'title');
        }else{
            $info['construction'] = '其他';
        }
        //小区描述
        if(is_base64($info['describe'])) $info['describe']   = htmlspecialchars_decode(base64_decode($info['describe']));
        //小区特色
        if(!empty($info['characteristic_ids'])){
            $info['characteristic_ids'] = explode(',',$info['characteristic_ids']);
            $labelList = pdo_getall(PDO_NAME."house_label",['id IN'=>$info['characteristic_ids']],['title']);
            $labelList = array_column($labelList,'title');
            $info['characteristic'] =  implode('/',$labelList);
        }else{
            $info['characteristic'] = '';
        }

        //删除无用数据
        unset($info['cover_image']);
        unset($info['video_image']);
        unset($info['traffic']);
        unset($info['property_desc']);
        unset($info['property_ids']);
        unset($info['characteristic_ids']);
        unset($info['construction_id']);
        unset($info['shop_id']);
        unset($info['current_province']);
        unset($info['current_city']);
        unset($info['current_area']);
        unset($info['falsecon']);
        unset($info['sort']);


        $this->renderSuccess('小区详情',$info);
    }


    /**
     * Comment: 结束查阅
     * Author: wlf
     * Date: 2022/08/08 15:48
     */
    public function endConsult(){
        global $_GPC, $_W;
        $cid = $_GPC['house_id'];
        $type = $_GPC['type'];

        $info = pdo_get('wlmerchant_house_history',array('cid' => $cid,'mid' => $_W['mid'],'type' => $type),array('id','create_time','alltime'));
        $newtime = time() - $info['create_time'] + $info['alltime'];
        pdo_update('wlmerchant_house_history',array('alltime' => $newtime),array('id' => $info['id']));

        $this->renderSuccess('ok');

    }

    /**
     * Comment: 获客列表
     * Author: wlf
     * Date: 2022/08/08 16:28
     */

    public function getHistory(){
        global $_GPC, $_W;
        $type = $_GPC['type']; //类型 1个人 2商户
        $sid = $_GPC['storeid'];
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        if($type == 1){
            $where = " WHERE releasetype = 1 AND releaseid = {$_W['mid']}";
        }else{
            $where = " WHERE releasetype = 2 AND releaseid = {$sid}";
        }
        $limit = " LIMIT {$pageStart},{$pageIndex} ";

        $AreaTab = tablename(PDO_NAME . "house_history");
        $orderBy = " ORDER BY create_time DESC";

        $list = pdo_fetchall("SELECT cid,mid,type,create_time,alltime  FROM".$AreaTab . $where .  $orderBy.$limit);
        foreach ($list as &$li){
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','mobile','nickname','encodename'));
            //用户信息
            $li['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $li['mobile'] = $member['mobile'];
            $li['avatar'] = tomedia($member['avatar']);
            //帖子信息
            if($li['type'] == 1){
                $house = pdo_get('wlmerchant_new_house',array('id' => $li['cid']),array('title','cover_image'));
            }else if($li['type'] == 2){
                $house = pdo_get('wlmerchant_old_house',array('id' => $li['cid']),array('title','cover_image'));
            }else if($li['type'] == 3){
                $house = pdo_get('wlmerchant_renting',array('id' => $li['cid']),array('title','cover_image'));
            }

            $li['housename'] = $house['title'];
            $li['houseimg'] = tomedia($house['cover_image']);
            //时间
            if($li['alltime'] > 0){
                $li['min'] = floor($li['alltime']/60);
                $li['sec'] = $li['alltime']%60;
            }
            $uptime = time() - $li['create_time'];
            if($uptime > 86400){
                $day = floor($uptime/86400);
                $li['uptime'] = $day.'天前';
            }else if($uptime > 3600){
                $hour = floor($uptime/3600);
                $li['uptime'] = $hour.'小时前';
            }else{
                $min = ceil($uptime/60);
                $li['uptime'] = $min.'分钟前';
            }
        }
        $allnum = count(pdo_fetchall("SELECT id FROM " . $AreaTab . $where));
        $allpage = ceil($allnum/10);
        $data = [
            'total' => $allpage,
            'list' => $list
        ];
        $this->renderSuccess('获得列表',$data);
    }



}