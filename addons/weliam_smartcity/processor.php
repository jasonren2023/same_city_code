<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/core/common/defines.php";
require_once PATH_CORE . "common/autoload.php";
require __DIR__ . "/vendor/autoload.php";
Func_loader::core('global');


class Weliam_smartcityModuleProcessor extends WeModuleProcessor {

    public function respond() {
        global $_W;
        $_W['wlsetting'] = Setting::wlsetting_load();
        $rule = pdo_fetch('select * from ' . tablename('rule') . ' where id=:id limit 1', array(':id' => $this->rule));
        if (empty($rule)) {
            return false;
        }
        $message = $this->message;
        file_put_contents(PATH_DATA . "processor_qr.log", var_export($message, true) . PHP_EOL, FILE_APPEND);
        $fansinfo = Member::wl_fans_info($message['from']);
        $_W['wlmember'] = Member::wl_member_create($fansinfo, 'wechat');
        $_W['mid'] = $_W['wlmember']['id'];
        $dotime = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('protime'));
        $dotime = $dotime['protime'];
        if(time() - 10 < $dotime){
            return false;
        }else{
            pdo_update('wlmerchant_member',array('protime' => time()),array('id' => $_W['mid']));
        }


        $names = explode(':', $rule['name']);
        $plugin = (isset($names[1]) ? $names[1] : '');

        if (!empty($plugin)) {
            $plugin::Processor($message);
        }
    }
}
class Weliam_smartcity1ModuleProcessor extends WeModuleProcessor {

    public function respond() {
        global $_W;
        $_W['wlsetting'] = Setting::wlsetting_load();
        $rule = pdo_fetch('select * from ' . tablename('rule') . ' where id=:id limit 1', array(':id' => $this->rule));
        if (empty($rule)) {
            return false;
        }
        $message = $this->message;
        $fansinfo = Member::wl_fans_info($message['from']);
        $_W['wlmember'] = Member::wl_member_create($fansinfo, 'wechat');
        $_W['mid'] = $_W['wlmember']['id'];
        file_put_contents(PATH_DATA . "processor_qr.log", var_export($message, true) . PHP_EOL, FILE_APPEND);

        $names = explode(':', $rule['name']);
        $plugin = (isset($names[1]) ? $names[1] : '');

        if (!empty($plugin)) {
            $plugin::Processor($message);
        }
    }
}