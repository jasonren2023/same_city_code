<?php
defined('IN_IA') or exit('Access Denied');

class Storeqr {

    static function get_storeqr($sid) {
        global $_W;
        $merchant = pdo_get(PDO_NAME . 'merchantdata', ['uniacid' => $_W['uniacid'], 'id' => $sid], ['id', 'cardsn']);
        if (empty($merchant)) {
            return error(-1, '商户不存在，请检查后重试');
        }
        if (!empty($merchant['cardsn'])) {
            $qrid = pdo_getcolumn(PDO_NAME . 'qrcode', array('sid' => $sid, 'status' => 2), 'qrid');
        } else {
            $qrid = self::create_storeqr($sid);
        }
        $ticket = pdo_getcolumn('qrcode', array('id' => $qrid), 'ticket');
        if(!$ticket){
            $qrid = self::create_storeqr($sid);
            $ticket = pdo_getcolumn('qrcode', array('id' => $qrid), 'ticket');
        }

        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
        return $url;
    }

    static function create_storeqr($sid) {
        global $_W;
        Weixinqrcode::createkeywords('商户关注二维码:Storeqr', 'weliam_merchant_storeqr');
        $qrid = Weixinqrcode::createqrcode('商户关注二维码:Storeqr', 'weliam_merchant_storeqr', 0, 2, -1, '商户关注二维码');
        if (!is_error($qrid)) {
            $qrcode = pdo_get(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
            pdo_update(PDO_NAME . 'qrcode', array('sid' => $sid, 'status' => 2), array('id' => $qrcode['id']));
            pdo_update(PDO_NAME . 'merchantdata', array('cardsn' => $qrcode['cardsn']), array('id' => $sid));
        }
        return $qrid;
    }

    static function Processor($message) {
        global $_W;
        if (strtolower($message['msgtype']) == 'event') {
            //获取数据
            $returnmess = array();
            $qrid = Weixinqrcode::get_qrid($message);
            $data = self::get_member($message, $qrid);
            $card = $data['card'];
            $storedata = $data['store'];
            $aid = $storedata['aid'];
            $member = $data['member'];
            $base = Setting::wlsetting_read('storeqr');
            //判断是否为二次请求
            $onlyKey = md5(serialize($message));
            $caCheOnlyKey = Cache::getCache('Processor','storeqr');
            if($onlyKey == $caCheOnlyKey) return false;//阻断重复请求
            Cache::setCache('Processor','storeqr',$onlyKey);//记录请求信息

            //二维码未绑定
            if ($card['status'] == 1) {
                $returnmess[] = array('title' => urlencode('店铺快速入驻'), 'description' => '', 'picurl' => tomedia($base['enterfast']), 'url' => h5_url('pages/mainPages/Settled/Settled'));
                Weixinqrcode::send_news($returnmess, $message);
            }
            //二维码已绑定
            if ($card['status'] == 2) {
                //商家信息
                $adminmid = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $card['sid'], 'ismain' => 1), 'mid');

                if($_W['wlsetting']['diyposter']['replytype'] == 1){
                    $path = 'pages/mainPages/store/index?sid='.$storedata['id'].'&head_id='.$adminmid;
                    $returnmess = array('title' => urlencode($storedata['storename']), 'appid' => $_W['wlsetting']['wxapp_config']['appid'], 'path' => tomedia($storedata['logo']), 'pagepath' => $path);
                    Weixinqrcode::send_wxapp($returnmess, $message);
                }else{
                    $returnmess[] = array('title' => urlencode($storedata['storename']), 'description' => urlencode($storedata['address']), 'picurl' => tomedia($storedata['logo']), 'url' => h5_url('pages/mainPages/store/index', ['sid' => $storedata['id'],'headid' => $adminmid]));
                    Weixinqrcode::send_news($returnmess, $message);
                }
            }
            //二维码已禁止
            if ($card['status'] == 3) {
                Weixinqrcode::send_text('抱歉，此二维码已失效！', $message);
            }
        }
    }

    private static function get_member($message, $qrid) {
        global $_W;
        $card = pdo_get(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
        $_W['aid'] = $card['aid'];
        $member = pdo_get(PDO_NAME . 'member', array('uniacid' => $_W['uniacid'], 'openid' => $message['from']), array('id'));
//        if (empty($member['id'])) {
//            $member = array(
//                'uniacid'    => $_W['uniacid'],
//                'openid'     => $message['from'],
//                'createtime' => time()
//            );
//            pdo_insert(PDO_NAME . 'member', $member);
//            $member['id'] = pdo_insertid();
//        }
        $member['storeid'] = pdo_getcolumn(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'mid' => $member['id'], 'status' => 2, 'enabled' => 1), 'storeid');
        if ($card['sid'] && $member['id']) {
            $storedata = pdo_get(PDO_NAME . 'merchantdata', array('uniacid' => $_W['uniacid'], 'id' => $card['sid']), array('id', 'storename', 'aid', 'logo', 'address', 'payonline'));
            //给店铺增加客户
            Store::addFans($storedata['id'], $member['id'], 3);
            //给店长增加下线
            if (p('distribution')) {
                $adminmid = pdo_getcolumn(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $card['sid'], 'ismain' => 1), 'mid');
                if ($adminmid) {
                    Distribution::addJunior($adminmid, $member['id']);
                }
            }
        }
        return array('card' => $card, 'store' => $storedata, 'member' => $member);
    }

}
