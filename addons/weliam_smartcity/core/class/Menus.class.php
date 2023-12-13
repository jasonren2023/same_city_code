<?php
defined('IN_IA') or exit('Access Denied');

class Menus {

    public static function __callStatic($method, $arg) {
        global $_W, $_GPC;
        $method = substr($method, 3, -6);
        $config = App::ext_plugin_config($method);
        if (empty($config['menus']) || $config['setting']['agent'] != 'true') {
            wl_message('您访问的应用不存在，请重试！');
        }
        return $config['menus'];
    }

    /**
     * static function函数应用
     *
     * @access static
     * @name _calc_current_frames2 函数名称
     * @param $frames
     * @return array
     */
    static function _calc_current_frames2(&$frames) {
        global $_W, $_GPC;
        if (!empty($frames) && is_array($frames)) {
            foreach ($frames as &$frame) {
                foreach ($frame['items'] as &$fr) {
                    if (count($fr['actions']) == 2) {
                        if (is_array($fr['actions']['1'])) {
                            $fr['active'] = in_array($_GPC[$fr['actions']['0']], $fr['actions']['1']) ? 'active' : '';
                        } else {
                            if ($fr['actions']['1'] == $_GPC[$fr['actions']['0']]) {
                                $fr['active'] = 'active';
                            }
                        }
                    } elseif (count($fr['actions']) == 3) {
                        if (($fr['actions']['1'] == $_GPC[$fr['actions']['0']] || @in_array($_GPC[$fr['actions']['0']], $fr['actions']['1'])) && ($fr['actions']['2'] == $_GPC['do'] || @in_array($_GPC['do'], $fr['actions']['2']))) {
                            $fr['active'] = 'active';
                        }
                    } elseif (count($fr['actions']) == 4) {
                        if (($fr['actions']['1'] == $_GPC[$fr['actions']['0']] || @in_array($_GPC[$fr['actions']['0']], $fr['actions']['1'])) && ($fr['actions']['3'] == $_GPC[$fr['actions']['2']] || @in_array($_GPC[$fr['actions']['2']], $fr['actions']['3']))) {
                            $fr['active'] = 'active';
                        }
                    } elseif (count($fr['actions']) == 5) {
                        if ($fr['actions']['1'] == $_GPC[$fr['actions']['0']] && $fr['actions']['3'] == $_GPC[$fr['actions']['2']] && $fr['actions']['4'] == $_GPC['status']) {
                            $fr['active'] = 'active';
                        }
                    } else {
                        $query = parse_url($fr['url'], PHP_URL_QUERY);
                        parse_str($query, $urls);
                        if (defined('ACTIVE_FRAME_URL')) {
                            $query = parse_url(ACTIVE_FRAME_URL, PHP_URL_QUERY);
                            parse_str($query, $get);
                        } else {
                            $get = $_GET;
                        }
                        if (!empty($_GPC['a'])) {
                            $get['a'] = $_GPC['a'];
                        }
                        if (!empty($_GPC['c'])) {
                            $get['c'] = $_GPC['c'];
                        }
                        if (!empty($_GPC['do'])) {
                            $get['do'] = $_GPC['do'];
                        }
                        if (!empty($_GPC['ac'])) {
                            $get['ac'] = $_GPC['ac'];
                        }
                        if (!empty($_GPC['status'])) {
                            $get['status'] = $_GPC['status'];
                        }
                        if (!empty($_GPC['p'])) {
                            $get['p'] = $_GPC['p'];
                        }
                        if (!empty($_GPC['op'])) {
                            $get['op'] = $_GPC['op'];
                        }
                        if (!empty($_GPC['m'])) {
                            $get['m'] = $_GPC['m'];
                        }
                        $diff = array_diff_assoc($urls, $get);

                        if (empty($diff)) {
                            $fr['active'] = 'active';
                        } else {
                            $fr['active'] = '';
                        }
                    }
                }
            }
        }
    }

