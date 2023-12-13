<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Sms {
    static function sendSms($smstpl, $param, $mobile) {
        global $_W;
        if ($smstpl['type'] == 'aliyun') {
            include PATH_CORE . 'library/aliyun/Config.php';
            $profile = DefaultProfile::getProfile("cn-hangzhou", $_W['wlsetting']['sms']['note_appkey'], $_W['wlsetting']['sms']['note_secretKey']);
            DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");
            $acsClient = new DefaultAcsClient($profile);
            m('aliyun/sendsmsrequest')->setSignName($_W['wlsetting']['sms']['note_sign']);
            m('aliyun/sendsmsrequest')->setTemplateParam(json_encode($param));
            m('aliyun/sendsmsrequest')->setTemplateCode($smstpl['smstplid']);
            m('aliyun/sendsmsrequest')->setPhoneNumbers($mobile);
            $resp = $acsClient->getAcsResponse(m('aliyun/sendsmsrequest'));
            $res = Util::object_array($resp);
            if ($res['Code'] == 'OK') {
                self::create_apirecord(-1, '', $_W['mid'], $mobile, 1, '阿里云身份验证');
                $recode = array("result" => 1);
            } else {
                $recode = array("result" => 2, "msg" => $res['Message']);
            }
        } else {
            m('alidayu/topclient')->appkey = $_W['wlsetting']['sms']['note_appkey'];
            m('alidayu/topclient')->secretKey = $_W['wlsetting']['sms']['note_secretKey'];
            m('alidayu/smsnum')->setSmsType("normal");
            m('alidayu/smsnum')->setSmsFreeSignName($_W['wlsetting']['sms']['note_sign']);
            m('alidayu/smsnum')->setSmsParam(json_encode($param));
            m('alidayu/smsnum')->setRecNum($mobile);
            m('alidayu/smsnum')->setSmsTemplateCode($smstpl['smstplid']);
            $resp = m('alidayu/topclient')->execute(m('alidayu/smsnum'), '6100e23657fb0b2d0c78568e55a3031134be9a3a5d4b3a365753805');
            $res = Util::object_array($resp);
            if ($res['result']['success'] == 1) {
                self::create_apirecord(-1, '', $_W['mid'], $mobile, 1, '阿里大于身份验证');
                $recode = array("result" => 1);
            } else {
                $recode = array("result" => 2, "msg" => $res['sub_msg']);
            }
        }

        return $recode;
    }

    //替换默认变量
    static function replaceTemplate($str, $datas = array()) {
        foreach ($datas as $d) {
            $str = str_replace('【' . $d['name'] . '】', $d['value'], $str);
        }
        return $str;
    }

    //记录短信发送记录
    static function create_apirecord($sendmid, $sendmobile = '', $takemid, $takemobile, $type, $remark) {
        global $_W;
        $data = array(
            'uniacid'    => $_W['uniacid'],
            'sendmid'    => $sendmid,
            'sendmobile' => $sendmobile,
            'takemid'    => $takemid,
            'takemobile' => $takemobile,
            'type'       => $type,
            'remark'     => $remark,
            'createtime' => time()
        );
        pdo_insert(PDO_NAME . 'apirecord', $data);
    }

    //发送身份验证信息
    static function smsSF($code, $mobile) {
        global $_W;
        //发送海外短信时进行不同处理
        if (strlen($mobile) > 11) {
            if (substr($mobile, 0, 2) == 86) {
                $mobile = substr($mobile, -11);
            } else {
                $_W['wlsetting']['smsset']['dy_sf'] = $_W['wlsetting']['smsset']['dy_sfhw'];
            }
        }
        $smses = pdo_fetch("select * from" . tablename(PDO_NAME . 'smstpl') . "where uniacid=:uniacid and id=:id", array(':uniacid' => $_W['uniacid'], ':id' => $_W['wlsetting']['smsset']['dy_sf']));
        $param = unserialize($smses['data']);
        $datas = array(
            array('name' => '系统名称', 'value' => $_W['wlsetting']['base']['name']),
            array('name' => '版权信息', 'value' => $_W['wlsetting']['base']['copyright']),
            array('name' => '验证码', 'value' => $code)
        );
        foreach ($param as $d) {
            $params[$d['data_temp']] = self::replaceTemplate($d['data_shop'], $datas);
        }
        return self::sendSms($smses, $params, $mobile);
    }
}
