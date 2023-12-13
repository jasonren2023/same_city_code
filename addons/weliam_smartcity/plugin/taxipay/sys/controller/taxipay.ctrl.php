<?php
defined('IN_IA') or exit('Access Denied');

class Taxipay_WeliamController {

    public function master_lists() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        if ($_GPC['name']) {
            $where['name LIKE'] = '%' . $_GPC['name'] . '%';
        }

        $lists = pdo_getslice('wlmerchant_taxipay_master', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as &$master) {
            $master['member'] = $master['mid'] ? Member::wl_member_get($master['mid'], ['nickname', 'avatar']) : [];
            $master['company'] = pdo_getcolumn('wlmerchant_taxipay_company', array('id' => $master['cid']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('taxipay/master_lists');
    }

    public function master_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            $cloudspeaker = $_GPC['cloudspeaker'] ? : '';

            $data['plate_number'] = strtoupper($data['plate_number']);
            $data['cloudspeaker'] = serialize($cloudspeaker);

            if (!empty($id)) {
                pdo_update('wlmerchant_taxipay_master', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['createtime'] = time();
                pdo_insert('wlmerchant_taxipay_master', $data);
                $id = pdo_insertid();
            }
            wl_message('编辑师傅成功', web_url('taxipay/taxipay/master_edit', array('id' => $id)), 'success');
        }

        if (!empty($id)) {
            $item = Taxipay::master_get($id);
            $cloudspeaker = unserialize($item['cloudspeaker']);
        } else {
            $item = ['status' => 1];
        }
        $companys = pdo_getall('wlmerchant_taxipay_company', array('uniacid' => $_W['uniacid']));
        $province_codes = Taxipay::province_code();
        $letters = Taxipay::a_to_z();

        include wl_template('taxipay/master_edit');
    }

    public function master_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_taxipay_master', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_taxipay_master', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }
    public function master_pass() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_taxipay_master', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_update('wlmerchant_taxipay_master', array('status' => 1),array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function master_qrcode() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $h5qrcode = Taxipay::master_qr_code($id, 'h5');
        $wxappqrcode = Taxipay::master_qr_code($id, 'wxapp');

        include wl_template('taxipay/master_qrcode');
    }

    public function company_lists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_taxipay_company', array('uniacid' => $_W['uniacid']), array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as &$company) {
            $company['province_name'] = pdo_getcolumn('wlmerchant_area', array('id' => $company['province']), 'name');
            $company['city_name'] = pdo_getcolumn('wlmerchant_area', array('id' => $company['city']), 'name');
            $company['district_name'] = pdo_getcolumn('wlmerchant_area', array('id' => $company['district']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('taxipay/company_lists');
    }

    public function company_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if (!empty($id)) {
                pdo_update('wlmerchant_taxipay_company', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                pdo_insert('wlmerchant_taxipay_company', $data);
            }
            wl_message('编辑公司成功', web_url('taxipay/taxipay/company_lists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_taxipay_company', array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            $item = [];
        }
        include wl_template('taxipay/company_edit');
    }

    public function company_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_taxipay_company', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_taxipay_company', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }


    public function adv_lists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_taxipay_adv', array('uniacid' => $_W['uniacid'],), array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as &$adv) {
            $adv['adv_uv'] = pdo_getcolumn('wlmerchant_taxipay_advlog', array('advid' => $adv['id']), 'COUNT(id)');
            $adv['adv_pv'] = pdo_getcolumn('wlmerchant_taxipay_advlog', array('advid' => $adv['id']), 'SUM(times)');
            if(empty($adv['adv_pv'])){
                $adv['adv_pv'] = 0;
            }
        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('taxipay/adv_lists');
    }

    public function adv_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if (!empty($id)) {
                pdo_update('wlmerchant_taxipay_adv', $data, array('id' => $id));
                pdo_delete('wlmerchant_taxipay_advcids', array('advid' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                pdo_insert('wlmerchant_taxipay_adv', $data);
                $id = pdo_insertid();
            }

            if (!empty($_GPC['companys'])) {
                foreach ($_GPC['companys'] as $company) {
                    pdo_insert('wlmerchant_taxipay_advcids', ['advid' => $id, 'cid' => $company]);
                }
            }
            wl_message('编辑广告成功', web_url('taxipay/taxipay/adv_lists'), 'success');
        }

        $companyids = [];
        if (!empty($id)) {
            $item = pdo_get('wlmerchant_taxipay_adv', array('uniacid' => $_W['uniacid'], 'id' => $id));
            $advcids = pdo_getall('wlmerchant_taxipay_advcids', array('advid' => $id), 'cid');
            foreach ($advcids as $advcid) {
                $companyids[] = $advcid['cid'];
            }
        } else {
            $item = ['sort' => 100, 'status' => 1];
        }
        $companys = pdo_getall('wlmerchant_taxipay_company', array('uniacid' => $_W['uniacid']));

        include wl_template('taxipay/adv_edit');
    }

    public function adv_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_taxipay_adv', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_taxipay_adv', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function adv_log() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_taxipay_advlog', array('uniacid' => $_W['uniacid'], 'advid' => intval($_GPC['id'])), array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as &$advlog) {
            $advlog['member'] = Member::wl_member_get($advlog['mid'], ['nickname', 'avatar']);
        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('taxipay/adv_log');
    }

    public function setting() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('taxipay');
        if (checksubmit('submit')) {
            $data = $_GPC['settings'];

            $len = count($_GPC['urlname']);
            for ($k = 0; $k < $len; $k++) {
                $urls[$k]['name'] = $_GPC['urlname'][$k];
                $urls[$k]['url'] = $_GPC['url'][$k];
            }
            $data['urls'] = $urls;

            Setting::wlsetting_save($data, 'taxipay');
            wl_message('更新设置成功！', web_url('taxipay/taxipay/setting'));
        }
        include wl_template('taxipay/setting');
    }

    public function url_tpl() {
        $id = random(5);
        include wl_template('taxipay/url_tpl');
    }


}