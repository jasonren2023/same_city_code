<?php
defined('IN_IA') or exit('Access Denied');

class Diyposter {

    static function getgzqrcode($gid, $type) {
        global $_W;
        //关注二维码上传
        $qrid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'sid' => $gid, 'type' => 2, 'remark' => $type,'status' => 1), 'qrid');
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
            Weixinqrcode::createkeywords('商品关注二维码:Diyposter', 'weliam_smartcity_diyposter');
            $result = Weixinqrcode::createqrcode('商品关注二维码:Diyposter', 'weliam_smartcity_diyposter', 2, 1, -1, $type);
            if (!is_error($result)) {
                $qrid = $result;
                pdo_update(PDO_NAME . 'qrcode', array('sid' => $gid), array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
            }
        }
        if (empty($qrurl)) {
            $qrurl = pdo_get('qrcode', array('id' => $qrid, 'uniacid' => $_W['uniacid']), array('url', 'ticket'));
        }
        return $qrurl;
    }

    static function Processor($message) {
        global $_W;
        if (strtolower($message['msgtype']) == 'event') {
            //获取数据
            $returnmess = array();
            $qrid = Weixinqrcode::get_qrid($message);
            $qrinfo = self::geturlinfo($qrid);

            if(!empty($qrinfo['desc'])){
                $desc = $qrinfo['desc'];
            }else{
                if(!$qrinfo['title']) $qrinfo['title'] = $_W['wlsetting']['base']['name'];
                $desc = '欢迎使用'.$_W['wlsetting']['base']['name'];
                if(!$qrinfo['imgurl']) $qrinfo['imgurl'] = $_W['wlsetting']['base']['logo'];
                if(!$qrinfo['path'])  $qrinfo['path'] = h5_url('pages/mainPages/index/index');
            }
            //发送消息
            if ($_W['wlsetting']['diyposter']['replytype'] == 1) {
                $returnmess = array('title' => urlencode($qrinfo['title']), 'appid' => $_W['wlsetting']['wxapp_config']['appid'], 'path' => tomedia($qrinfo['imgurl']), 'pagepath' => $qrinfo['path']);
                Weixinqrcode::send_wxapp($returnmess, $message);
            } else {
                $returnmess = array(array('title' => urlencode($qrinfo['title']), 'description' => urlencode($desc), 'picurl' => tomedia($qrinfo['imgurl']), 'url' => $qrinfo['url']));
                Weixinqrcode::send_news($returnmess, $message);
            }
        }
    }

    static function geturlinfo($qrid) {
        global $_W;
        $qrcode = pdo_get(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'qrid' => $qrid,'model' => 1), array('sid', 'remark'));
        $qrarray = explode(":", $qrcode['remark']);
        if (p('distribution')) {
            Distribution::addJunior($qrarray[0], $_W['wlmember']['id']);
        }
        //获取商品信息和链接
        if (strexists($qrcode['remark'], 'rush')) {
            $rushgoods = Rush::getSingleActive($qrcode['sid'], 'name,thumb,aid');
            $title = $rushgoods['name'];
            $imgurl = $rushgoods['thumb'];
            $path = 'pages/subPages/goods/index?type=1&id=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url('pages/subPages/goods/index', ['type' => 1, 'id' => $qrcode['sid'], 'head_id' => $qrarray[0]]);
        }
        if (strexists($qrcode['remark'], 'wlcoupon')) {
            $wlCoupon = wlCoupon::getSingleCoupons($qrcode['sid'], 'title,logo,aid');
            $title = $wlCoupon['title'];
            $imgurl = $wlCoupon['logo'];
            $path = 'pages/subPages/goods/index?type=5&id=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url('pages/subPages/goods/index', ['type' => 5, 'id' => $qrcode['sid'], 'head_id' => $qrarray[0]]);
        }
        if (strexists($qrcode['remark'], 'wlfightgroup')) {
            $group = Wlfightgroup::getSingleGood($qrcode['sid'], 'name,logo,aid');
            $title = $group['name'];
            $imgurl = $group['logo'];
            $path = 'pages/subPages/goods/index?type=3&id=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url('pages/subPages/goods/index', ['type' => 3, 'id' => $qrcode['sid'], 'head_id' => $qrarray[0]]);
        }
        if (strexists($qrcode['remark'], 'groupon')) {
            $groupon = pdo_get('wlmerchant_groupon_activity', array('id' => $qrcode['sid']), array('name', 'thumb', 'aid'));
            $title = $groupon['name'];
            $imgurl = $groupon['thumb'];
            $path = 'pages/subPages/goods/index?type=2&id=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url('pages/subPages/goods/index', ['type' => 2, 'id' => $qrcode['sid'], 'head_id' => $qrarray[0]]);
        }
        if (strexists($qrcode['remark'], 'bargain')) {
            $bargain = pdo_get('wlmerchant_bargain_activity', array('id' => $qrcode['sid']), array('name', 'thumb', 'aid'));
            $title = $bargain['name'];
            $imgurl = $bargain['thumb'];
            $path = 'pages/subPages/goods/index?type=7&id=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url('pages/subPages/goods/index', ['type' => 7, 'id' => $qrcode['sid'], 'head_id' => $qrarray[0]]);
        }
        //名片关注二维码
        if (strexists($qrcode['remark'], 'citycard')) {
            $bargain      = pdo_get('wlmerchant_citycard_lists' , ['id' => $qrcode['sid']] , [
                'name' ,
                'logo' ,
                'company' ,
                'branch' ,
                'position' ,
                'desc' ,
                'one_class' ,
                'two_class',
                'mid',
                'uniacid',
                'aid'
            ]);
            $onelevelname  = pdo_getcolumn(PDO_NAME . 'citycard_cates' , ['id' => $bargain['one_class']] , 'name');
            $twolevelname  = pdo_getcolumn(PDO_NAME . 'citycard_cates' , ['id' => $bargain['two_class']] , 'name');
            $_W['aid'] = $bargain['aid'];
            $_W['uniacid'] = $bargain['uniacid'];
            $set           = Setting::agentsetting_read('citycard');
            $title = $set['share_detail_title'];
            $desc  = $set['share_detail_desc'];
            $imgurl   = $set['share_detail_image'];
            $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$bargain['mid']),'nickname');
            $time     = date("Y-m-d H:i:s" , time());
            $sysname  = $_W['wlsetting']['base']['name'];
            if ($title) {
                $title = str_replace('[昵称]' , $nickname , $title);
                $title = str_replace('[时间]' , $time , $title);
                $title = str_replace('[系统名称]' , $sysname , $title);
                $title = str_replace('[名片名称]' , $bargain['name'] , $title);
                $title = str_replace('[公司]' , $bargain['company'] , $title);
                $title = str_replace('[部门]' , $bargain['branch'] , $title);
                $title = str_replace('[职务]' , $bargain['position'] , $title);
                $title = str_replace('[介绍]' , $bargain['desc'] , $title);
                $title = str_replace('[一级分类]' , $onelevelname , $title);
                $title = str_replace('[二级分类]' , $twolevelname , $title);
            }
            if ($desc) {
                $desc = str_replace('[昵称]' , $nickname , $desc);
                $desc = str_replace('[时间]' , $time , $desc);
                $desc = str_replace('[系统名称]' , $sysname , $desc);
                $desc = str_replace('[名片名称]' , $bargain['name'] , $desc);
                $desc = str_replace('[公司]' , $bargain['company'] , $desc);
                $desc = str_replace('[部门]' , $bargain['branch'] , $desc);
                $desc = str_replace('[职务]' , $bargain['position'] , $desc);
                $desc = str_replace('[介绍]' , $bargain['desc'] , $desc);
                $desc = str_replace('[一级分类]' , $onelevelname , $desc);
                $desc = str_replace('[二级分类]' , $twolevelname , $desc);
            }else{
                $desc = '快来联系我吧~';
            }
            $imgurl   = $imgurl ? $imgurl : $bargain['logo'];
            $path = 'pages/subPages/businesscard/carddetail/carddetail?cardid=' . $qrcode['sid'] . "&head_id=" . $qrarray[0];
            $url = h5_url($path);
        }
        if (strexists($qrcode['remark'], 'yellow')) {
            $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $qrcode['sid']),array('name','logo','desc'));
            $title = $yellowpage['name'];
            $desc  = $yellowpage['desc'];
            $imgurl = tomedia($yellowpage['logo']);
            $path = "pages/subPages2/phoneBook/logistics/logistics?id={$qrcode['sid']}";//基本路径，也是小程序路径
            $url = h5_url($path);
        }
        if (strexists($qrcode['remark'], 'draw')) {
            $draw = pdo_get('wlmerchant_draw',array('id' => $qrcode['sid']),array('title','share_image'));
            $title = $draw['title'];
            $desc  = '点击抽奖';
            $imgurl = tomedia($draw['share_image']);
            $path = "pages/subPages2/drawGame/drawGame?id={$qrcode['sid']}";//基本路径，也是小程序路径
            $url = h5_url($path);
        }
        if (strexists($qrcode['remark'], 'activity')) {
            $activity = pdo_get('wlmerchant_activitylist',array('id' => $qrcode['sid']),array('title','thumb'));
            $title = $activity['title'];
            $desc  = '点击参与';
            $imgurl = tomedia($activity['thumb']);
            $path = "pages/subPages2/coursegoods/coursegoods?id={$qrcode['sid']}";//基本路径，也是小程序路径
            $url = h5_url($path);
        }
        if (strexists($qrcode['remark'], 'dating')) {
            $activity = pdo_get(PDO_NAME.'dating_matchmaker',['id' => $qrcode['sid']],[
                'nickname',
                'avatar',
                'describe',
                'mid'
            ]);
            $title    = $activity['nickname'];
            $desc     = $activity['describe'];
            $imgurl   = tomedia($activity['avatar']);
            $path     = "pages/mainPages/index/diypage?type=16&head_id={$activity['mid']}";//基本路径，也是小程序路径
            $url      = h5_url($path);
        }

        return array('title' => $title,'desc'=>$desc,'imgurl' => $imgurl, 'url' => $url, 'qrcode' => $qrcode, 'path' => $path);
    }
}