<?php
defined('IN_IA') or exit('Access Denied');

class User {
	
    static function agentuser_single($user_or_uid) {
		$user = $user_or_uid;
		if (empty($user)) {
			return false;
		}
        if (is_numeric($user)) {
			$user = array('id' => $user);
		}
		if (!is_array($user)) {
			return false;
		}
		$where = ' WHERE 1 ';
		$params = array();
		if (!empty($user['id'])) {
			$where .= ' AND `id`=:id';
			$params[':id'] = intval($user['id']);
		}
        if (!empty($user['uniacid'])) {
            $where .= ' AND `uniacid`=:uniacid';
            $params[':uniacid'] = $user['uniacid'];
        }
		if (!empty($user['username'])) {
			$where .= ' AND `username`=:username';
			$params[':username'] = $user['username'];
		}
		if (!empty($user['status'])) {
			$where .= " AND `status`=:status";
			$params[':status'] = intval($user['status']);
		}
		if (empty($params)) {
			return false;
		}
		$sql = 'SELECT * FROM ' . tablename(PDO_NAME.'agentusers') . " $where LIMIT 1";
		$record = pdo_fetch($sql, $params);
		if (empty($record)) {
			return false;
		}
		if (!empty($user['password'])) {
			$password = Util::encryptedPassword($user['password'], $record['salt']);
			if ($password != $record['password']) {
				return false;
			}
		}
		return $record;
	}

