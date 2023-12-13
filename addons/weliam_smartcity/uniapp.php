<?php

use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use Yansongda\Pay\Exceptions\BusinessException;
use Yansongda\Pay\Exceptions\GatewayException;

defined('IN_IA') or exit('Access Denied');
class Weliam_smartcityModuleUniapp extends Uniapp
{
    /**
     * Comment: 进行安全验证
     * Author: zzw
     * Date: 2019/9/2 16:23
     * @param $method
     */
    public function securityVerification($method)
    {
        global $_W , $_GPC;
        //定义需要验证的安全文件
        $privateMethod = ['Poster','SetFromId','TempId','GetTempList','SaveFromId'];
        //判断当前访问方法是否为安全文件
        if (in_array($method , $privateMethod) && $_GPC['source'] == 1) {
            if (!$_W['mid']) $this->reLogin();
        }
        //判断方法是否存在 存在进行正常请求
        $method = 'doPage' . ucfirst($method);
        if (method_exists($this , $method)) $this->$method();
        else $this->renderError('错误的请求 - '.$method.' Method does not exist! ');
    }

    /******** 公共接口 ************************************************************************************************/
    /**
     * Comment: 获取当前平台的信息(公共)
     * Author: zzw
     * Date: 2019/7/9 11:09
     */
    public function doPageGetPlatformInfor()
    {
        global $_W , $_GPC;
        $type = $_GPC['type'];//1=平台基本信息；2=热门搜索信息
        if($type != 2){
            $type = 1;
        }
        switch ($type) {
            case 1:
                $set             = Setting::wlsetting_read("base");
                $info['name']    = $set['name'];
                $info['logo']    = tomedia($set['logo']);
                $info['phone']   = $set['phone'];
                $info['loading'] = $set['loading'] ? tomedia($set['loading']) : '';
                $info['videoimg'] = $set['videoimg'] ? tomedia($set['videoimg']) : '';
                $info['languageStatus']   = $set['languageStatus'] ? : 0;
                $title           = '本平台基本信息';
                //获取代理商设置 - 无代理商时显示的图片
                $settings = Setting::wlsetting_read('areaset');
                $info['agent_iamge']   = tomedia($settings['show_img']);
                //是否有名片
                if(p('citycard')){
                    $info['citycardflag'] = 1;
                }else{
                    $info['citycardflag'] = 0;
                }
                break;//本平台基本信息
            case 2:
                $set = Setting::wlsetting_read("base");
                if (strlen($set['shout']) > 1) {
                    $data = explode(',',$set['shout']);
                }
                $info['data'] = $data;
                $info['serbgw'] = $set['serbgw'] ? : '#FFD93F';
                $info['serbgn'] = $set['serbgn'] ? : '#FFF4C4';
                $title = '热门搜索信息';
                break;//热门搜索信息
        }
        if($type != 2){
            $memberset = Setting::wlsetting_read("userset");
            $info['verifycode'] = $memberset['verifycode'];
            $info['smsver'] = $memberset['smsver'];
            //炮灰域名跳转
            $domain = Cloud::wl_syssetting_read('jumpadmin');
            $info['targetDmain'] = [];
            if(!empty($domain['targetDmain'])){
                foreach($domain['targetDmain'] as $ain){
                    if(!empty($ain)){
                        $info['targetDmain'][] = $ain;
                    }
                }
            }
            $info['endDmain'] = $domain['endDmain'];
            if(Customized::init('customized336')){
                $info['newpayonline'] = 1;
            }
            //家政入驻类型
            $houseset = Setting::agentsetting_read('housekeep');
            $info['houseinfo'] = $houseset['intostatus'] ? : 0;
            //私信功能
            $info['privatestatus'] = $memberset['privatestatus'] ? : 0;
            //注销功能
            $info['cancellation'] = $memberset['cancellation'] ? : 0;
            //自动授权定位
            $info['nogetgps'] = $memberset['getGps'] ? : 0;
            //掌上信息回复功能
            $pocket = Setting::agentsetting_read('pocket');
            $info['cancomment'] = $pocket['commentStatus'] ? : 0;
            //140定制
            if(Customized::init('pocket140') > 0){
                $info['newpocket'] = 1;
            }else{
                $info['newpocket'] = 0;
            }
            //1510定制
            if(Customized::init('transfer1510') > 0){
                $info['transfer1510'] = 1;
            }else{
                $info['transfer1510'] = 0;
            }

        }

        $this->renderSuccess($title , $info);
    }
    /**
     * Comment: 文件上传
     * Author: zzw
     * Date: 2019/7/23 9:32
     */
    public function doPageUploadFiles(){
        global $_W , $_GPC;
        #1、判断上传方式
        $uploadType = $_GPC['upload_type'] ? $_GPC['upload_type'] : 1;//1=普通上传；2=微信端上传
        #2、调用方法进行处理
        UploadFile::uploadIndex($_FILES ,$uploadType, $_GPC['id']);
    }
    /**
     * Comment: 搜索内容
     * Author: zzw
     * Date: 2019/12/25 11:58
     */
    public function doPageSearch()
    {
        global $_W , $_GPC;
        #1、参数信息获取
        $type      = $_GPC['type'];//搜索类型  1=商品，2=商户，3=头条
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search    = strlen($_GPC['search']) > 0 ? $_GPC['search'] : $this->renderError('请输入搜索内容！');
        $lng       = $_GPC['lng'] ? : 0;//104.0091133118 经度
        $lat       = $_GPC['lat'] ? : 0;//30.5681964123  纬度
        //获取设置
        $plugin = Setting::agentsetting_read('searchset');
        if(empty($plugin)){
            $plugin = [
                'spname' => '商品',
                'ttname' => '头条',
                'dpname' => '店铺',
                'tzname' => '帖子',
                'spstatus' => 1,
                'ttstatus' => 1,
                'dpstatus' => 1,
                'tzstatus' => 1,
                'sporder' => 4,
                'ttorder' => 3,
                'dporder' => 2,
                'tzorder' => 1,
            ];

            $tabinfo = [
                ['name'=> '商品','order' => 4,'status' => 1,'type' => 1],
                ['name'=> '店铺','order' => 3,'status' => 1,'type' => 2],
            ];
            if(agent_p('headname')){
                $tabinfo[] = ['name'=> '头条','order' => 2,'status' => 1,'type' => 3];
            }
            if(agent_p('pocket')){
                $tabinfo[] = ['name'=> '帖子','order' => 1,'status' => 1,'type' => 4];
            }

            if(empty($type)){
                $type = 1;
            }
        }else{
            $plugin['spname'] = $plugin['spname'] ? : '商品';
            $plugin['ttname'] = $plugin['ttname'] ? : '头条';
            $plugin['dpname'] = $plugin['dpname'] ? : '店铺';
            $plugin['tzname'] = $plugin['tzname'] ? : '帖子';

            $tabinfo = [
                ['name'=> $plugin['spname'],'order' => $plugin['sporder'],'status' => $plugin['spstatus'],'type' => 1],
                ['name'=> $plugin['dpname'],'order' => $plugin['dporder'],'status' => $plugin['dpstatus'],'type' => 2],
            ];

            if(agent_p('headline')){
                $tabinfo[] =  ['name'=> $plugin['ttname'],'order' => $plugin['ttorder'],'status' => $plugin['ttstatus'],'type' => 3];
            }
            if(agent_p('pocket')){
                $tabinfo[] = ['name'=> $plugin['tzname'],'order' => $plugin['tzorder'],'status' => $plugin['tzstatus'],'type' => 4];
            }

            $order = array_column($tabinfo , 'order');
            array_multisort($order , SORT_DESC , $tabinfo);
            if(empty($type)){
                $max = max([$plugin['sporder'],$plugin['ttorder'],$plugin['dporder'],$plugin['tzorder']]);
                if($plugin['sporder'] == $max){
                    $type = 1;
                }else if($plugin['dporder'] == $max){
                    $type = 2;
                }else if($plugin['ttorder'] == $max){
                    $type = 3;
                }else if($plugin['tzorder'] == $max){
                    $type = 4;
                }
            }
        }

        foreach ($tabinfo as $key => &$tab){
            if($tab['status'] != 1){
                unset($tabinfo[$key]);
            }
        }

        #1、根据type进行对应类型的搜索
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";//基本条件生成
        $list = [];
        switch ($type) {
            case 1:
                $title = '商品';
                #商品搜索规则：销售中商品、当前公众号，当前代理商，名称匹配
                //条件生成
                $where .= " AND status = 2 AND (`name` LIKE '%{$search}%' OR price LIKE '%{$search}%') ";
                //sql语句生成  商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
                $sql = "SELECT id,'1' as type,sort FROM " . tablename(PDO_NAME . "rush_activity")
                    . $where . " UNION ALL SELECT id,'2' as type,sort FROM "
                    . tablename(PDO_NAME . "groupon_activity")
                    . $where . " UNION ALL SELECT id,'3' as type,listorder as sort FROM "
                    . tablename(PDO_NAME . "fightgroup_goods")
                    . $where . " UNION ALL SELECT id,'5' as type,indexorder as sort FROM "
                    . tablename(PDO_NAME . "couponlist")
                    . $where . " AND is_show = 0 UNION ALL SELECT id,'7' as type,sort FROM "
                    . tablename(PDO_NAME . "bargain_activity")
                    . $where . " UNION ALL SELECT id,'8' as type,sort FROM "
                    . tablename(PDO_NAME . "delivery_activity")
                    . $where;
                //总数获取
                $totalSql  = str_replace('id,' , 'count(*) as total,' , $sql);
                $totalList = pdo_fetchall($totalSql);
                $total     = array_sum(array_column($totalList , 'total'));
                //列表获取
                $list = pdo_fetchall($sql . "  ORDER BY sort DESC LIMIT {$pageStart},{$pageIndex} ");
                foreach ($list as &$val) {
                    if($val['type'] == 8){
                        $info = pdo_get('wlmerchant_delivery_activity',array('id' => $val['id']),array('name','price','oldprice','thumb','fictitiousnum'));
                        $info['goods_type'] = 'delivery';
                        $info['logo'] = tomedia($info['thumb']);
                        $info['goods_name'] = $info['name'];
                        $buy_num = pdo_getcolumn('wlmerchant_delivery_order',array('gid' => $val['id'],'status >' => 0),array("SUM(num)"));
                        $info['buy_num'] = intval($buy_num + $info['fictitiousnum']);
                        $info['type'] = 8;
                    }else{
                        $info               = WeliamWeChat::getHomeGoods($val['type'] , $val['id']);
                        $info['goods_type'] = $info['plugin'];
                        //删除多余信息
                        unset($info['optionstatus']);
                        unset($info['appointment']);
                        unset($info['allowapplyre']);
                        unset($info['communityid']);
                        unset($info['address']);
                        unset($info['plugin']);
                        unset($info['user_list']);
                        unset($info['user_num']);
                        unset($info['pay_state']);
                        unset($info['is_vip']);
                        unset($info['discount_price']);
                        unset($info['spec']);
                    }

                    //数据处理
                    switch ($info['type']) {
                        case 1:
                            $info['url'] = h5_url('pages/subPages/goods/index' , ['id' => $val['id'] , 'type' => 1]);
                            break;//抢购商品
                        case 2:
                            $info['url'] = h5_url('pages/subPages/goods/index' , ['id' => $val['id'] , 'type' => 2]);
                            break;//团购商品
                        case 3:
                            $info['url'] = h5_url('pages/subPages/goods/index' , ['id' => $val['id'] , 'type' => 3]);
                            break;//拼团商品
                        case 4:
                            $info['url'] = h5_url('pages/mainPages/memberCard/memberCard' , [
                                'id'   => $val['id'] ,
                                'type' => 4
                            ]);
                            break;//大礼包
                        case 5:
                            $info['url'] = h5_url('pages/subPages/goods/index' , ['id' => $val['id'] , 'type' => 5]);
                            break;//优惠券
                        case 6:
                            $info['url'] = h5_url('pages/mainPages/memberCard/memberCard' , [
                                'id'   => $val['id'] ,
                                'type' => 6
                            ]);
                            break;//折扣卡
                        case 7:
                            $info['url'] = h5_url('pages/subPages/goods/index' , ['id' => $val['id'] , 'type' => 7]);
                            break;//砍价商品
                        case 8:
                            $info['url'] = h5_url('pages/subPages2/businessCenter/foodIntroduced/foodIntroduced' , ['id' => $val['id']]);
                            break;//配送商品
                    }
                    $val['info']       = $info;
                    $val['goods_type'] = $info['goods_type'];
                    if ($val['goods_type'] == 'wlfightgroup') $val['goods_type'] = 'fight';
                    //获取商品详情链接   1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品
                    unset($val['id']);
                    unset($val['sort']);
                    unset($val['type']);
                }
                break;//商品
            case 2:
                $title = '商户';
                //条件生成
                $where .= " AND status = 2 AND enabled = 1 AND ( storename LIKE '%{$search}%' OR `describe` LIKE '%{$search}%') ";
                //sql语句生成
                $sql = "SELECT * FROM " . tablename(PDO_NAME . "merchantdata") . $where;
                //总数获取
                $totalSql = str_replace('*' , 'count(*)' , $sql);
                $total    = pdo_fetchcolumn($totalSql);
                //列表获取
                $field = "id,storename,logo,address,storehours,location,pv,score,panorama,videourl,tag";
                $sql   = str_replace('*' , $field , $sql);
                $list  = pdo_fetchall($sql . " ORDER BY listorder DESC LIMIT {$pageStart},{$pageIndex} ");
                foreach ($list as $key => &$val) {
                    //获取店铺分类信息
                    $val['panorama'] = !empty($val['panorama']) ? 1 : 0;
                    $val['videourl'] = !empty($val['videourl']) ? 1 : 0;
                    //店铺标签
                    $val['tags'] = [];
                    $tagids      = unserialize($val['tag']);
                    if (!empty($tagids)) {
                        $tags        = pdo_getall('wlmerchant_tags' , ['id' => $tagids] , ['title']);
                        $val['tags'] = $tags ? array_column($tags , 'title') : [];
                    }
                    unset($val['tag']);
                    //获取店铺信息地址跳转链接
                    $url              = h5_url('pages/mainPages/store/index' , ['sid' => $val['id']]);
                    $val['jump_link'] = $url;
                    //处理图片信息
                    $val['logo'] = tomedia($val['logo']);
                    //处理营业时间
                    $storehours        = unserialize($val['storehours']);
                    if(!empty($storehours['startTime'])){
                        $val['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
                    }else{
                        $val['storehours'] = '';
                        foreach($storehours as $hk => $hour){
                            if($hk > 0){
                                $val['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                            }else{
                                $val['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                            }
                        }
                    }

                    //处理店铺距离
                    $location      = unserialize($val['location']);
                    $val['length'] = Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
                    if ((!empty($val['length']) || is_numeric($val['length'])) && $lng && $lat) {
                        if ($val['length'] > 9999998) {
                            $val['distance'] = " ";
                        }
                        else if ($val['length'] > 1000) {
                            $val['distance'] = (floor(($val['length'] / 1000) * 10) / 10) . "km";
                        }
                        else {
                            $val['distance'] = intval($val['length']) . "m";
                        }
                    }
                    unset($val['location']);
                }
                //获取店铺商品活动信息
                $list = WeliamWeChat::getStoreList($list);
                break;//商户
            case 3:
                $title = '头条';
                //条件生成
                $where .= " AND title LIKE '%{$search}%' ";
                //sql语句生成
                $sql = " SELECT * FROM " . tablename(PDO_NAME . "headline_content") . $where;
                //总数获取
                $totalSql = str_replace('*' , 'count(*)' , $sql);
                $total    = pdo_fetchcolumn($totalSql);
                //列表获取
                $sql  = str_replace('*' , 'id,display_img,title,browse' , $sql);
                $list = pdo_fetchall($sql . " ORDER BY release_time DESC LIMIT {$pageStart},{$pageIndex}");
                foreach ($list as $k => &$v) {
                    $v['display_img'] = tomedia($v['display_img']);
                    $v['jump_link']   = h5_url('pages/mainPages/headline/headlineDetail' , ['headline_id' => $v['id']]);//头条详情链接
                }
                break;//头条
        }
        #1、信息拼装
        $data = [
            'tabinfo' => $tabinfo,
            'total'   => ceil($total / $pageIndex) ,
            'list'    => $list,
            'type'    => $type
        ];

        $this->renderSuccess($title . '列表' , $data);
    }
    /**
     * Comment: 获取图片验证码
     * Author: zzw
     * Date: 2019/8/8 9:09
     */
    public function doPageGVC()
    {
        global $_W , $_GPC;
        load()->classs("captcha");
        error_reporting(0);
        session_start();
        $captcha = new Captcha();
        $captcha->build(108 , 44);
        $hash = md5(strtolower($captcha->phrase) . $_W["config"]["setting"]["authkey"]);
        isetcookie("__code" , $hash);
        $_SESSION["__code"] = $hash;
        $time = time();
        $code = $captcha->phrase;
        pdo_insert(PDO_NAME . 'gvccode', ['createtime' => $time,'code' => $code]);
        ob_start();
        imagepng($captcha->image);
        $image_data = base64_encode(ob_get_contents());
        ob_end_clean();
        $image_data = "data:image/png;base64," . $image_data;
        $this->renderSuccess('图形验证码信息' , ['image_data' => $image_data , 'time' => $time]);
    }
    /**
     * Comment: 发送短信验证码
     * Author: zzw
     * Date: 2019/8/8 9:37
     */
    public function doPagePIN()
    {
        global $_W , $_GPC;
        #1、参数获取
        $type  = $_GPC['type'] ? $_GPC['type'] : 1;//1=注册 2=登录 3修改支付密码
        $phone = $_GPC['phone'];//手机号码
        $mid = $_W['mid'];
        if (!$phone) $this->renderError('请输入手机号码');
        if ($_W['wlsetting']['userset']['verifycode']>0) {
            $time = $_GPC['time'];
            $code = trim($_GPC['code']);
            if(empty($code)){
                $this->renderError('请输入图形验证码');
            }
            $truecode = pdo_getcolumn(PDO_NAME.'gvccode',array('createtime'=>$time),'code');
            if($code != $truecode){
                $this->renderError('图形验证码错误');
            }
            pdo_delete('wlmerchant_gvccode',array( 'createtime <'=> $time - 86400));
        }
        #2、分钟级流控  每分钟每个手机号只能发送一次
        $time         = igetcookie("phone_" . $phone);
        $intervalTime = 65;//记录手机号信息  偏移5秒钟，用于兼容接口请求时间
        if ($time) {
            $surplusSecond = time() - $time;
            if ($surplusSecond > 0) {
                $surplusTime = $intervalTime - $surplusSecond;
                $this->renderError('发送过于频繁，请在' . $surplusTime . '秒后进行发送');
            }
        }
        isetcookie("phone_" . $phone , time() , $intervalTime);
        #3、判断当前手机是否已经绑定
        $where['mobile']  = $phone;
        $where['uniacid'] = $_W['uniacid'];
        if ($mid > -1) $where['id !='] = $mid;
        $have = pdo_get(PDO_NAME . "member" , $where);
        if ($have && $type == 1) $this->renderError('该手机已被绑定');
        #4、验证码发送
        $code = rand(1000 , 9999);
        $res = WeliamWeChat::smsSF($code , $phone,$mid);
        if ($res['result'] == 1) {
            pdo_delete('wlmerchant_pincode',array('mobile'=>$phone));
            pdo_insert(PDO_NAME.'pincode', array('mobile'=>$phone,'code'=>$code,'time'=>time()));
            $this->renderSuccess('发送成功' , ['code' => 0]);
        }
        else {
            $this->renderError('验证码发送失败:' . $res['msg']);
        }
    }
    /**
     * Comment: 获取地区列表
     * Author: wlf
     * Date: 2019/8/12 14:12
     */
    public function doPageCityInfo()
    {
        global $_W;
        $citylist = pdo_getall('wlmerchant_area' , ['level' => 1,'visible' => 2] , ['id' , 'name']);
        foreach ($citylist as $key => &$prov) {
            $prov['area'] = pdo_getall('wlmerchant_area' , ['level' => 2 , 'pid' => $prov['id'],'visible' => 2] , ['id' , 'name']);
            foreach ($prov['area'] as $k => &$area) {
                $area['dist'] = pdo_getall('wlmerchant_area' , ['level' => 3 , 'pid' => $area['id'],'visible' => 2] , ['id' , 'name']);
            }
        }
        $this->renderSuccess('地区数据' , $citylist);
    }
    /**
     * Comment: 生成（获取）海报信息
     * Author: zzw
     * Date: 2019/8/14 18:30
     */
    public function doPagePoster()
    {
        global $_W , $_GPC;
        #1、参数获取
        //1=分销邀请购买、2=分销邀请下级、3=抢购、4=团购、5=卡券、6=拼团、7=砍价、8=店铺、9=业务员商家入驻、10=积分商品海报、11=个人名片海报 14=同城活动 17红包活动
        $type = trim($_GPC['type']) OR $this->renderError('请明确海报类型');
        $id        = intval($_GPC['id']);//商户/商品的id
        $bgImg     = trim($_GPC['bg_img']);
        $source    = $_GPC['source'] ? : 1;//渠道：1=公众号（默认）；2=h5；3=小程序
        $goodsType = $_GPC['goods_type'] ? : 1;//商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
        //527定制红包信息
        $redid     = $_GPC['redid'] ? : 0;
        
        #2、获取自定义海报设置信息
        if (p('diyposter')) {
            $ids = [
                1  => $_W['wlsetting']['diyposter']['distpid'] ,//分销自定义海报id
                2  => $_W['wlsetting']['diyposter']['distpid'] ,//分销自定义海报id
                3  => $_W['wlsetting']['diyposter']['rushpid'] ,//抢购自定义海报id
                4  => $_W['wlsetting']['diyposter']['grouponpid'] ,//团购自定义海报id
                5  => $_W['wlsetting']['diyposter']['cardpid'] ,//卡券自定义海报id
                6  => $_W['wlsetting']['diyposter']['fgrouppid'] ,//拼团自定义海报id
                7  => $_W['wlsetting']['diyposter']['bargainid'] ,//砍价自定义海报id
                8  => $_W['wlsetting']['diyposter']['storepid'] ,//商户自定义海报id
                9  => $_W['wlsetting']['diyposter']['storepid'] ,//业务员自定义海报id
                10 => $_W['wlsetting']['diyposter']['consumption_id'] ,//积分商品自定义海报id
                11 => $_W['wlsetting']['diyposter']['user_card_id'] ,//个人名片自定义海报id
                12 => $_W['wlsetting']['diyposter']['yellow_id'] ,//黄页自定义海报id
                14 => $_W['wlsetting']['diyposter']['activityid'] ,//活动自定义海报id
                16 => $_W['wlsetting']['diyposter']['housekeepid'], //家政服务服务项目海报id
            ];
            if (is_array($ids) && $ids[$type] > 0) {
                $diyInfo = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $ids[$type]] , [
                    'bg' ,
                    'otherbg'
                ]);
                $bgList  = iunserializer($diyInfo['otherbg']);
                if (is_array($bgList) && count($bgList) > 0) {
                    foreach ($bgList as $key => &$val) {
                        $val = tomedia($val);
                    }
                }
            }
        }
        #3、获取海报信息  1=分销邀请购买、2=分销邀请下级、3=抢购、4=团购、5=卡券、6=拼团、7=砍价、8=店铺、9=业务员商家入驻、10=积分商品海报
        $useagent = 'wechat';
        switch ($type) {
            case 1:
                $poster = Poster::createDistriPoster($id , $source , $bgImg , $goodsType);
                break;//分销邀请购买
            case 2:
                $poster = Poster::createInvitevipPoster($_W['mid'] , $source , $bgImg);
                break;//分销邀请下级
            case 3:
                $poster = Poster::createRushPoster($id , $source , $bgImg);
                break;//抢购
            case 4:
                $poster = Poster::createGrouponPoster($id , $source , $bgImg);
                break;//团购
            case 5:
                $poster = Poster::createCouponPoster($id , $source , $bgImg);
                break;//卡券
            case 6:
                $poster = Poster::createFightgroupPoster($id , $source , $bgImg);
                break;//拼团
            case 7:
                $poster = Poster::createBargainPoster($id , $source , $bgImg);
                break;//砍价
            case 8:
			case 17:	
                $poster = Poster::createStorePoster($id , $source , $bgImg,$redid);
                break;//店铺
            case 9:
                $poster = Poster::createSalesmanPoster($_W['mid'] , 0 , $useagent , $bgImg);
                break;//业务员
            case 10:
                $poster = Poster::createConsumptionPoster($id , $source , $bgImg);
                break;//积分商品海报
            case 11:
                $poster = Poster::createUserCardPoster($id , $source , $bgImg);
                break;//个人名片海报
            case 12:
                $poster = Poster::createYellowPoster($id , $source , $bgImg);
                break;//商户114海报
            case 13:
                $poster = Poster::createDrawPoster($id , $source );
                break;//抽奖海报生成
            case 14:
                $poster = Poster::createActivityPoster($id , $source , $bgImg);
                break;//活动
            case 15:
                $poster = Poster::createDatingPoster($id , $source , $bgImg);
                break;//相亲交友 - 红娘邀请函
            case 16:
                $poster = Poster::createHousekeepPoster($id , $source , $bgImg);
                break;//家政服务项目海报
        }
        #4、数据拼装
        $data['url']     = $poster . "?v=" . time();
        $data['bg_list'] = is_array($bgList) ? $bgList : [];
        $this->renderSuccess('海报信息' , $data);
    }
    /**
     * Comment: 推荐商品获取
     * Author: zzw
     * Date: 2019/8/15 9:09
     */
    public function doPageGetRecommendGoods()
    {
        global $_W , $_GPC;
        if(!empty($_W['wlsetting']['base']['recommendGoods'])) $this->renderSuccess('推荐商品获取' , []);
        #1、参数获取
        $num  = $_GPC['num'] ? $_GPC['num'] : 4;//获取的商品数量
        $type = $_GPC['type'] ? $_GPC['type'] : 0;//当前商品类型 1=抢购，2=团购，3=拼团， 5=优惠券，7=砍价
        $id   = $_GPC['id'] ? $_GPC['id'] : 0;//当前商品的id
        #2、商品列表获取
        $list = WeliamWeChat::getRecommendGoods($num , $type , $id);
        $this->renderSuccess('推荐商品获取' , $list);
    }
    /**
     * Comment: 商品购买弹幕获取
     * Author: zzw
     * Date: 2019/8/16 14:00
     */
    public function doPagePayBarrageList()
    {
        global $_W , $_GPC;
        #1、参数获取
        $type = $_GPC['type'];//商品类型：1=抢购  2=团购  3=拼团 4=一卡通开卡弹幕 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
        $id   = $_GPC['id'];//商品id
        if($_W['wlsetting']['base']['goodBarrage'] > 0 ){
            $list = [];
        }else{
            if (!$type) $this->renderError('错误的商品类型!');
            #2、根据商品类型配置查询参数信息
            $by = " GROUP BY a.mid ORDER BY a.createtime DESC LIMIT 10 ";
            switch ($type) {
                case 1:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "rush_order")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.activityid = {$id} AND a.status !=0 AND a.status !=5  AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//抢购商品
                case 2:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "order")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.fkid = {$id} AND a.status !=0 AND a.status !=5 AND plugin = 'groupon' AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//团购商品
                case 3:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "order")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.fkid = {$id} AND a.status !=0 AND a.status !=5 AND plugin = 'wlfightgroup' AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//拼团商品
                case 4:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar,a.createtime,a.expiretime FROM "
                        . tablename(PDO_NAME . "halfcardmember") . " as a RIGHT JOIN "
                        . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id WHERE a.uniacid = {$_W['uniacid']} " . $by);
                    break;//一卡通开卡弹幕
                case 5:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "order")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.fkid = {$id} AND a.status !=0 AND a.status !=5 AND plugin = 'coupon' AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//优惠券
                case 6:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "order")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.fkid = {$id} AND a.status !=0 AND a.status !=5 AND plugin = 'halfcard' AND a.uniacid = {$_W['uniacid']} " . $by);
                    $list = array_column($list , 'nickname');
                    break;//折扣卡
                case 7:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar,a.bargainprice FROM " . tablename(PDO_NAME . "bargain_helprecord")
                        . " as a RIGHT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.activityid = {$id}  AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//砍价商品
                case 8:
                    $list = pdo_fetchall("SELECT b.nickname,b.avatar FROM " . tablename(PDO_NAME . "order")
                        . " as a LEFT JOIN " . tablename(PDO_NAME . "member")
                        . " as b ON a.mid = b.id WHERE a.plugin = 'consumption' AND a.fkid = {$id} AND a.status !=0 AND a.status !=5 AND a.uniacid = {$_W['uniacid']} " . $by);
                    break;//积分商品
            }
            if ($type == 4) {
                foreach ($list as &$li) {
                    $li['day'] = ceil(($li['expiretime'] - $li['createtime']) / 86400);
                }
            }
        }
        $this->renderSuccess('商品购买弹幕列表' , $list);
    }
    /**
     * Comment: 获取公告详细信息
     * Author: zzw
     * Date: 2019/9/10 9:09
     */
    public function doPageNoticeDetail()
    {
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');
        #1、获取详细信息
        $info               = pdo_get(PDO_NAME . "notice" , ['id' => $id] , ['title' , 'content' , 'createtime']);
        $info['createtime'] = date("Y-m-d H:i:s" , $info['createtime']);
        $info ? $this->renderSuccess('公告详情' , $info) : $this->renderError('公告不存在!');
    }
    /**
     * Comment: 链接转换列表
     * Author: zzw
     * Date: 2019/8/16 14:42
     */
    public function doPageTransformationLink(){
        $list = Links::getTransformationLink();

        $this->renderSuccess('链接转换列表' , $list);
    }
    /**
     * Comment: 文本替换内容
     * Author: wlf
     * Date: 2019/09/19 16:20
     * @return array
     */
    public function doPageTextSubstitution(){
        global $_W , $_GPC;
        $data                 = Setting::wlsetting_read('trade');
        $base                 = Setting::wlsetting_read('base');
        $data['credittext']   = $data['credittext'] ? $data['credittext'] : '积分';
        $data['moneytext']    = $data['moneytext'] ? $data['moneytext'] : '余额';
        $data['halfcardtext'] = $data['halfcardtext'] ? $data['halfcardtext'] : '一卡通';
        $data['privilege']    = $data['privilege'] ? $data['privilege'] : '特权';
        $data['hljkttext']    = $data['hljkttext'] ? $data['hljkttext'] : '立即开通';
        $data['hljxftext']    = $data['hljxftext'] ? $data['hljxftext'] : '立即续费';
        if(empty($data['fxtext'])){
            $data['fxtext'] = Customized::init('distributionText') > 0 ? '共享股东' : '分销';
        }
        $data['xxtext']       = $data['xxtext'] ? $data['xxtext'] : '客户';
        $data['sjtext']       = $data['sjtext'] ? $data['sjtext'] : '上级';
        $data['yjtext']       = $data['yjtext'] ? $data['yjtext'] : '佣金';
        if(empty($data['fxstext'])){
            $data['fxstext'] = Customized::init('distributionText') > 0 ? '共享股东' : '分销商';
        }
        $data['myposter']     = $data['myposter'] ? $data['myposter'] : '我的海报';
        $data['sysname']      = $base['name'] ? $base['name'] : '智慧城市';
        $data['shangchengtext'] = $data['shangchengtext'] ? $data['shangchengtext'] : '商城';
        $data['shiylctext']   = $data['shiylctext'] ? $data['shiylctext'] : '使用流程';
        $data['jiagsmext']    = $data['jiagsmext'] ? $data['jiagsmext'] : '价格说明';
        $data['maidantext']    = $data['maidantext'] ? $data['maidantext'] : '买单';

        //小程序审核模式
        $wxappset = Setting::wlsetting_read('wxappset');
        $data['examineing'] = $wxappset['examineing'];
        $data['collocStatus'] = $wxappset['collocStatus'] ? : 0;
        //小程序自定义头部
        $data['bgc'] = $wxappset['top_bg_color'] ? $wxappset['top_bg_color'] : '#ffffff';
        $data['textc'] = $wxappset['top_text_color'] == 1 ? '#ffffff': '#000000';
        //074定制
        $data['flag074'] = Customized::init('integral074') > 0 ? 1 : 0;

        $this->renderSuccess('文本替换' , $data);
    }
    /**
     * Comment: 关注接口
     * Author: wlf
     * Date: 2019/11/25 17:10
     * @return array
     */
    public function doPageFollow()
    {
        global $_W , $_GPC;
        $type        = $this->conversion($_GPC['type']);
        $data        = [];
        $forcefollow = $_W['wlsetting']['share']['forcefollow'];
        if (!empty($_W['wlmember']['openid']) && $_W['source'] == 1) {
            $follow = pdo_getcolumn('mc_mapping_fans' , ['openid' => $_W['wlmember']['openid']] , 'follow');
            if (empty($follow)) {
                if (empty($type)) {  //引导关注
                    if ($_W['wlsetting']['share']['gz_status']) {
                        $data['status'] = 1;
                    }
                }
                else if (in_array($type , $forcefollow) || $type == 'storeRedpack') {
                    $data['status'] = 1;
                }
            }
        }
        if (empty($data['status'])) {
            $data['status']  = 0;
            $data['gzimage'] = '';
            $this->renderSuccess('不显示' , $data);
        }
        else {
            $data['gzimage'] = tomedia($_W['wlsetting']['share']['gz_image']);
            pdo_insert('wlmerchant_halfcard_qrscan' , [
                'uniacid'  => $_W['uniacid'] ,
                'openid'   => $_W['wlmember']['openid'] ,
                'scantime' => time() ,
                'cardid'   => intval($_GPC['id']) ,
                'type'     => $type
            ]);
            if(Customized::init('pocket140') > 0){
                $data['korea'] = 1;
            }else{
                $data['korea'] = 0;
            }
            $this->renderSuccess('显示关注信息' , $data);
        }
    }
    /**
     * Comment: 转换plugin参数
     * Author: wlf
     * Date: 2019/11/26 14:43
     */
    public function conversion($type)
    {
        $types = [
            1 => 'rush' ,
            2 => 'groupon' ,
            3 => 'wlfightgroup' ,
            4 => 'activity',
            5 => 'wlcoupon' ,
            6 => 'payOnline' ,
            7 => 'bargain' ,
            8 => 'helpBargain' ,
            9 => 'distribution' ,
            10 => 'draw',
            12 => 'pocket',
            11 => 'mobilerecharge',
            13 => 'integral',
            14 => 'storeRedpack'
        ];
        return $types[$type];
    }
    /**
     * Comment: 获取默认分享信息
     * Author: wlf
     * Date: 2019/09/23 15:21
     * @return array
     */
    public function doPageShareinfo(){
        global $_W , $_GPC;
        $pageinfo      = $_GPC['pageinfo'];
        $data     = Logistics::getShareInfo($pageinfo);
        //分销商关系绑定
        $head_id = intval($_GPC['head_id']);
        if ($head_id > 0 && $_W['mid'] > 0 && p('distribution') && $_W['mid'] != $head_id) {
            Distribution::addJunior($head_id, $_W['mid']);
            $data['cleanheadid'] = 1;
        }
        //336定制  全民分销商  所有用户进入平台则自动成为分销商
        if(Customized::init('customized336') && intval($_W['mid']) > 0){
            //获取分销商设置信息   dis_model：0=默认模式；1=全民分销
            $set = $_W['wlsetting']['distribution'];
            if($set['dis_model'] == 1){
                //获取分销商信息
                $disInfo = pdo_get(PDO_NAME."distributor",['mid'=>$_W['mid']]);
                try {
                    if (!$disInfo) {
                        //没有分销商信息 添加分销商信息
                        $disParams = [
                            'uniacid'    => $_W['uniacid'] ,
                            'aid'        => $_W['aid'] ,
                            'mid'        => $_W['mid'] ,
                            'disflag'    => 1 ,
                            'leadid'     => $head_id ? : 0,
                            'createtime' => time() ,
                            'nickname'   => $_W['wlmember']['nickname'] ,
                            'realname'   => $_W['wlmember']['realname'] ,
                            'mobile'     => $_W['wlmember']['mobile'] ,
                            'expiretime' => 0 ,
                            'source'     => 0 ,
                            'updatetime' => time() ,
                        ];
                        pdo_insert(PDO_NAME."distributor",$disParams);
                        $disId = pdo_insertid();
                        if($disId > 0) pdo_update(PDO_NAME."member",['distributorid'=>$disId],['id'=>$_W['mid']]);
                    }else if ($disInfo['disflag'] == 0) {
                        //当前用户是下线  修改为分销商信息
                        pdo_update(PDO_NAME."distributor",['leadid'=>$head_id,'disflag'=>1,'updatetime'=>time()],['id'=>$disInfo['id']]);
                    }
                } catch (\Exception $e) {
                    $this->renderError($e->getMessage());
                }
            }
        }

        $this->renderSuccess('分享信息' , $data);
    }


    /**
     * Comment: 模板id获取
     * Author: zzw
     * Date: 2020/1/14 16:22
     */
    public function doPageTempId(){
        global $_W,$_GPC;
        #1、参数获取
        $temp_type = $_GPC['temp_type'];
        #2、模板id获取
        $set = Setting::wlsetting_read('new_temp_set');
        $data = [
            0 => [
                0 => [
                    'temp_id'   => $set['pay']['weappSubscription']['id'] ,
                    'status'    => $set['pay']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'pay'
                ]
            ],//提交订单按钮【订单支付成功通知】
            1 => [
                0 => [
                    'temp_id'   => $set['after_sale']['weappSubscription']['id'] ,
                    'status'    => $set['after_sale']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'after_sale'
                ],
                1 => [
                    'temp_id'   => $set['refund']['weappSubscription']['id'] ,
                    'status'    => $set['refund']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'refund'
                ],
            ],//申请售后按钮【售后状态通知/退款通知】
            2 => [
                0 => [
                    'temp_id'   => $set['fight']['weappSubscription']['id'] ,
                    'status'    => $set['fight']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'fight'
                ],
                1 => [
                    'temp_id'   => $set['send']['weappSubscription']['id'] ,
                    'status'    => $set['send']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'send'
                ],
            ],//回到首页、查看订单按钮 - 拼团商品 - 发货订单【拼团进度通知/订单发货通知】
            3 => [
                0 => [
                    'temp_id'   => $set['fight']['weappSubscription']['id'] ,
                    'status'    => $set['fight']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'fight'
                ],
                1 => [
                    'temp_id'   => $set['write_off']['weappSubscription']['id'] ,
                    'status'    => $set['write_off']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'write_off'
                ],
            ],//回到首页、查看订单按钮 - 拼团商品 - 核销、自提订单【拼团进度通知/核销成功通知】
            4 => [
                0 => [
                    'temp_id'   => $set['send']['weappSubscription']['id'] ,
                    'status'    => $set['send']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'send'
                ]
            ],//回到首页、查看订单按钮 - 非拼团商品 - 发货订单【订单发货通知】
            5 => [
                0 => [
                    'temp_id'   => $set['write_off']['weappSubscription']['id'] ,
                    'status'    => $set['write_off']['weappSubscription']['status'] ,
                    'type'      => 2 ,
                    'temp_type' => 'write_off'
                ]
            ],//回到首页、查看订单按钮 - 非拼团商品 - 核销、自提订单【核销成功通知】
        ];
        #2、信息处理  如果已存在授权信息 则不获取该订阅消息的授权信息
        $info = $data[$temp_type];
        foreach($info as $key => $val){
            $res = pdo_get(PDO_NAME."formid",['mid'=>$_W['mid'],'temp_type'=>$val['temp_type']]);
            if($res) unset($info[$key]);
        }
        $info = is_array($info) ? array_values($info) : [];

        $this->renderSuccess('模板id',$info);
    }
    /**
     * Comment: 储存小程序模板消息发送必须的form_id
     * Author: zzw
     * Date: 2019/11/1 9:24
     */
    public function doPageSetFromId(){
        global $_W , $_GPC;
        $info = json_decode(base64_decode($_GPC['info']),true);
        foreach($info as $key => $val){
            $data = [
                'uniacid'     => $_W['uniacid'] ,
                'mid'         => $_W['mid'] ,
                'form_id'     => $val['temp_id'] ,
                'expiry_time' => time() + ((3600 * 24 * 7) - 3600) ,//过期时间为7天，偏移一小时作为时间差处理
                'create_time' => time() ,
                'type'        => $val['type'] ,
                'temp_type'   => $val['temp_type']
            ];
            pdo_insert(PDO_NAME . "formid" , $data);
        }

        $this->renderSuccess('储存form_id');
    }

    /**
     * Comment: 获取订阅消息模板消息列表
     * Author: zzw
     * Date: 2021/2/18 17:43
     */
    public function doPageGetTempList(){
        global $_W,$_GPC;
        //参数获取
        $set = Setting::wlsetting_read('new_temp_set');
        if($_W['source'] == 3) $key = 'weappSubscription';//微信小程序
        else  $key = 'wechatSubscription';//微信公众号
        $titleList = [
            'pay'        => '订单支付成功',
            'send'       => '订单发货提醒',
            'after_sale' => '售后状态通知',
            'refund'     => '退款成功通知',
            'service'    => '业务处理通知',
            'write_off'  => '核销成功提醒',
            'fight'      => '拼团结果通知',
            'sign'       => '签到成功通知',
            'change'     => '积分变更提醒',
        ];
        //修改获取信息
        $sql = " SELECT MAX(scene) FROM ".tablename(PDO_NAME."formid")." WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ";
        $list = [];
        foreach($set as $index => $item){
            if($item[$key]['status'] == 1){
                //基础信息获取
                $item[$key]['title'] = $titleList[$index];
                $item[$key]['num'] = pdo_count(PDO_NAME."formid",['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid'],'form_id'=>$item[$key]['id']]);
                $item[$key]['temp_type'] = $index;
                unset($item[$key]['status']);
                $list[$index] = $item[$key];
            }
        }
        //信息拼装
        $params = $_W['account']->account ? : get_object_vars($_W['account']);
        $data   = [
            'list'    => array_values($list),
            'temp_id' => Setting::wlsetting_read('wechat_template_id') ? : '',
            'app_id'  => $params['key'],
        ];

        $this->renderSuccess('模板id',$data);
    }
    /**
     * Comment: 保存订阅的模板消息id
     * Author: zzw
     * Date: 2021/2/18 17:51
     */
    public function doPageSaveFromId(){
        global $_W , $_GPC;
        $info = json_decode(html_entity_decode($_GPC['info']),true);
        //信息判断
        if(!$info['form_id']) $this->renderError('模板id不存在，模板授权获取失败');
        if(!$info['temp_type']) $this->renderError('模板类型错误，模板授权获取失败');
        //信息拼装
        $data = [
            'uniacid'     => $_W['uniacid'] ,
            'mid'         => $_W['mid'] ,
            'form_id'     => $info['form_id'] ,
            'expiry_time' => time() + ((3600 * 24 * 7) - 3600) ,//过期时间为7天，偏移一小时作为时间差处理
            'create_time' => time() ,
            'type'        => $_W['source'] == 3 ? 2 : 3 ,//id类型：1=小程序模板消息id，2=小程序订阅消息id，3=公众号订阅消息id
            'temp_type'   => $info['temp_type'],
        ];
        pdo_insert(PDO_NAME . "formid" , $data);

        $this->renderSuccess('储存form_id');
    }
    /**
     * Comment: 获取平台客户设置信息
     * Author: zzw
     * Date: 2019/11/1 16:15
     */
    public function doPageCustomerService()
    {
        global $_W , $_GPC;
        #1、获取设置信息
        if($_W['aid']>0){
            $set           = Setting::agentsetting_read('agentcustomer');
            if($set['alone'] > 0){
                $set           = Setting::wlsetting_read("customer");
            }
        }else{
            $set           = Setting::wlsetting_read("customer");
        }
        $set['qrcode'] = tomedia($set['qrcode']);
        $set['susicon'] = tomedia($set['susicon']);

        $set['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        $set['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;

        $set['listimgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['listwidth'])) ? trim($_W['wlsetting']['base']['listwidth']) : 640;
        $set['listimgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['listheight'])) ? trim($_W['wlsetting']['base']['listheight']) :  300;

        $set['payclose'] = intval($_W['wlsetting']['base']['payclose']);
        $set['wxappcur'] = $set['wxapptype'] ? : 0;

        $this->renderSuccess('客户设置信息' , $set);
    }
    /**
     * Comment: 小程序客服消息
     * Author: zzw
     * Date: 2019/11/19 9:07
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doPageWxAppCustomerService()
    {
        global $_W , $_GPC;
        #1、接收信息 写入日志
        $input = $_GPC['__input'];
        Util::wl_log('customerService' , PATH_MODULE . "log/" , $input , '微信小程序客服接收信息' , false); //写入日志记录
        #2、请求验证（配置信息时进行验证接口是否可用的请求验证）
        $verRes = WeApp::pleaseVerification($_GET);
        if ($verRes) {
            echo $verRes;
            die;
        }
        #3、不是验证消息
        //判断是否是支付回调
        if($input['Event'] == 'funds_order_pay'){
            $flag = pdo_getcolumn(PDO_NAME.'temporary_orderlist',array('orderno'=>$input['order_info']['orderno']),'id');
            if(empty($flag)){
                $deteletime = time() + 30;
                pdo_insert(PDO_NAME . 'temporary_orderlist', ['orderno'=>$input['order_info']['orderno'],'deteletime'=>$deteletime]);
                $info = [
                    'type'           => 5 ,//支付方式
                    'tid'            => $input['order_info']['trade_no'] ,//订单号
                    'transaction_id' => $input['order_info']['transaction_id'] ,
                    'time'           => strtotime($input['order_info']['pay_time']),
                ];
                PayResult::main($info);//调用方法处理订单
            }
            exit('success');
        }else{
            #发送客服消息
            WeApp::CustomerService($input);
        }


    }
    /**
     * Comment: 选择信息获取
     * Author: zzw
     * Date: 2019/11/27 15:50
     */
    public function doPageSelectInfo()
    {
        global $_W , $_GPC;
        #1、参数获取
        $cate_one = $_GPC['cate_one'];
        $cate_two = $_GPC['cate_two'];
        //1=掌上信息;2=好店首页;3=积分商城;4=名片首页;5=同城配送;6=黄页114;7=求职招聘;8=企业;9=相亲交友;
        $type = $_GPC['type'] ? : 1;
        #2、生成选择信息数组
        switch ($type) {
            case 1:
                $whole = [
                    [
                        'id'   => '0' ,
                        'name' => '全部' ,
                        'list' => []
                    ]
                ];
                //获取掌上信息分类列表
                $list = pdo_fetchall("SELECT id,title as name FROM " . tablename(PDO_NAME . "pocket_type") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND url = '' AND status = 1 AND `type` = 0 AND isnav = 0 ORDER BY sort DESC,id DESC ");
                if (is_array($list) && count($list) > 0) {
                    foreach ($list as $key => &$val) {
                        $val['list'] = pdo_fetchall("SELECT id,title as name FROM " . tablename(PDO_NAME . "pocket_type") . " WHERE url = '' AND status = 1 AND `type` = {$val['id']} ORDER BY sort DESC,id DESC ");
                    }
                }
                $list = array_merge($whole , $list);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => 'do=WholeCityList' ,
                    'class'  => $list ,
                    'orders' => [
                        ['title' => '发帖时间' , 'val' => 0] ,
                        ['title' => '浏览数量' , 'val' => 1] ,
                        ['title' => '分享数量' , 'val' => 2] ,
                        ['title' => '点赞数量' , 'val' => 3] ,
                    ] ,
                ];
                break;//掌上信息
            case 2:
                $whole = [
                    [
                        'cate_one' => '0' ,
                        'name'     => '全部' ,
                        'list'     => []
                    ]
                ];
                //获取好店分类列表
                $shopList = pdo_fetchall("SELECT id as cate_one,`name` FROM " . tablename(PDO_NAME . "category_store") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND state = 0 AND parentid = 0 AND enabled = 1 ORDER BY displayorder DESC,id DESC ");
                if (is_array($shopList) && count($shopList) > 0) {
                    foreach ($shopList as $shopKey => &$shopVal) {
                        if($cate_one == $shopVal['cate_one']){
                            $shopVal['check'] = 1;
                        }else{
                            $shopVal['check'] = 0;
                        }
                        $shopVal['list'] = pdo_fetchall("SELECT id as cate_two,`name` FROM " . tablename(PDO_NAME . "category_store") . " WHERE state = 0 AND parentid = {$shopVal['cate_one']} AND enabled = 1 ORDER BY displayorder DESC,id DESC");
                        foreach ($shopVal['list'] as &$shop) {
                            if($cate_two == $shop['cate_two']){
                                $shop['check'] = 1;
                            }else{
                                $shop['check'] = 0;
                            }
                        }
                    }
                }
                $shopList = array_merge($whole , $shopList);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => 'do=WholeCityList' ,
                    'class'  => $shopList ,
                    'orders' => [
                        ['title' => '默认排序' , 'val' => 0] ,
                        ['title' => '创建时间' , 'val' => 1] ,
                        ['title' => '店铺距离' , 'val' => 2] ,
                        ['title' => '推荐设置' , 'val' => 3] ,
                        ['title' => '浏览人气' , 'val' => 4] ,
                    ] ,
                ];
                break;//好店首页
            case 3:
                $whole = [
                    [
                        'id'   => '0' ,
                        'name' => '全部'
                    ]
                ];
                //获取积分商品分类列表
                $classList = pdo_getall(PDO_NAME . "consumption_category" , [
                    'uniacid' => $_W['uniacid'] ,
                    'status'  => 1
                ] , [
                    'id' ,
                    'name'
                ] , '' , ' displayorder DESC,id DESC ' , '');
                $classList = array_merge($whole , $classList);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 0] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => '' ,
                    'class'  => $classList ,
                    'orders' => [
                        ['title' => '创建时间' , 'val' => 1] ,
                        ['title' => '默认设置' , 'val' => 3] ,
                        ['title' => '浏览人气' , 'val' => 4] ,
                    ] ,
                ];
                break;//积分商城
            case 4:
                //获取好店分类列表
                $classList = pdo_fetchall("SELECT id as cate_one,`name` FROM " . tablename(PDO_NAME . "citycard_cates") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 AND parentid = 0  ORDER BY sort DESC,id DESC");
                if (is_array($classList) && count($classList) > 0) {
                    foreach ($classList as $cardKey => &$cardVal) {
                        $cardVal['list'] = pdo_fetchall("SELECT id as cate_two,`name` FROM " . tablename(PDO_NAME . "citycard_cates") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 AND parentid = {$cardVal['cate_one']} ORDER BY sort DESC,id DESC");
                    }
                }
                $classList = array_merge([
                    [
                        'cate_one' => '0' ,
                        'name'     => '全部' ,
                        'list'     => []
                    ]
                ] , $classList);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => 'do=WholeCityList' ,
                    'class'  => $classList ,
                    'orders' => [
                        ['title' => '最新' , 'val' => 1] ,
                        ['title' => '附近' , 'val' => 2] ,
                        ['title' => '点赞榜' , 'val' => 3] ,
                        ['title' => '人气榜' , 'val' => 4] ,
                        ['title' => '收存榜' , 'val' => 5] ,
                    ] ,
                ];
                break;//名片首页
            case 5:
                $whole = [
                    [
                        'cate_one' => '0' ,
                        'name'     => '全部' ,
                        'list'     => []
                    ]
                ];
                //获取好店分类列表
                $shopList = pdo_fetchall("SELECT id as cate_one,`name` FROM " . tablename(PDO_NAME . "category_store") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND state = 0 AND parentid = 0 AND enabled = 1 ORDER BY displayorder DESC,id DESC");
                if (is_array($shopList) && count($shopList) > 0) {
                    foreach ($shopList as $shopKey => &$shopVal) {
                        $shopVal['list'] = pdo_fetchall("SELECT id as cate_two,`name` FROM " . tablename(PDO_NAME . "category_store") . " WHERE state = 0 AND parentid = {$shopVal['cate_one']} AND enabled = 1 ORDER BY displayorder DESC,id DESC");
                    }
                }
                $shopList = array_merge($whole , $shopList);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => 'do=WholeCityList' ,
                    'class'  => $shopList ,
                    'orders' => [
                        ['title' => '附近' , 'val' => 0] ,
                        ['title' => '最新' , 'val' => 1] ,
                        ['title' => '推荐' , 'val' => 2] ,
                        ['title' => '人气' , 'val' => 3] ,
                    ] ,
                ];
                break;//同城配送
            case 6:
                //获取好店分类列表
                $classList = pdo_fetchall("SELECT id as cate_one,`name` FROM " . tablename(PDO_NAME . "yellowpage_cates") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 AND parentid = 0  ORDER BY sort DESC,id DESC");
                if (is_array($classList) && count($classList) > 0) {
                    foreach ($classList as $cardKey => &$cardVal) {
                        $cardVal['list'] = pdo_fetchall("SELECT id as cate_two,`name` FROM " . tablename(PDO_NAME . "yellowpage_cates") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 AND parentid = {$cardVal['cate_one']} ORDER BY sort DESC,id DESC");
                    }
                }
                $classList = array_merge([
                    [
                        'cate_one' => '0' ,
                        'name'     => '全部' ,
                        'list'     => []
                    ]
                ] , $classList);
                //信息拼装
                $data = [
                    'top'    => [
                        ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                        ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                        ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                    ] ,
                    'area'   => 'do=WholeCityList' ,
                    'class'  => $classList ,
                    'orders' => [
                        ['title' => '附近' , 'val' => 1] ,
                        ['title' => '最新' , 'val' => 2] ,
                        ['title' => '人气' , 'val' => 3] ,
                        ['title' => '收藏' , 'val' => 4] ,
                        ['title' => '推荐' , 'val' => 5] ,
                    ] ,
                ];
                break;//黄页114首页
            case 7:
                //信息拼装
                $data = [
                    'top' => [
                        ['title' => '排序','subscript' => 'orders','status' => 1],
                        ['title' => '区域','subscript' => 'nwe_area','status' => 1],
                        ['title' => '职位','subscript' => 'industry','status' => 1],
                        ['title' => '招聘类型','subscript' => 'recruitment_type','status' => 1],
                        ['title' => '工作类型','subscript' => 'job_type','status' => 1],
                        ['title' => '学历要求','subscript' => 'educational_experience','status' => 1],
                        ['title' => '经验要求','subscript' => 'work_experience','status' => 1],
                        ['title' => '薪资待遇','subscript' => 'salary','status' => 1],
                    ],
                    'orders'                 => [
                        ['title' => '推荐排序','val' => 1],
                        ['title' => '浏览量','val' => 2],
                        ['title' => '发布时间','val' => 3],
                    ],
                    'nwe_area'               => 'do=WholeCityList',
                    'industry'               => 'p=recruit&do=industryList',
                    'recruitment_type'       => [
                        ['title' => '个人发布','val' => 1],
                        ['title' => '企业招聘','val' => 2],
                    ],
                    'job_type'               => [
                        ['title' => '全职','val' => 1],
                        ['title' => '兼职','val' => 2],
                    ],
                    'educational_experience' => Recruit::getLabelList(1,'id as val,title'),
                    'work_experience'        => Recruit::getLabelList(3,'id as val,title'),
                    'salary'                 => [
                        ['title' => '3K以下','salary_min' => 0,'salary_max' => 3000],
                        ['title' => '3~5K','salary_min' => 3000,'salary_max' => 5000],
                        ['title' => '5~10K','salary_min' => 5000,'salary_max' => 10000],
                        ['title' => '10~20K','salary_min' => 10000,'salary_max' => 20000],
                        ['title' => '20K以上','salary_min' => 20000,'salary_max' => 99999999],
                    ],
                ];
                break;//求职招聘
            case 8:
                $data = [
                    'top'               => [
                        ['title' => '排序','subscript' => 'orders','status' => 1],
                        ['title' => '位置','subscript' => 'nwe_area','status' => 1],
                        ['title' => '行业','subscript' => 'education','status' => 1],
                        ['title' => '规模','subscript' => 'recruit_scale_id','status' => 1],
                        ['title' => '性质','subscript' => 'recruit_nature_id','status' => 1],
                    ],
                    'orders'            => [
                        ['title' => '创建时间','val' => 1],
                        ['title' => '企业距离','val' => 2],
                        ['title' => '平台推荐','val' => 3],
                        ['title' => '浏览人气','val' => 4],
                    ],
                    'nwe_area'          => 'do=WholeCityList',
                    'industry'          => 'p=recruit&do=industryList',
                    'recruit_scale_id'  => Recruit::getLabelList(4,'id as val,title'),
                    'recruit_nature_id' => Recruit::getLabelList(5,'id as val,title'),
                ];
                break;//企业
            case 9:
                if(Customized::init('university442')){
                    $data['top'] = [
                        ['title' => '排序','subscript' => 'sort','status' => 1],
                        ['title' => '位置','subscript' => 'area_id','status' => 1],
                        ['title' => '性别','subscript' => 'gneder','status' => 1],
                        ['title' => '年纪','subscript' => 'marital_status','status' => 1],
                        ['title' => '专业','subscript' => 'education','status' => 1],
                        ['title' => '社团协会','subscript' => 'registered_residence_type','status' => 1],
                        ['title' => '类型','subscript' => 'get_type','status' => 1],
                    ];
                    $data['sort'] = [
                        ['title' => '推荐排序','val' => 1],
                        ['title' => '浏览量','val' => 2],
                        ['title' => '发布时间','val' => 3],
                    ];
                }else {
                    $data['top'] = [
                        ['title' => '排序','subscript' => 'sort','status' => 1],
                        ['title' => '位置','subscript' => 'area_id','status' => 1],
                        ['title' => '性别','subscript' => 'gneder','status' => 1],
                        ['title' => '婚姻情况','subscript' => 'marital_status','status' => 1],
                        ['title' => '学历','subscript' => 'education','status' => 1],
                        ['title' => '户籍类型','subscript' => 'registered_residence_type','status' => 1],
                        ['title' => '居住情况','subscript' => 'live','status' => 1],
                        ['title' => '出行情况','subscript' => 'travel','status' => 1],
                        ['title' => '类型','subscript' => 'get_type','status' => 1],
                    ];
                    $data['sort'] = [
                        ['title' => '推荐排序','val' => 1],
                        ['title' => '浏览量','val' => 2],
                        ['title' => '发布时间','val' => 3],
                        ['title' => '最近距离','val' => 4],
                    ];

                }
                $data['area_id'] = 'do=WholeCityList';
                $data['gneder']  = [
                    ['title' => '不限','val' => 1],
                    ['title' => '男','val' => 2],
                    ['title' => '女','val' => 3],
                ];

                $data['marital_status'] = [];
                $marital_status = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 1),array('id','title'));
                if(!empty($marital_status)){
                    foreach ($marital_status as $mar){
                        $marinfo = ['val' => $mar['id'],'title' => $mar['title'] ];
                        $data['marital_status'][] = $marinfo;
                    }
                }

                $data['education'] = [];
                $education = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 2),array('id','title'));
                if(!empty($education)){
                    foreach ($education as $edu){
                        $eduinfo = ['val' => $edu['id'],'title' => $edu['title'] ];
                        $data['education'][] = $eduinfo;
                    }
                }

                $data['registered_residence_type'] = [];
                $registered_residence_type = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 3),array('id','title'));
                if(!empty($registered_residence_type)){
                    foreach ($registered_residence_type as $reg){
                        $reginfo = ['val' => $reg['id'],'title' => $reg['title'] ];
                        $data['registered_residence_type'][] = $reginfo;
                    }
                }
                $data['live'] = [];
                $live = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 4),array('id','title'));
                if(!empty($live)){
                    foreach ($live as $liv){
                        $livinfo = ['val' => $liv['id'],'title' => $liv['title'] ];
                        $data['live'][] = $livinfo;
                    }
                }

                $data['travel'] = [];
                $travel = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 5),array('id','title'));
                if(!empty($travel)){
                    foreach ($travel as $tra){
                        $trainfo = ['val' => $tra['id'],'title' => $tra['title'] ];
                        $data['travel'][] = $trainfo;
                    }
                }
                $data['get_type'] = [
                    ['title' => '全部会员','val' => 0],
                    ['title' => '有视频','val' => 1],
                ];
                break;//相亲交友会员筛选
        }
        $this->renderSuccess('选择信息列表' , $data);
    }
    /**
     * Comment: 前端获取图片素材地址前缀
     * Author: wlf
     * Date: 2020/05/12 10:40
     */
    public function doPageGetimgPrefix(){
        global $_W;
        $data['imgPrefix'] = $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/';
        $this->renderSuccess('图片素材前缀' , $data);
    }
    //调试接口，请勿删除
    public function doPageDemo(){
        global $_W , $_GPC;
        #https://citydev.weliam.com.cn/addons/weliam_smartcity/core/common/uniapp.php?i=1&aid=125&do=demo


        //红娘佣金到账通知
        //Dating::handleMatchmakerCommissionInfo(12,100,"测试红娘佣金到账通知");
//        $str = '这里是一句话的内容，这里是一句话的内容，这里是一句话的内容，这里是一句话的内容，这里是一句话的内容这里是一句话的内容，这里是一句话的内容，这里是一句话的内容，';
//        TempModel::subStr($str);


        wl_debug("调试专用接口......");
    }





    /******** 城市管理/地区选择 ***************************************************************************************/
    /**
     * Comment: 切换城市列表
     * Author: Hexin
     */
    public function doPageCityList()
    {
        global $_W , $_GPC;
        #1、参数获取
        $keyword = trim($_GPC['keyword']);
        #2、获取定位类型
        $set      = Setting::wlsetting_read("areaset");
        $location = $set['location'] ? $set['location'] : 0;//0=城市定位  1=精确定位
        if ($location == 0) {
            //城市定位数据
            $citylists = Cache::getCache('urbanLocationData' , 'citylist');
            //            if (!$citylists || !empty($keyword)) {
            if (1 == 1) {
                //查询条件生成
                $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.status = 1 ";
                if (!empty($keyword)) $where .= " AND b.name LIKE '%{$keyword}%' ";
                //获取拥有代理商的地区列表
                $list = pdo_fetchall("SELECT b.*,a.aid FROM " . tablename(PDO_NAME . "oparea") . " as a RIGHT JOIN " . tablename(PDO_NAME . "area") . " as b ON a.areaid = b.id " . $where);
                //获取热门地区
                $hotcityids = (new AgentareaTable())->selectFields('areaid')->searchWithUniacid($_W['uniacid'])->searchWithHot()->searchWithOpen()->getAreaList();
                $hotcityids = array_column($hotcityids , 'areaid');
                //重新定义数组信息
                if (count($list) > 0) {
                    foreach ($list as $city) {
                        $agentinfo = pdo_get('wlmerchant_agentusers' , ['id' => $city['aid']] , ['status' , 'endtime']);
                        if (($agentinfo['status'] == 1 && $agentinfo['endtime'] > time()) || empty($city['aid'])) {
                            $city['only_id'] = md5(uniqid(microtime(true) , true));
                            if (!empty($keyword)) {
                                $newcitys[] = $city;
                            }
                            else {
                                $newcitys[$city['initial']][] = $city;
                            }
                            if (!empty($hotcityids) && in_array($city['id'] , $hotcityids)) {
                                $hotcitys[] = $city;
                            }
                        }
                    }
                    ksort($newcitys);
                }
                //拼装数据 并且写入缓存
                $citylists = ['hotcity' => $hotcitys , 'citylist' => $newcitys];
                if (!$keyword) {
                    //非搜索时才会进行缓存
                    Cache::setCache('urbanLocationData' , 'citylist' , $citylists);
                }
            }
        }
        else {
            //精确定位数据
            $areatable = new AreaTable();
            if (!empty($keyword)) {
                //搜索地区时
                $citylists['citylist'] = $areatable->searchWithLevel(2)->searchWithKeyword($keyword)->searchWithOpen()->searchWithUniacid($_W['uniacid'])->selectFields([
                    'initial' ,
                    'id' ,
                    'name'
                ])->getAreaList();
            }
            else {
                $citylists = Cache::getCache('area' , 'citylist');
                //获取所有市级地区
                $citys = $areatable->searchWithLevel(2)->searchWithOpen()->searchWithUniacid($_W['uniacid'])->selectFields([
                    'initial' ,
                    'id' ,
                    'name'
                ])->getAreaList();
                //获取热门地区
                $hotcityids = (new AgentareaTable())->selectFields('areaid')->searchWithUniacid($_W['uniacid'])->searchWithHot()->searchWithOpen()->getAreaList();
                $hotcityids = array_column($hotcityids , 'areaid');
                $hotcitys   = $newcitys = [];
                foreach ($citys as $city) {
                    $city['only_id']              = md5(uniqid(microtime(true) , true));
                    $newcitys[$city['initial']][] = $city;
                }
                if (!empty($hotcityids)) {
                    foreach ($hotcityids as &$hot) {
                        $hot = pdo_get('wlmerchant_area' , ['id' => $hot] , ['initial' , 'id' , 'name']);
                    }
                }
                $hotcitys = $hotcityids;
                ksort($newcitys);
                //写入缓存
                $citylists = ['hotcity' => $hotcitys , 'citylist' => $newcitys];
                Cache::setCache('area' , 'citylist' , $citylists);
            }
        }
        $citylists['location'] = $location;//0=城市定位  1=精确定位

        $this->renderSuccess('获取地址信息' , $citylists);
    }
    /**
     * Comment: 根据城市ID或经纬度获取当前位置信息
     * Author: Hexin
     */
    public function doPageCityLocation()
    {
        global $_W , $_GPC;
        if (!empty($_GPC['citycode'])) {
            $areatable = new AreaTable();
            $areatable->selectFields(['lat' , 'lng']);
            $cityinfo = $areatable->getAreaById(intval($_GPC['citycode']));
        }
        $lat = $cityinfo['lat'] ? $cityinfo['lat'] : trim($_GPC['lat']);
        $lng = $cityinfo['lng'] ? $cityinfo['lng'] : trim($_GPC['lng']);
        if (empty($lat) || empty($lng)) {
            $area = MapService::guide_ip($_W['clientip']);
            if (!is_error($area)) {
                $lat = $area['result']['location']['lat'];
                $lng = $area['result']['location']['lng'];
            }
        }
        //获取当前城市
        $location                                 = MapService::guide_gcoder($lat . ',' . $lng , 1);
        $location['result']['ad_info']['only_id'] = md5(uniqid(microtime(true) , true));
        if (is_error($location)) {
            $this->renderError($location['message']);
        }
        //城市id的再处理  当前区域
        $agentuser = pdo_getcolumn('wlmerchant_oparea' , [
            'uniacid' => $_W['uniacid'] ,
            'areaid'  => $location['result']['ad_info']['adcode'] ,
            'status'  => 1
        ] , 'id');
        if (empty($agentuser) && !empty($_GPC['areaid'])) {
            $location['result']['ad_info']['adcode'] = $_GPC['areaid'];
        }
        //城市id的再处理  下级区域
        if (is_array($location['result']['pois']) && count($location['result']['pois']) > 0) {
            foreach ($location['result']['pois'] as &$poi) {
                $flag = pdo_getcolumn(PDO_NAME . "oparea" , [
                    'areaid'  => $poi['ad_info']['adcode'] ,
                    'status'  => 1 ,
                    'uniacid' => $_W['uniacid']
                ] , 'aid');
                if (empty($flag)) {
                    $poi['ad_info']['adcode'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $poi['ad_info']['adcode']] , 'pid');
                }
                $poi['ad_info']['only_id'] = md5(uniqid(microtime(true) , true));
            }
        }
        $location['result']['ad_info']['location'] = $location['result']['location'];
        $this->renderSuccess('success' , $location['result']);
    }
    /**
     * Comment: 根据城市搜索地点
     * Author: Hexin
     */
    public function doPageCitySearch()
    {
        global $_GPC;
        $keyword   = empty($_GPC['keyword']) ? $this->renderError("请填写搜索内容") : trim($_GPC['keyword']);
        $city_name = empty($_GPC['city_name']) ? $this->renderError("请指定地区名称") : trim($_GPC['city_name']);
        //获取当前城市
        $location = MapService::guide_search($keyword , "region(" . urlencode($city_name) . ",0)");
        if (is_error($location)) {
            $this->renderError($location['message']);
        }
        $this->renderSuccess('success' , $location['data']);
    }
    /**
     * Comment: 获取微信jssdk
     * Author: Hexin
     */
    public function doPageGetJssdk()
    {
        global $_W , $_GPC;
        $unisetting = uni_setting_load();
        if (!empty($unisetting['jsauth_acid'])) {
            $jsauth_acid = $unisetting['jsauth_acid'];
        } else {
            if ($_W['account']['level'] < ACCOUNT_SUBSCRIPTION_VERIFY && !empty($unisetting['oauth']['account'])) {
                $jsauth_acid = $unisetting['oauth']['account'];
            } else {
                $jsauth_acid = $_W['acid'];
            }
        }

        $url         = !empty($_GPC['sign_url']) ? urldecode($_GPC['sign_url']) : $_W['siteroot'] . "app/index.php?i=" . $_W['uniacid'] . "&c=entry&m=" . MODULE_NAME . "&p=area&ac=region&do=index";
        $account_api = WeAccount::create($jsauth_acid);
        if (!is_error($account_api)) {
            $jssdkconfig = $account_api->getJssdkConfig($url);
        }else{
            $account_api = WeAccount::create($_W['uniacid']);
            if (!is_error($account_api)) {
                $jssdkconfig = $account_api->getJssdkConfig($url);
            }
        }
        $this->renderSuccess('success' , $jssdkconfig);
    }
    /**
     * Comment: 获取区域列表
     * Author: zzw
     * Date: 2019/10/18 11:36
     */
    public function doPageWholeCityList()
    {
        global $_W , $_GPC;
        #1、参数获取
        $cityId = $_GPC['city_id'] OR $this->renderError('缺少参数：city_id');
        #2、区域列表获取
//        $id             = pdo_fetch("SELECT
//        CASE WHEN pid > 0 THEN (SELECT id FROM " . tablename(PDO_NAME . 'area') . " as b WHERE b.id = a.pid)
//             ELSE a.id
//        END as id
//        FROM " . tablename(PDO_NAME . "area") . " as a WHERE a.id = {$cityId} ");
        $id = $cityId;
        $lowlist = pdo_getcolumn(PDO_NAME.'area',array('pid'=>$id),'id');
        if(empty($lowlist)){
            $id = pdo_getcolumn(PDO_NAME.'area',array('id'=>$id),'pid');
        }
        $list           = pdo_get(PDO_NAME . "area" , ['id' => $id] , ['id' , 'name']);
        $list['select'] = 1;
        $list['list']   = pdo_fetchall("SELECT id,name FROM ".tablename(PDO_NAME."area") ." WHERE (displayorder = {$_W['uniacid']} OR displayorder = 0 )  AND pid = ".$list['id']);
        foreach ($list['list'] as $key => &$val) {
            $val['select'] = 0;
            //判断是否选中
            if ($val['id'] == $cityId) $val['select'] = 1;
            //获取下级信息
            $val['list'] = pdo_getall(PDO_NAME . "area" , ['pid' => $val['id']] , ['id' , 'name']);
            foreach ($val['list'] as $k => &$v) {
                $v['select'] = 0;
                //判断是否选中
                if ($v['id'] == $cityId) {
                    $v['select']   = 1;
                    $val['select'] = 1;
                }
            }
        }
        array_unshift($list['list'],['id'=>$id,'name'=>'全部','select'=>0,'list'=>[]]);
        $this->renderSuccess('区域列表' , $list);
    }
    /**
     * Comment: 通过经纬度/IP/地区id获取代理id
     * Author: WLF
     * Date: 2019/11/06 13:46
     */
    public function doPageGetAid()
    {
        global $_W , $_GPC;
        $settings = Setting::wlsetting_read('areaset');
        $data     = [];
        $lat      = $_GPC['lat'];
        $lng      = $_GPC['lng'];
        $areaid   = $_GPC['areaid'];
        //处理特殊
        if($areaid == '411603'){
            $areaid = '411626';
        }
        $cityname = $_GPC['cityname'];
        $aid = $_GPC['aid'];
        if(!empty($aid) && $aid != 'undefined' && empty($areaid) && empty($cityname)){
            $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'aid'=>$aid),'areaid');
        }
        //优先使用前端传过来的areaid 没有则通过经纬度获取
        if(empty($areaid) && empty($settings['location'])){
            $area = pdo_get(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] ,'aid'  => $_W['aid'],'status'  => 1] , ['areaid']);
            $areaid = $area['areaid'];
        }
        if (!empty($lat) && !empty($lng) && empty($areaid)) {
            $area = MapService::guide_gcoder($lat . ',' . $lng , 0);
            if (!is_error($area)) {
                $areaid = $area['result']['ad_info']['adcode'];
                $defaultareaname = pdo_getcolumn(PDO_NAME.'area',array('id'=>$areaid),'name');
            }
        }
        //都不行通过IP获取
        if (empty($areaid)) {
            $area = MapService::guide_ip($_W['clientip']);
            if (!is_error($area)) {
                $areaid = $area['result']['ad_info']['adcode'];
            }
        }
        $area = pdo_get(PDO_NAME . 'oparea' , [
            'uniacid' => $_W['uniacid'] ,
            'areaid'  => $areaid ,
            'status'  => 1
        ] , ['aid' , 'id' , 'areaid']);
        //如果当前地区不存在代理 获取一下城市代理
        if (empty($area['id'])) {
            $pinfo            = pdo_get(PDO_NAME . 'area' , ['id' => $areaid] , ['pid' , 'level' , 'name']);
            $data['areaname'] = $pinfo['name'];
            $area             = pdo_get(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $pinfo['pid'] ,
                'status'  => 1
            ] , ['aid' , 'id' , 'areaid']);
            if ($pinfo['level'] == 3 && empty($area['id'])) {  //查询一级地区
                $pinfo = pdo_get(PDO_NAME . 'area' , ['id' => $pinfo['pid']] , ['pid']);
                $area  = pdo_get(PDO_NAME . 'oparea' , [
                    'uniacid' => $_W['uniacid'] ,
                    'areaid'  => $pinfo['pid'] ,
                    'status'  => 1
                ] , ['aid' , 'id' , 'areaid']);
            }
        }
        if (empty($area['id'])) { //没有查询到相关地区
            if (empty($settings['location'])) {  //城市定位
//                $citynum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_oparea') . " WHERE uniacid = {$_W['uniacid']} AND status = 1");
                    $settings['defaultAid'] = $settings['defaultAid']> 0 ? $settings['defaultAid'] : 0;
                    $aid = pdo_get(PDO_NAME . 'oparea' , ['aid' => $settings['defaultAid'],'uniacid' => $_W['uniacid'] ] , [
                        'areaid' ,
                        'aid'
                    ]);
                    $data['aid']      = $aid['aid'];
                    $data['areaname'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $aid['areaid']] , 'name');

//                    $aid = pdo_get(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'status' => 1] , [
//                        'areaid' ,
//                        'aid'
//                    ]);
//                    $data['aid']      = $aid['aid'];
//                    $data['areaname'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $aid['areaid']] , 'name');
//
//                else {
//                    $data['status'] = 3;
//                    $this->renderSuccess('选择地区' , $data);
//                }
            }
            else {  //精准定位
                if ($settings['datashow'] == 1) {
                    $data['status']  = 1;
                    $data['message'] = '未开通地区，显示加盟申请';
                    $data['name']    = $_W['wlsetting']['base']['name'];
                    $data['phone']   = $_W['wlsetting']['base']['phone'];
                }
                else if ($settings['datashow'] == 2) {
                    $data['status']  = 2;
                    $data['message'] = '未开通地区，显示暂未开通';
                    $data['name']    = $_W['wlsetting']['base']['name'];
                    $data['phone']   = $_W['wlsetting']['base']['phone'];
                }else if($settings['datashow'] == 3){
                    $data['aid']      = $settings['defaultAid'];
                    $data['areaid']   = pdo_getcolumn(PDO_NAME . 'oparea' , [
                        'uniacid' => $_W['uniacid'] ,
                        'aid'     => $settings['defaultAid']
                    ] , 'areaid');
                    $data['areaname'] = !empty($defaultareaname) ? $defaultareaname : pdo_getcolumn(PDO_NAME . 'area' , ['id' => $data['areaid']] , 'name');
                }
                else {
                    $data['aid']      = 0;
                    $data['areaid']   = pdo_getcolumn(PDO_NAME . 'oparea' , [
                        'uniacid' => $_W['uniacid'] ,
                        'aid'     => 0
                    ] , 'areaid');
                    $data['areaname'] = !empty($defaultareaname) ? $defaultareaname : pdo_getcolumn(PDO_NAME . 'area' , ['id' => $data['areaid']] , 'name');
                }
            }
        }
        else {
            $data['aid']      = $area['aid'];
            $data['areaid']   = $area['areaid'];
            $data['areaname'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $data['areaid']] , 'name');
        }
        if (!empty($cityname)) {
            $data['areaname'] = $cityname;
        }
        $data['lat'] = $lat;
        $data['lng'] = $lng;
        $this->renderSuccess('代理id' , $data);
    }
    /**
     * Comment: 获取当前地址换区提示文本
     * Author: WLF
     * Date: 2020/05/15 11:25
     */
    public function doPageAidtip(){
        global $_W , $_GPC;
        $lat  = trim($_GPC['lat']);
        $lng  = trim($_GPC['lng']);
        $aid  = trim($_GPC['aid']);
        if(empty($lat) || empty($lng)){
            $this->renderSuccess('无提示',['status' => 2]);
        }
        $area = MapService::guide_gcoder($lat.','.$lng,0);
        if (!is_error($area)) {
            $areaid = $area['result']['ad_info']['adcode'];
        }else{
            $this->renderError($area['message']);
        }
        $area = pdo_get(PDO_NAME . 'oparea' , [
            'uniacid' => $_W['uniacid'] ,
            'areaid'  => $areaid ,
            'status'  => 1
        ] , ['aid' , 'id' , 'areaid']);
        //如果当前地区不存在代理 获取一下城市代理
        if (empty($area['id'])) {
            $pinfo            = pdo_get(PDO_NAME . 'area' , ['id' => $areaid] , ['pid' , 'level' , 'name']);
            $data['areaname'] = $pinfo['name'];
            $area             = pdo_get(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $pinfo['pid'] ,
                'status'  => 1
            ] , ['aid' , 'id' , 'areaid']);
            if ($pinfo['level'] == 3 && empty($area['id'])) {  //查询一级地区
                $pinfo = pdo_get(PDO_NAME . 'area' , ['id' => $pinfo['pid']] , ['pid']);
                $area  = pdo_get(PDO_NAME . 'oparea' , [
                    'uniacid' => $_W['uniacid'] ,
                    'areaid'  => $pinfo['pid'] ,
                    'status'  => 1
                ] , ['aid' , 'id' , 'areaid']);
            }
        }
        if(empty($area['id']) || $area['aid'] == $aid){
            $this->renderSuccess('无提示',['status' => 2]);
        }else{
            $data['status'] = 1;
            $data['memberCityName'] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$area['areaid']),'name');
            $data['memberCityAid'] = $area['aid'];
            $data['memberCityAreaid'] = $area['areaid'];
            $this->renderSuccess('提示切换',$data);
        }

    }
    /**
     * Comment: 获取当前代理商所在的区域(仅获取当前区域级以下的区域)
     * Author: zzw
     * Date: 2021/4/15 10:11
     */
    public function doPageGetNowCity(){
        global $_W,$_GPC;
        //获取当前代理商区域信息
        $agentAreaId = pdo_getcolumn(PDO_NAME."oparea",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],'areaid');
        $agentArea = pdo_get(PDO_NAME."area",['id'=>$agentAreaId],['id','pid','level']);
        if($agentArea['level'] == 4) $agentArea = pdo_get(PDO_NAME."area",['id'=>$agentArea['pid']],['id','pid','level']);//当前代理为四级时获取三级的信息
        //根据等级获取内容
        switch ($agentArea['level']){
            case 1:
                $list = Area::getAreaList(['id'=>$agentArea['id']]);//获取当前省信息
                foreach($list as &$listVal){
                    $listVal['area'] = Area::getAreaList(['pid'=>$agentArea['id']]);//获取下级市信息
                    foreach($listVal['area'] as &$areaVal){
                        $areaVal['dist'] = Area::getAreaList(['pid'=>$areaVal['id']]);//获取下级区县信息
                    }
                }
                break;//当前区域为省
            case 2:
                $list = Area::getAreaList(['id'=>$agentArea['pid']]);//获取上级省信息
                foreach($list as &$listVal){
                    $listVal['area'] = Area::getAreaList(['id'=>$agentArea['id']]);//获取当前市信息
                    foreach($listVal['area'] as &$areaVal){
                        $areaVal['dist'] = Area::getAreaList(['pid'=>$areaVal['id']]);//获取下级区县信息
                    }
                }
                break;//当前区域为市
            case 3:
                $areaPid = pdo_getcolumn(PDO_NAME."area",['id'=>$agentArea['pid']],'pid');
                $list = Area::getAreaList(['id'=>$areaPid]);//获取上级省信息
                foreach($list as &$listVal){
                    $listVal['area'] = Area::getAreaList(['id'=>$agentArea['pid']]);//获取上级市信息
                    foreach($listVal['area'] as &$areaVal){
                        $areaVal['dist'] = Area::getAreaList(['id'=>$agentArea['id']]);//获取下级区县信息
                    }
                }
                break;//当前区域为区、县
        }

        $this->renderSuccess('当前区域相关区域信息',$list);
    }




    /******** 装修功能管理/装修功能对应操作 ****************************************************************************/
    /**
     * Comment: 获取平台菜单信息
     * Author: zzw
     * Date: 2019/7/25 16:17
     */
    public function doPageBottomMenu()
    {
        global $_W , $_GPC;
        #1、获取设置信息
        $type = intval($_GPC['type']) ?  intval($_GPC['type']) : 2;
        $id   = $_GPC['id'] ? : 0;
        //页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;
        //8=好店首页;9=掌上信息;10=积分商城;11=积分签到;12=头条首页;13=名片首页;14=活动首页;15=招聘首页;
        //16=相亲交友;17=一卡通首页默认菜单;18=家政首页默认菜单;20=房产默认菜单;21=房产列表菜单
        if(Customized::init('personal559') > 0){
            $this->renderSuccess('无菜单' , []);
        }
        $set = Setting::agentsetting_read("diypageset");
        switch ($type) {
            case 1:
                if ($id > 0) {
                    #2、页面配置信息获取
                    $pageset  = Diy::getPage($id , false);
                    $pageInfo = $pageset['data']['page'];//本页面标题信息
                    if($pageInfo['diymenu'] > 0) $menudata = Diy::getMenu($pageInfo['diymenu']);//自定义菜单
                    else if($pageInfo['diymenu'] == -1) $menudata = DiyMenu::defaultBottomMenu();//默认菜单
                }

                $menudata = is_array($menudata) ? $menudata : [];
                break;//自定义页面默认菜单
            case 2:
                if ($set['menu_index'] > 0) $menudata = Diy::getMenu($set['menu_index']);
                else  $menudata = DiyMenu::defaultBottomMenu();
                break;//商城首页默认菜单
            case 3:
                if ($set['menu_rush'] > 0) $menudata = Diy::getMenu($set['menu_rush']);
                else  $menudata = DiyMenu::defaultRushMenu();
                break;//抢购首页默认菜单
            case 4:
                if ($set['menu_groupon'] > 0) $menudata = Diy::getMenu($set['menu_groupon']);
                else  $menudata = DiyMenu::defaultGroupMenu();
                break;//团购首页默认菜单
            case 5:
                if ($set['menu_wlcoupon'] > 0) $menudata = Diy::getMenu($set['menu_wlcoupon']);
                else  $menudata = DiyMenu::defaultCouponMenu();
                break;//卡券首页默认菜单
            case 6:
                if ($set['menu_wlfightgroup'] > 0) $menudata = Diy::getMenu($set['menu_wlfightgroup']);
                else  $menudata = DiyMenu::defaultFightMenu();
                break;//拼团首页默认菜单
            case 7:
                if ($set['menu_bargain'] > 0) $menudata = Diy::getMenu($set['menu_bargain']);
                else  $menudata = DiyMenu::defaultBargainMenu();
                break;//砍价首页默认菜单
            case 8:
                if ($set['menu_storepage'] > 0) $menudata = Diy::getMenu($set['menu_storepage']);
                else  $menudata = DiyMenu::defaultBottomMenu();
                break;//自定义页面默认菜单
            case 9:
            case 91:
                if ($set['menu_pocket'] > 0) $menudata = Diy::getMenu($set['menu_pocket']);
                else  $menudata = DiyMenu::defaultPocketMenu();
                break;//掌上信息默认菜单
            case 10:
                if ($set['menu_consumption'] > 0) $menudata = Diy::getMenu($set['menu_consumption']);
                else  $menudata = DiyMenu::defaultConsumptionMenu();
                break;//积分商城默认菜单
            case 11:
                if ($set['menu_wlsign'] > 0) $menudata = Diy::getMenu($set['menu_wlsign']);
                else  $menudata = DiyMenu::defaultSignMenu();
                break;//积分签到默认菜单
            case 12:
                if ($set['menu_headline'] > 0) $menudata = Diy::getMenu($set['menu_headline']);
                else  $menudata = DiyMenu::defaultHeadlineMenu();
                break;//头条首页默认菜单
            case 13:
                if ($set['menu_card'] > 0) $menudata = Diy::getMenu($set['menu_card']);
                else $menudata = DiyMenu::defaultCardMenu();
                break;//名片首页默认菜单
            case 14:
                if ($set['menu_activity'] > 0) $menudata = Diy::getMenu($set['menu_activity']);
                else $menudata = DiyMenu::defaultBottomMenu();
                break;//一卡通首页默认菜单
            case 15:
                if ($set['menu_recruit'] > 0) $menudata = Diy::getMenu($set['menu_recruit']);
                else $menudata = DiyMenu::defaultRecruitMenu();
                break;//求职招聘菜单信息
            case 16:
                if ($set['menu_dating'] > 0) $menudata = Diy::getMenu($set['menu_dating']);
                else $menudata = DiyMenu::defaultDatingMenu();
                break;//相亲交友菜单信息
            case 17:
                if ($set['menu_half'] > 0) $menudata = Diy::getMenu($set['menu_half']);
                else $menudata = DiyMenu::defaultBottomMenu();
                break;//一卡通首页默认菜单
            case 18:
                if ($set['menu_housekeep'] > 0) $menudata = Diy::getMenu($set['menu_housekeep']);
                else $menudata = DiyMenu::defaultHouseKeepMenu();
                break;//家政首页默认菜单
            case 19:
                if ($set['menu_yellow'] > 0) $menudata = Diy::getMenu($set['menu_yellow']);
                else $menudata = DiyMenu::defaultYellowMenu();
                break;//家政首页默认菜单
            case 20:
                if ($set['menu_house'] > 0) $menudata = Diy::getMenu($set['menu_house']);
                else $menudata = DiyMenu::defaultHouseMenu();
                break;//房产默认菜单
            case 21:
                if ($set['menu_house_list'] > 0) $menudata = Diy::getMenu($set['menu_house_list']);
                else $menudata = DiyMenu::defaultHouseListMenu();
                break;//房产列表菜单
            case 22:
                if ($set['menu_house_list'] > 0) $menudata = Diy::getMenu($set['menu_release_house']);
                else $menudata = DiyMenu::defaultReleaseHouseMenu();
                break;//发布房产菜单
            case 23:
                if ($set['menu_hotel_index'] > 0) $menudata = Diy::getMenu($set['menu_hotel_index']);
                else $menudata = DiyMenu::defaultHotelMenu();
                break;//酒店首页菜单
        }
        if($type == 91){
            foreach($menudata['data']['data'] as &$in){
                if($in['page_path'] == 'pages/mainPages/sendInformation/sendInformation'){
                    $in['linkurl'] = h5_url('pages/subPages2/sendInform/sendInform');
                    $in['page_path'] = 'pages/subPages2/sendInform/sendInform';
                }
            }
        }
        $this->renderSuccess('平台菜单信息' , $menudata);
    }
    /**
     * Comment: 获取自定义装修页面配置信息
     * Author: zzw
     */
    public function doPageHomePage(){
        global $_W , $_GPC;
        #1、信息获取
        //页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页;
        //13=名片首页;14=活动首页;15=招聘首页;16=相亲首页;18=家政首页;20=房产首页
        $type      = $_GPC['type'] ? $_GPC['type'] : 2;
        $page_id   = $_GPC['page_id'] ? $_GPC['page_id'] : 0;//页面的id
        $_W['aid'] = $_GPC['aid'] ? : 0;
        $settings  = Setting::agentsetting_read('diypageset');//装修设置信息
        $titleList = [
            1  => '自定义页面' ,
            2  => '平台首页' ,
            3  => '抢购首页' ,
            4  => '团购首页' ,
            5  => '卡券首页' ,
            6  => '拼团首页' ,
            7  => '砍价首页' ,
            8  => '好店首页' ,
            13 => '名片首页' ,
            14 => '活动首页' ,
            15 => '招聘首页' ,
            16 => '相亲首页' ,
            18 => '家政首页' ,
            20 => '房产首页' ,
            21 => '表单页面'
        ];
        //兼容自定义选择页面
        $oldType = $type;
        if($page_id > 0 && $type != 21) $type = 1;
        #2、根据type获取不同页面的配置信息
        switch ($type) {
            case 1:
                if (!$page_id) $this->renderError('缺少参数：页面id');
                #2、页面配置信息获取
                $pageset = Diy::getPage($page_id , true);
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                $advId    = $pageInfo['diyadv'];//广告id
                //$menuId   = $pageInfo['diymenu'];//菜单id
                break;//自定义页面
            case 2:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_index'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                else $pageset = DiyPage::getHomePageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_index'];//广告id
                //$menuId   = $settings['menu_index'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//商城首页
            case 3:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_rush'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getRushPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_rush'];//广告id
                //$menuId   = $settings['menu_rush'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//抢购首页
            case 4:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_groupon'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getGroupPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_groupon'];//广告id
                //$menuId   = $settings['menu_groupon'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//团购首页
            case 5:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_wlcoupon'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getCouponPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_wlcoupon'];//广告id
                //$menuId   = $settings['menu_wlcoupon'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//卡券首页
            case 6:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_wlfightgroup'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getFightPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_wlfightgroup'];//广告id
                //$menuId   = $settings['menu_wlfightgroup'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//拼团首页
            case 7:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_bargain'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getBargainPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_bargain'];//广告id
                //$menuId   = $settings['menu_bargain'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//砍价首页
            case 8:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_shop'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getShopPageDefaultInfo();
                //其他信息获取
                //$menuId   = $settings['menu_index'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//好店首页
            case 13:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_card'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getCardPageDefaultInfo();
                //其他信息获取
                //$menuId   = $settings['menu_card'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//名片首页
            case 14:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_activity'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getActivityPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_activity'];//广告id
                //$menuId   = $settings['menu_activity'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//活动首页
            case 15:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_recruit'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getRecruitPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_recruit'];//广告id
                //$menuId   = $settings['menu_recruit'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//招聘首页
            case 16:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_dating'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getDatingPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_dating'];//广告id
                //$menuId   = $settings['menu_dating'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//相亲首页
            case 18:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_housekeep'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getHouseKeepPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_housekeep'];//广告id
                //$menuId   = $settings['menu_housekeep'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//家政首页
            case 20:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_house'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getHousePageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_house'];//广告id
                //$menuId   = $settings['menu_house'];//菜单id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//房产首页
            case 21:
                //表单页面
                
				$redid = $_GPC['redid'];
                $pageset = Diy::getPage($page_id,true,$sid);
				
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//表单页面
        }
        $type = $oldType;
        #3、获取页面标题信息
        $page['music']             = $pageInfo['bgm_music'] ? tomedia($pageInfo['bgm_music']) : '';
        $page['title']             = $pageInfo['title'] ? : $_W['wlsetting']['base']['name'];
        $page['background']        = $pageInfo['background'] ? : '';
        $page['share_title']       = $pageInfo['share_title'] ? : '';
        $page['share_description'] = $pageInfo['share_description'] ? : '';
        $page['share_image']       = tomedia($pageInfo['share_image']) ? : '';
        if($type == 2){
            $page['copytext'] = unserialize($_W['wlsetting']['base']['copytext']);
            $page['copyurl'] = unserialize($_W['wlsetting']['base']['copyurl']);
        }
        //获取图片设置信息
        $page['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        $page['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;
        #4、根据id获取页面菜单信息、广告信息
        if ($advId > 0) $advdata = Diy::BeOverdue($advId , false)['data'];//广告配置信息获取
        #5、信息拼装
        $data['page'] = $page ? $page : [];//本页面配置信息
        $data['adv'] = $advdata ? $advdata : [];//广告配置信息
        //组件配置信息
        if (is_array($pageset['data']['items'])) {
            foreach ($pageset['data']['items'] as $key => &$val) {
                if ($val) $data['item'][$key] = $val;
            }
        }else {
            $data['item'] = [];
        }
        if(p('redpack')){
            $data['page']['redpackflag'] = 1;
        }

        $this->renderSuccess($titleList[$type] . '配置信息' , $data);
    }
    /**
     * Comment: 获取装修页面基本配置信息
     * Author: zzw
     */
    public function doPageNewHomePage(){
        global $_W , $_GPC;
        //信息获取
        $type      = $_GPC['type'] ? $_GPC['type'] : 2;//页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页;13=名片首页;14=活动首页;15=招聘首页
        $page_id   = $_GPC['page_id'] ? $_GPC['page_id'] : 0;//页面的id
        $_W['aid'] = $_GPC['aid'] ? : 0;
        if($type == 2 &&  Customized::init('personal559') > 0){
            $this->renderError('去个人中心',['toperson'=>1]);
        }
        //兼容自定义选择页面
        if ($page_id > 0 && $type != 1) $type = 1;
        if (!$page_id && $type == 1) $this->renderError('缺少参数：页面id');
        //根据type获取不同页面的配置信息
        [$advId,$pageInfo,$pageset] = Diy::getPageParams($type,$page_id);
        //处理组件信息
        $items = $pageset['data']['items'];
        $items = is_array($items) ? array_keys($items) : [];
        //获取页面标题信息
        $page['music']             = $pageInfo['bgm_music'] ? tomedia($pageInfo['bgm_music']) : '';
        $page['title']             = $pageInfo['title'] ? : $_W['wlsetting']['base']['name'];
        $page['background']        = $pageInfo['background'] ? : '';
        $page['share_title']       = $pageInfo['share_title'] ? : '';
        $page['share_description'] = $pageInfo['share_description'] ? : '';
        $page['share_image']       = tomedia($pageInfo['share_image']) ? : '';
        if($type == 2){
            $page['copytext'] = unserialize($_W['wlsetting']['base']['copytext']);
            $page['copyurl'] = unserialize($_W['wlsetting']['base']['copyurl']);
        }
        //获取图片设置信息
        $page['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        $page['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;
        #4、根据id获取页面菜单信息、广告信息
        if ($advId > 0) $advdata = Diy::BeOverdue($advId , false)['data'];//广告配置信息获取
        #5、信息拼装
        $data['page'] = $page ? $page : [];//本页面配置信息
        $data['adv']  = $advdata ? $advdata : [];//广告配置信息
        $data['item'] = $items;//组件配置信息
        //判断是否存在红包插件
        if(p('redpack')) $data['page']['redpackflag'] = 1;

        $this->renderSuccess( '装修页面配置信息' , $data);
    }
    /**
     * Comment: 获取某个组件的配置信息
     * Author: zzw
     */
    public function doPageGetItemParams(){
        global $_W , $_GPC;
        //信息获取
        $type      = $_GPC['type'] ? $_GPC['type'] : 2;//页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页;13=名片首页;14=活动首页
        $page_id   = $_GPC['page_id'] ? $_GPC['page_id'] : 0;//页面的id
        $itemKey   = $_GPC['item_key'] OR $this->renderError('不存在的组件!');//组件下标名称
        $_W['aid'] = $_GPC['aid'] ? : 0;
        //兼容自定义选择页面
        if ($page_id > 0 && $type != 1) $type = 1;
        if (!$page_id && $type == 1) $this->renderError('缺少参数：页面id');
        //根据type获取不同页面的配置信息
        [$advId,$pageInfo,$pageset] = Diy::getPageParams($type,$page_id);
        $items = $pageset['data']['items'];
        $info = Diy::handlePageItem($items[$itemKey]);
        $this->renderSuccess( '组件配置信息' , $info);
    }
    /**
     * Comment: 顶部关注接口
     * Author: wlf
     */
    public function doPageTopFollow(){
        global $_W , $_GPC;
        $url = $_GPC['url'];
        $openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'openid');
        if(!empty($openid)){
            pdo_insert('wlmerchant_halfcard_qrscan', array('uniacid' => $_W['uniacid'],'type' => 'top' ,'openid' => $openid, 'scantime' => time(), 'url' => $url));
        }
        $this->renderSuccess('OK');
    }

}