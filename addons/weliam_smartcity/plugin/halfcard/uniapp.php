<?php
defined('IN_IA') or exit('Access Denied');

class HalfcardModuleUniapp extends Uniapp {
    /**
     * Comment: 获取折扣卡信息列表
     * Author: zzw
     * Date: 2019/8/7 10:06
     */
    public function homeList() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('halfcard');
        $pluginset = unserialize($settings['plugin']);
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $type = $_GPC['type'] ?: 0;//0=全部折扣卡 1=特权折扣 2=平日折扣
        if($type == 2){
            $time = time();
        }else{
            $time = $_GPC['time'] ? : time();//特权折扣的时间
        }
        $is_total = $_GPC['is_total'] ?: 0;//0=不获取总页数；1=获取总页数
        $storeCateId = $_GPC['cate_id'] ?: 0;


        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ?: $set['wzsort'];//排序 1=创建时间；2=店铺距离；3=推荐设置；4=浏览人气
        #2、生成基本查询条件
        $where = " (a.aid = {$_W['aid']} or a.type > 0) AND a.uniacid = {$_W['uniacid']} AND a.status = 1 AND ((b.id > 0 AND b.enabled =1 ) or a.type > 0)  ";
        if ($storeCateId > 0) {
            $storeids = pdo_getall('wlmerchant_merchant_cate', ['onelevel' => $storeCateId], array('sid'), 'sid');
            $where .= " AND b.id in (" . implode(',', array_keys($storeids)) . ") ";
        }
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期
        if ($type == 1) {
            //查询特权折扣的条件生成
            if (empty($pluginset['przkstatus'])) {
                $where .= " AND ( CASE datestatus
                            WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                            WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        END OR a.daily = 1 OR a.type = 1 )";
            } else {
                $where .= " AND CASE a.datestatus
                            WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                            WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        END ";
            }
        } else if ($type == 2) {
            //查询平日折扣的条件生成
            $where .= " AND (CASE a.datestatus
                            WHEN 1 THEN a.`week` NOT LIKE '%\"{$week}\"%'
                            WHEN 2 THEN a.`day` NOT LIKE '%\"{$toDay}\"%'
                            ELSE 1 = 1
                        END AND a.daily = 1 OR a.type = 1)";
        }
        #4、生成排序条件
        switch ($sort) {
            case 1:
                $order = " ORDER BY a.createtime DESC ";
                break;//创建时间
            case 2:
                break;//店铺距离
            case 3:
                $order = " ORDER BY a.sort DESC ";
                break;//默认排序
            case 4:
                $order = " ORDER BY a.pv DESC ";
                break;//浏览人气
        }
        #5、获取商品列表
        if ($sort != 2) {
            //普通查询
            $info = pdo_fetchall("SELECT a.id as goods_id FROM "
                . tablename(PDO_NAME . "halfcardlist")
                . " as a LEFT JOIN "
                . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id WHERE {$where}{$order} "
                . " LIMIT {$page_start},{$page_index} ");
        } else {
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                . tablename(PDO_NAME . "halfcardlist")
                . " as a LEFT JOIN "
                . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            $info = array_slice($info, $page_start, $page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //平台特权的处理
            $val = WeliamWeChat::getHomeGoods(6, $val['goods_id'],0,0,$time);
            //$val['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/subPages2/newBuyOrder/buyOrder',['sid'=>$val['sid'],'type'=>2,'mid'=>$_W['mid']]));
            //当商品信息中带有sid时添加店铺链接
            if($val['discount'] > 9.99){
                unset($info[$key]);
            }else{
                $val['shop_url'] = h5_url('pages/mainPages/store/index', ['sid' => $val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
                $val['url'] = !empty($val['url']) ? $val['url'] : h5_url('pages/mainPages/memberCard/memberCard', ['id' => $val['id'], 'type' => 6]);
            }
        }
        #7、获取总页数
        if ($is_total == 1) {
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "halfcardlist") . " as a LEFT JOIN "
                . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id WHERE {$where}");
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('折扣卡信息列表', $data);
        }

        $this->renderSuccess('折扣卡信息列表', $info);
    }

    /**
     * Comment: 获取礼包信息列表
     * Author: zzw
     * Date: 2019/8/7 15:45
     */
    public function packageList() {
        global $_W, $_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $storeCateId = $_GPC['cate_id'] ?: 0;
        $is_total = $_GPC['is_total'] ?: 0;//0=不获取总页数；1=获取总页数

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $userCardInfo = WeliamWeChat::VipVerification($_W['mid']);
        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ?: $set['gpsort'];//排序 1=创建时间；2=店铺距离；3=推荐设置；4=浏览人气
        #2、生成基本查询条件
        $where = " a.aid IN (0,{$_W['aid']}) AND a.uniacid = {$_W['uniacid']} AND a.status = 1 AND a.listshow = 0";
        if ($storeCateId > 0) {
            $storeids = pdo_getall('wlmerchant_merchant_cate', ['onelevel' => $storeCateId], array('sid'), 'sid');
            $where .= " AND b.id in (" . implode(',', array_keys($storeids)) . ") ";
        }

        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";

        #4、生成排序条件
        switch ($sort) {
            case 1:
                $order = " ORDER BY a.createtime DESC ";
                break;//创建时间
            case 3:
                $order = " ORDER BY a.sort DESC ";
                break;//默认排序
            case 4:
                $order = " ORDER BY a.pv DESC ";
                break;//浏览人气
        }
        #5、获取商品列表
        if($sort != 2){
            $sql = "SELECT a.id as goods_id FROM "
                . tablename(PDO_NAME . "package")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id  WHERE {$where}{$order}" . " LIMIT {$page_start},{$page_index} ";
            $info = pdo_fetchall($sql);
        }else{
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                . tablename(PDO_NAME . "package")
                . " as a LEFT JOIN "
                . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            $info = array_slice($info, $page_start, $page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //平台礼包处理
            $val = WeliamWeChat::getHomeGoods(4, $val['goods_id']);
            //$val['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/subPages2/newBuyOrder/buyOrder',['pack_id'=>$val['id'],'type'=>3,'mid'=>$_W['mid']]));
            //当商品信息中带有sid时添加店铺链接
            $val['shop_url'] = h5_url('pages/mainPages/store/index', ['sid' => $val['sid']]);
            $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            $val['url'] = !empty($val['url']) ? $val['url'] : h5_url('pages/mainPages/memberCard/memberCard', ['id' => $val['goods_id'], 'type' => 4]);
            if($val['exttype']){
                $val['usetimes'] = 1;
                $val['surplus'] = 1;
                $val['exdetail'] = pdo_getcolumn(PDO_NAME.'package',array('id'=>$val['id']),'describe');
                $val['exdetail'] = htmlspecialchars_decode($val['exdetail']);
            }
            //判断等级
            $lvInfo = unserialize($val['level']);//会员限制列表
            if ($userCardInfo['id'] > 0) {
                //明确会员等级限制 只能是当前等级的会员可以使用
                if ($lvInfo && !in_array($userCardInfo['levelid'], $lvInfo)) {
                    $val['nolevel'] = 1;
                }
            }
        }
        #7、获取总页数
        if ($is_total == 1) {
            $total = pdo_fetchcolumn("SELECT count(*) FROM "
                . tablename(PDO_NAME . "package")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.merchantid = b.id  WHERE {$where}");
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('礼包信息列表', $data);
        }

        $this->renderSuccess('礼包信息列表', $info);
    }

    /**
     * Comment: 获取一卡通首页信息
     * Author: zzw
     * Date: 2019/8/19 10:36
     */
    public function memberCardHome() {
        global $_W, $_GPC;
        #1、基本信息获取
        $set = $_W['wlsetting']['halfcard'];
        if (empty($set['status'])) {
            $this->renderError('该功能已关闭');
        }
        $defaultImg = $_W['siteroot'] . 'addons/'.MODULE_NAME.'/web/resource/images/default.png';
        #2、获取用户会员卡信息
        $info = Halfcard::getUserMemberCardInfo();
        //添加用户会员卡二维码
        if ($_W['source'] == 3) {
            $info['qrcode'] = tomedia(WeApp::getQrCode('pages/subPages2/newBuyOrder/buyOrder?type=1&mid=' . $_W['mid'], 'halfcardqr' . $_W['mid'] . '.png'));
            $info['path'] = 'pages/subPages2/newBuyOrder/buyOrder?type=1&mid=' . $_W['mid'];
        } else {
            $info['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/subPages2/newBuyOrder/buyOrder', ['type' => 1, 'mid' => $_W['mid']]));
            $info['path'] = h5_url('pages/subPages2/newBuyOrder/buyOrder', ['type' => 1, 'mid' => $_W['mid']]);
        }
        #3、获取会员权益信息
        $list = pdo_getall(PDO_NAME . "nav"
            , ['uniacid' => $_W['uniacid'], 'enabled' => 1, 'type' => 2]
            , ['name', 'link', 'thumb', 'color'],'','displayorder DESC');
        foreach ($list as $key => &$val) {
            $val['link'] = $val['link'] ? $val['link'] : h5_url('pages/mainPages/memberCard/interests');
            $val['thumb'] = $val['thumb'] ? tomedia($val['thumb']) : $defaultImg;
            if($info['levelarmy'] > 0){
                $val['name'] = str_replace('会员','优待',$val['name']);
            }
        }
        #4、获取幻灯片列表
        $bannerList = pdo_getall(PDO_NAME . "adv"
            , ['uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'enabled' => 1, 'type' => 5]
            , ['thumb', 'link']);
        foreach ($bannerList as $bK => &$bV) {
            $bV['thumb'] = $bV['thumb'] ? tomedia($bV['thumb']) : $defaultImg;
        }
        #5、获取选项卡设置信息
        $optionSet = $set['plugin'] ? unserialize($set['plugin']) : [];
        $optionSet['zkname'] = $optionSet['zkname'] ?: '特权折扣';//特权折扣
        $optionSet['przkname'] = $optionSet['przkname'] ?: '平日折扣';//平日折扣
        $optionSet['lbname'] = $optionSet['lbname'] ?: '免费礼包';//免费礼包
        $optionSet['qgname'] = $optionSet['qgname'] ?: '尊享抢购';//尊享抢购
        $optionSet['tgname'] = $optionSet['tgname'] ?: '特惠团购';//特惠团购
        $optionSet['kqname'] = $optionSet['kqname'] ?: '专属卡券';//专属卡券
        $optionSet['jfname'] = $optionSet['jfname'] ?: '积分商品';//积分商品
        $optionSet['ptname'] = $optionSet['ptname'] ?: '拼团活动';//拼团活动
        $optionSet['kjname'] = $optionSet['kjname'] ?: '砍价活动';//砍价活动
        $optionSet['zkstatus'] = $optionSet['zkstatus'] ?: '0';//特权折扣
        $optionSet['przkstatus'] = $optionSet['przkstatus'] ?: '0';//平日折扣
        $optionSet['lbstatus'] = $optionSet['lbstatus'] ?: '0';//免费礼包
        $optionSet['qgstatus'] = $optionSet['qgstatus'] ?: '0';//尊享抢购
        $optionSet['tgstatus'] = $optionSet['tgstatus'] ?: '0';//特惠团购
        $optionSet['kqstatus'] = $optionSet['kqstatus'] ?: '0';//专属卡券
        $optionSet['jfstatus'] = $optionSet['jfstatus'] ?: '0';//积分商品
        $optionSet['ptstatus'] = $optionSet['ptstatus'] ?: '0';//拼团活动
        $optionSet['kjstatus'] = $optionSet['kjstatus'] ?: '0';//砍价活动
        #6、选项卡重定义
        foreach ($optionSet as $index => $item) {
            $key = substr($index, 0, 2);
            if ($key == 'pr') {
                $sub = substr($index, 4, strlen($index) - 2);
            } else {
                $sub = substr($index, 2, strlen($index) - 2);
            }
            $optionData[$key]['type'] = $key;
            $optionData[$key][$sub] = $item;
        }
        //校验
        foreach ($optionData as &$opp) {
            if(empty($opp['order'])){
                $opp['order'] = '0';
            }
            if(empty($opp['sort'])){
                $opp['sort'] = '1';
            }
        }
        $sortArr = array_column($optionData, 'order');
        array_multisort($sortArr, SORT_DESC, $optionData);
        $optionData = array_values($optionData);
        //判断选项卡是否全部关闭
        $maxStatus = max(array_column($optionData, 'status'));
        #8、信息拼装
        $data['info'] = $info;//用户开卡信息
        $data['list'] = $list ?: [];//会员权益信息列表
        $data['banner_list'] = $bannerList ?: [];//幻灯片列表
        $data['option_set'] = $optionData ?: [];//选项卡设置信息
        $data['switch_set'] = [
            'statistics'    => $set['statisticsdiv'] ?: 0,//首页统计框
            'halfcard'      => $set['halfcate'] ?: 0,//特权分类栏
            'package'       => $set['packagecate'] ?: 0,//礼包分类栏
            'noticestatus'  => $set['noticestatus'] ?: 0, //开卡弹幕
            'levelstatus'   => $set['levelstatus'] ?: 0, //会员等级是否显示
            'option_switch' => intval($maxStatus ? $maxStatus : 0), //判断信息卡是否开启
        ];//开关设置信息
        $data['cardTextColor'] = $set['cardTextColor'] ? $set['cardTextColor'] : '#000000' ;
        $defaultBgImg = URL_H5_RESOURCE . '/image/memberBacImg.png';
        $data['cardbgimg'] = !empty(trim($set['cardbgimg'])) ? tomedia($set['cardbgimg']) : $defaultBgImg ;

        if(p('payback')){
            $data['credit'] = sprintf("%.2f", $_W['wlmember']['credit2']);
            $data['payback'] = 1;
        }
        //功能扩展
        $data['moreset'] = 1;
        if(empty($info['isVip'])){
            $agentset = Setting::agentsetting_read('halfcard');
            $data['nodescribe'] = empty($agentset['nodescribe']) ? $set['nodescribe'] : $agentset['nodescribe'];
            $data['unshowtab'] = $set['unshowtab'];
        }

        $this->renderSuccess('获取一卡通首页信息', $data);
    }

    /**
     * Comment: 获取开卡页面信息
     * Author: zzw
     * Date: 2019/8/19 16:49
     */
    public function cardList() {
        global $_W, $_GPC;
        #1、基本信息配置
        $set = $_W['wlsetting']['halfcard'];
        if (empty($set['status'])) {
            $data['list'] = [];
            $this->renderSuccess('该功能已关闭', $data);
        }
        $data['realname'] = $_W['wlmember']['realname'];//用户真实姓名
        $data['mobile'] = $_W['wlmember']['mobile'];//用户头像
        $data['area_type'] = $set['halfcardtype'];//用户真实姓名
        $data['activation_code'] = $set['hideact'];//是否开启激活码开通功能 0=开启 1=关闭
        #2、判断用户是否已经开通会员卡
        $data['info'] = $info = Halfcard::getUserMemberCardInfo();
        unset($data['info']['levelid']);
        unset($data['info']['id']);
        unset($data['info']['name']);
        unset($data['info']['cardimg']);
        #3、生成会员卡查询条件
        $where = [
            'status'  => 1,
            'uniacid' => $_W['uniacid'],
        ];
        $card_id = WeliamWeChat::VipVerification($_W['mid'], true);
        if ($card_id > 0) {
            //获取可以用于续费的会员卡列表
            $where['renew !='] = 1;
            if ($set['renewstatus']) {
                //锁定续费类型
                $where['levelid'] = $info['levelid'];
            }
        } else {
            //获取可以用于第一次开通的会员卡列表
            $where['renew !='] = 2;
        };
        //判断 联盟模式/地区模式
        if ($data['area_type'] == 2) {
            $where['aid IN'] = [0, $_W['aid']];
        }
        #4、获取会员卡列表
        $data['list'] = pdo_getall(PDO_NAME . "halfcard_type", $where
            , ['id', 'name', 'is_hot', 'detail', 'price', 'days', 'old_price','levelid'], '', 'sort DESC');
        #5、获取设置信息
        $data['text'] = $_W['wlsetting']['halfcard']['text'];
        //自定义表单
        if($set['diyformid'] > 0){
            $diyFromInfo       = pdo_getcolumn(PDO_NAME . 'diyform' , ['id' => $set['diyformid']] , 'info');
            $data['diyform']   = json_decode(base64_decode($diyFromInfo) , true);//页面的配置信息
            $data['diyformid'] = $set['diyformid'];
        }
        #6、IOS支付功能 1关闭0开启
        $data['payclose'] = intval($_W['wlsetting']['base']['payclose']);

        if (file_exists(PATH_MODULE . 'N814.log')) {
            $data['bankflag'] = 1;
            $data['banknum'] = $_W['wlmember']['card_number'];
        }
        //074定制拥军卡
        $data['flag074'] = 0;
        if(Customized::init('integral074') > 0){
            $yjkres = pdo_getcolumn(PDO_NAME.'halflevel',array('uniacid'=>$_W['uniacid'],'status'=> 1,'army' => 1),'id');
            if($yjkres > 0 ){
                $data['flag074'] = 1;
            }
        }
        //858定制新人优惠
        $data['newreate'] = 0;
        if($set['newdiscount'] > 0){
            $newflag = pdo_getcolumn(PDO_NAME.'halfcard_record',array('mid'=>$_W['mid'],'status'=>1,'uniacid'=> $_W['uniacid']),'id');
            if(empty($newflag)){
                $data['newreate'] = $set['newreate'];
            }
        }


        $this->renderSuccess('开卡页面信息', $data);
    }

    /**
     * Comment: 开卡续费接口
     * Author: wlf
     * Date: 2019/8/20 14:30
     */
    public function halfcardOrder() {
        global $_W, $_GPC;
        if (empty($_W['aid'])) {
            $aidstatus = pdo_getcolumn(PDO_NAME . 'oparea', array('uniacid' => $_W['uniacid'], 'aid' => 0), 'status');
            if (empty($aidstatus)) {
                $this->renderError('地区参数错误，请返回地区选择页面选择代理地区');
            }
        }
        $typeid = $_GPC['typeid'];
        $username = trim($_GPC['username']) ?: $_W['wlmember']['nickname'];
        $mobile = $_GPC['mobile'];
        $banknum = trim($_GPC['banknum']) ? : 0;
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        $diyformid    = intval($_GPC['diyformid']) ? : 0; //自定义表单id

        if (empty($_W['wlmember']['mobile']) && in_array('halfcard', $mastmobile)) {
            $this->renderError('未绑定手机号');
        }

        $base = Setting::wlsetting_read('halfcard');
        if ($base['status']) {
            $halftype = pdo_get(PDO_NAME . 'halfcard_type', array('id' => $typeid));
            if (empty($halftype)) {
                $this->renderError('选择的充值类型错误，请重试');
            }
            if ($halftype['num']) {
                $times = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('wlmerchant_halfcard_record') . " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND status = 1 AND typeid = {$halftype['id']}");
                if ($times > $halftype['num'] || $times == $halftype['num']) {
                    $this->renderError('选择的充值卡最多充值' . $halftype['num'] . '次。');
                }
            }
            $cardinfo = Halfcard::getUserMemberCardInfo();
            if ($cardinfo['id']) {
                $mdata = array('uniacid' => $_W['uniacid'], 'mid' => $_W['mid'], 'id' => $cardinfo['id']);
                $vipInfo = Util::getSingelData('*', PDO_NAME . "halfcardmember", $mdata);
                $lastviptime = $vipInfo['expiretime'];
                if ($lastviptime && $lastviptime > time()) {
                    $limittime = $lastviptime + $halftype['days'] * 24 * 60 * 60;
                } else {
                    $limittime = time() + $halftype['days'] * 24 * 60 * 60;
                }
            } else {
                $limittime = time() + $halftype['days'] * 24 * 60 * 60;
            }
            //额外表单
            $diyFormInfo = [];
            if($diyformid > 0){
                //额外表单
                $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
                $diyFormInfo = serialize($diyFormInfo);
            }else{
                $diyFormInfo = '';
            }
            //价格
            $price = $halftype['price'];
            $newreate = 0;
            if($base['newdiscount'] > 0){
                $newflag = pdo_getcolumn(PDO_NAME.'halfcard_record',array('mid'=>$_W['mid'],'status'=>1,'uniacid'=> $_W['uniacid']),'id');
                if(empty($newflag)){
                    $price =  sprintf("%.2f",$price - $price*$base['newreate']/100) ;
                    $newreate = $base['newreate'];
                }
            }
            $data = array(
                'aid'           => $_W['aid'],
                'uniacid'       => $_W['uniacid'],
                'mid'           => $_W['mid'],
                'orderno'       => createUniontid(),
                'status'        => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'    => TIMESTAMP,
                'price'         => $price,
                'limittime'     => $limittime,
                'typeid'        => $halftype['id'],
                'howlong'       => $halftype['days'],
                'todistributor' => $halftype['todistributor'],
                'cardid'        => $cardinfo['id'],
                'username'      => $username,
                'mobile'        => $mobile,
                'platenumber'   => $banknum,
                'moinfo'        => $diyFormInfo,
                'platenumber'   => $newreate
            );
            //0元免费开通
            if ($data['price'] < 0.01) {
                $data['status'] = 1;
                $data['paytime'] = time();
                $data['issettlement'] = 1;
                //用户定制 激活码激活会员卡时赠送用户余额
                if (file_exists(IA_ROOT . '/addons/'.MODULE_NAME.'/pTLjC21GjCGj.log')) {
                    if ($halftype['give_price'] > 0) {
                        Member::credit_update_credit2($data['mid'], $halftype['give_price'], '一卡通赠送金额');
                    }
                }
                //电商联盟定制 会员卡同步到其他模块
                if (file_exists(PATH_MODULE . 'lsh.log')) {
                    Halfcard::toHccardMode($data['mid'],$username,$mobile,$halftype['levelid']);
                }
                $halfcarddata = array(
                    'uniacid'     => $_W['uniacid'],
                    'aid'         => $data['aid'],
                    'mid'         => $data['mid'],
                    'expiretime'  => $data['limittime'],
                    'username'    => $data['username'],
                    'levelid'     => $halftype['levelid'],
                    'createtime'  => time(),
                    //    'mototype'    => $data['mototype'],
                    //    'platenumber' => $data['platenumber']
                );
                if ($data['cardid']) {
                    pdo_update(PDO_NAME . 'halfcardmember', $halfcarddata, array('id' => $data['cardid']));
                } else {
                    pdo_insert(PDO_NAME . 'halfcardmember', $halfcarddata);
                }
                $member = pdo_get('wlmerchant_member', array('id' => $halfcarddata['mid']), array('openid', 'mobile'));
                $mobile = empty($member['mobile']) ? $data['mobile'] : $member['mobile'];
                if (empty($member['mobile']) || !empty($banknum)) {
                    $memberdata['mobile'] = $data['mobile'];
                    if(!empty($banknum)){
                        $memberdata['card_number'] = $banknum;
                    }
                    pdo_update('wlmerchant_member',$memberdata,array('id' => $halfcarddata['mid']));
                }
                $url = h5_url('pages/mainPages/memberCard/memberCard');
                $time = date('Y-m-d H:i:s', $halfcarddata['expiretime']);
                $settings = Setting::wlsetting_read('halfcard');
                /*** 模板信息通知 ***/
                $halftext = $_W['wlsetting']['trade']['halfcardtext'] ? $_W['wlsetting']['trade']['halfcardtext'] : '一卡通';
                $tqtext = $_W['wlsetting']['trade']['privilege'] ? $_W['wlsetting']['trade']['privilege'] : '特权';
                //通知用户开卡成功
                $userModelData = [
                    'first'   => '您已成功开通' . $halftext . $tqtext,
                    'type'    => '信息通知',//业务类型
                    'content' => '开通账号：' . $mobile,//业务内容
                    'status'  => '开通商品：' . $halftype['name'],//处理结果
                    'time'    => '到期时间：' . $time,//操作时间
                    'remark'  => '点击前往' . $halftext . '首页'
                ];
                TempModel::sendInit('service', $halfcarddata['mid'], $userModelData, $_W['source'], $url);
                //通知管理员
                $adminModelData = [
                    'first'   => '客户:[' . $halfcarddata['username'] . ']已成功开通' . $halftext . $tqtext,
                    'type'    => '信息通知',//业务类型
                    'content' => '开通账号：' . $mobile,//业务内容
                    'status'  => '开通商品：' . $halftype['name'],//处理结果
                    'time'    => '到期时间：' . $time,//操作时间
                    'remark'  => '点击前往' . $halftext . '首页'
                ];
                TempModel::sendInit('service', -1, $adminModelData, $_W['source']);
                /*** 模板信息通知 ***/
                $base = Setting::wlsetting_read('distribution');
                if ($base['appdis'] == 2 && $base['switch'] && $base['together'] == 1) {
                    $member = pdo_get('wlmerchant_member', array('id' => $data['mid']), array('mobile', 'nickname', 'realname', 'distributorid'));
                    $distributor = pdo_get('wlmerchant_distributor', array('id' => $member['distributorid']));
                    if ($distributor) {
                        if (empty($distributor['disflag'])) {
                            pdo_update('wlmerchant_distributor', array('disflag' => 1, 'updatetime' => time()), array('mid' => $data['mid']));
                        }
                    } else {
                        $data = array(
                            'uniacid'    => $_W['uniacid'],
                            'aid'        => $data['aid'],
                            'mid'        => $data['mid'],
                            'createtime' => time(),
                            'disflag'    => 1,
                            'nickname'   => $member['nickname'],
                            'mobile'     => $member['mobile'],
                            'realname'   => $member['realname'],
                            'leadid'     => 0
                        );
                        pdo_insert('wlmerchant_distributor', $data);
                        $disid = pdo_insertid();
                        pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $data['mid']));
                    }
                }
            }
            pdo_insert(PDO_NAME . 'halfcard_record', $data);
            $halfid = pdo_insertid();
            if ($price < 0.01) {
                $unidata['status'] = 0;
                $unidata['orderid'] = $halfid;
                $this->renderSuccess('开通成功', $unidata);
            } else {
                $unidata['status'] = 1;
                $unidata['orderid'] = $halfid;
                $this->renderSuccess('下单成功', $unidata);
            }
        } else {
            $this->renderError('功能已禁用');
        }
    }

    /**
     * Comment: 激活码激活一卡通
     * Author: wlf
     * Date: 2019/8/20 15:03
     */
    public function activationHalfcard() {
        global $_W, $_GPC;
        $cardpa = $_GPC['cardpa'];
        $username = trim($_GPC['username']);
        $mobile = trim($_GPC['mobile']);
        $banknum = trim($_GPC['banknum']);
        $base = Setting::wlsetting_read('halfcard');
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('halfcard', $mastmobile)) {
            $this->renderError('未绑定手机号');
        }
        if ($cardpa) {
            $type = Util::getSingelData("*", PDO_NAME . 'token', array('number' => $cardpa));
            if ($type['aid'] && $type['aid'] != $_W['aid'] && $base['halfcardtype'] == 2) {
                $this->renderError('该激活码不属于当前地区');
            }
            if (empty($type)) {
                $this->renderError('激活码不存在');
            }
            if ($type['status'] == 1) {
                $this->renderError('该激活码已使用');
            }

            $member = pdo_get('wlmerchant_member', array('id' => $_W['mid']), array('mobile'));
            if (empty($member['mobile']) || !empty($banknum)) {
                $memberdata['mobile'] = $mobile;
                if(!empty($banknum)){
                    $memberdata['card_number'] = $banknum;
                }
                pdo_update('wlmerchant_member',$memberdata,array('id' => $_W['mid']));
            }
            $dayNum = $type['days'];
            $cardinfo = Halfcard::getUserMemberCardInfo();
            if ($cardinfo['id']) {
                $mdata = array('uniacid' => $_W['uniacid'], 'mid' => $_W['mid'], 'id' => $cardinfo['id']);
                $halfInfo = Util::getSingelData('*', PDO_NAME . "halfcardmember", $mdata);
                $lastviptime = $halfInfo['expiretime'];
                $limittime = $lastviptime + $dayNum * 24 * 60 * 60;
            } else {
                $limittime = time() + $dayNum * 24 * 60 * 60;
            }
            $aid = Util::idSwitch('areaid', 'aid', $_W['areaid']);
            $halfcarddata = array(
                'uniacid'    => $_W['uniacid'],
                'aid'        => $_W['aid'],
                'mid'        => $_W['mid'],
                'expiretime' => $limittime,
                'username'   => $username,
                'levelid'    => $type['levelid'],
                'createtime' => time(),
                'channel'    => 1
            );
            //电商联盟定制
            if (!empty($type['caraid'])) {
                $settings = iunserializer(pdo_getcolumn('weliam_shiftcar_agentsetting', array('key' => 'set_base', 'uniacid' => $_W['uniacid'], 'aid' => $type['caraid']), 'value'));
                $invitid = pdo_getcolumn(PDO_NAME . "member", array('openid' => $settings['openid'], 'uniacid' => $_W['uniacid']), 'id');
                Distribution::addJunior($invitid, $_W['mid']);
            }
            //电商联盟定制 会员卡同步到其他模块
            if (file_exists(PATH_MODULE . 'lsh.log')) {
                Halfcard::toHccardMode($_W['mid'],$username,$mobile,$type['levelid']);
            }
            //成为分销商
            if (p('distribution')) {
                $base = Setting::wlsetting_read('distribution');
                if ($base['appdis'] == 2 && $base['switch'] && $base['together'] == 1) {
                    $member = pdo_get('wlmerchant_member', array('id' => $_W['mid']), array('mobile', 'nickname', 'realname', 'distributorid'));
                    $distributor = pdo_get('wlmerchant_distributor', array('id' => $member['distributorid']));
                    if ($distributor) {
                        if (empty($distributor['disflag'])) {
                            pdo_update('wlmerchant_distributor', array('disflag' => 1, 'updatetime' => time()), array('mid' => $_W['mid']));
                        }
                    } else {
                        $data = array(
                            'uniacid'    => $_W['uniacid'],
                            'aid'        => $_W['aid'],
                            'mid'        => $_W['mid'],
                            'createtime' => time(),
                            'disflag'    => 1,
                            'nickname'   => $member['nickname'],
                            'mobile'     => $member['mobile'],
                            'realname'   => $member['realname'],
                            'leadid'     => 0
                        );
                        pdo_insert('wlmerchant_distributor', $data);
                        $disid = pdo_insertid();
                        pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $_W['mid']));
                    }
                }
            }
            if ($cardinfo['id']) {
                if (pdo_update(PDO_NAME . 'halfcardmember', $halfcarddata, array('id' => $cardinfo['id']))) {
                    pdo_update(PDO_NAME . 'token', array('status' => 1, 'mid' => $_W['mid'], 'openid' => $_W['openid']), array('number' => $cardpa));
                    $this->renderSuccess('续费成功');
                } else {
                    $this->renderError('续费失败');
                }
            } else {
                if (pdo_insert(PDO_NAME . 'halfcardmember', $halfcarddata)) {
                    pdo_update(PDO_NAME . 'token', array('status' => 1, 'mid' => $_W['mid'], 'openid' => $_W['openid']), array('number' => $cardpa));
                    $this->renderSuccess('激活成功');
                } else {
                    $this->renderError('激活失败');
                }
            }
        } else {
            $this->renderError('请填入激活码');
        }
    }

    /**
     * Comment: 获取折扣卡使用信息
     * Author: zzw
     * Date: 2019/8/20 18:11
     */
    public function useDiscountCard() {
        global $_W, $_GPC;
        #1、获取参数
        $id = $_GPC['id'];
        #2、获取折扣卡信息
        $info = WeliamWeChat::getHomeGoods(6, $id);
        $describe = pdo_getcolumn(PDO_NAME . "halfcardlist", ['id' => $id], 'describe');
        $info['describe'] = htmlspecialchars_decode($describe);
        #3、判断当前用户是否可用该折扣卡
        $userCardInfo = WeliamWeChat::VipVerification($_W['mid']);
        $lvInfo = unserialize($info['level']);//会员限制列表
        if ($userCardInfo['id'] > 0) {
            //明确会员等级限制 只能是当前等级的会员可以使用
            if ($lvInfo && !in_array($userCardInfo['levelid'], $lvInfo)) {
                $this->renderError('对不起，您不符合使用要求');
            }
        } else {
            $data = Setting::wlsetting_read('trade');
            $halfcardtext = $data['halfcardtext'] ? $data['halfcardtext'] : '一卡通';
            $this->renderError('请先开通' . $halfcardtext);
        }
        if ($info['buy_limit']) {
            $begintime = strtotime(date('Y-m-d', time()));
            $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND createtime > {$begintime} AND type = 1");
            if ($todaytime >= $info['buy_limit']) {
                $this->renderError('该商户今日特权名额已用完');
            }
        }

        #3、二维码生成
        if ($_W['source'] == 3) {
            $showurl = 'pages/subPages2/newBuyOrder/buyOrder?sid=' . $info['sid'] . '&type=2&mid=' . $_W['mid'];
            $logo = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $info['sid']), 'logo'));
            $info['qrcode'] = tomedia(Store::getShopWxAppQrCode($info['sid'], $logo, $showurl));
        } else {
            $info['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/subPages2/newBuyOrder/buyOrder', ['sid' => $info['sid'], 'type' => 2, 'mid' => $_W['mid']]));
        }
        #4、删除多余的信息
        unset($info['type']);
        unset($info['id']);
        unset($info['logo']);
        unset($info['plugin']);
        unset($info['sid']);

        $this->renderSuccess('折扣卡使用信息', $info);
    }

    /**
     * Comment: 获取大礼包使用信息
     * Author: zzw
     * Date: 2019/8/21 9:21
     */
    public function useGiftPackage() {
        global $_W, $_GPC;
        #1、参数获取
        $id = $_GPC['id'];//礼包id
        #2、获取礼包基本信息
        $info = WeliamWeChat::getHomeGoods(4, $id);
        //判断今日是否可以使用
        $toWeek = date("w", time());//当前时间的星期
        if ($toWeek == 0) $toWeek = 7;//星期天时值的转换
        $toDay = date("j", time());//当前时间的日期
        if($info['usedatestatus'] == 1){
            $week = unserialize($info['week']);
            if(!in_array($toWeek,$week)){
                //$this->renderError('今日礼包未在发放中');
                $info['tipstatus'] = 1;
                $info['tips'] = '今日礼包未在发放中';
            }
        }else if($info['usedatestatus'] == 2){
            $day = unserialize($info['day']);
            if(!in_array($toDay,$day)){
                //$this->renderError('今日礼包未在发放中');
                $info['tipstatus'] = 1;
                $info['tips'] = '今日礼包未在发放中';
            }
        }

        $describe = pdo_getcolumn(PDO_NAME . "package", ['id' => $id], 'describe');
        $info['describe'] = htmlspecialchars_decode($describe);
        #3、判断当前用户是否可用该礼包
        $userCardInfo = WeliamWeChat::VipVerification($_W['mid']);
        $lvInfo = unserialize($info['level']);//会员限制列表
        if ($userCardInfo['id'] > 0) {
            //明确会员等级限制 只能是当前等级的会员可以使用
            if ($lvInfo && !in_array($userCardInfo['levelid'], $lvInfo)) {
                //$this->renderError('抱歉，您的会员等级不能领取');
                $info['tipstatus'] = 1;
                $info['tips'] = '您的会员等级不能领取';
            }
        } else {
            $data = Setting::wlsetting_read('trade');
            $halfcardtext = $data['halfcardtext'] ? $data['halfcardtext'] : '一卡通';
            //$this->renderError('请先开通' . $halfcardtext);
            $info['tipstatus'] = 1;
            $info['tips'] = '请先开通' . $halfcardtext;
        }
        if ($info['surplus'] <= 0) {
           // $this->renderError('领取失败，您已全部领取');
            $info['tipstatus'] = 1;
            $info['tips'] = '您已全部领完';
        }
        if ($info['timeslimit']) {
            $begintime = strtotime(date('Y-m-d', time()));
            $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND createtime > {$begintime} AND type = 2");
            if ($todaytime >= $info['timeslimit']) {
                //$this->renderError('该礼包今日已发完');
                $info['tipstatus'] = 1;
                $info['tips'] = '该礼包今日已发完';
            }
        }
        //单人每天次数限制
        if ($info['oplimit']) {
            $zerotime = strtotime(date("Y-m-d"), time());
            $times3 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND mid = {$_W['mid']}  AND usetime > {$zerotime} AND type = 2");
            $surplus = $info['oplimit'] - $times3;
            if ($surplus < 1) {
                //$this->renderError('您今天只能领取' . $info['oplimit'] . '次该礼包');
                $info['tipstatus'] = 1;
                $info['tips'] = '您今天只能领取' . $info['oplimit'] . '次该礼包';
            }
        }
        //单人每周次数限制
        if ($info['weeklimit']) {
            $begin = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
            $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND mid = {$_W['mid']}  AND usetime > {$begin} AND aid = {$_W['aid']} AND type = 2");
            $surplus = $info['weeklimit'] - $timeflag;
            if ($surplus < 1) {
                //$this->renderError('您每周只能领取' . $info['weeklimit'] . '次该礼包');
                $info['tipstatus'] = 1;
                $info['tips'] = '您每周只能领取' . $info['weeklimit'] . '次该礼包';
            }
        }
        //单人每月次数限制
        if ($info['monthlimit']) {
            $begin = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND mid = {$_W['mid']}  AND usetime > {$begin} AND aid = {$_W['aid']} AND type = 2");
            $surplus = $info['monthlimit'] - $timeflag;
            if ($surplus < 1) {
                //$this->renderError('您每月只能领取' . $info['monthlimit'] . '次该礼包');
                $info['tipstatus'] = 1;
                $info['tips'] = '您每月只能领取' . $info['monthlimit'] . '次该礼包';
            }
        }
        if ($info['stk'] <= 0 && $info['allnum'] > 0) {
            //$this->renderError('礼包已发放完毕');
            $info['tipstatus'] = 1;
            $info['tips'] = '礼包已全部发放';
        }
        //限制时间
        if ($info['timestatus']) {
            if ($userCardInfo['createtime'] < $info['starttime'] || $userCardInfo['createtime'] > $info['endtime']) {
                //$this->renderError('活动即将上线，敬请期待');
                $info['tipstatus'] = 1;
                $info['tips'] = '活动即将上线，敬请期待';
            }
        }
        if ($info['packtimestatus']) {
            if ($info['datestarttime'] > time()) {
                //$this->renderError('该礼包活动还未开始');
                $info['tipstatus'] = 1;
                $info['tips'] = '该礼包活动还未开始';
            }
            if ($info['dateendtime'] < time()) {
                $this->renderError('该礼包活动已结束');
                //$info['tipstatus'] = 1;
                $info['tips'] = '该礼包活动已结束';
            }
        }
        #4、二维码生成
        if(empty($info['tipstatus'])){
            if ($_W['source'] == 3) {
                $showurl = 'pages/subPages2/newBuyOrder/buyOrder?pack_id=' . $info['id'] . '&type=3&mid=' . $_W['mid'];
                $logo = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $info['sid']), 'logo'));
                $info['qrcode'] = tomedia(Store::getShopWxAppQrCode($info['sid'], $logo, $showurl));
            } else {
                $info['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/subPages2/newBuyOrder/buyOrder', ['pack_id' => $info['id'], 'type' => 3, 'mid' => $_W['mid'], 'sid' => $info['sid']]));
            }
        }
        #5、删除多余的信息
        unset($info['type']);
        unset($info['id']);
        unset($info['datestatus']);
        unset($info['price']);
        unset($info['logo']);
        unset($info['sid']);
        unset($info['plugin']);
        unset($info['stk']);

        $this->renderSuccess('大礼包使用信息', $info);
    }

    /**
     * Comment: 获取会员权益信息
     * Author: zzw
     * Date: 2019/9/11 16:19
     */
    public function getMembershipInterests() {
        global $_W, $_GPC;
        $set = $_W['wlsetting']['halfcard'];
        $agentset = Setting::agentsetting_read('halfcard');
        $info = empty($agentset['describe']) ? $set['describe'] : $agentset['describe'];

        $this->renderSuccess('会员权益', $info);
    }

    /**
     * Comment: 获取一卡通消费记录
     * Author: zzw
     * Date: 2019/9/17 11:58
     */
    public function usageRecord() {
        global $_W, $_GPC;
        #1、获取参数信息
        $page = $_GPC['page'] ?: 1;
        $pageIndex = $_GPC['page_index'] ?: 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        #1、条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} ";
        $table = tablename(PDO_NAME . "timecardrecord") . " as a LEFT JOIN " .
            tablename(PDO_NAME . "merchantdata") . " as b ON a.merchantid = b.id ";
        #1、获取总页数
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . $table . $where);
        $data['total'] = ceil($total / $pageIndex);
        #1、获取列表信息
        $field = " b.storename,b.logo,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.ordermoney as old_price,
        a.realmoney as pay_price,(a.ordermoney - a.realmoney) as save_price,a.id,a.type,a.activeid ";
        $data['list'] = pdo_fetchall("SELECT {$field} FROM " . $table . $where . " ORDER BY createtime DESC LIMIT {$pageStart},{$pageIndex} ");
        foreach ($data['list'] as $key => &$val) {
            $val['logo'] = tomedia($val['logo']);
            if ($val['type'] == 1) {
                $val['name'] = pdo_getcolumn(PDO_NAME . 'halfcardlist', array('id' => $val['activeid']), 'title');
                if (empty($val['name'])) {
                    $val['name'] = $val['storename'] . '在线买单';
                }
            } else {
                $val['name'] = pdo_getcolumn(PDO_NAME . 'package', array('id' => $val['activeid']), 'title');
            }
        }
        $this->renderSuccess('消费记录', $data);
    }

    /**
     * Comment: 获取某条消费记录的详细信息
     * Author: zzw
     * Date: 2019/10/11 9:50
     */
    public function getRecordDetails() {
        global $_W, $_GPC;
        #1、参数接收
        $id = intval($_GPC['id']) OR $this->renderError('缺少参数：id');
        #2、获取详细信息
        $info = pdo_fetch("SELECT a.id,b.logo,b.storename,a.ordermoney,a.type,a.activeid,a.realmoney,a.discount,hm.username,mu.name as vername,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.commentflag,a.merchantid,a.orderid FROM "
            . tablename(PDO_NAME . "timecardrecord")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
            . " as b ON a.merchantid = b.id LEFT JOIN " . tablename(PDO_NAME . "halfcardmember")
            . " as hm ON a.cardid = hm.id LEFT JOIN " . tablename(PDO_NAME . "merchantuser")
            . " as mu ON a.verfmid = mu.mid AND a.merchantid = mu.storeid " . "WHERE a.id = {$id} ");
        $info['logo'] = tomedia($info['logo']);
        if ($info['type'] == 1) {
            $info['name'] = pdo_getcolumn(PDO_NAME . 'halfcardlist', array('id' => $info['activeid']), 'title');
        } else {
            $info['name'] = pdo_getcolumn(PDO_NAME . 'package', array('id' => $info['activeid']), 'title');
        }
        if (empty($info['vername'])) {
            $info['vername'] = '无';
        }
        if(!$info['username']) $info['username'] = $_W['wlmember']['nickname'];
        if($info['orderid'] > 0 ){
            $orderinfo = pdo_get('wlmerchant_order',array('id' => $info['orderid']),array('cerditmoney','redpackmoney','fullreducemoney'));
            $info['cerditmoney'] = $orderinfo['cerditmoney'];
            $info['redpackmoney'] = $orderinfo['redpackmoney'];
            $info['fullreducemoney'] = $orderinfo['fullreducemoney'];
        }else{
            $info['cerditmoney'] = 0;
            $info['redpackmoney'] = 0;
            $info['fullreducemoney'] = 0;
        }

        $this->renderSuccess('消费记录详情', $info);
    }


    /**
     * Comment: 折扣卡信息（在线买单信息，用户自己进入在线买单页面请求该接口）
     * Author: zzw
     * Date: 2019/9/25 18:05
     */
    public function getPayOnlineInfo() {
        global $_W, $_GPC;
        #1、参数获取
        $sid = $_GPC['sid'] OR $this->renderError('错误的商户id信息');//商户id
        $settings = Setting::wlsetting_read('halfcard');
        $settings['limit'] = $settings['limit'] ? : '请询问服务员输入不参与优惠金额';
        $defaultCardImg = tomedia('/addons/'.MODULE_NAME.'/h5/resource/image/defaulthalfimg.png');
        $cardInfo = pdo_get(PDO_NAME . "halfcardlist", ['merchantid' => $sid, 'status' => 1], ['id','limit','describe']);
        $card_id = $cardInfo['id'];
        $store = pdo_get('wlmerchant_merchantdata' , ['id' => $sid] , ['storename','logo','enabled','aid' , 'payfullid','panorama_discount','payinrate','payintegral']);
        if($store['enabled'] != 1){
            $this->renderError('商户已关闭',['url'=>'pages/mainPages/index/index']);
        }
        #2、获取折扣卡信息
        if (!$card_id) {
            $info = [
                'discount'  => 10,
                'storename' => $store['storename']
            ];
            $tip = '商户无特权折扣活动';
        } else {
            $info = WeliamWeChat::getHomeGoods(6, $card_id);
        }
        #2、判断用户是否为会员
        $vipInfo = WeliamWeChat::VipVerification($_W['mid']);
        if ($vipInfo['id'] <= 0) {
            $info['discount'] = 10;
            $tip = '您不是会员';
            $info['levelname'] = '普通用户';
            $info['bgthumb'] = tomedia($settings['cardimg']) ?: $defaultCardImg;
        } else if ($card_id) {
            $card = pdo_get(PDO_NAME . 'halfcardmember', array('id' => $vipInfo['id']));
            $levelinfo = pdo_get(PDO_NAME.'halflevel',array('id'=>$card['levelid']),array('name','cardimg','army'));
            if(empty($levelinfo)){
                $levelname = Setting::wlsetting_read('halflevel');
                $info['levelname'] = $levelname['name'] ? : '普通会员' ;
                $info['bgthumb'] = !empty($levelname['cardimg']) ? tomedia($levelname['cardimg']) : tomedia($settings['cardimg']);
            }else{
                $info['levelname'] = $levelinfo['name'];
                $info['bgthumb'] = $levelinfo['cardimg'] ? tomedia($levelinfo['cardimg']) : tomedia($settings['cardimg']);
                $info['army'] = $levelinfo['army'];
            }
            $expiretime = $card['expiretime'];
            if($expiretime - time() < 7*86400){
                $overflag = 1;
            }
            $realcard = pdo_get('wlmerchant_halfcard_realcard', array('cardid' => $card['id']), array('icestatus'));
            if ($realcard['icestatus']) {
                $info['discount'] = 10;
                $tip = '此卡已被冻结,如有疑问请联系管理员';
            }
            if ($expiretime < time()) {
                $info['discount'] = 10;
                $tip = '此卡已过期，请续费或换卡重试';
            } else if ($card['disable']) {
                $info['discount'] = 10;
                $tip = '此卡已被禁用,如有疑问请联系管理员';
            } else {
                $info['level'] = unserialize($info['level']);
                if (is_array($info['level'])) {
                    if (in_array($card['levelid'], $info['level'])) {
                        $levelpass = 1;
                    } else {
                        $levelpass = 0;
                    }
                } else {
                    $levelpass = 1;
                }
                if (empty($levelpass)) {
                    $info['discount'] = 10;
                    $tip = '您的会员等级无法使用优惠';
                }
                if ($info['buy_limit'] > 0) {
                    $surflag = 1;
                    $begintime = strtotime(date('Y-m-d', time()));
                    $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND createtime > {$begintime} AND type = 1");
                    if ($todaytime >= $info['buy_limit']) {
                        $info['discount'] = 10;
                        $tip = '该商户今日特权名额已用完';
                    }else{
                        $surnum = $info['buy_limit'] - $todaytime;
                    }
                }else{
                    $surflag = 0;
                }
            }
            if(empty($info['discount'])){
                $info['discount'] = 10;
                $tip = '无可用折扣优惠';
            }
            if ($info['discount'] == 10 && empty($tip)) {
                $tip = '商户今日无特权折扣';
            }
        }
        $tip = $tip ? $tip : 0;
        if (!$info) {
            $this->renderError('折扣卡已被禁用');
        }else {
            //积分抵扣信息
            if($_W['wlsetting']['creditset']['dkstatus']>0){
                $payinrate = $store['payinrate'] / 100;
                $payintegral = $store['payintegral'] > 0 ? $store['payintegral'] : $_W['wlsetting']['creditset']['proportion'];
            }else{
                $payinrate = 0;
                $payintegral = 0;
            }
            if($payintegral < 0.01){
                $payinrate = 0;
                $payintegral = 0;
            }
            $data = [
                'discount' => $info['discount'] ,
                'name'     => $info['storename'] ,
                'storelogo'=> tomedia($store['logo']),
                'tip'      => $tip,
                'describe' => $cardInfo['describe'] ? : '',
                'limit'    => $cardInfo['limit'] ? : $settings['limit'],
                'levelname'=> $info['levelname'],
                'levelarmy'=> $info['army'] ?  : 0,
                'bgthumb'  => $info['bgthumb'],
                'surnum'   => $surnum ? : 0,
                'overflag' => $overflag ? : 0,
                'surflag'  => $surflag ? : 0,
                'payinrate' => $payinrate,
                'payintegral' => $payintegral,
                'memberintegral' => $_W['wlmember']['credit1']
            ];
            $data['usepayonline'] = 1;
            if($store['payfullid']>0){
                $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $store['payfullid'],'status' => 1),array('rules','title'));
                if(!empty($fullreduce)){
                    $data['fullreducelist']['title'] = $fullreduce['title'];
                    $data['fullreducelist']['list'] = unserialize($fullreduce['rules']);
                }
            }
            //336定制  商户买单折扣
            if(Customized::init('customized336') && $data['discount'] > $store['panorama_discount']){
                $data['discount'] = $store['panorama_discount'];
                $data['tip'] = '商户折扣减免';
                $data['newpageflag'] = 1;
            }
            //判断红包组件
            if(p('redpack')){
                $data['redpackflag'] = 1;
            }else{
                $data['redpackflag'] = 0;
            }
            $storeSet = Setting::wlsetting_read('agentsStoreSet');
            $data['bannerid'] = $storeSet['bannerid'] ? : '';
            $data['banposition'] = $storeSet['banposition'] ? : 1;

            $this->renderSuccess('折扣卡信息',$data);
        }
    }

    /**
     * Comment: 使用在线买单
     * Author: zzw
     * Date: 2019/9/26 11:44
     */
    public function usePayOnline() {
        global $_W, $_GPC;
        #1、参数获取
        $sid = intval($_GPC['sid']) OR $this->renderError('商户信息不存在，请刷新重试');//商户id
        $money = $_GPC['money'] OR $this->renderError('买单总金额不能为空');
        $noMoney = $_GPC['no_money'] ? : 0;//不参与折扣的金额
        $integralstatus = $_GPC['integralstatus'] ? : 0;//是否开启积分抵扣
        $redpackId = $_GPC['redpackid'] ? : 0;//使用红包id
        //校验参数
        if(!is_numeric($money)){
            $this->renderError('买单总金额必须为数字');
        }
        if($money < 0.01){
            $this->renderError('买单总金额必须大于0');
        }
        if(!is_numeric($noMoney)){
            $this->renderError('不参与优惠金额必须为数字');
        }
        if($noMoney < 0.01){
            $noMoney = 0;
        }
        #2、获取折扣卡信息  计算实际支付金额
        $card_id = pdo_getcolumn(PDO_NAME . "halfcardlist" , ['merchantid' => $sid , 'status' => 1] , 'id');
        $store   = pdo_get('wlmerchant_merchantdata' , ['id' => $sid] , ['storename' ,'enabled','aid' , 'payfullid','payolsetstatus', 'panorama_discount','payinrate','payintegral']);
        if($store['enabled'] != 1){
            $this->renderError('商户已关闭',['url'=>'pages/mainPages/index/index']);
        }
        #2、获取折扣卡信息
        if (!$card_id) {
            $info = [
                'discount' => 10,
                'sid'      => $sid,
                'card'     => WeliamWeChat::VipVerification($_W['mid'], true)
            ];
        } else {
            $info = WeliamWeChat::getHomeGoods(6, $card_id);
        }
        $aid = $store['aid'];
        //判断用户是否是会员
        if ($info['card'] < 1) {
            $info['discount'] = 10;
        }
        $card = pdo_get(PDO_NAME . 'halfcardmember', array('id' => $info['card']));
        $expiretime = $card['expiretime'];
        $realcard = pdo_get('wlmerchant_halfcard_realcard', array('cardid' => $card['id']), array('icestatus'));
        if ($realcard['icestatus']) {
            $info['discount'] = 10;
        }

        if ($expiretime < time()) {
            $info['discount'] = 10;
        } else if ($card['disable']) {
            $info['discount'] = 10;
        }
        if ($info['buy_limit']) {
            $begintime = strtotime(date('Y-m-d', time()));
            $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$info['id']} AND createtime > {$begintime} AND type = 1");
            if ($todaytime >= $info['buy_limit']) {
                $info['discount'] = 10;
            }
        }
        //336定制  商户买单折扣
        if(Customized::init('customized336') && $info['discount'] > $store['panorama_discount']){
            $info['discount'] = $store['panorama_discount'];
        }

        $discount = sprintf("%.2f", $money - $noMoney);//获取参与折扣的金额
        $payMoney = sprintf("%.2f", $discount * 0.1 * $info['discount']);//获取参与优惠金额 打折后的金额
        $payMoney = sprintf("%.2f", $payMoney + $noMoney);//折后金额 + 不参与优惠的金额 = 实际支付金额
        $setmoney = $payMoney;
        $discountMoney = sprintf("%.2f", $money - $payMoney);//订单总金额 - 实际支付金额 = 优惠的金额
        //积分抵扣
        if($integralstatus > 0){
            $store['payintegral'] = $store['payintegral'] > 0 ? $store['payintegral'] : $_W['wlsetting']['creditset']['proportion'];
            $onecreditmoney = sprintf("%.4f" , 1 / $store['payintegral']);
            $allcredit      = sprintf("%.2f" , $_W['wlmember']['credit1']);
            $dkmoney        = sprintf("%.4f" , $payMoney * $store['payinrate'] / 100);
            $dkcredit       = sprintf("%.2f" , $dkmoney / $onecreditmoney);
            if ($dkcredit > $allcredit) {
                $dkcredit = $allcredit;
                $dkmoney  = sprintf("%.2f" , $onecreditmoney * $dkcredit);
            }
            $remark = '在线买单积分抵扣消耗';
            if($dkcredit > 0){
                Member::credit_update_credit1($_W['mid'] , -$dkcredit , $remark);
            }
        }else{
            $dkcredit = 0;
            $dkmoney = 0;
        }
        //红包优惠
        if($redpackId > 0){
            $redpack = pdo_fetch("SELECT b.cut_money FROM".tablename(PDO_NAME . "redpack_records")
                ." as a LEFT JOIN " . tablename(PDO_NAME . "redpacks")
                ." as b ON a.packid = b.id WHERE a.id = {$redpackId}");
            $redpackmoney = $redpack['cut_money'];
        }else{
            $redpackmoney = 0;
        }
        //满减优惠
        if($store['payfullid']>0){
            $fulldkmoney = Fullreduce::getFullreduceMoney($setmoney,$store['payfullid']);
        }else{
            $fulldkmoney = 0;
        }
        $payMoney = sprintf("%.2f", $payMoney - $fulldkmoney - $dkmoney - $redpackmoney);
        #3、判断支付金额是否合格
        if ($payMoney < 0.01){
            $payMoney = 0;
        }
        #4、获取结算金额
        if ($info['discount'] < 10) {
            $vipbuyflag = 1;
            $remark = '不可优惠金额:' . $noMoney . '元，优惠折扣：' . $info['discount'] . '折';
        } else {
            $vipbuyflag = 0;
            $remark = '不可优惠金额:' . $noMoney . '元，无优惠折扣';
        }
        if($fulldkmoney > 0){
            $remark .= '满减优惠金额:' . $fulldkmoney . '元';
        }
        if($store['payolsetstatus'] == 1){
            $settlementMoney = Store::gethalfsettlementmoney($setmoney, $info['sid'], $vipbuyflag);
        }else if($store['payolsetstatus'] == 2){
            $settlementMoney = Store::gethalfsettlementmoney($money, $info['sid'], $vipbuyflag);
        } else{
            $settlementMoney = Store::gethalfsettlementmoney($payMoney, $info['sid'], $vipbuyflag);
        }
        #5、订单生成
        $data = [
            'uniacid'         => $_W['uniacid'],
            'mid'             => $_W['mid'],
            'sid'             => $info['sid'],
            'aid'             => $aid,
            'fkid'            => $card_id,
            'plugin'          => 'halfcard',
            'payfor'          => 'halfcardOrder',
            'orderno'         => createUniontid(),
            'status'          => 0,//订单状态：0未支付,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
            'createtime'      => TIMESTAMP,
            'oprice'          => $noMoney,
            'price'           => $payMoney,
            'num'             => 1,
            'vipbuyflag'      => $vipbuyflag,
            'specid'          => 0,
            'goodsprice'      => $money,
            'card_type'       => $vipbuyflag,
            'card_id'         => $info['card'],
            'card_fee'        => $discountMoney,
            'remark'          => $remark,
            'spec'            => $info['discount'],
            'settlementmoney' => $settlementMoney,
            'fullreduceid'    => $store['payfullid'],
            'fullreducemoney' => $fulldkmoney,
            'usecredit'       => $dkcredit,
            'cerditmoney'     => $dkmoney,
            'redpackid'       => $redpackId,
            'redpackmoney'    => $redpackmoney
        ];
        pdo_insert(PDO_NAME . 'order', $data);
        $orderid = pdo_insertid();
        if($redpackId > 0 && $orderid > 0){
            pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => 'payonline'),array('id' => $redpackId));
        }
        if($payMoney > 0){
            $this->renderSuccess('请支付',$orderid);
        }else{
            $order_out = $data;
            $newdata = [
                'status'   => 2 ,
                'paytime'  => time() ,
                'paytype'  => 6 ,
            ];
            $store = pdo_get(PDO_NAME . 'merchantdata', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['sid']),array('storename','paypaidid','onepayonlinescale','twopayonlinescale','payonlinedisstatus'));
            //分销
            if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && !empty($store['payonlinedisstatus']) && empty($nodis)) {
                $_W['aid'] = $order_out['aid'];
                $one = sprintf("%.2f",$order_out['price'] * $store['onepayonlinescale'] / 100);
                $two = sprintf("%.2f",$order_out['price'] * $store['twopayonlinescale'] / 100);
                $disorderid = Distribution::disCore($order_out['mid'], $order_out['price'], $one,$two, 0, $order_out['id'], 'payonline', 1);
                $newdata['disorderid'] = $disorderid;
            }
            //支付有礼
            if($store['paypaidid'] > 0){
                $newdata['paidprid'] = Paidpromotion::getpaidpr(7,$store['paypaidid'],$order_out['mid'],$order_out['id'],$newdata['paytype'],$order_out['price'],1,2);
            }
            $res = pdo_update(PDO_NAME . 'order', $newdata, array('id' => $orderid)); //更新订单状态
            //结算在线买单
            if ($res) {
                Store::ordersettlement($order_out['id']);
            }
            $record = array(
                'uniacid'     => $order_out['uniacid'],
                'aid'         => $order_out['aid'],
                'mid'         => $order_out['mid'],
                'type'        => 1,
                'cardid'      => $order_out['card_id'],
                'activeid'    => $order_out['fkid'],
                'merchantid'  => $order_out['sid'],
                'freeflag'    => $order_out['card_type'],
                'ordermoney'  => $order_out['goodsprice'],
                'realmoney'   => $order_out['price'],
                'verfmid'     => $order_out['mid'],
                'usetime'     => time(),
                'createtime'  => time(),
                'commentflag' => 1,
                'discount'    => $order_out['spec'],
                'undismoney'  => $order_out['oprice']
            );
            $flagtime = time() - 5;
            $flag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_timecardrecord') . "WHERE cardid = {$order_out['card_id']} AND activeid = {$order_out['fkid']} AND type = 1 AND createtime > {$flagtime} ");
            if (empty($flag)) {
                pdo_insert(PDO_NAME . 'timecardrecord', $record);
            }

            if (empty($disorderid)) {
                $disorderid = 0;
            }
            //收藏店铺
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'],$order_out['mid']);
            //发消息给买家
            $openid = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['mid']), 'openid');
            $nickname = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['mid']), 'nickname');
            $storename = $store['storename'];
            if ($order_out['fkid']) {
                $goodsname = pdo_getcolumn(PDO_NAME . 'halfcardlist', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['fkid']), 'title');
            } else {
                $goodsname = $storename . '在线买单';
            }
            $payinfo = array(
                'first'      => '您的在线支付订单已经成功付款' ,
                'order_no'   => $order_out['orderno'],//订单编号
                'time'       => date('Y-m-d H:i:s', time()),//支付时间
                'money'      => $order_out['price'],//支付金额
                'goods_name' => $goodsname,//商品名称
                'remark'     => '点击可查看订单详情，如有疑问请联系客服'
            );
            $url = h5_url('pages/subPages/orderList/orderList',['status'=>1]);
            TempModel::sendInit('pay',$order_out['mid'],$payinfo,$_W['source'],$url);
            //发送给商家
            $admins = pdo_fetchall("SELECT mid FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$order_out['uniacid']} AND storeid = {$order_out['sid']} AND ismain IN (1,3) ORDER BY id DESC");
            if ($admins) {
                foreach ($admins as $key => $ad) {
                    $userModelData = [
                        'first'   => '用户:[' . $nickname . ']在线买单付费成功',
                        'type'    => '在线买单' ,//业务类型
                        'content' => '买单商户：' .$storename ,//业务内容
                        'status'  => '已付款' . $order_out['price'] . '元',//处理结果
                        'time'    => date('Y-m-d H:i:s', time()),//操作时间
                        'remark'  => '点击查看订单'
                    ];
                    $url = h5_url('pages/subPages/merchant/merchantOrderList/merchantOrderList',array('aid'=>$order_out['aid'],'storeid'=>$order_out['sid']));
                    $url = str_replace('payment/','',$url);
                    TempModel::sendInit('service',$ad['mid'],$userModelData,$_W['source'],$url);
                }
            }
            //打印
            Order::sendPrinting($order_out['id'],'payonline');

            $unidata['status']  = 0;
            $unidata['orderid'] = $orderid;
            $unidata['tid']     = $data['orderno'];
            $unidata['plugin']  = 'payonline';
            $this->renderSuccess('支付成功' , $unidata);
        }
    }

    /**
     * Comment: 一卡通，折扣卡，礼包使用信息(仅管理员扫码进入请求当前接口)
     * Author: zzw
     * Date: 2019/9/25 16:58
     */
    public function useInfo() {
        global $_W, $_GPC;
        #1、参数获取
        //使用类型：1=vip二维码(全部)；2=折扣卡二维码(使用折扣卡)；3=大礼包二维码(使用大礼包)
        $type = in_array($_GPC['type'], [1, 2, 3]) ? $_GPC['type'] : $this->renderError('无效的使用类型');
        $mid = $_GPC['mid'] OR $this->renderError('无用户信息，请刷新重试');//消费用户id
        $sid = intval($_GPC['sid']) ? intval($_GPC['sid']) : '';//必须
        $pack_id = $_GPC['pack_id'] ?: '';//礼包必须
        //判断店员
        if ($pack_id) {
            $sid = pdo_getcolumn(PDO_NAME . 'package', array('id' => $pack_id), 'merchantid');
        }
        if (!empty($sid)) {
            $verifier = SingleMerchant::verifier($sid, $_W['mid']);
            if (!$verifier) $this->renderError('非管理员无法核销');
        }
        $vipInfo = WeliamWeChat::VipVerification($mid);
        #2、根据使用类型获取参数信息。1=全部；2=使用折扣卡；3=使用大礼包
        if ($type == 1) {
            //判断当前扫码人员是否存在多个商家  AND a.aid = {$_W['aid']}
            $where = " WHERE a.uniacid = {$_W['uniacid']}  AND a.enabled = 1 AND a.ismain IN (1,2,3) AND a.mid = {$_W['mid']}";
            $sql = "SELECT b.id,b.storename,b.logo,b.address FROM " . tablename(PDO_NAME . "merchantuser")
                . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.storeid = b.id " . $where . " GROUP BY a.storeid ";
            $shopList = pdo_fetchall($sql);
            //判断当前用户是否已经选中某个商家
            if (!$sid && count($shopList) > 1) {
                foreach ($shopList as $key => &$val) {
                    $val['logo'] = tomedia($val['logo']);
                }
                $this->renderSuccess('多商户，请选择其中一个', ['is_shop' => 1, 'shop_list' => $shopList]);
            } else if (!$sid && count($shopList) == 1) {
                $sid = $shopList[0]['id'];
            } else if (!$sid && count($shopList) <= 0) {
                $this->renderError('非管理员无法核销');
            }
            //获取当前商户的折扣卡信息
            $card_id = pdo_getcolumn(PDO_NAME . "halfcardlist", ['merchantid' => $sid, 'status' => 1], 'id');
            $cardInfo = WeliamWeChat::getHomeGoods(6, $card_id,0,$mid);
            //获取当前商户的所有礼包信息
            $packList = pdo_fetchall("SELECT id,`level` FROM " . tablename(PDO_NAME . "package") . " WHERE merchantid = {$sid} AND status = 1 ");
            foreach ($packList as $pk => &$pv) {
                //判断是否可以使用该礼包
                $plv = unserialize($pv['level']);
                if (!in_array($vipInfo['levelid'], $plv) && count($plv) > 0) {
                    //没有使用权限
                    unset($packList[$pk]);
                    continue;
                }
                $goods = WeliamWeChat::getHomeGoods(4, $pv['id'],0,$mid);
                if ($goods['surplus'] <= 0) {
                    //该礼包使用次数已经用完
                    unset($packList[$pk]);
                    continue;
                }
                if ($goods['timeslimit']) {
                    $begintime = strtotime(date('Y-m-d', time()));
                    $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$goods['id']} AND createtime > {$begintime} AND type = 2");
                    if ($todaytime >= $goods['timeslimit']) {
                        unset($packList[$pk]);
                        continue;
                    }
                }
                //单人每天次数限制
                if ($goods['oplimit']) {
                    $zerotime = strtotime(date("Y-m-d"), time());
                    $times3 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$goods['id']} AND mid = {$_W['mid']}  AND usetime > {$zerotime} AND aid = {$_W['aid']} AND type = 2");
                    $surplus = $goods['oplimit'] - $times3;
                    if ($surplus < 1) {
                        unset($packList[$pk]);
                        continue;
                    }
                }
                //单人每周次数限制
                if ($goods['weeklimit']) {
                    $begin = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                    $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$goods['id']} AND mid = {$_W['mid']}  AND usetime > {$begin} AND aid = {$_W['aid']} AND type = 2");
                    $surplus = $goods['weeklimit'] - $timeflag;
                    if ($surplus < 1) {
                        unset($packList[$pk]);
                        continue;
                    }
                }
                //单人每月次数限制
                if ($goods['monthlimit']) {
                    $begin = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$goods['id']} AND mid = {$_W['mid']}  AND usetime > {$begin} AND aid = {$_W['aid']} AND type = 2");
                    $surplus = $goods['monthlimit'] - $timeflag;
                    if ($surplus < 1) {
                        unset($packList[$pk]);
                        continue;
                    }
                }
                if ($goods['stk'] <= 0 && $goods['allnum'] > 0) {
                    unset($packList[$pk]);
                    continue;
                }
                //信息重构
                $pv = [];
                $pv['name'] = $goods['name'];
                $pv['id'] = $goods['id'];
                $pv['limit'] = $goods['newlimit'];
                $pv['surplus'] = $goods['surplus'];
            }
        } else if ($type == 2) {
            $card_id = pdo_getcolumn(PDO_NAME . "halfcardlist", ['merchantid' => $sid, 'status' => 1], 'id');
            $cardInfo = WeliamWeChat::getHomeGoods(6, $card_id,0,$mid);
            $payonline = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $sid), 'payonline');
            if (!$cardInfo['id']) {
                $this->renderError('商户无折扣卡且未开启在线买单');
            }
        } else if ($type == 3) {
            $pack = pdo_fetch("SELECT id,`level` FROM " . tablename(PDO_NAME . "package")
                . " WHERE status = 1 AND id = {$pack_id} ");
            $goods = WeliamWeChat::getHomeGoods(4, $pack['id'],0,$mid);
            if ($goods['surplus'] < 1) {
                $this->renderError('该礼包已使用完');
            }
            if ($goods['stk'] <= 0 && $goods['allnum'] > 0) {
                $this->renderError('该礼包已提供完毕');
            }
            //信息重构
            $pack = [];
            $pack['name'] = $goods['name'];
            $pack['id'] = $goods['id'];
            $pack['limit'] = $goods['newlimit'];
            $pack['surplus'] = $goods['surplus'];
            $packList[] = $pack;
        }
        #3、拼装信息
        $data['is_shop'] = 0;
        $data['card_id'] = $card_id ?: 0;
        $data['name'] = $vipInfo['username'];
        $data['type'] = $type;
        $data['discount'] = $cardInfo['discount'] ?: 10;
        $data['payonline'] = $cardInfo['payonline'] ? $cardInfo['payonline'] : 0;
        $data['usepayonline'] = $type == 2 ? 1 : 0;
        $data['pack'] = $packList ?: [];
        //判断是否有项目
        if (($data['payonline'] || empty($data['card_id'])) && empty($data['pack'])) {
            $this->renderError('用户无可核销项目');
        }

        $this->renderSuccess('会员特权使用信息获取', $data);
    }

    /**
     * Comment: 管理员扫码使用户进行消费的操作请求
     * Author: zzw
     * Date: 2019/9/26 16:41
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function usePrivileged() {
        global $_W, $_GPC;
        #1、参数接收,设置信息获取
        $mid = $_GPC['mid'] OR $this->renderError('无用户信息，请刷新重试');//消费用户id
        $packids = $_GPC['packids'] ? explode(',', $_GPC['packids']) : [];//礼包id数组
        $money = $_GPC['money'] ?: 0;//买单总金额
        $noMoney = $_GPC['no_money'] ?: 0;//不参与折扣的金额
        $cardId = $_GPC['card_id'] ?: 0;//折扣卡的id
        $base = Setting::wlsetting_read('halfcard');

        if (count($packids) <= 0 && $cardId <= 0) $this->renderError('请选择特权项目');
        #1、获取当前用户的会员卡信息
        $vipInfo = WeliamWeChat::VipVerification($mid);//用户开卡信息
        if ($vipInfo['id'] <= 0) $this->renderError('当前用户无会员信息');
        $cardInfo = WeliamWeChat::getHomeGoods(6, $cardId);//折扣卡信息
        #1、判断是否使用折扣卡
        WeliamWeChat::startTrans();//开启事务处理
        if ($money > 0) {
            //判断是否存在折扣卡id
            if ($cardId <= 0) {
                WeliamWeChat::rollback();//事务回滚
                $this->renderError('当前商户未开启折扣卡');
            }
            if ($cardInfo['buy_limit']) {
                $begintime = strtotime(date('Y-m-d', time()));
                $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$cardInfo['id']} AND createtime > {$begintime} AND type = 1");
                if ($todaytime >= $cardInfo['buy_limit']) {
                    WeliamWeChat::rollback();//事务回滚
                    $this->renderError('商户今日特权名额已用完');
                }
            }
            //折扣金额计算  获取时间支付金额
            $discount = sprintf("%.2f", $money - $noMoney);//获取参与折扣的金额
            $payMoney = sprintf("%.2f", $discount * 0.1 * $cardInfo['discount']);//获取参与优惠金额 打折后的金额
            $payMoney = sprintf("%.2f", $payMoney + $noMoney);//折后金额 + 不参与优惠的金额 = 实际支付金额
            //获取公众号id，代理id
            $idInfo = pdo_get(PDO_NAME . 'halfcardlist', ['id' => $cardId], ['uniacid', 'aid', 'title']);
            $cardData['mid'] = $mid;
            $cardData['verfmid'] = $_W['mid'];
            $cardData['uniacid'] = $idInfo['uniacid'];
            $cardData['aid'] = $idInfo['aid'];
            $cardData['type'] = 1;
            $cardData['activeid'] = $cardId;
            $cardData['ordermoney'] = $money;
            $cardData['usetime'] = time();
            $cardData['createtime'] = time();
            $cardData['realmoney'] = $payMoney;
            $cardData['discount'] = $cardInfo['discount'];
            $cardData['undismoney'] = $noMoney;
            $cardData['freeflag'] = $cardInfo['discount'] < 10 ? 1 : 0;
            $cardData['cardid'] = $vipInfo['id'];
            $cardData['merchantid'] = $cardInfo['sid'];
            $cardRes = pdo_insert(PDO_NAME . 'timecardrecord', $cardData);
            if ($cardRes) {
                $orderid = pdo_insertid();
                //记录核销信息
                SingleMerchant::verifRecordAdd($_W['aid'], $cardInfo['sid'], $mid, 'halfcard1', $orderid, $orderid, $idInfo['title'], 2);
                //给用户发送模板信息
                $userModelData = [
                    'first'   => '您好，您的买单特权已成功核销，只需现金支付' . $cardData['realmoney'] . '元',
                    'type'    => $idInfo['title'] . '买单特权',
                    'content' => '使用特权进行一次买单',
                    'status'  => '已记录',
                    'time'    => date('Y年m月d日 H:i:s', $cardData['usetime']),
                    'remark'  => ''
                ];
                TempModel::sendInit('service', $mid, $userModelData, $_W['source']);
                //给商户发送模板信息
                $storeModelData = [
                    'first'   => '您好,一个商户特权已核销',
                    'type'    => $idInfo['title'],
                    'content' => '使用特权进行一次买单',
                    'status'  => '已核销',
                    'time'    => date('Y年m月d日 H:i:s', $cardData['usetime']),
                    'remark'  => ''
                ];
                $smid = pdo_getcolumn(PDO_NAME . "merchantuser", ['storeid' => $cardInfo['sid'], 'ismain' => 1], 'mid');
                TempModel::sendInit('service', $smid, $storeModelData, $_W['source']);
                //赠送积分
                if ($base['carddeduct']>0) {
                    $jifen = floor($cardData['realmoney'] / $base['carddeduct']);
                    if ($jifen > 0) {
                        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', ['id' => $cardInfo['sid']], 'storename');
                        $remark = '会员折扣支付赠积分 ——' . $storename;
                        Member::credit_update_credit1($mid, $jifen, $remark);
                    }
                }
            }
        }
        #1、判断是否使用大礼包
        if (is_array($packids) && count($packids) > 0) {
            foreach ($packids as $key => $packactiveid) {
                //礼包详细信息
                $packInfo = WeliamWeChat::getHomeGoods(4, $packactiveid);
                //判断当前用户是否还能使用当前礼包
                if ($packInfo['timeslimit']) {
                    $begintime = strtotime(date('Y-m-d', time()));
                    $todaytime = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$packactiveid} AND createtime > {$begintime} AND type = 2");
                    if ($todaytime >= $packInfo['timeslimit']) {
                        WeliamWeChat::rollback();//事务回滚
                        $this->renderError('该礼包今日已发完');
                    }
                }
                //单人每天次数限制
                if ($packInfo['oplimit']) {
                    $zerotime = strtotime(date("Y-m-d"), time());
                    $times3 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$packactiveid} AND mid = {$mid}  AND usetime > {$zerotime} AND type = 2");
                    $surplus = $packInfo['oplimit'] - $times3;
                    if ($surplus < 1) {
                        WeliamWeChat::rollback();//事务回滚
                        $this->renderError('用户今天只能领取' . $packInfo['oplimit'] . '次该礼包');
                    }
                }
                //单人每周次数限制
                if ($packInfo['weeklimit']) {
                    $begin = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                    $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$packactiveid} AND mid = {$mid}  AND usetime > {$begin} AND type = 2");
                    $surplus = $packInfo['weeklimit'] - $timeflag;
                    if ($surplus < 1) {
                        WeliamWeChat::rollback();//事务回滚
                        $this->renderError('用户每周只能领取' . $packInfo['weeklimit'] . '次该礼包');
                    }
                }
                //单人每月次数限制
                if ($packInfo['monthlimit']) {
                    $begin = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    $timeflag = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_timecardrecord') . " WHERE activeid = {$packactiveid} AND mid = {$mid}  AND usetime > {$begin} AND type = 2");
                    $surplus = $packInfo['monthlimit'] - $timeflag;
                    if ($surplus < 1) {
                        WeliamWeChat::rollback();//事务回滚
                        $this->renderError('用户每月只能领取' . $packInfo['monthlimit'] . '次该礼包');
                    }
                }
                if ($packInfo['stk'] <= 0 && $packInfo['allnum'] > 0) {
                    WeliamWeChat::rollback();//事务回滚
                    $this->renderError('礼包已发放完毕');
                }
                //获取当前礼包的公众号id和代理商id
                $idInfo = pdo_get(PDO_NAME . 'package', ['id' => $packactiveid], ['uniacid', 'aid']);
                //生成数据信息
                $packData['mid'] = $mid;
                $packData['verfmid'] = $_W['mid'];
                $packData['uniacid'] = $idInfo['uniacid'];
                $packData['aid'] = $idInfo['aid'];
                $packData['type'] = 2;
                $packData['activeid'] = $packactiveid;
                $packData['ordermoney'] = sprintf("%.2f", $packInfo['price'] / $packInfo['usetimes']);
                $packData['usetime'] = time();
                $packData['createtime'] = time();
                $packData['freeflag'] = 0;
                $packData['cardid'] = $vipInfo['id'];
                $packData['merchantid'] = $packInfo['sid'];
                $packRes = pdo_insert(PDO_NAME . 'timecardrecord', $packData);
                if ($packRes) {
                    $orderid = pdo_insertid();
                    //为商户增加余额
                    if ($packInfo['storemoney'] > 0) {
                        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney+{$packInfo['storemoney']},nowmoney=nowmoney+{$packInfo['storemoney']} WHERE id = {$packInfo['sid']}");
                        $merchantnowmoney = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $packInfo['sid']), 'nowmoney');
                        Store::addcurrent(1, 14, $packInfo['sid'], $packInfo['storemoney'], $merchantnowmoney, $orderid);
                    }
                    //记录核销信息
                    SingleMerchant::verifRecordAdd($_W['aid'], $packInfo['sid'], $mid, 'halfcard2', $orderid, $orderid, $packInfo['name'], 2);
                    //发送模板信息给用户
                    $userModelData = [
                        'first'   => '您好，您的特权大礼包已成功核销',
                        'type'    => $packInfo['name'] . '礼包使用',
                        'content' => '使用一次礼包',
                        'status'  => '已记录',
                        'time'    => date('Y年m月d日 H:i:s', $packData['usetime']),
                        'remark'  => ''
                    ];
                    TempModel::sendInit('service', $mid, $userModelData, $_W['source']);
                    //给商户发送模板信息
                    $storeModelData = [
                        'first'   => '您好,一个商家大礼包已核销',
                        'type'    => $packInfo['name'],
                        'content' => '成功发放一个礼包',
                        'status'  => '已核销',
                        'time'    => date('Y年m月d日 H:i:s', $packData['usetime']),
                        'remark'  => ''
                    ];
                    $smid = pdo_getcolumn(PDO_NAME . "merchantuser", ['storeid' => $cardInfo['sid'], 'ismain' => 1], 'mid');
                    TempModel::sendInit('service', $smid, $storeModelData, $_W['source']);
                    //赠送积分
                    if ($base['packdeduct']>0) {
                        $jifen = floor($packData['ordermoney'] / $base['packdeduct']);
                        if ($jifen) {
                            $remark = '会员使用礼包赠积分 ——' . $packInfo['storename'];
                            Member::credit_update_credit1($mid, $jifen, $remark);
                        }
                    }
                }
                //判断当前礼包是否已被领取完毕
                if (($packInfo['stk'] - 1) <= 0 && $packInfo['allnum'] > 0) pdo_update(PDO_NAME . 'package', ['status' => 0], ['id' => $packactiveid]);
            }
        }
        if ($cardRes || $packRes) {
            $sid = $cardInfo['sid'] ? $cardInfo['sid'] : $packInfo['sid'];
            Store::addFans($sid, $mid);
            WeliamWeChat::commit();//提交事务
            $this->renderSuccess('消费成功');
        } else {
            $this->renderError('消费失败，请刷新重试');
        }
    }


    /**
     * Comment: 实体卡信息获取
     * Author: zzw
     * Date: 2019/10/16 9:56
     */
    public function getRealCardInfo() {
        global $_W, $_GPC;
        #1、参数获取
        $cardsn = $_GPC['cardsn'] OR $this->renderError('卡号不存在');
        $salt = $_GPC['salt'] OR $this->renderError('加密盐错误');
        $settings = Setting::wlsetting_read('halfcard');
        #2、获取信息
        $cardInfo = pdo_get(PDO_NAME . 'halfcard_realcard', ['uniacid' => $_W['uniacid'], 'cardsn' => $cardsn, 'salt' => $salt]);
        if (!$cardInfo) $this->renderError('二维码无效，请重新获取二维码！', ['url' => h5_url('pages/mainPages/index/index')]);
        //Halfcard::checkFollow($cardInfo['id']);//判断用户是否关注 暂时弃用
        if ($cardInfo['icestatus']) $this->renderError('此卡已被冻结,无法使用', ['url' => h5_url('pages/mainPages/index/index')]);
        #3、根据状态进行不同的操作
        switch ($cardInfo['status']) {
            case 1:
                if ($settings['morecard']) {
                    $halfcard = Member::checkhalfmember();
                    if ($halfcard) $this->renderError('您已拥有正在生效的特权卡', ['url' => h5_url('pages/mainPages/index/index')]);
                }
                if($cardInfo['levelid'] > 0){
                    $level = pdo_getcolumn(PDO_NAME."halflevel",['id'=>$cardInfo['levelid']],'name');
                }else{
                    $level = Setting::wlsetting_read('halflevel')['name'] ? : '普通会员' ;
                }
                $limittime = time() + $cardInfo['days'] * 3600 * 24;
                $limittime = date("Y-m-d", $limittime);
                $data = [
                    'limittime'  => $limittime,
                    'cardsn'     => $cardInfo['cardsn'],
                    'remark'     => $cardInfo['remark'],
                    'level'      => $level
                ];
                $this->renderSuccess('实卡信息', $data);
                break;//挪车卡未绑定
            case 2:
                $cardmid = pdo_getcolumn(PDO_NAME . 'halfcardmember', ['id' => $cardInfo['cardid']], 'mid');
                //当前卡已被绑定 判断用户id与开卡人id是否一致
                if ($cardmid == $_W['mid']) $this->renderError('此卡已被绑定', ['url' => h5_url('pages/mainPages/memberCard/memberCard')]);
                //当前卡已被绑定 判断扫码人员是否为店员
                $verifier = pdo_getcolumn(PDO_NAME . 'merchantuser', ['mid' => $_W['mid']], 'storeid');
                if ($verifier) $this->renderSuccess('', ['url' => h5_url('pages/subPages2/newBuyOrder/buyOrder', ['type' => 1, 'mid' => $cardmid])]);
                else $this->renderError('此卡已失效', ['url' => h5_url('pages/mainPages/memberCard/getMembership/getMembership', ['card' => 'have'])]);
                break;//挪车卡已绑定
            case 3:
                $this->renderError('抱歉，此卡已失效！');
                break;//挪车卡已禁止
        }
    }

    /**
     * Comment: 实体卡绑定
     * Author: zzw
     * Date: 2019/10/16 10:18
     */
    public function realCardBinding() {
        global $_W, $_GPC;
        #1、参数获取
        $cardsn = $_GPC['cardsn'] OR $this->renderError('卡号不存在');
        $salt = $_GPC['salt'] OR $this->renderError('加密盐错误');
        $mobile = $_GPC['mobile'] OR $this->renderError('请输入正确的手机号');
        $name = $_GPC['name'] OR $this->renderError('请输入正确的持卡人名称');
        #2、获取实体卡信息
        $card = pdo_get(PDO_NAME . 'halfcard_realcard', ['uniacid' => $_W['uniacid'], 'cardsn' => $cardsn, 'salt' => $salt]);
        if (!$card) $this->renderError('不存在的实体卡');
        if ($card['icestatus']) $this->renderError('此卡已被冻结,无法绑定');
        #3、根据实体卡的状态进行操作
        if ($card['status'] == 1) {
            //判断用户是否已经绑定实体卡
            $cardId = pdo_getcolumn(PDO_NAME . 'halfcardmember', ['uniacid' => $_W['uniacid'], 'mid' => $_W['mid']], 'id');
            if ($cardId > 0) {
                $cardsn = pdo_getcolumn(PDO_NAME . 'halfcard_realcard', ['cardid' => $cardId], 'cardsn');
                if ($cardsn) $this->renderError('您已绑定实体卡，不可再次绑定');
            }
            //用户未绑定实体卡，开始绑定实体卡
            pdo_delete(PDO_NAME.'halfcardmember',array('mid'=>$_W['mid'],'uniacid' => $_W['uniacid']));
            $data = [
                'uniacid'    => $_W['uniacid'],
                'aid'        => $card['aid'],
                'mid'        => $_W['mid'],
                'expiretime' => time() + $card['days'] * 3600 * 24,
                'createtime' => time(),
                'levelid'    => $card['levelid'],
                'disable'    => 0,
                'username'   => $name,
                'channel'    => 2
            ];
            pdo_insert(PDO_NAME . 'halfcardmember', $data);
            $cardid = pdo_insertid();
            if ($cardid > 0) {
                pdo_update(PDO_NAME . 'member', ['mobile' => $mobile], ['id' => $_W['mid']]);
                pdo_update(PDO_NAME . 'halfcard_realcard', ['cardid' => $cardid, 'status' => 2, 'bindtime' => time()], ['id' => $card['id']]);
                //电商联盟定制 会员卡同步到其他模块
                if (file_exists(PATH_MODULE . 'lsh.log')) {
                    Halfcard::toHccardMode($_W['mid'],$name,$mobile,$card['levelid']);
                }

                //成为分销商
                if (p('distribution')) {
                    $base = Setting::wlsetting_read('distribution');
                    if ($base['appdis'] == 2 && $base['switch'] && $base['together'] == 1) {
                        $member = pdo_get('wlmerchant_member', array('id' => $_W['mid']), array('mobile', 'nickname', 'realname', 'distributorid'));
                        $distributor = pdo_get('wlmerchant_distributor', array('id' => $member['distributorid']));
                        if ($distributor) {
                            if (empty($distributor['disflag'])) {
                                pdo_update('wlmerchant_distributor', array('disflag' => 1, 'updatetime' => time()), array('mid' => $_W['mid']));
                            }
                        } else {
                            $data = array(
                                'uniacid'    => $_W['uniacid'],
                                'aid'        => $_W['aid'],
                                'mid'        => $_W['mid'],
                                'createtime' => time(),
                                'disflag'    => 1,
                                'nickname'   => $member['nickname'],
                                'mobile'     => $member['mobile'],
                                'realname'   => $member['realname'],
                                'leadid'     => 0
                            );
                            pdo_insert('wlmerchant_distributor', $data);
                            $disid = pdo_insertid();
                            pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $_W['mid']));
                        }
                    }
                }

                $this->renderSuccess('绑定成功', ['id' => $cardid]);
            } else {
                $this->renderError('绑定失败，请重试或及时联系管理');
            }
        } else if ($card['status'] == 2) {
            $this->renderError('此卡已绑定，无法再次绑定');
        } else if ($card['status'] == 3) {
            $this->renderError('此卡已失效，无法绑定');
        }
    }

    /**
     * Comment: 获取买单可用红包
     * Author: wlf
     * Date: 2020/12/10 11:48
     */
    public function getRedpackList() {
        global $_W, $_GPC;
        $data = [];
        $sid = intval($_GPC['sid']) OR $this->renderError('商户信息不存在，请刷新重试');//商户id
        $money = $_GPC['money'];
        $aid = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid),'aid');
        $redpacklist = Redpack::getNotUseList(9999999,$sid,$aid,0,'payonline');
        $redpacklist = $redpacklist['list'];
        $useList = $noUseList = [];
        if(!empty($redpacklist)){
            foreach($redpacklist as $redpack){
                if($redpack['full_money'] > $money){
                    $noUseList[] = $redpack;
                }else{
                    $useList[] = $redpack;
                }
            }
        }
        $data['defaultId'] = $useList[0]['id'];
        $data['redPackNum'] = count($useList);
        $data['useList'] = $useList;
        $data['noUseList'] = $noUseList;
        $this->renderSuccess('红包列表', $data);

    }


}