    /**
     * static function 顶部列表
     *
     * @access static
     * @name topmenus
     * @param
     * @return array
     */
    static function topmenus() {
        global $_W;
        $frames = array();
        $appact = Util::traversingFiles(PATH_PLUGIN);
        $appact[] = 'app';
        $appact[] = 'goodshouse';

        $frames['dashboard']['title'] = '<i class="fa fa-desktop"></i>&nbsp;&nbsp; 平台';
        $frames['dashboard']['url'] = web_url('dashboard/dashboard');
        $frames['dashboard']['active'] = 'dashboard';
        $frames['dashboard']['jurisdiction'] = 'dashboard';

        if (file_exists(PATH_MODULE . 'jimaiwang.log')) {
            $frames['member']['title'] = '<i class="fa fa-user"></i>&nbsp;&nbsp; 客户';
            $frames['member']['url'] = web_url('member/wlMember/index');
            $frames['member']['active'] = 'member';
        }

        $frames['store']['title'] = '<i class="fa fa-users"></i>&nbsp;&nbsp; 商户';
        $frames['store']['url'] = web_url('store/merchant/index', array('enabled' => ''));
        $frames['store']['active'] = 'store';
        $frames['store']['jurisdiction'] = 'store';

//        $frames['goods']['title'] = '<i class="fa fa-users"></i>&nbsp;&nbsp; 商品';
//        $frames['goods']['url'] = web_url('goods/Goods/index');
//        $frames['goods']['active'] = 'goods';
//        $frames['goods']['jurisdiction'] = 'goods';

        $frames['order']['title'] = '<i class="fa fa-list"></i>&nbsp;&nbsp; 订单';
        $frames['order']['url'] = web_url('order/wlOrder/orderlist');
        $frames['order']['active'] = 'order';
        $frames['order']['jurisdiction'] = 'order';

        $frames['finance']['title'] = '<i class="fa fa-money"></i>&nbsp;&nbsp; 财务';
        $frames['finance']['url'] = web_url('finace/finaceBill/cashrecord');
        $frames['finance']['active'] = 'finace';
        $frames['finance']['jurisdiction'] = 'finace';

        $frames['data']['title'] = '<i class="fa fa-bar-chart"></i>&nbsp;&nbsp; 数据';
        $frames['data']['url'] = web_url('datacenter/datacenter/stat_operate');
        $frames['data']['active'] = 'datacenter';
        $frames['data']['jurisdiction'] = 'datacenter';

        $frames['app']['title'] = '<i class="fa fa-cubes"></i>&nbsp;&nbsp; 应用';
        $frames['app']['url'] = web_url('app/plugins');
        $frames['app']['active'] = $appact;
        $frames['app']['jurisdiction'] = 'app';

        $frames['setting']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['url'] = web_url('agentset/agentSetAccount/profile');
        $frames['setting']['active'] = 'agentset';
        $frames['setting']['jurisdiction'] = 'agentset';


        return $frames;
    }

    /**
     * static function 首页左侧列表
     *
     * @access static
     * @name getdashboardFrames
     * @param
     * @return array
     */
    static function getdashboardFrames() {
        global $_W;
        $frames = array();
        $frames['member']['title'] = '<i class="fa fa-dashboard"></i>&nbsp;&nbsp; 概况';
        $frames['member']['items'] = array();

        $frames['member']['items']['setting']['url'] = web_url('dashboard/dashboard/index');
        $frames['member']['items']['setting']['title'] = '运营概况';
        $frames['member']['items']['setting']['actions'] = array('ac', 'dashboard');
        $frames['member']['items']['setting']['active'] = '';

        $frames['page']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 主页管理';
        $frames['page']['items'] = array();
        $frames['page']['items']['notice']['url'] = web_url('dashboard/notice/index');
        $frames['page']['items']['notice']['title'] = '公告';
        $frames['page']['items']['notice']['actions'] = array('ac', 'notice');
        $frames['page']['items']['notice']['active'] = '';

        $frames['page']['items']['adv']['url'] = web_url('dashboard/adv/index');
        $frames['page']['items']['adv']['title'] = '幻灯片';
        $frames['page']['items']['adv']['actions'] = array('ac', 'adv');
        $frames['page']['items']['adv']['active'] = '';

        $frames['page']['items']['nav']['url'] = web_url('dashboard/nav/index');
        $frames['page']['items']['nav']['title'] = '导航栏';
        $frames['page']['items']['nav']['actions'] = array('ac', 'nav');
        $frames['page']['items']['nav']['active'] = '';

        $frames['page']['items']['banner']['url'] = web_url('dashboard/banner/index');
        $frames['page']['items']['banner']['title'] = '广告栏';
        $frames['page']['items']['banner']['actions'] = array('ac', 'banner');
        $frames['page']['items']['banner']['active'] = '';

        $frames['page']['items']['cube']['url'] = web_url('dashboard/cube/index');
        $frames['page']['items']['cube']['title'] = '商品魔方';
        $frames['page']['items']['cube']['actions'] = array('ac', 'cube');
        $frames['page']['items']['cube']['active'] = '';

//		$frames['page']['items']['sort']['url'] = web_url('dashboard/sort/index');
//		$frames['page']['items']['sort']['title'] = '主页排版';
//		$frames['page']['items']['sort']['actions'] = array();
//		$frames['page']['items']['sort']['active'] = '';

        $frames['page']['items']['plugin']['url'] = web_url('dashboard/plugin/index');
        $frames['page']['items']['plugin']['title'] = '选项卡管理';
        $frames['page']['items']['plugin']['actions'] = array('ac', 'plugin');
        $frames['page']['items']['plugin']['active'] = '';

        $frames['page']['items']['foot']['url'] = web_url('dashboard/foot/index');
        $frames['page']['items']['foot']['title'] = '底部菜单';
        $frames['page']['items']['foot']['actions'] = array('ac', 'foot','do','index');
        $frames['page']['items']['foot']['active'] = '';

        $frames['page']['items']['search']['url'] = web_url('dashboard/foot/searchSet');
        $frames['page']['items']['search']['title'] = '搜索页';
        $frames['page']['items']['search']['actions'] = array('do', 'searchSet');
        $frames['page']['items']['search']['active'] = '';

        $frames['other']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 其他信息';
        $frames['other']['items'] = array();
        $frames['other']['items']['notice']['url'] = web_url('dashboard/pagelinks/index');
        $frames['other']['items']['notice']['title'] = '页面链接';
        $frames['other']['items']['notice']['actions'] = array('ac', 'pagelinks');
        $frames['other']['items']['notice']['active'] = '';

        return $frames;
    }

