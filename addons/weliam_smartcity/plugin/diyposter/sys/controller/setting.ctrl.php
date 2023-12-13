<?php
defined('IN_IA') or exit('Access Denied');

class Setting_WeliamController {

    public function base() {
        global $_W, $_GPC;
        if (checksubmit('submit')) {
            $base = $_GPC['data'] ? : [];

            Setting::wlsetting_save($base, 'diyposter');
            Tools::clearwxapp();
            Tools::clearposter();
            wl_message('更新设置成功！', web_url('diyposter/setting/base'));
        }

        $settings = Setting::wlsetting_read('diyposter');
        $allposter = pdo_getall(PDO_NAME . 'poster', array('uniacid' => $_W['uniacid']), array('title', 'type', 'id'));
        $store = $rush = $card = $dist = array();
        foreach ($allposter as $key => $value) {
            if ($value['type'] == 1) {
                $store[] = $value;
            } elseif ($value['type'] == 2) {
                $rush[] = $value;
            } elseif ($value['type'] == 3) {
                $card[] = $value;
            } elseif ($value['type'] == 4) {
                $dist[] = $value;
            } elseif ($value['type'] == 5) {
                $groupon[] = $value;
            } elseif ($value['type'] == 6) {
                $fightgroup[] = $value;
            } elseif ($value['type'] == 7) {
                $bargain[] = $value;
            } elseif ($value['type'] == 8) {
                $salesman[] = $value;
            } elseif ($value['type'] == 9) {
                $consumption[] = $value;
            } elseif ($value['type'] == 10) {
                $userCard[] = $value;
            } elseif ($value['type'] == 11) {
                $subposters[] = $value;
            } elseif ($value['type'] == 14) {
                $activity[] = $value;
            }elseif ($value['type'] == 15) {
                $dating[] = $value;
            }elseif ($value['type'] == 16) {
                $housekeep[] = $value;
            }
        }

        include wl_template('poster/setting');
    }

}
