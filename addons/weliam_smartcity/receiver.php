<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/core/common/defines.php";
require_once PATH_CORE . "common/autoload.php";
Func_loader::core('global');

class Weliam_smartcityModuleReceiver extends WeModuleReceiver {

    public function receive() {
        global $_W;
        $_W['wlsetting'] = Setting::wlsetting_load();
        $message = $this->message;
        file_put_contents(PATH_DATA . "receiver_qr.log", var_export($message, true) . PHP_EOL, FILE_APPEND);
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


        if (!empty($message['scene'])) {
            $name = pdo_getcolumn('qrcode', array('scene_str' => $message['scene'], 'uniacid' => $_W['uniacid']), 'name');
            $names = explode(':', $name);
            $plugin = (isset($names[1]) ? $names[1] : '');
            if (!empty($plugin)) {
                $plugin::Processor($message);
            }
        } else {
            $scanrecord = pdo_fetch("SELECT cardid,scantime,type,url FROM "
                                    . tablename('wlmerchant_halfcard_qrscan')
                                    . " WHERE uniacid = {$_W['uniacid']} AND openid = '{$message['from']}' order by id desc");
            if (!empty($scanrecord) && ($scanrecord['scantime'] + 120) > time()) {
                //一卡通实卡
                if (empty($scanrecord['type'])) {
                    $card = pdo_get('wlmerchant_halfcard_realcard', array('uniacid' => $_W['uniacid'], 'id' => $scanrecord['cardid']));
                    if (!empty($card)) {
                        //一卡通未绑定
                        if ($card['status'] == 1) {
                            $setting = Setting::wlsetting_read('halfcard');
                            $imgurl = $setting['cardimg'] ? $setting['cardimg'] : URL_MODULE . 'plugin/halfcard/app/resource/images/cord-bg.jpg';
                            $returnmess = array(array('title' => urlencode("点击立即激活此卡"), 'description' => urlencode('激活此卡'), 'picurl' => tomedia($imgurl), 'url' => app_url('halfcard/halfcard_app/realcard', array('cardsn' => $card['cardsn'], 'salt' => $card['salt']))));
                            Weixinqrcode::send_news($returnmess, $message);
                        }
                        //一卡通已绑定
                        if ($card['status'] == 2) {
                            Weixinqrcode::send_text('关注成功，请重新扫描二维码操作', $message);
                        }
                        //一卡通已禁止
                        if ($card['status'] == 3) {
                            Weixinqrcode::send_text('抱歉，此卡已失效！', $message);
                        }
                    }
                } else {
                    switch ($scanrecord['type']) {
                        case 'rush':
                            $rushgoods = Rush::getSingleActive($scanrecord['cardid'] , 'name,thumb');
                            $title     = $rushgoods['name'];
                            $imgurl    = $rushgoods['thumb'];
                            $url       = h5_url('pages/subPages/goods/index' , [
                                'type' => 1 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            $desc      = '手快有,手慢无...';
                            break;
                        case 'wlcoupon':
                            $wlCoupon = wlCoupon::getSingleCoupons($scanrecord['cardid'] , 'title,logo');
                            $title    = $wlCoupon['title'];
                            $imgurl   = $wlCoupon['logo'];
                            $url      = h5_url('pages/subPages/goods/index' , [
                                'type' => 5 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'wlfightgroup':
                            $group  = Wlfightgroup::getSingleGood($scanrecord['cardid'] , 'name,logo');
                            $title  = $group['name'];
                            $imgurl = $group['logo'];
                            $url    = h5_url('pages/subPages/goods/index' , [
                                'type' => 3 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'wlgroupdetail':
                            $group  = pdo_get('wlmerchant_fightgroup_group' , ['id' => $scanrecord['cardid']] , ['goodsid']);
                            $goods  = Wlfightgroup::getSingleGood($group['goodsid'] , 'name,logo');
                            $title  = $goods['name'];
                            $imgurl = $goods['logo'];
                            $url    = h5_url('pages/subPages/group/assemble/assemble' , ['group_id' => $scanrecord['cardid']]);
                            break;
                        case 'groupon':
                            $groupon = pdo_get('wlmerchant_groupon_activity' , ['id' => $scanrecord['cardid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title   = $groupon['name'];
                            $imgurl  = $groupon['thumb'];
                            $url     = h5_url('pages/subPages/goods/index' , [
                                'type' => 2 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'bargain':
                            $groupon = pdo_get('wlmerchant_bargain_activity' , ['id' => $scanrecord['cardid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title   = $groupon['name'];
                            $imgurl  = $groupon['thumb'];
                            $url     = h5_url('pages/subPages/goods/index' , [
                                'type' => 7 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'helpBargain':
                            $bargainuser = pdo_get('wlmerchant_bargain_userlist' , ['id' => $scanrecord['cardid']] , ['activityid']);
                            $barActivity = pdo_get('wlmerchant_bargain_activity' , ['id' => $bargainuser['activityid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title       = $barActivity['name'];
                            $imgurl      = $barActivity['thumb'];
                            $desc        = '邀您一起砍!';
                            $url         = h5_url('pages/subPages/bargin/barginDetail/barginDetail' , ['bargin_id' => $scanrecord['cardid']]);
                            break;
                        case 'payOnline':
                            $title = '在线买单';
                            $url   = h5_url('pages/subPages2/newBuyOrder/buyOrder' , ['sid' => $scanrecord['cardid']]);
                            break;
                        case 'distribution':
                            $base   = Setting::wlsetting_read('distribution');
                            $title  = $base['gztitle'] ? $base['gztitle'] : '申请分销商';
                            $imgurl = tomedia($base['gzthumb']);
                            $desc   = $base['gzdesc'];
                            $url    = h5_url('pages/subPages/dealer/apply/apply');
                            break;
                        case 'draw':
                            $activity = pdo_get('wlmerchant_draw',array('id' => $scanrecord['cardid']),array('title','share_image'));
                            $title  = $activity['title'];
                            $imgurl = tomedia($activity['share_image']);
                            $desc   = '快来参与吧';
                            $url    = h5_url('pages/subPages2/drawGame/drawGame',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'activity':
                            $activity = pdo_get('wlmerchant_activitylist',array('id' => $scanrecord['cardid']),array('title','share_image','thumb'));
                            $title  = $activity['title'];
                            $imgurl = !empty($activity['share_image'])? tomedia($activity['share_image']) : tomedia($activity['thumb']);
                            $desc   = '快来报名吧';
                            $url    = h5_url('pages/subPages2/coursegoods/coursegoods',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'housekeep':
                            $activity = pdo_get('wlmerchant_housekeep_service',array('id' => $scanrecord['cardid']),array('title','share_image','thumb'));
                            $title  = $activity['title'];
                            $imgurl = !empty($activity['share_image'])? tomedia($activity['share_image']) : tomedia($activity['thumb']);
                            $desc   = '快来看看吧';
                            $url    = h5_url('pages/subPages2/homemaking/homemakingDetails/homemakingDetails',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'mobilerecharge':
                            $title  = '点击充值';
                            $desc   = '欢迎使用'.$_W['wlsetting']['base']['name'];
                            $imgurl = $_W['wlsetting']['mobilerecharge']['share_image'];
                            $url    = h5_url('pages/subPages2/voucherCenter/voucherCenter');
                            break;
                        case 'integral':
                            $goods = pdo_get('wlmerchant_consumption_goods',array('id' => $scanrecord['cardid']),array('title','thumb'));
                            $title = $goods['title'];
                            $desc  = '快来兑换吧';
                            $imgurl = tomedia($goods['thumb']);
                            $url       = h5_url('pages/subPages/goods/index' , [
                                'goodsType' => 'integral',
                                'goods_id'  => $scanrecord['cardid']
                            ]);
                            break;
                        default:
                            $title  = $_W['wlsetting']['base']['name'];
                            $desc   = '欢迎使用' . $_W['wlsetting']['base']['name'];
                            $imgurl = $_W['wlsetting']['base']['logo'];
                            $url    = $scanrecord['url'] ? : h5_url('pages/mainPages/index/index');
                            break;
                    }

                    //信息补充
                    if(!$title) $title = $_W['wlsetting']['base']['name'];
                    if(!$desc) $desc = '欢迎使用'.$_W['wlsetting']['base']['name'];
                    if(!$imgurl) $imgurl = $_W['wlsetting']['base']['logo'];
                    if(!$url) $url = h5_url('pages/mainPages/index/index');


                    $returnmess = array(array('title' => urlencode($title), 'description' => urlencode($desc), 'picurl' => tomedia($imgurl), 'url' => $url));
                    Weixinqrcode::send_news($returnmess, $message);
                }
            }
        }
    }
}
class Weliam_smartcity1ModuleReceiver extends WeModuleReceiver {

    public function receive() {
        global $_W;
        $_W['wlsetting'] = Setting::wlsetting_load();
        $message = $this->message;
        $fansinfo = Member::wl_fans_info($message['from']);
        $_W['wlmember'] = Member::wl_member_create($fansinfo, 'wechat');
        $_W['mid'] = $_W['wlmember']['id'];
        file_put_contents(PATH_DATA . "receiver_qr.log", var_export($message, true) . PHP_EOL, FILE_APPEND);

        if (!empty($message['scene'])) {
            $name = pdo_getcolumn('qrcode', array('scene_str' => $message['scene'], 'uniacid' => $_W['uniacid']), 'name');
            $names = explode(':', $name);
            $plugin = (isset($names[1]) ? $names[1] : '');
            if (!empty($plugin)) {
                $plugin::Processor($message);
            }
        } else {
            $scanrecord = pdo_fetch("SELECT cardid,scantime,type,url FROM "
                . tablename('wlmerchant_halfcard_qrscan')
                . " WHERE uniacid = {$_W['uniacid']} AND openid = '{$message['from']}' order by id desc");
            if (!empty($scanrecord) && ($scanrecord['scantime'] + 120) > time()) {
                //一卡通实卡
                if (empty($scanrecord['type'])) {
                    $card = pdo_get('wlmerchant_halfcard_realcard', array('uniacid' => $_W['uniacid'], 'id' => $scanrecord['cardid']));
                    if (!empty($card)) {
                        //一卡通未绑定
                        if ($card['status'] == 1) {
                            $setting = Setting::wlsetting_read('halfcard');
                            $imgurl = $setting['cardimg'] ? $setting['cardimg'] : URL_MODULE . 'plugin/halfcard/app/resource/images/cord-bg.jpg';
                            $returnmess = array(array('title' => urlencode("点击立即激活此卡"), 'description' => urlencode('激活此卡'), 'picurl' => tomedia($imgurl), 'url' => app_url('halfcard/halfcard_app/realcard', array('cardsn' => $card['cardsn'], 'salt' => $card['salt']))));
                            Weixinqrcode::send_news($returnmess, $message);
                        }
                        //一卡通已绑定
                        if ($card['status'] == 2) {
                            Weixinqrcode::send_text('关注成功，请重新扫描二维码操作', $message);
                        }
                        //一卡通已禁止
                        if ($card['status'] == 3) {
                            Weixinqrcode::send_text('抱歉，此卡已失效！', $message);
                        }
                    }
                } else {
                    switch ($scanrecord['type']) {
                        case 'rush':
                            $rushgoods = Rush::getSingleActive($scanrecord['cardid'] , 'name,thumb');
                            $title     = $rushgoods['name'];
                            $imgurl    = $rushgoods['thumb'];
                            $url       = h5_url('pages/subPages/goods/index' , [
                                'type' => 1 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            $desc      = '手快有,手慢无...';
                            break;
                        case 'wlcoupon':
                            $wlCoupon = wlCoupon::getSingleCoupons($scanrecord['cardid'] , 'title,logo');
                            $title    = $wlCoupon['title'];
                            $imgurl   = $wlCoupon['logo'];
                            $url      = h5_url('pages/subPages/goods/index' , [
                                'type' => 5 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'wlfightgroup':
                            $group  = Wlfightgroup::getSingleGood($scanrecord['cardid'] , 'name,logo');
                            $title  = $group['name'];
                            $imgurl = $group['logo'];
                            $url    = h5_url('pages/subPages/goods/index' , [
                                'type' => 3 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'wlgroupdetail':
                            $group  = pdo_get('wlmerchant_fightgroup_group' , ['id' => $scanrecord['cardid']] , ['goodsid']);
                            $goods  = Wlfightgroup::getSingleGood($group['goodsid'] , 'name,logo');
                            $title  = $goods['name'];
                            $imgurl = $goods['logo'];
                            $url    = h5_url('pages/subPages/group/assemble/assemble' , ['group_id' => $scanrecord['cardid']]);
                            break;
                        case 'groupon':
                            $groupon = pdo_get('wlmerchant_groupon_activity' , ['id' => $scanrecord['cardid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title   = $groupon['name'];
                            $imgurl  = $groupon['thumb'];
                            $url     = h5_url('pages/subPages/goods/index' , [
                                'type' => 2 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'bargain':
                            $groupon = pdo_get('wlmerchant_bargain_activity' , ['id' => $scanrecord['cardid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title   = $groupon['name'];
                            $imgurl  = $groupon['thumb'];
                            $url     = h5_url('pages/subPages/goods/index' , [
                                'type' => 7 ,
                                'id'   => $scanrecord['cardid']
                            ]);
                            break;
                        case 'helpBargain':
                            $bargainuser = pdo_get('wlmerchant_bargain_userlist' , ['id' => $scanrecord['cardid']] , ['activityid']);
                            $barActivity = pdo_get('wlmerchant_bargain_activity' , ['id' => $bargainuser['activityid']] , [
                                'name' ,
                                'thumb'
                            ]);
                            $title       = $barActivity['name'];
                            $imgurl      = $barActivity['thumb'];
                            $desc        = '邀您一起砍!';
                            $url         = h5_url('pages/subPages/bargin/barginDetail/barginDetail' , ['bargin_id' => $scanrecord['cardid']]);
                            break;
                        case 'payOnline':
                            $title = '在线买单';
                            $url   = h5_url('pages/subPages2/newBuyOrder/buyOrder' , ['sid' => $scanrecord['cardid']]);
                            break;
                        case 'distribution':
                            $base   = Setting::wlsetting_read('distribution');
                            $title  = $base['gztitle'] ? $base['gztitle'] : '申请分销商';
                            $imgurl = tomedia($base['gzthumb']);
                            $desc   = $base['gzdesc'];
                            $url    = h5_url('pages/subPages/dealer/apply/apply');
                            break;
                        case 'draw':
                            $activity = pdo_get('wlmerchant_draw',array('id' => $scanrecord['cardid']),array('title','share_image'));
                            $title  = $activity['title'];
                            $imgurl = tomedia($activity['share_image']);
                            $desc   = '快来参与吧';
                            $url    = h5_url('pages/subPages2/drawGame/drawGame',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'activity':
                            $activity = pdo_get('wlmerchant_activitylist',array('id' => $scanrecord['cardid']),array('title','share_image','thumb'));
                            $title  = $activity['title'];
                            $imgurl = !empty($activity['share_image'])? tomedia($activity['share_image']) : tomedia($activity['thumb']);
                            $desc   = '快来报名吧';
                            $url    = h5_url('pages/subPages2/coursegoods/coursegoods',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'housekeep':
                            $activity = pdo_get('wlmerchant_housekeep_service',array('id' => $scanrecord['cardid']),array('title','share_image','thumb'));
                            $title  = $activity['title'];
                            $imgurl = !empty($activity['share_image'])? tomedia($activity['share_image']) : tomedia($activity['thumb']);
                            $desc   = '快来看看吧';
                            $url    = h5_url('pages/subPages2/homemaking/homemakingDetails/homemakingDetails',['id'=>$scanrecord['cardid']]);
                            break;
                        case 'mobilerecharge':
                            $title  = '点击充值';
                            $desc   = '欢迎使用'.$_W['wlsetting']['base']['name'];
                            $imgurl = $_W['wlsetting']['mobilerecharge']['share_image'];
                            $url    = h5_url('pages/subPages2/voucherCenter/voucherCenter');
                            break;
						case 'storeRedpack':
							$activity = pdo_get('wlmerchant_merchantdata',array('id' => $scanrecord['cardid']),array('storename','logo'));
                           	$title  = $activity['storename'];
                            $desc   = '欢迎使用'.$_W['wlsetting']['base']['name'];
                            $imgurl = tomeida($activity['logo']);
                            $url    = h5_url('pages/mainPages/store/index',['sid'=> $scanrecord['cardid']]);
                            break;
                        default:
                            $title  = $_W['wlsetting']['base']['name'];
                            $desc   = '欢迎使用' . $_W['wlsetting']['base']['name'];
                            $imgurl = $_W['wlsetting']['base']['logo'];
                            $url    = $scanrecord['url'] ? : h5_url('pages/mainPages/index/index');
                            break;
                    }

                    //信息补充
                    if(!$title) $title = $_W['wlsetting']['base']['name'];
                    if(!$desc) $desc = '欢迎使用'.$_W['wlsetting']['base']['name'];
                    if(!$imgurl) $imgurl = $_W['wlsetting']['base']['logo'];
                    if(!$url) $url = h5_url('pages/mainPages/index/index');


                    $returnmess = array(array('title' => urlencode($title), 'description' => urlencode($desc), 'picurl' => tomedia($imgurl), 'url' => $url));
                    Weixinqrcode::send_news($returnmess, $message);
                }
            }
        }
    }
}