    static function getmemberFrames() {
        global $_W;
        $frames = array();
        $frames['user']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 客户';
        $frames['user']['items'] = array();

        $frames['user']['items']['register']['url'] = web_url('member/wlMember/index');
        $frames['user']['items']['register']['title'] = '客户概况';
        $frames['user']['items']['register']['actions'] = array('ac', 'wlMember', 'do', 'index');
        $frames['user']['items']['register']['active'] = '';

        $frames['user']['items']['notice']['url'] = web_url('member/wlMember/memberIndex');
        $frames['user']['items']['notice']['title'] = '客户列表';
        $frames['user']['items']['notice']['actions'] = array('ac', 'wlMember', 'do', array('memberIndex', 'memberDetail'));
        $frames['user']['items']['notice']['active'] = '';

        return $frames;
    }

    static function getfinaceFrames() {
        global $_W, $_GPC;
        $frames = array();

        $frames['cashSurvey']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 财务';
        $frames['cashSurvey']['items'] = array();

//		$frames['cashSurvey']['items']['datemana']['url'] = web_url('finace/wlCash/cashSurvey');
//		$frames['cashSurvey']['items']['datemana']['title'] = '财务概况';
//		$frames['cashSurvey']['items']['datemana']['actions'] = array();
//		$frames['cashSurvey']['items']['datemana']['active'] = '';

        $frames['cashSurvey']['items']['cashrecord']['url'] = web_url('finace/finaceBill/cashrecord');
        $frames['cashSurvey']['items']['cashrecord']['title'] = '账单明细';
        $frames['cashSurvey']['items']['cashrecord']['actions'] = array();
        $frames['cashSurvey']['items']['cashrecord']['active'] = '';

        $frames['cashSurvey']['items']['refundrecord']['url'] = web_url('finace/finaceRefundRecord/refundrecord');
        $frames['cashSurvey']['items']['refundrecord']['title'] = '退款记录';
        $frames['cashSurvey']['items']['refundrecord']['actions'] = array();
        $frames['cashSurvey']['items']['refundrecord']['active'] = '';

        $frames['cashApplyAgent']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 提现';
        $frames['cashApplyAgent']['items'] = array();

        $frames['cashApplyAgent']['items']['display1']['url'] = web_url('finace/finaceWithdrawal/cashApplyAgent', array('status' => '1'));
        $frames['cashApplyAgent']['items']['display1']['title'] = '余额提现';
        $frames['cashApplyAgent']['items']['display1']['actions'] = array('do', 'cashApplyAgent', 'status', '1');
        $frames['cashApplyAgent']['items']['display1']['active'] = '';

        $frames['cashApplyAgent']['items']['account']['url'] = web_url('finace/finaceWithdrawal/account');
        $frames['cashApplyAgent']['items']['account']['title'] = '提现账户';
        $frames['cashApplyAgent']['items']['account']['actions'] = array();
        $frames['cashApplyAgent']['items']['account']['active'] = '';

        $frames['cashApplyAgent']['items']['display2']['url'] = web_url('finace/finaceWithdrawal/cashApplyAgentRecord');
        $frames['cashApplyAgent']['items']['display2']['title'] = '提现记录';
        $frames['cashApplyAgent']['items']['display2']['actions'] = array('do', 'cashApplyAgentRecord');
        $frames['cashApplyAgent']['items']['display2']['active'] = '';

        if($_W['wlsetting']['cashset']['allocationtype']>0) {
            $frames['cashAll']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 分账';
            $frames['cashAll']['items'] = array();

            $frames['cashAll']['items']['allidset']['url'] = web_url('finace/wlCash/allidset');
            $frames['cashAll']['items']['allidset']['title'] = '账户设置';
            $frames['cashAll']['items']['allidset']['actions'] = array('do', 'allidset');
            $frames['cashAll']['items']['allidset']['active'] = '';

        }

        $frames['current']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 账户';
        $frames['current']['items'] = array();

        $frames['current']['items']['currentstore']['url'] = web_url('finace/newCash/currentlist', array('type' => 'store'));
        $frames['current']['items']['currentstore']['title'] = '商家账户';
        $frames['current']['items']['currentstore']['actions'] = array('type', 'store');
        $frames['current']['items']['currentstore']['active'] = '';

        $frames['current']['items']['currentmy']['url'] = web_url('finace/newCash/currentlist', array('type' => 'agent'));
        $frames['current']['items']['currentmy']['title'] = '我的账户';
        $frames['current']['items']['currentmy']['actions'] = array('type', 'agent');
        $frames['current']['items']['currentmy']['active'] = '';

        return $frames;
    }

