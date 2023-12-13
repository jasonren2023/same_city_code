<?php
defined('IN_IA') or exit('Access Denied');
class StoreModuleUniapp extends Uniapp
{

    public function __construct(){
        global $_W , $_GPC;
        $apiList = ['storeInfo','storeNewList','orderList','storeMember'];
        if(in_array($_GPC['do'],$apiList)){
            //判断资格
            $where = " WHERE uniacid = {$_W['uniacid']} AND storeid = {$_GPC['storeid']} AND mid = {$_W['mid']} AND ismain != 2 AND enabled = 1 ";
            $flag = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_merchantuser') .$where);
            if (empty($flag)) {
                $this->renderError('您无权管理该商户',['url'=>h5_url('pages/mainPages/index/index')]);
            }
        }
        //判断过期
        $overdueApiList = ['createGoods','changeGoodsStatus','commentReply','saveGoods','addDynamicPage','addDynamic','checkcodeCash','commentReply'];
        if(in_array($_GPC['do'],$overdueApiList)){
            $enabled = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$_GPC['storeid']),'enabled');
            if($enabled == 2){
                $this->renderError('商户暂停营业中，无法进行此操作');
            }else if($enabled == 3){
                $this->renderError('商户已过期，无法进行此操作');
            }else if($enabled == 4){
                $this->renderError('商户已删除，无法进行此操作');
            }else if($enabled == 5){
                $this->renderError('商户审核中，无法进行此操作');
            }else if($enabled == 6){
                $this->renderError('商户未在入驻中，无法进行此操作');
            }
        }
    }

    /**
     * Comment: 获取商户列表信息
     * Author: zzw
     * Date: 2019/8/6 15:41
     */
    public function homeList()
    {
        global $_W , $_GPC;
        #1、获取参数/设置信息
        $set        = Setting::agentsetting_read("pluginlist");
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng        = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度 104.0091133118 经度
        $lat        = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度 30.5681964123  纬度
        $order      = $_GPC['order'] ? $_GPC['order'] : $set['sjsort'];//排序方式
        $cateOne    = $_GPC['cate_one'] ? : 0;//一级分类id
        $cateTwe    = $_GPC['cate_two'] ? : 0;//二级分类id
        $regionId   = $_GPC['region_id'] ? : 0;
        $search     = trim($_GPC['search']); //搜索 1520定制
//        if ($regionId > 0) $getAid = pdo_getcolumn(PDO_NAME . "oparea" , [
//            'areaid'  => $regionId ,
//            'status'  => 1 ,
//            'uniacid' => $_W['uniacid']
//        ] , 'aid');
        $aid      = $_W['aid'];
        $is_total = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        #2、生成基本条件

        if(empty($search)){
            if($regionId > 0){
                $getShopWhere = " WHERE uniacid = {$_W['uniacid']} AND ( provinceid = {$regionId} OR areaid = {$regionId} OR distid = {$regionId} ) AND status = 2 AND enabled = 1 AND listshow = 0 ";
            }else{
                $getShopWhere = " WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} AND status = 2 AND enabled = 1 AND listshow = 0 ";
            }

            if ($cateOne > 0) {
                $cateWhere = " WHERE onelevel = {$cateOne} ";
                if ($cateTwe > 0) $cateWhere .= "  AND twolevel = {$cateTwe} ";
                $ids          = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . "merchant_cate") . $cateWhere);
                $getShopWhere .= " AND id in (" . implode(',' , array_column($ids , 'sid')) . ") ";
            }
        }else{
            if($regionId > 0){
                $getShopWhere = " AND uniacid = {$_W['uniacid']} AND ( provinceid = {$regionId} OR areaid = {$regionId} OR distid = {$regionId} ) AND status = 2 AND enabled = 1 AND listshow = 0 ";
            }else{
                $getShopWhere = " AND uniacid = {$_W['uniacid']} AND aid = {$aid} AND status = 2 AND enabled = 1 AND listshow = 0 ";
            }
            //分类
            $sercate = pdo_get(PDO_NAME.'category_store',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'name' => $search),['id','parentid']);
            if(!empty($sercate)){
                if($sercate['parentid'] > 0 ){
                    $ids = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . "merchant_cate") . " WHERE twolevel = {$sercate['id']}");
                }else{
                    $ids = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . "merchant_cate") . " WHERE onelevel = {$sercate['id']}");
                }
            }
            if(empty($ids)){
                $getShopWhere = " WHERE ( storename LIKE '%{$search}%' OR `describe` LIKE '%{$search}%' )   " .$getShopWhere;
            }else{
                $getShopWhere = " WHERE ( storename LIKE '%{$search}%' OR `describe` LIKE '%{$search}%' OR id in (" . implode(',' , array_column($ids , 'sid')) . ") )   " .$getShopWhere;
            }
        }
        #2、根据排序规则获取数据信息
        if ($order == 2) {
            //按照距离排序
            $list = pdo_fetchall("SELECT id,location FROM " . tablename(PDO_NAME . "merchantdata") . $getShopWhere);
            foreach ($list as $index => &$item) {
                $location       = unserialize($item['location']);
                $item['length'] = intval(Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat));
            }
            $length = array_column($list , 'length');
            array_multisort($length , SORT_ASC , $list);
            $list = array_splice($list , $page_start , $page_index);
        }
        else {
            //获取排序方式    1 = 创建时间，2 = 店铺距离，3 = 默认设置，4 = 浏览人气
            switch ($order) {
                case 1:
                    $getShopWhere .= " ORDER BY createtime DESC ";
                    break;
                case 3:
                    $getShopWhere .= " ORDER BY listorder DESC,id DESC ";
                    break;
                case 4:
                    $getShopWhere .= " ORDER BY pv DESC ";
                    break;
            }
            $list = pdo_fetchall("SELECT id FROM " . tablename(PDO_NAME . "merchantdata") . $getShopWhere . " LIMIT {$page_start},{$page_index} ");
        }
        #3、获取最新的商户信息
        foreach ($list as $key => &$val) {
            $goodsInfo = pdo_get(PDO_NAME . "merchantdata" , ['id' => $val['id']] , [
                'id' ,
                'storename' ,
                'logo' ,
                'address' ,
                'storehours' ,
                'location' ,
                'pv' ,
                'score' ,
                'panorama' ,
                'videourl' ,
                'tag',
                'deliverystatus',
                'hotelstatus'
            ]);
            //获取店铺分类信息
            $goodsInfo['panorama'] = !empty($goodsInfo['panorama']) ? 1 : 0;
            $goodsInfo['videourl'] = !empty($goodsInfo['videourl']) ? 1 : 0;
            //店铺标签
            $goodsInfo['tags'] = [];
            $tagids            = unserialize($goodsInfo['tag']);
            if (!empty($tagids)) {
                $tags              = pdo_getall('wlmerchant_tags' , ['id' => $tagids] , ['title']);
                $goodsInfo['tags'] = $tags ? array_column($tags , 'title') : [];
            }
            unset($goodsInfo['tag']);
            //获取店铺信息地址跳转链接
            $url                   = h5_url('pages/mainPages/store/index' , ['sid' => $goodsInfo['id']]);
            $goodsInfo['shop_url'] = $url;
            //处理图片信息
            $goodsInfo['logo'] = tomedia($goodsInfo['logo']);
            //处理营业时间
            $storehours              = unserialize($goodsInfo['storehours']);
            if(!empty($storehours['startTime'])){
                $goodsInfo['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime']. "  营业";
            }else{
                $goodsInfo['storehours'] = '';
                foreach($storehours as $hk => $hour){
                    if($hk > 0){
                        $goodsInfo['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                    }else{
                        $goodsInfo['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                    }
                }
                $goodsInfo['storehours'] .= "  营业";
            }
            //处理店铺距离
            $location            = unserialize($goodsInfo['location']);
            $goodsInfo['length'] = Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
            if ((!empty($goodsInfo['length']) || is_numeric($goodsInfo['length'])) && $lng && $lat) {
                if ($goodsInfo['length'] > 9999998) {
                    $goodsInfo['distance'] = " ";
                }
                else if ($goodsInfo['length'] > 1000) {
                    $goodsInfo['distance'] = (floor(($goodsInfo['length'] / 1000) * 10) / 10) . "km";
                }
                else {
                    $goodsInfo['distance'] = intval($goodsInfo['length']) . "m";
                }
            }
            //获取店铺分类信息
            $storecates = pdo_getall('wlmerchant_merchant_cate',array('sid' => $val['id'],'twolevel >' => 0),array('twolevel'));
            $goodsInfo['catename'] = '';
            if(!empty($storecates)){
                foreach ($storecates as $ke => $cate){
                    $catename = pdo_getcolumn(PDO_NAME.'category_store',array('id'=>$cate['twolevel']),'name');
                    if($ke > 0){
                        $goodsInfo['catename'] .= '|'.$catename;
                    }else{
                        $goodsInfo['catename'] .= $catename;
                    }
                }
            }
            unset($val['location']);
            //查询认证和保证金
            if(p('attestation')){
                $goodsInfo['attestation'] = Attestation::checkAttestation(2,$goodsInfo['id']);
            }else{
                $goodsInfo['attestation'] = 0;
            }


            $val = $goodsInfo;
        }
        #5、获取店铺商品活动信息
        $list = WeliamWeChat::getStoreList($list);
        #5、判断是否获取获取总页数
        if ($is_total == 1) {
            $total         = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "merchantdata") . $getShopWhere);
            $data['list']  = $list;
            $data['total'] = ceil($total / $page_index);
            $this->renderSuccess('店铺列表' , $data);
        }
        $this->renderSuccess('店铺列表' , $list);
    }
    /**
     * Comment: 获取商户入驻信息
     * Author: wlf
     * Date: 2019/8/7 16:11
     */
    public function storeSettled()
    {
        global $_W , $_GPC;
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        $id = $_GPC['id'];
        if($storeSet['storeSetted'] > 0 && empty($id)){
            $this->renderError('功能已关闭，无法进行此操作',['url'=>h5_url('pages/mainPages/index/index')]);
        }
        //获取商户信息
        $data = ['havestore' => 0];
        if (!empty($id)) {
            $store      = pdo_get('wlmerchant_merchantdata' , ['id' => $id]);
            $_W['aid']  = $store['aid'];
            $user       = pdo_get('wlmerchant_merchantuser' , ['storeid' => $id] , ['name' , 'mobile']);
            $location   = unserialize($store['location']);
            $storehours = unserialize($store['storehours']);
            $adv        = unserialize($store['adv']);
            $recruitAdv        = unserialize($store['recruit_adv']);
            if ($adv) {
                foreach ($adv as $key => &$aa) {
                    $aa = tomedia($aa);
                }
            } else {
                $adv = [];
            }
            if ($recruitAdv) {
                foreach ($recruitAdv as $recruitAdvKey => &$recruitAdvVal) {
                    $recruitAdvVal = tomedia($recruitAdvVal);
                }
            } else {
                $recruitAdv = [];
            }
            $album = unserialize($store['album']);
            if ($album) {
                foreach ($album as $key => &$ll) {
                    $ll = tomedia($ll);
                }
            }
            else {
                $album = [];
            }
            $examineimg = unserialize($store['examineimg']);
            if ($examineimg) {
                foreach ($examineimg as $key => &$ee) {
                    $ee = tomedia($ee);
                }
            }
            else {
                $examineimg = [];
            }
            $data['store'] = [
                'storename'           => $store['storename'],
                'name'                => $user['name'],
                'mobile'              => $user['mobile'],
                'provinceid'          => $store['provinceid'],
                'areaid'              => $store['areaid'],
                'distid'              => $store['distid'],
                'address'             => $store['address'],
                'lat'                 => $location['lat'],
                'lng'                 => $location['lng'],
                'logo'                => tomedia($store['logo']),
                'adv'                 => $adv,
                'album'               => $album,
                'examine'             => $examineimg,
                'introduction'        => htmlspecialchars_decode($store['introduction']),
                'recruit_switch'      => $store['recruit_switch'] ? : 0,//是否开启求职招聘功能：0=关闭，1=开启
                'recruit_nature_id'   => $store['recruit_nature_id'] ? : 0,//企业性质id
                'recruit_scale_id'    => $store['recruit_scale_id'] ? : 0,//企业性质id
                'recruit_industry_id' => $store['recruit_industry_id'] ? : 0,//企业行业id
                'recruit_adv'         => $recruitAdv,//企业幻灯片数组
                'housekeepstatus'     => $store['housekeepstatus'],
                'verkey'              => $store['verkey'],
                'deliverystatus'      => $store['deliverystatus'],
                'acceptstatus'        => $store['acceptstatus'],
                'deliverymoney'       => $store['deliverymoney'],
                'deliverydistance'    => $store['deliverydistance'],
                'lowdeliverymoney'    => $store['lowdeliverymoney'],
                'housestatus'         => $store['housestatus'],
                'hotelstatus'         => $store['hotelstatus'],
                'hotellabel'          => unserialize($store['hotellabel']) ? : []
            ];
            if(!empty($store['deliverytype'])){
                $data['store']['deliverytype'] = unserialize($store['deliverytype']);
            }else{
                $data['store']['deliverytype'] = [];
            }
            if(empty($storehours)){
                $hour['startTime'] = '00:00';
                $hour['endTime'] = '00:00';
                $data['store']['storehours'][] = $hour;
            }else{
                if(!empty($storehours['startTime'])){
                    $hour['startTime'] = $storehours['startTime'];
                    $hour['endTime'] = $storehours['endTime'];
                    $data['store']['storehours'][] = $hour;
                }else{
                    $data['store']['storehours'] = $storehours;
                }
            }
            //分类信息获取
            $storeonecate = pdo_fetchall('select distinct onelevel from ' . tablename(PDO_NAME.'merchant_cate')." WHERE  sid = {$id} ");
            $storeonecate = array_column($storeonecate,'onelevel');
            $storetwocate = pdo_fetchall('select distinct twolevel from ' . tablename(PDO_NAME.'merchant_cate')." WHERE  sid = {$id} ");
            $storetwocate = array_column($storetwocate,'twolevel');
        }
        else {
            //判断是否已有店铺
            $havestore = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                'mid'     => $_W['mid'] ,
                'ismain'  => [1 , 3 , 4] ,
                'uniacid' => $_W['uniacid']
            ] , 'id');
            if ($havestore) {
                $data['havestore'] = 1;
            }
        }
        //获取地区信息
        if ($_W['aid']) {
            $areaid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'aid'     => $_W['aid']
            ] , 'areaid');
        }
        else {
            $areaid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'aid' => 0] , 'areaid');
        }
        if($areaid<110100){
            $areaid = 110100;
        }
        $area = pdo_get('wlmerchant_area' , ['id' => $areaid] , ['pid' , 'name' , 'level']);
        if ($area['level'] == 3) {
            $data['distid']     = $areaid;
            $data['areaid']     = $area['pid'];
            $data['provinceid'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $data['areaid']] , 'pid');
        }
        else if ($area['level'] == 2) {
            $data['distid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
            $data['areaid']     = $areaid;
            $data['provinceid'] = $area['pid'];
        }
        else {
            $data['provinceid'] = $areaid;
            $data['areaid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
            $data['distid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $data['areaid']] , 'id');
        }
        $citylist = pdo_getall('wlmerchant_area' , ['level' => 1,'visible' => 2] , ['id' , 'name']);
        foreach ($citylist as $key => &$prov) {
            $prov['area'] = pdo_getall('wlmerchant_area' , ['level' => 2 , 'pid' => $prov['id'],'visible' => 2] , ['id' , 'name']);
            foreach ($prov['area'] as $k => &$area) {
                $area['dist'] = pdo_getall('wlmerchant_area' , ['level' => 3 , 'pid' => $area['id'],'visible' => 2] , ['id' , 'name']);
            }
        }
        $data['citylist'] = $citylist;
        //获取分类信息
        if (empty($_W['aid'])) {
            $_W['aid'] = 0;
        }
        $categoryes = pdo_getall(PDO_NAME . 'category_store' , [
            'uniacid'  => $_W['uniacid'] ,
            'aid'      => $_W['aid'] ,
            'parentid' => 0 ,
            'state'    => 0 ,
            'enabled'  => 1
        ] , ['id' , 'name'] , '' , 'displayorder DESC');
        if (!empty($categoryes)) {
            foreach ($categoryes as $cid => &$cate) {
                $cate['twotype'] = pdo_getall(PDO_NAME . 'category_store' , [
                    'parentid' => $cate['id'] ,
                    'uniacid'  => $_W['uniacid'] ,
                    'aid'      => $_W['aid'] ,
                    'enabled'  => 1
                ] , ['name' , 'id'] , '' , 'displayorder DESC');
                if(!empty($cate['twotype'])){
                    foreach($cate['twotype'] as &$twoc){
                        if(!empty($storetwocate) && in_array($twoc['id'],$storetwocate)){
                            $twoc['selectflag'] = 1;
                        }
                    }
                }
                if(!empty($storeonecate) && in_array($cate['id'],$storeonecate)){
                    $cate['selectflag'] = 1;
                }
            }
        }
        $data['typelist'] = $categoryes;
        //获取客户电话
        $settings            = Setting::wlsetting_read('base');
        $agentsettings       = Setting::agentsetting_read('agentcustomer');
        $data['systemphone'] = $agentsettings['tel'] ? : $settings['phone'];
        //入驻协议
        $register         = Setting::wlsetting_read('register');
        $data['describe'] = $register['detail'];
        //求职招聘准备信息
        $data['is_recruit'] = 0;//不存在招聘插件
        if(store_p('recruit',$id)){
            $data['nature'] = Recruit::getLabelList(5);//企业性质
            $data['scale'] = Recruit::getLabelList(4);//企业规模
            $data['industry'] = Recruit::getIndustryList(['pid'=>0],['id','title']);//上级行业列表
            $data['is_recruit'] = 1;//存在招聘插件
        }
        //家政准备信息
        $data['is_housekeep'] = 0;//不存在招聘插件
        if(store_p('housekeep',$id)){
            $data['store_housekeep_cate'] = Housekeep::getRelation($id,3,1);
            $data['all_housekeep_cate'] = Housekeep::getCategory();
            $data['is_housekeep'] = 1;//存在招聘插件
        }
        //同城配送准备
        $data['is_delivery'] = 0;//不存在同城配送
        if(store_p('citydelivery',$id)){
            $data['is_delivery'] = 1;//存在同城配送
        }

        //酒店准备
        $data['is_hotel'] = 0;//不存在酒店
        if(store_p('hotel',$id)){
            $data['is_hotel'] = 1;//存在酒店
            $data['hotellabel'] = pdo_getall('wlmerchant_hotel_label', array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 3), array('id', 'title'), '', 'sort DESC,id DESC');
        }
        //房产准备
        $data['is_house'] = 0;//不存在房产
        if(store_p('house',$id)){
            $data['is_house'] = 1;//存在房产
        }
        //获取商户入驻顶部图片
        $data['top_img'] = tomedia($storeSet['top_img']);
        $data['top_link'] = $storeSet['top_link'];

        $this->renderSuccess('商户信息' , $data);
    }
    /**
     * Comment: 通过地区id更换分类
     * Author: wlf
     * Date: 2019/8/7 18:30
     */
    public function area2type()
    {
        global $_W , $_GPC;
        $provinceid = $_GPC['provinceid'];
        $areaid     = $_GPC['areaid'];
        $distid     = $_GPC['distid'];
        $aid        = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $distid] , 'aid');
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $areaid] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $provinceid] , 'aid');
        }
        if (empty($aid)) {
            $aid = 0;
        }
        $categoryes = pdo_getall(PDO_NAME . 'category_store' , [
            'uniacid'  => $_W['uniacid'] ,
            'aid'      => $aid ,
            'parentid' => 0 ,
            'state'    => 0,
            'enabled'  => 1
        ] , ['id' , 'name'] , '' , 'displayorder DESC');
        if (!empty($categoryes)) {
            foreach ($categoryes as $cid => &$cate) {
                $cate['twotype'] = pdo_getall(PDO_NAME . 'category_store' , [
                    'parentid' => $cate['id'] ,
                    'uniacid'  => $_W['uniacid'] ,
                    'aid'      => $aid,
                    'state'    => 0,
                    'enabled'  => 1
                ] , ['name' , 'id'] , '' , 'displayorder DESC');
            }
        }
        $this->renderSuccess('商户分类信息' , $categoryes);
    }
    /**
     * Comment: 创建商户和店长
     * Author: wlf
     * Date: 2019/8/8 09:40
     */
    public function createStore()
    {
        global $_W , $_GPC;
        $set               = Setting::wlsetting_read('register');
        $data              = [];
        $data['storename'] = $_GPC['storename'];
        $sale_id = intval($_GPC['sale_id']);
        if (empty($data['storename'])) {
            $this->renderError('请输入商户名');
        }
        $textRes = Filter::init($data['storename'],$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError('商户名'.$textRes['message']);
        }
        $storeid = $_GPC['storeid'];
        //获取商户数据
        $data['realname'] = trim($_GPC['name']);
        $data['mobile']   = $_GPC['mobile'];
        $data['logo']     = $_GPC['logo'];
        $adv              = $_GPC['adv'];
        if (!empty($adv)) {
            $data['adv'] = serialize(explode(',' , $adv));
        }
        else {
            $data['adv'] = '';
        }
        $album = $_GPC['album'];
        if (!empty($album)) {
            $data['album'] = serialize(explode(',' , $album));
        }
        else {
            $data['album'] = '';
        }
        $examineimg = $_GPC['examine'];
        if (!empty($examineimg)) {
            $data['examineimg'] = serialize(explode(',' , $examineimg));
        }
        else {
            $data['examineimg'] = '';
        }
        $data['location']   = serialize(['lng' => $_GPC['lng'] , 'lat' => $_GPC['lat']]);
        $data['lng'] = $_GPC['lng'];
        $data['lat'] = $_GPC['lat'];
        $data['provinceid'] = $_GPC['provinceid'];
        $data['areaid']     = $_GPC['areaid'];
        $data['distid']     = $_GPC['distid'];
        $data['address']    = $_GPC['address'];
        $data['introduction'] = $_GPC['detail'];//店铺简介
        $data['verkey']       = $_GPC['verkey'];//核销密码

        //招聘
        $data['recruit_switch']      = $_GPC['recruit_switch'] ? : 0;//是否开启求职招聘功能：0=关闭，1=开启
        $data['recruit_nature_id']   = $_GPC['recruit_nature_id'] ? : 0;//企业性质
        $data['recruit_scale_id']    = $_GPC['recruit_scale_id'] ? : 0;//企业规模
        $data['recruit_industry_id'] = $_GPC['recruit_industry_id'];//企业行业
        $recruitAdv                  = $_GPC['recruit_adv'];
        $data['recruit_adv']         = $recruitAdv ? serialize(explode(',',$recruitAdv)) : '';//企业幻灯片
        //家政
        $data['housekeepstatus'] = $_GPC['housekeepstatus'];
        $housekeepcate = json_decode(base64_decode($_GPC['store_housekeep_cate']),true);
        $data['storehours'] = serialize(json_decode(base64_decode($_GPC['storehours']),true));
        //配送
        $data['deliverystatus'] = $_GPC['deliverystatus'];
        $data['acceptstatus'] = $_GPC['acceptstatus'];
        $data['deliverymoney'] = $_GPC['deliverymoney'];
        $data['deliverydistance'] = $_GPC['deliverydistance'];
        $data['lowdeliverymoney'] = $_GPC['lowdeliverymoney'];
        $data['deliverytype']  = $_GPC['deliverytype'] ? serialize(explode(',',$_GPC['deliverytype'])) : '';//配送方式
        //房产
        $data['housestatus'] = $_GPC['housestatus'];
        if ($storeid) {
            Tools::clearwxapp();
            Tools::clearposter();
            $storestatus = pdo_get(PDO_NAME . 'merchantdata' , ['id' => $storeid] , ['status' , 'aid']);
            $alflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$storestatus['aid']} AND storename = {$data['storename']} AND id != {$storeid}");
            if ($alflag) {
                $this->renderError('该商户已入驻，请更换商户名');
            }
            if ($storestatus['status'] == 3) {
                $data['status']     = 1;
                $userdata['status'] = 1;
                //发送消息
                $first   = '您好,' . $_GPC['name'] . '修改了新商家入驻信息';
                $type    = '店铺入驻资料修改';
                $content = '店铺名:[' . $data['storename'] . ']';
                $status  = '待审核';
                $remark  = '请尽快前往系统后台审核商家资料';
                News::noticeAgent('storesettled' , $storestatus['aid'] , $first , $type , $content , $status , $remark , time());
            }
            pdo_update(PDO_NAME . 'merchantdata' , $data , ['id' => $storeid]);
            //插入分类
            if(!empty($_GPC['cateidArray'])){
                pdo_delete('wlmerchant_merchant_cate' , ['sid' => $storeid]);
                $cateidArray = json_decode(base64_decode($_GPC['cateidArray']),true);
                foreach ($cateidArray as $cate){
                    $resss = pdo_insert('wlmerchant_merchant_cate' , [
                        'sid'      => $storeid ,
                        'onelevel' => intval($cate['onelevel']) ,
                        'twolevel' => intval($cate['twolevel'])
                    ]);
                }
            }
            $userdata['name']   = $_GPC['name'];
            $userdata['mobile'] = $_GPC['mobile'];
            pdo_update(PDO_NAME . 'merchantuser' , $userdata , ['storeid' => $storeid , 'ismain' => 1]);
            //处理家政服务分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 3,'objid' => $storeid));
            foreach ($housekeepcate as $item) {
                $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                pdo_insert('wlmerchant_housekeep_relation', ['type' => 3,'objid' => $storeid, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
            }

            $this->renderSuccess('修改商户成功' , $storeid);
        } else {
            //详情
            $data['introduction'] = $_GPC['introduction'];
            $storethumb           = $_GPC['thumbimages'];
            if ($storethumb) {
                $storethumb = explode(',' , $storethumb);
                foreach ($storethumb as $key => $th) {
                    $th                   = tomedia($th);
                    $data['introduction'] .= '<img src= "' . $th . '" />';
                }
            }
            if (!empty($data['introduction'])) {
                $data['introduction'] = htmlspecialchars_decode($data['introduction']);
            }
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
            //插入分类
            $cateidArray = json_decode(base64_decode($_GPC['cateidArray']),true);
            foreach ($cateidArray as $cate){
                $resss = pdo_insert('wlmerchant_merchant_cate' , [
                    'sid'      => $storeid ,
                    'onelevel' => intval($cate['onelevel']) ,
                    'twolevel' => intval($cate['twolevel'])
                ]);
            }
            //处理家政服务分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 3,'objid' => $storeid));
            foreach ($housekeepcate as $item) {
                $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                $res = pdo_insert('wlmerchant_housekeep_relation', ['type' => 3,'objid' => $storeid, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
            }
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
            //添加业务员
            if(!empty($sale_id > 0 && $_GPC['sale_id'] != $_W['mid'])){
                $_W['aid'] = $aid;
                $saleset = Setting::agentsetting_read('salesman');
                if($saleset['isopen']>0){
                    $salemember = pdo_get('wlmerchant_member',array('id' => intval($sale_id)),array('realname','nickname','mobile'));
                    $saledata = array(
                        'mid'          => $sale_id,
                        'storeid'      => $storeid,
                        'name'         => $salemember['realname'] ? $salemember['realname'] : $salemember['nickname'],
                        'mobile'       => $salemember['mobile'],
                        'enabled'      => 1,
                        'uniacid'      => $_W['uniacid'],
                        'aid'          => $aid,
                        'ismain'       => 4,
                        'createtime'   => time()
                    );
                    pdo_insert('wlmerchant_merchantuser', $saledata);
                }
            }
            $this->renderSuccess('创建商户成功' , $data);
        }
    }
    /**
     * Comment: 获取付费入驻列表
     * Author: wlf
     * Date: 2019/8/8 10:52
     */
    public function chargeList()
    {
        global $_W , $_GPC;
        $settings = Setting::wlsetting_read('register');
        $storeid  = $_GPC['storeid'];
        $store    = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , ['aid' , 'status']);
        if ($store['status'] == 2) {
            $chargetypes = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 AND renewstatus = 1 AND aid IN (0,{$store['aid']}) ORDER BY sort DESC");
        }
        else {
            $chargetypes = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 AND aid IN (0,{$store['aid']}) ORDER BY sort DESC");
        }
        $data['typelist'] = $chargetypes;
        $data['describe'] = $settings['description'];
        $this->renderSuccess('付费类型列表' , $data);
    }
    /**
     * Comment: 生成商户入驻付费订单
     * Author: wlf
     * Date: 2019/8/8 14:07
     */
    public function createChargeOrder()
    {
        global $_W , $_GPC;
        $storeid    = $_GPC['storeid'];
        $typeid     = $_GPC['typeid'];
        $store      = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid]);
        $chargetype = pdo_get('wlmerchant_chargelist' , ['id' => $typeid]);
        if (empty($chargetype)) {
            $this->renderError('请选择商户入驻类型');
        }
        if (empty($store)) {
            $this->renderError('商户ID未传入或已被删除');
        }
        //判断绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('charge' , $mastmobile)) {
            $this->renderError('未绑定手机号');
        }
        if (intval($chargetype['audits']) == 1) {
            pdo_update(PDO_NAME.'merchantdata',['audits' => 1],['id' => $storeid]);
        }else{
            pdo_update(PDO_NAME.'merchantdata',['audits' => 0],['id' => $storeid]);
        }
        if ($chargetype['price'] > 0) {
            $data            = [
                'uniacid'    => $store['uniacid'] ,
                'mid'        => $_W['mid'] ,
                'aid'        => $store['aid'] ,
                'fkid'       => $typeid ,
                'sid'        => $storeid ,
                'status'     => 0 ,
                'paytype'    => 0 ,
                'createtime' => time() ,
                'orderno'    => createUniontid() ,
                'price'      => $chargetype['price'] ,
                'num'        => $chargetype['days'] ,
                'plugin'     => 'store' ,
                'payfor'     => 'merchant'
            ];
            $res             = pdo_insert(PDO_NAME . 'order' , $data);
            $orderid         = pdo_insertid();
            $data['orderid'] = $orderid;
            $data['status']  = 1;
            $this->renderSuccess('订单生成成功' , $data);
        }
        else {
            $endtime = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'endtime');
            $groupid = $typeid;
            if ($endtime > time()) {
                $newendtime = $chargetype['days'] * 24 * 3600 + $endtime;
            }
            else {
                $newendtime = $chargetype['days'] * 24 * 3600 + time();
            }
            $merstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$storeid),'status');
            //audits 0=要审核  1=不审核
            if (intval($chargetype['audits']) == 1 || $merstatus == 2) {
                pdo_update(PDO_NAME . 'merchantdata' , [
                    'status'  => 2 ,
                    'endtime' => $newendtime ,
                    'enabled' => 1 ,
                    'audits'  => 1 ,
                    'groupid' => $groupid
                ] , ['id' => $storeid]); //更新商户
                pdo_update(PDO_NAME . 'merchantuser' , ['status' => 2] , ['storeid' => $storeid]); //更新管理员
            }else {
                pdo_update(PDO_NAME . 'merchantdata' , [
                    'status'  => 1 ,
                    'endtime' => $newendtime ,
                    'groupid' => $groupid
                ] , ['id' => $storeid]); //更新商户(待审核)
                $appname = pdo_getcolumn(PDO_NAME . 'merchantuser' , ['storeid' => $storeid , 'ismain' => 1] , 'name');
                $agentname = pdo_getcolumn(PDO_NAME . 'agentusers' , ['id' => $store['aid']] , 'agentname');
                $first = '您好,'. $appname . '申请了新商家入驻';
                $type = '店铺申请入驻';
                $content = '店铺名:['.$store['storename'] .']';
                $status = '待审核';
                $time = time();
                $remark = '入驻代理:['.$agentname.'],请尽快前往系统后台审核商家资料';
                News::noticeAgent('storesettled',$store['aid'],$first,$type,$content,$status,$remark,$time);
            }
            $data['status'] = 0;
            $this->renderSuccess('入驻成功' , $data);
        }
    }
    /**
     * Comment: 获取店员信息
     * Author: wlf
     * Date: 2019/8/8 15:34
     */
    public function adminInfo()
    {
        global $_W , $_GPC;
        $data       = [];
        $adminid    = $_GPC['adminid'];
        $storeid    = $_GPC['storeid'];
        $newadminid = $_GPC['newadminid'];
        if ($newadminid > 0) {
            $newadmin               = pdo_get('wlmerchant_member' , ['id' => $newadminid] , [
                'nickname' ,
                'avatar' ,
                'mobile'
            ]);
            $newadmin['name']       = $newadmin['nickname'];
            $newadmin['avatar']     = tomedia($newadmin['avatar']);
            $newadmin['enabled']    = 0;
            $newadmin['ismain']     = 2;
            $newadmin['orderwrite'] = 0;
            $newadmin['viewdata']   = 0;
            $data['admin']          = $newadmin;
            $this->renderSuccess('新店员数据' , $data);
        }
        else if ($adminid > 0) {
            $admin           = pdo_get('wlmerchant_merchantuser' , ['id' => $adminid] , [
                'mobile' ,
                'mid' ,
                'enabled' ,
                'name' ,
                'ismain' ,
                'orderwrite' ,
                'viewdata'
            ]);
            $admin['avatar'] = pdo_getcolumn(PDO_NAME . 'member' , ['id' => $admin['mid']] , 'avatar');
            $admin['avatar'] = tomedia($admin['avatar']);
            $data['admin']   = $admin;
            $this->renderSuccess('店员数据' , $data);
        }
        else {
            $storeSet = Setting::wlsetting_read('agentsStoreSet');
            if($storeSet['admin_apply'] > 0){
                $filename = md5('sid'.$storeid.'source'.$_W['source'].'adminapply');//保证图片唯一性，每种渠道，类型海报二维码都不一致
                $path = 'pages/subPages/merchant/bindingClerk/bindingClerk?sid='.$storeid;
                if ($_W['source'] == 3) {
                    $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
                    if (is_array($qrCodeLink)) $qrCodeLink = Poster::qrcodeimg($path , $filename);
                }else{
                    $path = h5_url($path);//非小程序渠道  基本路径转超链接
                    $qrCodeLink = Poster::qrcodeimg($path , $filename);
                }
                $data['src'] = tomedia($qrCodeLink) ;
            }else{
                $qrcode = time();
                if ($_W['source'] == 3) { //小程序
                    $showurl = 'pages/subPages/merchant/merchantSuccess/merchantSuccess?codes=' . $qrcode . '&storeid=' . $storeid;
                    $logo    = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'logo'));
                    $src     = tomedia(Store::getShopWxAppQrCode($storeid , $logo , $showurl));
                }
                else {
                    $showurl = h5_url('pages/subPages/merchant/merchantSuccess/merchantSuccess' , [
                        'codes'   => $qrcode ,
                        'storeid' => $storeid
                    ]); //暂无页面
                    $src     = WeliamWeChat::getQrCode($showurl);
                }
                $data['src']    = $src;
                $data['qrcode'] = $qrcode;
                pdo_insert(PDO_NAME . 'merchantuser_qrlog' , [
                    'uniacid'    => $_W['uniacid'] ,
                    'codes'      => $qrcode ,
                    'createtime' => $qrcode
                ]);
                pdo_delete(PDO_NAME . 'merchantuser_qrlog' , [
                    'uniacid'      => $_W['uniacid'] ,
                    'createtime <' => $qrcode - 300 ,
                    'status !='    => 1
                ]);
            }

            $this->renderSuccess('入驻二维码' , $data);
        }
    }
    /**
     * Comment: 店员扫码后页面
     * Author: wlf
     * Date: 2019/09/25 16:41
     */
    public function adminSurePage()
    {
        global $_W , $_GPC;
        $qrcode  = $_GPC['codes'];
        $storeid = $_GPC['storeid'];
        if (empty($qrcode) || empty($storeid)) {
            $this->renderError('缺少重要参数，请重新扫描二维码');
        }
        $store             = Store::getSingleStore($storeid);
        $data['storename'] = $store['storename'];
        $this->renderSuccess('扫码成功' , $data);
    }
    /**
     * Comment: 确认成为店员接口
     * Author: wlf
     * Date: 2019/09/25 16:53
     */
    public function adminSureAPI()
    {
        global $_W , $_GPC;
        $qrcode = $_GPC['codes'];
        if (empty($qrcode)) {
            $this->renderError('缺少重要参数，请重新扫描二维码');
        }
        $item     = pdo_get(PDO_NAME . 'merchantuser_qrlog' , ['uniacid' => $_W['uniacid'] , 'codes' => $qrcode]);
        $itemtime = $item['createtime'] + 300;
        if (empty($item) || $itemtime < time() || $item['status'] == 1) {
            $this->renderError('二维码过期,请刷新生成新的二维码');
        }
        $res = pdo_update(PDO_NAME . 'merchantuser_qrlog' , [
            'memberid' => $_W['mid'] ,
            'status'   => 1
        ] , ['id' => $item['id']]);
        if ($res) {
            $this->renderSuccess('修改成功');
        }
        else {
            $this->renderError('修改失败，请刷新重试');
        }
    }
    /**
     * Comment: 验证店员是否扫码
     * Author: wlf
     * Date: 2019/8/8 16:07
     */
    public function adminAjax()
    {
        global $_W , $_GPC;
        $qrcode   = $_GPC['qrcode'];
        $item     = pdo_get(PDO_NAME . 'merchantuser_qrlog' , ['uniacid' => $_W['uniacid'] , 'codes' => $qrcode]);
        $itemtime = $item['createtime'] + 300;
        if (empty($item) || $itemtime < time()) {
            $this->renderError('二维码过期');
        }
        if ($item['status']) {
            $data = pdo_get(PDO_NAME . "member" , ['id' => $item['memberid']] , ['avatar' , 'nickname' , 'id']);
            $this->renderSuccess('扫码人信息' , $data);
        }
        $this->renderSuccess('暂未扫码');
    }
    /**
     * Comment: 保存店员信息
     * Author: wlf
     * Date: 2019/8/8 16:57
     */
    public function createAdmin()
    {
        global $_W , $_GPC;
        $id       = intval($_GPC['adminid']);
        $storeid  = $_GPC['storeid'];
        $storeaid = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'aid');
        if (empty($_GPC['ismain'])) {
            $this->renderError('请选择店员类型');
        }
        if ($id) {
            $data = [
                'name'       => $_GPC['name'] ,
                'mobile'     => $_GPC['mobile'] ,
                'ismain'     => $_GPC['ismain'] ,
                'orderwrite' => $_GPC['orderwrite'] ,
                'viewdata'   => $_GPC['viewdata'] ,
                'enabled'    => $_GPC['enabled']
            ];
            if (pdo_update(PDO_NAME . 'merchantuser' , $data , ['id' => $id])) {
                $this->renderSuccess('店员修改成功');
            }
            else {
                $this->renderError('店员修改失败，请重试');
            }
        }
        else {
            $flag = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                'uniacid' => $_W['uniacid'] ,
                'mid'     => $_GPC['mid'] ,
                'storeid' => $storeid
            ] , 'id');
            if ($flag) {
                $this->renderError('此用户已是店员，请勿重复添加');
            }
            else {
                $data = [
                    'uniacid'    => $_W['uniacid'] ,
                    'mid'        => $_GPC['mid'] ,
                    'storeid'    => $storeid ,
                    'name'       => $_GPC['name'] ,
                    'mobile'     => $_GPC['mobile'] ,
                    'areaid'     => $_W['areaid'] ,
                    'createtime' => time() ,
                    'status'     => 2 ,
                    'enabled'    => $_GPC['enabled'] ,
                    'aid'        => $storeaid ,
                    'ismain'     => $_GPC['ismain'] ,
                    'orderwrite' => $_GPC['orderwrite'] ,
                    'viewdata'   => $_GPC['viewdata']
                ];
                if (pdo_insert(PDO_NAME . 'merchantuser' , $data)) {
                    $this->renderSuccess('店员添加成功');
                }
                else {
                    $this->renderError('店员添加失败，请重试');
                }
            }
        }
    }
    /**
     * Comment: 店铺店员列表
     * Author: wlf
     * Date: 2019/8/8 17:06
     */
    public function adminList()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];
        if (empty($storeid)) {
            $storeid = $_W['storeid'];
        }
        $list = pdo_fetchall("SELECT id,mid,name,mobile,ismain,enabled FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$_W['uniacid']} AND storeid = {$storeid} ORDER BY id ASC");
        foreach ($list as $key => &$li) {
            $avatar       = pdo_getcolumn(PDO_NAME . 'member' , ['id' => $li['mid']] , 'avatar');
            $li['avatar'] = tomedia($avatar);
        }
        $this->renderSuccess('店员列表' , $list);
    }
    /**
     * Comment: 商户中心信息
     * Author: wlf
     * Date: 2019/8/8 17:46
     */
    public function storeInfo()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];
        $store   = Store::getSingleStore($storeid);
        if (empty($store)) {
            $this->renderError('商户不存在，请重试');
        }
        $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $store['groupid']] , 'authority');
        $authority = unserialize($authority);
        //数据统计
        $time           = strtotime(date("Y-m-d") , time());
        $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$time} ");
        $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$time} ");
        $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$time} AND plugin != 'store' ");
        $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$time} AND plugin != 'store' ");
        $todaymoney     = sprintf("%.2f" , $rushordermoney + $ordermoney);
        $todayordernum  = sprintf("%.0f" , $ordernum + $rushordernum);
        $newsnum        = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "im") . " WHERE uniacid = {$_W['uniacid']} AND receive_type = 2 AND receive_id = {$storeid} AND is_read = 0");
        $data           = [
            'todaymoney'    => $todaymoney ,
            'todayordernum' => $todayordernum ,
            'newsnum'       => $newsnum ,
        ];
        if(p('citydelivery')){
            if((in_array('citydelivery',$authority) || empty($authority)) && $store['deliverystatus'] > 0 ){
                $data['showDelivery'] = 1;
                $data['deliveryName'] = pdo_getcolumn(PDO_NAME.'plugin',array('name'=>'citydelivery'),'title');
            }else{
                $data['showDelivery'] = 0;
            }
        }else{
            $data['showDelivery'] = 0;
        }
        //图标数据
        $timetype = $_GPC['timetype'] ? $_GPC['timetype'] : 1;  //1今天 2昨天 3 最近7日 4最近30天 5自定义
        $plugin   = $_GPC['plugin'];
        $type     = $_GPC['type'] ? $_GPC['type'] : 1;  // 1 支付金额 2支付单数
        $chart    = [];
        if (!empty($plugin) && $plugin != 'rush') {
            $where = " AND plugin = '" . $plugin . "'";
        }
        if ($timetype == 1) {
            $h                = date('H' , time());
            $zreo             = strtotime(date("Y-m-d") , time());
            $chart['xaxis'][] = '0:00';
            $chart['yaxis'][] = '0.00';
            for ($start = 0 ; $start <= $h ; $start++) {
                $starttime = $zreo + $start * 3600;
                $endtime   = $starttime + 3600;
                if (!empty($plugin) && $plugin == 'rush') {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = 0;
                    $ordernum       = 0;
                }
                else if (!empty($plugin)) {
                    $rushordermoney = 0;
                    $rushordernum   = 0;
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                }
                else {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                }
                $money            = sprintf("%.2f" , $rushordermoney + $ordermoney);
                $ordernum         = sprintf("%.0f" , $ordernum + $rushordernum);
                $chart['xaxis'][] = ($start + 1) . ':00';
                if ($type == 1) {
                    $chart['yaxis'][] = $money;
                }
                else {
                    $chart['yaxis'][] = $ordernum;
                }
            }
        }
        else if ($timetype == 2) {
            $zreo             = strtotime(date("Y-m-d") , time()) - 86400;
            $chart['xaxis'][] = '0:00';
            $chart['yaxis'][] = '0.00';
            for ($start = 0 ; $start <= 23 ; $start++) {
                $starttime = $zreo + $start * 3600;
                $endtime   = $starttime + 3600;
                if (!empty($plugin) && $plugin == 'rush') {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = 0;
                    $ordernum       = 0;
                }
                else if (!empty($plugin)) {
                    $rushordermoney = 0;
                    $rushordernum   = 0;
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                }
                else {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                }
                $money            = sprintf("%.2f" , $rushordermoney + $ordermoney);
                $ordernum         = sprintf("%.0f" , $ordernum + $rushordernum);
                $chart['xaxis'][] = ($start + 1) . ':00';
                if ($type == 1) {
                    $chart['yaxis'][] = $money;
                }
                else {
                    $chart['yaxis'][] = $ordernum;
                }
            }
        }
        else if ($timetype == 3) {
            $zreo = strtotime(date("Y-m-d") , time()) - 86400 * 6;
            for ($start = 0 ; $start <= 6 ; $start++) {
                $starttime = $zreo + $start * 86400;
                $endtime   = $starttime + 86400;
                if (!empty($plugin) && $plugin == 'rush') {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = 0;
                    $ordernum       = 0;
                }
                else if (!empty($plugin)) {
                    $rushordermoney = 0;
                    $rushordernum   = 0;
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                }
                else {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                }
                $money            = sprintf("%.2f" , $rushordermoney + $ordermoney);
                $ordernum         = sprintf("%.0f" , $ordernum + $rushordernum);
                $chart['xaxis'][] = date('m-d' , $starttime);
                if ($type == 1) {
                    $chart['yaxis'][] = $money;
                }
                else {
                    $chart['yaxis'][] = $ordernum;
                }
            }
        }
        else if ($timetype == 4) {
            $zreo = strtotime(date("Y-m-d") , time()) - 86400 * 29;
            for ($start = 0 ; $start <= 29 ; $start++) {
                $starttime = $zreo + $start * 86400;
                $endtime   = $starttime + 86400;
                if (!empty($plugin) && $plugin == 'rush') {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = 0;
                    $ordernum       = 0;
                }
                else if (!empty($plugin)) {
                    $rushordermoney = 0;
                    $rushordernum   = 0;
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                }
                else {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                }
                $money            = sprintf("%.2f" , $rushordermoney + $ordermoney);
                $ordernum         = sprintf("%.0f" , $ordernum + $rushordernum);
                $chart['xaxis'][] = date('m-d' , $starttime);
                if ($type == 1) {
                    $chart['yaxis'][] = $money;
                }
                else {
                    $chart['yaxis'][] = $ordernum;
                }
            }
        }
        else if ($timetype == 5) {
            $time      = $_GPC['time'];
            $times     = explode(',' , $time);
            $starttime = $times[0];
            $endtime   = $times[1] + 86399;
            $zreo      = $starttime;
            $days      = floor(($endtime - $starttime) / 86400);
            for ($start = 0 ; $start <= $days ; $start++) {
                $starttime = $zreo + $start * 86400;
                $endtime   = $starttime + 86400;
                if (!empty($plugin) && $plugin == 'rush') {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = 0;
                    $ordernum       = 0;
                }
                else if (!empty($plugin)) {
                    $rushordermoney = 0;
                    $rushordernum   = 0;
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' {$where} ");
                }
                else {
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM' . tablename(PDO_NAME . "rush_order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} ");
                    $ordermoney     = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                    $ordernum       = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND paytime > {$starttime} AND paytime <= {$endtime} AND plugin != 'store' ");
                }
                $money            = sprintf("%.2f" , $rushordermoney + $ordermoney);
                $ordernum         = sprintf("%.0f" , $ordernum + $rushordernum);
                $chart['xaxis'][] = date('m-d' , $starttime);
                if ($type == 1) {
                    $chart['yaxis'][] = $money;
                }
                else {
                    $chart['yaxis'][] = $ordernum;
                }
            }
        }
        $data['chart']    = $chart;
        $data['typelist'] = [0 => '全部' , 'rush' => '抢购' , 'coupon' => '卡券'];
        if (p('wlfightgroup')) {
            $data['typelist']['wlfightgroup'] = '拼团';
        }
        if (p('groupon')) {
            $data['typelist']['groupon'] = '团购';
        }
        if (p('halfcard')) {
            $data['typelist']['halfcard'] = '买单';
        }
        if (p('bargain')) {
            $data['typelist']['bargain'] = '砍价';
        }
        $data['maxY'] = max($chart['yaxis']);
        $data['minY'] = min($chart['yaxis']);
        $this->renderSuccess('店铺信息' , $data);
    }
    /**
     * Comment: 商户详细信息
     * Author: zzw
     * Date: 2019/8/19 18:17
     */
    public function detail()
    {
        global $_W , $_GPC;
        #1、参数接收
        $sid = $_GPC['sid'] OR $this->renderError("缺少参数：商户id");;//商户id
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//104.0091133118 经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//30.5681964123  纬度
        //排序设置
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        if(empty($storeSet['order'])){
            $order = [
                'sporder' => 8,
                'spstatus' => 1,
                'xqorder' => 7,
                'xqstatus' => 1,
                'dtorder' => 6,
                'dtstatus' => 1,
                'plorder' => 5,
                'plstatus' => 1,
                'xcorder' => 4,
                'xcstatus' => 1,
                'xcxmorder' => 3,
                'xcxmstatus' => 1,
                'sjzzorder' => 2,
                'sjzzstatus' => 1,
                'zporder' => 1,
                'zpstatus' => 1,
            ];
        }else{
            $order = unserialize($storeSet['order']);
        }

        #2、获取商户详细信息
        $info = pdo_get(PDO_NAME . "merchantdata" , ['id' => $sid] , [
            'id' ,
            'videourl' ,
            'panorama' ,
            'adv' ,
            'logo' ,
            'storename' ,
            'score' ,
            'pv' ,
            'storehours' ,
            'address' ,
            'mobile' ,
            'introduction' ,
            'album' ,
            'location' ,
            'payonline' ,
            'wxappswitch' ,
            'tag' ,
            'describe',
            'deliverystatus',
            'proportion',
            'communityid',
            'hotelstatus',
            'storeRedpackId'
        ]);
        if (!$info) $this->renderError("商户不存在!");
        $info['logo']         = tomedia($info['logo']);
        $info['introduction'] = htmlspecialchars_decode($info['introduction']);
        $info['location']     = unserialize($info['location']);
        //幻灯片的处理
        $info['adv'] = unserialize($info['adv']);
        foreach ($info['adv'] as $advK => &$advV) {
            $advV = tomedia($advV);
        }
        //营业时间的处理
        $storehours          = unserialize($info['storehours']);
        $info['is_business'] = Store::getShopBusinessStatus($storehours,$sid);//判断商户当前状态：0=休息中，1=营业中
        if(!empty($storehours['startTime'])){
            $info['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
        }else{
            $info['storehours'] = '';
            foreach($storehours as $hk => $hour){
                if($hk > 0){
                    $info['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                }else{
                    $info['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                }
            }
        }
        //相册的处理
        $info['album'] = unserialize($info['album']);
        foreach ($info['album'] as $albumK => &$albumV) {
            $albumV = tomedia($albumV);
        }
        //视频处理
        $info['videourl'] = tomedia($info['videourl']);
        //商户人气 +1 操作
        $info['pv'] = intval($info['pv']) + 1;
        pdo_update(PDO_NAME . "merchantdata" , ['pv' => $info['pv']] , ['id' => $sid]);
        #3、对店铺进行定位
        $info['distance'] = Store::shopLocation(0 , $lng , $lat , $info['location']);
        #4、判断用户是否已经收藏当前商户
        $storefans             = pdo_getcolumn(PDO_NAME . "storefans" , [
            'sid'    => $sid ,
            'mid'    => $_W['mid'] ,
            'source' => 1
        ] , 'id');
        $info['is_collection'] = $storefans > 0 ? 1 : 0;
        $info['collectionnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_storefans')." WHERE source = 1 AND sid = {$sid}");
        $info['collectionnum'] = sprintf("%.0f",$info['pv'] / 10 ) + $info['collectionnum'];
        #5、处理商户全景地址
        if (!empty($info['panorama']) && count(explode('https' , $info['panorama'])) < 2) {
            $info['panorama'] = str_replace('http' , 'https' , $info['panorama']);
        }

        #6、判断是否开启商户小程序码
        if (p('wxapp')) {
            $wxappSet = Setting::wlsetting_read('wxappset');
            if ($wxappSet['storeqrcode'] == 1 && $info['wxappswitch'] != 1) {
                $info['wxapp_qrcode'] = Store::getShopWxAppQrCode($sid , $info['logo']);
            }
            if($_W['source'] == 3 && empty($wxappSet['vp_status'])){
                $info['panorama'] = '';
            }
        }
        if(empty($info['wxapp_qrcode'])){
            $order['xcxmstatus'] = 0;
        }
        #6、判断是否存在商户标签
        $tag          = unserialize($info['tag']);
        $info['tags'] = [];
        if (is_array($tag) && count($tag) > 0) {
            foreach ($tag as $tagK => &$tagV) {
                $taginfo = pdo_get(PDO_NAME . "tags" , ['id' => $tagV] , ['title','content']);
                $info['tags'][] = $taginfo['title'];
                $info['tagslist'][] = $taginfo;
            }
        }
        //认证信息
        if (p('attestation')) {
            $info['attestation'] = Attestation::checkAttestation(2 , $sid);
            if($info['attestation']['attestation'] == 2){
                $moreatt = pdo_get('wlmerchant_attestation_list',array('uniacid'=>$_W['uniacid'],'storeid'=>$sid,'status'=>1,'type'=>2),array('subjectname','atttel','cardnum','pic','moreinfo'));
                $moreatt['pic'] = unserialize($moreatt['pic']);
                $info['moreinfo']['主体名称'] = ['value' => $moreatt['subjectname'],'type' => 'text'];
//                $info['moreinfo']['联系电话'] = ['value' => $moreatt['atttel'],'type' => 'number'];
                $info['moreinfo']['营业执照号'] = ['value' => $moreatt['cardnum'],'type' => 'number'];
                $info['moreinfo']['营业执照'] = ['value' => tomedia($moreatt['pic'][0]),'type' => 'pic'];
                $moreatt = unserialize($moreatt['moreinfo']);
                foreach ($moreatt as $k => &$ll) {
                    if(!empty($ll['id'])){
                        if($ll['id'] == 'img'){
                            foreach($ll['data'] as &$pic){
                                $pic = tomedia($pic);
                            }
                        }
                        if(empty($ll['att_show'])){
                            $llnn['value'] = $ll['data'];
                            $llnn['type'] = $ll['id'];
                            $info['moreinfo'][$ll['title']] = $llnn;
                        }
                    }else{
                        if($ll['type'] == 'pics'){
                            $ll['value'] = unserialize($ll['value']);
                            foreach($ll['value'] as &$pic){
                                $pic = tomedia($pic);
                            }
                        }else if($ll['type'] == 'pic'){
                            $ll['value'] = tomedia($ll['value']);
                        }
                        $info['moreinfo'][$k] = $ll;
                    }
                }
            }else{
                $order['sjzzstatus'] = 0;
            }
        }else {
            $info['attestation'] = 0;
            $order['sjzzstatus'] = 0;
        }

        $info['mid'] = $_W['mid'];
        unset($info['tag']);
        //判断商户是否有商品
        $where = "  AND uniacid = {$_W['uniacid']} AND status = 2 ";
        $whereH = "  AND uniacid = {$_W['uniacid']} AND status = 1 ";
        $sql   = "SELECT id,'1' as type,sort FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE sid = {$sid} " . $where . " UNION ALL SELECT id,'2' as type,sort FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE sid = {$sid} " . $where . " UNION ALL SELECT id,'3' as type,listorder as sort FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE merchantid = {$sid} " . $where . " UNION ALL SELECT id,'5' as type,indexorder as sort FROM " . tablename(PDO_NAME . "couponlist") . " WHERE  merchantid = {$sid} AND nostoreshow = 0" . $where . " UNION ALL SELECT id,'7' as type,sort FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE sid = {$sid} " . $where. " UNION ALL SELECT id,'9' as type,sort FROM " . tablename(PDO_NAME . "activitylist") . " WHERE sid = {$sid} " . $where." UNION ALL SELECT id,'10' as type,sort FROM " . tablename(PDO_NAME . "housekeep_service") . " WHERE objid = {$sid} AND type = 1". $whereH;
        $goodslist  = pdo_fetch($sql);
        if(empty($goodslist)){
            $order['spstatus'] = 0;
        }
        //判断是否有详情
        if(empty($info['introduction'])){
            $order['xqstatus'] = 0;
        }
        //判断是否有动态
        $dynamic = pdo_fetch("SELECT id FROM ".tablename('wlmerchant_store_dynamic')."WHERE uniacid = {$_W['uniacid']} AND sid = {$sid} AND status > 0 ");
        if(empty($dynamic)){
            $order['dtstatus'] = 0;
        }
        //判断是否有评论
        $comment = pdo_fetch("SELECT id FROM ".tablename('wlmerchant_comment')." WHERE sid = {$sid} AND status = 1 AND (checkone = 2 OR mid = {$_W['mid']})");
        if(empty($comment)){
            $order['plstatus'] = 0;
        }
        //判断是否有相册
        if(empty($info['album'])){
            $order['xcstatus'] = 0;
        }
        //判断是否有招聘
        $recruit = pdo_get('wlmerchant_recruit_recruit',array('release_sid' => $sid,'status' => 4),array('id'));
        if(empty($recruit)){
            $order['zpstatus'] = 0;
        }
        #17、获取图片高度
        $proportion = unserialize($info['proportion']);
        $info['imgstyle']['width'] = $proportion['shopwidth'];
        $info['imgstyle']['height'] = $proportion['shopheight'];
        if(empty($info['imgstyle']['width'])){
            $info['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        }
        if(empty($info['imgstyle']['height'])){
            $info['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;
        }
        //获取社群信息
        if($info['communityid'] > 0){
            $community = Commons::getCommunity($info['communityid']);
            if(is_array($community)){
                $info['community'] = $community;
            }
        }
        //标签页排序
        $neworder[] = ['title' => '商品','type' => 'goods','status' => $order['spstatus'],'sort' => $order['sporder']];
        $neworder[] = ['title' => '详情','type' => 'detail','status' => $order['xqstatus'],'sort' => $order['xqorder']];
        $neworder[] = ['title' => '动态','type' => 'state','status' => $order['dtstatus'],'sort' => $order['dtorder']];
        $neworder[] = ['title' => '评论','type' => 'comment','status' => $order['plstatus'],'sort' => $order['plorder']];
        $neworder[] = ['title' => '相册','type' => 'photo','status' => $order['xcstatus'],'sort' => $order['xcorder']];

        $order['xcxmorder'] = $order['xcxmorder'] ? : 0;
        $order['xcxmstatus'] = $order['xcxmstatus'] ? : 0;
        $neworder[] = ['title' => '小程序码','type' => 'qrCode','status' => $order['xcxmstatus'],'sort' => $order['xcxmorder']];

        $order['sjzzorder'] = $order['sjzzorder'] ? : 0;
        $order['sjzzstatus'] = $order['sjzzstatus'] ? : 0;
        $neworder[] = ['title' => '商家资质','type' => 'certification','status' => $order['sjzzstatus'],'sort' => $order['sjzzorder']];

        $order['zporder'] = $order['zporder'] ? : 0;
        $order['zpstatus'] = $order['zpstatus'] ? : 0;
        $neworder[] = ['title' => '招聘','type' => 'recruit','status' => $order['zpstatus'],'sort' => $order['zporder']];

        $sortArr = array_column($neworder, 'sort');
        array_multisort($sortArr, SORT_DESC, $neworder);

        foreach($neworder as &$oor){
            $oor['status'] = $oor['status'] ? : 0;
        }

        $info['order'] = $neworder;

        //视频号图文
        $info['imgtext'] = 0;
        if(p('wxchannels')){
            $wxappset = Setting::wlsetting_read('wxapp_config');
            $wxplat = Util::object_array($_W['account']);
            if(!empty($wxappset['appid']) && !empty($wxplat['key'])){
                $info['imgtext'] = 1;
            }
        }
		
		//商户红包
		if($info['storeRedpackId'] > 0){
			$storeRedpack = pdo_get('wlmerchant_store_redpack',array('id' => $info['storeRedpackId']),array('status','title','diypageid','id','onemoney','fishmoney'));
			if($storeRedpack['status'] > 0){
				$redpackInfo = [
					'redid' => $storeRedpack['id'],
					'money' => sprintf("%.2f",$storeRedpack['onemoney'] + $storeRedpack['fishmoney']),
					'title' => $storeRedpack['title'],
					'diypageid' => $storeRedpack['diypageid']
				];
				$info['redpackInfo'] = $redpackInfo;
			}
		}

        $this->renderSuccess('商户详情' , $info);
    }
    /**
     * Comment: 获取店铺商品信息
     * Author: zzw
     * Date: 2019/8/20 10:31
     */
    public function getStoreGoods()
    {
        global $_W , $_GPC;
        #1、参数接收  商品类型：1=抢购  2=团购  3=拼团 5=优惠券 7=砍价商品 9 = 同城活动
        $sid       = $_GPC['sid'];//店铺id
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $storeflag = $_GPC['storeflag'] ? $_GPC['storeflag'] : 0;
        $pageStart = $page * $pageIndex - $pageIndex;
        $plugin    = $_GPC['plugin'];
        if (!$sid) $this->renderError("缺少参数：sid");
        $data = ['hidegoods' => 0];
        //判断权限
        $groupid = pdo_getcolumn('wlmerchant_merchantdata' , ['id' => $sid] , 'groupid');
        if (empty($groupid)) {
            $authority = 0;
        }
        else {
            $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $groupid] , 'authority');
        }
        if (!empty($authority)) {
            $authority = unserialize($authority);
            if (!empty($authority) && !in_array('rush' , $authority) && !in_array('groupon' , $authority) && !in_array('wlfightgroup' , $authority) && !in_array('bargain' , $authority)) {
                $data['hidegoods'] = 1;
            }
        }
        if(p('activity')){
            if (empty($authority) || in_array('activity' , $authority)) {
                $data['activityflag'] = 1;
            }
        }
        //数据筛选
        $where1 = "";
        $where2 = "";
        if (!empty($_GPC['keyword'])) {
            $keyword = trim($_GPC['keyword']);
            $where1  .= " AND name LIKE '%{$keyword}%'";
            $where2  .= " AND title LIKE '%{$keyword}%'";
        }
        $status = $_GPC['status'];
        if (empty($status)) {
            $where1 .= " AND status = 2";
            $where2 .= " AND status = 2";
        }
        else if ($status == 1) {
            $where1 .= " AND status IN (1,5,6)";
            $where2 .= " AND status IN (1,5,6)";
        }
        else if ($status == 4) {
            $where1 .= " AND status IN (0,3,4)";
            $where2 .= " AND status IN (0,3,4)";
        }
        else {
            $where1 .= " AND status = {$status}";
            $where2 .= " AND status = {$status}";
        }
        #2、商品信息获取AND aid = {$_W['aid']}
        $where = "  AND uniacid = {$_W['uniacid']} ";
        if(empty($plugin)){
            $sql   = "SELECT id,'1' as type,sort FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE sid = {$sid} " . $where . $where1 . " UNION ALL SELECT id,'2' as type,sort FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE sid = {$sid} " . $where . $where1 . " UNION ALL SELECT id,'3' as type,listorder as sort FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE merchantid = {$sid} " . $where . $where1 . " UNION ALL SELECT id,'5' as type,indexorder as sort FROM " . tablename(PDO_NAME . "couponlist") . " WHERE  merchantid = {$sid} AND nostoreshow = 0" . $where . $where2 . " UNION ALL SELECT id,'7' as type,sort FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE sid = {$sid} " . $where . $where1. " UNION ALL SELECT id,'9' as type,sort FROM " . tablename(PDO_NAME . "activitylist") . " WHERE sid = {$sid} " . $where . $where2;
        }else if($plugin == 'rush'){
            $sql   = "SELECT id,'1' as type,sort FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE sid = {$sid} " . $where . $where1;
        }else if($plugin == 'groupon'){
            $sql   = "SELECT id,'2' as type,sort FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE sid = {$sid} " . $where . $where1;
        }else if($plugin == 'wlfightgroup'){
            $sql   = "SELECT id,'3' as type,listorder as sort FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE merchantid = {$sid} " . $where . $where1 ;
        }else if($plugin == 'coupon'){
            $sql   = "SELECT id,'5' as type,indexorder as sort FROM " . tablename(PDO_NAME . "couponlist") . " WHERE  merchantid = {$sid} AND nostoreshow = 0" . $where . $where2;
        }else if($plugin == 'bargain'){
            $sql   = "SELECT id,'7' as type,sort FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE sid = {$sid} " . $where . $where1;
        }else if($plugin == 'activity'){
            $sql   = "SELECT id,'9' as type,sort FROM " . tablename(PDO_NAME . "activitylist") . " WHERE sid = {$sid} " . $where . $where2;
        }
        $list  = pdo_fetchall($sql . " ORDER BY sort DESC,id DESC LIMIT {$pageStart},{$pageIndex}");
        #3、循环处理商品信息
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $key => &$val) {
                $val = WeliamWeChat::getHomeGoods($val['type'] , $val['id'] , $storeflag);
                switch ($val['type']) {
                    case 1:
                        $val['creategoodstype'] = 'rush';
                        break;
                    case 2:
                        $val['creategoodstype'] = 'groupon';
                        break;
                    case 3:
                        $val['creategoodstype'] = 'fightgroup';
                        break;
                    case 5:
                        $val['creategoodstype'] = 'coupon';
                        break;
                    case 7:
                        $val['creategoodstype'] = 'bargain';
                        break;
                    case 9:
                        $val['creategoodstype'] = 'activity';
                        break;
                }
                if ($val['creategoodstype'] == 'fightgroup') {
                    $val['checktype'] = 'wlfightgroup';
                }
                else if ($val['creategoodstype'] == 'coupon') {
                    $val['checktype'] = 'wlcoupon';
                }
                else {
                    $val['checktype'] = $val['creategoodstype'];
                }
                if (!empty($authority) && !in_array($val['checktype'] , $authority)) {
                    $val['hidegoods'] = 1;
                }
                else {
                    $val['hidegoods'] = 0;
                }
                unset($val['checktype']);
                unset($val['shop_logo']);
                unset($val['address']);
                unset($val['storename']);
                unset($val['sid']);
                unset($val['realsalenum']);
                unset($val['allsalenum']);
                unset($val['plugin']);
                unset($val['user_list']);
                unset($val['pay_state']);
                unset($val['is_vip']);
                unset($val['user_limit_num']);
                unset($val['user_num']);
                unset($val['buy_limit']);
                unset($val['discount_price']);
                unset($val['totalnum']);
                unset($val['vipprice']);
                unset($val['vipstatus']);
                unset($val['starttime']);
                unset($val['endtime']);
            }
        }
        #4、商品总数获取
        $total = count(pdo_fetchall($sql));
        $data['list']  = $list;
        $data['total'] = ceil($total / $pageIndex);
        //增加插件筛选列表
        if($page == 1){
            $pluginlist[] = ['name'=>'全部','plugin'=>''];
            $pluginlist[] = ['name'=>'抢购','plugin'=>'rush'];
            if(p('groupon')){
                $pluginlist[] = ['name'=>'团购','plugin'=>'groupon'];
            }
            if(p('wlfightgroup')){
                $pluginlist[] = ['name'=>'拼团','plugin'=>'wlfightgroup'];
            }
            if(p('bargain')){
                $pluginlist[] = ['name'=>'砍价','plugin'=>'bargain'];
            }
//            if(p('wlcoupon')){
//                $pluginlist[] = ['name'=>'卡券','plugin'=>'coupon'];
//            }
            if(p('activity')){
                $pluginlist[] = ['name'=>'活动','plugin'=>'activity'];
            }
            $data['pluginlist'] = $pluginlist;
        }

        $this->renderSuccess('店铺商品列表' , $data);
    }
    /**
     * Comment: 商户详情页面获取店铺商品信息
     * Author: wlf
     * Date: 2021/05/25 11:24
     */
    public function getStoreDetailGoods(){
        global $_W , $_GPC;
        $sid       = $_GPC['sid'];//店铺id
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $storeflag = 0;
        $pageStart = $page * $pageIndex - $pageIndex;
        if (!$sid) $this->renderError("缺少参数：sid");
        $where = "  AND uniacid = {$_W['uniacid']} ";
        $where1 = $where. " AND status = 2";
        $where2 = $where. " AND status = 1";
        $sql   = "SELECT id,1 as type,sort FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE sid = {$sid} " . $where1 . " UNION ALL SELECT id,2 as type,sort FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE sid = {$sid} " . $where1 . " UNION ALL SELECT id,3 as type,listorder as sort FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE merchantid = {$sid} " . $where1 . " UNION ALL SELECT id,5 as type,indexorder as sort FROM " . tablename(PDO_NAME . "couponlist") . " WHERE  merchantid = {$sid} AND nostoreshow = 0" . $where1 . " UNION ALL SELECT id,7 as type,sort FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE sid = {$sid} " . $where1. " UNION ALL SELECT id,9 as type,sort FROM " . tablename(PDO_NAME . "activitylist") . " WHERE sid = {$sid} ". $where1." UNION ALL SELECT id,10 as type,sort FROM " . tablename(PDO_NAME . "housekeep_service") . " WHERE objid = {$sid} AND type = 1". $where2;
        $list  = pdo_fetchall($sql . " ORDER BY type ASC,sort DESC,id DESC LIMIT {$pageStart},{$pageIndex}");
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $key => &$val) {
                if($val['type'] == 10){
                    $val = Housekeep::getServiceBaseInfo($val['id']);
                    $val['type'] = 10;
                }else{
                    $val = WeliamWeChat::getHomeGoods($val['type'] , $val['id'] , $storeflag);
                }
                switch ($val['type']) {
                    case 1:
                        $val['creategoodstype'] = 'rush';
                        break;
                    case 2:
                        $val['creategoodstype'] = 'groupon';
                        break;
                    case 3:
                        $val['creategoodstype'] = 'fightgroup';
                        break;
                    case 5:
                        $val['creategoodstype'] = 'coupon';
                        break;
                    case 7:
                        $val['creategoodstype'] = 'bargain';
                        break;
                    case 9:
                        $val['creategoodstype'] = 'activity';
                        break;
                    case 10:
                        $val['creategoodstype'] = 'housekeep';
                        break;
                }
                if ($val['creategoodstype'] == 'fightgroup') {
                    $val['checktype'] = 'wlfightgroup';
                }
                else if ($val['creategoodstype'] == 'coupon') {
                    $val['checktype'] = 'wlcoupon';
                }
                else {
                    $val['checktype'] = $val['creategoodstype'];
                }
                if (!empty($authority) && !in_array($val['checktype'] , $authority)) {
                    $val['hidegoods'] = 1;
                }
                else {
                    $val['hidegoods'] = 0;
                }
                unset($val['checktype']);
                unset($val['shop_logo']);
                unset($val['address']);
                unset($val['storename']);
                unset($val['sid']);
                unset($val['realsalenum']);
                unset($val['allsalenum']);
                unset($val['plugin']);
                unset($val['user_list']);
                unset($val['pay_state']);
                unset($val['is_vip']);
                unset($val['user_limit_num']);
                unset($val['user_num']);
                unset($val['buy_limit']);
                unset($val['totalnum']);
                unset($val['vipprice']);
                unset($val['vipstatus']);
                unset($val['starttime']);
                unset($val['endtime']);
            }
        }
        #4、商品总数获取
        $total = count(pdo_fetchall($sql));
        $data['list']  = $list;
        $data['total'] = ceil($total / $pageIndex);
        $this->renderSuccess('店铺商品列表' , $data);
    }
    /**
     * Comment: 获取商户评论信息列表
     * Author: zzw
     * Date: 2019/8/20 11:49
     */
    public function getStoreComment()
    {
        global $_W , $_GPC;
        #1、参数接收
        $sid       = $_GPC['sid'];//店铺id
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 5;
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、获取评论信息
        if(empty($_W['mid'])){
            $_W['mid'] = 0;
        }
        $sql  = "SELECT headimg,nickname,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i') as datetime,star,pic,text,replytextone,replypicone FROM " . tablename(PDO_NAME . "comment") . " WHERE sid = {$sid} AND status = 1 AND (checkone = 2 OR mid = {$_W['mid']}) ";
        $list = pdo_fetchall($sql . " ORDER BY createtime DESC LIMIT {$pageStart},{$pageIndex} ");
        foreach ($list as $key => &$val) {
            //用户评论图片的处理
            $commentPic = unserialize($val['pic']);
            if(is_string($commentPic) && strlen($commentPic) > 10){
                $commentPic = explode(',',$commentPic);
            }
            if (is_array($commentPic) && count($commentPic) > 0) {
                foreach ($commentPic as $picKey => &$picVal) {
                    if($picVal) $picVal = tomedia($picVal);
                    else unset($commentPic[$picKey]);
                }
            }
            $val['pic'] = is_array($commentPic) ? array_values($commentPic) : [];
            //商家回复信息图片的处理
            $replyPic = unserialize($val['replypicone']);
            if (is_array($replyPic) && count($replyPic) > 0) {
                foreach ($replyPic as $replyPicKey => &$replyPicVal) {
                    $replyPicVal = tomedia($replyPicVal);
                }
            }
            $val['replypicone'] = $replyPic;
            $val['star']        = intval($val['star']);
        }
        #2、获取评论中页数
        $total         = ceil(count(pdo_fetchall($sql)) / $pageIndex);
        $data['total'] = $total;
        $data['list']  = $list;
        $this->renderSuccess('获取商户评论信息列表' , $data);
    }
    /**
     * Comment: 获取商户动态信息列表
     * Author: zzw
     * Date: 2019/8/20 14:12
     */
    public function getStoreDynamic()
    {
        global $_W , $_GPC;
        #1、参数信息获取
        $sid = $_GPC['sid'];//店铺id
        //$this->checkAuthority('dynamic' , $sid);
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 5;
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、获取动态信息列表
        $sql = "SELECT b.logo,b.storename,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i') as datetime,a.content,a.imgs FROM " . tablename(PDO_NAME . "store_dynamic") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.sid = b.id WHERE a.sid = {$sid} AND a.status IN (1,2) ORDER BY a.createtime DESC ";
        $list = pdo_fetchall($sql . " LIMIT {$pageStart},{$pageIndex}");
        #3、循环处理信息
        foreach ($list as $key => &$val) {
            $val['logo'] = tomedia($val['logo']);
            $val['imgs'] = unserialize($val['imgs']);
            if (is_array($val['imgs']) && count($val['imgs']) > 0) {
                foreach ($val['imgs'] as $imgK => &$imgV) {
                    $imgV = tomedia($imgV);
                }
            }
        }
        #3、总页数获取
        $total = count(pdo_fetchall($sql));
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;
        $this->renderSuccess('获取商户动态信息列表' , $data);
    }
    /**
     * Comment: 收藏商户
     * Author: zzw
     * Date: 2019/8/20 14:33
     */
    public function collection()
    {
        global $_W , $_GPC;
        #1、获取参数信息
        $sid = $_GPC['sid'];
        if (!$sid) $this->renderError('缺少参数：sid');
        #2、查询用户是否已经收藏该商户
        $cid = pdo_getcolumn(PDO_NAME . "storefans" , ['sid' => $sid , 'mid' => $_W['mid']] , 'id');
        if ($cid) {
            //已收藏 —— 取消收藏信息
            $res = pdo_delete(PDO_NAME . "storefans" , ['sid' => $sid,'mid'=> $_W['mid']]);
        }
        else {
            //未收藏 —— 添加收藏信息
            $res = Store::addFans($sid , $_W['mid'] , $_W['source']);
        }
        #3、判断操作是否成功
        if ($res) $this->renderSuccess('操作成功!');
        else $this->renderError('操作失败!');
    }
    /**
     * Comment: 商户提现
     * Author: wlf
     * Date: 2019/8/22 16:01
     */
    public function storeCash()
    {
        global $_W , $_GPC;
        $storeid           = $_GPC['storeid'];
        $store             = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , ['allmoney' , 'nowmoney']);
        $data              = [];
        $data['allmoney']  = $store['allmoney'];
        $data['nowmoney']  = $store['nowmoney'];
        $data['cashmoney'] = sprintf("%.2f" , $store['allmoney'] - $store['nowmoney']);
        $this->renderSuccess('商户账户' , $data);
    }
    /**
     * Comment: 商户收支明细
     * Author: wlf
     * Date: 2019/8/22 16:25
     */
    public function storeCurrent()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid'] ? $_GPC['storeid'] : $_W['storeid'];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageStart = $page * 10 - 10;
        //金额
        $store     = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , ['nowmoney' , 'allmoney']);
        $cashmoney = sprintf("%.2f" , $store['allmoney'] - $store['nowmoney']);
        $currents  = pdo_fetchall("SELECT fee,orderid,type,createtime,remark FROM " . tablename('wlmerchant_current') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 AND objid = {$storeid} ORDER BY id DESC LIMIT {$pageStart},10");
        $count     = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_current') . " WHERE uniacid = {$_W['uniacid']} AND status = 1 AND objid = {$storeid}");
        foreach ($currents as &$current) {
            $current['createtime'] = date('Y-m-d H:i:s' , $current['createtime']);
            $current['checkcode'] = $current['remark'];
            if ($current['type'] == 1) {
                $order = pdo_get(PDO_NAME . 'rush_order' , ['id' => $current['orderid']] , array('activityid','orderno'));
                $goodsid = $order['activityid'];
                $current['orderno'] = $order['orderno'];
                $current['remark'] = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $goodsid] , 'name');
            }
            else if ($current['type'] == 10) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $goodsid = $order['fkid'];
                $current['orderno'] = $order['orderno'];
                $current['remark'] = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $goodsid] , 'name');
            }
            else if ($current['type'] == 2) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $goodsid = $order['fkid'];
                $current['orderno'] = $order['orderno'];
                $current['remark'] = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $goodsid] , 'name');
            }
            else if ($current['type'] == 3) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $goodsid = $order['fkid'];
                $current['orderno'] = $order['orderno'];
                $current['remark'] = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $goodsid] , 'title');
            }
            else if ($current['type'] == 12) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $goodsid = $order['fkid'];
                $current['orderno'] = $order['orderno'];
                $current['remark'] = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $goodsid] , 'name');
            }
            else if ($current['type'] == 11) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('orderno'));
                $current['orderno'] = $order['orderno'];
                $money             = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $current['orderid']] , 'goodsprice');
                $current['remark'] = '商户在线买单' . $money . '元';
            }else if ($current['type'] == 14) {
                $order           = pdo_get(PDO_NAME . 'timecardrecord' , ['id' => $current['orderid']] , array('activeid'));
                $packagename = pdo_getcolumn(PDO_NAME.'package',array('id'=>$order['activeid']),'title');
                $current['remark'] = '礼包【' . $packagename . '】';
            }else if ($current['type'] == 17) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $current['orderno'] = $order['orderno'];
                $title           = pdo_getcolumn(PDO_NAME . 'housekeep_service' , ['id' => $order['fkid']] , 'title');
                $current['remark'] = '家政服务【' . $title . '】';
            }else if ($current['type'] == 19) {
                $order           = pdo_get(PDO_NAME . 'order' , ['id' => $current['orderid']] , array('fkid','orderno'));
                $current['orderno'] = $order['orderno'];
                $title           = pdo_getcolumn(PDO_NAME . 'hotel_room' , ['id' => $order['fkid']] , 'name');
                $current['remark'] = '酒店预约【' . $title . '】';
            }
            else if ($current['type'] == 7) {
                if ($current['fee'] > 0) {
                    $current['remark'] = '提现申请被驳回';
                }else {
                    $current['remark'] = '提现申请';
                    //商户提现申请 获取手续费金额
                    $current['spercentmoney'] = pdo_getcolumn(PDO_NAME."settlement_record",['id'=>$current['orderid']],'spercentmoney');
                }
            }
            else if ($current['type'] == -1) {
                $current['remark'] = '系统后台修改';
            }
            else if ($current['type'] == 140) {
                $orderno = pdo_getcolumn(PDO_NAME.'order',array('id'=>$current['orderid']),'orderno');
                $current['orderno'] = $orderno;
                $smallorder = pdo_getall('wlmerchant_delivery_order',array('tid'=>$orderno),array('gid'));
                $va['title'] = pdo_getcolumn(PDO_NAME.'delivery_activity',array('id'=>$smallorder[0]['gid']),'name');
                if(count($smallorder)>1){
                    $va['title'] .= ' 等';
                }
                $current['remark'] = '同城配送 - '.$va['title'];
            }
        }
        $data['list']      = $currents;
        $data['totalpage'] = ceil($count / 10);
        $data['allmoney']  = $store['allmoney'];
        $data['nowmoney']  = $store['nowmoney'];
        $data['cashmoney'] = $cashmoney;
        $this->renderSuccess('商户收支明细' , $data);
    }
    /**
     * Comment: 商户提现页面
     * Author: wlf
     * Date: 2019/8/23 09:25
     */
    public function cashPage()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid'];
        $storeaid  = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'aid');
        $cashsets  = Setting::wlsetting_read('cashset');
        $data      = [];
        $storemain = pdo_get('wlmerchant_member' , ['id' => $_W['mid']] , [
            'alipay' ,
            'realname',
            'bank_name' ,
            'card_number' ,
            'bank_username'
        ]);
        $ismian    = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
            'uniacid' => $_W['uniacid'] ,
            'mid'     => $_W['mid'] ,
            'storeid' => $storeid
        ] , 'ismain');
        if ($ismian != 1) {
            $this->renderError('只有店铺店长才能进行财务结算。');
        }
        //可提现金额
        $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , [
            'allmoney' ,
            'nowmoney' ,
            'reservestatus' ,
            'reservemoney'
        ]);
        //预留金额设置
        if ($merchant['reservestatus']) {
            $reservemoney = $merchant['reservemoney'];
        }
        else {
            $reservemoney = sprintf("%.2f" , $cashsets['reservemoney']);
        }
        $usemoney = sprintf("%.2f" , $merchant['nowmoney'] - $reservemoney);
        if ($usemoney < 0) {
            $usemoney = 0;
        }
        $data['nowmoney']     = $usemoney;
        $data['reservemoney'] = $reservemoney;
        //提现方式
        $payment_type = $cashsets['payment_type'];
        if ($payment_type['alipay']) {
            $data['alipaystatus'] = 1;
            $data['alipay']       = $storemain['alipay'];
            $data['realname']     = $storemain['realname'];
        }
        else {
            $data['alipaystatus'] = 0;
        }
        if ($payment_type['we_chat']) {
            $data['wechatstatus'] = 1;
        }
        else {
            $data['wechatstatus'] = 0;
        }
        if ($payment_type['bank_card']) {
            $data['bankcardstatus'] = 1;
            $data['bank_name']      = $storemain['bank_name'];
            $data['card_number']    = $storemain['card_number'];
            $data['bank_username']  = $storemain['bank_username'];
        }
        else {
            $data['bankcardstatus'] = 0;
        }
        //提现手续费
        $agent          = Area::getSingleAgent($storeaid);
        $syssalepercent = $agent['percent']['syssalepercent'];
        if (empty($syssalepercent)) {
            $syssalepercent = sprintf("%.2f" , $_W['wlsetting']['cashset']['syssalepercent']);
        }
        else {
            $syssalepercent = sprintf("%.2f" , $syssalepercent);
        }
        $data['syssalepercent'] = $syssalepercent;
        $data['lowsetmoney'] = $cashsets['lowsetmoney'] ? : 0;
        $data['maxsetmoney'] = $cashsets['maxsetmoney'] ? : 0;

        $this->renderSuccess('商户提现' , $data);
    }
    /**
     * Comment: 商户提现申请接口
     * Author: wlf
     * Date: 2019/8/23 10:15
     */
    public function cashApply(){
        global $_W , $_GPC;
        $storeid         = $_GPC['storeid'];
        //判断临时申请内容
        $flag = pdo_get('wlmerchant_settlement_temporary',array('mid' => $storeid,'uniacid'=>$_W['uniacid'],'type' => 2),array('id'));
        if(!empty($flag)){
            $this->renderError('此商户有处理中的提现申请,请稍后再试');
        }
        $storeaid        = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'aid');
        $settlementmoney = $_GPC['settlementmoney'];
        $cashtype        = $_GPC['cashtype'];
        $storeadmin      = pdo_get('wlmerchant_member' , ['id' => $_W['mid']] , ['id' , 'openid' , 'wechat_openid']);
        if ($cashtype == 1) {
            $alipay = $_GPC['alipay'];
            $realname = $_GPC['realname'];
            if (empty($alipay)) {
                $this->renderError('请填写支付宝账号');
            }
            if (empty($realname)) {
                $this->renderError('请填写支付宝账号真实姓名');
            }
            pdo_update('wlmerchant_member' , ['alipay' => $alipay,'realname'=>$realname] , ['id' => $_W['mid']]);
        }
        if ($cashtype == 2) {
            if ($_W['source'] == 1) {
                if (empty($storeadmin['openid'])) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                }
                else {
                    $sopenid = $storeadmin['openid'];
                }
            }
            else if ($_W['source'] == 3) {
                if (empty($storeadmin['wechat_openid'])) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                }
                else {
                    $sopenid = $storeadmin['wechat_openid'];
                }
            }
        }
        else {
            $sopenid = '';
        }
        if ($cashtype == 3) {
            $bank_name     = $_GPC['bank_name'];
            $card_number   = $_GPC['card_number'];
            $bank_username = $_GPC['bank_username'];
            if (empty($bank_name) || empty($card_number) || empty($bank_username)) {
                $this->renderError('请填写银行卡各项参数');
            }
            pdo_update('wlmerchant_member' , [
                'bank_name'     => $bank_name ,
                'card_number'   => $card_number ,
                'bank_username' => $bank_username
            ] , ['id' => $_W['mid']]);
        }
        $cashsets = Setting::wlsetting_read('cashset');
        //提现手续费
        $agent          = Area::getSingleAgent($storeaid);
        $syssalepercent = $agent['percent']['syssalepercent'];
        if (empty($syssalepercent)) {
            $syssalepercent = sprintf("%.2f" , $_W['wlsetting']['cashset']['syssalepercent']);
        }
        else {
            $syssalepercent = sprintf("%.2f" , $syssalepercent);
        }

        //判断提现金额限制
        if ($_GPC['settlementmoney'] < $cashsets['lowsetmoney']) {
            $this->renderError('申请失败，最低提现金额为' . $cashsets['lowsetmoney'] . '元。');
        }
        //判断提现金额限制
        if ($_GPC['settlementmoney'] > $cashsets['maxsetmoney'] && $cashsets['maxsetmoney'] > 0) {
            $this->renderError('申请失败，单次最大提现金额为' . $cashsets['maxsetmoney'] . '元。');
        }

        //判断商家的提现时间限制
        $shopIntervalTime = $cashsets['shopIntervalTime'];
        if ($shopIntervalTime > 0) {
            //获取上次提现申请时间
            $startTime   = pdo_fetchcolumn("SELECT applytime FROM " . tablename(PDO_NAME . "settlement_record") . " WHERE sid = {$storeid} AND uniacid = {$_W['uniacid']} ORDER BY applytime DESC ");
            $interval    = time() - $startTime;
            $intervalDay = $interval / 3600 / 24;
            //判断间隔时间
            $intercalRes = ceil($shopIntervalTime - $intervalDay);
            if ($intercalRes > 0) {
                $this->renderError('请等' . $intercalRes . '天后再申请！。');
            }
        }
        //可提现金额
        $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , [
            'allmoney' ,
            'nowmoney' ,
            'reservestatus' ,
            'reservemoney',
            'autocash'
        ]);
        //预留金额设置
        if ($merchant['reservestatus']) {
            $reservemoney = $merchant['reservemoney'];
        }
        else {
            $reservemoney = sprintf("%.2f" , $cashsets['reservemoney']);
        }
        $usemoney = sprintf("%.2f" , $merchant['nowmoney'] - $reservemoney);
        if ($usemoney < 0) {
            $usemoney = 0;
        }
        if ($_GPC['settlementmoney'] > $usemoney) {
            $this->renderError("'申请失败,最大可以提现金额:{$usemoney}");
        }
        //生成记录
        $spercentmoney = sprintf("%.2f" , $syssalepercent * $settlementmoney / 100);
        $sgetmoney = sprintf("%.2f" , $settlementmoney - $spercentmoney);
        $data = [
            'uniacid'       => $_W['uniacid'] ,
            'sid'           => $storeid ,
            'aid'           => $storeaid ,
            'status'        => 2 ,
            'type'          => 1 ,
            'sapplymoney'   => $settlementmoney ,
            'sgetmoney'     => $sgetmoney ,
            'spercentmoney' => $spercentmoney ,
            'spercent'      => $syssalepercent ,
            'applytime'     => TIMESTAMP ,
            'updatetime'    => TIMESTAMP ,
            'sopenid'       => $sopenid ,
            'payment_type'  => $cashtype ,
            'source'        => $_W['source']
        ];
        if ($cashsets['noaudit']) {
            $data['status']     = 3;
            $trade_no           = time() . random(4 , true);
            $data['trade_no']   = $trade_no;
            $data['updatetime'] = time();
        }
        $value = serialize($data);
        $temporary = array(
            'info' => $value,
            'type' => 2,
            'uniacid' => $_W['uniacid'],
            'mid'  => $data['sid']
        );
        $res = pdo_insert(PDO_NAME . 'settlement_temporary' , $temporary);
        if($res){
            $this->renderSuccess('申请成功');
        }else{
            $this->renderError('申请失败，请重试');
        }
    }
    /**
     * Comment: 商户提现记录
     * Author: wlf
     * Date: 2019/8/23 11:35
     */
    public function cashList()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid'];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageStart = $page * 10 - 10;
        $status    = $_GPC['status'] ? $_GPC['status'] : 1;  // 1审核中 2已打款 3被驳回
        $where     = " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid} AND type = 1 ";
        if ($status == 1) {
            $where .= " AND status IN (1,2,3,4)";
        }
        else if ($status == 2) {
            $where .= " AND status = 5";
        }
        else if ($status == 3) {
            $where .= " AND status IN (-1,-2)";
        }
        $lists = pdo_fetchall("SELECT status,sapplymoney,sgetmoney,spercentmoney,updatetime FROM " . tablename('wlmerchant_settlement_record') . "{$where} ORDER BY applytime DESC LIMIT {$pageStart},10");
        foreach ($lists as &$li) {
            $li['updatetime'] = date('Y-m-d H:i:s' , $li['updatetime']);
        }
        $count             = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_settlement_record') . "{$where}");
        $data['totalpage'] = ceil($count / 10);
        $data['lists']     = $lists;
        $this->renderSuccess('提现列表' , $data);
    }
    /**
     * Comment: 输码核销接口
     * Author: wlf
     * Date: 2019/8/23 14:43
     */
    public function checkcodeCash()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];
        $code    = trim($_GPC['verifcode']);
        $order   = pdo_get(PDO_NAME . 'smallorder',['uniacid' => $_W['uniacid'],'sid' => $storeid ,'checkcode' => $code]);
        if($order['appointstatus'] > 0){
            if($order['appointstatus'] != 3){
                $this->renderError('此核销码未预约，无法核销');
            }else if($order['appstarttime'] > time()){
                $this->renderError('此核销码未到预约时间，无法核销');
            }else if($order['appendtime'] < time()){
                $this->renderError('此核销码预约时间已过期，无法核销');
            }
        }
        if ($order) {
            if ($order['status'] == 1) {
                if ($order['plugin'] == 'rush') {
                    $res = Rush::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                else if ($order['plugin'] == 'groupon') {
                    $res = Groupon::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                else if ($order['plugin'] == 'wlfightgroup') {
                    $res = Wlfightgroup::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                else if ($order['plugin'] == 'bargain') {
                    $res = Bargain::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                else if ($order['plugin'] == 'activity') {
                    $res = Activity::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                else if ($order['plugin'] == 'coupon') {
                    $couponid = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $order['orderid']] , 'recordid');
                    $res      = wlCoupon::hexiaoorder($couponid , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }else if ($order['plugin'] == 'hotel') {
                    $res = Hotel::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , 1 , $order['checkcode']);
                }
                if ($res) {
                    $this->renderSuccess('核销成功');
                }
                else {
                    $this->renderError('核销失败,请重试');
                }
            }
            else {
                $this->renderError('此核销码已核销完成');
            }
        }
        else {
            $this->renderError('核销码无效,请检查');
        }
    }
    /**
     * Comment: 验证记录列表
     * Author: wlf
     * Date: 2019/8/23 15:43
     */
    public function verifList()
    {
        global $_W , $_GPC;
        $data      = [];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageStart = ($page - 1) * 20;
        $admin     = $_GPC['verifmid'];
        $type      = trim($_GPC['type']);
        $storeid   = intval($_GPC['storeid']) ? intval($_GPC['storeid']) : $_W['storeid'];
        $time      = trim($_GPC['time']);
        $keyword   = trim($_GPC['keyword']);
        $where     = " WHERE uniacid = {$_W['uniacid']} AND  storeid = {$storeid} ";
        if (!empty($type)) {
            $where .= " AND plugin = '{$type}'";
        }
        if (!empty($admin)) {
            $where .= " AND verifmid = {$admin}";
        }
        if (!empty($time)) {
            $times     = explode(',' , $time);
            $starttime = $times[0];
            $endtime   = $times[1] + 86399;
            $where     .= " AND createtime > {$starttime} AND createtime < {$endtime}";
        }
        if (!empty($keyword)) {
            $where .= " AND orderno LIKE '%{$keyword}%' or verifnickname LIKE '%{$keyword}%'";
        }
        $allfen  = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename(PDO_NAME . "verifrecord") . $where);
        $myorder = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_verifrecord') . " {$where} ORDER BY id DESC LIMIT {$pageStart},20");
        $allnum  = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "verifrecord") . $where);
        foreach ($myorder as $key => &$value) {
            $member            = Member::wl_member_get($value['mid'] , ['nickname' , 'mobile']);
            $value['nickname'] = $member['nickname'];
            $value['mobile']   = $member['mobile'];
            if (empty($value['verifnickname'])) {
                $verifnickname          = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                    'uniacid' => $_W['uniacid'] ,
                    'mid'     => $value['verifmid'] ,
                    'storeid' => $value['storeid']
                ] , 'name');
                $value['verifnickname'] = $verifnickname;
            }
            $value['createtime'] = date('Y-m-d H:i:s' , $value['createtime']);
            //处理数据
            switch ($value['plugin']) {
                case 'rush':
                    $order               = pdo_get(PDO_NAME . 'rush_order' , ['id' => $value['orderid']] , [
                        'optionid' ,
                        'activityid'
                    ]);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $order['activityid']] , 'thumb');
                    $optionid            = $order['optionid'];
                    $value['pluginname'] = '抢购';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//抢购
                case 'groupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , [
                        'specid' ,
                        'fkid'
                    ]);
                    $optionid            = $order['specid'];
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $order['fkid']] , 'thumb');
                    $value['pluginname'] = '团购';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//团购
                case 'wlfightgroup':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , [
                        'specid' ,
                        'fkid'
                    ]);
                    $optionid            = $order['specid'];
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '拼团';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//拼团
                case 'coupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '卡券';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//卡券
                case 'wlcoupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '卡券';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//卡券
                case 'bargain':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $order['fkid']] , 'thumb');
                    $value['pluginname'] = '砍价';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//砍价
                case 'halfcard1':
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['storeid']] , 'logo');
                    $value['pluginname'] = '会员特权';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'timecardrecord' , ['id' => $value['verifrcode']] , 'realmoney');
                    break;//特权
                case 'halfcard2':
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['storeid']] , 'logo');
                    $value['pluginname'] = '大礼包';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'timecardrecord' , ['id' => $value['verifrcode']] , 'realmoney');
                    break;//大礼包
            }
            if ($optionid && $value['plugin'] != 'coupon' && $value['plugin'] != 'wlcoupon') {
                $value['optionname'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $optionid] , 'title');
            }
            $value['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $value['storeid']
            ] , 'storename');
            $value['goodimg']   = tomedia($value['goodimg']);
            //兼容处理
            if (empty($value['goodimg'])) $value['goodimg'] = $_W['siteroot'] . 'web/resource/images/nopic-107.png';
        }
        $data['list']      = $myorder;
        $data['allnum']    = intval($allnum);
        $data['allfen']    = intval($allfen);
        $data['totalpage'] = ceil($allnum / 20);
        $alladminarray     = [];
        if ($_GPC['getinfoflag']) {
            //所有店员
            $meroof = Setting::agentsetting_read('meroof');
            if ($meroof['veradmin']) {
                $ismain = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                    'mid'     => $_W['mid'] ,
                    'storeid' => $storeid
                ] , 'ismain');
                if ($ismain == 1) {
                    $alladmin = pdo_getall(PDO_NAME . 'merchantuser' , [
                        'uniacid' => $_W['uniacid'] ,
                        'storeid' => $storeid
                    ] , ['mid' , 'name' , 'mobile'] , 'mid');
                }
                else {
                    $alladmin = pdo_getall(PDO_NAME . 'merchantuser' , [
                        'uniacid'  => $_W['uniacid'] ,
                        'verifmid' => $_W['mid'] ,
                        'storeid'  => $storeid
                    ] , ['mid' , 'name' , 'mobile'] , 'mid');
                }
                foreach ($alladmin as $ak => &$av) {
                    $member       = Member::wl_member_get($av['mid'] , ['nickname' , 'mobile']);
                    $av['name']   = !empty($av['name']) ? $av['name'] : $member['nickname'];
                    $av['mobile'] = !empty($av['mobile']) ? $av['mobile'] : $member['mobile'];
                }
            }
            else {
                $alladmin = pdo_getall(PDO_NAME . 'merchantuser' , [
                    'uniacid' => $_W['uniacid'] ,
                    'storeid' => $storeid
                ] , ['mid' , 'name' , 'mobile'] , 'mid');
                foreach ($alladmin as $ak => $av) {
                    $member          = Member::wl_member_get($av['mid'] , ['nickname' , 'mobile']);
                    $av['name']      = !empty($av['name']) ? $av['name'] : $member['nickname'];
                    $av['mobile']    = !empty($av['mobile']) ? $av['mobile'] : $member['mobile'];
                    $alladminarray[] = $av;
                }
            }
            //所有分类
            $alladmin          = $alladminarray;
            $halfcardtext      = !empty($_W['wlsetting']['trade']['halfcardtext']) ? $_W['wlsetting']['trade']['halfcardtext'] : '一卡通';
            $alltype[] = ['name' => $halfcardtext ,'type' => 'halfcard1'];
            $alltype[] = ['name' => '大礼包' ,'type' => 'halfcard2'];
            $alltype[] = ['name' => '抢购' ,'type' => 'rush'];
            $alltype[] = ['name' => '卡券' ,'type' => 'wlcoupon'];
            $alltype[] = ['name' => '拼团' ,'type' => 'wlfightgroup'];
            $alltype[] = ['name' => '团购' ,'type' => 'groupon'];
            $alltype[] = ['name' => '砍价' ,'type' => 'bargain'];
            $data['typelist']  = $alltype;
            $data['adminlist'] = $alladmin;
        }
        $this->renderSuccess('验证记录数据' , $data);
    }
    /**
     * Comment: 店铺评价列表
     * Author: wlf
     * Date: 2019/8/23 16:45
     */
    public function commentList()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];
        $rank    = $_GPC['rank'];  //1好评 2中评 3差评
        $page    = $_GPC['page'] ? $_GPC['page'] : 1;
        $where   = ['uniacid' => $_W['uniacid'] , 'sid' => $storeid,'checkone' => 2];
        if($_GPC['personal'] > 0){
            $where['housekeepflag'] = 1;
        }
        if ($rank == 1) {
            $where['star#'] = "(4,5)";
        }
        else if ($rank == 2) {
            $where['star#'] = "(2,3)";
        }
        else if ($rank == 3) {
            $where['star'] = 1;
        }
        $myorder = Util::getNumData('id,text,createtime,nickname,status,star,replyone,gid,idoforder,plugin,headimg,sid,replytextone' , PDO_NAME . 'comment' , $where , 'ID DESC' , $page , 20 , 1);
        $allnum  = $myorder[2];
        $myorder = $myorder[0];
        foreach ($myorder as $key => &$value) {
            switch ($value['plugin']) {
                case 'rush':
                    if(empty($value['gid'])){
                        $activityid        = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['idoforder']] , 'activityid');
                    }else{
                        $activityid = $value['gid'];
                    }
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $activityid] , 'thumb');
                    break;
                case 'groupon':
                    if(empty($value['gid'])){
                        $activityid        = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['idoforder']] , 'fkid');
                    }else{
                        $activityid = $value['gid'];
                    }
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $activityid] , 'thumb');
                    break;
                case 'wlfightgroup':
                    if(empty($value['gid'])){
                        $activityid        = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['idoforder']] , 'fkid');
                    }else{
                        $activityid = $value['gid'];
                    }
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $activityid] , 'logo');
                    break;
                case 'coupon':
                    if(empty($value['gid'])){
                        $activityid        = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['idoforder']] , 'fkid');
                    }else{
                        $activityid = $value['gid'];
                    }
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $activityid] , 'logo');
                    break;
                case 'bargain':
                    if(empty($value['gid'])){
                        $activityid        = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['idoforder']] , 'fkid');
                    }else{
                        $activityid = $value['gid'];
                    }
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $activityid] , 'thumb');
                    break;
                case 'housekeep':
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'housekeep_service' , ['id' => $value['gid']] , 'thumb');
                    break;
                default:
                    $value['goodsimg'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['sid']] , 'logo');
                    break;
            }
            $value['headimg']    = tomedia($value['headimg']);
            $value['goodsimg']   = tomedia($value['goodsimg']);
            $value['createtime'] = date('Y-m-d H:i:s' , $value['createtime']);
        }
        $data['list']      = $myorder;
        $data['totalpage'] = ceil($allnum / 20);
        $this->renderSuccess('评价列表' , $data);
    }
    /**
     * Comment: 店铺回复评价
     * Author: wlf
     * Date: 2019/8/26 11:28
     */
    public function commentReply()
    {
        global $_W , $_GPC;
        $sid = $_GPC['storeid'];
        $orderid   = intval($_GPC['commentid']);
        $replytext = trim($_GPC['replytext']);
        if (empty($orderid) || empty($replytext)) {
            $this->renderError('缺少重要参数,请重试');
        }
        $comment   = pdo_get('wlmerchant_comment' , ['id' => $orderid] , ['mid' ,'housekeepflag', 'sid']);
        if($comment['housekeepflag'] > 0){
            $storename = pdo_getcolumn(PDO_NAME . 'housekeep_artificer' , ['id' => $comment['sid']] , 'name');
        }else {
            $aut = Store::checkAuthority('comment', $sid);
            if ($aut) {
                $this->renderError('商户暂无此权限');
            }
            $storename = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $comment['sid']] , 'storename');
        }
        //给评论用户发模板消息
        pdo_update(PDO_NAME . 'comment' , ['replytextone' => $replytext , 'replyone' => 2] , ['id' => $orderid]);
        $modelData = [
            'first'   => '商家回复了您的评论' ,
            'type'    => '评论回复提醒' ,//业务类型
            'content' => '商家[' . $storename . ']的回复' ,//业务内容
            'status'  => '已回复' ,//处理结果
            'time'    => date("Y-m-d H:i:s" , time()) ,//操作时间$store['createtime']
            'remark'  => '回复内容:' . $replytext
        ];
        //$url = h5_url('pages/mainPages/store/index',['sid'=>$comment['sid']]);
        TempModel::sendInit('service' , $comment['mid'] , $modelData , $_W['source']);
        $this->renderSuccess('回复成功');
    }
    /**
     * Comment: 店铺订单列表
     * Author: wlf
     * Date: 2019/8/23 10:01
     */
    public function orderList()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid'];
        $type      = $_GPC['type'];  //订单类型
        $status    = intval($_GPC['status']);  //订单状态
        $sort      = intval($_GPC['sort']);  //订单排序
        $time      = trim($_GPC['time']); //时间筛选
        $page      = $_GPC['page'];
        $keyword   = trim($_GPC['keyword']);
        $mid       = trim($_GPC['userid']);
        $pageStart = $page * 20 - 20;
        $settings = Setting::wlsetting_read('orderset');
        $data      = [];
        $where     = " where uniacid = {$_W['uniacid']} and sid = $storeid and orderno != 666666";
        if (!empty($time)) {
            if ($time == 'today') {
                $starttime = strtotime(date('Y-m-d'));
                $endtime   = $starttime + 86399;
            }
            else if ($time == 'week') {
                $starttime = strtotime("previous monday");
                $endtime   = time();
            }
            else if ($time == 'month') {
                $starttime = mktime(0 , 0 , 0 , date('m') , 1 , date('Y'));
                $endtime   = time();
            }
            else {
                $times     = explode(',' , $time);
                $starttime = (strtotime($times[0]) > time()) ? strtotime(date('Y-m-d')) : strtotime($times[0]);
                $endtime   = ($starttime > strtotime($times[1])) ? $starttime + 86399 : strtotime($times[1]) + 86399;
            }
            $where .= " AND createtime > {$starttime}";
            $where .= " AND createtime < {$endtime}";
        }
        if ($status) {
            if ($status == 3) {
                $where .= " AND status IN (2,3)";
            }
            else {
                $where .= " AND status = {$status}";
            }
        }
        else {
            $where .= " AND status IN (1,2,3,4,8,6,7,9)";
        }
        if (!empty($keyword)) {
            $where .= " AND orderno LIKE '%{$keyword}%'";
        }
        if (!empty($mid)) {
            $where .= " AND mid = {$mid}";
        }
        if ($type) {
            if ($type == 'rush') {
                $where2 = $where . " AND plugin IN ('null') ";
            }
            else {
                $where2 = $where . " AND plugin = '{$type}' ";
                $where  .= " and orderno = 00000 ";
            }
        }
        else {
            $where2 = $where . " AND plugin IN ('wlfightgroup','activity','groupon','coupon','bargain','halfcard','citydelivery','housekeep','hotel')";
        }
        //获取订单
        if ($sort) {
            $myorder = pdo_fetchall('SELECT id,createtime,sid,status,paidprid,mid,orderno,num,price,paytype,vipbuyflag,paytime,disorderid,mobile,remark,expressid,moinfo, "a" FROM ' . tablename(PDO_NAME . "order") . " {$where2}" . ' UNION ALL SELECT id,createtime,sid,status,paidprid,mid,orderno,num,price,paytype,vipbuyflag,paytime,disorderid,mobile,remark,expressid,moinfo, "b" FROM ' . tablename(PDO_NAME . "rush_order") . " {$where} ORDER BY createtime ASC LIMIT " . $pageStart . ',' . "20");
        }
        else {
            $myorder = pdo_fetchall('SELECT id,createtime,sid,status,paidprid,mid,orderno,num,price,paytype,vipbuyflag,paytime,disorderid,mobile,remark,expressid,moinfo, "a" FROM ' . tablename(PDO_NAME . "order") . " {$where2}" . ' UNION ALL SELECT id,createtime,sid,status,paidprid,mid,orderno,num,price,paytype,vipbuyflag,paytime,disorderid,mobile,remark,expressid,moinfo, "b" FROM ' . tablename(PDO_NAME . "rush_order") . " {$where} ORDER BY createtime DESC LIMIT " . $pageStart . ',' . "20");
        }
        foreach ($myorder as $key => &$value) {
            if ($value['a'] == 'a') {
                $aorder = pdo_get(PDO_NAME . 'order' , ['id' => $value['id']] ,['fkid','specid','plugin','buyremark','packingmoney','fightgroupid','deposit','starttime','endtime']);
                $value['fkid']   = $aorder['fkid'];
                $value['plugin'] = $aorder['plugin'];
                $value['remark'] = $aorder['buyremark'];
                $value['deposit'] = $aorder['deposit'];
                if ($value['plugin'] == 'coupon') {
                    $goods               = wlCoupon::getSingleCoupons($value['fkid'] , 'title,logo');
                    $value['gname']      = $goods['title'];
                    $value['gimg']       = $goods['logo'];
                    $value['pluginname'] = '卡券';
                }
                if ($value['plugin'] == 'wlfightgroup') {
                    $goods               = Wlfightgroup::getSingleGood($value['fkid'] , 'name,logo');
                    $value['gname']      = $goods['name'];
                    $value['gimg']       = $goods['logo'];
                    $value['pluginname'] = '拼团';
                    $optionid            = $aorder['specid'];
                }
                if ($value['plugin'] == 'groupon') {
                    $goods               = pdo_get(PDO_NAME . 'groupon_activity' , ['id' => $value['fkid']] , ['name','thumb']);
                    $value['gname']      = $goods['name'];
                    $value['gimg']       = $goods['thumb'];
                    $value['pluginname'] = '团购';
                    $optionid            = $aorder['specid'];

                }
                if ($value['plugin'] == 'activity') {
                    $goods               = pdo_get(PDO_NAME . 'activitylist' , ['id' => $value['fkid']] , ['title','thumb']);
                    $value['gname']      = $goods['title'];
                    $value['gimg']       = $goods['thumb'];
                    $value['pluginname'] = '活动';
                    $optionid            = $aorder['specid'];
                }
                if ($value['plugin'] == 'halfcard') {
                    if ($value['fkid']) {
                        $value['gname'] = pdo_getcolumn(PDO_NAME . 'halfcardlist' , ['id' => $value['fkid']] , 'title');
                    }
                    else {
                        $value['gname'] = '在线买单';
                    }
                    $value['gimg']       = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['sid']] , 'logo');
                    $value['pluginname'] = '买单';
                }
                if ($value['plugin'] == 'bargain') {
                    $goods               = pdo_get(PDO_NAME . 'bargain_activity' , ['id' => $value['fkid']] , ['name','thumb']);
                    $value['gname']      = $goods['name'];
                    $value['gimg']       = $goods['thumb'];
                    $value['pluginname'] = '砍价';
                }
                if ($value['plugin'] == 'housekeep') {
                    $goods               = pdo_get(PDO_NAME . 'housekeep_service' , ['id' => $value['fkid']] , ['title','thumb']);
                    $value['gname']      = $goods['title'];
                    $value['gimg']       = $goods['thumb'];
                    $value['pluginname'] = '家政';
                }
                if ($value['plugin'] == 'hotel') {
                    $goods               = pdo_get(PDO_NAME . 'hotel_room' , ['id' => $value['fkid']] , ['name','thumb','roomtype']);
                    $value['gname']      = $goods['name'];
                    $value['gimg']       = $goods['thumb'];
                    $value['pluginname'] = '房间';
                    if($goods['roomtype'] == 3){
                        $value['remark'] = '';
                        $value['livetime'] = date('m/d H:i',$aorder['starttime']).' - '.date('m/d H:i',$aorder['endtime']);
                    }else{
                        $value['remark'] = '到店时间:'.$value['remark'];
                        $value['livetime'] = date('y/m/d',$aorder['starttime']).' - '.date('y/m/d',$aorder['endtime']);
                    }
                }
                if($value['plugin'] == 'citydelivery'){
                    $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')." WHERE `tid` = '{$value['orderno']}' ORDER BY price DESC");
                    foreach ($smallorders  as $ke => &$orr){
                        $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                        $orr['name'] = $goods['name'];
                        if($orr['specid']>0){
                            $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                            $orr['name'] .= '/'.$specname;
                        }
                        if($ke == 0){
                            $value['gimg'] = tomedia($goods['thumb']);
                        }
                        unset($orr['gid']);
                        $value['goodsarray'][] = $orr;
                    }
                    $value['pluginname'] = '同城配送';
                    if($aorder['fightgroupid']>0){
                        $value['express'] = pdo_get('wlmerchant_express',array('id' => $aorder['fightgroupid']),array('name','tel','address','expressprice'));
                    }
                    $value['price'] = $value['price'] - $value['express']['expressprice'] - $aorder['packingmoney'];
                }
                //规格
                $value['goodsprice'] = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['id']] , 'goodsprice');
                $value['markprice'] = sprintf("%.2f" , $value['goodsprice'] + $value['deposit']  - $value['price']);
                $value['goodsprice'] = sprintf("%.2f" , $value['goodsprice'] / $value['num']);
            }
            else {
                $value['activityid'] = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['id']] , 'activityid');
                $value['gname']      = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $value['activityid']] , 'name');
                $value['gimg']       = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $value['activityid']] , 'thumb');
                $value['address']    = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['id']] , 'address');
                $value['username']   = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['id']] , 'username');
                //规格
                $optionid            = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['id']] , 'optionid');
                $allgmoney = $value['price'];
                $value['goodsprice'] = sprintf("%.2f" , $value['price'] / $value['num']);
                $value['price']      = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['id']] , 'actualprice');
                $value['markprice'] = sprintf("%.2f" , $allgmoney - $value['price']);
                $value['pluginname'] = '抢购';
                $value['plugin'] = 'rush';
            }
            //地址
            if ($value['expressid']) {
                if($value['plugin'] == 'citydelivery' || $value['plugin'] == 'housekeep'){
                    $value['express'] = pdo_get('wlmerchant_address',array('id' => $value['expressid']),['name','tel','province','city','county','detailed_address']);
                    if($value['plugin'] == 'citydelivery'){
                        $value['express']['address'] = $value['express']['province'].$value['express']['city'].$value['express']['county'].$value['express']['detailed_address'];
                    }else{
                        $value['express']['address'] = $value['express']['county'].$value['express']['detailed_address'];
                    }
                }else{
                    $value['express'] = pdo_get('wlmerchant_express' , ['id' => $value['expressid']] , [
                        'name' ,
                        'tel' ,
                        'address' ,
                        'expressname' ,
                        'expresssn'
                    ]);
                }
            }
            $member            = pdo_get(PDO_NAME . 'member' , [
                'id'      => $value['mid'] ,
                'uniacid' => $_W['uniacid']
            ] , ['nickname' , 'mobile','encodename']);
            $value['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
            if (empty($value['mobile'])) {
                $value['mobile'] = $member['mobile'];
            }
            $value['createtime'] = date('Y-m-d H:i:s' , $value['createtime']);
            if ($optionid) {
                if($value['plugin'] == 'activity'){
                    $value['optiontitle'] = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $optionid] , 'name');
                }else{
                    $value['optiontitle'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $optionid] , 'title');
                }
            }
            if (!$value['optiontitle']) {
                $value['optiontitle'] = '';
            }
            $value['gimg'] = tomedia($value['gimg']);
            switch ($value['status']) {
                case 1:
                    if($value['plugin'] == 'citydelivery'){
                        $value['statusname'] = '待自提';
                    }else if($value['plugin'] == 'wlfightgroup' && $aorder['fightgroupid'] > 0){
                        $groupstatus = pdo_getcolumn(PDO_NAME.'fightgroup_group',array('id'=>$aorder['fightgroupid']),'status');
                        if($groupstatus['status'] == 1){
                            $value['statusname'] = '组团中';
                            $value['status'] = 5;
                        }else{
                            $value['statusname'] = '待使用';
                        }
                    } else{
                        $value['statusname'] = '待使用';
                    }
                    break;
                case 2:
                    $value['statusname'] = '待评价';
                    break;
                case 3:
                    $value['statusname'] = '已完成';
                    break;
                case 4:
                    if($value['plugin'] == 'citydelivery'){
                        $value['statusname'] = '配送中';
                    }else {
                        $value['statusname'] = '待收货';
                    }
                    break;
                case 6:
                    $value['statusname'] = '待退款';
                    break;
                case 7:
                    $value['statusname'] = '已退款';
                    break;
                case 8:
                    if($value['plugin'] == 'citydelivery'){
                        $value['statusname'] = '待接单';
                    }else{
                        $value['statusname'] = '待发货';
                    }
                    break;
                case 9:
                    $value['statusname'] = '已过期';
                    break;
            }

            //判断当前订单是否存在售后申请
            if($value['a'] == 'a'){
                $aftersale = pdo_fetch("SELECT id,status FROM ".tablename(PDO_NAME."aftersale")
                    ." WHERE plugin != 'rush' AND orderid = {$value['id']} ");
            }else{
                $aftersale = pdo_fetch("SELECT id,status FROM ".tablename(PDO_NAME."aftersale")
                    ." WHERE plugin = 'rush' AND orderid = {$value['id']} ");
            }
            //状态 1申请中 2处理中 3已驳回 4用户取消
            if($aftersale) {
                switch ($aftersale['status']) {
                    case 1:
                        $value['statusname'] = '退款申请中';
                        if($settings['storerefund']>0){
                            $value['refundflag'] = 1;
                        }
                        break;//申请中
                    case 2:
                        $value['statusname'] = '已退款';
                        break;//处理中
                    //case 3:$value['statusname'] = '退款驳回';;break;//已驳回
                    //case 4:$value['statusname'] = '取消退款申请';;break;//用户取消
                }
            }

            //自定义表单信息处理
            $moinfo = unserialize($value['moinfo']);
            if($moinfo){
                foreach($moinfo as &$moinfoItem){
                    if($moinfoItem['id'] == 'img'){
                        foreach($moinfoItem['data'] as $imgKey => $imgLink){
                            $moinfoItem['data'][$imgKey] = tomedia($imgLink);
                        }
                    }
                }
                $value['moinfo'] = $moinfo;
                $value['moinfoflag'] = 1;
            }else{
                $value['moinfo'] = [];
                $value['moinfoflag'] = 0;
            }


        }
        $data['orderlist'] = $myorder;
        if ($_GPC['getinfoflag']) {
            //初始化数据
            $data['statuslist'] = [
                0   => '全部状态' ,
                1   => '待使用' ,
                2   => '待评价' ,
                3   => '已完成' ,
                4   => '待收货' ,
                6   => '待退款' ,
                7   => '已退款' ,
                8   => '待发货' ,
                9   => '已过期'
            ];
            $data['typelist']   = [0 => '全部' , 'rush' => '抢购' , 'coupon' => '卡券'];
            if (p('wlfightgroup')) {
                $data['typelist']['wlfightgroup'] = '拼团';
            }
            if (p('groupon')) {
                $data['typelist']['groupon'] = '团购';
            }
            if (p('halfcard')) {
                $data['typelist']['halfcard'] = '买单';
            }
            if (p('bargain')) {
                $data['typelist']['bargain'] = '砍价';
            }
            if (p('citydelivery')) {
                $data['typelist']['citydelivery'] = '同城配送';
            }
            if (p('housekeep')) {
                $data['typelist']['housekeep'] = '家政服务';
            }
            $data['sortlist'] = [0 => '时间降序' , 1 => '时间升序'];
            //统计
            $rushordernum          = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . "{$where}");
            $ordernum              = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . "{$where2}");
            $data['allordernum']   = $rushordernum + $ordernum;
            $rushordermoney        = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . "{$where}");
            $ordermoney            = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . "{$where2}");
            $data['allordermoney'] = $rushordermoney + $ordermoney;
            $rushgoodnum           = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename('wlmerchant_rush_order') . "{$where}");
            $goodnum               = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename('wlmerchant_order') . "{$where2}");
            $data['goodnum']       = $rushgoodnum + $goodnum;
            $data['pagetotal']     = ceil($data['allordernum'] / 20);
        }
        //家政隐藏完成按钮
        if (p('housekeep')) {
            $set = Setting::agentsetting_read('housekeep');
            $data['hkhidefish'] = $set['storefish'] ? : 0;
        }

        if(Customized::init('citycard1503')>0){
            $data['fishimg'] = 1;
        }else{
            $data['fishimg'] = 0;
        }

        $this->renderSuccess('订单数据' , $data);
    }
    /**
     * Comment: 商户分类信息列表
     * Author: zzw
     * Date: 2019/8/26 14:07
     */
    public function cateList()
    {
        global $_W , $_GPC;
        #1、获取参数信息
        $type = $_GPC['type'] ? : 0;//0=只获取一级分类（默认）；1=需要获取二级分类
        $aid =  $_W['aid'];
        $regionId   = $_GPC['areaid'] ? : 0;
        if ($regionId > 0){
            $getAid = pdo_getcolumn(PDO_NAME . "oparea" , [
                'areaid'  => $regionId ,
                'status'  => 1 ,
                'uniacid' => $_W['uniacid']
            ] , 'aid');
            if(empty($getAid)){
                $this->renderSuccess('无信息',[]);
            }else{
                $aid = $getAid;
            }
        }
        #2、获取商户分类列表
        $list = pdo_getall(PDO_NAME . "category_store" , [
            'aid'      => $aid,
            'uniacid'  => $_W['uniacid'] ,
            'state'    => 0 ,
            'parentid' => 0 ,
            'enabled'  => 1
        ] , ['id' , 'name' , 'thumb'] , '' , ' displayorder DESC ');
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $key => &$val) {
                $val['thumb'] = tomedia($val['thumb']);
                //获取当前一级分类下的二级分类
                if ($type == 1) {
                    $twoList = pdo_getall(PDO_NAME . "category_store" , [
                        'aid'      => $_W['aid'] ,
                        'uniacid'  => $_W['uniacid'] ,
                        'state'    => 0 ,
                        'parentid' => $val['id'] ,
                        'enabled'  => 1
                    ] , [
                        'id' ,
                        'name' ,
                        'thumb'
                    ] , '' , ' displayorder DESC ');
                    if (is_array($twoList) && count($twoList) > 0) {
                        foreach ($twoList as $index => &$item) {
                            $item['thumb'] = tomedia($item['thumb']);
                        }
                    }
                    $val['list'] = $twoList;
                }
            }
        }
        $this->renderSuccess('商户一级分类信息' , $list);
    }
    /**
     * Comment: 获取某个用户下的所有店铺列表
     * Author: zzw
     * Date: 2019/8/30 16:18
     */
    public function userShopList()
    {
        global $_W , $_GPC;
        #1、参数获取
        $mid = $_GPC['mid'] ? : -1;
        $lng = $_GPC['lng'] ? : 0;//104.0091133118 经度
        $lat = $_GPC['lat'] ? : 0;//30.5681964123  纬度
        #2、获取信息
        $list = Store::getUserShopList($mid , $lng , $lat);
        $this->renderSuccess('用户店铺列表' , $list);
    }
    /**
     * Comment: 切换商户列表
     * Author: wlf
     * Date: 2019/09/24 16:52
     */
    public function userStoreList()
    {
        global $_W , $_GPC;
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        $where = " WHERE a.mid = {$_W['mid']} AND a.ismain != 2 AND a.enabled = 1";
        #1、获取有效代理列表
        $agentIds = pdo_getall(PDO_NAME . "agentusers" , ['uniacid' => $_W['uniacid']] , ['id']);
        $aids     = array_column($agentIds , 'id');
        if (is_array($aids) && count($aids) > 1) {
            $where .= " AND a.aid IN (0," . implode(',' , $aids) . ") ";
        }
        else if (count($aids) == 1) {
            $where .= " AND a.aid IN (0,{$aids['0']}) ";
        }
        $sql    = "SELECT a.storeid,a.ismain,b.status,a.reject,a.aid,a.manage_store,a.hasmanage FROM " . tablename(PDO_NAME . "merchantuser") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.storeid = b.id {$where} ORDER BY a.ismain ASC,a.id DESC ";
        $stores = pdo_fetchall($sql);
        $data   = [];
        if (empty($stores)) {
            $this->renderError('您没有可管理商户');
        }else {
            if(p('attestation')){
                $attset = Setting::wlsetting_read('attestation');
            }else{
                $attset = [];
            }
            foreach ($stores as &$store) {
                $user              = [];
                $user['status']    = $store['status'];
                $merchant          = pdo_get('wlmerchant_merchantdata' , ['id' => $store['storeid']] , [
                    'storename' ,
                    'logo'
                ]);
                $user['storename'] = $merchant['storename'];
                $user['storelogo'] = tomedia($merchant['logo']);
                $user['storeid']   = $store['storeid'];
                if ($store['ismain'] == 1) {
                    $user['ismain'] = '店长';
                }else if ($store['ismain'] == 3) {
                    $user['ismain'] = '管理员';
                }else if ($store['ismain'] == 4) {
                    $user['ismain'] = '业务员';
                }
                if ($store['status'] == 0) {
                    $user['orderid'] = pdo_getcolumn(PDO_NAME . 'order' , [
                        'sid'    => $user['storeid'] ,
                        'status' => 0 ,
                        'plugin' => 'store'
                    ] , 'id');
                    if (!$user['orderid']) {
                        $user['orderid'] = 0;
                    }
                    $user['reject'] = '您还未支付入驻金额';
                }
                else if ($store['status'] == 3) {
                    $user['reject'] = $store['reject'];
                }
                //认证管理商户权限
                if($attset['switch'] == 1 && $attset['attmanage'] == 1 && $store['status'] == 2){
                    $attestation = Attestation::checkAttestation(2,$store['storeid']);
                    if(empty($attestation['attestation'])){
                        $user['reject'] = '您的商户未认证';
                        $user['status'] = 4;
                    }else if($attestation['attestation'] == 1){
                        $user['reject'] = '您的商户认证中';
                        $user['status'] = 4;
                    }else if($attestation['attestation'] == 3){
                        $user['reject'] = '您的商户认证被驳回';
                        $user['status'] = 4;
                    }
                }
                //代理商信息获取
                $agentName = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$store['aid']],'agentname');
                $user['agent_name'] = $agentName ? $agentName : '总平台';
                //判断业务员权限
                if ($store['ismain'] == 4){
                    if($store['manage_store']){
                        if($store['hasmanage']){
                            $data[] = $user;
                        }
                    }else{
                        $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$store['aid'],'key' => 'salesman'),array('value'));
                        $setting = unserialize($setting['value']);
                        if($setting['hasmanage']){
                            $data[] = $user;
                        }
                    }
                }else{
                    $data[] = $user;
                }
            }
            $set['settled'] = $storeSet['storeSetted'] ? 0 : 1;
            $info = [
                'list' =>  $data,
                'set'  =>  $set
            ];

            if(empty($data)){
                $this->renderError('您没有可管理商户');
            }else{
                $this->renderSuccess('切换商户列表' , $info);
            }
        }
    }
    /**
     * Comment: 修改店员状态
     * Author: wlf
     * Date: 2019/09/24 18:38
     */
    public function changeAdminEnabled()
    {
        global $_W , $_GPC;
        $userid      = $_GPC['userid'];
        $userEnabled = pdo_getcolumn(PDO_NAME . 'merchantuser' , ['id' => $userid] , 'enabled');
        $ismain      = pdo_getcolumn(PDO_NAME . 'merchantuser' , ['id' => $userid] , 'ismain');
        if ($ismain == 1) {
            $this->renderError('店长不能被停用');
        }
        if ($userEnabled) {
            $res = pdo_update('wlmerchant_merchantuser' , ['enabled' => 0] , ['id' => $userid]);
            if ($res) {
                $this->renderSuccess('停用成功');
            }
            else {
                $this->renderError('停用失败');
            }
        }
        else {
            $res = pdo_update('wlmerchant_merchantuser' , ['enabled' => 1] , ['id' => $userid]);
            if ($res) {
                $this->renderSuccess('启用成功');
            }
            else {
                $this->renderError('启用失败');
            }
        }
    }
    /**
     * Comment: 店铺客户列表
     * Author: wlf
     * Date: 2019/09/25 17:27
     */
    public function storeFansList()
    {
        global $_W , $_GPC;
        $storeid        = $_GPC['storeid'] ? $_GPC['storeid'] : $_W['storeid'];
        $page           = $_GPC['page'] ? $_GPC['page'] : 1;
        $initialization = $_GPC['initialization'];
        $keyword        = trim($_GPC['keyword']);
        $pageStart      = $page * 10 - 10;
        $data           = [];
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('paybackstatus'));
        if (!empty($initialization)) {
            //获取今日新增客户
            $time               = strtotime(date('Y-m-d' , time()));
            $data['newfansnum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_storefans') . " WHERE sid = {$storeid} AND createtime > {$time} ");
            //总客户数量
            $data['allfansnum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_storefans') . " WHERE sid = {$storeid}");
        }
        $where = " WHERE a.sid = {$storeid} AND a.uniacid = {$_W['uniacid']}";
        if (!empty($keyword)) {
            $where .= " AND (b.nickname LIKE '%{$keyword}%' or b.realname LIKE '%{$keyword}%')";
        }
        $fans              = pdo_fetchall("SELECT a.id,a.mid,a.createtime,b.avatar,b.nickname,b.realname FROM " . tablename(PDO_NAME . "storefans") . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id {$where} ORDER BY createtime DESC LIMIT {$pageStart},10");
        $allfans           = pdo_fetchcolumn("SELECT count(a.id) FROM " . tablename(PDO_NAME . "storefans") . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id {$where}");
        $data['pagetotal'] = ceil($allfans / 10);
        if ($fans) {
            foreach ($fans as &$fan) {
                $fan['avatar']     = tomedia($fan['avatar']);
                $fan['createtime'] = date('Y-m-d H:i' , $fan['createtime']);
                //抢购订单统计
                $rushordernum   = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . " WHERE sid = {$storeid} AND mid = {$fan['mid']} AND status IN (1,2,3,4,8,9)");
                $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . " WHERE sid = {$storeid} AND mid = {$fan['mid']} AND status IN (1,2,3,4,8,9)");
                //其他订单统计
                $ordernum             = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . " WHERE sid = {$storeid} AND mid = {$fan['mid']} AND status IN (1,2,3,4,8,9)");
                $ordermoney           = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . " WHERE sid = {$storeid} AND mid = {$fan['mid']} AND status IN (1,2,3,4,8,9)");
                $fan['allordernum']   = sprintf("%.0f" , $rushordernum + $ordernum);
                $fan['allordermoney'] = sprintf("%.2f" , $rushordermoney + $ordermoney);
                pdo_update('wlmerchant_storefans' , ['isread' => 1] , ['id' => $fan['id']]);
            }
        }
        $data['list'] = $fans;
        if(p('payback') && $store['paybackstatus'] > 0 ){
            $data['paybak'] = 1;
        }
        $this->renderSuccess('客户数据' , $data);
    }
    /**
     * Comment: 店铺消息列表
     * Author: wlf
     * Date: 2019/09/26 09:42
     */
    public function storeNewList()
    {
        global $_W , $_GPC;
        #1参数获取
        $storeid   = $_GPC['storeid'] ? $_GPC['storeid'] : $_W['storeid'];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        #2、信息列表获取
        $list                 = Im::myList($storeid , 2 , $page , $pageIndex);
        $data['list']         = $list['list'];
        $data['total']        = $list['total'];
        $data['newnoticenum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_systemnotice') . " WHERE sid = {$storeid} AND isread = 0");
        $data['newusernum']   = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_storefans') . " WHERE sid = {$storeid} AND isread = 0");
        $this->renderSuccess('商户通讯信息列表' , $data);
    }
    /**
     * Comment: 店铺个人中心
     * Author: wlf
     * Date: 2019/09/28 17:59
     */
    public function storeMember()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid'];
        $storeinfo = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , [
            'storename' ,
            'logo' ,
            'endtime' ,
            'nowmoney',
            'recruit_switch',//是否开启求职招聘功能：0=关闭，1=开启
            'housekeepstatus', //是否开启家政服务功能：0=关闭，1=开启
            'temclose',  //临时休息参数 0 营业中 1休息中
            'housestatus',  //是否开启房产功能：0=关闭，1=开启
            'hotelstatus',  //是否开启酒店功能：0=关闭，1=开启
        ]);
        $data      = [
            'storename' => $storeinfo['storename'] ,
            'storelogo' => tomedia($storeinfo['logo']) ,
            'endtime'   => date('Y-m-d' , $storeinfo['endtime']) ,
            'nowmoney'  => $storeinfo['nowmoney'],
            'recruit_switch'  => $storeinfo['recruit_switch'],
            'housekeepstatus' => $storeinfo['housekeepstatus'],
            'temclose'  => $storeinfo['temclose'],
            'housestatus'  => $storeinfo['housestatus'],
            'hotelstatus'  => $storeinfo['hotelstatus']
        ];
        //判断是否开启商家入驻
        $set         = Setting::wlsetting_read('attestation');
        $set['type'] = is_array(unserialize($set['type'])) ? unserialize($set['type']) : [];
        if ($set['switch'] == 1 && in_array('store' , $set['type'])) {
            $data['switch'] = 1;
        }
        else {
            $data['switch'] = 0;
        }
        if (p('attestation')) {
            $data['attestation'] = Attestation::checkAttestation(2 , $storeid);
        }
        //入住114标识
        if(p('yellowpage')){
            $ismain = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                'mid'     => $_W['mid'] ,
                'storeid' => $storeid
            ] , 'ismain');
            if($ismain == 1){
                $flag = pdo_getcolumn(PDO_NAME.'yellowpage_lists',array('storeid'=>$storeid),'id');
                if(empty($flag)){
                    $data['oofflag'] = 1;
                }else{
                    $data['oofflag'] = 2;
                }
            }
        }
		//商户红包表单
		$redfalg = pdo_get('wlmerchant_store_redpack_record',array('sid' => $storeid),array('id'));
		if(empty($redfalg)){
			$data['diyformstatus'] = 0;
		}else{
			$data['diyformstatus'] = 1;
		}
        $this->renderSuccess('商户个人中心' , $data);
    }
    /**
     * Comment: 隐藏显示评价接口
     * Author: wlf
     * Date: 2019/10/08 16:12
     */
    public function hideCommnet()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];
        $aut = Store::checkAuthority('comment' , $storeid);
        if($aut){
            $this->renderError('商户暂无此权限');
        }
        $id           = $_GPC['commentid'];
        $comentStatus = pdo_getcolumn(PDO_NAME . 'comment' , ['id' => $id] , 'status');
        if ($comentStatus) {
            $res = pdo_update('wlmerchant_comment' , ['status' => 0] , ['id' => $id]);
            if ($res) {
                $this->renderSuccess('隐藏成功');
            }
            else {
                $this->renderError('隐藏失败，请重试');
            }
        }
        else {
            $res = pdo_update('wlmerchant_comment' , ['status' => 1] , ['id' => $id]);
            if ($res) {
                $this->renderSuccess('显示成功');
            }
            else {
                $this->renderError('显示失败，请重试');
            }
        }
    }
    /**
     * Comment: 商家回复评价详情
     * Author: wlf
     * Date: 2019/10/08 16:45
     */
    public function commnetDetail()
    {
        global $_W , $_GPC;
        $id      = $_GPC['commentid'];
        $comment = pdo_get('wlmerchant_comment' , ['id' => $id] , [
            'gid' ,
            'mid' ,
            'sid' ,
            'pic' ,
            'idoforder' ,
            'text' ,
            'createtime' ,
            'star' ,
            'plugin',
            'replytextone'
        ]);
        $member  = pdo_get('wlmerchant_member' , ['id' => $comment['mid']] , ['avatar' , 'nickname']);
        $data    = [
            'pic'        => unserialize($comment['pic']) ,
            'text'       => $comment['text'] ,
            'createtime' => date('Y-m-d H:i' , $comment['createtime']) ,
            'star'       => $comment['star'] ,
            'nickname'   => $member['nickname'] ,
            'avatar'     => tomedia($member['avatar']) ,
        ];
        switch ($comment['plugin']) {
            case 'rush' :
                $goodsid               = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $comment['idoforder']] , 'activityid');
                $goods                 = pdo_get('wlmerchant_rush_activity' , ['id' => $goodsid] , [
                    'name' ,
                    'thumb' ,
                    'price' ,
                    'oldprice'
                ]);
                $data['goodsname']     = $goods['name'];
                $data['goodsimg']      = tomedia($goods['thumb']);
                $data['goodsprcie']    = $goods['price'];
                $data['goodsoldprice'] = $goods['oldprice'];
                break;
            case 'wlfightgroup':
                $goodsid               = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $comment['idoforder']] , 'fkid');
                $goods                 = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $goodsid] , [
                    'name' ,
                    'logo' ,
                    'price' ,
                    'oldprice'
                ]);
                $data['goodsname']     = $goods['name'];
                $data['goodsimg']      = tomedia($goods['logo']);
                $data['goodsprcie']    = $goods['price'];
                $data['goodsoldprice'] = $goods['oldprice'];
                break;
            case 'bargain':
                $goodsid               = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $comment['idoforder']] , 'fkid');
                $goods                 = pdo_get('wlmerchant_bargain_activity' , ['id' => $goodsid] , [
                    'name' ,
                    'thumb' ,
                    'price' ,
                    'oldprice'
                ]);
                $data['goodsname']     = $goods['name'];
                $data['goodsimg']      = tomedia($goods['thumb']);
                $data['goodsprcie']    = $goods['price'];
                $data['goodsoldprice'] = $goods['oldprice'];
                break;
            case 'groupon':
                $goodsid               = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $comment['idoforder']] , 'fkid');
                $goods                 = pdo_get('wlmerchant_groupon_activity' , ['id' => $goodsid] , [
                    'name' ,
                    'thumb' ,
                    'price' ,
                    'oldprice'
                ]);
                $data['goodsname']     = $goods['name'];
                $data['goodsimg']      = tomedia($goods['thumb']);
                $data['goodsprcie']    = $goods['price'];
                $data['goodsoldprice'] = $goods['oldprice'];
                break;
            case 'coupon':
                $goodsid               = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $comment['idoforder']] , 'fkid');
                $goods                 = pdo_get('wlmerchant_couponlist' , ['id' => $goodsid] , [
                    'title' ,
                    'logo' ,
                    'price'
                ]);
                $data['goodsname']     = $goods['title'];
                $data['goodsimg']      = tomedia($goods['logo']);
                $data['goodsprcie']    = $goods['price'];
                $data['goodsoldprice'] = 0;
                break;
            case 'housekeep':
                $goods                 = pdo_get('wlmerchant_housekeep_service' , ['id' => $comment['gid']] , [
                    'title' ,
                    'thumb' ,
                    'pricetype',
                    'price',
                    'unit'
                ]);
                $data['goodsname']     = $goods['title'];
                $data['goodsimg']      = tomedia($goods['thumb']);
                $data['goodsprcie']    = $goods['price'];
                $data['pricetype']     = $goods['pricetype'];
                $data['goodsoldprice'] = 0;
                //查询留言信息
                $data['replylist'] = pdo_fetchall("SELECT a.id,a.content,b.nickname FROM ".tablename(PDO_NAME."housekeep_reply")." a LEFT JOIN".tablename('wlmerchant_member')." b ON a.smid = b.id WHERE cid = {$id} LIMIT 5");
                break;
            default :
                $merchant              = pdo_get('wlmerchant_merchantdata' , ['id' => $comment['sid']] , [
                    'storename' ,
                    'logo'
                ]);
                $data['goodsname']     = $merchant['storename'];
                $data['goodsimg']      = tomedia($merchant['logo']);
                $data['goodsprcie']    = 0;
                $data['goodsoldprice'] = 0;
                break;
        }
        $this->renderSuccess('评价数据' , $data);
    }
    /**
     * Comment: 商家发布动态页面初始化
     * Author: wlf
     * Date: 2020/02/26 10:35
     */
    public function addDynamicPage(){
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];  //店铺id
        $groupid = pdo_getcolumn('wlmerchant_merchantdata' , ['id' => $storeid] , 'groupid');
        if (empty($groupid)) {
            $authority = 0;
        }
        else {
            $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $groupid] , 'authority');
        }
        $authority = unserialize($authority);
        if (!empty($authority)) {
            if (!in_array('dynamic' , $authority)) {
                $this->renderSuccess('商户暂无此权限',array('status'=>1));
            }
        }
        $this->renderSuccess('商户有此权限',array('status'=>0));
    }

    /**
     * Comment: 商家发布动态
     * Author: wlf
     * Date: 2019/10/09 9:28
     */
    public function addDynamic()
    {
        global $_W , $_GPC;
        $storeid = $_GPC['storeid'];  //店铺id
        $aut = Store::checkAuthority('dynamic' , $storeid);
        if($aut){
            $this->renderError('商户暂无此权限');
        }
        $content = $_GPC['content'];  //动态内容
        $pic     = $_GPC['pic'];
        if (!empty($pic)) {
            $pic = explode(',' , $_GPC['pic']);
            $pic = serialize($pic);  //动态图片
        }
        else {
            $pic = '';
        }
        $store = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , ['storename' , 'aid' , 'uniacid' , 'audits']);
        if (empty($content)) {
            $this->renderSuccess('请输入动态内容');
        }
        $data = [
            'uniacid'    => $store['uniacid'] ,
            'aid'        => $store['aid'] ,
            'sid'        => $storeid ,
            'mid'        => $_W['mid'] ,
            'content'    => $content ,
            'imgs'       => $pic ,
            'createtime' => time() ,
            'status'     => $store['audits']
        ];
        $res  = pdo_insert('wlmerchant_store_dynamic' , $data);
        if ($res) {
            if ($store['audits'] != 1) {
                //未开启免审核   给代理商管理员发送模板消息
                $first   = "商户【{$store['storename']}】发布了动态信息";//消息头部
                $type    = "商家动态审核";//业务类型
                $content = !empty($content) ? $content : '';//业务内容
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = $data['createtime'];//操作时间
                News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , $time);
            }
            $this->renderSuccess('发布成功');
        }
        else {
            $this->renderError('发布失败,请重试');
        }
    }
    /**
     * Comment: 商家删除动态
     * Author: wlf
     * Date: 2019/10/09 10:08
     */
    public function deleteDynamic()
    {
        global $_W , $_GPC;
        $id  = $_GPC['id'];  //动态id
        $res = pdo_delete('wlmerchant_store_dynamic' , ['id' => $id]);
        if ($res) {
            $this->renderSuccess('删除成功');
        }
        else {
            $this->renderError('删除失败,请重试');
        }
    }
    /**
     * Comment: 商家系统通知列表
     * Author: wlf
     * Date: 2019/10/09 17:37
     */
    public function sysNoticeList()
    {
        global $_W , $_GPC;
        $storeid           = $_GPC['storeid'] ? $_GPC['storeid'] : $_W['storeid'];
        $page              = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageStart         = $page * 10 - 10;
        $data              = [];
        $list              = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_systemnotice') . "WHERE sid = {$storeid} ORDER BY createtime DESC LIMIT {$pageStart},10 ");
        $allnum            = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_systemnotice') . " WHERE uniacid = {$_W['uniacid']} AND sid = {$storeid}");
        $data['pagetotal'] = ceil($allnum / 10);
        $newlist           = [];
        if ($list) {
            foreach ($list as $li) {
                $arr       = [];
                $arr['id'] = $li['id'];
                switch ($li['type']) {
                    case 1:
                        $order           = pdo_get('wlmerchant_rush_order' , ['id' => $li['objid']] , [
                            'activityid' ,
                            'mid'
                        ]);
                        $goods           = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']] , [
                            'name' ,
                            'thumb'
                        ]);
                        $member          = pdo_get('wlmerchant_member' , ['id' => $order['mid']] , [
                            'nickname' ,
                            'avatar'
                        ]);
                        $arr['head']     = '订单消息';
                        $arr['title']    = $goods['name'];
                        $arr['thumb']    = tomedia($goods['thumb']);
                        $arr['subtitle'] = $member['nickname'];
                        $arr['subthumb'] = tomedia($member['avatar']);
                        break;
                    case 2:
                        $arr['head'] = '订单消息';
                        $order       = pdo_get('wlmerchant_order' , ['id' => $li['objid']] , [
                            'fkid' ,
                            'plugin' ,
                            'mid' ,
                            'sid'
                        ]);
                        if ($order['plugin'] == 'groupon') {
                            $goods        = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $arr['title'] = $goods['name'];
                            $arr['thumb'] = tomedia($goods['thumb']);
                        }
                        else if ($order['plugin'] == 'wlfightgroup') {
                            $goods        = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']] , [
                                'name' ,
                                'logo'
                            ]);
                            $arr['title'] = $goods['name'];
                            $arr['thumb'] = tomedia($goods['logo']);
                        }
                        else if ($order['plugin'] == 'coupon') {
                            $goods        = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']] , [
                                'title' ,
                                'logo'
                            ]);
                            $arr['title'] = $goods['title'];
                            $arr['thumb'] = tomedia($goods['logo']);
                        }
                        else if ($order['plugin'] == 'halfcard') {
                            $goods        = pdo_get('wlmerchant_merchantdata' , ['id' => $order['sid']] , [
                                'storename' ,
                                'logo'
                            ]);
                            $arr['title'] = $goods['storename'] . '在线买单';
                            $arr['thumb'] = tomedia($goods['logo']);
                        }
                        else if ($order['plugin'] == 'bargain') {
                            $goods        = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $arr['title'] = $goods['name'];
                            $arr['thumb'] = tomedia($goods['thumb']);
                        }
                        $member          = pdo_get('wlmerchant_member' , ['id' => $order['mid']] , [
                            'nickname' ,
                            'avatar'
                        ]);
                        $arr['subtitle'] = $member['nickname'];
                        $arr['subthumb'] = tomedia($member['avatar']);
                        break;
                    case 3:
                        $arr['head']  = '提现消息';
                        $goods        = pdo_get('wlmerchant_merchant' , ['id' => $li['sid']] , ['storename' , 'logo']);
                        $record       = pdo_get('wlmerchant_settlement_record' , ['id' => $li['objid']] , ['sgetmoney']);
                        $arr['title'] = '提现￥' . $record['sgetmoney'];
                        $arr['thumb'] = tomedia($goods['logo']);
                        if ($li['status'] == 1) {
                            $arr['subtitle'] = '审核通过';
                        }
                        else if ($li['status'] == 2) {
                            $arr['subtitle'] = '申请驳回';
                        }
                        else if ($li['status'] == 3) {
                            $arr['subtitle'] = '已打款:+￥' . $record['sgetmoney'];
                        }
                        break;
                    case 4:
                        $arr['head']     = '粉丝消息';
                        $goods           = pdo_get('wlmerchant_member' , ['id' => $li['objid']] , [
                            'nickname' ,
                            'avatar'
                        ]);
                        $arr['title']    = $goods['nickname'];
                        $arr['thumb']    = tomedia($goods['avatar']);
                        $arr['subtitle'] = '成为了店铺的新粉丝';
                        break;
                }
                //处理时间
                $differtime = time() - $li['createtime'];
                if ($differtime < 60) {
                    $arr['timetext'] = $differtime . '秒前';
                }
                else if ($differtime < 3600) {
                    $differtime      = floor($differtime / 60);
                    $arr['timetext'] = $differtime . '分前';
                }
                else if ($differtime < 24 * 3600) {
                    $differtime      = floor($differtime / 3600);
                    $arr['timetext'] = $differtime . '小时前';
                }
                else {
                    $arr['timetext'] = date('Y-m-d H:i' , $li['createtime']);
                }
                $newlist[] = $arr;
                pdo_update('wlmerchant_systemnotice' , ['isread' => 1] , ['id' => $li['id']]);
            }
            $data['list'] = $newlist;
        }
        else {
            $data['list'] = [];
        }
        $this->renderSuccess('通知列表' , $data);
    }
    /**
     * Comment: 商家系统公告列表
     * Author: wlf
     * Date: 2019/10/10 11:47
     */
    public function agentNoticeList()
    {
        global $_W , $_GPC;
        $page              = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageStart         = $page * 10 - 10;
        $data              = [];
        $allnum            = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_notice') . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND enabled = 1");
        $data['pagetotal'] = ceil($allnum / 10);
        $list              = pdo_fetchall("SELECT title,createtime,id,link FROM " . tablename('wlmerchant_notice') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND enabled = 1 ORDER BY createtime DESC LIMIT {$pageStart},10 ");
        foreach ($list as &$li) {
            //处理时间
            $differtime = time() - $li['createtime'];
            if ($differtime < 60) {
                $li['timetext'] = $differtime . '秒前';
            }
            else if ($differtime < 3600) {
                $differtime     = floor($differtime / 60);
                $li['timetext'] = $differtime . '分前';
            }
            else if ($differtime < 24 * 3600) {
                $differtime     = floor($differtime / 3600);
                $li['timetext'] = $differtime . '小时前';
            }
            else {
                $li['timetext'] = date('Y-m-d H:i' , $li['createtime']);
            }
        }
        if (!empty($list)) {
            $data['list'] = $list;
        }
        else {
            $data['list'] = [];
        }
        $this->renderSuccess('公告列表' , $data);
    }
    /**
     * Comment: 上下架删除商品接口
     * Author: wlf
     * Date: 2019/10/10 13:50
     */
    public function changeGoodsStatus()
    {
        global $_W , $_GPC;
        $storeid   = $_GPC['storeid']; //店铺id
        $goodsid   = $_GPC['goodsid']; //商户id
        $operation = $_GPC['operation'];  // 1上架 2下架 3放入回收站 4彻底删除
        $type      = $_GPC['type']; // 1=抢购  2=团购  3=拼团 5=优惠券 7=砍价商品
        if (empty($goodsid) || empty($operation) || empty($type)) {
            $this->renderError('缺少重要参数');
        }
        //判断权限
        switch ($type) {
            case 1:
                $aut = Store::checkAuthority('rush' , $storeid);
                if($aut){
                    $this->renderError('商户暂无此权限');
                }
                break;
            case 2:
                $aut = Store::checkAuthority('groupon' , $storeid);
                if($aut){
                    $this->renderError('商户暂无此权限');
                }
                break;
            case 3:
                $aut = Store::checkAuthority('wlfightgroup' , $storeid);
                if($aut){
                    $this->renderError('商户暂无此权限');
                }
                break;
            case 5:
                $aut = Store::checkAuthority('wlcoupon' , $storeid);
                if($aut){
                    $this->renderError('商户暂无此权限');
                }
                break;
            case 7:
                $aut = Store::checkAuthority('bargain' , $storeid);
                if($aut){
                    $this->renderError('商户暂无此权限');
                }
                break;
        }
        if ($operation == 1) {
            $audits = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$storeid),'audits');
            if (empty($audits)) {
                $status = 5;
            }else {
                $status = 1;
            }
            switch ($type) {
                case 1:
                    $res = pdo_update('wlmerchant_rush_activity' , ['status' => $status] , ['id' => $goodsid]);
                    break;
                case 2:
                    $res = pdo_update('wlmerchant_groupon_activity' , ['status' => $status] , ['id' => $goodsid]);
                    break;
                case 3:
                    $res = pdo_update('wlmerchant_fightgroup_goods' , ['status' => $status] , ['id' => $goodsid]);
                    break;
                case 5:
                    $res = pdo_update('wlmerchant_couponlist' , ['status' => $status] , ['id' => $goodsid]);
                    break;
                case 7:
                    $res = pdo_update('wlmerchant_bargain_activity' , ['status' => $status] , ['id' => $goodsid]);
                    break;
            }
            if ($res) {
                $this->renderSuccess('上架成功');
            }
            else {
                $this->renderError('上架失败，请刷新重试');
            }
        }
        else if ($operation == 2) {
            switch ($type) {
                case 1:
                    $res = pdo_update('wlmerchant_rush_activity' , ['status' => 4] , ['id' => $goodsid]);
                    break;
                case 2:
                    $res = pdo_update('wlmerchant_groupon_activity' , ['status' => 4] , ['id' => $goodsid]);
                    break;
                case 3:
                    $res = pdo_update('wlmerchant_fightgroup_goods' , ['status' => 4] , ['id' => $goodsid]);
                    break;
                case 5:
                    $res = pdo_update('wlmerchant_couponlist' , ['status' => 4] , ['id' => $goodsid]);
                    break;
                case 7:
                    $res = pdo_update('wlmerchant_bargain_activity' , ['status' => 4] , ['id' => $goodsid]);
                    break;
            }
            if ($res) {
                $this->renderSuccess('下架成功');
            }
            else {
                $this->renderError('下架失败，请刷新重试');
            }
        }
        else if ($operation == 3) {
            switch ($type) {
                case 1:
                    $res = pdo_update('wlmerchant_rush_activity' , ['status' => 8] , ['id' => $goodsid]);
                    break;
                case 2:
                    $res = pdo_update('wlmerchant_groupon_activity' , ['status' => 8] , ['id' => $goodsid]);
                    break;
                case 3:
                    $res = pdo_update('wlmerchant_fightgroup_goods' , ['status' => 8] , ['id' => $goodsid]);
                    break;
                case 5:
                    $res = pdo_update('wlmerchant_couponlist' , ['status' => 8] , ['id' => $goodsid]);
                    break;
                case 7:
                    $res = pdo_update('wlmerchant_bargain_activity' , ['status' => 8] , ['id' => $goodsid]);
                    break;
            }
            if ($res) {
                $this->renderSuccess('放入回收站成功');
            }
            else {
                $this->renderError('放入回收站失败，请刷新重试');
            }
        }
        else if ($operation == 4) {
            switch ($type) {
                case 1:
                    $res = pdo_delete('wlmerchant_rush_activity' , ['id' => $goodsid]);
                    break;
                case 2:
                    $res = pdo_delete('wlmerchant_groupon_activity' , ['id' => $goodsid]);
                    break;
                case 3:
                    $res = pdo_delete('wlmerchant_fightgroup_goods' , ['id' => $goodsid]);
                    break;
                case 5:
                    $res = pdo_delete('wlmerchant_couponlist' , ['id' => $goodsid]);
                    break;
                case 7:
                    $res = pdo_delete('wlmerchant_bargain_activity' , ['id' => $goodsid]);
                    break;
            }
            if ($res) {
                $this->renderSuccess('彻底删除成功');
            }
            else {
                $this->renderError('彻底删除失败，请刷新重试');
            }
        }
    }
    /**
     * Comment: 商户中心动态列表
     * Author: wlf
     * Date: 2019/10/11 17:05
     */
    public function dynamicList()
    {
        global $_W , $_GPC;
        #1、参数信息获取
        $sid       = $_GPC['storeid'];//店铺id
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageStart = $page * 10 - 10;
        #2、获取动态信息列表
        $sql  = "SELECT b.logo,b.storename,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i') as datetime,a.id,a.status,a.content,a.imgs FROM " . tablename(PDO_NAME . "store_dynamic") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.sid = b.id WHERE a.sid = {$sid} ORDER BY a.createtime DESC";
        $list = pdo_fetchall($sql . " LIMIT {$pageStart},10");
        #3、循环处理信息
        foreach ($list as $key => &$val) {
            $val['logo'] = tomedia($val['logo']);
            $val['imgs'] = unserialize($val['imgs']);
            if (is_array($val['imgs']) && count($val['imgs']) > 0) {
                foreach ($val['imgs'] as $imgK => &$imgV) {
                    $imgV = tomedia($imgV);
                }
            }
        }
        #3、总页数获取
        $total         = count(pdo_fetchall($sql));
        $data['total'] = ceil($total / 10);
        $data['list']  = $list;
        #4、查看发帖权限
        $groupid = pdo_getcolumn('wlmerchant_merchantdata' , ['id' => $sid] , 'groupid');
        if (empty($groupid)) {$authority = 0;}
        else {
            $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $groupid] , 'authority');
        }
        $authority = unserialize($authority);
        if (!empty($authority)) {
            if (!in_array('dynamic' , $authority)) {
                $data['hideadd'] = 1;
            }
        }
        if(empty($data['hideadd'])){
            $data['hideadd'] = 0;
        }
        $this->renderSuccess('商户中心动态列表' , $data);
    }
    /**
     * Comment: 商户登录状态改变
     * Author: zzw
     * Date: 2019/10/31 11:45
     */
    public function storeLoginStatusChange()
    {
        global $_W , $_GPC;
        #1、登录状态获取
        $status = $_GPC['status'] ? : -1;
        $onlyKey = $_GPC['only_key'] OR $this->renderError('网络错误，请重新扫码!');
        #1、获取当前的登录状态
        $loginInfo = json_decode(Cache::getCache('storeLoginInfo' , $onlyKey) , true);
        if ($loginInfo['end_time'] <= time()) $this->renderError('操作超时，请重新扫码!');
        #1、登录状态修改
        $data = [
            'status'   => intval($status) ,
            'end_time' => $loginInfo['end_time'] ,
            'mid'      => $_W['mid']
        ];
        Cache::setCache('storeLoginInfo' , $onlyKey , json_encode($data));
        $this->renderSuccess('操作成功' , ['status' => intval($status)]);
    }
    /**
     * Comment: 商户创建商品页面
     * Author: wlf
     * Date: 2019/12/03 11:59
     */
    public function createGoods()
    {
        global $_W , $_GPC;
        $type    = $_GPC['type'] ? $_GPC['type'] : 'rush';  //商品类型
        $id      = $_GPC['id'];      //商品id
        $storeid = $_GPC['storeid'];
        $data    = [];
        if(empty($storeid)){
            $this->renderError('无商户数据，请返回重试');
        }
        $store = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] ,array('groupid','uniacid','aid'));
        //获取权限
        $groupid = $store['groupid'];
        if (empty($groupid)) {
            $authority = 0;
        }
        else {
            $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $groupid] , 'authority');
        }
        if (!empty($authority)) {
            $authority = unserialize($authority);
        }
        //商品类型
        $data['type'] = [];
        if ((!empty($authority) && in_array('rush' , $authority)) || empty($authority)) {
            $data['type'][] = 'rush';
        }
        if (p('groupon') && ((!empty($authority) && in_array('groupon' , $authority)) || empty($authority))) {
            $data['type'][] = 'groupon';
        }
        if (p('wlfightgroup') && ((!empty($authority) && in_array('wlfightgroup' , $authority)) || empty($authority))) {
            $data['type'][] = 'fightgroup';
        }
        if (p('bargain') && ((!empty($authority) && in_array('bargain' , $authority)) || empty($authority))) {
            $data['type'][] = 'bargain';
        }
        //商品分类
        $cate = pdo_getall('wlmerchant_' . $type . '_category' , [
            'uniacid' => $store['uniacid'] ,
            'aid'     => $store['aid'] ,
            'is_show' => 0
        ] , ['name' , 'id']);
        $data['cate'] = $cate;
        //运费模板
        $express = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express_template')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND sid IN (0,{$storeid}) ORDER BY id DESC");
        $data['express'] = $express;
        //商品标签
        $tag_type           = ['rush' => 1 , 'groupon' => 3 , 'bargain' => 4 , 'fightgroup' => 2];
        $presettags         = pdo_getall('wlmerchant_tags' , [
            'uniacid' => $store['uniacid'] ,
            'aid'     => $store['aid'] ,
            'type'    => $tag_type[$type]
        ] , ['id' , 'title'] , '' , 'sort DESC');
        $data['presettags'] = $presettags;
        if ($id) {
            //基础信息
            if ($type == 'rush') {
                $goods = Rush::getSingleActive($id , "name,is_describe_tip,unit,cateid,starttime,endtime,thumb,thumbs,optionstatus,price,oldprice,retainage,appointment,tag,op_one_limit,num,usestatus,expressid,cutoffstatus,cutofftime,cutoffday,`describe`,detail,status,lp_status,lp_set");
                $goods['lp_set'] = unserialize($goods['lp_set']);
                unset($goods['a']);
            }
            else if ($type == 'groupon') {
                $goods = Groupon::getSingleActive($id , "name,is_describe_tip,unit,cateid,starttime,endtime,thumb,thumbs,optionstatus,price,oldprice,retainage,appointment,tag,op_one_limit,num,usestatus,expressid,cutoffstatus,cutofftime,cutoffday,`describe`,detail,status");
            }
            else if ($type == 'bargain') {
                $goods          = Bargain::getSingleActive($id , "name,is_describe_tip,unit,cateid,starttime,endtime,thumb,thumbs,price,oldprice,appointment,tag,rules,stock,usestatus,expressid,cutoffstatus,cutofftime,cutoffday,`describe`,detail,status,helplimit,dayhelpcount,joinlimit,onlytimes");
                $goods['rules'] = unserialize($goods['rules']);
                if (empty($goods['rules'])) {
                    $goods['rules'] = [];
                }
                $goods['num'] = $goods['stock'];
                unset($goods['stock']);
            }
            else if ($type == 'fightgroup') {
                $goods              = Wlfightgroup::getSingleGood($id , "name,is_describe_tip,unit,categoryid,limitstarttime,limitendtime,logo,adv,price,aloneprice,oldprice,appointment,tag,stock,op_one_limit,usestatus,expressid,cutoffstatus,cutofftime,cutoffday,`describe`,detail,status,grouptime,peoplenum,specstatus");
                $goods['starttime'] = $goods['limitstarttime'];
                unset($goods['limitstarttime']);
                $goods['endtime'] = $goods['limitendtime'];
                unset($goods['limitendtime']);
                $goods['thumb'] = $goods['logo'];
                unset($goods['logo']);
                $goods['thumbs'] = $goods['adv'];
                unset($goods['adv']);
                $goods['num'] = $goods['stock'];
                unset($goods['stock']);
                $goods['optionstatus'] = $goods['specstatus'];
                unset($goods['specstatus']);
                $goods['cateid'] = $goods['categoryid'];
                unset($goods['categoryid']);
            }
            $goods['tag'] = unserialize($goods['tag']);
            if (empty($goods['tag'])) {
                $goods['tag'] = [];
            }
            $goods['thumb']  = tomedia($goods['thumb']);
            $goods['thumbs'] = unserialize($goods['thumbs']);
            if(is_base64($goods['detail'])){
                $goods['detail'] = htmlspecialchars_decode(base64_decode($goods['detail']));
            }
            if(is_base64($goods['describe'])){
                $goods['describe'] = htmlspecialchars_decode(base64_decode($goods['describe']));
            }
            if (empty($goods['thumbs'])) {
                $goods['thumbs'] = [];
            }
            else {
                foreach ($goods['thumbs'] as &$th) {
                    $th = tomedia($th);
                }
            }
            //规格
            if ($goods['optionstatus'] == 1) {
                if ($type == 'rush') {
                    $spectype = 1;
                }
                else if ($type == 'fightgroup') {
                    $spectype = 2;
                }
                else if ($type == 'groupon') {
                    $spectype = 3;
                }
                //规格项
                $allspecs = pdo_fetchall("select id,title from " . tablename('wlmerchant_goods_spec') . " where goodsid = {$id} AND type = {$spectype}");
                foreach ($allspecs as &$s) {
                    $s['items'] = pdo_fetchall("SELECT id,title FROM " . tablename('wlmerchant_goods_spec_item') . "WHERE uniacid = {$_W['uniacid']} AND specid = {$s['id']}");
                }
                //价目表
                $options          = pdo_fetchall("select id,title,stock,price from " . tablename('wlmerchant_goods_option') . " where goodsid={$id} and type = {$spectype} ");
                $goods['spec']    = $allspecs;
                $goods['options'] = $options;
            }
            else {
                $goods['spec']    = [];
                $goods['options'] = [];
            }
            if ($goods['status'] == 1 || $goods['status'] == 2 || $goods['status'] == 3 || $goods['status'] == 5) {
                $goods['status'] = 1;
            }
            else {
                $goods['status'] = 0;
            }
            if (empty($goods['cutofftime'])) {
                $goods['cutofftime'] = time() + 86400 * 30;
            }
            $data['goods'] = $goods;
        }
        else {
            $data['goods'] = [
                'name'         => '' ,
                'unit'         => '' ,
                'cateid'       => '' ,
                'onelevel'     => '' ,
                'starttime'    => time() ,
                'endtime'      => time() + 86400 * 30 ,
                'thumb'        => '' ,
                'thumbs'       => [] ,
                'optionstatus' => 0 ,
                'price'        => '' ,
                'aloneprice'   => '' ,
                'oldprice'     => '' ,
                'retainage'    => '' ,
                'appointment'  => '' ,
                'tag'          => [] ,
                'op_one_limit' => '' ,
                'num'          => '' ,
                'usestatus'    => '' ,
                'expressid'    => '' ,
                'cutoffstatus' => 0 ,
                'cutofftime'   => time() + 86400 * 30 ,
                'cutoffday'    => 30 ,
                'describe'     => '' ,
                'detail'       => '' ,
                'status'       => 1 ,
                'spec'         => [] ,
                'options'      => [] ,
                'rules'        => [] ,
                'helplimit'    => '' ,
                'dayhelpcount' => '' ,
                'joinlimit'    => '' ,
                'onlytimes'    => '' ,
                'grouptime'    => '' ,
                'peoplenum'    => 2 ,
                'lp_set'       => [],
            ];
        }
        $this->renderSuccess('初始化商品创建' , $data);
    }
    /**
     * Comment: 多规格根据规格项生成规格详情数组
     * Author: wlf
     * Date: 2019/12/10 09:38
     */
    public function specToOption()
    {
        global $_W , $_GPC;
        $spec      = json_decode(base64_decode($_GPC['spec']) , true);
        $option    = json_decode(base64_decode($_GPC['options']) , true);
        $newoption = [];
        foreach ($spec as $sp) {
            $items[] = $sp['items'];
        }
        $length = count($items);
        for ($spec0 = 0 ; $spec0 < count($items[0]) ; $spec0++) {
            $title = $items[0][$spec0]['title'];
            if ($length > 1) {
                for ($spec1 = 0 ; $spec1 < count($items[1]) ; $spec1++) {
                    $lasttitle = $title;
                    $lasttitle .= '&' . $items[1][$spec1]['title'];
                    if ($length > 2) {
                        for ($spec2 = 0 ; $spec2 < count($items[2]) ; $spec2++) {
                            $lasttitle2 = $lasttitle;
                            $lasttitle2 .= '&' . $items[2][$spec2]['title'];
                            if ($length > 3) {
                                for ($spec3 = 0 ; $spec3 < count($items[3]) ; $spec3++) {
                                    $lasttitle3 = $lasttitle2;
                                    $lasttitle3 .= '&' . $items[3][$spec3]['title'];
                                    if ($length > 4) {
                                        for ($spec4 = 0 ; $spec4 < count($items[4]) ; $spec4++) {
                                            $lasttitle4 = $lasttitle3;
                                            $lasttitle4 .= '&' . $items[4][$spec4]['title'];
                                            if ($length > 5) {
                                                for ($spec5 = 0 ; $spec5 < count($items[5]) ; $spec5++) {
                                                    $lasttitle5 = $lasttitle4;
                                                    $lasttitle5 .= '&' . $items[5][$spec5]['title'];
                                                    $titles[]   = $lasttitle5;
                                                }
                                            }
                                            else {
                                                $titles[] = $lasttitle4;
                                            }
                                        }
                                    }
                                    else {
                                        $titles[] = $lasttitle3;
                                    }
                                }
                            }
                            else {
                                $titles[] = $lasttitle2;
                            }
                        }
                    }
                    else {
                        $titles[] = $lasttitle;
                    }
                }
            }
            else {
                $titles[] = $title;
            }
        }
        foreach ($titles as $tit) {
            $new = '';
            if (!empty($option)) {
                foreach ($option as $op) {
                    if (empty($new)) {
                        if ($tit == $op['title']) {
                            $new = $op;
                        }
                    }
                }
            }
            if ($new) {
                $newoption[] = $new;
            }
            else {
                $newoption[] = [
                    'id'    => 0 ,
                    'title' => $tit ,
                    'stock' => 0 ,
                    'price' => 0
                ];
            }
        }
        $this->renderSuccess('新的规格详情表' , $newoption);
    }
    /**
     * Comment: 商户保存商品接口
     * Author: wlf
     * Date: 2019/12/04 16:20
     */
    public function saveGoods()
    {
        global $_W , $_GPC;
        $type    = $_GPC['type'];
        $storeid = $_GPC['storeid'];
        if (empty($storeid)) {
            $this->renderError('无商户信息，请刷新重试');
        }
        else {
            $store = pdo_get('wlmerchant_merchantdata' , ['id' => $storeid] , [
                'audits' ,
                'storename' ,
                'uniacid' ,
                'aid'
            ]);
        }
        if(empty($store)){
            $this->renderError('商户信息错误，请刷新重试');
        }
        $id    = $_GPC['id'];  //商品id
        $goods = [
            'name'         => $_GPC['name'] ,   //商品名
            'sid'          => $storeid ,        //所属商户
            'aid'          => $store['aid'],
            'cateid'       => $_GPC['cateid'] ,
            'starttime'    => $_GPC['starttime'] / 1000 ,  //活动开始时间
            'endtime'      => $_GPC['endtime'] / 1000 , //活动结束时间
            'thumb'        => $_GPC['thumb'] ,      //缩略图
            'optionstatus' => $_GPC['optionstatus'] ,  //是否多规格
            'price'        => $_GPC['price'] ,         //价格
            'oldprice'     => $_GPC['oldprice'] ,      //原价
            'retainage'    => $_GPC['retainage'] ,     //尾款
            'appointment'  => $_GPC['appointment'] ,   //预约天数
            'op_one_limit' => $_GPC['op_one_limit'] ,   //单人限购
            'num'          => $_GPC['num'] ,            //库存
            'usestatus'    => $_GPC['usestatus'] ,      //使用方式
            'expressid'    => $_GPC['expressid'] ,      //快递模板
            'cutoffstatus' => $_GPC['cutoffstatus'] ,   //截止时间类型
            'cutofftime'   => $_GPC['cutofftime'] / 1000 ,     //固定时间
            'cutoffday'    => $_GPC['cutoffday'] ,      //有效期天数
            'describe'     => base64_encode(htmlspecialchars_decode($_GPC['describe'])) ,       //购买须知
            'detail'       => base64_encode(htmlspecialchars_decode($_GPC['detail'])),         //商品详情
            'is_describe_tip' => $_GPC['is_describe_tip']       //有效期天数
        ];
        //文本校验
        $textRes = Filter::init($goods['name'],$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError('商品名'.$textRes['message']);
        }
        //轮播图
        $thumbs = $_GPC['thumbs'];
        if (!empty($thumbs)) {
            $goods['thumbs'] = serialize(explode(',' , $thumbs));
        }
        //标签
        $tag = $_GPC['tag'];
        if (!empty($tag)) {
            $goods['tag'] = serialize(explode(',' , $tag));
        }
        else {
            $goods['tag'] = '';
        }
        $status = $_GPC['status'];
        if ($status == 1) {
            $audits = $store['audits'];
            if (empty($audits)) {
                $goods['status'] = 5;
            }
            else {
                if ($goods['starttime'] > time()) {
                    $goods['status'] = 1;
                }
                else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                    $goods['status'] = 2;
                }
                else if ($goods['endtime'] < time()) {
                    $goods['status'] = 3;
                }
            }
        }
        else {
            $goods['status'] = 0;
        }
        if (empty($id)) {
            $goods['uniacid']      = $store['uniacid'];
            $goods['aid']          = $store['aid'];
            $goods['isdistri']     = 1;
            $goods['independent']  = 1;
            $goods['allowapplyre'] = 0;//默认允许退款
        }
        //阶梯价信息处理
        if($type == 'rush'){
            //是否开启阶梯价(0=关闭 1=开启)
            $goods['lp_status'] = $_GPC['lp_status'] ? : 0;
            //阶梯价配置信息
            $lpSet = json_decode(html_entity_decode($_GPC['lp_set']),true);
            $goods['lp_set'] = is_array($lpSet) ? serialize($lpSet) : serialize([]);
        }else {
            //不是抢购商品  删除阶梯价信息
            unset($goods['lp_set'],$goods['lp_status']);
        }
        if ($type == 'rush') {
            $spectype = 1;
            $plugin   = '抢购';
            if (!empty($id)) {
                $res = pdo_update('wlmerchant_rush_activity' , $goods , ['id' => $id]);
            }
            else {
                $res = pdo_insert(PDO_NAME . 'rush_activity' , $goods);
                $id  = pdo_insertid();
            }
        }
        else if ($type == 'fightgroup') {
            $spectype            = 2;
            $plugin              = '拼团';
            $goods['merchantid'] = $goods['sid'];
            unset($goods['sid']);
            $goods['limitstarttime'] = $goods['starttime'];
            unset($goods['starttime']);
            $goods['limitendtime'] = $goods['endtime'];
            unset($goods['endtime']);
            $goods['logo'] = $goods['thumb'];
            unset($goods['thumb']);
            $goods['adv'] = $goods['thumbs'];
            unset($goods['thumbs']);
            $goods['stock'] = $goods['num'];
            unset($goods['num']);
            unset($goods['retainage']);
            $goods['specstatus'] = $goods['optionstatus'];
            unset($goods['optionstatus']);
            $goods['categoryid'] = $goods['cateid'];
            unset($goods['cateid']);
            $goods['grouptime']  = $_GPC['grouptime'];
            $goods['peoplenum']  = $_GPC['peoplenum'];
            $goods['aloneprice'] = $_GPC['aloneprice'];
            if ($goods['peoplenum'] < 2) {
                $this->renderError('组团人数最小为2');
            }
            if (!empty($id)) {
                $res = pdo_update('wlmerchant_fightgroup_goods' , $goods , ['id' => $id]);
            }
            else {
                $res = pdo_insert(PDO_NAME . 'fightgroup_goods' , $goods);
                $id  = pdo_insertid();
            }
        }
        else if ($type == 'groupon') {
            $spectype          = 3;
            $plugin            = '团购';
            if (!empty($id)) {
                $res = pdo_update('wlmerchant_groupon_activity' , $goods , ['id' => $id]);
            }
            else {
                $res = pdo_insert(PDO_NAME . 'groupon_activity' , $goods);
                $id  = pdo_insertid();
            }
        }
        else if ($type == 'bargain') {
            $rules = json_decode(base64_decode($_GPC['rules']),true);
            if(!empty($rules)){
                $rules = serialize($rules);
            }
            unset($goods['op_one_limit']);
            unset($goods['optionstatus']);
            unset($goods['retainage']);
            $goods['stock'] = $goods['num'];
            unset($goods['num']);
            $plugin                = '砍价';
            $goods['rules']        = $rules;
            $goods['helplimit']    = $_GPC['helplimit'];
            $goods['dayhelpcount'] = $_GPC['dayhelpcount'];
            $goods['joinlimit']    = $_GPC['joinlimit'];
            $goods['onlytimes']    = $_GPC['onlytimes'];
            if (!empty($id)) {
                $res = pdo_update('wlmerchant_bargain_activity' , $goods , ['id' => $id]);
            }
            else {
                $goods['submitmoneylimit'] = $goods['oldprice'];
                $res                       = pdo_insert(PDO_NAME . 'bargain_activity' , $goods);
            }
        }
        else {
            $this->renderError('类型错误，请刷新重试');
        }
        //处理规格
        if ($_GPC['optionstatus'] && $spectype && $id) {
            //获取规格
            $spec = json_decode(base64_decode($_GPC['spec']) , true);
            //储存
            foreach ($spec as &$sp) {
                //处理规格项
                if ($sp['id'] > 0) {
                    $res2 = pdo_update('wlmerchant_goods_spec' , ['title' => $sp['title']] , ['id' => $sp['id']]);
                }
                else {
                    $spdata = $sp;
                    unset($spdata['id']);
                    unset($spdata['items']);
                    $spdata['uniacid'] = $store['uniacid'];
                    $spdata['type']    = $spectype;
                    $spdata['goodsid'] = $id;
                    $res2              = pdo_insert(PDO_NAME . 'goods_spec' , $spdata);
                    $sp['id']          = pdo_insertid();
                }
                $spid[] = $sp['id'];
                //处理子规格项
                $spec_itemid = [];
                foreach ($sp['items'] as &$item) {
                    if ($item['id'] > 0) {
                        $res2 = pdo_update('wlmerchant_goods_spec_item' , [
                            'title'  => $item['title'] ,
                            'specid' => $sp['id']
                        ] , ['id' => $item['id']]);
                    }
                    else {
                        unset($item['id']);
                        $item['uniacid'] = $store['uniacid'];
                        $item['show']    = 1;
                        $item['specid']  = $sp['id'];
                        $res2            = pdo_insert(PDO_NAME . 'goods_spec_item' , $item);
                        $item['id']      = pdo_insertid();
                    }
                    $spec_itemid[] = $item['id'];
                }
                //删除要的的子规格
                if (0 < count($spec_itemid)) {
                    pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $sp['id'] . ' and id not in (' . implode(',' , $spec_itemid) . ')');
                }
                else {
                    pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $sp['id']);
                }
                pdo_update('wlmerchant_goods_spec' , ['content' => serialize($spec_itemid)] , ['id' => $sp['id']]);
            }
            //删除不要的规格项
            if (0 < count($spid)) {
                pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where  type = ' . $spectype . ' and goodsid=' . $id . ' and id not in (' . implode(',' , $spid) . ')');
            }
            else {
                pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where  type = ' . $spectype . ' and goodsid=' . $id);
            }
            //获取规格详情
            $options = json_decode(base64_decode($_GPC['options']) , true);
            foreach ($options as $option) {
                $newids = [];
                $idsarr = explode('&' , $option['title']);
                foreach ($idsarr as $k => $title) {
                    foreach ($spec[$k]['items'] as $it) {
                        while ($title == $it['title']) {
                            $newids[] = $it['id'];
                            break;
                        }
                    }
                }
                $newids = implode('_' , $newids);
                if ($option['id'] > 0) {
                    $res2 = pdo_update('wlmerchant_goods_option' , [
                        'title' => $option['title'] ,
                        'stock' => $option['stock'] ,
                        'price' => $option['price'] ,
                        'specs' => $newids
                    ] , ['id' => $option['id']]);
                }
                else {
                    unset($option['id']);
                    $option['uniacid'] = $store['uniacid'];
                    $option['specs']   = $newids;
                    $option['type']    = $spectype;
                    $option['goodsid'] = $id;
                    if ($spectype == 2) {
                        $option['vipprice'] = $option['price'] + $goods['aloneprice'] - $goods['price'];
                    }
                    $res2         = pdo_insert(PDO_NAME . 'goods_option' , $option);
                    $option['id'] = pdo_insertid();
                }
                $optionids[] = $option['id'];
            }
            if (0 < count($optionids)) {
                pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = ' . $spectype . ' AND goodsid=' . $id . ' and id not in ( ' . implode(',' , $optionids) . ')');
            }
            else {
                pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = ' . $spectype . ' AND goodsid=' . $id);
            }
        }
        //审核通知代理
        if ($goods['status'] == 5 && ($res || $res2)) {
            $first   = '您好，您有一个待审核任务需要处理';
            $type    = '审核商品';
            $content = $plugin . '商品:' . $goods['name'];
            $status  = '待审核';
            $remark  = "商户[" . $store['storename'] . "]上传了一个" . $plugin . '商品待审核,请管理员尽快前往后台审核';
            News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');
        }
        if ($res || $res2) {
            $this->renderSuccess('保存成功');
        }
        else {
            $this->renderError('保存失败或未修改，请刷新重试');
        }
    }

    /**
     * Comment: 店员调整粉丝余额
     * Author: wlf
     * Date: 2020/06/08 17:07
     */
    public function changeUserCredit(){
        global $_W , $_GPC;
        $mid = $_GPC['mid'];
        $sid = $_GPC['sid'];
        if(empty($sid)){
            $this->renderError('店铺信息错误，请刷新重试');
        }
        $type = $_GPC['type'];
        $price = $_GPC['price'];
        $remark = $_GPC['remark'];
        if(empty($type)){
            $price = sprintf("%.2f",0 - $price);
        }
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('uniacid','aid','nowmoney'));
        if($store['nowmoney'] < $price){
            $this->renderError('店铺现有金额不足,无法为用户充值');
        }
        $doid = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$_W['mid'],'storeid' => $sid),'id');
        if(empty($doid)){
            $this->renderError('店员信息错误，请刷新重试');
        }
        $res = Payback::payCore($sid,$mid,$doid,'store',$price,0,0,$store['uniacid'],$store['aid'],0,$remark);
        if($res){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请联系管理员');
        }


    }

    /**
     * Comment: 商户退款接口
     * Author: wlf
     * Date: 2020/08/03 14:03
     */
    public function storeRefund(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        if ($plugin == 'wlfightgroup') {
            $res = Wlfightgroup::refund($id);
        } else if ($plugin == 'coupon') {
            $res = wlCoupon::refund($id);
        } else if ($plugin == 'groupon') {
            $res = Groupon::refund($id);
        } else if ($plugin == 'bargain') {
            $res = Bargain::refund($id);
        } else if ($plugin == 'citydelivery'){
            $res = Citydelivery::refund($id);
        } else if ($plugin == 'rush'){
            $res = Rush::refund($id);
        } else if ($plugin == 'housekeep'){
            $res = Housekeep::refund($id);
        }
        if($res['status']){
            $afters = pdo_getall('wlmerchant_aftersale',array('orderid' => $id,'status'=>1,'plugin'=>$plugin),array('id','checkcodes'));
            if(!empty($afters)){
                $refundtype = '商家退款:根据支付方式原路退款';
                $journal = array(
                    'time' => time(),
                    'title' => '到账成功',
                    'detail' => '商家已退款:'.$refundtype,
                );
                foreach ($afters as $af){
                    $journals = Order::addjournal($journal,$af['id']);
                    $af['checkcodes'] = unserialize($af['checkcodes']);
                    if(empty($checkcode) || in_array($checkcode,$af['checkcodes'])) {
                        pdo_update('wlmerchant_aftersale', array('dotime' => time(), 'status' => 2, 'journal' => $journals), array('id' => $af['id']));
                    }
                }
            }
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('退款失败，请刷新重试');
        }
    }

    /**
     * Comment: 店员绑定申请页接口
     * Author: wlf
     * Date: 2021/07/09 16:51
     */
    public function storeAdminApply(){
        global $_W , $_GPC;
        $sid = $_GPC['sid'];
        if(empty($sid)){
            $this->renderError('参数错误，请重新扫码');
        }
        $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('adv','storename','address'));
        if(empty($storeinfo)){
            $this->renderError('商户错误或被删除，请重新扫码');
        }
        $storeinfo['adv'] = beautifyImgInfo($storeinfo['adv']);

        $this->renderSuccess('商户基础信息',$storeinfo);
    }

    /**
     * Comment: 店员绑定提交申请接口
     * Author: wlf
     * Date: 2021/07/09 17:41
     */
    public function adminApplyApi(){
        global $_W , $_GPC;
        $sid = $_GPC['sid'];
        $realname = trim($_GPC['realname']);
        $mobile = trim($_GPC['mobile']);
        if(empty($sid)){
            $this->renderError('商户参数错误');
        }

        if(empty($realname)){
            $this->renderError('请填写姓名');
        }
        if(empty($mobile)){
            $this->renderError('请填写手机号');
        }
        $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('uniacid','aid'));
        if(empty($storeinfo)){
            $this->renderError('商户错误或被删除，请重新扫码');
        }
        //判断是否重复
        $flag = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
            'uniacid' => $storeinfo['uniacid'] ,
            'mid'     => $_W['mid'] ,
            'storeid' => $sid
        ] , 'id');
        if ($flag) {
            $this->renderError('您已提交申请，请勿重复申请');
        }

        $data = [
            'uniacid' => $storeinfo['uniacid'],
            'aid'     => $storeinfo['aid'],
            'mid'     => $_W['mid'],
            'storeid' => $sid,
            'name'    => $realname,
            'mobile'  => $mobile,
            'createtime'  => time(),
            'ismain'  => 2,
        ];
        $res = pdo_insert(PDO_NAME . 'merchantuser', $data);
        if($res){
            $this->renderSuccess('提交成功');
        }else{
            $this->renderError('提交失败，请刷新重试');
        }
    }

    /**
     * Comment: 1520定制一级分类页面接口
     * Author: wlf
     * Date: 2021/12/22 16:03
     */
    public function oneCatePage(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        $cate = pdo_get('wlmerchant_category_store',array('id' => $id),array('name','advs'));
        $data = [];
        $data['title'] = $cate['name'];
        if(!empty($cate['advs'])){
            $advs = unserialize($cate['advs']);
            foreach ($advs as &$ad){
                $ad['thumb'] = tomedia($ad['thumb']);
            }
            $data['adv'] = $advs;
        }else{
            $data['adv'] = [];
        }
        $children = pdo_getall('wlmerchant_category_store',array('parentid' => $id,'enabled' => 1),array('id','name','thumb','state','abroad'));
        if(!empty($children)){
            foreach ($children as &$ch){
                $ch['thumb'] = tomedia($ch['thumb']);
            }
            $data['chilread'] = $children;
        }else{
            $data['chilread'] = [];
        }
        $this->renderSuccess('页面信息',$data);
    }

    /**
     * Comment: 临时闭店接口
     * Author: wlf
     * Date: 2021/12/31 10:51
     */
    public function temCloseApi(){
        global $_W , $_GPC;
        $sid = $_GPC['sid'];
        if(empty($sid)){
            $this->renderError('参数错误，请刷新扫码');
        }
        $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('id','temclose'));
        if($storeinfo['temclose'] > 0){
            pdo_update('wlmerchant_merchantdata',array('temclose' => 0),array('id' => $sid));
            $this->renderSuccess('营业成功');
        }else{
            pdo_update('wlmerchant_merchantdata',array('temclose' => 1),array('id' => $sid));
            $this->renderSuccess('休息成功');
        }
    }

    /**
     * Comment: 商户二维码获取接口
     * Author: wlf
     * Date: 2022/06/24 15:13
     */
    public function getStoreQr(){
        global $_W , $_GPC;
        $id = $_GPC['sid'];
        $merchantuserid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$id,'ismain'=>1),'mid');
        $data = [];
        if($_W['source'] == 3){
            $logo = pdo_getcolumn(PDO_NAME . "merchantdata", ['id' => $id], 'logo');
            $shopLink2 = "pages/mainPages/store/index?sid={$id}&head_id={$merchantuserid}";
            $data['shopLink'] = Store::getShopWxAppQrCode('', tomedia($logo),$shopLink2);
            if(Customized::init('customized336')){
                $BuyLink = "pages/mainPages/store/newBuyOrder/newBuyOrder?sid={$id}&head_id={$merchantuserid}";
                $data['buyLink'] = Store::getShopWxAppQrCode('', tomedia($logo), $BuyLink);
            }else{
                $BuyLink = "pages/subPages2/newBuyOrder/buyOrder?sid={$id}&head_id={$merchantuserid}";
                $data['buyLink'] = Store::getShopWxAppQrCode('', tomedia($logo), $BuyLink);
            }
            if(store_p('citydelivery',$id)){
                //同城配送二维码
                $link = "pages/subPages2/businessCenter/foodList/foodList?id={$id}&storeid={$id}&head_id={$merchantuserid}";
                $data['cityLink'] = Store::getShopWxAppQrCode('', tomedia($logo),$link);
            }
        }else{

            $data['followLink'] = Storeqr::get_storeqr($id);//获取关注二维码
            $shopLink = h5_url('pages/mainPages/store/index', ['sid' => $id,'head_id' => $merchantuserid]);
            $filename     = md5('store_url'. md5($shopLink));
            $qrCodeLink   = Poster::qrcodeimg($shopLink , $filename);
            $data['shopLink'] = tomedia($qrCodeLink);

            if(Customized::init('customized336')){
                $buyLink = h5_url('pages/mainPages/store/newBuyOrder/newBuyOrder', ['sid' => $id,'head_id' => $merchantuserid]);
            }else{
                $buyLink = h5_url('pages/subPages2/newBuyOrder/buyOrder', ['sid' => $id,'head_id' => $merchantuserid]);
            }

            $filename     = md5('store_url'. md5($buyLink));
            $qrCodeLink   = Poster::qrcodeimg($buyLink , $filename);
            $data['buyLink'] = tomedia($qrCodeLink);

            if(store_p('citydelivery',$id)) {
                //同城配送信息
                $distributionLink = h5_url('pages/subPages2/businessCenter/foodList/foodList', ['id' => $id, 'storeid' => $id, 'head_id' => $merchantuserid]);
                $filename     = md5('store_url'. md5($distributionLink));
                $qrCodeLink   = Poster::qrcodeimg($distributionLink , $filename);
                $data['cityLink'] = tomedia($qrCodeLink);
            }
        }

        $this->renderSuccess('二维码',$data);

    }

}