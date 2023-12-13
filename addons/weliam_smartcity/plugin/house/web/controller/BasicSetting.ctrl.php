<?php
defined('IN_IA') or exit('Access Denied');

class BasicSetting_WeliamController {

    public function basicsetting() {
        global $_W, $_GPC;
        $data = Setting::agentsetting_read('house');

        $viparray1 = unserialize($data['viparray1']);
        $viparray2 = unserialize($data['viparray2']);
        $viparray3 = unserialize($data['viparray3']);

        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");

        if (checksubmit('submit')) {
            $data = $_GPC['data'];

            $viparray = [];
            $vipleid = $_GPC['vipleid'];
            $vipprice = $_GPC['vipprice'];
            foreach($vipleid as $key => $vle){
                $vipa = sprintf("%.2f",$vipprice[$key]);
                $viparray[$vle] = $vipa;
            }
            $data['viparray1'] = serialize($viparray);

            $viparray2 = [];
            $vipleid2 = $_GPC['vipleid2'];
            $vipprice2 = $_GPC['vipprice2'];
            foreach($vipleid2 as $key2 => $vle2){
                $vipa2 = sprintf("%.2f",$vipprice2[$key2]);
                $viparray2[$vle2] = $vipa2;
            }
            $data['viparray2'] = serialize($viparray2);

            $viparray3 = [];
            $vipleid3 = $_GPC['vipleid3'];
            $vipprice3 = $_GPC['vipprice3'];
            foreach($vipleid3 as $key3 => $vle3){
                $vipa3 = sprintf("%.3f",$vipprice3[$key3]);
                $viparray3[$vle3] = $vipa3;
            }
            $data['viparray3'] = serialize($viparray3);






//            if($data['newhouse_price']['vip_price'] < 0.01 || $data['newhouse_price']['price'] < 0.01 || $data['renting_price']['vip_price'] < 0.01 || $data['renting_price']['price'] < 0.01 || $data['oldhouse_price']['vip_price'] < 0.01 || $data['oldhouse_price']['price'] < 0.01){
//                wl_message('价格金额不能为0');
//            }


//            $day = $_GPC['day'];
//            $price = $_GPC['price'];
//            $paramids = array();
//            $len = count($day);
//            for ($k = 0; $k < $len; $k++) {
//                if($day[$k]>0){
//                    $paramids[$k]['day'] = sprintf("%.0f",$day[$k]);
//                    $paramids[$k]['price'] = sprintf("%.2f",$price[$k]);
//                    // $paramids[$k]['vipprice'] = sprintf("%.2f",$vipprice[$k]);
//                    if($paramids[$k]['price'] < 0.01){
//                        wl_message('价格金额不能为0');
//                    }
//                }
//            }
//            $data['newhouse_price'] = $paramids;


//            $day = $_GPC['renting_day'];
//            $price = $_GPC['renting_price'];
//            $paramids = array();
//            $len = count($day);
//            for ($k = 0; $k < $len; $k++) {
//                if($day[$k]>0){
//                    $paramids[$k]['day'] = sprintf("%.0f",$day[$k]);
//                    $paramids[$k]['price'] = sprintf("%.2f",$price[$k]);
//                    // $paramids[$k]['vipprice'] = sprintf("%.2f",$vipprice[$k]);
//                    if($paramids[$k]['price'] < 0.01){
//                        wl_message('价格金额不能为0');
//                    }
//                }
//            }
//            $data['renting_price'] = $paramids;


//            $day = $_GPC['oldhouse_day'];
//            $price = $_GPC['oldhouse_price'];
//            $paramids = array();
//            $len = count($day);
//            for ($k = 0; $k < $len; $k++) {
//                if($day[$k]>0){
//                    $paramids[$k]['day'] = sprintf("%.0f",$day[$k]);
//                    $paramids[$k]['price'] = sprintf("%.2f",$price[$k]);
//                    // $paramids[$k]['vipprice'] = sprintf("%.2f",$vipprice[$k]);
//                    if($paramids[$k]['price'] < 0.01){
//                        wl_message('价格金额不能为0');
//                    }
//                }
//            }
//            $data['oldhouse_price'] = $paramids;

            Setting::agentsetting_save($data, 'house');
            wl_message('设置成功', web_url('house/BasicSetting/basicsetting'));
        }
        include wl_template('setting/basicsetting');
    }

    public function newhouseprice() {
        include wl_template('setting/newhouseprice');
    }

    public function rentingprice() {
        include wl_template('setting/rentingprice');
    }
    public function oldhouseprice() {
        include wl_template('setting/oldhouseprice');
    }
}