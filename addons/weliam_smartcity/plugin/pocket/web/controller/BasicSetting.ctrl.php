<?php
defined('IN_IA') or exit('Access Denied');

class BasicSetting_WeliamController {

    public function basicsetting() {
        global $_W, $_GPC;
        $data = Setting::agentsetting_read('pocket');
        $vip_level = unserialize($data['vip_level']);
        $distri = Setting::wlsetting_read('distribution');
        if ($data['noticeopenid']) {
            $noticename = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $data['noticeopenid']), 'nickname');
        }
        //一卡通等级
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $advs = unserialize($data['advs']);


        if (checksubmit('submit')) {
            $data = $_GPC['data'];
            $status = $_GPC['status'];
            $search = $_GPC['search'];
            $pass = $_GPC['passstatus'];
            $free = $_GPC['freestatus'];
            $auto = $_GPC['automobile'];
            $listorder = $_GPC['listorder'];
            $data['search_float'] = $_GPC['search_float'];
            $data['search_bgColor'] = $_GPC['search_bgColor'];
            $data['search'] = $search;
            $data['status'] = $status;
            $data['passstatus'] = $pass;
            $data['freestatus'] = $free;
            $data['locastatus'] = $_GPC['locastatus'];
            $data['automobile'] = $auto;
            $data['listorder'] = $listorder;
            $data['is_openRed'] = $_GPC['is_openRed'];
            $data['comment_reply'] = $_GPC['comment_reply'] ? : 0;
            $data['vip_show'] = $_GPC['vip_show'] ? : 0;
            $data['storesettle'] = $_GPC['storesettle'] ? : 0;
            $data['videoupload'] = $_GPC['videoupload'] ? : 0;
            $data['imgupload'] = $_GPC['imgupload'] ? : 0;
            $data['audioupload'] = $_GPC['audioupload'] ? : 0;

            $data['credit_like'] = intval($data['credit_like']);
            $data['credit_comment'] = intval($data['credit_comment']);
            $data['credit_follow'] = intval($data['credit_follow']);
            $data['credit_day'] = intval($data['credit_day']);

            $day = $_GPC['day'];
            $price = $_GPC['price'];
            $vipprice = $_GPC['vipprice'];
            $paramids = array();
            $len = count($day);
            for ($k = 0; $k < $len; $k++) {
                if($day[$k]>0){
                    $paramids[$k]['day'] = sprintf("%.0f",$day[$k]);
                    $paramids[$k]['price'] = sprintf("%.2f",$price[$k]);
                    $paramids[$k]['vipprice'] = sprintf("%.2f",$vipprice[$k]);
                    if($paramids[$k]['price'] < 0.01){
                        $paramids[$k]['price'] = 0;
                    }
                    if($paramids[$k]['vipprice'] < 0.01){
                        $paramids[$k]['vipprice'] = 0;
                    }
                }
            }
            $data['price'] = $paramids;

            $minute = $_GPC['minute'];
            $integral = $_GPC['integral'];
            $videoc = array();
            $len = count($minute);
            for ($k = 0; $k < $len; $k++) {
                $videoc[$k]['minute'] = sprintf("%.0f",$minute[$k]);
                $videoc[$k]['integral'] = sprintf("%.0f",$integral[$k]);
            }
            $data['videocredit'] = $videoc;


            //幻灯片
            $advlogo = $_GPC['advlogo'];
            $advlink = $_GPC['advlink'];
            $advs = [];
            if(!empty($advlogo)){
                foreach($advlogo as $ak => $logo){
                    $adv = [];
                    $adv['link'] = $advlink[$ak];
                    $adv['thumb'] = $advlogo[$ak];
                    $advs[] = $adv;
                }
            }
            $data['advs'] = serialize($advs);


            $data['fullprice'] = sprintf("%.2f",$data['fullprice']);
            $data['fullvip'] = sprintf("%.2f",$data['fullvip']);

            Setting::agentsetting_save($data, 'pocket');
            wl_message('设置成功', web_url('pocket/BasicSetting/basicsetting'));
        }
        include wl_template('pocket/basicsetting');
    }

    public function dayandprice() {
        include wl_template('pocket/dayandprice');
    }

    public function redEnvelopes() {
        include wl_template('pocket/redEnvelopes');
    }
    public function videocredit() {
        include wl_template('pocket/videocredit');
    }

    /**
     * Comment: 幻灯片div
     * Author: wlf
     */
    public function advinfo(){
        global $_W,$_GPC;
        $kw = $_GPC['kw'];

        include wl_template("pocket/advinfo");
    }
}