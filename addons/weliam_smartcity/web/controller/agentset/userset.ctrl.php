<?php
defined('IN_IA') or exit('Access Denied');
class Userset_WeliamController{
    /**
     * Comment: 个人中心按钮设置
     * Author: zzw
     * Date: 2020/3/27 14:58
     */
    public function userindex()
    {
        global $_W , $_GPC;
        if (checksubmit('submit')) {
            $set = $_GPC['set'];
            //循环处理数组
            foreach ($set as $setK => &$setV) {
                if (!array_key_exists('switch' , $setV)) {
                    $setV['switch'] = 0;
                }
            }
            $res = Setting::wlsetting_save($set , 'userindex');

            $data = $_GPC['userset'];
            $res2 = Setting::wlsetting_save($data , 'userindexset');


            if ($res || $res2) show_json(1);
            else show_json(0 , '设置保存失败');
        }
        $setInfo = User::userSet();
        foreach ($setInfo as $index => &$item) {
            $item['show_img'] = tomedia($item['image']);
        }
        $yue = $_W['wlsetting']['trade']['moneytext'] ? : '余额';
        $jifen = $_W['wlsetting']['trade']['credittext'] ? : '积分';

        $data = Setting::wlsetting_read('userindexset');

        include wl_template('agentset/userindex');
    }
    /**
     * Comment: 代理客服设置
     * Author: wlf
     * Date: 2020/08/10 10:57
     */
    public function agentcustomer()
    {
        global $_W , $_GPC;
        $settings = Setting::agentsetting_read('agentcustomer');
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['customer']);
            Setting::agentsetting_save($base , 'agentcustomer');
            wl_message('更新设置成功！' , web_url('agentset/userset/agentcustomer'));
        }
        include wl_template('agentset/customer');
    }

}
