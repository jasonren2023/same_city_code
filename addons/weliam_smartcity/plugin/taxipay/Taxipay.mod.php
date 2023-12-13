<?php
defined('IN_IA') or exit('Access Denied');

class Taxipay {

    static function province_code() {
        return ['京', '津', '冀', '晋', '蒙', '辽', '吉', '黑', '沪', '苏', '浙', '皖', '闽', '赣', '鲁', '豫', '鄂', '湘', '粤', '桂', '琼', '渝', '川', '贵', '云', '藏', '陕', '甘', '青', '宁', '新', '港', '澳'];
    }

    static function a_to_z() {
        return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    }

    static function master_get($id) {
        global $_W;
        return pdo_get('wlmerchant_taxipay_master', array('uniacid' => $_W['uniacid'], 'id' => $id));
    }

    static function master_money_query($arr, $type = 'sum') {
        return pdo_getcolumn(PDO_NAME . 'disdetail', $arr, $type == 'sum' ? 'SUM(price)' : 'COUNT(id)') ?: ($type == 'sum' ? '0.00' : '0');
    }

    static function master_qr_code($id, $type = 'wxapp') {
        #1、基本信息生成
        $master = Taxipay::master_get($id);
        $company = pdo_get(PDO_NAME.'taxipay_company',array('id'=>$master['cid']),array('uniacid','district','city','province'));
        $aid             = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $company['uniacid'] ,
                'areaid'  => $company['district'] ,
                'status'  => 1
        ] , 'aid');
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                    'uniacid' => $company['uniacid'] ,
                    'areaid'  => $company['city'] ,
                    'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                    'uniacid' => $company['uniacid'] ,
                    'areaid'  => $company['province'] ,
                    'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME.'agentusers',array('uniacid'=>$company['uniacid'],'status'=>1),'id');
        }
        $master_member = Member::wl_member_get($master['mid'], ['avatar']);
        $link = h5_url('pages/subPages/buyOrder/index', ['masid' => $id,'head_id' => $master['mid']],'',$aid);
        $path = explode('#/', $link)[1];

        //小程序码名称（带logo）
        $finalPath = 'addons/' . MODULE_NAME . '/data/poster/master_' . $type . '_' . md5($id . $path . $master['name']) . '.png';
        $finalSavePath = IA_ROOT . '/' . $finalPath;

        if (!file_exists($finalSavePath)) {
            if ($type == 'wxapp') {
                $TqrCode = WeApp::getQrCode($path, md5($finalPath . $type) . '.png', ['width' => 900]);
                if ($TqrCode['errno'] == 0 && is_array($TqrCode)) {
                    return $TqrCode;
                }
            } else {
                $TqrCode = Poster::qrcodeimg($link, md5($finalPath . $type));
            }
            set_time_limit(0);
            @ini_set('memory_limit', '512M');
            $target = imagecreatetruecolor(1765, 2558);
            imagecopy($target, Tools::createImage(tomedia('addons/' . MODULE_NAME . '/h5/resource/poster/master_bg.png')), 0, 0, 0, 0, 1765, 2558);
            if ($type == 'wxapp') {
                imagecopy($target, Tools::createImage(tomedia($TqrCode)), 432, 640, 0, 0, 900, 900);
            }else{
                imagecopy($target, Tools::createImage(tomedia($TqrCode)), 422, 630, 0, 0, 950, 950);
            }
            $target = Tools::mergeText($target, ['color' => '#FFFFFF', 'size' => 60, 'top' => 420, 'left' => 432, 'width' => 900, 'align' => 'center', 'line' => 1], $master['name']);
            imagepng($target, $finalSavePath);
            imagedestroy($target);
        }
        return ['url' => $type == 'wxapp' ? $path : $link, 'img' => !empty($finalPath) ? tomedia($finalPath) : ''];
    }

    static function adv_shows($cid = 0){
        global $_W;
        $showadv = [];
        $where = array('uniacid' => $_W['uniacid'], 'status' => 1);
        if($cid>0){
            $idarray = [];
            $ids = pdo_getall('wlmerchant_taxipay_advcids',array('cid' => $cid),array('advid'));
            foreach ($ids as $id){
                $idarray[] = $id['advid'];
            }
            $where['id'] = $idarray;
        }
        $advs = pdo_getall('wlmerchant_taxipay_adv', $where, '', '', 'sort DESC');
        foreach ($advs as &$adv) {
            $user_count = pdo_getcolumn('wlmerchant_taxipay_advlog', array('mid' => $_W['mid'], 'advid' => $adv['id']), 'SUM(times)');
            $cost_use = $user_count * $adv['cost_one'];
            if ($cost_use >= $adv['cost']) {
                unset($adv);
                continue;
            }
            $adv['thumb'] = tomedia($adv['thumb']);
            $showadv[] = $adv;
            break;
        }
        return $showadv;
    }

    static function adv_log($adv, $master) {
        global $_W;
        $adv = $adv[0];
        $log = pdo_get('wlmerchant_taxipay_advlog', array('mid' => $_W['mid'], 'advid' => $adv['id']), array('id', 'times', 'lasttime'));
        $today = strtotime(date("Y-m-d"), time());
        $end = $today + 60 * 60 * 24;
        if ($today < $log['lasttime'] && $log['lasttime'] < $end) {
            return false;
        }

        //广告记录
        $data = ['lasttime' => TIMESTAMP];
        if (!empty($log)) {
            $data['times'] = $log['times'] + 1;
            pdo_update('wlmerchant_taxipay_advlog', $data, ['id' => $log['id']]);
        } else {
            $data['uniacid'] = $_W['uniacid'];
            $data['mid'] = $_W['mid'];
            $data['advid'] = $adv['id'];
            $data['times'] = 1;
            $data['firsttime'] = TIMESTAMP;
            pdo_insert('wlmerchant_taxipay_advlog', $data);
        }

        //广告佣金分配
        $dismoney = $adv['cost_one'];
        $company = pdo_get('wlmerchant_taxipay_company', array('uniacid' => $_W['uniacid'], 'id' => $master['cid']));
        if (!empty($company['scale'])) {
            $commoney = $company['scale'] / 100 * $adv['cost_one'];
            $dismoney = $adv['cost_one'] - $commoney;
            pdo_update('wlmerchant_taxipay_company', ['money' => $company['money'] + $commoney], ['id' => $company['id']]);
        }

        pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney = dismoney + {$dismoney}, nowmoney = nowmoney + {$dismoney} WHERE id = {$master['disid']}");
        $onenowmoney = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $master['disid']), 'nowmoney');
        Distribution::adddisdetail(0, $master['mid'], $_W['mid'], 1, $dismoney, 'taxipayadv', 0, '出租车广告分润', $onenowmoney);
    }

    //支付回调
    static function payTaxipayOrderNotify($params) {
        global $_W;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']));
        if ($order['status'] == 0 || $order['status'] == 5) {
            //更新订单
            $data = array('status' => $params['result'] == 'success' ? 3 : 0);
            $data['paytype'] = $params['type'];
            $data['issettlement'] = 1;
            if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
            $data['paytime'] = TIMESTAMP;
            $data['deliverytype'] = 1;
            $res = pdo_update(PDO_NAME . 'order', $data, array('id' => $order['id']));
            //更新分销商金额
            if ($res) {
                pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney = dismoney + {$order['price']}, nowmoney = nowmoney + {$order['price']} WHERE id = {$order['card_id']}");
                $onenowmoney = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $order['card_id']), 'nowmoney');
                Distribution::adddisdetail($order['id'], $order['specid'], $order['mid'], 1, $order['price'], 'taxipay', 0, '出租车买单支付', $onenowmoney, $order['id']);
                Distribution::moneyNotice($order['mid'], 'taxipay', $order['id'], $order['card_id'], 0, 2);
                Distribution::checkup($order['card_id']);
            }
        }
    }
}