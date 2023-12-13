<?php
defined('IN_IA') or exit('Access Denied');

class Halfset_WeliamController {
    public function base() {
        global $_W, $_GPC;
        $listData = Util::getNumData("*", PDO_NAME . 'halfcard_type', array('aid' => 0, 'status' => 1));
        $types = $listData[0];
        $settings = Setting::wlsetting_read('halfcard');
        $plugin = unserialize($settings['plugin']);
        $text = $settings['text'];
        $formWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'sid' => 0];
        $diyFormList = pdo_getall(PDO_NAME."diyform",$formWhere,['id','title'],'','create_time DESC,id DESC');
        if (checksubmit('submit')) {
            if(floatval(trim($_GPC['carddeduct'])) < 0) wl_message('请填写正确的特权折扣赠送积分！', referer(), 'error');
            if(floatval(trim($_GPC['packdeduct'])) < 0) wl_message('请填写正确的大礼包赠送积分！', referer(), 'error');
            $base = array(
                'status'          => intval($_GPC['status']),
                'is_openvip'      => intval($_GPC['is_openvip']),
                'urlstatus'       => intval($_GPC['urlstatus']),
                'levelstatus'     => intval($_GPC['levelstatus']),
                'renewstatus'     => intval($_GPC['renewstatus']),
                'daily'           => intval($_GPC['daily']),
                'dailyshow'       => intval($_GPC['dailyshow']),
                'daytimes'        => intval($_GPC['daytimes']),
                'halfcardtype'    => intval($_GPC['halfcardtype']),
                'playtype'        => intval($_GPC['playtype']),
                'cardimg'         => $_GPC['cardimg'],
                'use_space'       => intval($_GPC['use_space']),
                'use_space_times' => intval($_GPC['use_space_times']),
                'use_space_days'  => intval($_GPC['use_space_days']),
                'statisticsdiv'   => $_GPC['statisticsdiv'],
                'packagecate'     => $_GPC['packagecate'],
                'describe'        => htmlspecialchars_decode($_GPC['describe']),
                'nodescribe'      => htmlspecialchars_decode($_GPC['nodescribe']),
                'unshowtab'       => $_GPC['unshowtab'],
                'noticestatus'    => intval($_GPC['noticestatus']),
                'carddeduct'      => sprintf("%.2f", trim($_GPC['carddeduct'])),
                'packdeduct'      => sprintf("%.2f", trim($_GPC['packdeduct'])),
                'monthprice'      => Util::currency_format($_GPC['monthprice']),
                'seasonprice'     => Util::currency_format($_GPC['seasonprice']),
                'halfyearprice'   => Util::currency_format($_GPC['halfyearprice']),
                'yearprice'       => Util::currency_format($_GPC['yearprice']),
                'halfcardtypeids' => $_GPC['type'],
                'share_title'     => $_GPC['share_title'],
                'share_image'     => $_GPC['share_image'],
                'share_desc'      => $_GPC['share_desc'],
                'halfstatus'      => intval($_GPC['halfstatus']),
                'OpenHalfcard'    => $_GPC['OpenHalfcard'],
                'OpenSwitch'      => intval($_GPC['OpenSwitch']),
                'UseHalfcard'     => $_GPC['UseHalfcard'],
                'UseSwitch'       => intval($_GPC['UseSwitch']),
                'hideact'         => $_GPC['hideact'],
                'halfcate'        => $_GPC['halfcate'],
                'limit'           => trim($_GPC['limit']),
                'diyformid'       => $_GPC['diyformid'],
                'cardTextColor'   => $_GPC['cardTextColor'],
                'newdiscount'     => $_GPC['newdiscount'],
                'newreate'        => intval($_GPC['newreate']),
                'cardbgimg'       => $_GPC['cardbgimg'],
                'settlement'      => $_GPC['settlement'],
            );
            if($base['newdiscount'] > 0 && empty($base['newdiscount'])){
                wl_message('请正确设置新人优惠折扣比例');
            }
            $plugin = $_GPC['plugin'];
            $base['plugin'] = serialize($plugin);
            Setting::wlsetting_save($base, 'halfcard');
            wl_message('更新设置成功！', web_url('halfcard/halfset/base'));
        }


        include wl_template('halfcardsys/baseset');
    }

    public function halfcardqa() {
        global $_W, $_GPC;
        include wl_template('halfcardsys/halfcardqa');
    }

    public function userright() {
        global $_W, $_GPC;
        $_W['aid'] = -1;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $naves = Dashboard::getAllNav($pindex - 1, $psize);
        $navs = $naves['data'];
        $pager = wl_pagination($naves['count'], $pindex, $psize);
        include wl_template('halfcardsys/userrightlist');
    }

    public function userrightedit() {
        global $_W, $_GPC;
        $_W['aid'] = -1;
        if (checksubmit('submit')) {
            $nav = $_GPC['nav'];
            $nav['name'] = trim($nav['name']);
            $nav['displayorder'] = intval($nav['displayorder']);
            $nav['enabled'] = intval($_GPC['enabled']);
            $nav['type'] = 2;
            $nav['link'] = $_GPC['link'];
            if (!empty($_GPC['id'])) {
                if (Dashboard::editNav($nav, $_GPC['id'])) wl_message('保存成功', web_url('halfcard/halfset/userright'), 'success');
            } else {
                if (Dashboard::editNav($nav)) wl_message('保存成功', web_url('halfcard/halfset/userright'), 'success');
            }
            wl_message('保存失败', referer(), 'error');
        }
        if (!empty($_GPC['id'])) $nav = Dashboard::getSingleNav($_GPC['id']);
        include wl_template('halfcardsys/userrightedit');
    }

    public function importdefault() {
        global $_W, $_GPC;
        $default = array();
        $default[] = array(
            'uniacid'      => $_W['uniacid'],
            'aid'          => -1,
            'name'         => '消费多返金币',
            'displayorder' => 1,
            'enabled'      => 1,
            'color'        => '#000000',
            'link'         => h5_url('pages/mainPages/memberCard/interests'),
            'thumb'        => URL_APP_IMAGE . 'jifen.png',
            'type'         => 2
        );
        $default[] = array(
            'uniacid'      => $_W['uniacid'],
            'aid'          => -1,
            'name'         => '专属超级券',
            'displayorder' => 1,
            'enabled'      => 1,
            'color'        => '#000000',
            'link'         => h5_url('pages/mainPages/memberCard/interests'),
            'thumb'        => URL_APP_IMAGE . 'jifen.png',
            'type'         => 2
        );
        $default[] = array(
            'uniacid'      => $_W['uniacid'],
            'aid'          => -1,
            'name'         => '抢购特权优惠',
            'displayorder' => 1,
            'enabled'      => 1,
            'color'        => '#000000',
            'link'         => h5_url('pages/mainPages/memberCard/interests'),
            'thumb'        => URL_APP_IMAGE . 'jifen.png',
            'type'         => 2
        );
        foreach ($default as $key => $im) {
            pdo_insert(PDO_NAME . 'nav', $im);
        }
        show_json(1);
    }

    function rightdelete() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('wlmerchant_nav', array('id' => $id));
        show_json(1);
    }

}