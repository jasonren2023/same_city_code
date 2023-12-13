<?php
defined('IN_IA') or exit('Access Denied');

class Menus_sys extends Menus {

    public static function __callStatic($method, $arg) {
        global $_W, $_GPC;
        $config = App::ext_plugin_config($_W['plugin']);
        if (empty($config['menus']) || $config['setting']['system'] != 'true') {
            wl_message('您访问的应用不存在，请重试！');
        }
        //没有公众号，则不显示实卡功能
        if ($_W['plugin'] == 'halfcard' && !p('wxplatform')) {
            unset($config['menus']['halfcard1']['items']['realcardcardlist']);
        }
        //非独立版隐藏自定义菜单
        if(IMS_FAMILY != 'wl'){
            unset($config['menus']['wxplatform0']['items']['wechatdiymenu']);
            unset($config['menus']['wxplatform0']['items']['wechatautoreply']);
        }
        if(Customized::init('distributionText') > 0 && $_W['plugin'] == 'distribution'){
            $config['menus']['distribution0']['title'] = '共享股东';
            $config['menus']['distribution0']['items']['dissysbasedistributorlist']['title'] = '股东列表';
            $config['menus']['distribution0']['items']['dissysbasedislevel']['title'] = '股东等级';
            $config['menus']['distribution2']['items']['dissysbasedisbaseset']['title'] = '应用设置';
        }

        if(Customized::init('groupon138') > 0 && $_W['plugin'] == 'distribution'){
            $config['menus']['distribution3'] = $config['menus']['distribution2'];
            $config['menus']['distribution2'] = [];
            $config['menus']['distribution2']['title'] = '&nbsp;&nbsp; 团长';
            $grouparray = [
                'url' => web_url('distribution/dissysbase/grouplist'),
                'title' => '团长列表',
                'actions' => ['ac','dissysbase','do','grouplist'],
                'active' => ''
            ];
            $grouplevel = [
                'url' => web_url('distribution/dissysbase/grouplevel'),
                'title' => '团长等级',
                'actions' => ['ac','dissysbase','do','grouplevel'],
                'active' => ''
            ];
            $config['menus']['distribution2']['items']['dissysbasegrouplist'] = $grouparray;
            $config['menus']['distribution2']['items']['dissysbasegrouplevel'] = $grouplevel;
        }
        return $config['menus'];
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

        $frames['member']['title'] = '<i class="fa fa-user"></i>&nbsp;&nbsp; 客户';
        $frames['member']['url'] = web_url('member/wlMember/index');
        $frames['member']['active'] = 'member';
        $frames['member']['jurisdiction'] = 'member';

        $frames['store']['title'] = '<i class="fa fa-users"></i>&nbsp;&nbsp; 商户';
        $frames['store']['url'] = web_url('store/merchant/index', array('enabled' => ''));
        $frames['store']['active'] = 'store';
        $frames['store']['jurisdiction'] = 'store';

        $frames['order']['title'] = '<i class="fa fa-list"></i>&nbsp;&nbsp; 订单';
        $frames['order']['url'] = web_url('order/wlOrder/orderlist');
        $frames['order']['active'] = 'order';
        $frames['order']['jurisdiction'] = 'order';

        $perms = App::get_account_perm("plugins", $_W['uniacid']);
        if (p('area') && ((in_array('area',$perms) && $perms) || !$perms )) {
            $frames['area']['title'] = '<i class="fa fa-map"></i>&nbsp;&nbsp; 代理';
            $frames['area']['url'] = web_url('area/areaagent/agentIndex');
            $frames['area']['active'] = 'area';
            $frames['area']['jurisdiction'] = 'area';
        }

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
        $frames['app']['active'] = array_merge(array_diff($appact, array('area')));
        $frames['app']['jurisdiction'] = 'app';

        $frames['setting']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['url'] = web_url('setting/shopset/base');
        $frames['setting']['active'] = ['setting', 'agentset'];
        $frames['setting']['jurisdiction'] = 'agentset';

        if ($_W['highest_role'] == 'founder' && IMS_FAMILY != 'wl') {
            $frames['cloud']['title'] = '<i class="fa fa-cloud"></i>&nbsp;&nbsp; 云服务';
            $frames['cloud']['url'] = web_url('cloud/auth/auth');
            $frames['cloud']['active'] = 'cloud';
            $frames['cloud']['jurisdiction'] = 'cloud';
        }
        return $frames;
    }

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

