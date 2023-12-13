<?php
defined('IN_IA') or exit('Access Denied');

class Halfcard
{

    static function checkFollow($cardid)
    {
        global $_W;
        if ($_W['fans']['follow'] != 1) {
            $showurl = !empty($_W['wlsetting']['share']['gz_image']) ? $_W['wlsetting']['share']['gz_image'] : 'qrcode_' . $_W['acid'] . '.jpg';
            pdo_insert('wlmerchant_halfcard_qrscan', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'scantime' => time(), 'cardid' => $cardid));
            include wl_template('newcard/qrcode');
            exit;
        }
    }

    /**
     * 保存特权内容
     *
     * @access static
     * @name saveHalfcard
     * @param mixed  参数一的说明
     * @return array
     */
    static function saveHalfcard($halfcard, $param = array())
    {
        global $_W;
        if (!is_array($halfcard)) return FALSE;
        $halfcard['uniacid'] = $_W['uniacid'];
        $halfcard['aid'] = $_W['aid'];
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'halfcardlist', $halfcard);
            return pdo_insertid();
        }
        return FALSE;
    }

    /**
     * 删除特权券
     *
     * @access static
     * @name deleteOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteHalfcard($where)
    {
        $res = pdo_delete(PDO_NAME . 'halfcardlist', $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * //TODO:删除特权券用户记录删除特权券用户记录
     *
     * @access static
     * @name deleteOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteHalfcardRecord($where)
    {
        $res = pdo_delete(PDO_NAME . 'timecardrecord', $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 获取单条特权券内容
     *
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleHalfcard($id, $select, $where = array())
    {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'halfcardlist', $where);
    }

    /**
     * 获取单条用户记录
     *
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleMember($id, $select, $where = array())
    {
        $where['mid'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'halfcardmember', $where);
    }

    /**
     * 更新特权券母类
     *
     * @access static
     * @name updateCoupons
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function updateHalfcard($params, $where)
    {
        $res = pdo_update(PDO_NAME . 'halfcardlist', $params, $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 获取多条特权优惠记录
     *
     * @access static
     * @name getNumRecord
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumRecord($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'halfcardrecord', $where, $order, $pindex, $psize, $ifpage);
        $newGoodInfo = $newGoodInfo ? $newGoodInfo : array();
        return array($newGoodInfo, $goodsInfo[1], $goodsInfo[2]) ? array($newGoodInfo, $goodsInfo[1], $goodsInfo[2]) : array();
    }

    //判断会员等级是否有权限购买商品
    static function checklevel($mid, $levels)
    {
        global $_W;
        $now = time();
        if ($_W['wlsetting']['halfcard']['halfcardtype'] == 2) {
            $cards = pdo_fetchall("SELECT levelid FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} AND aid = {$_W['aid']} AND expiretime > {$now} AND disable != 1");
        } else {
            $cards = pdo_fetchall("SELECT levelid FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} AND expiretime > {$now} AND disable != 1");
        }
        $flag = 0;
        if ($cards) {
            foreach ($cards as $key => $cs) {
                if (in_array($cs['levelid'], $levels)) {
                    $flag = 1;
                }
            }
        }
        return $flag;
    }


    /**
     * 获取多条店铺记录
     *
     * @access static
     * @name getNumCouponOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumstore($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'merchantdata', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    static function getstores($locations, $lng, $lat)
    {
        global $_W;
        if (empty($lat) || empty($lng)) return false;
        foreach ($locations as $key => $val) {
            $loca = unserialize($val['location']);
            $locations[$key]['distance'] = Store::getdistance($loca['lng'], $loca['lat'], $lng, $lat);
        }
        //排序
        for ($i = 0; $i < count($locations) - 1; $i++) {
            for ($j = 0; $j < count($locations) - 1 - $i; $j++) {
                if ($locations[$j]['distance'] > $locations[$j + 1]['distance']) {
                    $temp = $locations[$j + 1];
                    $locations[$j + 1] = $locations[$j];
                    $locations[$j] = $temp;
                }
            }
        }
        foreach ($locations as $key => $value) {
            if ($value['distance'] > 1000) {
                $locations[$key]['distance'] = (floor(($value['distance'] / 1000) * 10) / 10) . "km";
            } else {
                $locations[$key]['distance'] = round($value['distance']) . "m";
            }
        }
        return $locations;
    }

    /**
     * 异步支付结果回调 ，处理业务逻辑
     *
     * @access public
     * @name
     * @param mixed  参数一的说明
     * @return array
     */
    static function payHalfcardNotify($params)
    {
        global $_W;
        Util::wl_log('vip_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        $data = self::getVipPayData($params); //得到支付参数，处理代付
        pdo_update(PDO_NAME . 'halfcard_record', $data, array('orderno' => $params['tid'])); //更新订单状态

        $order_out = pdo_get(PDO_NAME . 'halfcard_record', array('orderno' => $params['tid']));
        $memberData = array(
            'halfcardstatus'   => 1,
            'lasthalfcardtime' => $order_out['limittime'],
            'areaid'           => $order_out['areaid'],
            'aid'              => $order_out['aid']
        );
        pdo_update(PDO_NAME . 'member', $memberData, array('id' => $order_out['mid']));
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function payHalfcardReturn($params)
    {
        global $_W;
        Util::wl_log('Vip_return', PATH_DATA . "merchant/data/", $params);//写入日志记录
        $order_out = pdo_get(PDO_NAME . 'halfcard_record', array('orderno' => $params['tid']), array('id'));
        header("location:" . h5_url('pages/mainPages/memberCard/getMembership/getMembership'));
    }

    /**
     * 获取多条活动数据
     *
     * @access static
     * @name getNumActive
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumActive($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'halfcardlist', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }


    static function getNumPackActive($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'package', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    /**
     * 获取多条活动数据1
     *
     * @access static
     * @name getNumActive
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumActive1($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'halfcardrecord', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    static function getNumActive2($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'timecardrecord', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    static function getNumhalfcardmember($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'halfcardmember', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    static function getNumhalfcardpay($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'halfcard_record', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    static function doTask()
    {
        global $_W, $_GPC;
        //自动过期礼包特权
        $nowtime = time();
        pdo_update('wlmerchant_package', array('status' => 0), array('status' => 1, 'packtimestatus' => 1, 'dateendtime <' => $nowtime));
        pdo_update('wlmerchant_package', array('status' => 0), array('status' => 1, 'packtimestatus' => 1, 'datestarttime >' => $nowtime));
        pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('status' => 1, 'timingstatus' => 1, 'endtime <' => $nowtime));
        pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('status' => 1, 'timingstatus' => 1, 'starttime >' => $nowtime));
        //自动上架礼包
        pdo_update('wlmerchant_package', array('status' => 1), array('status' => 0, 'packtimestatus' => 1, 'datestarttime <' => $nowtime, 'dateendtime >' => $nowtime));
        pdo_update('wlmerchant_halfcardlist', array('status' => 1), array('status' => 0, 'timingstatus' => 1, 'starttime <' => $nowtime, 'endtime >' => $nowtime));
    }

    /**
     * Comment: 获取用户正在使用的一张会员卡信息
     * Author: zzw
     * Date: 2019/8/19 15:15
     * @return array|bool
     */
    public static function getUserMemberCardInfo()
    {
        global $_W;
        $time = time();
        $set = $_W['wlsetting']['halfcard'];
        $defaultlevel = $_W['wlsetting']['halflevel'];
        $isVip = WeliamWeChat::VipVerification($_W['mid'], true);
        $settings = Setting::wlsetting_read('halfcard');
        if ($set['halfcardtype'] == 2) {
            $info = pdo_fetch("SELECT id,expiretime,username,levelid FROM " . tablename(PDO_NAME . "halfcardmember") . "WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND expiretime > {$time} AND disable != 1 ");
        } else {
            $info = pdo_fetch("SELECT id,expiretime,username,levelid FROM " . tablename(PDO_NAME . "halfcardmember") . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND expiretime > {$time} AND disable != 1 ");
        }
        $info['isVip'] = $isVip;
        //默认面
        $defaultCardImg = tomedia('/addons/{MODULE_NAME}/h5/resource/image/defaulthalfimg.png');
        if ($info['levelid'] > 0 && $isVip) {
            //会员并且会员等级不是最低级
            $level = pdo_get(PDO_NAME . "halflevel", array('id' => $info['levelid']), array('name', 'cardimg','army'));
            $info['name'] = $level['name'];
            $info['levelarmy'] = $level['army'];
            $info['cardimg'] = !empty($level['cardimg']) ? tomedia($level['cardimg']) : tomedia($settings['cardimg']);
        } else if ($isVip) {
            //会员 但是会员等级为默认等级
            $info['name'] = $defaultlevel['name'];
            $info['levelarmy'] = 0;
            $info['cardimg'] = !empty($defaultlevel['cardimg']) ? tomedia($defaultlevel['cardimg']) : tomedia($settings['cardimg']);
        } else {
            //不是会员
            $info['name'] = '';
            $info['levelarmy'] = 0;
            $info['cardimg'] = tomedia($settings['cardimg']) ?: $defaultCardImg;
        }

        $info['cardimg'] = $info['cardimg'] ? tomedia($info['cardimg']) : $defaultCardImg;
        $info['endtime'] = date('Y-m-d', $info['expiretime']);
        $info['cardimg'] = $info['cardimg'] ? tomedia($info['cardimg']) : tomedia($set['cardimg']);
        $info['avatar'] = $_W['wlmember']['avatar'];
        //获取已节省的信息
        $base = Setting::wlsetting_read('halfcard');
        //$info['is_statistics'] = $info['id'] > 0 ? $base['statisticsdiv'] : 0 ;//0=隐藏；1=显示
        if ($info['id'] > 0 && $base['statisticsdiv']) {
            $where['mid'] = $_W['mid'];
            $where['uniacid'] = $_W['uniacid'];
            $totalPrice = pdo_getcolumn(PDO_NAME . 'timecardrecord', $where, array("SUM(ordermoney)"));
            $price = pdo_getcolumn(PDO_NAME . 'timecardrecord', $where, array("SUM(realmoney)"));
            $savePrice = $totalPrice - $price;
            $info['total_price'] = sprintf("%.2f", $totalPrice) ?: 0.00;//消费金额
            $info['price'] = sprintf("%.2f", $savePrice) ?: 0.00;//节省金额
        }
        //获取实卡编号
        $info['cardsn'] = pdo_getcolumn(PDO_NAME . 'halfcard_realcard', array('cardid' => $info['id']), 'cardsn');
        if (!$info['cardsn']) {
            $info['cardsn'] = 0;
        }
        //N814定制项目
        if (file_exists(PATH_MODULE . 'N814.log')) {
            $info['banknum'] = $_W['wlmember']['card_number'];
        }
        return is_array($info) ? $info : [];
    }

    /**
     * Comment: 电商联盟定制 同步会员卡到另一个模块
     * Author: wlf
     * Date: 2020/04/16 16:46
     * @return array|bool
     */
    static function toHccardMode($mid, $username, $mobile = '',$levelid = 0)
    {
        global $_W;
        $member = pdo_get('wlmerchant_member', array('id' => $mid), array('nickname', 'uid', 'uniacid', 'avatar', 'realname', 'mobile', 'openid'));
        if (!empty($member['openid'])) {
            $hcmember = pdo_get('hccard_user', array('openid' => $member['openid']), array('id', 'username', 'tel'));
            if(empty($levelid)){
                $levelinfo = Setting::wlsetting_read('halflevel');
                $hclevel = $levelinfo['hhklevel'];
            }else{
                $hclevel = pdo_getcolumn('wlmerchant_halflevel',array('id'=>$levelid),'hhklevel');
            }
            $hclevel = $hclevel ? : 1;
            if (!empty($hcmember['id'])) {
                $update = ['is_pay' => 1];
                $update['level'] = $hclevel;
                if (empty($hcmember['username'])) {
                    $update['username'] = $username;
                }
                if (empty($hcmember['tel'])) {
                    $update['tel'] = $mobile;
                }
                pdo_update('hccard_user', $update, array('id' => $hcmember['id']));
            } else {
                $gender = pdo_getcolumn('mc_members', array('uid' => $member['uid']), 'gender');
                $data = [
                    'uniacid'    => $member['uniacid'],
                    'openid'     => $member['openid'],
                    'nickname'   => $member['nickname'],
                    'headimgurl' => tomedia($member['avatar']),
                    'username'   => $username,
                    'tel'        => $mobile,
                    'createtime' => time(),
                    'level'      => $hclevel,
                    'gender'     => $gender,
                    'is_pay'     => 1
                ];
                pdo_insert('hccard_user', $data);
            }
        }
    }
    /**
     * Comment: 获取激活码根据关键字分组后的信息
     * Author: zzw
     * Date: 2020/9/14 15:56
     * @return array|bool|mixed
     */
    public static function getGroupList(){
        global $_W,$_GPC;
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND remark <> '' AND status = 0 ";
        //信息获取
        $field = "remark,count(*) as total";
        $sql = "SELECT {$field} FROM  ".tablename(PDO_NAME."token");
        $order = " ORDER BY id DESC ";
        $group = " GROUP BY remark ";
        $list = pdo_fetchall($sql.$where.$group.$order);

        return $list;
    }
    /**
     * Comment: 根据关键字获取某个激活码序列中的一个激活码
     * Author: zzw
     * Date: 2020/9/21 14:42
     * @param $keyword
     * @return array
     */
    public static function getActivationCode($keyword){
        global $_W;
        //判断是否存在关键字信息
        if (!$keyword) return error(0 , '不存在的激活码序列');
        //判断是否存在激活码
        $sql      = "SELECT id,`number` FROM " . tablename(PDO_NAME . "token")
            . " WHERE remark = '{$keyword}' AND status = 0 AND uniacid = {$_W['uniacid']} "
            . " ORDER BY createtime ASC,id ASC ";
        $codeInfo = pdo_fetch($sql);
        if (!$codeInfo) return error(0 , '不存在的激活码序列');
        //锁定当前激活码
        $res = pdo_update(PDO_NAME . "token" , ['status' => 2] , ['id' => $codeInfo['id']]);
        if (!$res) return error(0 , '不存在的激活码序列');
        //返回激活码信息
        return $codeInfo;
    }




}