	static function agentuser_update($user) {
		if (empty($user['id']) || !is_array($user)) {
			return false;
		}
		$record = array();
		if (!empty($user['username'])) {
			$record['username'] = $user['username'];
		}
		if (!empty($user['password'])) {
			$record['password'] = user_hash($user['password'], $user['salt']);
		}
		if (!empty($user['lastvisit'])) {
			$record['lastvisit'] = (strlen($user['lastvisit']) == 10) ? $user['lastvisit'] : strtotime($user['lastvisit']);
		}
		if (!empty($user['lastip'])) {
			$record['lastip'] = $user['lastip'];
		}
		if (isset($user['joinip'])) {
			$record['joinip'] = $user['joinip'];
		}
		if (isset($user['remark'])) {
			$record['remark'] = $user['remark'];
		}
		if (isset($user['status'])) {
			$status = intval($user['status']);
			if (!in_array($status, array(0, 1))) {
				$status = 1;
			}
			$record['status'] = $status;
		}
		if (isset($user['groupid'])) {
			$record['groupid'] = $user['groupid'];
		}
		if (isset($user['starttime'])) {
			$record['starttime'] = $user['starttime'];
		}
		if (isset($user['endtime'])) {
			$record['endtime'] = $user['endtime'];
		}
		if (empty($record)) {
			return false;
		}
		return pdo_update(PDO_NAME.'agentusers', $record, array('id' => intval($user['id'])));
	}
    /**
     * Comment: 获取个人中心菜单设置信息
     * Author: zzw
     * Date: 2020/3/27 14:01
     * @return array|bool|mixed
     */
	public static function userSet(){
        global $_W;
        #1、信息获取
        $set = Setting::wlsetting_read('userindex');//旧版本设置信息
        $default = static::getDefaultUserMenuList();//默认菜单信息
        //循环处理信息
        foreach($default as $key => $val){
            if(is_array($set[$key])){
                $set[$key] = array_merge($val,$set[$key]);
                foreach($set[$key] as $kk => $vv){
                    if($kk != 'diy_title' && !$vv){
                        $set[$key][$kk] = $default[$key][$kk];
                    }
                }
            }else{
                $set[$key] = $val;
            }
        }


//        #1、信息设置  通过判断是否为一维数组 确定是否为新的设置信息
//        if (count($set , 1) == count($set)) {
//            //信息错误 生成正确的信息
//            $newSet = [
//                'kefu' => [
//                    'title'   => '客服按钮' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/kf.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/kf.png' ,
//                    'link'    => 'pages/subPages/customer/customer' ,
//                    'default' => 'pages/subPages/customer/customer' ,
//                    'switch'  => $set['kefu'] ? : 1
//                ] ,//客服按钮
//                'grzy' => [
//                    'title'   => '个人主页' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzy.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzy.png' ,
//                    'link'    => "pages/subPages/homepage/homepage/homepage?mid=&checkType=1" ,
//                    'default' => 'pages/subPages/homepage/homepage/homepage?mid=&checkType=1' ,
//                    'switch'  => $set['grzy'] ? : 1
//                ] ,//个人主页
//                'shzx' => [
//                    'title'   => '商户中心' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/pos.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/pos.png' ,
//                    'link'    => 'pages/subPages/merchant/merchantChangeShop/merchantChangeShop' ,
//                    'default' => 'pages/subPages/merchant/merchantChangeShop/merchantChangeShop' ,
//                    'switch'  => $set['shzx'] ? : 1
//                ] ,//商户中心
//                'hxjl' => [
//                    'title'   => '核销记录' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/hxjl.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/hxjl.png' ,
//                    'link'    => 'pages/subPages/writeRecord/index' ,
//                    'default' => 'pages/subPages/writeRecord/index' ,
//                    'switch'  => $set['hxjl'] ? : 1
//                ] ,//核销记录
//                'wddz' => [
//                    'title'   => '我的地址' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
//                    'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
//                    'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
//                    'switch'  => $set['wddz'] ? : 1
//                ] ,//我的地址
//            ];
//            if (p('halfcard')) {
//                $newSet['ykthy'] = [
//                    'title'   => '一卡通会员' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/card.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/card.png' ,
//                    'link'    => 'pages/mainPages/memberCard/memberCard' ,
//                    'default' => 'pages/mainPages/memberCard/memberCard' ,
//                    'switch'  => $set['ykthy'] ? : 1
//                ];//一卡通会员
//                $newSet['xfjl']  = [
//                    'title'   => '消费记录' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/consume.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/consume.png' ,
//                    'link'    => 'pages/subPages/consumptionRecords/consumptionRecords' ,
//                    'default' => 'pages/subPages/consumptionRecords/consumptionRecords' ,
//                    'switch'  => $set['xfjl'] ? : 1
//                ];//消费记录
//            }
//            if (p('wlcoupon')) {
//                $newSet['wdkq'] = [
//                    'title'   => '我的卡券' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/coupon.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/coupon.png' ,
//                    'link'    => 'pages/subPages/coupon/coupon' ,
//                    'default' => 'pages/subPages/coupon/coupon' ,
//                    'switch'  => $set['wdkq'] ? : 1
//                ];//我的卡券
//            }
//            if (p('bargain')) {
//                $newSet['wdkj'] = [
//                    'title'   => '我的砍价' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mykj.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mykj.png' ,
//                    'link'    => 'pages/subPages/bargin/barginlist/barginlist' ,
//                    'default' => 'pages/subPages/bargin/barginlist/barginlist' ,
//                    'switch'  => $set['wdkj'] ? : 1
//                ];//我的砍价
//            }
//            if (p('consumption')) {
//                $newSet['jfsc'] = [
//                    'title'   => '积分商城' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/integralMall.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/integralMall.png' ,
//                    'link'    => 'pages/subPages/integral/integralShop/integralShop' ,
//                    'default' => 'pages/subPages/integral/integralShop/integralShop' ,
//                    'switch'  => $set['jfsc'] ? : 1
//                ];//积分商城
//            }
//            if (p('pocket')) {
//                $newSet['wdtz'] = [
//                    'title'   => '我的帖子' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/invitation.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/invitation.png' ,
//                    'link'    => 'pages/subPages/myPost/myPost' ,
//                    'default' => 'pages/subPages/myPost/myPost' ,
//                    'switch'  => $set['wdtz'] ? : 1
//                ];//我的帖子
//            }
//            if (p('distribution')) {
//                $newSet['fxzx'] = [
//                    'title'   => '分销中心' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/distribution.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/distribution.png' ,
//                    'link'    => 'pages/subPages/dealer/index/index' ,
//                    'default' => 'pages/subPages/dealer/index/index' ,
//                    'switch'  => $set['fxzx'] ? : 1
//                ];//分销中心
//            }
//            if (p('helper')) {
//                $newSet['bzzx'] = [
//                    'title'   => '帮助中心' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/qa.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/qa.png' ,
//                    'link'    => 'pages/subPages/helpCenter/helpCenter' ,
//                    'default' => 'pages/subPages/helpCenter/helpCenter' ,
//                    'switch'  => $set['bzzx'] ? : 1
//                ];//帮助中心
//            }
//            if (p('attestation')) {
//                $newSet['rzzx'] = [
//                    'title'   => '认证中心' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/rzzx.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/rzzx.png' ,
//                    'link'    => 'pages/subPages/attestationCenter/index?rzType=1' ,
//                    'default' => 'pages/subPages/attestationCenter/index?rzType=1' ,
//                    'switch'  => $set['rzzx'] ? : 1
//                ];//认证中心
//            }
//            if (p('redpack')) {
//                $newSet['wdhb'] = [
//                    'title'   => '我的红包' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png' ,
//                    'link'    => 'pages/subPages/redpacket/myredpacket' ,
//                    'default' => 'pages/subPages/redpacket/myredpacket' ,
//                    'switch'  => $set['wdhb'] ? : 1
//                ];//我的红包
//                $newSet['hbgc'] = [
//                    'title'   => '红包广场' ,
//                    'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png' ,
//                    'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png' ,
//                    'link'    => 'pages/subPages/redpacket/redsquare' ,
//                    'default' => 'pages/subPages/redpacket/redsquare' ,
//                    'switch'  => $set['hbgc'] ? : 1
//                ];//红包广场
//            }
//            //设置默认在最后面
//            $newSet['sz'] = [
//                'title'   => '设置' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/setting.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/setting.png' ,
//                'link'    => 'pages/mainPages/userset/userset' ,
//                'default' => 'pages/mainPages/userset/userset' ,
//                'switch'  => 1
//            ];//设置
//            $set = $newSet;
//        }
//        //补丁：判断是否存在我的地址
//        if(!in_array('wddz',array_keys($set))){
//            $set['wddz'] = [
//                'title'   => '我的地址' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
//                'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'switch'  => $set['wddz'] ? : 1
//            ];
//        }
//        if (p('redpack')) {
//            if(!in_array('wdhb',array_keys($set))) {
//                $set['wdhb'] = [
//                    'title' => '我的红包',
//                    'icon' => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png',
//                    'image' => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png',
//                    'link' => 'pages/subPages/redpacket/myredpacket',
//                    'default' => 'pages/subPages/redpacket/myredpacket',
//                    'switch' => $set['wdhb'] ?: 1
//                ];//我的红包
//            }
//            if(!in_array('hbgc',array_keys($set))) {
//                $set['hbgc'] = [
//                    'title' => '红包广场',
//                    'icon' => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png',
//                    'image' => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png',
//                    'link' => 'pages/subPages/redpacket/redsquare',
//                    'default' => 'pages/subPages/redpacket/redsquare',
//                    'switch' => $set['hbgc'] ?: 1
//                ];//红包广场
//            }
//        }
//        //补丁：修改默认图片信息
//        $set['kefu']['image']  = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/kf.png';
//        $set['grzy']['image']  = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzy.png';
//        $set['shzx']['image']  = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/pos.png';
//        $set['hxjl']['image']  = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/hxjl.png';
//        $set['wddz']['image']  = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png';
//        if (p('halfcard')) {
//            $set['ykthy']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/card.png';
//            $set['xfjl']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/consume.png';
//        }
//        if (p('wlcoupon')) {
//            $set['wdkq']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/coupon.png';
//        }
//        if (p('bargain')) {
//            $set['wdkj']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mykj.png';
//        }
//        if (p('consumption')) {
//            $set['jfsc']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/integralMall.png';
//        }
//        if (p('pocket')) {
//            $set['wdtz']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/invitation.png';
//        }
//        if (p('distribution')) {
//            $set['fxzx']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/distribution.png';
//        }
//        if (p('helper')) {
//            $set['bzzx']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/qa.png';
//        }
//        if (p('attestation')) {
//            $set['rzzx']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/rzzx.png';
//        }
//        if (p('redpack')) {
//            $set['wdhb']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png';
//            $set['hbgc']['image'] = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png';
//        }
//        $set['sz']['image']    = '/addons/weliam_smartcity/h5/resource/wxapp/merchant/setting.png';

        return $set;
    }
    /**
     * Comment: 获取用户中心默认菜单信息
     * Author: zzw
     * Date: 2020/8/7 18:06
     * @return array
     */
    protected static function getDefaultUserMenuList(){
        //信息错误 生成正确的信息
        $set = Setting::wlsetting_read('userindex');//旧版本设置信息
        $newSet = [
            'kefu' => [
                'title'   => '客服按钮' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/kf.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/kf.png' ,
                'link'    => 'pages/subPages/customer/customer' ,
                'default' => 'pages/subPages/customer/customer' ,
                'switch'  => is_array($set['kefu']) ? $set['kefu']['switch'] : 1 ,
            ] ,//客服按钮
            'grzy' => [
                'title'   => '个人主页' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzy.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzy.png' ,
                'link'    => "pages/subPages/homepage/homepage/homepage?mid=&checkType=1" ,
                'default' => 'pages/subPages/homepage/homepage/homepage?mid=&checkType=1' ,
                'switch'  => is_array($set['grzy']) ? $set['grzy']['switch'] :  1 ,
            ] ,//个人主页
            'shzx' => [
                'title'   => '商户中心' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/pos.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/pos.png' ,
                'link'    => 'pages/subPages/merchant/merchantChangeShop/merchantChangeShop' ,
                'default' => 'pages/subPages/merchant/merchantChangeShop/merchantChangeShop' ,
                'switch'  => is_array($set['shzx']) ? $set['shzx']['switch'] : 1 ,
            ] ,//商户中心
            'hxjl' => [
                'title'   => '核销记录' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/hxjl.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/hxjl.png' ,
                'link'    => 'pages/subPages/writeRecord/index' ,
                'default' => 'pages/subPages/writeRecord/index' ,
                'switch'  => is_array($set['hxjl']) ? $set['hxjl']['switch'] :  1 ,
            ] ,//核销记录
            'wddz' => [
                'title'   => '我的地址' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mineWhere.png' ,
                'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
                'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
                'switch'  => is_array($set['wddz']) ? $set['wddz']['switch'] :  1 ,
            ] ,//我的地址
        ];
        if (p('halfcard')) {
            $newSet['ykthy'] = [
                'title'   => '一卡通会员' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/card.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/card.png' ,
                'link'    => 'pages/mainPages/memberCard/memberCard' ,
                'default' => 'pages/mainPages/memberCard/memberCard' ,
                'switch'  => is_array($set['ykthy']) ? $set['ykthy']['switch'] : 1 ,
            ];//一卡通会员
            $newSet['xfjl']  = [
                'title'   => '消费记录' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/consume.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/consume.png' ,
                'link'    => 'pages/subPages/consumptionRecords/consumptionRecords' ,
                'default' => 'pages/subPages/consumptionRecords/consumptionRecords' ,
                'switch'  =>  is_array($set['xfjl']) ? $set['xfjl']['switch'] : 1 ,
            ];//消费记录
        }
        if (p('wlcoupon')) {
            $newSet['wdkq'] = [
                'title'   => '我的卡券' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/coupon.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/coupon.png' ,
                'link'    => 'pages/subPages/coupon/coupon' ,
                'default' => 'pages/subPages/coupon/coupon' ,
                'switch'  => is_array($set['wdkq']) ? $set['wdkq']['switch'] : 1 ,
            ];//我的卡券
        }
        if (p('bargain')) {
            $newSet['wdkj'] = [
                'title'   => '我的砍价' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mykj.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/mykj.png' ,
                'link'    => 'pages/subPages/bargin/barginlist/barginlist' ,
                'default' => 'pages/subPages/bargin/barginlist/barginlist' ,
                'switch'  => is_array($set['wdkj']) ? $set['wdkj']['switch'] : 1 ,
            ];//我的砍价
        }
        if (p('consumption')) {
            $newSet['jfsc'] = [
                'title'   => '积分商城' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/integralMall.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/integralMall.png' ,
                'link'    => 'pages/subPages/integral/integralShop/integralShop' ,
                'default' => 'pages/subPages/integral/integralShop/integralShop' ,
                'switch'  => is_array($set['jfsc']) ? $set['jfsc']['switch'] : 1 ,
            ];//积分商城
        }
        if (p('pocket')) {
            $newSet['wdtz'] = [
                'title'   => '我的帖子' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/invitation.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/invitation.png' ,
                'link'    => 'pages/subPages/myPost/myPost' ,
                'default' => 'pages/subPages/myPost/myPost' ,
                'switch'  => is_array($set['wdtz']) ? $set['wdtz']['switch'] : 1 ,
            ];//我的帖子
        }
        if (p('distribution')) {
            $newSet['fxzx'] = [
                'title'   => '分销中心' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/distribution.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/distribution.png' ,
                'link'    => 'pages/subPages/dealer/index/index' ,
                'default' => 'pages/subPages/dealer/index/index' ,
                'switch'  =>  is_array($set['fxzx']) ? $set['fxzx']['switch'] : 1 ,
            ];//分销中心

            if(Customized::init('distributionText') > 0){
                $newSet['fxzx']['title'] = '共享股东中心';
            }
        }
        if (p('helper')) {
            $newSet['bzzx'] = [
                'title'   => '帮助中心' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/qa.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/qa.png' ,
                'link'    => 'pages/subPages/helpCenter/helpCenter' ,
                'default' => 'pages/subPages/helpCenter/helpCenter' ,
                'switch'  => is_array($set['bzzx']) ? $set['bzzx']['switch'] : 1 ,
            ];//帮助中心
        }
        if (p('attestation')) {
            $newSet['rzzx'] = [
                'title'   => '认证中心' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/rzzx.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/rzzx.png' ,
                'link'    => 'pages/subPages/attestationCenter/index?rzType=1' ,
                'default' => 'pages/subPages/attestationCenter/index?rzType=1' ,
                'switch'  => is_array($set['rzzx']) ? $set['rzzx']['switch'] : 1 ,
            ];//认证中心
        }
        if (p('redpack')) {
            $newSet['wdhb'] = [
                'title'   => '我的红包' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/RedPacket_sq.png' ,
                'link'    => 'pages/subPages/redpacket/myredpacket' ,
                'default' => 'pages/subPages/redpacket/myredpacket' ,
                'switch'  => is_array($set['wdhb']) ? $set['wdhb']['switch'] : 1 ,
            ];//我的红包
            $newSet['hbgc'] = [
                'title'   => '红包广场' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/myRedPacket.png' ,
                'link'    => 'pages/subPages/redpacket/redsquare' ,
                'default' => 'pages/subPages/redpacket/redsquare' ,
                'switch'  => is_array($set['hbgc']) ? $set['hbgc']['switch'] : 1 ,
            ];//红包广场
        }
        if (p('recruit')) {
            $newSet['wdjl'] = [
                'title'   => '我的简历' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdjl.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdjl.png' ,
                'link'    => 'pages/subPages2/hirePlatform/addResume/addResume' ,
                'default' => 'pages/subPages2/hirePlatform/addResume/addResume' ,
                'switch'  => is_array($set['wdjl']) ? $set['wdjl']['switch'] : 1 ,
            ];//我的简历
            $newSet['wdzp'] = [
                'title'   => '我的招聘' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzp.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdzp.png' ,
                'link'    => 'pages/subPages2/hirePlatform/recruitmentEnter/recruitmentEnter' ,
                'default' => 'pages/subPages2/hirePlatform/recruitmentEnter/recruitmentEnter' ,
                'switch'  => is_array($set['wdzp']) ? $set['wdzp']['switch'] : 1 ,
            ];//我的招聘
            $newSet['wdqz'] = [
                'title'   => '我的求职' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdqz.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/wdqz.png' ,
                'link'    => 'pages/subPages2/hirePlatform/deliverList/deliverList' ,
                'default' => 'pages/subPages2/hirePlatform/deliverList/deliverList' ,
                'switch'  => is_array($set['wdzp']) ? $set['wdzp']['switch'] : 1 ,
            ];//我的招聘
        }

        //设置默认在最后面
        $newSet['sz'] = [
            'title'   => '设置' ,
            'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/setting.png' ,
            'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/merchant/setting.png' ,
            'link'    => 'pages/mainPages/userset/userset' ,
            'default' => 'pages/mainPages/userset/userset' ,
            'switch'  => is_array($set['sz']) ? $set['sz']['switch'] : 1 ,
        ];//设置
        return $newSet;
    }


