<?php
defined('IN_IA') or exit('Access Denied');

class Menus_store extends Menus {

    public static function __callStatic($method, $arg) {
        global $_W, $_GPC;
        $method = substr($method, 3, -6);
        $config = App::ext_plugin_config($method);
        if (empty($config['menus']) || $config['setting']['agent'] != 'true') {
            wl_message('您访问的应用不存在，请重试！');
        }
        $perms = $_W['authority'];
        if(!empty($perms)){
            if(!in_array('halfcard',$perms)){
                unset($config['menus']['halfcard0']['items']['halfcard_webeditHalfcard']);
            }
            if(!in_array('package',$perms)){
                unset($config['menus']['halfcard0']['items']['halfcard_webpackagelist']);
            }
        }
        return $config['menus'];
    }

    static function topmenus() {
        global $_W;
        $frames = array();
        $appact = Util::traversingFiles(PATH_PLUGIN);
        $appact[] = 'app';
        $appact[] = 'goodshouse';

        $frames['dashboard']['title'] = '<i class="fa fa-desktop"></i>&nbsp;&nbsp; 概况';
        $frames['dashboard']['url'] = web_url('dashboard/dashboard');
        $frames['dashboard']['active'] = 'dashboard';
        $frames['dashboard']['jurisdiction'] = 'dashboard';

        $frames['order']['title'] = '<i class="fa fa-list"></i>&nbsp;&nbsp; 订单';
        $frames['order']['url'] = web_url('order/wlOrder/orderlist');
        $frames['order']['active'] = 'order';
        $frames['order']['jurisdiction'] = 'order';

        $frames['finance']['title'] = '<i class="fa fa-money"></i>&nbsp;&nbsp; 财务';
        $frames['finance']['url'] = web_url('finace/newCash/currentlist',array('type'=>'store'));
        $frames['finance']['active'] = 'finace';
        $frames['finance']['jurisdiction'] = 'finace';

        $frames['data']['title'] = '<i class="fa fa-bar-chart"></i>&nbsp;&nbsp; 数据';
        $frames['data']['url'] = web_url('datacenter/datacenter/stat_operate');
        $frames['data']['active'] = 'datacenter';
        $frames['data']['jurisdiction'] = 'datacenter';

        $frames['app']['title'] = '<i class="fa fa-cubes"></i>&nbsp;&nbsp; 营销';
        $frames['app']['url'] = web_url('app/plugins');
        $frames['app']['active'] = $appact;
        $frames['app']['jurisdiction'] = 'app';

        $frames['store']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 设置';
        $frames['store']['url'] = web_url('store/merchant/edit');
        $frames['store']['active'] = 'store';
        $frames['store']['jurisdiction'] = 'store';

        return $frames;
    }

    static function getdashboardFrames() {
        global $_W;
        $frames = array();
        return $frames;
    }

    static function getfinaceFrames() {
        global $_W, $_GPC;
        $frames = array();

        $frames['cashSurvey']['title'] = '<i class="fa fa-database"></i>&nbsp;&nbsp; 财务';
        $frames['cashSurvey']['items'] = array();

        $frames['cashSurvey']['items']['cashrecord']['url'] = web_url('finace/newCash/currentlist',array('type'=>'store'));
        $frames['cashSurvey']['items']['cashrecord']['title'] = '结算记录';
        $frames['cashSurvey']['items']['cashrecord']['actions'] = array();
        $frames['cashSurvey']['items']['cashrecord']['active'] = '';

        return $frames;
    }