    /**
     * static function 客户左侧列表
     *
     * @access static
     * @name getmemberFrames
     * @param
     * @return array
     */
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


        $frames['im']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 通信管理';
        $frames['im']['items'] = array();

        $frames['im']['items']['im']['url'] = web_url('member/userIm/index');
        $frames['im']['items']['im']['title'] = '通信管理';
        $frames['im']['items']['im']['actions'] = ['ac' , 'userIm' , 'do' , ['index']];
        $frames['im']['items']['im']['active'] = '';

//        $frames['im']['items']['im_set']['url'] = web_url('member/userIm/imSet');
//        $frames['im']['items']['im_set']['title'] = '通信设置';
//        $frames['im']['items']['im_set']['actions'] = ['ac' , 'userIm' , 'do' , ['imSet']];
//        $frames['im']['items']['im_set']['active'] = '';

        $frames['userlabel']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 标签';
        $frames['userlabel']['items'] = array();

        $frames['userlabel']['items']['labellist']['url'] = web_url('member/userlabel/labellist');
        $frames['userlabel']['items']['labellist']['title'] = '客户标签';
        $frames['userlabel']['items']['labellist']['actions'] = array();
        $frames['userlabel']['items']['labellist']['active'] = '';

        $frames['current']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 财务';
        $frames['current']['items'] = array();

        $frames['current']['items']['recharge']['url'] = web_url('member/memberFinancialDetails/recharge');
        $frames['current']['items']['recharge']['title'] = '充值明细';
        $frames['current']['items']['recharge']['actions'] = array('ac', 'memberFinancialDetails', 'do', 'recharge');
        $frames['current']['items']['recharge']['active'] = '';

        $frames['current']['items']['integral']['url'] = web_url('member/memberFinancialDetails/integral');
        $frames['current']['items']['integral']['title'] = '积分明细';
        $frames['current']['items']['integral']['actions'] = array('ac', 'memberFinancialDetails', 'do', 'integral');
        $frames['current']['items']['integral']['active'] = '';

        $frames['current']['items']['balance']['url'] = web_url('member/memberFinancialDetails/balance');
        $frames['current']['items']['balance']['title'] = '余额明细';
        $frames['current']['items']['balance']['actions'] = array('ac', 'memberFinancialDetails', 'do', 'balance');
        $frames['current']['items']['balance']['active'] = '';

        $frames['memberset']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 设置';
        $frames['memberset']['items'] = array();

        $frames['memberset']['items']['userset']['url'] = web_url('member/wlMember/userset');
        $frames['memberset']['items']['userset']['title'] = '用户设置';
        $frames['memberset']['items']['userset']['actions'] = array('ac', 'wlMember', 'do', 'userset');
        $frames['memberset']['items']['userset']['active'] = '';

        if (file_exists(PATH_MODULE . 'N561.log')) {
            $frames['transfer']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 余额转赠';
            $frames['transfer']['items'] = array();

            $frames['transfer']['items']['transferlist']['url'] = web_url('member/wlMember/transferlist');
            $frames['transfer']['items']['transferlist']['title'] = '转赠活动';
            $frames['transfer']['items']['transferlist']['actions'] = array('ac', 'wlMember', 'do', 'transferlist');
            $frames['transfer']['items']['transferlist']['active'] = '';

            $frames['transfer']['items']['transferrecord']['url'] = web_url('member/wlMember/transferrecord');
            $frames['transfer']['items']['transferrecord']['title'] = '转赠记录';
            $frames['transfer']['items']['transferrecord']['actions'] = array('ac', 'wlMember', 'do', 'transferrecord');
            $frames['transfer']['items']['transferrecord']['active'] = '';
        }