    /**
     * Comment: 获取房产用户中心默认菜单信息
     * @return array
     */
    public static function getDefaultHouseUserMenuList(){
        //信息错误 生成正确的信息
//        $set = Setting::wlsetting_read('userindex');//旧版本设置信息
        $newSet = [
            [
                'title'   => '订单管理' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/order.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/order.png' ,
                'link'    => 'pages/subPages2/houseproperty/roomtypeorder/roomtypeorder' ,
                'default' => 'pages/subPages2/houseproperty/roomtypeorder/roomtypeorder' ,
                'switch'  => 1 ,
            ] ,//订单管理
            [
                'title'   => '我的收藏' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/collection.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/collection.png' ,
                'link'    => "pages/subPages2/houseproperty/collectionhouse/collectionhouse" ,
                'default' => 'pages/subPages2/houseproperty/collectionhouse/collectionhouse' ,
                'switch'  => 1 ,
            ] ,//我的收藏
            [
                'title'   => '房源管理' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/old_house_my.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/old_house_my.png' ,
                'link'    => 'pages/subPages2/houseproperty/secondeman/secondeman?typecu=2&newtype=0' ,
                'default' => 'pages/subPages2/houseproperty/secondeman/secondeman?typecu=2&newtype=0' ,
                'switch'  => 1 ,
            ] ,//二手房管理
//            [
//                'title'   => '租房管理' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/renting_my.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/renting_my.png' ,
//                'link'    => 'pages/subPages2/houseproperty/secondeman/secondeman?typecu=3' ,
//                'default' => 'pages/subPages2/houseproperty/secondeman/secondeman?typecu=3' ,
//                'switch'  => 1 ,
//            ] ,//租房管理
//            [
//                'title'   => '我的设置' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/setting.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/setting.png' ,
//                'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'switch'  => 1 ,
//            ] ,//我的设置
//            [
//                'title'   => '帮助中心' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/help.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/help.png' ,
//                'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'switch'  => 1 ,
//            ] ,//帮助中心
//            [
//                'title'   => '顾问收藏' ,
//                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/adviser.png' ,
//                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/adviser.png' ,
//                'link'    => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'default' => 'pages/subPages/receivingAddress/receivingAddress' ,
//                'switch'  => 1 ,
//            ] ,//顾问收藏
            [
                'title'   => '我的预约' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/subscribe.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/subscribe.png' ,
                'link'    => 'pages/subPages2/houseproperty/mymake/mymake' ,
                'default' => 'pages/subPages2/houseproperty/mymake/mymake' ,
                'switch'  => 1 ,
            ] ,//我的预约
            [
                'title'   => '浏览历史' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/browse.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/browse.png' ,
                'link'    => 'pages/subPages2/houseproperty/browsinghistory/browsinghistory' ,
                'default' => 'pages/subPages2/houseproperty/browsinghistory/browsinghistory' ,
                'switch'  => 1 ,
            ] ,//浏览历史
            [
                'title'   => '信息反馈' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/feedback.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/feedback.png' ,
                'link'    => 'pages/subPages/feedback/feedback' ,
                'default' => 'pages/subPages/feedback/feedback' ,
                'switch'  => 1 ,
            ] ,//信息反馈
            [
                'title'   => '反馈列表' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/feedbcklist.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/feedbcklist.png' ,
                'link'    => 'pages/subPages2/houseproperty/feedbcklist/feedbcklist' ,
                'default' => 'pages/subPages2/houseproperty/feedbcklist/feedbcklist' ,
                'switch'  => 1 ,
            ] ,//信息反馈
            [
                'title'   => '获客列表' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/new_house_my.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/new_house_my.png' ,
                'link'    => 'pages/subPages2/houseproperty/browsetime/browsetime?type=1' ,
                'default' => 'pages/subPages2/houseproperty/browsetime/browsetime?type=1' ,
                'switch'  => 1 ,
            ] ,//获客列表
        ];
            $newSet[] = [
                'title'   => '商户入驻' ,
                'icon'    => '/addons/weliam_smartcity/h5/resource/wxapp/house/settle_in.png' ,
                'image'   => '/addons/weliam_smartcity/h5/resource/wxapp/house/settle_in.png' ,
                'link'    => 'pages/mainPages/Settled/Settled' ,
                'default' => 'pages/mainPages/Settled/Settled' ,
                'switch'  => 1 ,
            ];//商户入驻
        return $newSet;
    }



}