    static function getstoreFrames() {
        global $_W, $_GPC;
        $frames = array();

        $frames['user']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 商户管理';
        $frames['user']['items'] = array();
        $frames['user']['items']['edit']['url'] = web_url('store/merchant/edit');
        $frames['user']['items']['edit']['title'] = '商户详情';
        $frames['user']['items']['edit']['actions'] = array('ac', 'merchant', 'do', 'edit');
        $frames['user']['items']['edit']['active'] = '';

        if($_W['storeismain'] == 1){
            $frames['user']['items']['clerk']['url'] = web_url('store/merchant/clerkindex');
            $frames['user']['items']['clerk']['title'] = '店员管理';
            $frames['user']['items']['clerk']['actions'] = array('ac','merchant','do','clerkindex');
            $frames['user']['items']['clerk']['active'] = '';
        }

        if(empty($_W['authority']) || in_array('comment',$_W['authority']) || in_array('dynamic',$_W['authority'])){
            $frames['comment']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 评论与动态';
            $frames['comment']['items'] = array();
            if(empty($_W['authority']) || in_array('comment',$_W['authority'])){
                $frames['comment']['items']['comment']['url'] = web_url('store/storeComment/index');
                $frames['comment']['items']['comment']['title'] = '全部评论';
                $frames['comment']['items']['comment']['actions'] = array('ac', 'storeComment', 'do', 'index');
                $frames['comment']['items']['comment']['active'] = '';
            }
            if(empty($_W['authority']) || in_array('dynamic',$_W['authority'])) {
                $frames['comment']['items']['dynamic']['url'] = web_url('store/storeDynamic/dynamic');
                $frames['comment']['items']['dynamic']['title'] = '商户动态';
                $frames['comment']['items']['dynamic']['actions'] = array('ac', 'storeDynamic', 'do', 'dynamic');
                $frames['comment']['items']['dynamic']['active'] = '';
            }
        }

        $frames['setting']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['items'] = array();
        $frames['setting']['items']['divform']['url'] = web_url('agentset/diyForm/index');
        $frames['setting']['items']['divform']['title'] = '自定义表单';
        $frames['setting']['items']['divform']['actions'] = ['ac' , 'diyForm' , 'do' , ['index' ,'add', 'edit']];
        $frames['setting']['items']['divform']['active'] = '';



        return $frames;
    }

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
        $frames['datacenter']['items']['stat_operate']['title'] = '运营分析';
        $frames['datacenter']['items']['stat_operate']['actions'] = array();
        $frames['datacenter']['items']['stat_operate']['active'] = '';

        return $frames;
    }

    static function getappFrames() {
        global $_W;
        $frames = array();

        $category = App::get_cate_plugins('store');
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

    static function getagentsetFrames() {
        global $_W, $_GPC;


        return self::getstoreFrames();


        $frames = array();
        $frames['setting']['title'] = '<i class="fa fa-globe"></i>&nbsp;&nbsp; 设置';
        $frames['setting']['items'] = array();
        $frames['setting']['items']['base']['url'] = web_url('agentset/agentSetAccount/profile');
        $frames['setting']['items']['base']['title'] = '账号信息';
        $frames['setting']['items']['base']['actions'] = array('ac', 'agentSetAccount', 'do', 'profile');
        $frames['setting']['items']['base']['active'] = '';

        $frames['setting']['items']['adminset']['url'] = web_url('agentset/agentSetStaff/adminset');
        $frames['setting']['items']['adminset']['title'] = '管理设置';
        $frames['setting']['items']['adminset']['actions'] = array('ac', 'agentSetStaff', 'do', 'adminset');
        $frames['setting']['items']['adminset']['active'] = '';

        $frames['setting']['items']['customer']['url'] = web_url('agentset/userset/customer');
        $frames['setting']['items']['customer']['title'] = '客服设置';
        $frames['setting']['items']['customer']['actions'] = array('ac', 'userset', 'do', 'customer');
        $frames['setting']['items']['customer']['active'] = '';


        $frames['setting']['items']['community']['url'] = web_url('agentset/agentSetCommunity/communityList');
        $frames['setting']['items']['community']['title'] = '社群设置';
        $frames['setting']['items']['community']['actions'] = array('ac', 'agentSetCommunity', 'do', 'communityList');
        $frames['setting']['items']['community']['active'] = '';

        $frames['setting']['items']['tags']['url'] = web_url('agentset/agentSetTags/tags');
        $frames['setting']['items']['tags']['title'] = '标签设置';
        $frames['setting']['items']['tags']['actions'] = array('ac', 'agentSetTags', 'do', 'tags');
        $frames['setting']['items']['tags']['active'] = '';

        $frames['setting']['items']['userindex']['url'] = web_url('agentset/userset/userindex');
        $frames['setting']['items']['userindex']['title'] = '个人中心';
        $frames['setting']['items']['userindex']['actions'] = array('ac', 'userset', 'do', 'userindex');
        $frames['setting']['items']['userindex']['active'] = '';


        return $frames;
    }


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
