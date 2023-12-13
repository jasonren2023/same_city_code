<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 交易管理
 * Author: zzw
 * Date: 2021/1/8 10:36
 * Class SettingTransaction_WeliamController
 */
class SettingTransaction_WeliamController {
    //充值设置
    public function recharge() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('recharge');
        $count = count($settings['kilometre']);
        for ($i = 0; $i < $count; $i++) {
            $array[$i]['kilometre'] = $settings['kilometre'][$i];
            $array[$i]['kilmoney'] = $settings['kilmoney'][$i];
        }

        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['recharge']);
            Setting::wlsetting_save($base, 'recharge');
            wl_message('更新设置成功！', web_url('setting/settingTransaction/recharge'));
        }

        $isAuth = Customized::init('diy_userInfo');

        include wl_template('setting/recharge');
    }
    //积分设置
    public function creditset() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('creditset');
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['data']);
            $base['integralsxf'] = sprintf("%.2f",$base['integralsxf']);
            Setting::wlsetting_save($base, 'creditset');
            wl_message('更新设置成功！', web_url('setting/settingTransaction/creditset'));
        }

        include wl_template('setting/creditset');
    }

}