    /**
     * static function 商户左侧列表
     *
     * @access static
     * @name getstoreFrames
     * @param
     * @return array
     */
    static function getstoreFrames() {
        global $_W, $_GPC;
        $frames = array();

        $frames['user']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 商户管理';
        $frames['user']['items'] = array();
        $frames['user']['items']['index']['url'] = web_url('store/merchant/index', array('enabled' => ''));
        $frames['user']['items']['index']['title'] = '商户列表';
        $frames['user']['items']['index']['actions'] = array('ac', 'merchant');
        $frames['user']['items']['index']['active'] = '';

        $frames['user']['items']['category']['url'] = web_url('store/category/index');
        $frames['user']['items']['category']['title'] = '商户分类';
        $frames['user']['items']['category']['actions'] = array('ac', 'category');
        $frames['user']['items']['category']['active'] = '';

        $frames['register']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 入驻管理';
        $frames['register']['items'] = array();
        $frames['register']['items']['register']['url'] = web_url('store/storeApply/index');
        $frames['register']['items']['register']['title'] = '入驻申请';
        $frames['register']['items']['register']['actions'] = array('ac', 'storeApply', 'do', 'index');
        $frames['register']['items']['register']['active'] = '';

        if ($_W['wlsetting']['register']['chargestatus']) {
            $frames['register']['items']['chargerecode']['url'] = web_url('store/register/chargerecode');
            $frames['register']['items']['chargerecode']['title'] = '付费记录';
            $frames['register']['items']['chargerecode']['actions'] = array('ac', 'storeApply', 'do', 'chargerecode');
            $frames['register']['items']['chargerecode']['active'] = '';
        }

        if ($_W['wlsetting']['register']['agentright']) {
            $frames['register']['items']['agentcharge']['url'] = web_url('store/storeSetMeal/chargelist');
            $frames['register']['items']['agentcharge']['title'] = '入驻套餐';
            $frames['register']['items']['agentcharge']['actions'] = array('ac', 'storeSetMeal', 'do', array('chargelist', 'add'));
            $frames['register']['items']['agentcharge']['active'] = '';
        }

        $frames['comment']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 评论与动态';
        $frames['comment']['items'] = array();
        $frames['comment']['items']['comment']['url'] = web_url('store/storeComment/index');
        $frames['comment']['items']['comment']['title'] = '全部评论';
        $frames['comment']['items']['comment']['actions'] = array('ac', 'storeComment', 'do', 'index');
        $frames['comment']['items']['comment']['active'] = '';

        $frames['comment']['items']['dynamic']['url'] = web_url('store/storeDynamic/dynamic');
        $frames['comment']['items']['dynamic']['title'] = '商户动态';
        $frames['comment']['items']['dynamic']['actions'] = array('ac', 'storeDynamic', 'do', 'dynamic');
        $frames['comment']['items']['dynamic']['active'] = '';

//        $frames['setting']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 商户设置';
//        $frames['setting']['items'] = array();
//        $frames['setting']['items']['setting']['url'] = web_url('store/comment/storeSet');
//        $frames['setting']['items']['setting']['title'] = '基本设置';
//        $frames['setting']['items']['setting']['actions'] = array('ac', 'comment', 'do', 'index');
//        $frames['setting']['items']['setting']['active'] = '';

        return $frames;
    }

