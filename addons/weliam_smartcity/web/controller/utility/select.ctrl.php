<?php
defined('IN_IA') or exit('Access Denied');

class select_WeliamController{
    /**
     * Comment: 获取公众号地址
     * Author: zzw
     */
    public function comurl()
    {
        global $_W , $_GPC;
        #1、获取公共链接信息
        $data = Links::getLinks();
        #1、规避url前缀字符串  将完整超链接变为内部path路径
        //获取将要规避的url字符串信息
        $string = 'h5/#/';
        $urlStr = trim(json_encode(explode($string , h5_url(''))[0] . $string) , '"');
        //将要规避的ulr字符串替换为空
        $data = json_decode(str_replace($urlStr , '' , json_encode($data)) , true);
        #3、重定义链接信息
        $shop_pageNum       = 5;
        $rush_pageNum       = 5;
        $fightgroup_pageNum = 5;
        $groupon_pageNum    = 5;
        $coupon_pageNum     = 5;
        $bargain_pageNum    = 5;
        $system             = $data['system'];
        $shop_total         = $data['shop_total'];
        $shopList           = $data['shopList'];
        $rush_total         = $data['rush_total'];
        $rush               = $data['rush'];
        $fightgroup_total   = $data['fightgroup_total'];
        $fightgroup         = $data['fightgroup'];
        $groupon_total      = $data['groupon_total'];
        $groupon            = $data['groupon'];
        $coupon_total       = $data['coupon_total'];
        $coupon             = $data['coupon'];
        $bargain_total      = $data['bargain_total'];
        $bargain            = $data['bargain'];
        $pageInfo           = $data['pageInfo'];
        $rushSpecial        = $data['rush_special'];
        $cate               = $data['cate'];

        include wl_template('utility/selecturl');
    }
    /**
     * Comment: 获取店铺信息
     * Author: zzw
     */
    public function getShop()
    {
        global $_W , $_GPC;
        $search    = $_GPC['search'] ? $_GPC['search'] : '';
        $page      = $_GPC['page'];
        $pageNum   = $_GPC['pageNum'];
        $limit     = ' LIMIT ' . ($page * $pageNum - $pageNum) . ',' . $pageNum;
        $shopWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 ";
        if ($search) {
            $shopWhere .= " AND storename LIKE '%{$search}%' ";
        }
        $shop       = pdo_fetchall("SELECT id,storename,logo,storehours FROM " . tablename(PDO_NAME . "merchantdata") . " WHERE {$shopWhere} {$limit}");
        $shop_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE {$shopWhere}");
        foreach ($shop as $k => &$v) {
            $v['url']        = h5_url('pages/mainPages/store/index' , ['sid' => $v['id']]);
            $v['page_path']  = 'pages/mainPages/store/list';
            $v['logo']       = tomedia($v['logo']);
            $storehours      = unserialize($v['storehours']);
            $v['storehours'] = $storehours['startTime'] . '-' . $storehours['endTime'];
            unset($v['id']);
        }
        $data['page']    = $page;
        $data['pageNum'] = $pageNum;
        $data['total']   = $shop_total;
        $data['list']    = $shop;
        $data['search']  = $search;

        //获取将要规避的url字符串信息
        $string = 'h5/#/';
        $urlStr = trim(json_encode(explode($string , h5_url(''))[0] . $string) , '"');
        //将要规避的ulr字符串替换为空
        $data = json_decode(str_replace($urlStr , '' , json_encode($data)) , true);

        wl_json(1 , '商户分页信息' , $data);
    }
    /**
     * Comment: 获取商品信息
     * Author: zzw
     */
    public function getGoods()
    {
        global $_W , $_GPC;
        $search  = $_GPC['search'] ? $_GPC['search'] : '';
        $page    = $_GPC['page'];
        $pageNum = $_GPC['pageNum'];
        $limit   = ' LIMIT ' . ($page * $pageNum - $pageNum) . ',' . $pageNum;
        $type    = $_GPC['type'];
        $where   = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        switch ($type) {
            case 1:
                if ($search) {
                    $where .= " AND name LIKE '%{$search}%' ";
                }
                $info      = pdo_fetchall(" SELECT id,name,thumb FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE status IN (1,2) AND {$where} {$limit}");
                $infoTotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE status IN (1,2) AND {$where}");
                foreach ($info as $k => &$v) {
                    $v['url']       = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 1]);
                    $v['page_path'] = 'pages/subPages/goods/index?type=1';
                    $v['logo']      = tomedia($v['thumb']);
                    unset($v['id']);
                }
                break;//抢购商品
            case 2:
                if ($search) {
                    $where .= " AND name LIKE '%{$search}%' ";
                }
                $info      = pdo_fetchall(" SELECT id,name,thumb FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE status IN (1,2) AND {$where} {$limit}");
                $infoTotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'groupon_activity') . " WHERE status IN (1,2) AND {$where}");
                foreach ($info as $k => &$v) {
                    $v['url']       = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 2]);
                    $v['page_path'] = 'pages/subPages/goods/index?type=2';
                    $v['logo']      = tomedia($v['thumb']);
                    unset($v['id']);
                }
                break;//团购商品
            case 3:
                if ($search) {
                    $where .= " AND name LIKE '%{$search}%' ";
                }
                $info      = pdo_fetchall(" SELECT id,name,logo FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE status IN (1,2) AND {$where} {$limit}");
                $infoTotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'fightgroup_goods') . " WHERE status IN (1,2) AND {$where}");
                foreach ($info as $k => &$v) {
                    $v['url']       = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 3]);
                    $v['page_path'] = 'pages/subPages/goods/index?type=3';
                    $v['logo']      = tomedia($v['logo']);
                    unset($v['id']);
                }
                break;//拼团商品
            case 5:
                $where .= " AND status IN (1,2) ";
                if ($search) {
                    $where .= " AND title LIKE '%{$search}%' ";
                }
                $info      = pdo_fetchall(" SELECT id,title,logo FROM " . tablename(PDO_NAME . "couponlist") . " WHERE {$where} {$limit}");
                $infoTotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE {$where}");
                foreach ($info as $k => &$v) {
                    $v['url']       = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 5]);
                    $v['page_path'] = 'pages/subPages/goods/index?type=5';
                    $v['name']      = $v['title'];
                    $v['logo']      = tomedia($v['logo']);
                    unset($v['id']);
                    unset($v['title']);
                }
                break;//卡券信息
            case 6:
                if ($search) {
                    $where .= " AND name LIKE '%{$search}%' ";
                }
                $info      = pdo_fetchall(" SELECT id,name,thumb FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE status IN (1,2) AND {$where} {$limit}");
                $infoTotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE status IN (1,2) AND {$where}");
                foreach ($info as $k => &$v) {
                    $v['url']       = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 7]);
                    $v['page_path'] = 'pages/subPages/goods/index?type=7';
                    $v['logo']      = tomedia($v['thumb']);
                    unset($v['id']);
                    unset($v['title']);
                }
                break;//砍价信息
        }

        $data['page']    = $page;
        $data['pageNum'] = $pageNum;
        $data['total']   = $infoTotal;
        $data['list']    = $info;
        $data['search']  = $search;

        //获取将要规避的url字符串信息
        $string = 'h5/#/';
        $urlStr = trim(json_encode(explode($string , h5_url(''))[0] . $string) , '"');
        //将要规避的ulr字符串替换为空
        $data = json_decode(str_replace($urlStr , '' , json_encode($data)) , true);

        wl_json(1 , '商品信息' , $data);
    }
    /**
     * Comment: 获取小图标信息
     * Author: zzw
     */
    public function comicon()
    {
        global $_W , $_GPC;


        include wl_template('utility/selecticon');
    }
    /**
     * Comment: 根据状态获取商品信息
     * Author: zzw
     * @param $plugin  商品类型1=抢购  2=团购  3=拼团  5=优惠券
     * @param $search  搜索内容
     * @return array
     */
    protected function getGoodsReturn($plugin , $search,$sid = 0,$addWhere = [])
    {
        global $_W;
        $where = " AND a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($sid > 0) $where .= " AND b.id = {$sid} ";
        if($addWhere['is_optionstatus'] == 1) $where .= " AND a.optionstatus = 0 ";
        if($addWhere['is_specstatus'] == 1) $where .= " AND a.specstatus = 0 ";
        switch ($plugin) {
            case 1:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','rush') as `plugin` FROM "
                                      . tablename(PDO_NAME . "rush_activity")
                                      . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                                      . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where}  AND a.name LIKE '%{$search}%'");//
                break;//抢购商品
            case 2:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','groupon') as `plugin` FROM " . tablename(PDO_NAME . "groupon_activity") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where} AND a.name LIKE '%{$search}%'");
                break;//团购商品
            case 3:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','wlfightgroup') as `plugin` FROM " . tablename(PDO_NAME . "fightgroup_goods") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.merchantid = b.id WHERE a.status IN (1,2) {$where} AND b.storename != '' AND a.name LIKE '%{$search}%'");
                break;//拼团商品
            case 4:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','package') as `plugin` FROM " . tablename(PDO_NAME . "package") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.merchantid = b.id WHERE a.status = 1 {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//大礼包
            case 5:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','coupon') as `plugin` FROM " . tablename(PDO_NAME . "couponlist") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.merchantid = b.id WHERE a.status IN (1,2) {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//优惠券
            case 6:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','halfcard') as `plugin` FROM " . tablename(PDO_NAME . "halfcardlist") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.merchantid = b.id WHERE a.status = 1 {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//折扣卡
            case 7:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','bargain') as `plugin` FROM " . tablename(PDO_NAME . "bargain_activity") . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where} AND b.storename != '' AND a.name LIKE '%{$search}%'");
                break;//砍价
        }
        return $goods;
    }
    /**
     * Comment: 获取全部商品信息
     * Author: zzw
     * Date: 2019/7/9 17:50
     */
    public function getWholeGoods()
    {
        global $_W , $_GPC;
        $plugin  = 0;//商品类型 0=全部 1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品
        $search  = $_GPC['search'];//搜索内容  商品名称
        $isOptionstatus  = intval($_GPC['is_optionstatus']) ? intval($_GPC["is_optionstatus"]) : 0;//是否只获取单规格商品0=不，1=是
        $page    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageNum = $_GPC['pageNum'] ? $_GPC['pageNum'] : 7;
        $geturl  = $_GPC['geturl'] ? $_GPC['geturl'] : 0;
        $sid = $_GPC['sid']? $_GPC['sid'] : 0;
        $start   = $page * $pageNum - $pageNum;
        //获取商品信息
        $rush         = self::getGoodsReturn(1 , $search , $sid,['is_optionstatus' => $isOptionstatus]);//抢购商品
        $groupon      = self::getGoodsReturn(2 , $search , $sid,['is_optionstatus' => $isOptionstatus]);//团购商品
        $wlfightgroup = self::getGoodsReturn(3 , $search , $sid,['is_specstatus' => $isOptionstatus]);//拼团商品
        $coupon       = self::getGoodsReturn(5 , $search , $sid);//优惠券
        $bargain      = self::getGoodsReturn(7 , $search , $sid);//砍价
        $goods        = array_merge($rush , $groupon , $wlfightgroup , $coupon , $bargain);
        if (!$goods) {
            $popup = '暂无该类型商品';
            include wl_template('utility/selecetgoods');
            die;
        }
        //获取总页数  进行分页
        $totalPgae = ceil(count($goods) / $pageNum);
        $goods     = array_slice($goods , $start , $pageNum);
        //只有抢购、团购、拼团、优惠券才会进行下面的操作
        $initPlugin = $plugin;
        foreach ($goods as $k => &$v) {
            //查询全部商品时 每个商品从新定义内容
            if ($initPlugin == 0) {
                switch ($v['plugin']) {
                    case 'rush':
                        $plugin = 1;
                        break;//抢购商品
                    case 'groupon':
                        $plugin = 2;
                        break;//团购商品
                    case 'wlfightgroup':
                        $plugin = 3;
                        break;//拼团商品
                    case 'coupon':
                        $plugin = 5;
                        break;//优惠券
                    case 'bargain':
                        $plugin = 7;
                        break;//砍价商品
                }
            }
            $v = WeliamWeChat::getHomeGoods($plugin , $v['id']);
            //获取商品详情页面的跳转地址
            if ($geturl == 1) {
                switch ($plugin) {
                    case 1:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 1]);
                        $v['page_path']  = 'pages/subPages/goods/index?type=1';
                        break;//抢购商品
                    case 2:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 2]);
                        $v['page_path']  = 'pages/subPages/goods/index?type=2';
                        break;//团购商品
                    case 3:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 3]);
                        $v['page_path']  = 'pages/subPages/goods/index?type=3';
                        break;//拼团商品
                    case 5:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 5]);
                        $v['page_path']  = 'pages/subPages/goods/index?type=5';
                        break;//优惠券
                    case 7:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index' , ['id' => $v['id'] , 'type' => 7]);
                        $v['page_path']  = 'pages/subPages/goods/index?type=7';
                        break;//砍价商品
                }
            }
        }

        include wl_template('utility/selecetgoods');
    }
    /**
     * Comment: 用户选择器
     * Author: zzw
     * Date: 2019/11/29 17:30
     */
    public function selectUserInfo(){
        global $_W , $_GPC;
        #1、参数获取
        $getType = $_GPC['get_type'] ? : 'main';//main=主要信息；list=仅列表信息
        $search  = $_GPC['search'] ? : '';
        $params = $_GPC['params'] ? : '';// data-params='{"is_salesman":1}'>
        $list    = [];
        if (!empty($search)) {
            #1、条件生成
            $where = " WHERE uniacid = {$_W['uniacid']} ";
            $where .= " AND ( nickname LIKE '%{$search}%' OR id = '{$search}' OR realname LIKE '%{$search}%' OR mobile LIKE '%{$search}%' )";
            $params = json_decode(base64_decode($params),true);
            if($params){
                //获取业务员信息
                if($params['is_salesman'] == 1){
                    $members = pdo_getall(PDO_NAME.'distributor', ['uniacid' => $_W['uniacid'], 'disflag' => 1], ['mid']);
                    $ids = implode(array_column($members,'mid'),',');
                    $where .= " AND id IN ($ids) ";
                }
            }
            #2、获取用户信息
            $field = " id,nickname,avatar,realname,mobile,openid,wechat_openid,webapp_openid ";
            $list  = pdo_fetchall(" SELECT {$field} FROM " . tablename(PDO_NAME . "member") . $where . " ORDER BY createtime ASC LIMIT 0,30 ");
        }

        $params = base64_encode(json_encode($_GPC['params']));


        include wl_template($getType == 'main' ? 'utility/select_user' : 'utility/select_user_tpl');
    }
    /**
     * Comment: 商户选择器
     * Author: zzw
     * Date: 2020/12/1 17:14
     */
    public function selectShopInfo(){
        global $_W , $_GPC;
        #1、参数获取
        $getType = $_GPC['get_type'] ? : 'main';//main=主要信息；list=仅列表信息
        $search  = $_GPC['search'] ? : '';
        $params = $_GPC['params'] ? : '';// data-params='{"is_salesman":1}'>
        if (!empty($search)) {
            #1、条件生成
            $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
            $where .= " AND ( storename LIKE '%{$search}%' OR id = '{$search}' OR mobile LIKE '%{$search}%' )";
            $params = json_decode(base64_decode($params),true);
            if($params){
                //获取业务员信息
                if($params['housestatus'] == 1){
                    $where .= " AND housestatus = 1";
                }

            }
            #2、获取商户信息
            $field = " id,storename,mobile,logo,address";
            $order = " ORDER BY createtime ASC LIMIT 0,30 ";
            $sql   = " SELECT {$field} FROM " . tablename(PDO_NAME . "merchantdata");
            $list  = pdo_fetchall($sql.$where.$order);
        }
        $params = base64_encode(json_encode($_GPC['params']));


        include wl_template($getType == 'main' ? 'utility/select_shop' : 'utility/select_shop_tpl');
    }
    /**
     * Comment: 获取图文抓取模板
     * Author: zzw
     * Date: 2019/12/24 16:08
     */
    public function importTextModel(){
        global $_W,$_GPC;

        include wl_template('utility/import');
    }
    /**
     * Comment: 通过链接获取商品详情
     * Author: zzw
     * Date: 2019/12/24 14:51
     */
    public function importTextInfo(){
        global $_W , $_GPC;
        #1、参数获取
        $url = $_GPC['url'] OR Commons::sRenderError("url错误!");
        #2、抓取内容
        $result = file_get_contents($url);
        if (empty($result['contents'])) {
            $result = (new GatherArticle())->get_caiji($url);
        }
        #2、抓取内容
        if (!empty($result['contents'])) {
            $result['contents'] = htmlspecialchars_decode($result['contents']);
            Commons::sRenderSuccess('内容' , $result);
        } else {
            Commons::sRenderError('获取失败，请检查链接是否可用！');
        }
    }
    /**
     * Comment: 地图定位选择坐标
     * Author: zzw
     * Date: 2020/9/8 16:22
     */
    public function mapPositioning(){
        global $_W,$_GPC;
        //基本信息获取
        $address    = $_GPC['address'] ? : '';
        $lat        = $_GPC['lat'] ? : '39.90960456049752';
        $lng        = $_GPC['lng'] ? : '116.3972282409668';
        $zoom       = 12;
        $tencentkey = $_W['wlsetting']['api']['txmapkey'];
        if(!$address || !$lat || !$lng){
            //未传递默认信息  则根据ip自动获取
            $area = MapService::guide_ip($_W['clientip']);
            if (!is_error($area)) {
                $lat = $area['result']['location']['lat'];
                $lng = $area['result']['location']['lng'];
                $location = $area['result']['ad_info']['city'];
            }
        }else{
            //通过经纬度获取城市信息
            $area = MapService::guide_gcoder($lat . ',' . $lng , 0);
            $location = $area['result']['ad_info']['city'];
        }

        include wl_template('utility/select_address');
    }
    /**
     * Comment: 红包选择器 —— 红包列表
     * Author: zzw
     * Date: 2020/9/14 14:58
     */
    public function selectRedPack(){
        global $_W,$_GPC;
        //基本参数信息获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = trim($_GPC['name']) ? : '';
        //获取红包列表信息
        if($_W['ispost']){
            //条件生成
            $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND 
             CASE WHEN usetime_type = 0 AND use_end_time <= unix_timestamp(now()) THEN 2
                  ELSE status
             END = 1";
            if ($title) $where .= " AND title LIKE '%" . $title . "%'";
            //sql语句生成
            $field = "id,title,use_start_time,use_end_time,usetime_day1,usetime_day2,usetime_type,all_count,limit_count,full_money,cut_money";
            $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."redpacks");
            $order = " ORDER BY createtime DESC ";
            $limit = " LIMIT {$pageStart},{$pageIndex} ";
            //信息获取
            $list = pdo_fetchall($sql.$where.$order.$limit);
            foreach ($list as $key => &$val) {
                $usetimes            = [
                    date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                    '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                    '领取次日起' . $val['usetime_day2'] . '天内有效'
                ];
                $val['usetime_text'] = $usetimes[$val['usetime_type']];
                $val['all_count']    = $val['all_count'] ? $val['all_count'] . '个' : '无限';
                $val['limit_count']  = $val['limit_count'] ? $val['limit_count'] . '个' : '无限';
                $val['get_count']    = pdo_getcolumn(PDO_NAME.'redpack_records' , ['packid' => $val['id']] , 'COUNT(id)');
                //删除多余的信息
                unset($val['use_start_time'],$val['use_end_time'],$val['usetime_day1'],$val['usetime_day2'],$val['usetime_type']);
            }
            //获取红包信息总数
            $totalSql = str_replace($field,'count(*)',$sql);
            $total = pdo_fetchcolumn($totalSql.$where);

            Commons::sRenderSuccess('红包列表',['list'=>$list,'total'=>ceil($total / $pageIndex)]);
        }

        include wl_template('utility/select_red_pack');
    }
    /**
     * Comment: 获取抽奖奖品信息列表
     * Author: zzw
     * Date: 2020/9/16 11:05
     */
    public function selectDrawPrize(){
        global $_W,$_GPC;
        if($_W['ispost']){
            //基本参数信息获取
            $page      = max(1 , intval($_GPC['page']));
            $pageIndex = 10;
            $pageStart = $page * $pageIndex - $pageIndex;
            $title     = trim($_GPC['name']) ? : '';
            //条件生成
            $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}";
            if ($title) $where .= " AND title LIKE '%{$title}%'";
            //基本sql语句生成  列表信息获取及处理
            $field = "id,type,title,image,probability,status,day_number,total_number,create_time";
            $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_goods");
            $order = " ORDER BY create_time DESC,id DESC ";
            $limit = " LIMIT {$pageStart},{$pageIndex} ";
            $list = pdo_fetchall($sql.$where.$order.$limit);
            foreach($list as $key => &$val){
                //基本信息处理
                $val['image'] = tomedia($val['image']);
                $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
                //提供数量处理
                $val['day_number'] = ($val['day_number'] == 0) ? '无限制' : $val['day_number']."份" ;
                $val['total_number'] = ($val['total_number'] == 0) ? '无限制' : $val['total_number']."份" ;
            }
            //获取总数信息  数据分页信息获取
            $totalSql = str_replace($field,'count(*)',$sql);
            $total = pdo_fetchcolumn($totalSql.$where);

            Commons::sRenderSuccess('抽奖奖品列表' , ['list' => $list , 'total' => ceil($total / $pageIndex)]);
        }

        include wl_template('utility/select_draw_prize');
    }
    /**
     * Comment: 求职招聘 - 获取企业信息列表(企业选择器)
     * Author: zzw
     * Date: 2020/12/8 17:28
     */
    public function selectEnterprise(){
        global $_W,$_GPC;
        //参数信息获取
        $search = $_GPC['search'];
        $returnType = $_GPC['return_type'] ? : 'html';
        //条件生成
        $where = '';
        if($search) $where = " AND (storename LIKE '%{$search}%' OR id LIKE '%{$search}%' OR mobile LIKE '%{$search}%') ";
        $field = "id,logo,storename,recruit_nature_id,recruit_scale_id,recruit_industry_id,provinceid,areaid,distid";
        $limit = " limit 0,30 ";
        [$list,$total] = Recruit::getEnterpriseList($where,$field,'',$limit);
        //信息返回
        if($returnType == 'json') wl_json(1,'企业列表',$list);
        //else wl_template('');
    }
    
    
    /**
     * Comment: 掌上信息 - 帖子列表(帖子选择器)
     * Author: wlf
     * Date: 2022/11/02 18:05
     */
    public function selectInformations(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search = $_GPC['search'];
        $returnType = $_GPC['return_type'] ? : 'json';
        $set = Setting::agentsetting_read('pocket');
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($search) $where = " AND content LIKE '%{$search}%'  ";
        $field = "id,content,mid,status,top,look,likenum,share,onetype,type,refreshtime,nickname,avatar,img";
        $limit = " LIMIT {$pageStart},{$pageIndex}";
        
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename('wlmerchant_pocket_informations').$where." ORDER BY top DESC,refreshtime DESC".$limit);
        $toall = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_pocket_informations').$where);
        $allpage = ceil($toall/10);
		if(!empty($list)){
			foreach($list as &$li){
				if($li['mid'] > 0){
					$member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('nickname','encodename','avatar'));
					$li['nickname'] = $member['nickname'];
					$li['avatar'] = tomedia($li['avatar']);
				}
				if(empty($li['nickname'])){
					$li['nickname'] = $set['kefu_name'];
					$li['avatar'] = tomedia($set['kefu_avatar']);
				}
				if($li['type'] > 0){
					$li['type'] = pdo_getcolumn(PDO_NAME.'pocket_type',array('id'=>$li['type']),'title');
				}else{
					$li['type'] = '官方公告';
				}
				if($li['onetype'] > 0){
					$li['onetype'] = pdo_getcolumn(PDO_NAME.'pocket_type',array('id'=>$li['onetype']),'title');
				}else{
					$li['onetype'] = '公告';
				}
				if(!empty($li['img'])){
					$li['img'] = beautifyImgInfo($li['img']);
				}else{
					$li['img'] = [];
				}
				$li['refreshtime'] = date('Y-m-d H:i',$li['refreshtime']);
			}
		}        
        $data = [
        	'list' => $list,
        	'page_number' => $allpage
        ];
        //信息返回
        if($returnType == 'json') wl_json(1,'帖子列表信息',$data);
        //else wl_template('');
    }
    
     /**
     * Comment: 自定义表单选择器
     * Author: wlf
     * Date: 2022/11/09 16:54
     */
    public function selectDiyForm(){
        global $_W,$_GPC;
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search = $_GPC['search'];
        $returnType = $_GPC['return_type'] ? : 'json';
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($search) $where = " AND title LIKE '%{$search}%'  ";
        $field = "id,sid,title,info,create_time,update_time";
        $limit = " LIMIT {$pageStart},{$pageIndex}";
        
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename('wlmerchant_diyform').$where." ORDER BY id DESC".$limit);
        $toall = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_diyform').$where);
        $allpage = ceil($toall/10);
        
        if(!empty($list)){
			foreach($list as &$li){
				$li['diyform'] = json_decode(base64_decode($li['info']), true);//页面的配置信息
				if($li['sid'] > 0){
					$li['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$li['sid']),'storename');
				}else{
					$li['storename'] = '代理表单';
				}
				$li['create_time'] = date('Y-m-d H:i',$li['create_time']);
				$li['update_time'] = $li['update_time'] ? date('Y-m-d H:i',$li['update_time']) :  date('Y-m-d H:i',$li['create_time']);
				unset($li['info']);
			}
		}
		$data = [
        	'list' => $list,
        	'page_number' => $allpage
        ];
        //信息返回
        if($returnType == 'json') wl_json(1,'表单列表信息',$data);
    }
    
    
    /**
     * Comment: 求职招聘 - 获取招聘信息列表(招聘选择器)
     * Author: zzw
     * Date: 2020/12/9 15:36
     */
    public function selectRecruit(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search = $_GPC['search'];
        $returnType = $_GPC['return_type'] ? : 'html';
        //条件生成
        $where = " AND status IN (4,5) ";
        if($search) $where .= " AND title LIKE '%{$search}%' ";
        $field = "id,title,recruitment_type,release_mid,release_sid,job_type,full_type,full_salary_min,full_salary_max,
        welfare,part_type,part_salary,part_settlement,work_province,work_city,work_area,status,create_time,is_top";
        $limit = " limit {$pageStart},{$pageIndex} ";
        [$list,$total] = Recruit::getRecruitList($where,$field,'',$limit);
        foreach($list as &$del){
            unset($del['position_id'] , $del['release_mid'] , $del['release_sid'] , $del['full_type'] , $del['full_salary_min'] , $del['full_salary_max'] , $del['welfare'] , $del['part_type'] , $del['part_salary'] , $del['part_settlement'] , $del['work_province'] , $del['work_city'] , $del['work_area'] , $del['create_time']);
        }
        //信息返回
        if($returnType == 'json') wl_json(1,'招聘列表',['list'=>$list,'page'=>$page,'page_number'=>ceil($total / $pageIndex)]);
        //else wl_template('');
    }
    /**
     * Comment: 求职招聘 - 获取简历信息列表(简历选择器)
     * Author: zzw
     * Date: 2020/12/10 11:07
     */
    public function selectResume(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $search = $_GPC['search'];
        $returnType = $_GPC['return_type'] ? : 'html';
        //条件生成
        $where = [];
        if($search) $where['name LIKE'] .= "%{$search}%";
        $field = [
            'id' ,
            'name' ,
            'phone' ,
            'avatar' ,
            'gender' ,
            'work_status' ,
            'experience_label_id' ,
            'education_label_id' ,
            'birth_time' ,
            'expect_position' ,
            'job_type' ,
            'expect_salary_min' ,
            'expect_salary_max' ,
            'expect_work_province',
            'expect_work_city',
            'expect_work_area'
        ];
        [$list,$total] = Recruit::getResumeList($where,$field,'id DESC',[$page,$pageIndex]);
        foreach($list as &$del){
            unset($del['work_status'],$del['experience_label_id'],$del['education_label_id'],$del['birth_time'],$del['expect_salary_min'],$del['expect_salary_max'], $del['expect_work_province'],$del['expect_work_city'],$del['expect_work_area'],$del['expect_position']);
        }
        //信息返回
        if($returnType == 'json') wl_json(1,'招聘列表',['list'=>$list,'page'=>$page,'page_number'=>ceil($total / $pageIndex)]);
        //else wl_template('');
    }
    /**
     * Comment: 相亲交友 - 获取会员信息列表
     * Author: zzw
     * Date: 2021/3/17 17:05
     */
    public function selectDating(){
        global $_W,$_GPC;
        //参数信息获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search    = $_GPC['search'];
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($search) $where .= " AND (b.nickname LIKE '%{$search}%' OR a.real_name LIKE '%{$search}%')";
        //列表信息获取
        $field = "a.id,a.mid,a.gneder,a.birth,a.current_province,a.current_city,a.current_area,a.live,a.travel,a.pv,a.is_top,a.cover";
        $order = " ORDER BY create_time DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        $list  = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$item){
            //获取用户信息
            [$item['nickname'],$item['avatar']] = Dating::handleUserInfo($item['mid']);
            //处理区域信息  只取最小一级
            if ($item['current_area']) $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['current_area']],'name');
            else if ($item['current_city']) $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['current_city']],'name');
            else if ($item['current_province']) $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['current_province']],'name');
            //判断是否为vip
            [$item['is_vip'],$numTime] = Dating::isVip($item['mid']);
            //封面图
            $item['cover'] = tomedia($item['cover']);
            //年龄获取
            $item['age'] = Dating::getAge(date("Y-m-d",$item['birth']));
            //删除多余的信息
            unset($item['birth'],$item['current_province'],$item['current_city'],$item['current_area']);
        }
        //总数获取
        //获取总数信息  数据分页信息获取
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);

        //信息返回
        wl_json(1,'招聘列表',['list'=>$list,'page'=>$page,'page_number'=>ceil($total / $pageIndex)]);
    }
    /**
     * Comment: 家政服务选择器
     * Author: zzw
     * Date: 2021/4/30 17:47
     */
    public function selectHouseKeep(){
        global $_W,$_GPC;
        //参数信息获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $search    = $_GPC['search'];
        //基本信息生成
        $shopWhere = " AND enabled = 1 AND housekeepstatus = 1  ";
        $artificerWhere = $demandWhere = $serviceWhere = " AND status = 1 ";
        if($search){
            //商户名称
            $shopWhere .= " AND storename LIKE '%{$search}%' ";
            //师傅姓名
            $artificerWhere .= " AND name LIKE '%{$search}%' ";
            //需求类型
            $typeListSql = "SELECT id FROM ".tablename(PDO_NAME."housekeep_type")
                ." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE '%{$search}%' ";
            $typeList = pdo_fetchall($typeListSql);
            if(count($typeList) > 0) {
                $typeIds = array_column($typeList,'id');
                $typeIds = implode($typeIds,',');
                $demandWhere .= " AND type IN ({$typeIds}) ";
            }else{
                //无分类被查询出来  强制查询为空
                $demandWhere .= " AND type = -1 ";
            }
            //服务标题
            $serviceWhere .= " AND title LIKE '%{$search}%' ";
        }
        //获取列表信息
        $data = Housekeep::getList(0,$page,$pageIndex,$shopWhere,$artificerWhere,$demandWhere,$serviceWhere);

        //信息返回
        wl_json(1,'服务列表',$data);
    }







}
