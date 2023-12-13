<?php
defined('IN_IA') or exit('Access Denied');

class Subposter
{

    static function getqrcode($mid)
    {
        global $_W;
        $qrid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'sid' => $mid, 'type' => 2, 'remark' => 'subposter'), 'qrid');
        if ($qrid) {
            $qrurl = pdo_get('qrcode', array('id' => $qrid, 'uniacid' => $_W['uniacid']), array('url', 'ticket','expire','createtime'));
        }
        //判断是否存在二维码或者二维码是否已经过期
        if ($qrurl['expire'] > 0) {
            $createTime = $qrurl['createtime'];//建立时间  秒
            $expireTime = $qrurl['expire'];//有效时间  秒
            $endTime = ($createTime + $expireTime) - time();//距离结束时间还有多少时间  小于1则已经过期
        } else {
            $endTime = 1;
        }
        if (empty($qrid) || empty($qrurl) || $endTime < 1 ) {
            if ($qrid > 0) {
                pdo_update('qrcode', array('status' => 2), array('id' => $qrid));
                pdo_update(PDO_NAME . 'qrcode', array('status' => 2), array('qrid' => $qrid));
            }
            Weixinqrcode::createkeywords('倡议关注二维码:Subposter', 'weliam_smartcity_subposter');
            $result = Weixinqrcode::createqrcode('倡议关注二维码:Subposter', 'weliam_smartcity_subposter', 2, 1, -1, 'subposter');
            if (!is_error($result)) {
                $qrid = $result;
                pdo_update(PDO_NAME . 'qrcode', array('sid' => $mid), array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
            }
        }
        if (empty($qrurl)) {
            $qrurl = pdo_get('qrcode', array('id' => $qrid, 'uniacid' => $_W['uniacid']), array('url', 'ticket'));
        }
        return $qrurl;
    }

    static function getsort($mid, $invite_id = 0)
    {
        global $_W;
        if (empty($mid)) {
            return intval($_W['wlsetting']['subposter']['number']);
        }
        $record = pdo_get('wlmerchant_subposter_log', array('mid' => $mid, 'uniacid' => $_W['uniacid']), array('id', 'scan_times', 'sort'));
        if (empty($record)) {
            $sort = pdo_getcolumn('wlmerchant_subposter_log', array('uniacid' => $_W['uniacid']), 'COUNT(id)');
            $record = array(
                'uniacid' => $_W['uniacid'],
                'mid' => $mid,
                'invite_id' => $invite_id,
                'createtime' => time(),
                'sort' => $sort + 1
            );
            pdo_insert('wlmerchant_subposter_log', $record);
        }
        if (!empty($invite_id)) {
            pdo_update('wlmerchant_subposter_log', ['scan_times +=' => 1], ['mid' => $invite_id, 'uniacid' => $_W['uniacid']]);
        }
        return $record['sort'] + intval($_W['wlsetting']['subposter']['number']);
    }

    static function gettext($numbers) {
        global $_W;
        if (empty($_W['wlsetting']['subposter']['reply'])) {
            $_W['wlsetting']['subposter']['reply'] = '[昵称]感谢参与！您是第[人数]个倡议者，保存下列图片，转发给朋友们，提高全民防疫意识，从我做起！';
        }
        $datas = array(
            array('name' => '昵称', 'value' => $_W['wlmember']['nickname']),
            array('name' => '人数', 'value' => $numbers)
        );
        foreach ($datas as $d) {
            $_W['wlsetting']['subposter']['reply'] = str_replace('[' . $d['name'] . ']', $d['value'], $_W['wlsetting']['subposter']['reply']);
        }

        return $_W['wlsetting']['subposter']['reply'];
    }

    static function Processor($message)
    {
        global $_W;
        $invite_id = 0;
        if (!empty($message['scene'])) {
            $qrid = pdo_getcolumn('qrcode', array('qrcid' => $message['scene'], 'uniacid' => $_W['uniacid']), 'id');
            $invite_id = pdo_getcolumn(PDO_NAME . 'qrcode', array('qrid' => $qrid, 'uniacid' => $_W['uniacid']), 'sid');
        }
        $numbers = self::getsort($_W['mid'], $invite_id);
        $text = self::gettext($numbers);

        Weixinqrcode::send_text($text, $message, 0);
        $poster = Poster::createSubPoster($numbers);
        Weixinqrcode::send_image($poster, $message);
    }
}