    /**
     * static function 订单左侧列表
     *
     * @access static
     * @name getorderFrames
     * @param
     * @return array
     */
    static function getorderFrames() {
        global $_W;
        $frames = array();
        $frames['order']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp;订单';
        $frames['order']['items'] = array();

        $frames['order']['items']['orderlist']['url'] = web_url('order/wlOrder/orderlist');
        $frames['order']['items']['orderlist']['title'] = '商品订单';
        $frames['order']['items']['orderlist']['actions'] = array('ac', 'wlOrder', 'do', array('orderlist', 'orderdetail'));
        $frames['order']['items']['orderlist']['active'] = '';

        $frames['order']['items']['payonlinelist']['url'] = web_url('order/orderPayOnline/payonlinelist');
        $frames['order']['items']['payonlinelist']['title'] = '在线买单';
        $frames['order']['items']['payonlinelist']['actions'] = array('ac', 'orderPayOnline', 'do', 'payonlinelist');
        $frames['order']['items']['payonlinelist']['active'] = '';

        $frames['freight']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 运费';
        $frames['freight']['items'] = array();

        $frames['freight']['items']['freightlist']['url'] = web_url('order/orderFreightTemplate/freightlist');
        $frames['freight']['items']['freightlist']['title'] = '运费模板';
        $frames['freight']['items']['freightlist']['actions'] = array('ac', 'orderFreightTemplate', 'do', 'freightlist');
        $frames['freight']['items']['freightlist']['active'] = '';

        $frames['saleafter']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 预约售后';
        $frames['saleafter']['items'] = array();

        $frames['saleafter']['items']['afterlist']['url'] = web_url('order/orderAfterSales/afterlist');
        $frames['saleafter']['items']['afterlist']['title'] = '售后记录';
        $frames['saleafter']['items']['afterlist']['actions'] = array('ac', 'orderAfterSales', 'do', 'afterlist');
        $frames['saleafter']['items']['afterlist']['active'] = '';

        $frames['saleafter']['items']['appointlist']['url'] = web_url('order/wlOrder/appointlist');
        $frames['saleafter']['items']['appointlist']['title'] = '预约记录';
        $frames['saleafter']['items']['appointlist']['actions'] = array('ac', 'wlOrder', 'do', 'appointlist');
        $frames['saleafter']['items']['appointlist']['active'] = '';

        return $frames;
    }

    static function getdatacenterFrames() {
        global $_W;
        $frames = array();

        $frames['datacenter']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 统计分析';
        $frames['datacenter']['items'] = array();
        $frames['datacenter']['items']['stat_operate']['url'] = web_url('datacenter/datacenter/stat_operate');
        $frames['datacenter']['items']['stat_operate']['title'] = '运营统计';
        $frames['datacenter']['items']['stat_operate']['actions'] = array();
        $frames['datacenter']['items']['stat_operate']['active'] = '';

        $frames['datacenter']['items']['stat_store']['url'] = web_url('datacenter/datacenter/stat_store');
        $frames['datacenter']['items']['stat_store']['title'] = '店铺统计';
        $frames['datacenter']['items']['stat_store']['actions'] = array();
        $frames['datacenter']['items']['stat_store']['active'] = '';

        if (file_exists(PATH_MODULE . 'TnSrtWDJ.log')) {
            $frames['datacenter']['items']['stat_store_card']['url'] = web_url('datacenter/datacenter/stat_store_card');
            $frames['datacenter']['items']['stat_store_card']['title'] = '商户会员';
            $frames['datacenter']['items']['stat_store_card']['actions'] = array();
            $frames['datacenter']['items']['stat_store_card']['active'] = '';
        }

        return $frames;
    }

