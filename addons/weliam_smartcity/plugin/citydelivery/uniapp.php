<?php
defined('IN_IA') or exit('Access Denied');

class CitydeliveryModuleUniapp extends Uniapp {

    /**
     * Comment: 外卖商户列表信息
     * Author: wlf
     * Date: 2020/03/26 16:48
     */
    function storeList(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageStart = $page * 20 - 20;
        $lng       = $_GPC['lng'] && $_GPC['lng'] != 'undefined' ? $_GPC['lng'] : 104.0091133118;//用户当前所在经度 104.0091133118 经度
        $lat       = $_GPC['lat'] && $_GPC['lat'] != 'undefined' ? $_GPC['lat'] : 30.5681964123;//用户当前所在纬度 30.5681964123  纬度
        $name      = trim($_GPC['name']);

        $sort      = $_GPC['order'] ? $_GPC['order'] : 0;//排序方式
        $cateOne    = $_GPC['cate_one'] ? : 0;//一级分类id
        $cateTwe    = $_GPC['cate_two'] ? : 0;//二级分类id
        $regionId   = $_GPC['region_id'] ? : 0;
        //筛选条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND deliverystatus = 1 AND enabled = 1";
        if(!empty($name)){
            $where .= " AND storename LIKE '%{$name}%'";
        }
        if ($regionId > 0) $getAid = pdo_getcolumn(PDO_NAME . "oparea" , [
            'areaid'  => $regionId ,
            'status'  => 1 ,
            'uniacid' => $_W['uniacid']
        ] , 'aid');
        $aid      = $getAid > 0 ? $getAid : $_W['aid'];
        $where .= " AND aid = {$aid}";
        if ($cateOne > 0) {
            $cateWhere = " WHERE onelevel = {$cateOne} ";
            if ($cateTwe > 0) $cateWhere .= "  AND twolevel = {$cateTwe} ";
            $ids          = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . "merchant_cate") . $cateWhere);
            $where .= " AND id in (" . implode(',' , array_column($ids , 'sid')) . ") ";
        }
        //排序条件
        switch ($sort) {
            case 0:
                $order = " ORDER BY distance ASC,id DESC";
                break;
            case 1:
                $order = " ORDER BY createtime DESC";
                break;
            case 2:
                $order = " ORDER BY listorder DESC,id DESC";
                break;
            case 3:
                $order = " ORDER BY pv DESC,id DESC";
                break;
        }
        //商户列表获取
        $field = "id,id as storeid,
        (SELECT 
            CASE 
                WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.137 * 2 * ASIN(
                        SQRT(
                            POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                            )
                        ) * 1000
                    ) 
                ELSE 0
            END FROM ".tablename(PDO_NAME.'merchantdata')." as b WHERE b.id = storeid) as distance,
            storename,logo,mobile,address,score,storehours,deliverymoney,tag,pv,lat,lng,virtual_sales";

     //   $data['list'] = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata") .$where.$order." LIMIT {$pageStart},20");
        $storelist = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata") .$where.$order);
        $business = $nobusiness = [];
        if(!empty($storelist)){
            foreach ($storelist as &$item){
                $item['storehours'] = unserialize($item['storehours']);
                //判断是否营业中
                $item['is_business'] = Store::getShopBusinessStatus($item['storehours'],$item['id']);//判断商户当前状态：0=休息中，1=营业中
                $item['tag'] = unserialize($item['tag']);
                //获取店铺分类信息
                $storecates = pdo_getall('wlmerchant_merchant_cate',array('sid' => $item['id'],'twolevel >' => 0),array('twolevel'));
                $item['catename'] = '';
                if(!empty($storecates)){
                    foreach ($storecates as $ke => $cate){
                        $catename = pdo_getcolumn(PDO_NAME.'category_store',array('id'=>$cate['twolevel']),'name');
                        if($ke > 0){
                            $item['catename'] .= '|'.$catename;
                        }else{
                            $item['catename'] .= $catename;
                        }
                    }
                }
                //查询已售数量
                $item['salenum'] = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $item['id'],'status >' => 0),array("SUM(num)"));
                $item['salenum'] = $item['salenum'] ? $item['salenum'] : 0;
                //查询虚拟销量
                $item['falsenum'] = pdo_getcolumn('wlmerchant_delivery_activity',array('sid' => $item['id'],'uniacid' => $_W['uniacid']),array("SUM(fictitiousnum)"));
                $item['falsenum'] = $item['falsenum'] ? $item['falsenum'] : 0;
                $item['salenum'] = $item['salenum'] + $item['falsenum'];
                //商户虚拟销量
                $item['salenum'] = $item['virtual_sales'] > $item['salenum'] ? $item['virtual_sales'] : $item['salenum'];

                if ($item['distance'] > 1000) {
                    $item['distance'] = (floor(($item['distance'] / 1000) * 10) / 10) . "km";
                }else {
                    $item['distance'] = round($item['distance']) . "m";
                }
                $item['logo'] = tomedia($item['logo']);

                unset($item['storehours']);
                if($item['is_business'] == 1){
                    $business[] = $item;
                }else{
                    $nobusiness[] = $item;
                }
            }
        }
        $data['list'] = array_merge($business,$nobusiness);
        $data['list'] = array_splice($data['list'] , $pageStart , 20);
        //页数获取
        $is_init = $_GPC['is_init'] ? $_GPC['is_init'] : 0;//0=不获取总页数；1=获取总页数
        if($is_init>0){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."merchantdata") .$where);
            $data['total'] = ceil($total / 10);
            //获取社群数据
            $base = Setting::agentsetting_read('citydelivery');
            if($base['communityid']>0){
                $community = pdo_get('wlmerchant_community',array('id' => $base['communityid']),array('communname','commundesc','communimg','communqrcode','systel'));
                if(!empty($community['communqrcode'])){
                    $community['communimg'] = tomedia($community['communimg']);
                    $community['communqrcode'] = tomedia($community['communqrcode']);
                    $data['community'] = $community;
                }
            }
            //获取幻灯片数据
            $adves = Dashboard::getAllAdv(0,10,1,12);
            if(!empty($adves)){
                foreach ($adves['data'] as &$adv){
                    $adv['thumb'] = tomedia($adv['thumb']);
                }
            }
            $data['advs'] = $adves['data'];
            //购物车角标
            $data['cartnum'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('aid' => $_W['aid'],'mid'=>$_W['mid']),array("SUM(num)"));
        }

        $this->renderSuccess('商户列表', $data);
    }

    /**
     * Comment: 外卖商品分类列表信息
     * Author: wlf
     * Date: 2020/03/31 11:41
     */
    function cateList(){
        global $_W,$_GPC;
        //参数获取
        $storeid = $_GPC['storeid'];
        if(empty($storeid)){
            $this->renderError('无商户id，请返回重试');
        }
        //获取商户基础信息
        $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('storename','deliverytype','lowdeliverymoney','deliverydistance','deliverymoney','logo','adv','score','storehours','tag','delivery_adv','proportion','mobile','exemptmoney'));
        $storehours = unserialize($storeinfo['storehours']);
        if(!empty($storehours['startTime'])){
            $storeinfo['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
        }else{
            $storeinfo['storehours'] = '';
            foreach($storehours as $hk => $hour){
                if($hk > 0){
                    $storeinfo['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                }else{
                    $storeinfo['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                }
            }
        }

        $storeinfo['is_business'] = Store::getShopBusinessStatus($storehours,$storeid);//判断商户当前状态：0=休息中，1=营业中
        $storeinfo['logo'] = tomedia($storeinfo['logo']);
        $deliverytype = unserialize($storeinfo['deliverytype']);
        //幻灯片获取
        $adv = unserialize($storeinfo['adv']);
        $deliveryAdv = unserialize($storeinfo['delivery_adv']);
        $storeinfo['album'] = is_array($deliveryAdv) && count($deliveryAdv) > 0? $deliveryAdv : $adv;
        if(!empty($storeinfo['album'])){
            foreach ($storeinfo['album'] as &$al){
                $al = tomedia($al);
            }
        }
        $storeinfo['tag'] = unserialize($storeinfo['tag']);
        if(!empty($storeinfo['tag'])){
            foreach ($storeinfo['tag'] as &$tag){
                $taginfo = pdo_get(PDO_NAME . "tags" , ['id' => $tag] , ['title','content']);
                $tag = $taginfo['title'];
                $storeinfo['tagslist'][] = $taginfo;
            }
        }else{
            $storeinfo['tag'] = [];
        }
        if(in_array('store',$deliverytype)){
            if($storeinfo['deliverydistance']>0){
                $storeinfo['tag'][] = $storeinfo['deliverydistance'].'km内配送';
                $storeinfo['tagslist'][] = array( 'title'=> $storeinfo['deliverydistance'].'km内配送','content'=> '此商户只配送'.$storeinfo['deliverydistance'].'km内的订单');

            }
            if($storeinfo['lowdeliverymoney']>0){
                $storeinfo['tag'][] = $storeinfo['lowdeliverymoney'].'元起送';
                $storeinfo['tagslist'][] = array( 'title'=> $storeinfo['lowdeliverymoney'].'元起送','content'=> '此商户订单'.$storeinfo['lowdeliverymoney'].'元起送');
            }
            if($storeinfo['exemptmoney']>0){
                $storeinfo['tag'][] = $storeinfo['exemptmoney'].'元免费配送';
                $storeinfo['tagslist'][] = array( 'title'=> $storeinfo['exemptmoney'].'元免费配送','content'=> '此商户订单商品实际支付金额满'.$storeinfo['exemptmoney'].'元免费配送');
            }
            
        }
        //获取店铺分类信息
        $storecates = pdo_getall('wlmerchant_merchant_cate',array('sid' => $storeid,'twolevel >' => 0),array('twolevel'));
        $storeinfo['catename'] = '';
        if(!empty($storecates)){
            foreach ($storecates as $ke => $cate){
                $catename = pdo_getcolumn(PDO_NAME.'category_store',array('id'=>$cate['twolevel']),'name');
                if($ke > 0){
                    $storeinfo['catename'] .= '|'.$catename;
                }else{
                    $storeinfo['catename'] .= $catename;
                }
            }
        }
        unset($storeinfo['deliverytype']);

        //获取购物车数据
        $cartinfo = Citydelivery::getCartInfo($_W['mid'],$storeid);
        if($cartinfo['totalnum']>0){
            $cartinfo['deliveryprice'] = sprintf("%.2f",$cartinfo['deliveryprice'] + $storeinfo['deliverymoney']);
            $cartinfo['totalmoney'] = sprintf("%.2f",$cartinfo['totalmoney'] + $storeinfo['deliverymoney']);
        }
        $proportion = unserialize($storeinfo['proportion']);
        $imgstyle['width'] = $proportion['deliverywidth'];
        $imgstyle['height'] = $proportion['deliveryheight'];

        if(empty($imgstyle['width'])){
            $imgstyle['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        }
        if(empty($imgstyle['height'])) {
            $imgstyle['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) : 560;
        }

        $this->renderSuccess('商户信息', array('storeinfo'=>$storeinfo,'cartinfo'=>$cartinfo,'imgstyle' => $imgstyle));
    }


    /**
     * Comment: 获取店铺商品接口
     * Author: wlf
     * Date: 2021/05/19 10:58
     */
    function goodsInfo(){
        global $_W,$_GPC;
        //参数获取
        $storeid = $_GPC['storeid'];
        if(empty($storeid)){
            $this->renderError('无商户id，请返回重试');
        }
//        $caCheName = md5("sid={$storeid}&mid={$_W['mid']}");//缓存名
//        //获取缓存中的回放信息
//        $data = Cache::getCache('delivery',$caCheName);
//        $data = json_decode($data,true);
//        if(empty($data)){
            //获取快递商品分类
            $catelist = pdo_fetchall("SELECT id,name FROM ".tablename('wlmerchant_delivery_category')."WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND status = 1 ORDER BY sort DESC");
            if(!empty($catelist)){
                $falseid = 1;
                foreach ($catelist as &$cate){
                    $cate['goodsnum'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('cateid' => $cate['id'],'mid'=>$_W['mid']),array("SUM(num)"));
                    if(empty($cate['goodsnum'])){
                        $cate['goodsnum'] = 0;
                    }
                    //所属商品
                    $where = " WHERE uniacid = {$_W['uniacid']} AND status IN (2,7) AND cateid = {$cate['id']} AND sid = {$storeid}";
                    $order = " ORDER BY sort DESC,id DESC";
                    $categoods = pdo_fetchall("SELECT id,name,price,oldprice,allstock,daystock,status,thumb,vipstatus,vipdiscount,optionstatus FROM ".tablename(PDO_NAME."delivery_activity") .$where.$order);
                    if(!empty($categoods)){
                        foreach ($categoods as &$good){
                            $good['thumb'] = tomedia($good['thumb']);
                            if($good['optionstatus'] > 0){
                                $good['optionarray'] = pdo_fetchall("SELECT id,name,allstock,daystock,price,oldprice FROM ".tablename('wlmerchant_delivery_spec')."WHERE goodsid = {$good['id']} ORDER BY sort DESC");
                                foreach ($good['optionarray'] as $key => &$option){
                                    $option['num'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('goodid' => $good['id'],'specid' => $option['id'],'mid'=>$_W['mid']),array("SUM(num)"));
                                    if(empty($option['num'])){
                                        $option['num'] = 0;
                                    }
                                    //判断库存
                                    if($option['allstock'] > 0){
                                        $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $storeid,'gid' => $good['id'],'specid' => $option['id'],'status >' => 0),array("SUM(num)"));
                                        if($allsalenum > $option['allstock'] || $allsalenum == $option['allstock']){
                                            unset($good['optionarray'][$key]);
                                        }
                                    }
                                    if($option['daystock'] > 0){
                                        $nowtime = strtotime(date('Y-m-d',time()));
                                        $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $storeid,'gid' => $good['id'],'specid' => $option['id'],'status >' => 0,'createtime >' => $nowtime),array("SUM(num)"));
                                        if($daysalenum > $option['daystock'] || $daysalenum == $option['daystock']){
                                            unset($good['optionarray'][$key]);
                                        }
                                    }
                                }
                                $good['optionarray'] = array_values($good['optionarray']);
                                if(empty($good['optionarray'])){
                                    $good['saleoverflag'] = 1;
                                }
                            }else{
                                $good['optionarray'] = [];
                                //判断库存
                                if($good['allstock'] > 0){
                                    $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $storeid,'gid' => $good['id'],'status >' => 0),array("SUM(num)"));
                                    if($allsalenum > $good['allstock'] || $allsalenum == $good['allstock']){
                                        $good['saleoverflag'] = 1;
                                    }
                                }
                                if($good['daystock'] > 0 && empty($good['saleoverflag'])){
                                    $nowtime = strtotime(date('Y-m-d',time()));
                                    $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $storeid,'gid' => $good['id'],'status >' => 0,'createtime >' => $nowtime),array("SUM(num)"));
                                    if($daysalenum > $good['daystock'] || $daysalenum == $good['daystock']){
                                        $good['saleoverflag'] = 1;
                                    }
                                }
                            }
                            //查询购物车中已有数量
                            $good['num'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('goodid' => $good['id'],'mid'=>$_W['mid']),array("SUM(num)"));
                            $good['num'] = $good['num'] ? $good['num'] : 0;
                            //查询已售数量
                            $good['salenum'] = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $storeid,'gid' => $good['id'],'status >' => 0),array("SUM(num)"));
                            $good['salenum'] = $good['salenum'] ? $good['salenum'] : 0;
                            $good['falsenum'] = pdo_getcolumn(PDO_NAME.'delivery_activity',array('uniacid'=>$_W['uniacid'],'id'=>$good['id']),'fictitiousnum');
                            $good['salenum'] = $good['salenum'] + $good['falsenum'];

                            $good['pid'] = $falseid;
                        }
                    }
                    $categoodslist = array(
                        'pid'       => $cate['id'],
                        'id'        => $falseid,
                        'catename'  => $cate['name'],
                        'categoods' => $categoods
                    );
                    $goodslist[] = $categoodslist;
                    $falseid++;
                }
            }
            $data = ['catelist'=>$catelist,'goodslist'=>$goodslist];
