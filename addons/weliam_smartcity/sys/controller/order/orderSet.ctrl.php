<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 订单设置
 * Author: zzw
 * Date: 2021/1/7 17:32
 * Class WlOrder_WeliamController
 */
class OrderSet_WeliamController {
    //订单设置
    function orderset(){
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('orderset');
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['shop']);
            Setting::wlsetting_save($base,'orderset');
            wl_message('更新设置成功！', web_url('order/orderSet/orderset'));
        }

        include wl_template('order/orderset');
    }

}