    /**
     * static function 应用左侧列表
     *
     * @access static
     * @name getappFrames
     * @param
     * @return array
     */
    static function getappFrames() {
        global $_W;
        $frames = array();

        $category = App::get_cate_plugins('agent');
        foreach ($category as $key => $value) {
            if (!empty($value['plugins'])) {
                $frames[$key]['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; ' . $value['name'];
                $frames[$key]['items'] = array();
                foreach ($value['plugins'] as $pk => $plug) {
                    $frames[$key]['items'][$plug['ident']]['url'] = $plug['cover'];
                    $frames[$key]['items'][$plug['ident']]['title'] = $plug['name'];
                    $frames[$key]['items'][$plug['ident']]['actions'] = array('ac', $plug['ident']);
                    $frames[$key]['items'][$plug['ident']]['active'] = '';
                }
            }
        }

        return $frames;
    }

    /**
     * static function 应用左侧列表
     *
     * @access static
     * @name getappFrames
     * @param
     * @return array
     */
    static function getgoodshouseFrames() {
        global $_W, $_GPC;
        $method = $_GPC['plugin'] ?: 'rush';
        $method = ($method != 'fightgroup') ? $method : 'wlfightgroup';
        $config = App::ext_plugin_config($method);

        return $config['menus'];
    }

    static function getagentsetFrames() {
        global $_W, $_GPC;
        $frames = array();
        $frames['setting']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['items'] = array();
        $frames['setting']['items']['base']['url'] = web_url('agentset/agentSetAccount/profile');
        $frames['setting']['items']['base']['title'] = '账号信息';
        $frames['setting']['items']['base']['actions'] = array('ac', 'agentSetAccount', 'do', 'profile');
        $frames['setting']['items']['base']['active'] = '';

        $frames['setting']['items']['share']['url'] = web_url('agentset/agentSetAccount/shareSet');
        $frames['setting']['items']['share']['title'] = '分享设置';
        $frames['setting']['items']['share']['actions'] = array('ac', 'agentSetAccount', 'do', 'shareSet');
        $frames['setting']['items']['share']['active'] = '';

        $frames['setting']['items']['adminset']['url'] = web_url('agentset/agentSetStaff/adminset');
        $frames['setting']['items']['adminset']['title'] = '员工管理';
        $frames['setting']['items']['adminset']['actions'] = array('ac', 'agentSetStaff', 'do', 'adminset');
        $frames['setting']['items']['adminset']['active'] = '';

        $frames['setting']['items']['community']['url'] = web_url('agentset/agentSetCommunity/communityList');
        $frames['setting']['items']['community']['title'] = '社群设置';
        $frames['setting']['items']['community']['actions'] = array('ac', 'agentSetCommunity', 'do', 'communityList');
        $frames['setting']['items']['community']['active'] = '';

        $frames['setting']['items']['tags']['url'] = web_url('agentset/agentSetTags/tags');
        $frames['setting']['items']['tags']['title'] = '标签设置';
        $frames['setting']['items']['tags']['actions'] = array('ac', 'agentSetTags', 'do', 'tags');
        $frames['setting']['items']['tags']['active'] = '';

        $frames['setting']['items']['customer']['url'] = web_url('agentset/userset/agentcustomer');
        $frames['setting']['items']['customer']['title'] = '客服设置';
        $frames['setting']['items']['customer']['actions'] = array('ac', 'userset', 'do', 'agentcustomer');
        $frames['setting']['items']['customer']['active'] = '';

        $frames['setting']['items']['divform']['url'] = web_url('agentset/diyForm/index');
        $frames['setting']['items']['divform']['title'] = '自定义表单';
        $frames['setting']['items']['divform']['actions'] = ['ac' , 'diyForm' , 'do' , ['index' ,'add', 'edit']];
        $frames['setting']['items']['divform']['active'] = '';


        return $frames;
    }

    /**
     * Comment: 商品左侧菜单列表
     * Author: zzw
     * Date: 2019/7/3 18:30
     */
    static function getgoodsFrames() {
        global $_W;
        $frames = array();
        $frames['goods']['title'] = '<i class="fa fa-dashboard"></i>&nbsp;&nbsp; 商品管理';
        $frames['goods']['items'] = array();

        $frames['goods']['items']['list']['url'] = web_url('goods/Goods/index');
        $frames['goods']['items']['list']['title'] = '商品列表';
        $frames['goods']['items']['list']['actions'] = array('ac', 'Goods', 'do', 'index');
        $frames['goods']['items']['list']['active'] = '';

        $frames['goods']['items']['cate']['url'] = web_url('goods/Goods/category');
        $frames['goods']['items']['cate']['title'] = '商品分类';
        $frames['goods']['items']['cate']['actions'] = array('ac', 'Goods', 'do', 'category');
        $frames['goods']['items']['cate']['active'] = '';


        return $frames;
    }


}