        return $frames;
    }

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

        $frames['register']['items']['settled']['url'] = web_url('store/settled/baseset');
        $frames['register']['items']['settled']['title'] = '入驻设置';
        $frames['register']['items']['settled']['actions'] = array('ac', 'settled', 'do', 'baseset');
        $frames['register']['items']['settled']['active'] = '';

        $frames['register']['items']['chargerecode']['url'] = web_url('store/register/chargerecode');
        $frames['register']['items']['chargerecode']['title'] = '付费记录';
        $frames['register']['items']['chargerecode']['actions'] = array('ac', 'register', 'do', 'chargerecode');
        $frames['register']['items']['chargerecode']['active'] = '';

        $frames['register']['items']['chargelist']['url'] = web_url('store/storeSetMeal/chargelist');
        $frames['register']['items']['chargelist']['title'] = '入驻套餐';
        $frames['register']['items']['chargelist']['actions'] = array('ac', 'storeSetMeal', 'do', array('chargelist', 'add'));
        $frames['register']['items']['chargelist']['active'] = '';

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

        $frames['setting']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 商户设置';
        $frames['setting']['items'] = array();
        $frames['setting']['items']['setting']['url'] = web_url('store/comment/storeSet');
        $frames['setting']['items']['setting']['title'] = '基本设置';
        $frames['setting']['items']['setting']['actions'] = array('ac', 'comment', 'do', 'storeSet');
        $frames['setting']['items']['setting']['active'] = '';
        
        if(Customized::init('redpack527') > 0){
        	$frames['redpack']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 商户红包';
	        $frames['redpack']['items'] = array();
	        
	        $frames['redpack']['items']['redpack']['url'] = web_url('store/comment/redpackList');
	        $frames['redpack']['items']['redpack']['title'] = '红包设置';
	        $frames['redpack']['items']['redpack']['actions'] = array('ac', 'comment', 'do', 'redpackList');
	        $frames['redpack']['items']['redpack']['active'] = '';
	        
	        $frames['redpack']['items']['record']['url'] = web_url('store/comment/redpackRecord');
	        $frames['redpack']['items']['record']['title'] = '领取记录';
	        $frames['redpack']['items']['record']['actions'] = array('ac', 'comment', 'do', 'redpackRecord');
	        $frames['redpack']['items']['record']['active'] = '';
        }


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
        $frames['order']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 订单';
        $frames['order']['items'] = array();

        $frames['order']['items']['orderlist']['url'] = web_url('order/wlOrder/orderlist');
        $frames['order']['items']['orderlist']['title'] = '商品订单';
        $frames['order']['items']['orderlist']['actions'] = array('ac', 'wlOrder', 'do', array('orderlist', 'orderdetail'));
        $frames['order']['items']['orderlist']['active'] = '';

        $frames['order']['items']['payonlinelist']['url'] = web_url('order/orderPayOnline/payonlinelist');
        $frames['order']['items']['payonlinelist']['title'] = '在线买单';
        $frames['order']['items']['payonlinelist']['actions'] = array('ac', 'orderPayOnline', 'do', 'payonlinelist');
        $frames['order']['items']['payonlinelist']['active'] = '';

        $frames['order']['items']['orderset']['url'] = web_url('order/orderSet/orderset');
        $frames['order']['items']['orderset']['title'] = '订单设置';
        $frames['order']['items']['orderset']['actions'] = array('ac', 'orderSet', 'do', 'orderset');
        $frames['order']['items']['orderset']['active'] = '';

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


    static function getfinaceFrames() {
        global $_W, $_GPC;
        $frames = array();
        $frames['cashSurvey']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 财务概况';
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

        $frames['current']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 账户';
        $frames['current']['items'] = array();

        $frames['current']['items']['currentstore']['url'] = web_url('finace/newCash/currentlist', array('type' => 'store'));
        $frames['current']['items']['currentstore']['title'] = '商家账户';
        $frames['current']['items']['currentstore']['actions'] = array('type', 'store');
        $frames['current']['items']['currentstore']['active'] = '';

        $frames['current']['items']['currentmy']['url'] = web_url('finace/newCash/currentlist', array('type' => 'agent'));
        $frames['current']['items']['currentmy']['title'] = '代理账户';
        $frames['current']['items']['currentmy']['actions'] = array('type', 'agent');
        $frames['current']['items']['currentmy']['active'] = '';

        $frames['cashApply']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 提现';
        $frames['cashApply']['items'] = array();

        $frames['cashApply']['items']['display1']['url'] = web_url('finace/finaceWithdrawalApply/cashApply');
        $frames['cashApply']['items']['display1']['title'] = '提现申请';
        $frames['cashApply']['items']['display1']['actions'] = array();
        $frames['cashApply']['items']['display1']['active'] = '';

        $frames['cashApply']['items']['cashset']['url'] = web_url('finace/wlCash/cashset');
        $frames['cashApply']['items']['cashset']['title'] = '财务设置';
        $frames['cashApply']['items']['cashset']['actions'] = array();
        $frames['cashApply']['items']['cashset']['active'] = '';

        return $frames;
    }

    static function getdatacenterFrames() {
        global $_W;
        $frames = array();

        $frames['datacenter']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 统计分析';
        $frames['datacenter']['items'] = array();

        $frames['datacenter']['items']['stat_operate']['url'] = web_url('datacenter/datacenter/stat_operate');
        $frames['datacenter']['items']['stat_operate']['title'] = '运营分析';
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

//		$frames['datacenter']['items']['stat_agent']['url'] = web_url('datacenter/datacenter/stat_agent');
//		$frames['datacenter']['items']['stat_agent']['title'] = '代理统计';
//		$frames['datacenter']['items']['stat_agent']['actions'] = array();
//		$frames['datacenter']['items']['stat_agent']['active'] = '';

        if (p('distribution')) {
            if(Customized::init('distributionText') > 0){
                $frames['distri']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 共享股东分析';
            }else{
                $frames['distri']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 分销分析';
            }
            $frames['distri']['items'] = array();

            $frames['distri']['items']['stat_distri']['url'] = web_url('datacenter/datacenter/stat_distri');
            if(Customized::init('distributionText') > 0){
                $frames['distri']['items']['stat_distri']['title'] = '共享股东统计';
            }else{
                $frames['distri']['items']['stat_distri']['title'] = '分销统计';
            }
            $frames['distri']['items']['stat_distri']['actions'] = array();
            $frames['distri']['items']['stat_distri']['active'] = '';
        }

        return $frames;
    }

    /**
     * static function 设置左侧列表
     *
     * @access static
     * @name getsettingFrames
     * @param
     * @return array
     */
    static function getsettingFrames() {
        global $_W, $_GPC;
        $frames = array();
        $frames['setting']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['items'] = array();
        $frames['setting']['items']['base']['url'] = web_url('setting/shopset/base');
        $frames['setting']['items']['base']['title'] = '基础设置';
        $frames['setting']['items']['base']['actions'] = array('ac', 'shopset', 'do', 'base');
        $frames['setting']['items']['base']['active'] = '';

        $frames['setting']['items']['share']['url'] = web_url('setting/shopset/share');
        $frames['setting']['items']['share']['title'] = '分享关注';
        $frames['setting']['items']['share']['actions'] = array('ac', 'shopset', 'do', 'share');
        $frames['setting']['items']['share']['active'] = '';

        $frames['setting']['items']['api']['url'] = web_url('setting/shopset/api');
        $frames['setting']['items']['api']['title'] = '接口设置';
        $frames['setting']['items']['api']['actions'] = array('ac', 'shopset', 'do', 'api');
        $frames['setting']['items']['api']['active'] = '';

        $frames['setting']['items']['community']['url'] = web_url('agentset/agentSetCommunity/communityList');
        $frames['setting']['items']['community']['title'] = '社群设置';
        $frames['setting']['items']['community']['actions'] = array('ac', 'agentSetCommunity', 'do', 'communityList');
        $frames['setting']['items']['community']['active'] = '';

        $frames['setting']['items']['userindex']['url'] = web_url('agentset/userset/userindex');
        $frames['setting']['items']['userindex']['title'] = '个人中心';
        $frames['setting']['items']['userindex']['actions'] = array('ac', 'userset', 'do', 'userindex');
        $frames['setting']['items']['userindex']['active'] = '';

        $frames['setting']['items']['adminset']['url'] = web_url('agentset/agentSetStaff/adminset');
        $frames['setting']['items']['adminset']['title'] = '员工管理';
        $frames['setting']['items']['adminset']['actions'] = array('ac', 'agentSetStaff', 'do', 'adminset');
        $frames['setting']['items']['adminset']['active'] = '';

        $frames['setting']['items']['trade']['url'] = web_url('setting/shopset/trade');
        $frames['setting']['items']['trade']['title'] = '文字设置';
        $frames['setting']['items']['trade']['actions'] = array('ac', 'shopset', 'do', 'trade');
        $frames['setting']['items']['trade']['active'] = '';

        $frames['setting']['items']['customer']['url'] = web_url('setting/shopset/customer');
        $frames['setting']['items']['customer']['title'] = '客服设置';
        $frames['setting']['items']['customer']['actions'] = array('ac', 'shopset', 'do', 'customer');
        $frames['setting']['items']['customer']['active'] = '';

        $frames['setting']['items']['tags']['url'] = web_url('agentset/agentSetTags/tags');
        $frames['setting']['items']['tags']['title'] = '标签设置';
        $frames['setting']['items']['tags']['actions'] = array('ac', 'agentSetTags', 'do', 'tags');
        $frames['setting']['items']['tags']['active'] = '';

        $frames['setting']['items']['divform']['url'] = web_url('agentset/diyForm/index');
        $frames['setting']['items']['divform']['title'] = '自定义表单';
        $frames['setting']['items']['divform']['actions'] = ['ac' , 'diyForm' , 'do' , ['index' ,'add', 'edit']];
        $frames['setting']['items']['divform']['active'] = '';

        if(IMS_FAMILY == 'wl'){
            $frames['setting']['items']['enclosure']['url'] = web_url('setting/shopset/enclosure');
            $frames['setting']['items']['enclosure']['title'] = '附件设置';
            $frames['setting']['items']['enclosure']['actions'] = ['ac' , 'shopset' , 'do' ,'enclosure'];
            $frames['setting']['items']['enclosure']['active'] = '';
        }

        $frames['payset']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 交易';
        $frames['payset']['items'] = array();
        $frames['payset']['items']['recharge']['url'] = web_url('setting/settingTransaction/recharge');
        $frames['payset']['items']['recharge']['title'] = '充值设置';
        $frames['payset']['items']['recharge']['actions'] = array('ac', 'settingTransaction', 'do', 'recharge');
        $frames['payset']['items']['recharge']['active'] = '';

        $frames['payset']['items']['creditset']['url'] = web_url('setting/settingTransaction/creditset');
        $frames['payset']['items']['creditset']['title'] = '积分设置';
        $frames['payset']['items']['creditset']['actions'] = array('ac', 'settingTransaction', 'do', 'creditset');
        $frames['payset']['items']['creditset']['active'] = '';


        $frames['pay']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 支付';
        $frames['pay']['items'] = array();
        $frames['pay']['items']['recharge']['url'] = web_url('setting/pay/index');
        $frames['pay']['items']['recharge']['title'] = '支付设置';
        $frames['pay']['items']['recharge']['actions'] = array('ac', 'pay', 'do', 'index');
        $frames['pay']['items']['recharge']['active'] = '';

        $frames['pay']['items']['administration']['url'] = web_url('setting/pay/administration');
        $frames['pay']['items']['administration']['title'] = '支付管理';
        $frames['pay']['items']['administration']['actions'] = array('ac', 'pay', 'do', 'administration');
        $frames['pay']['items']['administration']['active'] = '';


        return $frames;
    }

    static function getagentsetFrames() {
        return self::getsettingFrames();
    }

    static function getappFrames() {
        global $_W;
        $frames = array();

        $category = App::get_cate_plugins('sys');
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

    static function getcloudFrames() {
        global $_W, $_GPC;
        $frames = array();
        $frames['member']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 云服务';
        $frames['member']['items'] = array();

        $frames['member']['items']['setting']['url'] = web_url('cloud/auth/auth');
        $frames['member']['items']['setting']['title'] = '系统授权';
        $frames['member']['items']['setting']['actions'] = array();
        $frames['member']['items']['setting']['active'] = '';

        if ($_W['wlcloud']['authinfo']['status'] == 0 && $_W['wlcloud']['authinfo']['endtime'] > time()) {
            $frames['member']['items']['display']['url'] = web_url('cloud/auth/upgrade');
            $frames['member']['items']['display']['title'] = '系统升级';
            $frames['member']['items']['display']['actions'] = array();
            $frames['member']['items']['display']['active'] = '';

            $frames['member']['items']['log']['url'] = web_url('cloud/auth/upgrade_log');
            $frames['member']['items']['log']['title'] = '更新日志';
            $frames['member']['items']['log']['actions'] = array();
            $frames['member']['items']['log']['active'] = '';
        }

        $frames['plugin']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 应用管理';
        $frames['plugin']['items'] = array();

        $frames['plugin']['items']['index']['url'] = web_url('cloud/plugin/index');
        $frames['plugin']['items']['index']['title'] = '应用信息';
        $frames['plugin']['items']['index']['actions'] = array();
        $frames['plugin']['items']['index']['active'] = '';

        $frames['plugin']['items']['perm']['url'] = web_url('cloud/plugin/account_list');
        $frames['plugin']['items']['perm']['title'] = '公众号权限';
        $frames['plugin']['items']['perm']['actions'] = array('do', array('account_list', 'account_post'));
        $frames['plugin']['items']['perm']['active'] = '';

        $frames['database']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 数据管理';
        $frames['database']['items'] = array();

        $frames['database']['items']['datemana']['url'] = web_url('cloud/database/datemana');
        $frames['database']['items']['datemana']['title'] = '数据管理';
        $frames['database']['items']['datemana']['actions'] = array();
        $frames['database']['items']['datemana']['active'] = '';

        $frames['database']['items']['run']['url'] = web_url('cloud/database/run');
        $frames['database']['items']['run']['title'] = '运行SQL';
        $frames['database']['items']['run']['actions'] = array();
        $frames['database']['items']['run']['active'] = '';

        $frames['sysset']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 系统设置';
        $frames['sysset']['items'] = array();

        $frames['sysset']['items']['base']['url'] = web_url('cloud/wlsysset/base');
        $frames['sysset']['items']['base']['title'] = '系统信息';
        $frames['sysset']['items']['base']['actions'] = array();
        $frames['sysset']['items']['base']['active'] = '';

        $frames['sysset']['items']['datemana']['url'] = web_url('cloud/wlsysset/taskcover');
        $frames['sysset']['items']['datemana']['title'] = '计划任务';
        $frames['sysset']['items']['datemana']['actions'] = array();
        $frames['sysset']['items']['datemana']['active'] = '';

        $frames['sysset']['items']['jumpadmin']['url'] = web_url('cloud/wlsysset/jumpadmin');
        $frames['sysset']['items']['jumpadmin']['title'] = '跳转域名';
        $frames['sysset']['items']['jumpadmin']['actions'] = array();
        $frames['sysset']['items']['jumpadmin']['active'] = '';

        return $frames;
    }
}
