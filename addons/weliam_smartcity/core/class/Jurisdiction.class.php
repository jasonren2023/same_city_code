<?php
defined('IN_IA') or exit('Access Denied');

class Jurisdiction {
    /**
     * Comment: 需要进行权限管理的菜单的列表
     * Author: zzw
     * @return array
     */
    static public function menuList(){
        global $_W;
        //列出基本权限
        $list = [
            //首页管理
            'dashboard'  => [
                'title' => '首页管理',
                'list'  => [
                    ['name' => '运营概况','url' => 'dashboard/dashboard','home'=>'index'],
                    ['name' => '公告','url' => 'dashboard/notice','home'=>'index'],
                    ['name' => '幻灯片','url' => 'dashboard/adv','home'=>'index'],
                    ['name' => '导航栏','url' => 'dashboard/nav','home'=>'index'],
                    ['name' => '广告栏','url' => 'dashboard/banner','home'=>'index'],
                    ['name' => '商品魔方','url' => 'dashboard/cube','home'=>'index'],
                    ['name' => '选项卡管理','url' => 'dashboard/plugin','home'=>'index'],
                    ['name' => '底部菜单','url' => 'dashboard/foot','home'=>'index'],
                    ['name' => '页面链接','url' => 'dashboard/pagelinks','home'=>'index'],
                ]
            ],
            //商户管理
            'store'      => [
                'title' => '商户管理',
                'list'  => [
                    ['name' => '商户列表','url' => 'store/merchant','home'=>'index'],
                    ['name' => '商户分类','url' => 'store/category','home'=>'index'],
                    ['name' => '入驻申请','url' => 'store/storeApply','home'=>'index'],
                    ['name' => '付费记录','url' => 'store/register','home'=>'chargerecode'],
                    ['name' => '入驻套餐','url' => 'store/storeSetMeal','home'=>'chargelist'],
                    ['name' => '全部评论','url' => 'store/storeComment','home'=>'index'],
                    ['name' => '商户动态','url' => 'store/storeDynamic','home'=>'dynamic'],
                ]
            ],
            //订单管理
            'order'      => [
                'title' => '订单管理',
                'list'  => [
                    ['name' => '商品订单','url' => 'order/wlOrder','home'=>'orderlist'],
                    ['name' => '在线买单','url' => 'order/orderPayOnline','home'=>'payonlinelist'],
                    ['name' => '运费模板','url' => 'order/orderFreightTemplate','home'=>'freightlist'],
                    ['name' => '售后记录','url' => 'order/orderAfterSales','home'=>'afterlist'],
                ]
            ],
            //财务管理
            'finace'     => [
                'title' => '财务管理',
                'list'  => [
                    ['name' => '账单明细','url' => 'finace/finaceBill','home'=>'cashrecord'],
                    ['name' => '退款记录','url' => 'finace/finaceRefundRecord','home'=>'refundrecord'],
                    ['name' => '账户管理','url' => 'finace/newCash','home'=>'currentlist','params'=>['type'=>'store']],
                ]
            ],
            //数据管理
            'datacenter' => [
                'title' => '数据管理',
                'list'  => [
                    ['name' => '统计管理','url' => 'datacenter/datacenter','home'=>'stat_operate'],
                ]
            ],
            //应用管理
            'app'        => [
                'title' => '应用管理',
                'list'  => '',
            ],
            //设置管理
            'agentset'   => [
                'title' => '设置管理',
                'list'  => [
                    ['name' => '员工管理','url' => 'agentset/agentSetStaff','home'=>'adminset'],
                    ['name' => '社群设置','url' => 'agentset/agentSetCommunity','home'=>'communityList'],
                    ['name' => '标签设置','url' => 'agentset/agentSetTags','home'=>'tags'],
                    ['name' => '自定义表单','url' => 'agentset/diyForm','home'=>'index'],
                ]
            ],
        ];
        //只显示该平台拥有的应用(插件)
        if (agent_p('groupon')) $appList[] = ['name' => '团购活动','url' => 'groupon/active','home'=>'activelist','keyword' => 'groupon'];
        if (agent_p('rush')) $appList[] = ['name' => '抢购活动','url' => 'rush/active','home'=>'activelist','keyword' => 'rush'];
        if (agent_p('wlfightgroup')) $appList[] = ['name' => '拼团商城','url' => 'wlfightgroup/fightgoods','home'=>'ptgoodslist','keyword' => 'wlfightgroup'];
        if (agent_p('bargain')) $appList[] = ['name' => '砍价活动','url' => 'bargain/bargain_web','home'=>'activitylist','keyword' => 'bargain'];
        if (agent_p('paidpromotion')) $appList[] = ['name' => '支付有礼','url' => 'paidpromotion/payactive','home'=>'activelist','keyword' => 'paidpromotion'];
        if (agent_p('pocket')) $appList[] = ['name' => '掌上信息','url' => 'pocket/Tiezi','home'=>'lists','keyword' => 'pocket'];
        if (agent_p('diypage')) $appList[] = ['name' => '平台装修','url' => 'diypage/diy','home'=>'pagelist','keyword' => 'diypage'];
        if (agent_p('wlcoupon')) $appList[] = ['name' => '超级券','url' => 'wlcoupon/couponlist','home'=>'couponsList','keyword' => 'wlcoupon'];
        if (agent_p('call')) $appList[] = ['name' => '分享有礼','url' => 'call/call','home'=>'callList','keyword' => 'call'];
        if (agent_p('headline')) $appList[] = ['name' => '头条','url' => 'headline/headline','home'=>'infoList','keyword' => 'headline'];
        if (agent_p('citycard')) $appList[] = ['name' => '同城名片','url' => 'citycard/citycard','home'=>'card_lists','keyword' => 'citycard'];
        if (agent_p('salesman')) $appList[] = ['name' => '业务员','url' => 'salesman/salesman','home'=>'lists','keyword' => 'salesman'];
        if (agent_p('yellowpage')) $appList[] = ['name' => '黄页114','url' => 'yellowpage/yellowpage','home'=>'page_lists','keyword' => 'yellowpage'];
        if (agent_p('citydelivery')) $appList[] = ['name' => '同城配送','url' => 'citydelivery/active','home'=>'activelist','keyword' => 'citydelivery'];
        if (agent_p('activity')) $appList[] = ['name' => '同城活动','url' => 'activity/activity_web','home'=>'activitylist','keyword' => 'activity'];
        if (agent_p('redpack')) $appList[] = ['name' => '线上红包','url' => 'redpack/redpack','home'=>'pack_lists','keyword' => 'redpack'];
        if (agent_p('recruit')) $appList[] = ['name' => '求职招聘','url' => 'recruit/industryPosition','home'=>'industryList','keyword' => 'recruit'];
        if (agent_p('dating')) $appList[] = ['name' => '相亲交友','url' => 'dating/member','home'=>'memberList','keyword' => 'dating'];
        if (agent_p('attestation')) $appList[] = ['name' => '认证中心','url' => 'attestation/attestation','home'=>'attestationList','keyword' => 'attestation'];
        if (agent_p('vehicle')) $appList[] = ['name' => '顺风车','url' => 'vehicle/route','home'=>'routeList','keyword' => 'vehicle'];
        if (agent_p('housekeep')) $appList[] = ['name' => '家政服务','url' => 'housekeep/KeepWeb','home'=>'serviceList','keyword' => 'housekeep'];
        if (agent_p('luckydraw')) $appList[] = ['name' => '锦鲤抽奖','url' => 'luckydraw/luckydraw','home'=>'index','keyword' => 'luckydraw'];
        if (uniacid_p('housekeep')) $appList[] = ['name' => '票付通','url' => 'pftapimod/basicSetting','home'=>'basicSetting','keyword' => 'pftapimod'];
        //平台管理员信息补充
        if($_W['aid'] <= 0){
            //插件权限补充
            if (uniacid_p('ydbapp')) $appList[] = ['name' => 'APP','url' => 'ydbapp/appset','home'=>'setting'];
            if (uniacid_p('wxplatform')) $appList[] = ['name' => '微信公众号','url' => 'wxplatform/wechat','home'=>'info'];
            if (uniacid_p('wxapp')) $appList[] = ['name' => '微信小程序','url' => 'wxapp/wxappset','home'=>'wxapp_info'];
            if (uniacid_p('payback')) $appList[] = ['name' => 'NEW支付返现','url' => 'payback/payback','home'=>'cashBackRecord'];
            if (uniacid_p('halfcard')) $appList[] = ['name' => '一卡通','url' => 'halfcard/halftype','home'=>'memberlist'];
            if (uniacid_p('cashback')) $appList[] = ['name' => '支付返现','url' => 'cashback/cashback','home'=>'cashBackRecord'];
            if (uniacid_p('subposter')) $appList[] = ['name' => '倡议关注海报','url' => 'subposter/subposter','home'=>'setting'];
            if (uniacid_p('sharegift')) $appList[] = ['name' => '分享有礼','url' => 'sharegift/sharebase','home'=>'sharerecord'];
            if (uniacid_p('ranklist')) $appList[] = ['name' => '排行榜','url' => 'ranklist/rank','home'=>'rank_list'];
            if (uniacid_p('fullreduce')) $appList[] = ['name' => '满减活动','url' => 'fullreduce/fullreduce','home'=>'activelist'];
            if (uniacid_p('consumption')) $appList[] = ['name' => '积分商城','url' => 'consumption/consumptionset','home'=>'consumptionapi'];
            if (uniacid_p('wlsign')) $appList[] = ['name' => '积分签到','url' => 'wlsign/signset','home'=>'signrule'];
            if (uniacid_p('taxipay')) $appList[] = ['name' => '出租车买单','url' => 'taxipay/taxipay','home'=>'master_lists'];
            if (uniacid_p('distribution')) $appList[] = ['name' => '分销合伙人','url' => 'distribution/dissysbase','home'=>'distributorlist'];
            if (uniacid_p('draw')) $appList[] = ['name' => '幸运抽奖','url' => 'draw/draw','home'=>'index'];
            if (uniacid_p('openapi')) $appList[] = ['name' => '开放接口','url' => 'openapi/apilist','home'=>'apimanage'];
            if (uniacid_p('weliam_house')) $appList[] = ['name' => '智慧房产','url' => 'weliam_house/house','home'=>'home'];
            if (uniacid_p('live')) $appList[] = ['name' => '直播管理','url' => 'live/live','home'=>'liveList'];
            if (uniacid_p('helper')) $appList[] = ['name' => '帮助中心','url' => 'helper/helperquestion','home'=>'lists'];
            if (uniacid_p('news')) $appList[] = ['name' => '消息管理','url' => 'news/template','home'=>'index'];
            if (uniacid_p('diyposter')) $appList[] = ['name' => '自定义海报','url' => 'diyposter/poster','home'=>'lists'];
            if (uniacid_p('mobilerecharge')) $appList[] = ['name' => '话费充值','url' => 'mobilerecharge/mrecharge','home'=>'orderList','keyword' => 'mobilerecharge'];
            //商户权限补充
            $list['store']['list'][] = ['name' => '入驻设置','url' => 'store/settled','home'=>'baseset'];
            $list['store']['list'][] = ['name' => '基本设置','url' => 'store/comment','home'=>'storeSet'];
            //订单权限补充
            $list['order']['list'][] = ['name' => '订单设置','url' => 'order/orderSet','home'=>'orderset'];
            //财务权限补充
            $list['finace']['list'][] = ['name' => '提现申请','url' => 'finace/finaceWithdrawalApply','home'=>'cashApply'];
            $list['finace']['list'][] = ['name' => '财务设置','url' => 'finace/wlCash','home'=>'cashset'];
            //设置权限补充
            $list['agentset']['list'][] = ['name' => '基本设置管理','url' => 'setting/shopset','home'=>'base'];//包括基础设置、客服设置、分享关注、接口设置、文字设置
            $list['agentset']['list'][] = ['name' => '交易管理','url' => 'setting/settingTransaction','home'=>'recharge'];
            $list['agentset']['list'][] = ['name' => '个人中心','url' => 'agentset/userset','home'=>'userindex'];
            $list['agentset']['list'][] = ['name' => '支付管理','url' => 'setting/pay','home'=>'index'];
            //用户权限补充
            $list['member'] = [
                'title' => '客户',
                'list'  => [
                    ['name' => '客户管理与设置','url' => 'member/wlMember','home'=>'index'],
                    ['name' => '通信管理','url' => 'member/userIm','home'=>'index'],
                    ['name' => '客户标签','url' => 'member/userlabel','home'=>'labellist'],
                    ['name' => '用户财务明细','url' => 'member/memberFinancialDetails','home'=>'recharge'],
                ]
            ];
            //代理权限补充
            if (p('area')) {
                $list['area'] = [
                    'title' => '代理',
                    'list'  => [
                        ['name' => '代理列表&分组','url' => 'area/areaagent','home'=>'agentIndex'],
                        ['name' => '地区列表&分组','url' => 'area/hotarea','home'=>'oparealist'],
                        ['name' => '自定义地区','url' => 'area/custom','home'=>'index'],
                        ['name' => '复制数据','url' => 'area/areadb','home'=>'copydata'],
                        ['name' => '代理设置','url' => 'area/areaset','home'=>'setting'],
                    ]
                ];
            }
        }else{
            //代理商独有权限
            //财务
            $list['finace']['list'][] = ['name' => '提现管理','url' => 'finace/finaceWithdrawal','home'=>'cashApply'];//代理商余额提现、提现账户、提现记录
            //设置
            $list['agentset']['list'][] = ['name' => '账号信息','url' => 'agentset/agentSetAccount','home'=>'profile'];
            $list['agentset']['list'][] = ['name' => '客服设置','url' => 'agentset/userset','home'=>'agentcustomer'];
            //应用
            if (p('halfcard')) $appList[] = ['name' => '一卡通','url' => 'halfcard/halfcard_web','home'=>'halfcardList','keyword' => 'halfcard'];

            //判断代理是否有插件权限
            $category = App::get_cate_plugins('agent');
            $newlist = [];
            foreach ($category as $car){
                if(!empty($car['plugins'])){
                    foreach ($car['plugins'] as $plu){
                        $newlist[] =  $plu['ident'];
                    }
                }
            }
            foreach($appList as $ak => $app){
                if(!in_array($app['keyword'],$newlist)){
                    unset($appList[$ak]);
                }
            }
        }

        $list['app']['list'] = $appList;
        return $list;
    }
    /**
     * Comment: 判断代理商员工拥有的主要功能菜单的权限
     * Author: zzw
     * Date: 2020/12/31 16:23
     * @param $list
     * @return array|null
     */
    static public function judgeMainMenu($list){
        //获取全部的权限菜单列表
        $menuList = self::menuList();//获取当前端口需要判断权限的所有菜单
        $menuListKeys = array_keys($menuList);//需要判断权限的菜单的别名列表
        $top_menus = is_store() ? Menus_store::topmenus() : (is_agent() ? Menus::topmenus() : Menus_sys::topmenus());//获取当前端口菜单列表
        $jurisdiction = array_column($top_menus,'jurisdiction');//获取权限别名列表
        //判断权限
        foreach($jurisdiction as $index => $item){
            $pathList = array_column($menuList[$item]['list'], 'url');
            #当前菜单需要判断权限  并且不存在拥有权限的子菜单   删除当前菜单
            if(in_array($item,$menuListKeys) && count(array_intersect($pathList,$list)) <= 0){
                unset($jurisdiction[$index]);
            }
        }


        return array_values($jurisdiction);
    }
    /**
     * Comment: 员工权限判断
     * Author: zzw
     * Date: 2021/1/8 15:21
     */
    static public function judge(){
        global $_W,$_GPC;
        //基本参数信息获取
        $jurisdiction = $_W['jurisdiction'];//当前员工的权限
        $pAc = $_GPC['p'].'/'.$_GPC['ac'];//前往页面的路径
        $permissionList = Jurisdiction::menuList();//所有加入权限控制的控制器列表
        $infoList = array_column($permissionList,'list');//权限列表
        $isJurisdiction = $_GPC['is_jurisdiction'] ? : 0;//是否判断权限，用于某些跨控制器跳转使用。1则不判断权限，强制通过
        //循环获取全部的url信息
        $urlInfoList = [];//需要权限的全部信息列表
        foreach($infoList as $listKey => $ListVal){
            $urlInfoList = array_merge($urlInfoList,$ListVal);
        }
        $_W['JUrlList'] = $urlList = array_column($urlInfoList,'url');//需要权限的全部路径列表
        //进行员工权限的判断 当：当前gotopath需要权限并且员工拥有该权限才会进入  否则返回上一页面
        if(in_array($pAc, $urlList) && !in_array($pAc, $jurisdiction) && $isJurisdiction != 1){
            //没有当前访问方法的权限  进行顺推，查看拥有哪一个的方法进行访问
            $groupList = $permissionList[$_GPC['p']]['list'];
            $sortList = array_column($groupList,'url');
            foreach ($sortList as $VisitKey => $VisitVal){
                if(in_array($VisitVal, $jurisdiction)) {
                    //获取以当前路径作为键的新数组
                    $rewardList = array_combine($sortList,$groupList);
                    $goToPath = $rewardList[$VisitVal]['url'] . '/' . $rewardList[$VisitVal]['home'];
                    header('Location: '.web_url($goToPath,$rewardList[$VisitVal]['params']));
                    die;
                }
            }
            //没有当前访问控制器/模块中任何方法的访问权限 获取第一个拥有权限的页面
            foreach($urlList as $urlListKey => $urlListVal){
                if($urlListVal == $jurisdiction[0]){
                    $rewardList = array_combine($urlList,$urlInfoList);
                    $goToPath = $rewardList[$urlListVal]['url'] . '/' . $rewardList[$urlListVal]['home'];

                    header('Location: '.web_url($goToPath,$rewardList[$urlListVal]['params']));
                    die;
                }
            }
        }
    }



}
