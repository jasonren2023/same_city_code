<?php
defined('IN_IA') or exit('Access Denied');

class Subposter_WeliamController {

    public function setting() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('subposter');
        if (checksubmit('submit')) {
            $data = $_GPC['settings'];
            $data['keywords'] = trim($data['keywords']);

            if (!empty($data['keywords']) && $data['keywords'] != $settings['keywords']) {
                Weixinqrcode::createkeywords('倡议关注二维码:Subposter', $data['keywords']);
            }

            Setting::wlsetting_save($data, 'subposter');
            Tools::clearposter();
            wl_message('更新设置成功！', web_url('subposter/subposter/setting'));
        }

        if (empty($settings)) {
            $settings = ['number' => 0, 'name_color' => '#FFFFFF', 'num_color' => '#FFFFFF', 'reply' => '[昵称]感谢参与！您是第[人数]个倡议者，保存下列图片，转发给朋友们，提高全民防疫意识，从我做起！'];
        }

        include wl_template('subposter/setting');
    }

    public function records() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_subposter_log', array('uniacid' => $_W['uniacid']), array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
            if (empty($val['mid'])) {
                unset($lists[$key]);
            }
            $val['createtime'] = date('Y-m-d H:i:s', $val['createtime']);
            $val['member'] = Member::wl_member_get($val['mid'], ['nickname', 'avatar']);
            $val['invite_num'] = pdo_getcolumn('wlmerchant_subposter_log', ['uniacid' => $_W['uniacid'], 'invite_id' => $val['mid']], 'COUNT(id)');
            $val['invite_member'] = Member::wl_member_get($val['invite_id'], ['nickname', 'avatar']);
        }
        $pager = wl_pagination($total, $pindex, $psize);

        $timetoday = strtotime(date("Y-m-d", time()));
        $all_num = pdo_getcolumn('wlmerchant_subposter_log', ['uniacid' => $_W['uniacid']], 'COUNT(id)');
        $today_num = pdo_getcolumn('wlmerchant_subposter_log', ['uniacid' => $_W['uniacid'], 'createtime >' => $timetoday], 'COUNT(id)');
        $yestoday_num = pdo_getcolumn('wlmerchant_subposter_log', ['uniacid' => $_W['uniacid'], 'createtime >' => $timetoday - 86400, 'createtime <' => $timetoday], 'COUNT(id)');
        $week_num = pdo_getcolumn('wlmerchant_subposter_log', ['uniacid' => $_W['uniacid'], 'createtime >' => $timetoday - 86400 * 6], 'COUNT(id)');

        include wl_template('subposter/records');
    }
}