//            if(!empty($catelist) && !empty($goodslist)){
//                Cache::setCache('delivery',$caCheName,json_encode($data));
//            }
//        }

        $this->renderSuccess('商户商品信息', $data);
    }

    /**
     * Comment: 外卖商品分类列表信息(未使用)
     * Author: wlf
     * Date: 2020/03/31 16:58
     */
//    function goodsList(){
//        global $_W,$_GPC;
//        //参数获取
//        $cateid = $_GPC['cateid'];
//        $storeid = $_GPC['storeid'];
//        $page      = $_GPC['page'] ? : 1;
//        $pageStart = $page * 10 - 10;
//        $name      = trim($_GPC['name']);
//        //筛选条件
//        $where = " WHERE uniacid = {$_W['uniacid']} AND status IN (2,7) AND cateid = {$cateid} AND sid = {$storeid}";
//        if(!empty($name)){
//            $where .= " AND name LIKE '%{$name}%'";
//        }
//        //排序条件
//        $order = " ORDER BY sort ASC";
//        //页数获取
//        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."delivery_activity") .$where);
//        $data['total'] = ceil($total / 10);
//        //列表获取
//        $data['list'] = pdo_fetchall("SELECT id,name,price,oldprice,status,thumb,vipstatus,vipdiscount,optionstatus FROM ".tablename(PDO_NAME."delivery_activity") .$where.$order." LIMIT {$pageStart},10");
//        if(!empty($data['list'])){
//            foreach ($data['list'] as &$good){
//                $good['thumb'] = tomedia($good['thumb']);
//                if($good['optionstatus']){
//                    $good['optionarray'] = pdo_fetchall("SELECT id,name,price,oldprice FROM ".tablename('wlmerchant_delivery_spec')."WHERE goodsid = {$good['id']} ORDER BY sort DESC");
//                }else{
//                    $good['optionarray'] = [];
//                }
//                //查询购物车中已有数量
//                $good['num'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('goodid' => $good['id']),array("SUM(num)"));
//            }
//        }
//        $this->renderSuccess('商品列表',$data);
//    }

    /**
     * Comment: 修改购物车商品数量
     * Author: wlf
     * Date: 2022/04/27 14:58
     */
    function changeShopCart(){
        global $_W,$_GPC;
        //初始化判断
        $goodid = $_GPC['goodid'];
        $specid = $_GPC['specid'] ? $_GPC['specid'] : 0;
        $num = $_GPC['num'];
        $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
        //查询购物车中是否已有商品
        $goodinfo = Citydelivery::getGoodprice($goodid,$specid,$halfflag);
        if($goodinfo['status'] != 2){
            $this->renderError('商品未在销售中，无法下单');
        }
        if($goodinfo['vipstatus'] == 2 && empty($halfflag)){
            $this->renderError('此商品会员特供，请先开通会员');
        }
        if($goodinfo['optionstatus'] > 0 && empty($specid)){
            $this->renderError('多规格商品请选择规格');
        }
        //判断商户状态
        $hour = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goodinfo['sid']),'storehours');
        $hour = unserialize($hour);
        $is_business = Store::getShopBusinessStatus($hour,$goodinfo['sid']);
        if(empty($is_business)){
            $this->renderError('商户已打烊');
        }
        $cartgood = pdo_get('wlmerchant_delivery_shopcart',array('goodid' => $goodid,'specid'=>$specid,'mid'=>$_W['mid']),array('id','num'));
        if(!empty($cartgood)) {
            if($num > 0){
                //判断库存
                if($goodinfo['allstock'] > 0){
                    $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0),array("SUM(num)"));
                    if($allsalenum + $num > $goodinfo['allstock']){
                        $this->renderError('下单数量超出库存，无法继续购买');
                    }
                }
                if($goodinfo['daystock'] > 0){
                    $nowtime = strtotime(date('Y-m-d',time()));
                    $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0,'createtime' => $nowtime),array("SUM(num)"));
                    if($daysalenum + $num > $goodinfo['daystock']){
                        $this->renderError('下单数量超出今日库存，无法继续购买');
                    }
                }
                $res = pdo_update('wlmerchant_delivery_shopcart',array('num' => $num),array('id' => $cartgood['id']));
            }else{
                $res = pdo_delete('wlmerchant_delivery_shopcart',array('id'=>$cartgood['id']));
            }
        }
        if($res){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }



    /**
     * Comment: 外卖商品添加进购物车
     * Author: wlf
     * Date: 2020/04/01 09:14
     */
    function addShopCart(){
        global $_W,$_GPC;
        //初始化判断
        $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
        if(empty($_W['mid'])){
            $this->renderError('请先登录');
        }
        //参数获取
        $goodid = $_GPC['goodid'];
        $specid = $_GPC['specid'] ? $_GPC['specid'] : 0;
        $addtype = $_GPC['addtype'];  //类型 1增加 0减少
        $num = 1;  //默认数量为1
        //查询购物车中是否已有商品
        $goodinfo = Citydelivery::getGoodprice($goodid,$specid,$halfflag);
        if($goodinfo['status'] != 2){
            $this->renderError('商品未在销售中，无法下单');
        }
        if($goodinfo['vipstatus'] == 2 && empty($halfflag)){
            $this->renderError('此商品会员特供，请先开通会员');
        }
        if($goodinfo['optionstatus'] > 0 && empty($specid)){
            $this->renderError('多规格商品请选择规格');
        }
        //判断商户状态
        $hour = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goodinfo['sid']),'storehours');
        $hour = unserialize($hour);
        $is_business = Store::getShopBusinessStatus($hour,$goodinfo['sid']);
        if(empty($is_business)){
            $this->renderError('商户已打烊');
        }
        $cartgood = pdo_get('wlmerchant_delivery_shopcart',array('goodid' => $goodid,'specid'=>$specid,'mid'=>$_W['mid']),array('id','num'));
        if(!empty($cartgood)){
            if($addtype){  //添加
                $newnum = $cartgood['num'] + $num;
                //判断库存
                if($goodinfo['allstock'] > 0){
                    $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0),array("SUM(num)"));
                    if($allsalenum + $newnum > $goodinfo['allstock']){
                        $this->renderError('下单数量超出库存，无法继续购买');
                    }
                }
                if($goodinfo['daystock'] > 0){
                    $nowtime = strtotime(date('Y-m-d',time()));
                    $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0,'createtime' => $nowtime),array("SUM(num)"));
                    if($daysalenum + $newnum > $goodinfo['daystock']){
                        $this->renderError('下单数量超出今日库存，无法继续购买');
                    }
                }
                $res = pdo_update('wlmerchant_delivery_shopcart',array('num' => $newnum),array('id' => $cartgood['id']));
            }else{   //减少
                if($cartgood['num'] > $num){
                    $newnum = $cartgood['num'] - $num;
                    $res = pdo_update('wlmerchant_delivery_shopcart',array('num' => $newnum),array('id' => $cartgood['id']));
                }else{
                    $res = pdo_delete('wlmerchant_delivery_shopcart',array('id'=>$cartgood['id']));
                }
            }
        }else if($addtype){  //添加进入
            //判断库存
            if($goodinfo['allstock'] > 0){
                $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0),array("SUM(num)"));
                if($allsalenum + 1 > $goodinfo['allstock']){
                    $this->renderError('超出库存，无法加入购物车');
                }
            }
            if($goodinfo['daystock'] > 0){
                $nowtime = strtotime(date('Y-m-d',time()));
                $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $goodid,'specid' => $specid,'status >' => 0,'createtime' => $nowtime),array("SUM(num)"));
                if($daysalenum + 1 > $goodinfo['daystock']){
                    $this->renderError('超出库存，无法加入购物车');
                }
            }
            $info = array(
                'uniacid' => $_W['uniacid'],
                'aid'     => $goodinfo['aid'],
                'sid'     => $goodinfo['sid'],
                'mid'     => $_W['mid'],
                'goodid'  => $goodid,
                'num'     => $num,
                'specid'  => $specid,
                'cateid'  => $goodinfo['cateid'],
                'createtime' => time()
            );
            $res = pdo_insert(PDO_NAME . 'delivery_shopcart', $info);
        }
        if($res){
            if(empty($addtype)){
                $data = array(
                    'changemoney' => sprintf("%.2f",(0 - $goodinfo['price'])*$num),
                    'changenum'   => 0 - $num
                );
            }else{
                $data = array(
                    'changemoney' => sprintf("%.2f",$goodinfo['price']*$num),
                    'changenum'   => $num
                );
            }
            $this->renderSuccess('操作成功',$data);
        }else{
            $this->renderError('操作失败，请刷新重试');
        }


    }

    /**
     * Comment: 清空外卖购物车
     * Author: wlf
     * Date: 2020/04/02 14:12
     */
    function deteShopCart(){
        global $_W,$_GPC;
        //初始化判断
        if(empty($_W['mid'])){
            $this->renderError('请先登录');
        }
        //条件判断
        $where = array(
            'uniacid' => $_W['uniacid'],
            'aid'     => $_W['aid']
        );
        if($_GPC['sid']){
            $where['sid'] = $_GPC['sid'];
        }
        $res = pdo_delete('wlmerchant_delivery_shopcart',$where);
        if($res){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('购物车已经空了');
        }
    }

    /**
     * Comment: 获取购物车页面数据
     * Author: wlf
     * Date: 2020/04/02 14:31
     */
    function cartinfo(){
        global $_W,$_GPC;
        //初始化判断
        if(empty($_W['mid'])){
            $this->renderError('请先登录');
        }
        //先查询商户
        $storelist = pdo_fetchall('select distinct sid from ' . tablename(PDO_NAME.'delivery_shopcart')." WHERE mid = {$_W['mid']}");
        if(!empty($storelist)){
            $allmoney = $alldeliverymoney = 0;
            foreach ($storelist as $key => &$store){
                $storeinfo = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('bzftext','storehours','storename','deliverymoney'));
                $store['storename'] = $storeinfo['storename'];
                $store['bzftext'] = $storeinfo['bzftext'] ? : '包装费';
                $hour = unserialize($storeinfo['storehours']);
                $store['is_business'] = Store::getShopBusinessStatus($hour,$store['sid']);
                $store['cartinfo'] = Citydelivery::getCartInfo($_W['mid'],$store['sid']);
                $store['cartinfo']['deliveryprice'] = $store['cartinfo']['deliveryprice'];
                $store['cartinfo']['totalmoney'] = $store['cartinfo']['totalmoney'];
                $allmoney += $store['cartinfo']['totalmoney'];
                $alldeliverymoney += $store['cartinfo']['deliveryprice'];
//                if(empty($store['is_business'])){
//                    unset($storelist[$key]);
//                    continue;
//                }
            }
            $data['list'] = $storelist;
            $data['allmoney'] = sprintf("%.2f",$allmoney);
            $data['alldeliverymoney'] = sprintf("%.2f",$alldeliverymoney);
        }else{
            $data = ['allmoney'=>0,'alldeliverymoney'=>0,'list' => []];
        }
        $this->renderSuccess('购物车数据',$data);
    }

    /**
     * Comment: 获取商品详情页面数据
     * Author: wlf
     * Date: 2020/04/10 11:19
     */
    function goodsDetail(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $id));
        if(empty($goods)){
            $this->renderError('商品错误，请返回重试');
        }
        $data = [];
        $data['goodsname'] = $goods['name'];
        $data['price'] = $goods['price'];
        $data['oldprice'] = $goods['oldprice'];
        $data['vipstatus'] = $goods['vipstatus'];
        $data['vipdiscount'] = $goods['vipdiscount'];
        $data['sid'] = $goods['sid'];
        $data['pv'] = $goods['pv'];
        $data['detail'] = htmlspecialchars_decode($goods['detail']);
        $data['deliveryprice'] = $goods['deliveryprice'];
        $data['creditmoney'] = $goods['creditmoney'];
        //规格
        $data['optionstatus'] = $goods['optionstatus'];
        if($data['optionstatus']>0){
            $data['specarray'] = pdo_getall('wlmerchant_delivery_spec',array('goodsid' => $id),array('id','allstock','daystock','name','price','oldprice'));
            foreach ($data['specarray'] as $key => &$spe){
                $spe['cartNum'] = pdo_getcolumn(PDO_NAME.'delivery_shopcart',array('goodid' => $id,'specid'=>$spe['id'],'mid'=>$_W['mid']),'num');
                $spe['cartNum'] = $spe['cartNum'] ? intval($spe['cartNum']) : 0;
                //判断库存
                if($spe['allstock'] > 0){
                    $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goods['sid'],'gid' => $id,'specid' => $spe['id'],'status >' => 0),array("SUM(num)"));
                    if($allsalenum > $spe['allstock'] || $allsalenum == $spe['allstock']){
                        unset($data['specarray'][$key]);
                    }
                }
                if($spe['daystock'] > 0){
                    $nowtime = strtotime(date('Y-m-d',time()));
                    $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goods['sid'],'gid' => $id,'specid' => $spe['id'],'status >' => 0,'createtime >' => $nowtime),array("SUM(num)"));
                    if($daysalenum > $spe['daystock'] || $daysalenum == $spe['daystock']){
                        unset($data['specarray'][$key]);
                    }
                }
            }
            $data['specarray'] = array_values($data['specarray']);
            if(empty($data['specarray'])){
                $data['saleoverflag'] = 1;
            }
        }else{
            $data['specarray'] = [];
            //判断库存
            if($goods['allstock'] > 0){
                $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goods['sid'],'gid' => $id,'status >' => 0),array("SUM(num)"));
                if($allsalenum > $goods['allstock'] || $allsalenum == $goods['allstock']){
                    $data['saleoverflag'] = 1;
                }
            }
            if($goods['daystock'] > 0 && empty($goods['saleoverflag'])){
                $nowtime = strtotime(date('Y-m-d',time()));
                $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goods['sid'],'gid' => $id,'status >' => 0,'createtime >' => $nowtime),array("SUM(num)"));
                if($daysalenum > $goods['daystock'] || $daysalenum == $goods['daystock']){
                    $data['saleoverflag'] = 1;
                }
            }
        }
        //幻灯片
        $data['thumbs'] = unserialize($goods['thumbs']);
        if(!empty($data['thumbs'])){
            foreach ($data['thumbs'] as &$thu){
                $thu = tomedia($thu);
            }
        }else{
            $data['thumbs'][] = tomedia($data['thumb']);
        }
        //查询购物车中已有数量
        $data['num'] = pdo_getcolumn('wlmerchant_delivery_shopcart',array('goodid' => $id,'mid'=>$_W['mid']),array("SUM(num)"));
        $data['num'] = $data['num'] ? $data['num'] : 0;
        //查询已售数量
        $data['salenum'] = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goods['sid'],'gid' => $id,'status >' => 0),array("SUM(num)"));
        $data['salenum'] = $data['salenum'] ? $data['salenum'] : 0;
        $data['salenum'] = $data['salenum'] + $goods['fictitiousnum'];
        //商户状态
        $store = pdo_get(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),['storehours','bzftext']);
        $hour = $store['storehours'];
        $hour = unserialize($hour);
        $data['is_business'] = Store::getShopBusinessStatus($hour,$goods['sid']);

        $data['bzftext'] = !empty($store['bzftext']) ? $store['bzftext'] : '包装费';

        $this->renderSuccess('商品详情页面',$data);
    }

    /**
     * Comment: 获取订单详情数据
     * Author: wlf
     * Date: 2020/04/22 15:03
     */
    function orderDetail(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $lat        = $_GPC['lat'];
        $lng        = $_GPC['lng'];
        $idstatus = $_GPC['idstatus'];  // 1 paylog订单  2 order订单
        $data       = [];
        if($idstatus == 1){
            $orders = pdo_getall('wlmerchant_order',array('paylogid' => $id),array('sid','id','num','packingmoney','fullreducemoney','orderno','fightstatus','makeorderno','vipdiscount','createtime','buyremark','status','expressprcie','goodsprice','price','cerditmoney','redpackmoney','fishimg'));
            if(empty($orders)){
                $tid = pdo_getcolumn(PDO_NAME.'paylogvfour',array('plid'=>$id),'tid');
                $orders = pdo_getall('wlmerchant_order',array('orderno' => $tid),array('sid','id','packingmoney','fullreducemoney','num','fightstatus','makeorderno','orderno','vipdiscount','createtime','buyremark','status','expressprcie','goodsprice','price','cerditmoney','redpackmoney','fishimg'));
            }
        }else{
            $orders = pdo_getall('wlmerchant_order',array('id' => $id),array('sid','id','num','packingmoney','fullreducemoney','orderno','fightstatus','makeorderno','vipdiscount','createtime','buyremark','status','expressprcie','goodsprice','price','cerditmoney','redpackmoney','fishimg'));
        }
        if(empty($orders)){
            $this->renderError('订单错误,请返回重试');
        }else{
            foreach ($orders as $or){
                //获取商户信息
                $storearray = [];
                if($or['status'] == 0){
                    $storearray['statustext'] = '待支付';
                    $storearray['order_rider'] = [];
                }else if($or['status'] == 5){
                    $storearray['statustext'] = '已取消';
                    $storearray['order_rider'] = [];
                }else if($or['status'] == 8){
                    $storearray['statustext'] = '待接单';
                    $storearray['order_rider'] = [];
                    $storearray['tiptext'] = '订单已推送商户，等待商户接单';
                }else{
                    //码科跑腿订单
                    if($or['fightstatus'] == 2){
                        $makeorder = Citydelivery::getMakeOrderDetail($or['makeorderno']);
                        $storearray['order_rider'] = $makeorder['order_rider'];
                        if(empty($storearray['order_rider'])){
                            $storearray['order_rider'] = [];
                            $storearray['statustext'] = '待接单';
                            $storearray['tiptext'] = '商家制作中，等待骑手接单';
                        }else{
                            $storearray['order_rider']['real_name'] = mb_substr($storearray['order_rider']['real_name'],0,1,'utf-8').'**';
                        }
                        switch ($makeorder['status']){
                            case 'cancel':
                                $storearray['statustext'] = '订单已取消';
                                $storearray['tiptext'] = '买家取消订单，订单关闭';
                                break;
                            case 'payed':
                                $storearray['statustext'] = '待接单';
                                $storearray['tiptext'] = '商家制作中，等待骑手接单';
                                break;
                            case 'accepted':
                                $storearray['statustext'] = '待取件';
                                $storearray['tiptext'] = '骑手已接单，等待骑手前往商家取件';
                                break;
                            case 'geted':
                                $storearray['statustext'] = '已取件';
                                $storearray['tiptext'] = '骑手已取件，请等待骑手送达';
                                break;
                            case 'gotoed':
                                $storearray['statustext'] = '已送达';
                                $storearray['tiptext'] = '骑手已送达，请注意接收';
                                break;
                            case 'completed':
                                $storearray['statustext'] = '已完成';
                                $storearray['tiptext'] = '买家已接收，订单完成';
                                break;
                        }
                    }else if($or['fightstatus'] == 3){
                        $body = ['order_id' => $or['orderno']];
                        $dadaInfo = Citydelivery::postDadaApi($body,3);
                        $dadaInfo = $dadaInfo['result'];
                        $storearray['statustext'] = $dadaInfo['statusMsg'];
                        switch ($dadaInfo['statusCode']){
                            case '1':
                                $storearray['tiptext'] = '商家制作中，等待骑手接单';
                                break;
                            case '2':
                                $storearray['tiptext'] = '骑手已接单，等待骑手前往商家取件';
                                break;
                            case '3':
                                $storearray['tiptext'] = '骑手已取件，请等待骑手送达';
                                break;
                            case '4':
                                $storearray['tiptext'] = '骑手已送达，请注意接收';
                                break;
                            case '5':
                                $storearray['tiptext'] = '配送订单已取消';
                                break;
                            case '9':
                                $storearray['tiptext'] = '配送异常，返回中';
                                break;
                            case '10':
                                $storearray['tiptext'] = '配送异常，已返回';
                                break;
                            case '100':
                                $storearray['tiptext'] = '骑手已到店，正等待取件';
                                break;
                            default:
                                $storearray['tiptext'] = '配送状态异常，请联系管理员';
                                break;
                        }
                        //order_rider内容构建
                        $storearray['order_rider']['accept_time'] = $dadaInfo['acceptTime'];
                        $storearray['order_rider']['get_time'] = $dadaInfo['fetchTime'];
                        $storearray['order_rider']['goto_time'] = $dadaInfo['finishTime'];
                        $storearray['order_rider']['real_name'] = $dadaInfo['transporterName'];
                        $storearray['order_rider']['mobile'] = $dadaInfo['transporterPhone'];
                        $storearray['order_rider']['latitude'] = $dadaInfo['transporterLat'];
                        $storearray['order_rider']['longitude'] = $dadaInfo['transporterLng'];
                    }else if($or['fightstatus'] == 4){
                        //UU跑腿订单
                        $body = ['origin_id' => $or['orderno']];
                        $UUInfo = Citydelivery::postUUApi($body,4);
                        $storearray['statustext'] = $UUInfo['note'];
                        switch ($UUInfo['state']){
                            case '1':
                                $storearray['tiptext'] = '商家制作中，等待骑手接单';
                                break;
                            case '3':
                                $storearray['tiptext'] = '骑手已接单，等待骑手前往商家取件';
                                break;
                            case '5':
                                $storearray['tiptext'] = '骑手已取件，请等待骑手送达';
                                break;
                            case '4':
                                $storearray['tiptext'] = '骑手已送达，请注意接收';
                                break;
                            case '-1':
                                $storearray['tiptext'] = '配送订单已取消';
                                break;
                            case '10':
                                $storearray['tiptext'] = '收件人已收货,配送完成';
                                break;
                            default:
                                $storearray['tiptext'] = '通信异常，请联系管理员';
                                break;
                        }
                        //order_rider内容构建
                        $driver_lastloc = explode(",",$UUInfo['driver_lastloc']);
                        $storearray['order_rider']['accept_time'] = $UUInfo['add_time'];
                        $storearray['order_rider']['get_time'] = $UUInfo['finish_time'];
                        $storearray['order_rider']['goto_time'] = $UUInfo['expectedarrive_time'];
                        $storearray['order_rider']['real_name'] = $UUInfo['driver_name'];
                        $storearray['order_rider']['mobile'] = $UUInfo['driver_mobile'];
                        $storearray['order_rider']['latitude'] = $driver_lastloc[1];
                        $storearray['order_rider']['longitude'] = $driver_lastloc[0];
                    }else{
                        if($or['status'] == 1){
                            $storearray['statustext'] = '待自提';
                        }else if($or['status'] == 4){
                            $storearray['statustext'] = '商户配送中';
                        }else if($or['status'] == 7){
                            $storearray['statustext'] = '已退款';
                        }
                        $storearray['order_rider'] = [];
                    }
                }
                $storearray['orderno'] = $or['orderno'];
                $storearray['createtime'] = date("Y-m-d H:i:s",$or['createtime']);
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $or['sid']),array('bzftext','lng','lat','storename','mobile','address'));
                $storearray['distance'] = Store::getdistance($store['lng'],$store['lat'],$lng,$lat,true);
                $storearray['storename'] = $store['storename'];
                $storearray['bzftext'] = $store['bzftext'] ? : '包装费';
                $storearray['mobile'] = $store['mobile'];
                $storearray['address'] = $store['address'];
                $storearray['buyremark'] = $or['buyremark'];
                $storearray['status'] = $or['status'];
                $storearray['id'] = $or['id'];
                $goods = pdo_getall('wlmerchant_delivery_order',array('tid' => $or['orderno']),array('gid','specid','num'));
                foreach ($goods as $go){
                    $goodarray = [];
                    $good = pdo_get('wlmerchant_delivery_activity',array('id' => $go['gid']),array('name','price','thumb'));
                    $goodarray['name'] = $good['name'];
                    $goodarray['price'] = $good['price'];
                    $goodarray['num'] = $go['num'];
                    $goodarray['thumb'] = tomedia($good['thumb']);
                    if($go['specid']){
                        $spec = pdo_get('wlmerchant_delivery_spec',array('id' => $go['specid']),array('name','price'));
                        $goodarray['price'] = $spec['price'];
                        $goodarray['specname'] = $spec['name'];
                    }
                    $storearray['num'] += $go['num'];
                    $storearray['goodlist'][] = $goodarray;
                }
                $storearray['allmoney'] = $or['price'];
                $storearray['allgoodsmoney'] = $or['goodsprice'];
                $storearray['allvipdiscount'] = $or['vipdiscount'];
                $storearray['allexpressprcie'] = $or['expressprcie'];
                $storearray['allfulldiscount'] = $or['fullreducemoney'];
                $storearray['packingmoney'] = $or['packingmoney'];
                $storearray['cerditmoney'] = $or['cerditmoney'];
                $storearray['redpackmoney'] = $or['redpackmoney'];
                //查询退款
                if($or['status'] != 7){
                    $canre = pdo_getcolumn(PDO_NAME . 'aftersale', array('orderid' => $or['id'], 'plugin' => 'citydelivery', 'status' => array(1,2)), 'id');
                    if($canre){
                        $storearray['status'] = 10;
                        $storearray['statustext'] = '申请退款中';
                        $storearray['afterid'] = $canre;
                    }
                }
                //完成图
                if(!empty($or['fishimg'])){
                    $storearray['fishimg'] = tomedia($or['fishimg']);
                }
                $data['storelist'][] = $storearray;


            }
            $this->renderSuccess('订单详情',$data);
        }
    }

    /**
     * Comment: 完成订单
     * Author: wlf
     * Date: 2020/04/22 14:09
     */
    function finishOrder(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $orderno = $_GPC['orderno'];
        $fishImg = $_GPC['fishimg'];
        $type = $_GPC['type'];  //1用户确认完成 2商户确认完成 3后台确认完成 4码科回调 5哒哒回调 6UU回调
        //码科配送
        if(!empty($id)){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('paytype','id','orderno','expressid','mid','aid','disorderid','sid','price'));
        }else{
            $order = pdo_get('wlmerchant_order',array('orderno' => $orderno),array('paytype','id','orderno','expressid','mid','aid','disorderid','sid','price'));
            $id = $order['id'];
        }
        $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE tid = {$order['orderno']} ORDER BY price DESC");
        $cityremark = '';
        foreach ($smallorders  as $ke => &$orr){
            $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
            $orr['name'] = $goods['name'];
            if($orr['specid']>0){
                $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                $orr['name'] .= '/'.$specname;
            }
            $cityremark .= '['.$orr['name'].'X'.$orr['num'].']';
        }
        if($type == 6){
            $UUdata = str_replace('&quot;','"',$_GPC['data']);
            $UUdata = json_decode($UUdata,true);
        }
        if($type == 4 || $type == 5 || $type == 6){
            if($_GPC['__input']['status'] == 'gotoed' || $_GPC['__input']['order_status'] == 4 || $UUdata['state'] == 4 ){
                $res = pdo_update('wlmerchant_order',array('status' => 2,'deliverytype' => $type),array('id' => $id));
                //发送模板消息
                $first = '您好,您的订单['.$order['orderno'].']已经送达';
                $newtype = '配送订单已送达';
                $content = $cityremark;
                $status = '已送达';
                $remark = '如有疑问请联系平台客服';
                $url = h5_url('pages/subPages/orderList/orderTakeout/orderTakeout',['orderid'=>$id,'aid'=>$order['aid'],'plugin'=>'citydelivery']);
                News::jobNotice($order['mid'],$first,$newtype,$content,$status,$remark,time(),$url);
            }else if($_GPC['__input']['status'] == 'accepted' || $_GPC['__input']['order_status'] == 2 || $UUdata['state'] == 3){
                $first = '您好,您的订单['.$order['orderno'].']已有骑手接单';
                $newtype = '骑手已接单';
                $content = $cityremark;
                $status = '已接单';
                $remark = '如有疑问请联系平台客服';
                $url = h5_url('pages/subPages/orderList/orderTakeout/orderTakeout',['orderid'=>$id,'aid'=>$order['aid'],'plugin'=>'citydelivery']);
                News::jobNotice($order['mid'],$first,$newtype,$content,$status,$remark,time(),$url);
                die('success');
            }else if($_GPC['__input']['status'] == 'geted' || $_GPC['__input']['order_status'] == 3 || $UUdata['state'] == 5){
                $first = '您好,您的订单['.$order['orderno'].']骑手已取件';
                $newtype = '骑手已取件';
                $content = $cityremark;
                $status = '已取件';
                $remark = '如有疑问请联系平台客服';
                $url = h5_url('pages/subPages/orderList/orderTakeout/orderTakeout',['orderid'=>$id,'aid'=>$order['aid'],'plugin'=>'citydelivery']);
                News::jobNotice($order['mid'],$first,$newtype,$content,$status,$remark,time(),$url);
                die('success');
            }
        }else{
            $res = pdo_update('wlmerchant_order',array('status' => 2,'deliverytype' => $type,'fishimg' => $fishImg),array('id' => $id));
        }
        if($res){
            pdo_update('wlmerchant_delivery_order',array('status' => 2,'dotime' => time()),array('tid' => $order['orderno']));
            $setres = Store::ordersettlement($id);
            if($order['expressid']){
                pdo_update('wlmerchant_express',array('receivetime' => time()),array('id' => $order['expressid']));
            }
            if($order['disorderid']){
                pdo_update('wlmerchant_disorder',array('status' => 1),array('id' => $order['disorderid'],'status' => 0));
            }
            Order::yueCityCashBack($order['mid'],$order['sid'],$order['price']);
            //抽奖码getDrawCode
            $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('city_drawid','city_draw_money','deliverypaidid'));
            if($storeinfo['city_drawid'] > 0 && $order['price'] > $storeinfo['city_draw_money'] ){
                Luckydraw::getDrawCode($storeinfo['city_drawid'],$order['mid'],$order['id'],'citydelivery');
            }
            //支付有礼
            if($storeinfo['deliverypaidid'] > 0){
                $paidprid = Paidpromotion::getpaidpr(8,$storeinfo['deliverypaidid'],$order['mid'],$order['id'],$order['paytype'],$order['price'],1,1);
                if($paidprid > 0 ){
                    pdo_update('wlmerchant_order' , ['paidprid' => $paidprid] , ['id' => $order['id']]);
                }
            }
            if($type == 4){
                die('success');
            }
            $this->renderSuccess('订单完成');
        }else{
            $this->renderError('修改状态失败，请刷新重试');
        }
    }

    /**
     * Comment: 计算当前购物车中选中商品金额
     * Author: wlf
     * Date: 2020/04/27 16:32
     */
    function CalculationPrice(){
        global $_W,$_GPC;
        $goodsinfo = json_decode(base64_decode($_GPC['goodsinfo']),true);
        $goodallmoney = $deliveryallmoney = $alldiscount = 0;
        foreach ($goodsinfo as $store){
            $storearray = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('storename','deliverymoney'));
            $storearray['deliverymoney'] = 0; //临时兼容码科
            $storearray['allmoney'] = 0;
            $storearray['vipdiscount'] = 0;
            $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
            foreach ($store['cartid'] as $good){
                $good = pdo_get('wlmerchant_delivery_shopcart',array('id' => $good),array('goodid','num','specid'));
                $goodinfo = Citydelivery::getGoodprice($good['goodid'],$good['specid'],$halfflag);
                $goodallmoney += $goodinfo['originalPrice'] * $good['num'];
                $deliveryallmoney += $goodinfo['deliveryprice'] * $good['num'];
                $alldiscount += $goodinfo['vipdiscount'] * $good['num'];
            }
            $deliveryallmoney += $storearray['deliverymoney'];
        }
        $data['allmoney'] = sprintf("%.2f",$goodallmoney + $deliveryallmoney - $alldiscount);
        $data['goodallmoney'] = sprintf("%.2f",$goodallmoney);
        $data['deliveryallmoney'] = sprintf("%.2f",$deliveryallmoney);
        $data['alldiscount'] = sprintf("%.2f",$alldiscount);
        $this->renderSuccess('计算结果',$data);
    }

    /**
     * Comment: 获取商户分类信息
     * Author: wlf
     * Date: 2020/04/30 13:54
     */
    function storeCateList(){
        global $_W,$_GPC;
        $storeid = trim($_GPC['storeid']);
        if(empty($storeid)){
            $this->renderError('无店铺信息，请刷新重试');
        }
        $where['sid'] = $storeid;
        $list = pdo_fetchall("SELECT id,status,name,sort FROM ".tablename('wlmerchant_delivery_category')."WHERE uniacid = {$_W['uniacid']} AND  sid = {$storeid} ORDER BY sort DESC,id DESC");
        foreach ($list as &$li){
            $li['goodsnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_delivery_activity')." WHERE cateid = {$li['id']}");
        }
        $data['list'] = $list;
        $this->renderSuccess('分类列表',$data);
    }

    /**
     * Comment: 商户新建配送商品分类
     * Author: wlf
     * Date: 2020/04/30 14:38
     */
    function addCate(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $sid = $_GPC['storeid'];
        if(empty($sid)){
            $this->renderError('无店铺信息，请刷新重试');
        }
        $name = $_GPC['name'];
        if(empty($name)){
            $this->renderError('请输入分类名');
        }
        $sort = $_GPC['sort']?intval($_GPC['sort']):0;
        $status = $_GPC['status']?intval($_GPC['status']):0;
        $newdata = [
            'sid'   => $sid,
            'name'  => $name,
            'sort'  => $sort,
            'status'=> $status
        ];
        if($id > 0){
            //修改操作
            $res = pdo_update('wlmerchant_delivery_category',$newdata,['id'=>$id]);
        }else{
            //添加操作
            $newdata['aid'] = $_W['aid'];
            $newdata['uniacid'] = $_W['uniacid'];
            $newdata['status'] = 1;
            $res = pdo_insert('wlmerchant_delivery_category',$newdata);
        }
        if($res){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 删除配送分类
     * Author: wlf
     * Date: 2020/04/30 16:00
     */
    function deleteCate(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id)){
            $this->renderError('无分类信息，请刷新重试');
        }
        pdo_delete('wlmerchant_delivery_category',array('id'=>$id));
        $this->renderSuccess('删除成功');
    }

    /**
     * Comment: 商品列表
     * Author: wlf
     * Date: 2020/05/09 11:01
     */
    function deliveGoodsList(){
        global $_W,$_GPC;
        $storeid = $_GPC['storeid'];  //店铺id
        $cateid = $_GPC['cateid'];  //分类id
        $name = trim($_GPC['name']);  //需要搜索的商品名
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $psize     = $_GPC['psize'] ? $_GPC['psize']:10; //每页数量
        $pageStart = $page * $psize - $psize;
        $status = trim($_GPC['status']);
        if(empty($storeid)){
            $this->renderError('无店铺信息，请刷新重试');
        }
        $where = " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid}";
        if($cateid > 0){
            $where .= "  AND cateid = {$cateid}";
        }
        if(!empty($name)){
            $where .= "  AND name LIKE '%{$name}%'";
        }
        if(!empty($status)){
            if($status == 5){
                $where .= "  AND status IN (5,6)";
            }else{
                $where .= "  AND status = {$status}";
            }
        }
        $list = pdo_fetchall("SELECT id,status,name,sort,thumb FROM ".tablename('wlmerchant_delivery_activity').$where." ORDER BY sort DESC,id DESC LIMIT {$pageStart},{$psize}");
        foreach ($list as &$li){
            $li['salenum'] = pdo_getcolumn('wlmerchant_delivery_order',array('gid' => $li['id'],'status >' => 0),array("SUM(num)"));
            $li['thumb'] = tomedia($li['thumb']);
        }
        $data['list'] = $list;
        $total = count(pdo_fetchall("SELECT id,status,name,sort,thumb FROM ".tablename('wlmerchant_delivery_activity').$where));
        $data['total'] = ceil($total / 10);
        //获取分类项目
        if($_GPC['initialization']){
            $data['catelist'] = pdo_fetchall("SELECT id,name FROM ".tablename('wlmerchant_delivery_category')."WHERE uniacid = {$_W['uniacid']} AND  sid = {$storeid} ORDER BY sort DESC,id DESC");
        }
        $this->renderSuccess('配送商品列表',$data);
    }

    /**
     * Comment: 添加商品页面接口
     * Author: wlf
     * Date: 2020/05/09 14:43
     */
    function createGoodsPage(){
        global $_W,$_GPC;
        $storeid = $_GPC['storeid'];  //店铺id
        if(empty($storeid)){
            $this->renderError('无店铺信息，请刷新重试');
        }
        $id = $_GPC['id'];  //商品id
        //分类信息
        $data['catelist'] = pdo_fetchall("SELECT id,name FROM ".tablename('wlmerchant_delivery_category')."WHERE uniacid = {$_W['uniacid']} AND  sid = {$storeid} AND status = 1 ORDER BY sort DESC,id DESC");
        if($id > 0){
            $godos = pdo_get('wlmerchant_delivery_activity',array('id' => $id),array('id','name','detail','thumb','cateid','thumbs','price','oldprice','optionstatus','deliveryprice','status'));
            if($godos['optionstatus']){
                $godos['optionArray'] = pdo_getall('wlmerchant_delivery_spec',array('uniacid' => $_W['uniacid'],'goodsid' => $id),array('id','allstock','daystock','name','price','oldprice'));
            }
            $godos['thumb'] = tomedia($godos['thumb']);
            $godos['thumbs'] = unserialize($godos['thumbs']);
            if(is_array($godos['thumbs'])){
                foreach ($godos['thumbs'] as $k => &$th){
                    if(!empty($th)){
                        $th = tomedia($th);
                    }else{
                        unset($godos['thumbs'][$k]);
                    }
                }
            }else{
                $godos['thumbs'] = [];
            }
        }else{
            $godos = [];
        }
        $data['goods'] = $godos;
        $this->renderSuccess('创建商品初始化数据',$data);
    }

    /**
     * Comment: 添加或保存商品
     * Author: wlf
     * Date: 2020/05/09 18:12
     */
    function saveDeliveryGoods(){
        global $_W,$_GPC;
        $storeid = $_GPC['storeid'];  //店铺id
        $optionArray = json_decode(base64_decode($_GPC['optionArray']),true);
        if(empty($storeid)){
            $this->renderError('无店铺信息，请刷新重试');
        }
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('aid','storename','audits'));
        $id = $_GPC['id'];  //商品id
        $thumbs = explode(',' , trim($_GPC['thumbs']));
        if(is_array($thumbs)){
            $thumbs = serialize($thumbs);
        }else{
            $thumbs = '';
        }
        $gooddata = [
            'name'      => $_GPC['name'],
            'thumb'     => $_GPC['thumb'],
            'cateid'    => trim($_GPC['cateid']),
            'thumbs'    => $thumbs,
            'price'     => trim($_GPC['price']),
            'oldprice'  => trim($_GPC['oldprice']),
            'deliveryprice'  => trim($_GPC['deliveryprice']),
            'detail'    => htmlspecialchars_decode($_GPC['detail']),
            'optionstatus' => trim($_GPC['optionstatus'])
        ];
        $gooddata['status'] = $_GPC['status'];
        if($gooddata['status'] == 2 && empty($store['audits'])){
            $gooddata['status'] = 5;
        }
        if(empty($id)){
            $gooddata['createtime'] = time();
            $gooddata['uniacid'] = $_W['uniacid'];
            $gooddata['aid'] = $store['aid'];
            $gooddata['sid'] = $storeid;
            $res = pdo_insert(PDO_NAME.'delivery_activity',$gooddata);
            if($res){
                $id = pdo_insertid();
            }
        }else{
            $res = pdo_update(PDO_NAME.'delivery_activity',$gooddata,array('id' => $id));
        }
        if(empty($id)){
            $this->renderError('保存失败,请重试');
        }
        if($gooddata['optionstatus']>0){
            $specids = [];
            foreach ($optionArray as $option){
                if($option['id']>0){
                    $specids[] = $specid = $option['id'];
                    unset($option['id']);
                    pdo_update('wlmerchant_delivery_spec',$option,array('id' => $specid));
                }else{
                    unset($option['id']);
                    $option['uniacid'] = $_W['uniacid'];
                    $option['goodsid'] = $id;
                    pdo_insert(PDO_NAME . 'delivery_spec',$option);
                    $specids[] = pdo_insertid();
                }
            }
            pdo_query('delete from ' . tablename('wlmerchant_delivery_spec') . ' where goodsid = '.$id.' AND id not in ('.implode(',' , $specids).')');
            $res = 1;
        }
        if($res){
            if($gooddata['status'] == 5){
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核商品';
                $content = '配送商品:' . $gooddata['name'];
                $status  = '待审核';
                $remark  = "商户[" . $store['storename'] . "]上传了一个同城配送商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');
            }

            $this->renderSuccess('保存成功');
        }else{
            $this->renderError('保存商品失败,请重试');
        }
    }

    /**
     * Comment: 上下架或删除商品
     * Author: wlf
     * Date: 2020/05/09 19:45
     */
    function changeGoodStatus(){
        global $_W,$_GPC;
        $storeid = $_GPC['storeid']; //商家id
        $goodsid = $_GPC['goodsid']; //商品id
        $status = trim($_GPC['status']) ? trim($_GPC['status']) : 2;  //状态 2上架 4下架 8放入回收站
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('aid','storename','audits'));
        if(empty($goodsid)){
            $this->renderError('无商品信息，请刷新重试');
        }
        if($status == 2 && empty($store['audits'])){
            $status = 5;
        }
        $res = pdo_update('wlmerchant_delivery_activity',array('status' => $status),array('id' => $goodsid));
        if($res){
            if($status == 5){
                $goodname =  pdo_getcolumn(PDO_NAME.'delivery_activity',array('id'=>$goodsid),'name');
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核商品';
                $content = '配送商品:' . $goodname;
                $status  = '待审核';
                $remark  = "商户[" . $store['storename'] . "]上传了一个同城配送商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');
            }
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败,请重试');
        }
    }

    /**
     * Comment: 同城配送的订单获取
     * Author: wlf
     * Date: 2020/04/13 10:24
     */
    public function deliveryOrderSubmit(){
        global $_W , $_GPC;
        //参数获取
        $data = [];
        $goodsinfo = json_decode(base64_decode($_GPC['goodsinfo']) , true);
        $addressid = $_GPC['addressid'];
        $usestatus = $_GPC['type'];  //配送方式 0到店自提 1商家配送 2平台配送
        $sid = $_GPC['sid'];
        $allcredit = sprintf("%.2f" , $_W['wlmember']['credit1']);
        $goodsUesCredit = 0;
        //快递信息
        if ($addressid>0) {
            $address = pdo_get('wlmerchant_address' , ['id' => $addressid] , ['id','name' ,'status','tel','province','city','county','detailed_address','lng','lat']);
        }else{
            $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'status' , 'name' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address','lng','lat']);
            $addressid = $address['id'];
        }
        //商品信息获取
        if((empty($goodsinfo) || !is_array($goodsinfo)) && empty($sid) ){
            $this->renderError('商品信息错误，请返回重试');
        }else{
            if(empty($goodsinfo) || !is_array($goodsinfo)){
                $storeinfo['sid'] = $sid;
                $storeinfo['cartid'] = pdo_getall('wlmerchant_delivery_shopcart',array('mid' => $_W['mid'],'sid' => $sid),array('id'));
                foreach ($storeinfo['cartid'] as $cart){
                    $infoarray[] = $cart['id'];
                }
                $storeinfo['cartid'] = $infoarray;
                $goodsinfo[] = $storeinfo;
            }
            $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
            $goodallmoney = $deliveryallmoney = $alldiscount = $fulldiscount = $packingmoney = 0;
            foreach ($goodsinfo as $ke => $store){
                $storearray = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('bzftext','aid','lng','lat','address','third_shop_no','third_city_name','third_city_code','deliveryfullid','makebiguser','deliverytype','storename','expresspricestatus','deliverymoney','deliverydistance','lowdeliverymoney','exemptmoney'));
                $storearray['bzftext'] = $storearray['bzftext'] ? : '包装费';
                $storegoodsmoney = 0;
                //判断配送方式
                if($ke == 0){
                    $deliverytype = unserialize($storearray['deliverytype']);
                    $data['statistics']['use_store'] = in_array('store',$deliverytype) ? 1 : 0;
                    $data['statistics']['use_make'] = in_array('make',$deliverytype) ? 1 : 0;
                    $data['statistics']['use_own'] = in_array('own',$deliverytype) ? 1 : 0;
                }
                unset($storearray['deliverytype']);
                $storearray['allmoney'] = 0;
                $storearray['vipdiscount'] = 0;
                $storearray['packingmoney'] = 0;
                $storearray['sid'] = $store['sid'];
                if($usestatus == 0 || ($usestatus == 2 && empty($storearray['expresspricestatus']) )){
                    $storearray['deliverymoney'] = 0;
                }else{
                    $storearray['distance'] = Store::getdistance($address['lng'],$address['lat'],$storearray['lng'],$storearray['lat'],true);
                    if($storearray['distance'] > 9999998){
                        $storearray['distance'] = 0;
                    }
                }
                foreach ($store['cartid'] as $good){
                    $good = pdo_get('wlmerchant_delivery_shopcart',array('id' => $good),array('goodid','num','specid'));
                    $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $good['goodid']),array('name','creditmoney','price','deliveryprice','thumb','vipstatus','vipdiscount','optionstatus'));
                    $goodarray['name'] = $goodinfo['name'];
                    $goodarray['thumb'] = tomedia($goodinfo['thumb']);
                    $goodarray['price'] = $goodinfo['price'];
                    $goodarray['deliveryprice'] = $goodinfo['deliveryprice'];
                    $goodarray['num'] = $good['num'];
                    //规格
                    if($goodinfo['optionstatus']){
                        $specarray = pdo_get('wlmerchant_delivery_spec',array('id' => $good['specid']),array('name','price','oldprice'));
                        $goodarray['price'] = $specarray['price'];
                        $goodarray['specname'] = $specarray['name'];
                    }
                    //会员
                    if($goodinfo['vipstatus'] == 1 && $halfflag){
                        $goodarray['vipdiscount'] = $goodinfo['vipdiscount'];
                    }else{
                        $goodarray['vipdiscount'] = 0;
                    }
                    //计算积分抵扣
                    if($goodinfo['creditmoney'] > 0 && $_W['wlsetting']['creditset']['dkstatus'] > 0 ){
                        $goodsUesCredit += $goodinfo['creditmoney'] * $good['num'];
                    }
                    //计算包装费
                    if($usestatus>0){
                        $storearray['packingmoney'] = sprintf("%.2f",$storearray['packingmoney'] + $goodinfo['deliveryprice']*$goodarray['num']);
                    }else{
                        $storearray['packingmoney'] = 0;
                    }
                    //计算小计信息
                    $storearray['vipdiscount'] = sprintf("%.2f",$storearray['vipdiscount'] + $goodarray['vipdiscount'] * $goodarray['num']);
                    $storearray['allmoney'] = sprintf("%.2f",$storearray['allmoney'] + $goodarray['price'] * $goodarray['num']);

                    $goodallmoney += sprintf("%.2f",$goodarray['price'] * $goodarray['num']);
                    $storegoodsmoney += sprintf("%.2f",$goodarray['price'] * $goodarray['num'] - ($goodinfo['creditmoney'] * $good['num']) - $goodarray['vipdiscount']);
                    $storearray['goodlist'][] = $goodarray;
                }
                //平台配送
                if($usestatus == 2){
                    //判断地址
                    if(empty($addressid) || empty($address['lng']) || empty($address['lat']) ){
                        $this->renderError('请先设置配送地址',['url'=>h5_url('pages/subPages/receivingAddress/receivingAddress')]);
                    }

                    //配送设置
			        $deliveset = Setting::agentsetting_read('citydelivery');
			        $apiset = Setting::wlsetting_read('api');
			        $apiset = $apiset['citydelivery'];
			        if($deliveset['type'] > 0){
			        	if($deliveset['type'] == 3){
			        		$apiset['type'] = 0;
			        	}else{
			        		$apiset['type'] = $deliveset['type'];
			        	}
			        }
                    
                    if($apiset['type'] == 0 && empty($storearray['expresspricestatus'])){
                        $big = $storearray['makebiguser'] > 0 ? $store['sid'] : 0;
                        $makePrice = Citydelivery::getMakePrice($store['sid'],$addressid,$big);
                        if(empty($makePrice)){
                            $this->renderError('跑腿平台通信错误，请返回重试');
                        }
                        $storearray['deliverymoney'] = $makePrice['total_price'];
                        $storearray['distance'] = $makePrice['distance'].'km';
                        $storearray['init'] = $makePrice['init'];
                        $storearray['premium'] = $makePrice['premium'];
                        $storearray['night_price'] = $makePrice['night_price'];
                        $storearray['mileage_price'] = $makePrice['mileage_price'];
                        unset($storearray['makebiguser']);
                    }else if($apiset['type'] == 1 && !empty($address)){  //达达跑腿
                        $product_list = [];
                        foreach($storearray['goodlist'] as $stfood){
                            if(!empty($stfood['specname'])){
                                $name = $stfood['name'].'['.$stfood['specname'].']';
                            }else{
                                $name = $stfood['name'];
                            }
                            $stginfo = [
                                'sku_name' => $name,
                                'src_product_no' => '0',
                                'count' => $stfood['num'],
                            ];
                            $product_list[] = $stginfo;
                        }
                        $orderno = createUniontid();
                        $callback = $_W['siteroot']."addons/".MODULE_NAME."/core/common/uniapp.php?i=".$_W['uniacid']."&p=citydelivery&do=finishOrder&orderno=".$orderno."&type=5";
                        $body = [
                            'shop_no' => $storearray['third_shop_no'],
                            'origin_id' => $orderno,
                            'city_code' => $storearray['third_city_code'],
                            'cargo_price' => $storearray['allmoney'],
                            'is_prepay' => 0,
                            'receiver_name' => $address['name'],
                            'receiver_address' => $address['detailed_address'],
                            'receiver_lat' => $address['lat'],
                            'receiver_lng' => $address['lng'],
                            'receiver_phone' => $address['tel'],
                            'cargo_weight' => 1,
                            'callback' => $callback
                        ];
                        $dadaPrice = Citydelivery::postDadaApi($body,1);
                        if($dadaPrice['status'] == 'fail'){
                            $this->renderError('跑腿平台通信错误，'.$dadaPrice['msg']);
                        }
                        $storearray['wlorderno'] = $orderno;
                        $storearray['deliverymoney'] = $dadaPrice['result']['fee'];
                        $storearray['distance'] = sprintf("%.2f",$dadaPrice['result']['distance']).'m';
                        $storearray['makeorderno'] = $dadaPrice['result']['deliveryNo'];
                    }else if($apiset['type'] == 2 && !empty($address)){
                        $orderno = createUniontid();
                        $toloca = Util::Convert_GCJ02_To_BD09($address['lat'],$address['lng']);
                        $fromloca = Util::Convert_GCJ02_To_BD09($storearray['lat'],$storearray['lng']);
                        $data = [
                            'origin_id'    => $orderno,
                            'from_address' => $storearray['address'],
                            'to_address'   => $address['detailed_address'],
                            'city_name'    => $storearray['third_city_name'],
                            'send_type'    => 0,
                            'to_lat'       => $toloca['lat'],
                            'to_lng'       => $toloca['lng'],
                            'from_lat'     => $fromloca['lat'],
                            'from_lng'     => $fromloca['lng'],
                            'openid'       => $apiset['uu']['openid']
                        ];
                        $UUPrice = Citydelivery::postUUApi($data,1);
                        if($UUPrice['return_code'] != 'ok'){
                            $this->renderError('跑腿平台通信错误，'.$UUPrice['return_msg']);
                        }
                        $storearray['wlorderno'] = $orderno;
                        $storearray['deliverymoney'] = $UUPrice['total_money'];
                        $storearray['alldeliverymoney'] = $UUPrice['need_paymoney'];
                        $storearray['distance'] = sprintf("%.2f",$UUPrice['distance']).'m';
                        $storearray['makeorderno'] = $UUPrice['price_token'];
                    }
                }else if($usestatus == 1 || ($usestatus == 2 && $storearray['expresspricestatus'] > 0 )){ //免配送费
                	if($storegoodsmoney > $storearray['exemptmoney'] && $storearray['exemptmoney'] > 0){
                		 $storearray['deliverymoney'] = 0;
                	}
                }
                if($storearray['deliveryfullid']>0){
                    $storearray['fulldkmoney'] = Fullreduce::getFullreduceMoney(sprintf("%.2f" , $storearray['allmoney'] - $storearray['vipdiscount']),$storearray['deliveryfullid']);
                }else{
                    $storearray['fulldkmoney'] = 0;
                }
                //查询红包信息
                if(p('redpack')){
                    $storearray['redpackTopMoney'] = sprintf("%.2f" , $storearray['allmoney'] - $storearray['vipdiscount']);
                    $storearray['redpacklist'] = Redpack::getNotUseList($storearray['redpackTopMoney'],$store['sid'],$storearray['aid'],0,'delivery');
                }else{
                    $storearray['redpacklist'] = ['list'=>[],'total'=>0];
                }
                $storearray['allmoney'] = sprintf("%.2f",$storearray['allmoney'] + $storearray['packingmoney'] + $storearray['deliverymoney'] - $storearray['vipdiscount'] - $storearray['fulldkmoney']);
                //计算总计信息
                $deliveryallmoney += $storearray['deliverymoney'];
                $alldiscount += $storearray['vipdiscount'];
                $fulldiscount += $storearray['fulldkmoney'];
                $packingmoney += $storearray['packingmoney'];
                $list[] = $storearray;
            }
        }
        $data['list'] = $list;
        $data['statistics']['goodallmoney'] = sprintf("%.2f",$goodallmoney);
        $data['statistics']['deliveryprice'] = sprintf("%.2f",$deliveryallmoney);
        $data['statistics']['vipdiscount'] = sprintf("%.2f",$alldiscount);
        $data['statistics']['fulldiscount'] = sprintf("%.2f",$fulldiscount);
        $data['statistics']['packingmoney'] = sprintf("%.2f",$packingmoney);
        $data['statistics']['toatlprice'] = sprintf("%.2f",$data['statistics']['goodallmoney'] + $data['statistics']['packingmoney'] + $data['statistics']['deliveryprice'] - $data['statistics']['vipdiscount'] - $data['statistics']['fulldiscount']);
        $data['statistics']['allcredit'] = $allcredit;
        //积分抵扣计算
        if($goodsUesCredit > 0){
            $dkcredit = sprintf("%.2f" , $goodsUesCredit * $_W['wlsetting']['creditset']['proportion']);
            if ($allcredit < $dkcredit) {
                $dkcredit = $allcredit;
            }
            $dkmoney                = sprintf("%.2f" , $dkcredit / $_W['wlsetting']['creditset']['proportion']);
            $data['statistics']['credit']         = $dkcredit;  //可使用积分
            $data['statistics']['creditdiscount'] = $dkmoney; //积分抵扣金额
        }
        if (empty($address)){
            $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']] , ['id' , 'name' , 'status' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
            if ($address) {
                pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $address['id']]);
            }else{
                $address = [];
            }
        }
        $data['address'] = $address;
        //提货信息
        $member = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('realname','nickname','mobile'));
        $data['thinfo']['thname'] = $member['realname']?$member['realname']:$member['nickname'];
        $data['thinfo']['thmobile'] = $member['mobile'];


        $this->renderSuccess('提交订单',$data);
    }


    /**
     * Comment: 同城配送创建订单
     * Author: wlf
     * Date: 2020/04/13 14:53
     */
    public function createDeliveryOrder(){
        global $_W , $_GPC;
        //参数获取
        $goodsinfo = json_decode(base64_decode($_GPC['goodsinfo']),true);
        $addressid = $_GPC['addressid'];  //地址信息
        $username     = trim($_GPC['thname']);  //提货人姓名
        $mobile       = trim($_GPC['thmobile']);  //提货人电话
        $type         = $_GPC['buytype'];   //配送方式 0到店自提 1商家配送 2平台配送
        $sid          = $_GPC['sid'];
        $remark       = trim($_GPC['remark']);
        $creditstatus = $_GPC['creditstatus']; //积分抵扣
        $memberAllCredit = sprintf("%.2f" , $_W['wlmember']['credit1']); //总积分
        $allprice = 0;
        if($type == 2){
            //配送设置
	        $deliveset = Setting::agentsetting_read('citydelivery');
	        $apiset = Setting::wlsetting_read('api');
	        $apiset = $apiset['citydelivery'];
	        if($deliveset['type'] > 0){
	        	if($deliveset['type'] == 3){
	        		$apiset['type'] = 0;
	        	}else{
	        		$apiset['type'] = $deliveset['type'];
	        	}
	        }
        }
        //获取位置信息
        if($type > 0){
            $address = pdo_get('wlmerchant_address' , ['id' => $addressid] , ['lng','lat']);
            if(empty($address['lng']) || empty($address['lat'])){
                $this->renderError('配送地址无定位信息，请添加');
            }
        }
        //商品信息获取
        if((empty($goodsinfo) || !is_array($goodsinfo)) && empty($sid)){
            $this->renderError('订单信息错误，请返回重试');
        }else{
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            if(empty($goodsinfo) || !is_array($goodsinfo)){
                $storeinfo['sid'] = $sid;
                $storeinfo['cartid'] = pdo_getall('wlmerchant_delivery_shopcart',array('mid' => $_W['mid'],'sid' => $sid),array('id'));
                foreach ($storeinfo['cartid'] as $cart){
                    $infoarray[] = $cart['id'];
                }
                $storeinfo['cartid'] = $infoarray;
                $storeinfo['remark'] = $remark;
                $storeinfo['wlorderno'] = $_GPC['wlorderno'];
                if($storeinfo['wlorderno'] == 'undefined'){
                    $storeinfo['wlorderno'] = '';
                }
                $storeinfo['makeorderno'] = $_GPC['makeorderno'];
                if($storeinfo['makeorderno'] == 'undefined'){
                    $storeinfo['makeorderno'] = '';
                }
                $storeinfo['dadaprice'] = $_GPC['deliverymoney'];
                $storeinfo['uuallprice'] = $_GPC['alldeliverymoney'];
                $storeinfo['redpackid'] = $_GPC['redpackid'];
                $goodsinfo[] = $storeinfo;
            }
            $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
            $settings = Setting::wlsetting_read('orderset'); //获取设置参数
            $ordersid = [];
            foreach ($goodsinfo as $store){
                $num = 0;
                $goodallmoney = $alldiscount = $packingmoney = $prices = $goodsUesCredit = $uuaexpressprice = 0;
                $storearray = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('deliverymoney','deliveryfullid','expresspricestatus','deliverytype','makebiguser','lat','lng','storename','deliverydistance','lowdeliverymoney','exemptmoney'));
                $deliveryallmoney = $storearray['deliverymoney'];
                $storearray['packingmoney'] = 0;
                $smallorderid = [];
                //判断是否能使用选择的配送方式
                $deliverytype = unserialize($storearray['deliverytype']);
                if($type == 0 && !in_array('own',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持到店自提,请单独结算支付');
                }
                if($type == 1 && !in_array('store',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持商户配送,请单独结算支付');
                }
                if($type == 2 && !in_array('make',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持平台配送,请单独结算支付');
                }
                unset($storearray['deliverytype']);
                foreach ($store['cartid'] as $goodid){
                    $good = pdo_get('wlmerchant_delivery_shopcart',array('id' => $goodid),array('goodid','num','specid'));
                    $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $good['goodid']),array('name','creditmoney','sid','allstock','daystock','uniacid','aid','price','deliveryprice','vipstatus','vipdiscount','optionstatus'));
                    $goodarray['price'] = $goodinfo['price'];
                    //规格
                    if($goodinfo['optionstatus']){
                        $specarray = pdo_get('wlmerchant_delivery_spec',array('id' => $good['specid']),array('name','allstock','daystock','price','oldprice'));
                        $goodarray['price'] = $specarray['price'];
                        $goodarray['specname'] = $specarray['name'];
                        $goodinfo['allstock'] = $specarray['allstock'];
                        $goodinfo['daystock'] = $specarray['daystock'];
                        $goodinfo['name'] .= '/'.$specarray['name'];
                    }
                    //判断库存
                    if($goodinfo['allstock'] > 0){
                        $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $good['goodid'],'specid' => $good['specid'],'status >' => 0),array("SUM(num)"));
                        if($allsalenum + $good['num'] > $goodinfo['allstock']){
                            $tipinfo = '商品['.$goodinfo['name'].']已售罄，无法下单';
                            $this->renderError($tipinfo);
                        }
                    }
                    if($goodinfo['daystock'] > 0){
                        $nowtime = strtotime(date('Y-m-d',time()));
                        $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $good['goodid'],'specid' => $good['specid'],'status >' => 0,'createtime' => $nowtime),array("SUM(num)"));
                        if($daysalenum + $good['num'] > $goodinfo['daystock']){
                            $tipinfo = '商品['.$goodinfo['name'].']已售罄，无法下单';
                            $this->renderError($tipinfo);
                        }
                    }
                    //会员
                    if($goodinfo['vipstatus'] == 1 && $halfflag){
                        $goodarray['vipdiscount'] = $goodinfo['vipdiscount'];
                    }else{
                        $goodarray['vipdiscount'] = 0;
                    }
                    //计算积分抵扣
                    if($goodinfo['creditmoney'] > 0 && $creditstatus > 0 ){
                        $goodsUesCredit += $goodinfo['creditmoney'] * $good['num'];
                    }
                    //计算配送费
                    if($type == 0){
                        $goodinfo['deliveryprice'] = 0;
                    }
                    if($goodinfo['deliveryprice']>0){
                        $storearray['packingmoney'] = sprintf("%.2f",$storearray['packingmoney'] + $goodinfo['deliveryprice']);
                    }
                    //创建小订单
                    $smallorder = array(
                        'uniacid'     => $goodinfo['uniacid'],
                        'aid'         => $goodinfo['aid'],
                        'sid'         => $goodinfo['sid'],
                        'gid'         => $good['goodid'],
                        'mid'         => $_W['mid'],
                        'specid'      => $good['specid'],
                        'money'       => sprintf("%.2f",($goodarray['price'] - $goodarray['vipdiscount'] + $goodinfo['deliveryprice']) * $good['num']),
                        'status'      => 0,
                        'num'         => $good['num'],
                        'price'       => sprintf("%.2f",$goodarray['price']  * $good['num'] ),
                        'vipdiscount' => sprintf("%.2f",$goodarray['vipdiscount'] * $good['num']),
                        'deliverymoney' => sprintf("%.2f",$goodinfo['deliveryprice'] * $good['num']),
                        'createtime'  => time()
                    );
                    $res = pdo_insert(PDO_NAME . 'delivery_order',$smallorder);
                    $smallorderid[] = pdo_insertid();
                    //累计金额
                    $goodallmoney = sprintf("%.2f",$goodallmoney + $smallorder['price']);
                    $alldiscount = sprintf("%.2f",$alldiscount + $smallorder['vipdiscount']);
                    $packingmoney = sprintf("%.2f",$packingmoney + $smallorder['deliverymoney']);
                    if($res){
                        pdo_delete('wlmerchant_delivery_shopcart',array('id'=>$goodid));
                    }else{
                        MysqlFunction::rollback();
                        $this->renderError('生成订单失败，请返回重试');
                    }
                    $num += $good['num'];
                }
                $prices = sprintf("%.2f",$goodallmoney - $alldiscount);
                //满减活动
                if($storearray['deliveryfullid']>0){
                    $storearray['fulldkmoney'] = Fullreduce::getFullreduceMoney(sprintf("%.2f" ,$prices),$storearray['deliveryfullid']);
                }else{
                    $storearray['fulldkmoney'] = 0;
                }
                //红包优惠
                if($store['redpackid'] > 0){
                    $redpack = pdo_fetch("SELECT b.cut_money FROM".tablename(PDO_NAME . "redpack_records")
                        ." as a LEFT JOIN " . tablename(PDO_NAME . "redpacks")
                        ." as b ON a.packid = b.id WHERE a.id = {$store['redpackid']}");
                    $redpackmoney = $redpack['cut_money'];
                }else{
                    $redpackmoney = 0;
                }
                //积分抵扣
                if($goodsUesCredit > 0){
                    $creditindo = self::creditDeduction($goodsUesCredit,$memberAllCredit);
                    $dkcredit = $creditindo['dkcredit'];
                    $dkmoney = $creditindo['dkmoney'];
                    $memberAllCredit -= $dkcredit;
                }else{
                    $dkcredit = 0;
                    $dkmoney = 0;
                }
                $storegoodsmoney = $prices - $redpackmoney - $dkmoney - $storearray['fulldkmoney'];
                if($storegoodsmoney > $storearray['exemptmoney'] && $storearray['exemptmoney'] > 0){
            		$deliveryallmoney = 0;
            	}
                
                if($type == 0){
                    $deliveryallmoney = $fightstatus = $packingmoney = $setdeliveryallmoney = $fightgroupid = $addressid = 0;
                }else if($type == 1){
                    $express = $this->freight($addressid ,0,array('id'=>0,'merchantid'=>$goodinfo['sid']),$deliveryallmoney);
                    $expressid = $addressid;
                    $fightgroupid = $express['expressid'];
                    $setdeliveryallmoney = sprintf("%.2f",$deliveryallmoney + $packingmoney);
                    $fightstatus = 1;
                }else{
                    if($apiset['type'] == 0){
                        $fightstatus = 2;
                        if(!empty($storearray['expresspricestatus'])){
                            $express = $this->freight($addressid ,0,array('id'=>0,'merchantid'=>$goodinfo['sid']),$deliveryallmoney);
                            $expressid = $addressid;
                            $fightgroupid = $express['expressid'];
                            $setdeliveryallmoney = sprintf("%.2f",$deliveryallmoney + $packingmoney);
                        }else{
                            $big = $storearray['makebiguser']>0 ? $store['sid'] : 0;
                            $express = Citydelivery::getMakePrice($store['sid'],$addressid,$big);
                            $deliveryallmoney = $express['total_price'];
                            $setdeliveryallmoney = $storearray['makebiguser']>0 ? sprintf("%.2f",$express['total_price'] + $packingmoney) : $packingmoney;
                            $expressid = $addressid;
                        }
                    }else if($apiset['type'] == 1){  //达达
                        $fightstatus = 3;
                        $expressid = $addressid;
                        $deliveryallmoney = $store['dadaprice'];
                        $setdeliveryallmoney = $packingmoney;
                    }else if($apiset['type'] == 2){  //UU
                        $fightstatus = 4;
                        $expressid = $addressid;
                        $deliveryallmoney = $store['dadaprice'];
                        $uuaexpressprice = $store['uuallprice'];
                        $setdeliveryallmoney = $packingmoney;
                    }
                }
                
                $settlementmoney = Citydelivery::getsettlementmoney($prices,$store['sid'],$setdeliveryallmoney);
                if($settlementmoney < 0.01){
                    $settlementmoney = Citydelivery::getsettlementmoney($prices,$store['sid'],$setdeliveryallmoney);
                }
                
                //自负营销
                $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$store['sid']),'marketstatus');
                if($marketstatus > 0){
                    $settlementmoney = sprintf("%.2f",$settlementmoney - $alldiscount - $redpackmoney - $dkmoney - $storearray['fulldkmoney']);
                }
                
                
                $prices = sprintf("%.2f",$prices + $deliveryallmoney + $packingmoney - $storearray['fulldkmoney'] - $redpackmoney - $dkmoney);
                //校验距离和起送金额
                if($type == 1 || ($type == 2 && empty($_W['wlsetting']['api']['citydelivery']['type']))){
                    if($storearray['deliverydistance']>0){
                        if($type == 1){
                            $distance = Store::getdistance($address['lng'],$address['lat'],$storearray['lng'],$storearray['lat']);
                        }else{
                            $express = Citydelivery::getMakePrice($store['sid'],$addressid,$big);
                            $distance = $express['distance']*1000;
                        }
                        if($distance > $storearray['deliverydistance']*1000){
                            MysqlFunction::rollback();
                            $this->renderError('['.$storearray['storename'].']的配送距离为'.$storearray['deliverydistance'].'km,请更换配送位置或选择其他配送方式');
                        }
                    }
                    if($storearray['lowdeliverymoney']>0){
                    	$getdemoney = $prices - $deliveryallmoney - $packingmoney;
                        if($getdemoney < $storearray['lowdeliverymoney']){
                            MysqlFunction::rollback();
                            $this->renderError('['.$storearray['storename'].']的起送金额为'.$storearray['lowdeliverymoney'].'元,请增购商品或选择其他配送方式');
                        }
                    }
                }
                $prices = $prices > 0 ? $prices : 0;
                //创建商户订单
                $orderData = [
                    'uniacid'         => $goodinfo['uniacid'] ,
                    'mid'             => $_W['mid'] ,
                    'sid'             => $goodinfo['sid'] ,
                    'aid'             => $goodinfo['aid'] ,
                    'plugin'          => 'citydelivery' ,
                    'payfor'          => 'deliveryOrder' ,
                    'orderno'         => $store['wlorderno'] ? : createUniontid() ,
                    'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                    'createtime'      => TIMESTAMP ,
                    'price'           => $prices > 0 ? $prices : 0,
                    'vipbuyflag'      => $halfflag ,
                    'name'            => $username ,
                    'mobile'          => $mobile ,
                    'goodsprice'      => $goodallmoney ,
                    'expressid'       => $expressid ,
                    'buyremark'       => $store['remark'],
                    'settlementmoney' => $settlementmoney ,
                    'vipdiscount'     => $alldiscount,
                    'expressprcie'    => $deliveryallmoney,
                    'canceltime'      => time() + $settings['cancel'] * 60,
                    'num'             => $num,
                    'fightstatus'     => $fightstatus,
                    'fullreduceid'    => $storearray['deliveryfullid'],
                    'fullreducemoney' => $storearray['fulldkmoney'],
                    'fightgroupid'    => $fightgroupid,
                    'packingmoney'    => $packingmoney,
                    'makeorderno'     => $store['makeorderno'],
                    'redpackid'       => $store['redpackid'],
                    'redpackmoney'    => $redpackmoney,
                    'usecredit'       => $dkcredit ,
                    'cerditmoney'     => $dkmoney,
                    'uuaexpressprice' => $uuaexpressprice
                ];
                pdo_insert(PDO_NAME . 'order' , $orderData);
                $orderid = pdo_insertid();
                if($store['redpackid'] > 0){
                    pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => 'citydelivery'),array('id' => $store['redpackid']));
                }
                if($orderid){
                    $ordersid[] = $orderid;
                    foreach ($smallorderid as $smallid){
                        pdo_update('wlmerchant_delivery_order',array('tid' => $orderData['orderno'],'orderid' => $orderid),array('id' => $smallid));
                    }
                }else{
                    MysqlFunction::rollback();
                    $this->renderError('生成订单失败，请返回重试');
                }
                $allprice += $prices;
            }
            if($allprice < 0.01){
                $newdata = [
                    'status'  => 1 ,
                    'paytime' => time() ,
                    'paytype' => 6 ,
                ];
                if(count($ordersid)>1){
                    $merchantlog = [
                        'uniacid'    => $_W['uniacid'] ,
                        'acid'       => $_W['acid'] ,
                        'mid'        => $_W['mid'] ,
                        'module'     => MODULE_NAME ,
                        'plugin'     => 'Citydelivery' ,
                        'payfor'     => 'DeliveryOrder' ,
                        'tid'        => createUniontid(),
                        'fee'        => 0,
                        'card_fee'   => 0,
                        'status'     => 1 ,
                        'is_usecard' => '0' ,
                        'type'       => 5
                    ];
                    pdo_insert(PDO_NAME . 'paylogvfour' , $merchantlog);
                    $merchantlogid = pdo_insertid();
                    $data['nopaytid'] = $merchantlog['tid'];
                    $data['nopaystatus'] = 1;
                }else{
                    $merchantlogid = 0;
                    $data['nopaytid'] = $orderData['orderno'];
                    $data['nopaystatus'] = 2;
                }
                foreach($ordersid as $orid){
                    if($merchantlogid > 0){
                        pdo_update('wlmerchant_order',array('paylogid' => $merchantlogid),array('id' => $orid));
                    }
                    Citydelivery::updeteOrder($newdata,$orid);
                }
            }else{
                $data['nopayid'] = 0;
            }
            MysqlFunction::commit();
            $data['info'] = base64_encode(json_encode($ordersid));
            $this->renderSuccess('订单信息',$data);
        }

    }

    /**
     * Comment: 计算运费
     * Author: wlf
     * Date: 2019/8/19 09:15
     */
    public function freight($addressid , $num , $good,$expressprice = 0)
    {
        global $_W;
        //设置默认
        pdo_update('wlmerchant_address' , ['status' => 0] , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']]);
        pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $addressid]);
        $address            = pdo_get('wlmerchant_address' , ['id' => $addressid]);
        $data['uniacid']    = $_W['uniacid'];
        $data['mid']        = $_W['mid'];
        $data['goodsid']    = $good['id'];
        $data['merchantid'] = $good['merchantid'];
        $data['address']    = $addre = $address['province'] . $address['city'] . $address['county'] . $address['detailed_address'];
        $data['name']       = $username = $address['name'];
        $data['tel']        = $mobile = $address['tel'];
        if ($good['expressid']>0) {
            $express = pdo_get('wlmerchant_express_template' , ['id' => $good['expressid']]);
            //添加设置错误项校验
            if(empty($express['defaultnum'])){
                $express['defaultnum'] = 99999;
            }
            if(empty($express['defaultnumex'])){
                $express['defaultnumex'] = 1;
            }
            if(!empty($express)){
                if (mb_substr($address['province'] , -1 , 1 , 'utf-8') == '省') {
                    $address['province'] = mb_substr($address['province'] , 0 , mb_strlen($address['province']) - 1 , 'utf-8');
                }
                if ($express['expressarray']) {
                    $expressarray = unserialize($express['expressarray']);
                    foreach ($expressarray as $key => &$v) {
                        $v['area'] = rtrim($v['area'] , ",");
                        $v['area'] = explode(',' , $v['area']);
                        if (in_array($address['province'] , $v['area'])) {
                            if ($num > $v['num']) {
                                $expressprice = $v['money'] + ceil(($num - $v['num']) / $v['numex']) * $v['moneyex'];
                            }
                            else {
                                $expressprice = $v['money'];
                            }
                        }
                    }
                }
                if (empty($expressprice)) {
                    if ($num > $express['defaultnum']) {
                        $expressprice = $express['defaultmoney'] + ceil(($num - $express['defaultnum']) / $express['defaultnumex']) * $express['defaultmoneyex'];
                    }
                    else {
                        $expressprice = $express['defaultmoney'];
                    }
                }
            }
            $expressprice = $expressprice < 0 ? 0 : $expressprice;
        }
        $data['expressprice'] = $expressprice;
        pdo_insert(PDO_NAME . 'express' , $data);
        $expressid = pdo_insertid();
        $express   = [
            'price'     => $expressprice ,
            'expressid' => $expressid
        ];
        return $express;
    }

    /**
     * Comment: 积分抵扣函数
     * Author: wlf
     * Date: 2021/03/08 15:31
     */
    public function creditDeduction($dkmoney,$allcredit)
    {
        global $_W , $_GPC;
        $onecreditmoney = sprintf("%.2f" , 1 / $_W['wlsetting']['creditset']['proportion']);
        $dkcredit       = sprintf("%.2f" , $dkmoney / $onecreditmoney);
        if ($dkcredit > $allcredit) {
            $dkcredit = $allcredit;
            $dkmoney  = sprintf("%.2f" , $onecreditmoney * $dkcredit);
        }
        Member::credit_update_credit1($_W['mid'] , -$dkcredit ,'同城配送订单抵扣积分');
        return ['dkcredit' => $dkcredit , 'dkmoney' => $dkmoney];
    }

    /**
     * Comment: 预选红包列表
     * Author: wlf
     * Date: 2021/03/17 11:51
     */
    public function getRedpackList()
    {
        global $_W , $_GPC;
        $redpackTopMoney = $_GPC['redpackTopMoney'];
        $sid = $_GPC['sid'];
        $redpacklist = Redpack::getNotUseList($redpackTopMoney,$sid,$_W['aid'],0,'delivery');
        $this->renderSuccess('红包列表信息',$redpacklist);
    }

    /**
     * Comment: 预选红包接口
     * Author: wlf
     * Date: 2021/03/17 14:01
     */
    public function selectRedpack()
    {
        global $_W , $_GPC;
        $selectId = $_GPC['selectId'];
        $cancelId = $_GPC['cancelId'];
        $cancelIds = explode(',',$_GPC['cancelIds']);
        if($selectId > 0){
            pdo_update('wlmerchant_redpack_records',array('status' => 1),array('id' => $selectId));
        }
        if($cancelId > 0){
            pdo_update('wlmerchant_redpack_records',array('status' => 0),array('id' => $cancelId));
        }
        if(is_array($cancelIds)){
            pdo_update('wlmerchant_redpack_records',array('status' => 0),array('id' => $cancelIds));
        }
        $this->renderSuccess('操作成功');
    }

    /**
     * Comment: 商户接单接口
     * Author: wlf
     * Date: 2021/07/12 11:07
     */
    public function acceptOrderApi(){
        global $_W , $_GPC;
        $orderid = $_GPC['orderid'];
        if(empty($orderid)){
            $this->renderError('缺少必要参数，请刷新重试');
        }
        $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('goodsprice','aid','vipdiscount','expressid','makeorderno','uniacid','paytime','fightstatus','price','expressprcie','uuaexpressprice','status','sid','orderno','id','mid'));
        $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','expresspricestatus','mobile','acceptstatus','third_shop_no','third_city_code','deliverypaidid','makebiguser','deliverydisstatus','onescale','twoscale'));
        $makeorderno = Citydelivery::acceptOrder($order,$storeinfo);
        if(!empty($makeorderno)){
            $data['makeorderno'] = $makeorderno;
        }
        $data['status'] = 4;
        $res = pdo_update('wlmerchant_order',$data,array('id' => $orderid));
        if($res){
            $first = '您好，您的订单商家已接单';
            $type = '商家接单通知';
            $content = '商家:['.$storeinfo['storename'].']';
            $newStatus = '已接单';
            $remark = '点击查看订单详情';
            $url = h5_url('pages/subPages/orderList/orderTakeout/orderTakeout',['orderid' => $orderid]);
            News::jobNotice($order['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }
}