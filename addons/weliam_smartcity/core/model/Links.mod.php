<?php
defined('IN_IA') or exit('Access Denied');

class Links {
    /**
     * Comment: 获取公共链接基本链接信息
     * Author: zzw
     * Date: 2019/9/6 9:50
     * @param string $state
     * @return array
     */
    public static function getLinks($state = '*') {
        global $_W;
        #1、系统链接 - 基础链接
        $system = [
            'basic' => [
                'name' => '基本链接',
                'list' => [
                    ['name' => '首页入口', 'url' => h5_url('pages/mainPages/index/index'), 'page_path' => 'pages/mainPages/index/index'],
                    ['name' => '好店入口', 'url' => h5_url('pages/mainPages/index/diypage?type=8'), 'page_path' => 'pages/mainPages/index/diypage?type=8'],
                    ['name' => '一卡通首页', 'url' => h5_url('pages/mainPages/memberCard/memberCard'), 'page_path' => 'pages/mainPages/memberCard/memberCard'],
                    ['name' => '商户入驻', 'url' => h5_url('pages/mainPages/Settled/Settled'), 'page_path' => 'pages/mainPages/Settled/Settled'],
                    ['name' => '商户中心', 'url' => h5_url('pages/subPages/merchant/merchantChangeShop/merchantChangeShop'), 'page_path' => 'pages/subPages/merchant/merchantChangeShop/merchantChangeShop'],
                    ['name' => '个人中心', 'url' => h5_url('pages/mainPages/userCenter/userCenter'), 'page_path' => 'pages/mainPages/userCenter/userCenter'],
                    ['name' => '我的订单', 'url' => h5_url('pages/subPages/orderList/orderList?status=10'), 'page_path' => 'pages/subPages/orderList/orderList?status=10'],
                    ['name' => '帮助中心', 'url' => h5_url('pages/subPages/helpCenter/helpCenter'), 'page_path' => 'pages/subPages/helpCenter/helpCenter'],
                    ['name' => '我的卡券', 'url' => h5_url('pages/subPages/coupon/coupon'), 'page_path' => 'pages/subPages/coupon/coupon'],
                    ['name' => '消费记录', 'url' => h5_url('pages/subPages/consumptionRecords/consumptionRecords'), 'page_path' => 'pages/subPages/consumptionRecords/consumptionRecords'],
                    ['name' => '通讯列表', 'url' => h5_url('pages/subPages/homepage/private/private'), 'page_path' => 'pages/subPages/homepage/private/private'],
                    ['name' => '客服页面', 'url' => h5_url('pages/subPages/customer/customer'), 'page_path' => 'pages/subPages/customer/customer'],

                ]
            ],
        ];
        #2、系统链接 - 模块链接，添加用户拥有的模块的链接信息
        if (p('rush')) {
            $plugin[] = ['name' => '抢购首页', 'url' => h5_url('pages/mainPages/index/diypage?type=3'), 'page_path' => 'pages/mainPages/index/diypage?type=3'];
            //分类链接 —— 抢购分类链接
            $cate['rush']['name'] = '抢购分类';
            $rushCateList = pdo_getall(PDO_NAME."rush_category",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','name'],'','sort DESC');
            foreach($rushCateList as $rushCate){
                $cate['rush']['list'][] = [
                    'name' => $rushCate['name'],
                    'url' => h5_url('pages/subPages2/coursegoods/localindex/localindex',['type'=>3,'cate_id'=>$rushCate['id']]),
                    'page_path' => 'pages/subPages2/coursegoods/localindex/localindex?type=3&cate_id='.$rushCate['id'],
                ];
            }
        }
        if (p('wlfightgroup')) {
            $plugin[] = ['name' => '拼团首页', 'url' => h5_url('pages/mainPages/index/diypage?type=6'), 'page_path' => 'pages/mainPages/index/diypage?type=6'];
            //分类链接 —— 拼团分类链接
            $cate['wlfightgroup']['name'] = '拼团分类';
            $fightCateList = pdo_getall(PDO_NAME."fightgroup_category",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','name'],'','listorder DESC');
            foreach($fightCateList as $fightItem){
                $cate['wlfightgroup']['list'][] = [
                    'name' => $fightItem['name'],
                    'url' => h5_url('pages/subPages2/coursegoods/localindex/localindex',['type'=>6,'cate_id'=>$fightItem['id']]),
                    'page_path' => 'pages/subPages2/coursegoods/localindex/localindex?type=6&cate_id='.$fightItem['id'],
                ];
            }
        }
        if (p('groupon')) {
            $plugin[] = ['name' => '团购首页', 'url' => h5_url('pages/mainPages/index/diypage?type=4'), 'page_path' => 'pages/mainPages/index/diypage?type=4'];
            //分类链接 —— 团购分类链接
            $cate['groupon']['name'] = '团购分类';
            $groupCateList = pdo_getall(PDO_NAME."groupon_category",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','name'],'','sort DESC');
            foreach($groupCateList as $groupItem){
                $cate['groupon']['list'][] = [
                    'name' => $groupItem['name'],
                    'url' => h5_url('pages/subPages2/coursegoods/localindex/localindex',['type'=>4,'cate_id'=>$groupItem['id']]),
                    'page_path' => 'pages/subPages2/coursegoods/localindex/localindex?type=4&cate_id='.$groupItem['id'],
                ];
            }
        }
        if (p('bargain')) {
            $plugin[] = ['name' => '砍价首页', 'url' => h5_url('pages/mainPages/index/diypage?type=7'), 'page_path' => 'pages/mainPages/index/diypage?type=7'];
            //分类链接 —— 砍价分类链接
            $cate['bargain']['name'] = '砍价分类';
            $bargainCateList = pdo_getall(PDO_NAME."bargain_category",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','name'],'','sort DESC');
            foreach($bargainCateList as $bargainItem){
                $cate['bargain']['list'][] = [
                    'name' => $bargainItem['name'],
                    'url' => h5_url('pages/subPages2/coursegoods/localindex/localindex',['type'=>7,'cate_id'=>$bargainItem['id']]),
                    'page_path' => 'pages/subPages2/coursegoods/localindex/localindex?type=7&cate_id='.$bargainItem['id'],
                ];
            }
        }
        if (p('wlcoupon')) {
            $plugin[] = ['name' => '卡券首页', 'url' => h5_url('pages/mainPages/index/diypage?type=5'), 'page_path' => 'pages/mainPages/index/diypage?type=5'];
        }
        if (p('pocket')) {
            $plugin[] = ['name' => '掌上信息', 'url' => h5_url('pages/mainPages/pocketIInformant/pocketIInformant'), 'page_path' => 'pages/mainPages/pocketIInformant/pocketIInformant'];
            $plugin[] = ['name' => '我的贴子', 'url' => h5_url('pages/subPages/myPost/myPost'), 'page_path' => 'pages/subPages/myPost/myPost'];
            $plugin[] = ['name' => '发布帖子', 'url' => h5_url('pages/mainPages/sendInformation/sendInformation'), 'page_path' => 'pages/mainPages/sendInformation/sendInformation'];
            if(Customized::init('pocket1500')){
                $plugin[] = ['name' => '分享页面', 'url' => h5_url('pagesA/hotelhomepage/handheldsharing/handheldsharing'), 'page_path' => 'pagesA/hotelhomepage/handheldsharing/handheldsharing'];
            }
        }
        if (p('wlsign')) {
            $plugin[] = ['name' => '签到页面', 'url' => h5_url('pages/subPages/signdesk/index/index'), 'page_path' => 'pages/subPages/signdesk/index/index'];
            $plugin[] = ['name' => '签到记录', 'url' => h5_url('pages/subPages/signdesk/record/record'), 'page_path' => 'pages/subPages/signdesk/record/record'];
            $plugin[] = ['name' => '签到排行', 'url' => h5_url('pages/subPages/signdesk/rank/rank'), 'page_path' => 'pages/subPages/signdesk/rank/rank'];
        }
        if (p('halfcard')) {
            $plugin[] = ['name' => '购卡入口', 'url' => h5_url('pages/mainPages/memberCard/getMembership/getMembership'), 'page_path' => 'pages/mainPages/memberCard/getMembership/getMembership'];
        }
        if (p('consumption')) {
            $plugin[] = ['name' => '积分商城首页', 'url' => h5_url('pages/subPages/integral/integralShop/integralShop'), 'page_path' => 'pages/subPages/integral/integralShop/integralShop'];
        }
        if (p('headline')) {
            $plugin[] = ['name' => '头条列表', 'url' => h5_url('pages/mainPages/headline/index'), 'page_path' => 'pages/mainPages/headline/index'];
            //分类链接 —— 头条分类链接
            $cate['headline']['name'] = '文章头条分类';
            $headCateList = pdo_getall(PDO_NAME."headline_class",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'state'=>1],['id','name','head_id'],'','sort DESC');
            foreach($headCateList as $headItem){
                //判断是否存在上级菜单  生成对应的url信息
                if($headItem['head_id'] > 0) $cardUrl = "pages/mainPages/headline/index?type=13&cate_one={$headItem['head_id']}&cate_two={$headItem['id']}&title={$headItem['name']}";
                else $cardUrl = "pages/mainPages/headline/index?type=13&cate_one={$headItem['id']}&cate_two=0&title={$headItem['name']}";
                //信息赋值
                $cate['headline']['list'][] = [
                    'name'      => $headItem['name'] ,
                    'url'       => h5_url($cardUrl),
                    'page_path' => $cardUrl ,
                ];
            }
        }
        if (p('citycard')) {
            $plugin[] = ['name' => '名片首页', 'url' => h5_url('pages/mainPages/index/diypage?type=13'), 'page_path' => 'pages/mainPages/index/diypage?type=13'];
            $plugin[] = ['name' => '名片夹', 'url' => h5_url('pages/subPages/businesscard/mycard/mycard'), 'page_path' => 'pages/subPages/businesscard/mycard/mycard'];
            $plugin[] = ['name' => '我的名片', 'url' => h5_url('pages/subPages/businesscard/carddetail/renewcarddetail'), 'page_path' => 'pages/subPages/businesscard/carddetail/renewcarddetail'];
            //分类链接 —— 名片分类链接
            $cate['citycard']['name'] = '名片分类';
            $cardCateList = pdo_getall(PDO_NAME."citycard_cates",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'enabled'=>1],['id','name','parentid'],'','sort DESC');
            foreach($cardCateList as $cardItem){
                //判断是否存在上级菜单  生成对应的url信息
                if($cardItem['parentid'] > 0) $cardUrl = "pages/mainPages/index/diypage?type=13&cate_one={$cardItem['parentid']}&cate_two={$cardItem['id']}&title={$cardItem['name']}";
                else $cardUrl = "pages/mainPages/index/diypage?type=13&cate_one={$cardItem['id']}&cate_two=0&title={$cardItem['name']}";
                //信息赋值
                $cate['citycard']['list'][] = [
                    'name'      => $cardItem['name'] ,
                    'url'       => h5_url($cardUrl),
                    'page_path' => $cardUrl ,
                ];
            }
        }
        if (p('wxapp')) {
            $plugin[] = ['name' => '直播首页', 'url' => h5_url('pages/subPages/live/index'), 'page_path' => 'pages/subPages/live/index'];
        }
        if (file_exists(PATH_MODULE . 'L304.log')) {
            $plugin[] = ['name' => '新直播首页', 'url' => h5_url('pages/subPages/live/newLive'), 'page_path' => 'pages/subPages/live/newLive'];
        }
        if (p('citydelivery')) {
            $plugin[] = ['name' => '配送商户', 'url' => h5_url('pages/subPages2/businessCenter/businessCenter'), 'page_path' => 'pages/subPages2/businessCenter/businessCenter'];
        }
        if (p('redpack')) {
            $plugin[] = ['name' => '红包广场', 'url' => h5_url('pages/subPages/redpacket/redsquare'), 'page_path' => 'pages/subPages/redpacket/redsquare'];
            $plugin[] = ['name' => '我的红包', 'url' => h5_url('pages/subPages/redpacket/myredpacket'), 'page_path' => 'pages/subPages/redpacket/myredpacket'];
        }
        if(agent_p('attestation')){
            $plugin[] = ['name' => '认证中心', 'url' => h5_url('pages/subPages/attestationCenter/index',['rzType' => 1]), 'page_path' => 'pages/subPages/attestationCenter/index?rzType=1'];
        }
        if (p('yellowpage')) {
            $system['yellowpage'] = [
                'name' => '黄页114',
                'list' => [
                    ['name'=>'114首页','url'=>h5_url('pages/subPages2/phoneBook/phoneBook'),'page_path' => 'pages/subPages2/phoneBook/phoneBook'],
                    ['name'=>'黄页分类','url'=>h5_url('pages/subPages2/phoneBook/phoneClass/phoneClass'),'page_path' => 'pages/subPages2/phoneBook/phoneClass/phoneClass'],
                    ['name'=>'黄页入驻','url'=>h5_url('pages/subPages2/phoneBook/enterForm/enterForm'),'page_path' => 'pages/subPages2/phoneBook/enterForm/enterForm'],
                    ['name'=>'黄页收藏','url'=>h5_url('pages/subPages2/phoneBook/yellowGoods/yellowGoods'),'page_path' => 'pages/subPages2/phoneBook/yellowGoods/yellowGoods'],
                    ['name'=>'我的黄页','url'=>h5_url('pages/subPages2/phoneBook/myGoods/myGoods'),'page_path' => 'pages/subPages2/phoneBook/myGoods/myGoods'],
                ]
            ];
        }
        if (p('activity')) {
            $plugin[] = [
                'name'      => '活动首页' ,
                'url'       => h5_url('pages/mainPages/index/diypage?type=14') ,
                'page_path' => 'pages/mainPages/index/diypage?type=14'
            ];
            //分类链接 —— 活动分类链接
            $cate['activity']['name'] = '活动分类';
            $activityCateList = pdo_getall(PDO_NAME."activity_category"
                ,['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'status'=>1],['id','name'],'','sort DESC');
            foreach($activityCateList as $activityCate){
                $cate['activity']['list'][] = [
                    'name'      => $activityCate['name'] ,
                    'url'       => h5_url('pages/subPages2/coursegoods/localindex/localindex' , ['type'    => 14 , 'cate_id' => $activityCate['id']]) ,
                    'page_path' => 'pages/subPages2/coursegoods/localindex/localindex?type=14&cate_id=' . $activityCate['id'] ,
                ];
            }
        }
        if (p('recruit')) {
            //招聘主要连接
            $system['recruit'] = [
                'name' => '招聘求职',
                'list' => [
                    ['name'=>'招聘首页','url'=>h5_url('pages/mainPages/index/diypage?type=15'),'page_path' => 'pages/mainPages/index/diypage?type=15'],
                    ['name'=>'招聘列表','url'=>h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList'),'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList'],
                    ['name'=>'企业列表','url'=>h5_url('pages/subPages2/hirePlatform/companiesList/companiesList'),'page_path' => 'pages/subPages2/hirePlatform/companiesList/companiesList'],
                    ['name'=>'发布招聘','url'=>h5_url('pages/subPages2/hirePlatform/postRecruitment/postRecruitment'),'page_path' => 'pages/subPages2/hirePlatform/postRecruitment/postRecruitment'],
                    ['name'=>'我的简历','url'=>h5_url('pages/subPages2/hirePlatform/addResume/addResume'),'page_path' => 'pages/subPages2/hirePlatform/addResume/addResume'],
                    ['name'=>'找兼职','url'=>h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList',['job_type' => 2]),'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?job_type=2'],
                    ['name'=>'找全职','url'=>h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList',['job_type' => 1]),'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?job_type=1'],
                    ['name'=>'个人招聘','url'=>h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList',['recruitment_type' => 1]),'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?recruitment_type=1'],
                    ['name'=>'企业招聘','url'=>h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList',['recruitment_type' => 2]),'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?recruitment_type=2'],
                ]
            ];
            //招聘行业连接
            $cate['recruit']['name'] = '招聘行业';
            $recruitCateList = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','title','pid'],'','sort DESC');
            foreach($recruitCateList as $recruitCate){
                if($recruitCate['pid'] > 0){
                    //子行业
                    $recruitData = [
                        'name'      => $recruitCate['title'] ,
                        'url'       => h5_url('pages/subPages2/hirePlatform/workClassList/workClassList' , ['pid'=> $recruitCate['pid'] , 'id' => $recruitCate['id']]) ,
                        'page_path' => 'pages/subPages2/hirePlatform/workClassList/workClassList?pid=' . $recruitCate['pid']."&id=" . $recruitCate['id'],
                    ];
                }else{
                    //上级行业
                    $recruitData = [
                        'name'      => $recruitCate['title'] ,
                        'url'       => h5_url('pages/subPages2/hirePlatform/workClassList/workClassList' , ['pid'=> $recruitCate['pid'] , 'id' => 0]) ,
                        'page_path' => 'pages/subPages2/hirePlatform/workClassList/workClassList?pid=' . $recruitCate['pid']."&id=0",
                    ];
                }
                //信息赋值
                $cate['recruit']['list'][] = $recruitData;
            }
        }
        if (p('vehicle')){
            $plugin[] = [
                'name'      => '顺风车',
                'url'       => h5_url('pages/subPages2/hitchRide/index/index'),
                'page_path' => 'pages/subPages2/hitchRide/index/index'
            ];
        }
        if (uniacid_p('mobilerecharge')){
            $plugin[] = [
                'name'      => '话费充值',
                'url'       => h5_url('pages/subPages2/voucherCenter/voucherCenter'),
                'page_path' => 'pages/subPages2/voucherCenter/voucherCenter'
            ];
        }
        $system['plugin'] = [
            'name' => '模块链接',
            'list' => ''
        ];

        #3、系统链接 - 分销链接
        if (p('distribution')) {
            $system['distribution']['name'] = '分销链接';
            $system['distribution']['list'][] = ['name' => '分销中心', 'url' => h5_url('pages/subPages/dealer/index/index'), 'page_path' => 'pages/subPages/dealer/index/index'];
            $system['distribution']['list'][] = ['name' => '客户管理', 'url' => h5_url('pages/subPages/dealer/client/client'), 'page_path' => 'pages/subPages/dealer/client/client'];
            $system['distribution']['list'][] = ['name' => '推广商品', 'url' => h5_url('pages/subPages/dealer/setshop/setshop'), 'page_path' => 'pages/subPages/dealer/setshop/setshop'];
            $system['distribution']['list'][] = ['name' => '提现中心', 'url' => h5_url('pages/subPages/dealer/withdraw/withdraw'), 'page_path' => 'pages/subPages/dealer/withdraw/withdraw'];
            $system['distribution']['list'][] = ['name' => '提现记录', 'url' => h5_url('pages/subPages/dealer/withdraw/withdrawrecord'), 'page_path' => 'pages/subPages/dealer/withdraw/withdrawrecord'];
            $system['distribution']['list'][] = ['name' => '推广订单', 'url' => h5_url('pages/subPages/dealer/gener/gener'), 'page_path' => 'pages/subPages/dealer/gener/gener'];
            if(Customized::init('distributionText') > 0){
                $system['distribution']['name'] = '共享股东链接';
                $system['distribution']['list'][0]['name'] = '共享股东中心';
            }
        }
        #4、商户分类连接
        $shopCate = pdo_getall(PDO_NAME . "category_store", ['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'],'enabled'=>1], ['id', 'name','parentid']);
        $system['shop_cate']['name'] = '商户分类';
        foreach ($shopCate as $k => &$v) {
            if($v['parentid'] > 0) {
                $cateParams = [
                    'cate_one' => $v['parentid'],
                    'cate_two' => $v['id']
                ];
                $system['shop_cate']['list'][] = ['name' => $v['name'] , 'url' => h5_url('pages/mainPages/store/list' , $cateParams) , 'page_path' => 'pages/mainPages/store/list'];
            }else{
                if(Customized::init('storecate1520') > 0){
                    $cateParams = ['id' => $v['id']];
                    $system['shop_cate']['list'][] = ['name' => $v['name'] , 'url' => h5_url('pages/subPages2/storeClass/storeClass' , $cateParams) , 'page_path' => 'pages/subPages2/storeClass/storeClass'];
                }else{
                    $cateParams = ['cate_one' => $v['id']];
                    $system['shop_cate']['list'][] = ['name' => $v['name'] , 'url' => h5_url('pages/mainPages/store/list' , $cateParams) , 'page_path' => 'pages/mainPages/store/list'];
                }
            }
        }
        #4、掌上信息分类
        $pockCate = pdo_getall(PDO_NAME . "pocket_type", ['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'],'status'=>1,'type'=>0]
            , ['id', 'title','url'],'','sort DESC');
        $system['pock_cate']['name'] = '信息分类';
        foreach ($pockCate as $pock_k => &$pock_v) {
            if ($pock_v['url']) $url = $pock_v['url'];
            else $url = h5_url('pagesA/econdaryClassification/econdaryClassification' , ['id' => $pock_v['id'],'title' => $pock_v['title']]);
            $system['pock_cate']['list'][] = [
                'name'      => $pock_v['title'] ,
                'url'       => $url ,
                'page_path' => 'pagesA/econdaryClassification/econdaryClassification'
            ];
        }
        //抽奖活动链接
        if (p('draw')) {
            $draw                   = pdo_getall(PDO_NAME . "draw" , ['aid'     => $_W['aid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 2] , ['id' , 'title'] , '' , 'create_time DESC');
            $system['draw']['name'] = '幸运抽奖';
            foreach ($draw as $draw_k => &$draw_v) {
                $url                      = h5_url('pages/subPages2/drawGame/drawGame' , ['id' => $draw_v['id']]);
                $system['draw']['list'][] = [
                    'name'      => $draw_v['title'] ,
                    'url'       => $url ,
                    'page_path' => 'pages/subPages2/drawGame/drawGame?id='.$draw_v['id']
                ];
            }
        }

        if(uniacid_p('luckydraw')){
            $luckydraw = pdo_getall(PDO_NAME . "luckydraw" , ['aid' => $_W['aid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'title'] , '' , 'createtime DESC');
            $system['luckydraw']['name'] = '锦鲤抽奖';
            foreach ($luckydraw as $ldraw_k => &$ldraw_v) {
                $url                      = h5_url('pages/subPages2/lottery/lotteryIndex/lotteryIndex' , ['id' => $ldraw_v['id']]);
                $system['luckydraw']['list'][] = [
                    'name'      => $ldraw_v['title'] ,
                    'url'       => $url ,
                    'page_path' => 'pages/subPages2/lottery/lotteryIndex/lotteryIndex?id='.$ldraw_v['id']
                ];
            }
        }

        if(uniacid_p('call')){
            $call = pdo_getall(PDO_NAME . "call" , ['aid' => $_W['aid'] , 'uniacid' => $_W['uniacid'] , 'state'  => 1] , ['id' , 'title'] , '' , 'createtime DESC');
            $system['call']['name'] = '分享有礼';
            foreach ($call as  &$call_v) {
                $url  = h5_url('pages/subPages/friendshelp/friendshelp' , ['id' => $call_v['id']]);
                $system['call']['list'][] = [
                    'name'      => $call_v['title'] ,
                    'url'       => $url ,
                    'page_path' => 'pages/subPages/friendshelp/friendshelp?id='.$call_v['id']
                ];
            }
        }


        //家政服务
        if (p('housekeep')){
            $plugin[] = [
                'name'      => '家政服务',
                'url'       => h5_url('pages/mainPages/index/diypage?type=18'),
                'page_path' => 'pages/mainPages/index/diypage?type=18'
            ];
            $system['housekeep'] = [
                'name' => '家政服务',
                'list' => [
                    ['name'=>'家政首页','url'=>h5_url('pages/mainPages/index/diypage?type=18'),'page_path' => 'pages/mainPages/index/diypage?type=18'],
                    ['name'=>'服务商家','url'=>h5_url('pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant'),'page_path' => 'pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant'],
                    ['name'=>'我的需求','url'=>h5_url('pages/subPages2/homemaking/myDemand/myDemand'),'page_path' => 'pages/subPages2/homemaking/myDemand/myDemand'],
                    ['name'=>'客户需求','url'=>h5_url('pages/subPages2/homemaking/customerDemand/customerDemand'),'page_path' => 'pages/subPages2/homemaking/customerDemand/customerDemand'],
                    ['name'=>'我的订单','url'=>h5_url('pages/subPages2/homemaking/myorderForm/myorderForm'),'page_path' => 'pages/subPages2/homemaking/myorderForm/myorderForm'],
                    ['name'=>'个人中心','url'=>h5_url('pages/subPages2/homemaking/homeUser/homeUser'),'page_path' => 'pages/subPages2/homemaking/homeUser/homeUser'],
                    ['name'=>'发布需求','url'=>h5_url('pages/subPages2/homemaking/postDemand/postDemand'),'page_path' => 'pages/subPages2/homemaking/postDemand/postDemand'],
                    ['name'=>'服务者入驻','url'=>h5_url('pages/subPages2/homemaking/serviceIn/serviceIn'),'page_path' => 'pages/subPages2/homemaking/serviceIn/serviceIn'],
                ]
            ];
            //分类链接
            $cate['housekeep']['name'] = '家政服务分类';
            $housekeepCateList = pdo_getall(PDO_NAME."housekeep_type",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']],['id','title','onelevelid'],'','sort DESC');
            foreach($housekeepCateList as $housekeepCate) {
                if ($housekeepCate['onelevelid'] > 0) {
                    //子行业
                    $housekeepData = [
                        'name' => $housekeepCate['title'],
                        'url' => h5_url('pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant', ['onelevelid' => $housekeepCate['onelevelid'], 'twolevelid' => $housekeepCate['id'], 'title' => $housekeepCate['title']]),
                        'page_path' => 'pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant?onelevelid=' . $housekeepCate['onelevelid'] . "&twolevelid=" . $housekeepCate['id'] . "&title=" . $housekeepCate['title'],
                    ];
                } else {
                    //上级行业
                    $housekeepData = [
                        'name' => $housekeepCate['title'],
                        'url' => h5_url('pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant', ['onelevelid' => $housekeepCate['id'], 'twolevelid' => 0, 'title' => $housekeepCate['title']]),
                        'page_path' => 'pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant?onelevelid=' . $housekeepCate['id'] . "&twolevelid=0&title=" . $housekeepCate['title'],
                    ];
                }
                //信息赋值
                $cate['housekeep']['list'][] = $housekeepData;
            }
        }

        if (p('dating')){
            $plugin[] = [
                'name'      => '相亲交友',
                'url'       => h5_url('pages/mainPages/index/diypage?type=16'),
                'page_path' => 'pages/mainPages/index/diypage?type=16'
            ];
            $datingplugin[] = [
                'name'      => '相亲首页',
                'url'       => h5_url('pages/mainPages/index/diypage?type=16'),
                'page_path' => 'pages/mainPages/index/diypage?type=16'
            ];
            $datingplugin[] = [
                'name'      => '相亲会员',
                'url'       => h5_url('pages/subPages2/blindDate/member/all'),
                'page_path' => 'pages/subPages2/blindDate/member/all'
            ];
            $datingplugin[] = [
                'name'      => '我的红娘',
                'url'       => h5_url('pages/subPages2/blindDate/matchmakerService'),
                'page_path' => 'pages/subPages2/blindDate/matchmakerService'
            ];
            $datingplugin[] = [
                'name'      => '相亲会员卡',
                'url'       => h5_url('pages/subPages2/blindDate/member/open'),
                'page_path' => 'pages/subPages2/blindDate/member/open'
            ];
            $datingplugin[] = [
                'name'      => '用户中心',
                'url'       => h5_url('pages/subPages2/blindDate/personal'),
                'page_path' => 'pages/subPages2/blindDate/personal'
            ];
            $datingplugin[] = [
                'name'      => '交友动态',
                'url'       => h5_url('pages/subPages2/blindDate/dynamics/index'),
                'page_path' => 'pages/subPages2/blindDate/dynamics/index'
            ];
            $datingplugin[] = [
                'name'      => '用户推荐',
                'url'       => h5_url('pages/subPages2/blindDate/recommend'),
                'page_path' => 'pages/subPages2/blindDate/recommend'
            ];
            $datingplugin[] = [
                'name'      => '红娘服务',
                'url'       => h5_url('pages/subPages2/blindDate/form/matchmakerApply'),
                'page_path' => 'pages/subPages2/blindDate/form/matchmakerApply'
            ];
            $datingplugin[] = [
                'name'      => '个人资料',
                'url'       => h5_url('pages/subPages2/blindDate/form/userInfo'),
                'page_path' => 'pages/subPages2/blindDate/form/userInfo'
            ];
            $datingplugin[] = [
                'name'      => '视频列表',
                'url'       => h5_url('pages/subPages2/blindDate/member/all',['get_type' => 1]),
                'page_path' => 'pages/subPages2/blindDate/form/userInfo?get_type=1'
            ];
            $system['dating'] = [
                'name' => '相亲交友',
                'list' => $datingplugin
            ];
        }


        if (p('house')){
            $plugin[] = [
                'name'      => '房产首页',
                'url'       => h5_url('pages/subPages2/houseproperty/houseproperty'),
                'page_path' => 'pages/subPages2/houseproperty/houseproperty'
            ];
            $houseplugin[] = [
                'name'      => '房产首页',
                'url'       => h5_url('pages/subPages2/houseproperty/houseproperty'),
                'page_path' => 'pages/subPages2/houseproperty/houseproperty'
            ];
            $houseplugin[] = [
                'name'      => '新房列表',
                'url'       => h5_url('pages/subPages2/houseproperty/anewhouse/anewhouse'),
                'page_path' => 'pages/subPages2/houseproperty/anewhouse/anewhouse'
            ];
            $houseplugin[] = [
                'name'      => '二手房列表',
                'url'       => h5_url('pages/subPages2/houseproperty/secondhandhouse/secondhandhouse'),
                'page_path' => 'pages/subPages2/houseproperty/secondhandhouse/secondhandhouse'
            ];
            $houseplugin[] = [
                'name'      => '小区列表',
                'url'       => h5_url('pages/subPages2/houseproperty/residentiallist/residentiallist'),
                'page_path' => 'pages/subPages2/houseproperty/residentiallist/residentiallist'
            ];
            $houseplugin[] = [
                'name'      => '租房列表',
                'url'       => h5_url('pages/subPages2/houseproperty/rentahouse/rentahouse'),
                'page_path' => 'pages/subPages2/houseproperty/rentahouse/rentahouse'
            ];
            $houseplugin[] = [
                'name'      => '顾问列表',
                'url'       => h5_url('pages/subPages2/houseproperty/adviserhouse/adviserhouse'),
                'page_path' => 'pages/subPages2/houseproperty/adviserhouse/adviserhouse'
            ];
            $houseplugin[] = [
                'name'      => '个人中心',
                'url'       => h5_url('pages/subPages2/houseproperty/personalcenter/personalcenter'),
                'page_path' => 'pages/subPages2/houseproperty/personalcenter/personalcenter'
            ];


            $houseplugin[] = [
                'name'      => '发布二手房',
                'url'       => h5_url('pages/subPages2/houseproperty/secondaryrelease/secondaryrelease',['reltype' => 1]),
                'page_path' => 'pages/subPages2/houseproperty/secondaryrelease/secondaryrelease?reltype=1'
            ];

            $houseplugin[] = [
                'name'      => '发布租房',
                'url'       => h5_url('pages/subPages2/houseproperty/releaserental/releaserental',['reltype' => 1]),
                'page_path' => 'pages/subPages2/houseproperty/releaserental/releaserental?reltype=1'
            ];

            $system['house'] = [
                'name' => '房产信息',
                'list' => $houseplugin
            ];
        }

        if (p('hotel')){
            $plugin[] = [
                'name'      => '酒店首页',
                'url'       => h5_url('pagesA/hotelhomepage/hotelhomepage'),
                'page_path' => 'pagesA/hotelhomepage/hotelhomepage'
            ];
            $hotelplugin[] = [
                'name'      => '酒店首页',
                'url'       => h5_url('pagesA/hotelhomepage/hotelhomepage'),
                'page_path' => 'pagesA/hotelhomepage/hotelhomepage'
            ];
            $hotelplugin[] = [
                'name'      => '酒店列表',
                'url'       => h5_url('pagesA/hotelhomepage/hotellist/hotellist'),
                'page_path' => 'pagesA/hotelhomepage/hotellist/hotellist'
            ];

            $system['hotel'] = [
                'name' => '酒店预约',
                'list' => $hotelplugin
            ];
        }




        $system['plugin']['list'] = $plugin;


        #5、判断是否只返回系统连接
        if ($state == 'system') return $system;
        #6、商户链接
        $shop_pageNum = 5;
        $shopWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 ";
        $shop = pdo_fetchall("SELECT id,storename,logo,storehours FROM " .
            tablename(PDO_NAME . "merchantdata") .
            " WHERE {$shopWhere} LIMIT 0,{$shop_pageNum}");
        $shop_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE {$shopWhere}");
        foreach ($shop as $k => &$v) {
            $v['url'] = h5_url('pages/mainPages/store/index', ['sid' => $v['id']]);
            $v['page_path'] = 'pages/mainPages/store/list';
            $v['logo'] = tomedia($v['logo']);
            $storehours = unserialize($v['storehours']);
            $v['storehours'] = $storehours['startTime'] . '-' . $storehours['endTime'];
            unset($v['id']);
        }
        $shopList['name'] = '选择店铺';
        $shopList['list'] = $shop;
        #7、获取抢购商品链接列表
        $rush_pageNum = 5;
        $rushWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status IN (1,2) ";
        $rush = pdo_fetchall(" SELECT id,name,thumb FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE {$rushWhere} LIMIT 0,{$rush_pageNum}");
        $rush_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE {$rushWhere}");
        foreach ($rush as $k => &$v) {
            $v['url'] = h5_url('pages/subPages/goods/index', ['id' => $v['id'], 'type' => 1]);
            $v['page_path'] = 'pages/subPages/goods/index?type=1';
            $v['logo'] = tomedia($v['thumb']);
            unset($v['id']);
        }
        #8、获取拼团商品链接列表
        $fightgroup_pageNum = 5;
        $fightgroupWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status IN (1,2) ";
        $fightgroup = pdo_fetchall(" SELECT id,name,logo FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE {$fightgroupWhere} LIMIT 0,{$fightgroup_pageNum}");
        $fightgroup_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'fightgroup_goods') . " WHERE {$fightgroupWhere}");
        foreach ($fightgroup as $k => &$v) {
            $v['url'] = h5_url('pages/subPages/goods/index', ['id' => $v['id'], 'type' => 3]);
            $v['page_path'] = 'pages/subPages/goods/index?type=3';
            $v['logo'] = tomedia($v['logo']);
            unset($v['id']);
        }
        #9、获取团购商品链接列表
        $groupon_pageNum = 5;
        $grouponWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status IN (1,2)  ";
        $groupon = pdo_fetchall(" SELECT id,name,thumb FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE {$grouponWhere} LIMIT 0,{$groupon_pageNum}");
        $groupon_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'groupon_activity') . " WHERE {$grouponWhere}");
        foreach ($groupon as $k => &$v) {
            $v['url'] = h5_url('pages/subPages/goods/index', ['id' => $v['id'], 'type' => 2]);
            $v['page_path'] = 'pages/subPages/goods/index?type=2';
            $v['logo'] = tomedia($v['thumb']);
            unset($v['id']);
        }
        #10、获取优惠券链接列表
        $coupon_pageNum = 5;
        $couponWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status IN (1,2)";
        $coupon = pdo_fetchall(" SELECT id,title,logo FROM " . tablename(PDO_NAME . "couponlist") . " WHERE {$couponWhere} LIMIT 0,{$coupon_pageNum}");
        $coupon_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE {$couponWhere}");
        foreach ($coupon as $k => &$v) {
            $v['name'] = $v['title'];
            $v['url'] = h5_url('pages/subPages/goods/index', ['id' => $v['id'], 'type' => 5]);
            $v['page_path'] = 'pages/subPages/goods/index?type=5';
            $v['logo'] = tomedia($v['logo']);
            unset($v['id']);
            unset($v['title']);
        }
        #11、获取砍价商品链接列表
        $bargain_pageNum = 5;
        $bargainWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status IN (1,2) ";
        $bargain = pdo_fetchall(" SELECT id,`name`,thumb FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE {$bargainWhere} LIMIT 0,{$bargain_pageNum}");
        $bargain_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE {$bargainWhere}");
        foreach ($bargain as $k => &$v) {
            $v['url'] = h5_url('pages/subPages/goods/index', ['id' => $v['id'], 'type' => 7]);
            $v['page_path'] = 'pages/subPages/goods/index?type=7';
            $v['logo'] = tomedia($v['thumb']);
            unset($v['id']);
        }
        #12、获取自定义页面地址信息。页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页
        #12-1、自定义页面
        $diyList = pdo_getall(PDO_NAME . "diypage", ['type' => 1, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($diyList) {
            $pageInfo['diy']['list'] = $diyList;
            $pageInfo['diy']['name'] = '自定义页面';
            foreach ($pageInfo['diy']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 1]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=1';
            }
        }
        #12-2、平台首页
        $homeList = pdo_getall(PDO_NAME . "diypage", ['type' => 2, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);;
        if ($homeList) {
            $pageInfo['home']['name'] = '平台首页';
            $pageInfo['home']['list'] = $homeList;
            foreach ($pageInfo['home']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 2]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=2';
            }
        }
        #12-3、抢购首页
        $rushList = pdo_getall(PDO_NAME . "diypage", ['type' => 3, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($rushList) {
            $pageInfo['rush']['name'] = '抢购首页';
            $pageInfo['rush']['list'] = $rushList;
            foreach ($pageInfo['rush']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 3]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=3';
            }
        }
        #12-4、团购首页
        $groupList = pdo_getall(PDO_NAME . "diypage", ['type' => 4, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($groupList) {
            $pageInfo['groupon']['name'] = '团购首页';
            $pageInfo['groupon']['list'] = $groupList;
            foreach ($pageInfo['groupon']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 4]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=4';
            }
        }
        #12-5、卡券首页
        $couponList = pdo_getall(PDO_NAME . "diypage", ['type' => 5, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($couponList) {
            $pageInfo['coupon']['name'] = '卡券首页';
            $pageInfo['coupon']['list'] = $couponList;
            foreach ($pageInfo['coupon']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 5]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=5';
            }
        }
        #12-6、拼团首页
        $couponList = pdo_getall(PDO_NAME . "diypage", ['type' => 6, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($couponList) {
            $pageInfo['coupon']['name'] = '拼团首页';
            $pageInfo['coupon']['list'] = $couponList;
            foreach ($pageInfo['coupon']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 6]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=6';
            }
        }
        #12-7、砍价首页
        $couponList = pdo_getall(PDO_NAME . "diypage", ['type' => 7, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'name']);
        if ($couponList) {
            $pageInfo['coupon']['name'] = '砍价首页';
            $pageInfo['coupon']['list'] = $couponList;
            foreach ($pageInfo['coupon']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/mainPages/index/diypage', ['id' => $v['id'], 'type' => 7]);
                $v['page_path'] = 'pages/mainPages/index/diypage?type=7';
            }
        }
        #12-8、自定义表单
        $diyformList = pdo_getall('wlmerchant_diyform',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']),array('id','title'));
        if ($diyformList) {
            $pageInfo['diyform']['name'] = '自定义表单';
            $pageInfo['diyform']['list'] = $diyformList;
            foreach ($pageInfo['diyform']['list'] as $k => &$v) {
                $v['url'] = h5_url('pages/subPages2/merchantca/merchantca', ['id' => $v['id']]);
                $v['page_path'] = 'pages/subPages2/merchantca/merchantca?id='.$v['id'];
                $v['name'] = $v['title'];
            }
        }

        #13、抢购专题链接
        $rushSpecial = pdo_getall(PDO_NAME . "rush_special", ['uniacid' => $_W['uniacid'], 'aid' => $_W['aid']], ['id', 'title']);
        if ($rushSpecial) {
            $rushSpecialLink = 'pages/subPages/special/rushspeci/rushspeci';
            foreach ($rushSpecial as $k => &$v) {
                $v['url'] = h5_url($rushSpecialLink, ['id' => $v['id']]);
                $v['page_path'] = $rushSpecialLink;
            }
        }
        #14、信息拼装
        $data['system'] = $system;
        $data['shop_total'] = $shop_total;
        $data['shopList'] = $shopList;
        $data['rush_total'] = $rush_total;
        $data['rush'] = $rush;
        $data['fightgroup_total'] = $fightgroup_total;
        $data['fightgroup'] = $fightgroup;
        $data['groupon_total'] = $groupon_total;
        $data['groupon'] = $groupon;
        $data['coupon_total'] = $coupon_total;
        $data['coupon'] = $coupon;
        $data['bargain_total'] = $bargain_total;
        $data['bargain'] = $bargain;
        $data['pageInfo'] = $pageInfo;
        $data['rush_special'] = $rushSpecial;
        $data['cate'] = $cate;


        return $data;
    }
    /**
     * Comment: 需要进行转换的路径信息
     * Author: zzw
     * Date: 2020/4/15 14:03
     * @return array
     */
    public static function getTransformationLink(){
        return [
            'dashboard/home/index'                              => 'pages/mainPages/index/index' , //首页
            'store/supervise/information'                       => 'pages/mainPages/Settled/Settled' , //商家入驻
            'store/storeManage/index'                           => 'pages/subPages/dealer/myStoreDetails/myStoreDetails' , //商家中心
            'member/user/index'                                 => 'pages/mainPages/userCenter/userCenter' , //个人中心
            'store/merchant/newindex'                           => 'pages/mainPages/index/diypage?type=8' , //好店
            'diypage/diyhome/home'                              => 'pages/mainPages/index/diypage?type=1' , //自定义页面
            'rush/home/index'                                   => 'pages/mainPages/index/diypage?type=3' , //抢购首页
            'groupon/grouponapp/grouponlist'                    => 'pages/mainPages/index/diypage?type=4' , //团购首页
            'wlcoupon/coupon_app/couponslist'                   => 'pages/mainPages/index/diypage?type=5' , //卡券首页
            'wlfightgroup/fightapp/fightindex'                  => 'pages/mainPages/index/diypage?type=6' , //拼团首页
            'bargain/bargain_app/bargainlist'                   => 'pages/mainPages/index/diypage?type=7' , //砍价首页
            'order/userorder/orderlist'                         => 'pages/subPages/orderList/orderList' , //我的订单列表
            'distribution/disappbase/index'                     => 'pages/subPages/dealer/index/index' , //分销中心
            'distribution/disappbase/applyindex'                => 'pages/subPages/dealer/apply/apply' , //申请分销
            'rush/home/detail'                                  => 'pages/subPages/goods/index' , //商品详情页(抢购)
            'groupon/grouponapp/groupondetail'                  => 'pages/subPages/goods/index' , //商品详情页(团购)
            'wlfightgroup/fightapp/goodsdetail'                 => 'pages/subPages/goods/index' , //商品详情页(拼团)
            'bargain/bargain_app/bargaindetail'                 => 'pages/subPages/goods/index' , //商品详情页(砍价)
            'wlcoupon/coupon_app/couponsdetail'                 => 'pages/subPages/goods/index' , //商品详情页(卡券)
            'pocket/pocket/index'                               => 'pages/mainPages/pocketIInformant/pocketIInformant' , //掌上信息首页
            'halfcard/halfcard_app/userhalfcard'                => 'pages/mainPages/memberCard/memberCard' , //一卡通首页
            'halfcard/halfcardopen/open'                        => 'pages/mainPages/memberCard/getMembership/getMembership' , //一卡通开卡入口
            'pocket/pocket/myinform'                            => 'pages/subPages/myPost/myPost' , //我的帖子
            'wlcoupon/coupon_app/couponList'                    => 'pages/subPages/coupon/coupon' , //我的卡券
            'wlsign/signapp/signindex'                          => 'pages/subPages/signdesk/index/index' , //签到页面
            'wlsign/signapp/signrecord'                         => 'pages/subPages/signdesk/record/record' , //签到记录
            'wlsign/signapp/signrank'                           => 'pages/subPages/signdesk/rank/rank' , //签到排行
            'store/merchant/detail'                             => 'pages/mainPages/store/index' , //商户详情
            'order/userorder/payover'                           => 'pages/mainPages/paySuccess/paySuccess' , //支付成功
            'wlfightgroup/fightapp/expressorder'                => 'pages/subPages/orderList/orderDetails/orderDetails' , //订单详情（拼团）
            'distribution/disappbase/apply'                     => 'pages/subPages/dealer/withdraw/withdrawrecord' , //分销商提现记录(详情)
            'distribution/disappbase/lowpeople'                 => 'pages/subPages/dealer/client/client' , //分销商客户信息
            'distribution/disappbase/topayorder'                => 'pages/mainPages/payment/payment' , //订单支付页面
            'consumption/goods/recordlist'                      => 'pages/subPages/orderList/orderList' , //积分商城兑换记录 转为订单列表
            'area/region/index#/pages/mainPages/headline/index' => 'pages/mainPages/headline/index' ,//头条
            'pages/mainPages/goods/index'                       => 'pages/subPages/goods/index' ,//商品链接  主包到分包
            'pages/mainPages/myPost/myPost'                     => 'pages/subPages/myPost/myPost' ,//我的帖子  主包到分包
        ];
    }

}

