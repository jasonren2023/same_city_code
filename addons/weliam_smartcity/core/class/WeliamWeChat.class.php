<?php
/**
 * Comment: 公众号方法类
 * Author: ZZW
 * Date: 2018/9/5
 * Time: 17:03
 */
defined('IN_IA') or exit('Access Denied');
use EasyWeChat\Factory;
use qcloudcos\Cosapi;
class WeliamWeChat {
    /**
     * Comment: 验证是否为一卡通会员
     * Author: zzw
     * Date: 2019/8/29 17:11
     * @param      $mid
     * @param bool $state
     * @return bool|int|mixed
     */
    static function VipVerification($mid,$state = false) {
        global $_W;
        $wlsetting = pdo_get(PDO_NAME . "setting", array('uniacid' => $_W['uniacid'], 'key' => 'halfcard'));
        $wlsetting['value'] = unserialize($wlsetting['value']);
        $now = time();
        if ($wlsetting['value']['halfcardtype'] == 2) {
            $halfcardflag = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} AND aid = {$_W['aid']} AND expiretime > {$now} AND disable != 1");
        } else {
            $halfcardflag = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} AND expiretime > {$now} AND disable != 1");
        }
        //判断state返回内容
        if($state) return is_array($halfcardflag) && count($halfcardflag) > 0 && $halfcardflag['id'] > 0 ? $halfcardflag['id'] : 0 ;
        return $halfcardflag;
    }
    /**
     * Comment: 获取头条信息列表
     * Author: zzw
     * Date: 2019/8/29 17:11
     * @param bool $shop_id
     * @param int  $page
     * @param int  $pageNum
     * @return array
     */
    static function getHeadline($shop_id = false, $page = 1, $pageNum = 10,$one_id = 0,$two_id = 0,$sort = 0){
        global $_W;
        #1、条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if ($shop_id) $where .= " AND sid = {$shop_id} ";
        if($one_id > 0){
            $where .= " AND one_id = {$one_id} ";
        }
        if($two_id > 0){
            $where .= " AND two_id = {$two_id} ";
        }
        if($sort == 1){
            $order = " ORDER BY browse DESC ";
        }else{
            $order = " ORDER BY release_time DESC ";
        }
        $pageStart = $page * $pageNum - $pageNum;
        #2、信息获取
        $list = pdo_fetchall("SELECT id,title,summary,display_img,author,author_img,browse,one_id,two_id FROM "
            .tablename(PDO_NAME."headline_content") .$where.$order." LIMIT {$pageStart},{$pageNum}");
        foreach ($list as $k => &$v) {
            $v['display_img'] = tomedia($v['display_img']);
            $v['author_img'] = tomedia($v['author_img']);
            $v['one_name'] = implode(pdo_get(PDO_NAME . 'headline_class' , ['id' => $v['one_id']] , ['name']));
            $v['two_name'] = implode(pdo_get(PDO_NAME . 'headline_class' , ['id' => $v['two_id']] , ['name']));
            unset($v['one_id']);
            unset($v['two_id']);
        }
        return $list;
    }
    /**
     * Comment: 获取某个店铺销量最好的商品
     * Author: zzw
     * Date: 2019/8/29 17:12
     * @param $Atable   string  商品表
     * @param $Btable   string  订单表
     * @param $field    string  查询的字段信息
     * @param $where    string  查询条件
     * @param $group    string  分组信息
     * @param $relation string  两表之间的关联信息
     * @param $SpareW   string  备用条件，如果没有销量最好的商品时 查询任意一条本店铺的商品
     * @param $SpareF   string  备用查询字段
     * @return string
     */
    static function getSalesChampion($Atable, $Btable, $field, $where, $group, $relation, $SpareW, $SpareF) {
        $info = pdo_fetchall("SELECT {$field} FROM "
            . tablename(PDO_NAME . $Atable)
            . " a LEFT JOIN "
            . tablename(PDO_NAME . $Btable)
            . " b ON {$relation} "
            . " WHERE {$where} GROUP BY {$group}");
        $num = array_column($info, 'num');
        array_multisort($num, SORT_DESC, $info);
        $info = $info[0];
        //$info 不存在，则当前店铺当前种类商品暂时没有销售 直接获取一个商品
        if (!$info) {
            $info = pdo_fetchall("SELECT {$SpareF} FROM "
                . tablename(PDO_NAME . $Atable)
                . " WHERE {$SpareW}");
            $info = $info[0];
        }
        return $info ? $info : '';
    }
    /**
     * Comment: 通过店铺列表 获取店铺每种类型的商品中销量最好的一个
     * Author: zzw
     * Date: 2019/8/29 17:13
     * @param $shopList
     * @return mixed
     */
    static function getStoreList($shopList) {
        global $_W, $_GPC;
        foreach ($shopList as $k => &$v) {
            $id = $v['id'];
            //获取店铺的头条信息
            $headline = WeliamWeChat::getHeadline($id, 1, 1);
            $headline = $headline[0] ? $headline[0] : '';
            if ($headline) {
                unset($headline['summary']);
                unset($headline['display_img']);
                unset($headline['author']);
                unset($headline['author_img']);
                unset($headline['browse']);
                unset($headline['one_name']);
                unset($headline['two_name']);
                $headline['jump_link'] = h5_url('pages/mainPages/headline/headlineDetail',['headline_id'=>$headline['id']]);
            }
            $v['headline'] = $headline;
            //获取店铺每种商品中销量最好的一件商品的详细信息
            #1、抢购信息
            $goods['active'] = self::getSalesChampion('rush_activity', 'rush_order', 'a.id,a.name,count(b.activityid) as num', "a.sid = {$id} AND a.status IN (1,2) ", 'b.activityid', "a.id = b.activityid", " sid = {$id} AND status IN (1,2) ", "id,name");//status IN (1,2)
            $goods['active']['jump_link'] = h5_url('pages/subPages/goods/index',['type'=>1,'id'=>$goods['active']['id']]);
            #2、团购信息
            $goods['groupon'] = self::getSalesChampion('groupon_activity', 'order', 'a.id,a.name,count(b.fkid) as num', "a.sid = {$id} AND a.status IN (1,2) AND b.plugin = 'groupon' ", 'b.fkid', "a.id = b.fkid", " sid = {$id} AND status IN (1,2) ", "id,name");
            $goods['groupon']['jump_link'] = h5_url('pages/subPages/goods/index',['type'=>2,'id'=>$goods['groupon']['id']]);
            #3、折扣信息
            $goods['halfcard'] = self::getSalesChampion('halfcardlist', 'timecardrecord', 'a.id,a.title as name,count(b.activeid) as num ', "a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 1 AND b.type = 1 AND b.merchantid = {$id}", 'b.activeid', "a.id = b.activeid", " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1  AND merchantid = {$id} ", "id,title as name");
            $goods['halfcard']['jump_link'] = h5_url('pages/subPages2/newBuyOrder/buyOrder', array('id' => $id));
            #4、礼包信息
            $goods['packages'] = self::getSalesChampion('package', 'timecardrecord', 'a.id,a.title as name,count(b.activeid) as num ', "a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 1 AND b.type = 2 AND b.merchantid = {$id}", 'b.activeid', "a.id = b.activeid", " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1  AND merchantid = {$id} ", "id,title as name");
            $goods['packages']['jump_link'] = h5_url('pages/mainPages/memberCard/memberCard');
            #5、超级券信息
            $goods['coupon'] = self::getSalesChampion('couponlist', 'order', 'a.id,a.title as name,count(b.fkid) as num ', "a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.merchantid = {$id} AND a.status IN (1,2) AND b.plugin = 'coupon' AND a.is_show = 0 ", 'b.fkid', "a.id = b.fkid", " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND merchantid = {$id} AND status IN (1,2) AND is_show = 0 ", "id,title as name");
            $goods['coupon']['jump_link'] = h5_url('pages/subPages/goods/index',['type'=>5,'id'=>$goods['coupon']['id']]);
            #6、拼团信息
            $goods['fightgroup'] = self::getSalesChampion('fightgroup_goods', 'order', 'a.id,a.name,count(b.fkid) as num ', "a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.merchantid = {$id} AND a.status IN (1,2) AND b.plugin = 'wlfightgroup'", 'b.fkid', "a.id = b.fkid", " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND merchantid = {$id} AND status IN (1,2) ", "id,name");
            $goods['fightgroup']['jump_link'] = h5_url('pages/subPages/goods/index',['type'=>3,'id'=>$goods['fightgroup']['id']]);
            #7、砍价信息
            $goods['bargain'] = self::getSalesChampion('bargain_activity', 'order', 'a.id,a.name,count(b.fkid) as num ', " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.sid = {$id} AND a.status IN (1,2) AND b.plugin = 'bargain'", 'b.fkid', "a.id = b.fkid", " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND sid = {$id} AND status IN (1,2) ", "id,name");
            $goods['bargain']['jump_link'] = h5_url('pages/subPages/goods/index',['type'=>7,'id'=>$goods['bargain']['id']]);
            //删除多余的数据
            unset($v['location']);
            unset($v['url']);
            unset($v['cate']);
            if (!is_array($goods['active'])) unset($goods['active']);
            if (!is_array($goods['groupon'])) unset($goods['groupon']);
            if (!is_array($goods['halfcard'])) unset($goods['halfcard']);
            if (!is_array($goods['packages'])) unset($goods['packages']);
            if (!is_array($goods['coupon'])) unset($goods['coupon']);
            if (!is_array($goods['fightgroup'])) unset($goods['fightgroup']);
            if (!is_array($goods['bargain'])) unset($goods['bargain']);





            $v['goods'] = $goods;
        }
        return $shopList;
    }
    /**
     * Comment: 图片上传到远程服务器
     * Author: zzw
     * Date: 2019/8/29 17:14
     * @param      $filename
     * @param bool $auto_delete_local
     * @return bool|string
     * @throws Exception
     */
    static function file_remote_upload($filename, $auto_delete_local = true) {
        global $_W;
        //本公众号设置信息
        if($_W['wlsetting']['enclosure']['service'] == 2){
            $_W['setting']['remote']['type'] = 2;
            $_W['setting']['remote']['alioss'] = $_W['wlsetting']['enclosure']['alioss'];
        }else if($_W['wlsetting']['enclosure']['service'] == 3){
            $_W['setting']['remote']['type'] = 3;
            $_W['setting']['remote']['qiniu'] = $_W['wlsetting']['enclosure']['qiniu'];
        }else if($_W['wlsetting']['enclosure']['service'] == 4){
            $_W['setting']['remote']['type'] = 4;
            $_W['setting']['remote']['cos'] = $_W['wlsetting']['enclosure']['tengxun'];
        }
        if (empty($_W['setting']['remote']['type'])) {
            return false;
        }
        if ($_W['setting']['remote']['type'] == '1') {
            load()->library('ftp');
            $ftp_config = array(
                'hostname' => $_W['setting']['remote']['ftp']['hostname'],
                'username' => $_W['setting']['remote']['ftp']['username'],
                'password' => $_W['setting']['remote']['ftp']['password'],
                'port'     => $_W['setting']['remote']['ftp']['port'],
                'ssl'      => $_W['setting']['remote']['ftp']['ssl'],
                'passive'  => $_W['setting']['remote']['ftp']['passive'],
                'timeout'  => $_W['setting']['remote']['ftp']['timeout'],
                'rootdir'  => $_W['setting']['remote']['ftp']['rootdir'],
            );
            $ftp = new Ftp($ftp_config);
            if (true === $ftp->connect()) {
                $response = $ftp->upload(ATTACHMENT_ROOT . '/' . $filename, $filename);
                if ($auto_delete_local) {
                    self::file_delete($filename);
                }
                if (!empty($response)) {
                    //return true;
                } else {
                    return '远程附件上传失败，请检查配置并重新上传';
                }
            } else {
                return '远程附件上传失败，请检查配置并重新上传';
            }
        } elseif ($_W['setting']['remote']['type'] == '2') {
            load()->library('oss');
            load()->model('attachment');
            $buckets = attachment_alioss_buctkets($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret']);
            $endpoint = 'http://' . $buckets[$_W['setting']['remote']['alioss']['bucket']]['location'] . '.aliyuncs.com';
            try {
                $ossClient = new \OSS\OssClient($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret'], $endpoint);
                $ossClient->uploadFile($_W['setting']['remote']['alioss']['bucket'], $filename, ATTACHMENT_ROOT . $filename);
            } catch (\OSS\Core\OssException $e) {
                return $e->getMessage();
            }
            if ($auto_delete_local) self::file_delete($filename);
        } elseif ($_W['setting']['remote']['type'] == '3') {
            load()->library('qiniu');
            $auth = new Qiniu\Auth($_W['setting']['remote']['qiniu']['accesskey'], $_W['setting']['remote']['qiniu']['secretkey']);
            $config = new Qiniu\Config();
            $uploadmgr = new Qiniu\Storage\UploadManager($config);
            $putpolicy = Qiniu\base64_urlSafeEncode(json_encode(array(
                'scope' => $_W['setting']['remote']['qiniu']['bucket'] . ':' . $filename,
            )));
            $uploadtoken = $auth->uploadToken($_W['setting']['remote']['qiniu']['bucket'], $filename, 3600, $putpolicy);
            [$ret, $err] = $uploadmgr->putFile($uploadtoken, $filename, ATTACHMENT_ROOT . '/' . $filename);
            if ($auto_delete_local) {
                self::file_delete($filename);
            }
            if ($err !== null) {
                return '远程附件上传失败，请检查配置并重新上传';
            }
        } elseif ($_W['setting']['remote']['type'] == '4') {
            //使用腾讯4.2版本上传
            //include IA_ROOT . "/framework/library/cosv4.2/include.php";//微擎5.0有问题  强制使用4.2
            load()->library('cos');
            if(in_array('qcloudcos\Cosapi',get_declared_classes())){
                //使用腾讯4.2版本上传
                $bucket = trim($_W['setting']['remote']['cos']['bucket']);
                qcloudcos\Cosapi::setRegion(trim($_W['setting']['remote']['cos']['local']));
                $uploadRet = qcloudcos\Cosapi::upload($bucket, ATTACHMENT_ROOT . $filename, '/' . $filename, '', 3 * 1024 * 1024, 0);
                if ($uploadRet['code'] != 0) {
                    switch ($uploadRet['code']) {
                        case -62:
                            $message = '输入的appid有误';
                            break;
                        case -79:
                            $message = '输入的SecretID有误';
                            break;
                        case -97:
                            $message = '输入的SecretKEY有误';
                            break;
                        case -166:
                            $message = '输入的bucket有误';
                            break;
                    }
                    return $message ? : $uploadRet['message'];
                }
                if ($auto_delete_local) self::file_delete($filename);
            }else {
                //使用腾讯5版本上传
                load()->library('cosv5');
                try {
                    $bucket = $_W['setting']['remote']['cos']['bucket'] . '-' . $_W['setting']['remote']['cos']['appid'];
                    $params = [
                        'region'      => $_W['setting']['remote']['cos']['local'] ,
                        'credentials' => [
                            'secretId'  => $_W['setting']['remote']['cos']['secretid'] ,
                            'secretKey' => $_W['setting']['remote']['cos']['secretkey']
                        ]
                    ];
                    $cosClient = new Qcloud\Cos\Client($params);
                    $cosClient->Upload($bucket, $filename, fopen(ATTACHMENT_ROOT . $filename, 'rb'));
                    if ($auto_delete_local) self::file_delete($filename);
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }
    }
    /**
     * Comment: 图片上传后删除本地图片
     * Author: zzw
     * Date: 2019/8/29 17:14
     * @param $file
     * @return bool
     */
    static function file_delete($file) {
        if (empty($file)) {
            return false;
        }
        if (file_exists($file)) {
            @unlink($file);
        }
        if (file_exists(ATTACHMENT_ROOT . '/' . $file)) {
            @unlink(ATTACHMENT_ROOT . '/' . $file);
        }
        return true;
    }
    /**
     * Comment: 获取已购买当前商品的用户信息   已参与的人数
     * Author: zzw
     * Date: 2019/8/29 17:15
     * @param $state    int  状态：代表商品类型  1=抢购商品   2=团购商品  3=拼团商品  5=优惠券
     * @param $id       int  商品id
     * @return mixed
     */
    static function PurchaseUser($state, $id) {
        global $_W, $_GPC;
        $limit = 5;
        //条件拼装
        $where = "uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}";//条件
        $table = 'order';//表
        switch ($state) {
            case 1:
                $table = 'rush_order';
                $where .= " AND `activityid` = {$id} ";
                break;//抢购商品
            case 2:
                $where .= " AND plugin = 'groupon' AND fkid = {$id} ";
                break;//团购商品
            case 3:
                $where .= " AND plugin = 'wlfightgroup' AND fkid = {$id} ";
                break;//拼团商品
            case 5:
                $where .= " AND plugin = 'coupon' AND fkid = {$id} ";
                break;//优惠券
            case 7:
                $where .= " AND plugin = 'bargain' AND fkid = {$id} ";
                break;//砍价商品
        }
        //获取内容
        $info = pdo_fetchall("SELECT id,mid FROM " . tablename(PDO_NAME . $table)."WHERE {$where} ORDER BY id DESC LIMIT {$limit} ");
        if(!empty($info)){
            foreach($info as &$in){
                $member = pdo_get('wlmerchant_member',array('id' => $in['mid']),array('nickname','avatar'));
                $in['nickname'] = $member['nickname'];
                $in['avatar'] = tomedia($member['avatar']);
            }
        }
        $count = pdo_fetchall("SELECT id FROM " . tablename(PDO_NAME . $table)."WHERE {$where}");
        //信息拼装
        $data['info'] = $info;
        $data['count'] = count($count);

        return $data;
    }
    /**
     * Comment: 获取商品多规格信息列表
     * Author: zzw
     * Date: 2019/8/29 17:16
     * @param $id
     * @param $type
     * @return array
     */
    static function getSpec($id, $type,$vipstatus = 0) {
        global $_W;
        #1、根据商品类型判断获取条件
        # 商品type：1=抢购  2=团购  3=拼团 4=大礼包(无佣金) 5=优惠券 6=折扣卡(无佣金) 7=砍价商品
        # 规格type：1抢购 2拼团 3团购
        $where = " WHERE uniacid = {$_W['uniacid']} AND goodsid = {$id} ";
        switch ($type){
            case 1:$where .= " AND type = 1";break;//抢购
            case 2:$where .= " AND type = 3";break;//团购
            case 3:$where .= " AND type = 2";break;//拼团
            default:return [];break;//当前商品不支持多规格
        }
        #2、获取规格信息
        $list = pdo_fetchall("SELECT title,content FROM ".tablename(PDO_NAME."goods_spec").$where." ORDER BY displayorder ASC ");
        foreach($list as $key => &$val){
            //生成规格参数查询条件
            $idList = unserialize($val['content']);
            if(is_array($idList) && count($idList) > 1){
                $ids = implode($idList,',');
                $itemWhere = " WHERE id in ({$ids}) AND `show` = 1 ";
            }else{
                $itemWhere = " WHERE id = {$idList[0]} AND `show` = 1 ";
            }
            //获取规格参数信息
            $val['item'] = pdo_fetchall(" SELECT id,title FROM ".tablename(PDO_NAME."goods_spec_item").$itemWhere." ORDER BY displayorder ASC ");

            unset($val['content']);
        }
        #3、获取规格组合后的销售信息
        $info = pdo_fetchall("SELECT id,specs,stock,price,thumb,viparray,onedismoney,vipprice as aloneprice FROM ".tablename(PDO_NAME."goods_option").$where." ORDER BY id ASC ");
        #5、数据拼装
        $data['list'] = $list;//规格信息列表
        $data['info'] = $info;//规格参数列表

        return $data;
    }
    /**
     * Comment: 获取同城活动的多规格信息列表
     * Author: wlf
     * Date: 2020/10/30 09:22
     * @param $id
     * @return array
     */
    static function getActivitySpec($id){
        global $_W;
        $where = " WHERE uniacid = {$_W['uniacid']} AND activityid = {$id} ";
        $info = pdo_fetchall("SELECT id,name,maxnum,price,viparray FROM ".tablename(PDO_NAME."activity_spec").$where." ORDER BY id ASC ");
        foreach($info as $infoK => &$infoV){
            $stopBuyNum = WeliamWeChat::getSalesNum(6,$id,$infoV['id'],1);
            $infoV['stock'] = $infoV['maxnum'] - intval($stopBuyNum);
        }
        //统计
        $prices = array_column($info,'price');
        $data['minprice'] = min($prices);
        $data['maxprice'] = max($prices);
        $stkList = array_column($info,'stock');
        $data['stk'] = array_sum($stkList);
        $data['info'] = $info;

        return $data;
    }
    /**
     * Comment: 新的首页商品信息查询
     * Author: wlf
     * Date: 2020/09/16 15:28
     * @param $plugin
     * @param $id
     * @return bool
     */
    static function getNewHomeGoods($plugin,$id) {
        global $_W;
        $mid = $_W['mid'];
        $usercard = WeliamWeChat::VipVerification($mid);
        if($usercard['id'] > 0){
            $usercardlevel = $usercard['levelid'];
        }else{
            $usercardlevel = -1;
        }
        switch ($plugin) {
            case 1:
                $goods = pdo_fetch("SELECT a.optionstatus,IFNULL(a.pv,0) as pv,a.thumbs as adv,a.appointment,a.allowapplyre,a.communityid,'1' as type,b.logo as shop_logo,a.op_one_limit as buy_limit,a.status,a.id,a.thumb as logo,a.name as goods_name,a.price,a.oldprice,a.num as totalnum,b.storename,b.id as sid,a.starttime,a.endtime,a.viparray,a.vipstatus,a.allsalenum,b.address FROM "
                    . tablename(PDO_NAME . "rush_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = 'rush';
                $goods['postertype'] = '3';
                break;//抢购商品
            case 2:
                $goods = pdo_fetch("SELECT IFNULL(a.pv,0) as pv,a.optionstatus,a.thumbs as adv,a.appointment,a.allowapplyre,a.communityid,'2' as type,b.logo as shop_logo,a.op_one_limit as buy_limit,b.address,a.status,a.id,a.thumb as logo,a.name as goods_name,a.price,a.oldprice,a.num as totalnum,b.storename,b.id as sid,a.starttime,a.endtime,a.viparray,a.vipstatus,a.allsalenum FROM "
                    . tablename(PDO_NAME . "groupon_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'groupon';
                $goods['postertype'] = '4';
                break;//团购商品
            case 3:
                $goods = pdo_fetch("SELECT a.specstatus as optionstatus,a.pv,a.adv,a.communityid,'3' as type,a.is_pool,a.allowapplyre,a.appointment,b.logo as shop_logo,a.buylimit as buy_limit,b.address,a.status,a.id,a.logo,a.name as goods_name,a.price,a.aloneprice as oldprice,stock as totalnum,a.peoplenum,b.storename,b.id as sid,a.viparray,a.realsalenum,a.falsesalenum as allsalenum FROM "
                    . tablename(PDO_NAME . "fightgroup_goods")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'wlfightgroup';
                $goods['postertype'] = '6';
                break;//拼团商品
        }
        $goods['logo'] = tomedia($goods['logo']);
        $goods['shop_logo'] = tomedia($goods['shop_logo']);
        $advs = unserialize($goods['adv']);
        $goods['long_logo'] = tomedia($advs[0]);
        if ($plugin == 1) {
            //抢购商品的销量
            $stopBuyNum = WeliamWeChat::getSalesNum(1,$id,0,1);
        } else if ($plugin == 2) {
            $stopBuyNum = WeliamWeChat::getSalesNum(2,$id,0,1);
        } else if ($plugin == 3) {
            $stopBuyNum = WeliamWeChat::getSalesNum(3,$id,0,1);
        }
        #3、加上虚拟销量
        if ($goods['allsalenum'] && empty($storeflag)) {
            $stopBuyNum = intval($stopBuyNum) + intval($goods['allsalenum']);
            $goods['totalnum'] = $goods['totalnum'] + $goods['allsalenum'];
        }
        $purchaseUser = WeliamWeChat::PurchaseUser($plugin, $goods['id']);
        $goods['user_list'] = array_column($purchaseUser['info'], 'avatar');//购买当前商品的用户的头像
        $goods['user_num'] = $stopBuyNum;//$purchaseUser['count'];//已参与人数
        $goods['buy_num'] = $stopBuyNum ? $stopBuyNum : 0;//获取已售数量
        //获取当前商品的浏览记录
        //$browseRecord = array_column(self::getBrowseRecord($plugin, $goods['id']), 'avatar');
        //$goods['user_list'] = is_array($browseRecord) ? $browseRecord :[];
        //$goods['user_list'] = is_array($browseRecord) ? $browseRecord :[];
        #4、已销售数量的百分比
        if($goods['buy_num'] > 0 && $goods['totalnum'] > 0){
            $goods['buy_percentage'] = sprintf("%.2f", ($goods['buy_num'] / $goods['totalnum']) * 100);
            if($goods['buy_percentage']>100){
                $goods['buy_percentage'] = 100.00;
            }
        }else{
            $goods['buy_percentage'] = 0.00;
        }
        $goods['stk'] = $goods['totalnum'] - $stopBuyNum;
        if($goods['stk']<0){$goods['stk'] = 0;}
        #7、商品为抢购商品时 并且状态为未开始时 判断用户是否关注当前抢购商品  0=未关注，1=已关注
        $goods['is_follow'] = 0;
        if($plugin == 1 && $goods['status'] == 1){
            $isFollow = pdo_getcolumn(PDO_NAME.'rush_follows',['mid'=>$mid,'actid'=>$id],'id');
            if($isFollow) $goods['is_follow'] = 1;
        }
        #9、获取商品的优惠金额  Discount
        if($goods['vipstatus'] == 1){
            $goods['discount_price'] = self::getVipDiscount($goods['viparray'],$usercardlevel);
        }else{
            $goods['discount_price'] = 0;
        }
        if($goods['vipstatus'] != 1){
            $goods['discount_price'] = 0;
        }

        if($goods['optionstatus'] > 0){
            if($plugin == 9){
                $specinfo = WeliamWeChat::getActivitySpec($id);
                $goods['spec'] = $specinfo['info'];
                if($goods['vipstatus'] == 1){
                    foreach($goods['spec'] as &$spb){
                        $spb['discount_price'] = self::getVipDiscount($spb['viparray'],$usercardlevel);
                        if($spb['discount_price'] < 0.01){
                            $spb['discount_price'] = $goods['discount_price'];
                        }
                    }
                }
                $goods['minprice'] = $specinfo['minprice'];
                $goods['maxprice'] = $specinfo['maxprice'];
                $goods['stk'] = $specinfo['stk'];
            }else{
                $goods['spec'] = WeliamWeChat::getSpec($id, $plugin,$goods['vipstatus']);
                //从新获取库存信息
                foreach ($goods['spec']['info'] as &$sp){
                    if ($plugin == 1) {
                        //抢购商品的销量
                        $stopBuyNum = WeliamWeChat::getSalesNum(1,$goods['id'],$sp['id'],1);
                        $sp['stock'] = $sp['stock'] - intval($stopBuyNum);
                    }else if($plugin == 2 || $plugin == 3){
                        $stopBuyNum = WeliamWeChat::getSalesNum($plugin,$goods['id'],$sp['id'],1);
                        $sp['stock'] = $sp['stock'] - intval($stopBuyNum);
                    }
                    $sp['thumb'] = tomedia($sp['thumb']);
                    if($goods['vipstatus'] == 1){
                        $sp['discount_price'] = self::getVipDiscount($sp['viparray'],$usercardlevel);
                        if($sp['discount_price'] < 0.01 ){
                            $sp['discount_price'] = $goods['discount_price'];
                        }
                    }
                }
                $stkList = array_column($goods['spec']['info'],'stock');
                $goods['stk'] = array_sum($stkList);
            }
        }

        return $goods;

    }
    /**
     * Comment: 首页商品信息查询
     * Author: zzw
     * Date: 2019/8/29 17:17
     * @param $plugin
     * @param $id
     * @return bool
     */
    static function getHomeGoods($plugin, $id,$storeflag = 0,$mid = 0,$time = 0) {
        global $_W;
        if(empty($mid)){
            $mid = $_W['mid'];
        }
        if(empty($time)){
            $time = time();
        }
        #商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品 9 = 同城活动
        #1、获取商品信息
        switch ($plugin) {
            case 1:
                $goods = pdo_fetch("SELECT a.id,a.optionstatus,IFNULL(a.pv,0) as pv,a.thumbs as adv,a.appointment,a.appointstatus,a.allowapplyre,a.communityid,'1' as type,b.logo as shop_logo,a.op_one_limit as buy_limit,a.status,a.id,a.thumb as logo,a.name as goods_name,a.price,a.oldprice,a.num as totalnum,b.storename,b.id as sid,a.starttime,a.endtime,a.viparray,a.vipstatus,a.allsalenum,b.address,a.pftid FROM "
                    . tablename(PDO_NAME . "rush_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = 'rush';
                $goods['postertype'] = '3';
                break;//抢购商品
            case 2:
                $goods = pdo_fetch("SELECT a.id,IFNULL(a.pv,0) as pv,a.optionstatus,a.thumbs as adv,a.appointment,a.appointstatus,a.allowapplyre,a.communityid,'2' as type,b.logo as shop_logo,a.op_one_limit as buy_limit,b.address,a.status,a.id,a.thumb as logo,a.name as goods_name,a.price,a.oldprice,a.num as totalnum,b.storename,b.id as sid,a.starttime,a.endtime,a.viparray,a.vipstatus,a.allsalenum,a.pftid FROM "
                    . tablename(PDO_NAME . "groupon_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'groupon';
                $goods['postertype'] = '4';
                break;//团购商品
            case 3:
                $goods = pdo_fetch("SELECT a.id,a.specstatus as optionstatus,a.pv,a.adv,a.communityid,'3' as type,a.is_pool,a.allowapplyre,a.appointment,a.appointstatus,b.logo as shop_logo,a.buylimit as buy_limit,b.address,a.status,a.id,a.logo,a.name as goods_name,a.price,a.oldprice,a.stock as totalnum,a.peoplenum,b.storename,b.id as sid,a.vipstatus,a.viparray,a.realsalenum,a.falsesalenum as allsalenum,a.aloneprice_switch FROM "
                    . tablename(PDO_NAME . "fightgroup_goods")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'wlfightgroup';
                $goods['postertype'] = '6';
                break;//拼团商品
            case 4:
                //获取礼包信息
                $goods = pdo_fetch("SELECT a.id,'1' as is_link,a.type as exttype,a.storemoney,a.extlink,a.extinfo,'4' as type,b.logo as shop_logo,
a.id,a.limit,a.datestatus,a.title as `name`,a.timeslimit,a.timestatus,a.starttime,a.endtime,a.packtimestatus,a.datestarttime,a.dateendtime,a.oplimit,a.weeklimit,a.monthlimit,a.price,a.usetimes,a.usetimes as surplus,b.storename,
b.logo,b.lat,b.lng,b.address,b.id as sid,REPLACE('table','table','package') as `plugin`,a.datestatus,a.usedatestatus,a.week,a.day,allnum,resetswitch,`level`  FROM "
                    . tablename(PDO_NAME . "package")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . "b ON a.merchantid = b.id WHERE a.id = {$id} ");
                $goods['logo'] = tomedia($goods['logo']);
                $goods['shop_logo'] = tomedia($goods['shop_logo']);
                //获取已被使用的数量礼包库存
                $hasUsed = pdo_fetchcolumn("SELECT COUNT(*) as stk FROM " . tablename(PDO_NAME . "timecardrecord") . " WHERE `type` = 2 AND activeid = {$id}");
                $goods['stk'] = $goods['allnum'];
                if ($goods['allnum'] > 0) {
                    $goods['stk'] = $goods['allnum'] - $hasUsed;
                }
                $userCardInfo = WeliamWeChat::VipVerification($mid);
                //查看用户剩余次数
                switch ($goods['datestatus']) {
                    case 2:
                        $startTime = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                        $goods['newlimit'] = '每周';
                        break;//每周
                    case 3:
                        $startTime = mktime(0, 0, 0, date('m'), 1, date('Y'));
                        $goods['newlimit'] = '每月';
                        break;//每月
                    case 4:
                        $startTime = mktime(0, 0, 0, 1, 1, date('Y'));
                        $goods['newlimit'] = '每年';
                        break;//每年
                    default:
                        $goods['newlimit'] = '可用';
                        break;
                }
                $goods['newlimit'] .= $goods['usetimes'].'次';
                if ($mid) {
                    //获取查询条件及可以使用的总次数
                    $where = " WHERE `type` = 2 AND activeid = {$id} AND mid = {$mid} ";
                    //判断是否开启周期使用
                    $goods['datestatus'] = $goods['datestatus'] ? : 1;
                    //判断是否开启续卡重置功能
                    if ($goods['resetswitch'] == 1) {
                        //获取用户最近续卡时间
                        $time1 = pdo_fetchcolumn("SELECT paytime FROM " . tablename(PDO_NAME . "halfcard_record")
                            . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$mid} AND cardid > 0 ORDER BY paytime DESC");
                        $time2 = pdo_getcolumn(PDO_NAME.'halfcardmember',array('id'=>$userCardInfo['id']),'createtime');
                        $time1 = intval($time1);
                        $time2 = intval($time2);
                        $time = $time1 > $time2 ? $time1 : $time2;
                        if ($startTime < $time) $startTime = $time;//续卡重置礼包使用时间
                    }
                    if($startTime > 0) {
                        $where .= " AND usetime >= " . $startTime;
                    }
                    //获取已使用数量
                    $surplus = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "timecardrecord") . $where);
                    $goods['surplus'] = ($goods['usetimes'] - $surplus) > 0 ? ($goods['usetimes'] - $surplus) : 0;
                    //判断用户是否为一卡通会员 并且获取一卡通的id
                    $goods['card'] = self::VipVerification($mid,true);
                }
                //外链礼包的信息处理
                if ($goods['exttype'] == 1) {
                    $setInfo = unserialize($goods['extinfo']);
                    $goods['url']        = $goods['extlink'];
                    $goods['storename']  = $setInfo['storename'];
                    $goods['shop_logo']  = tomedia($setInfo['storelogo']);
                    $goods['is_link']    = intval(0);//0=外链礼包
                }
                //判断当前用户是否可用该礼包
                $lvInfo = unserialize($goods['level']);//会员限制列表
                $goods['use_where'] = intval(1);
                if($userCardInfo['id'] > 0){
                    //明确会员等级限制 只能是当前等级的会员可以使用
                    if($lvInfo && !in_array($userCardInfo['levelid'],$lvInfo)){
                        $goods['use_where'] = intval(0);
                    }
                }else{
                    $goods['use_where'] = intval(0);
                }

                unset($goods['extinfo']);
                unset($goods['extlink']);
                unset($goods['storelogo']);

                return $goods;
                break;//大礼包
            case 5:
                $goods = pdo_fetch("SELECT a.id,IFNULL(a.pv,0) as pv,a.indexorder,a.extflag,a.extlink,a.extinfo,'5' as type,b.logo as shop_logo,a.get_limit as buy_limit,a.status,a.id,a.logo,a.title as goods_name,a.price,a.vipstatus,a.viparray,quantity as totalnum,b.storename,b.id as sid,a.surplus,a.is_charge,a.adv FROM "
                    . tablename(PDO_NAME . "couponlist")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'coupon';
                //外链卡券的信息处理
                if($goods['extflag'] == 1){
                    $extInfo = unserialize($goods['extinfo']);
                    $goods['storename'] = $extInfo['storename'];
                }
                $goods['postertype'] = '5';
                break;//优惠券
            case 6:
                $goods = pdo_fetch("SELECT a.id,'1' as is_link,a.type as exttype,a.extlink,a.extinfo,'6' as type,a.describe,
b.logo as shop_logo,a.timeslimit as buy_limit,a.levelstatus,a.activearray,a.dayactarray,a.id,a.title as `name`,a.limit,a.datestatus,a.week,a.day,a.activediscount,
a.discount,a.daily,b.id as sid,b.storename,a.pv,a.sort,b.logo,a.level,b.payonline,b.panorama_discount FROM "
                    . tablename(PDO_NAME . "halfcardlist")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = $pluginType = 'halfcard';
                $goods['logo'] = tomedia($goods['logo']);
                $goods['shop_logo'] = tomedia($goods['shop_logo']);
                if($goods['datestatus'] == 3 && $goods['daily'] > 0 ){
                    $goods['activediscount'] = $goods['discount'];
                }
                //判断用户是否为一卡通会员 并且获取一卡通的id
                if ($mid){
                    $vipInfo = self::VipVerification($mid);
                    $goods['card'] = $vipInfo['id'];
                }
                //判断等级限制  获取折扣信息
                $lvevl = unserialize($goods['level']);
                //分级折扣
                if($goods['levelstatus'] > 0){
                    $le_ac_array = unserialize($goods['activearray']);
                    $le_day_array = unserialize($goods['dayactarray']);
                    if(empty($vipInfo)){
                        $goods['activediscount'] = min($le_ac_array);
                        $goods['discount'] = min($le_day_array);
                        $goods['lowtip'] = 1;
                    }else{
                        $goods['activediscount'] = $le_ac_array[$vipInfo['levelid']];
                        $goods['discount'] = $le_day_array[$vipInfo['levelid']];
                    }
                    $lvevl = [];
                }
                if(!empty($vipInfo) && !in_array($vipInfo['levelid'],$lvevl) && count($lvevl) > 0){
                    //获取当前折扣卡今天的折扣情况
                    $weekflag = date('w', $time);//星期
                    $dayflag2 = date('j', $time);//日期
                    switch ($goods['datestatus']) {
                        case 1:
                            //日期格式：星期
                            if($weekflag == 0){
                                $weekflag = 7;
                            }
                            $goods['week'] = unserialize($goods['week']);
                            if(!in_array($weekflag, $goods['week']) && $goods['daily'] > 0){
                                $goods['activediscount'] = $goods['discount'];
                            }
                            break;
                        case 2:
                            //日期格式：日期
                            $goods['day'] = unserialize($goods['day']);
                            if(!in_array($dayflag2, $goods['day']) && $goods['daily'] > 0){
                                $goods['activediscount'] = $goods['discount'];
                            }
                            break;
                    }
                    $goods['discount'] = $goods['panorama_discount'];
                }else if( (!empty($vipInfo) && Customized::init('customized336') ) || !Customized::init('customized336') ){
                    if(empty($goods['daily'])){
                        $goods['discount'] = $goods['panorama_discount'];
                    }
                    //获取当前折扣卡今天的折扣情况
                    $weekflag = date('w', $time);//星期
                    $dayflag2 = date('j', $time);//日期
                    switch ($goods['datestatus']) {
                        case 1:
                            //日期格式：星期
                            if($weekflag == 0){
                                $weekflag = 7;
                            }
                            $goods['week'] = unserialize($goods['week']);
                            if (in_array($weekflag, $goods['week'])) {
                                $goods['discount'] = $goods['activediscount'];
                            }else if($goods['daily'] > 0){
                                $goods['activediscount'] = $goods['discount'];
                            }
                            break;
                        case 2:
                            //日期格式：日期
                            $goods['day'] = unserialize($goods['day']);
                            if (in_array($dayflag2, $goods['day'])) {
                                $goods['discount'] = $goods['activediscount'];
                            }else if($goods['daily'] > 0){
                                $goods['activediscount'] = $goods['discount'];
                            }
                            break;
                    }
                }else{
                    $goods['discount'] = $goods['panorama_discount'];
                }

                //外链折扣卡的信息处理
                if ($goods['exttype'] == 1) {
                    $setInfo = unserialize($goods['extinfo']);
                    $goods['storename'] = $setInfo['storename'];
                    $goods['logo']      = tomedia($setInfo['storelogo']);
                    $goods['url']       = $goods['extlink'];
                    $goods['is_link']   = intval(0);//0=外链折扣卡
                    $goods['discount']  = $goods['activediscount'];
                    $goods['exdetail']  = htmlspecialchars_decode($goods['describe']);
                }
                #3、判断当前用户是否可用该折扣卡
                $userCardInfo = WeliamWeChat::VipVerification($mid);
                $lvInfo = unserialize($goods['level']);//会员限制列表
                $goods['use_where'] = intval(1);
                if($userCardInfo['id'] > 0){
                    //明确会员等级限制 只能是当前等级的会员可以使用
                    if($lvInfo && !in_array($userCardInfo['levelid'],$lvInfo)){
                        $goods['use_where'] = intval(0);
                    }
                }else{
                    $goods['use_where'] = intval(0);
                }

                unset($goods['extinfo']);
                unset($goods['extlink']);
                unset($goods['exttype']);
                unset($goods['week']);
                unset($goods['day']);
                unset($goods['daily']);
                unset($goods['datestatus']);

                return $goods;
                break;//折扣卡
            case 7:
                $goods = pdo_fetch("SELECT a.id,IFNULL(a.pv,0) as pv,a.tag,a.thumbs as adv,a.communityid,'7' as type,b.logo as shop_logo,b.address,a.allowapplyre,a.appointment,a.appointstatus,a.status,a.id,a.thumb as logo,a.name as goods_name,stock as totalnum,b.storename,b.id as sid,a.starttime,a.endtime,a.viparray,a.vipstatus,a.oldprice,a.price FROM "
                    . tablename(PDO_NAME . "bargain_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.id = {$id} ");
                $goods['plugin'] = 'bargain';
                $goods['postertype'] = '7';
                break;//砍价商品
            case 8:
                #2、判断用户是否为会员
                $cardid = WeliamWeChat::VipVerification($mid,true);
                #3、获取商品详细信息
                $field = " id,advs,title,thumb,old_price,description,IFNULL(pv,0) as pv,stock,community_id,`describe`,isdistri,
                    CASE WHEN {$cardid} > 0 AND vipstatus = 1 THEN vipcredit1 
                         ELSE  use_credit1
                    END as use_credit1,
                    CASE WHEN {$cardid} > 0 AND vipstatus = 1 THEN vipcredit2 
                         ELSE use_credit2
                    END as price ";
                $info = pdo_fetch("SELECT {$field} FROM " . tablename(PDO_NAME . "consumption_goods") . " WHERE id = {$id} ");
                if (!$info) Commons::sRenderError('商品不存在!');
                $info['thumb']       = tomedia($info['thumb']);
                $info['description'] = htmlspecialchars_decode($info['description']);
                $info['is_vip']      = $cardid;
                #4、幻灯片处理
                $info['advs'] = unserialize($info['advs']);
                if (is_array($info['advs']) && count($info['advs']) > 0) {
                    foreach ($info['advs'] as $key => &$val) {
                        $val = tomedia($val);
                    }
                }else{
                    $info['advs'] = [];
                }
                #5、浏览量添加
                $pv = $info['pv'] + 1;
                pdo_update(PDO_NAME . "consumption_goods" , [ 'pv' => $pv ] , [ 'id' => $id ]);
                #6、获取销量
                $info['total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "order") . " WHERE plugin = 'consumption' AND fkid = {$id} ");
                #7、获取社群设置
                if ($info['community_id'] > 0) {
                    $info['community'] = Commons::getCommunity($info['community_id']);
                }
                if(!$info['community']) unset($info['community']);
                #8、获取购买人头像信息
                $member              = pdo_fetchall("SELECT b.avatar FROM " . tablename(PDO_NAME . "order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "member")
                    . " as b ON a.mid = b.id WHERE a.plugin = 'consumption' AND a.fkid = {$id} GROUP BY b.id LIMIT 5");
                $info['avatar_list'] = is_array($member) && count($member) > 0 ? array_column($member , 'avatar') : [];
                #9、一卡通文本信息获取
                $info['halfcard_text'] = !empty($_W['wlsetting']['trade']['halfcardtext']) ? $_W['wlsetting']['trade']['halfcardtext'] : '一卡通';
                #10、分销助手，获取当前用户分享最高可以获得的佣金
                $info['dis_assistant'] = WeliamWeChat::getDisInfo($plugin,$id);
                #10、修改商品的人气（浏览量）信息
                $pv = intval($info['pv']) + 1;
                pdo_update(PDO_NAME."consumption_goods",['pv'=>$pv],['id'=>$id]);
                $info['postertype'] = '10';
                $info['plugin'] = 'integral';
                //价格处理
                $trade = Setting::wlsetting_read('trade');
                $creditText = $trade['credittext'];
                $info['price_text'] = "{$info['use_credit1']}{$creditText} + {$info['price']}元";
                //获取轮播图第一张
                $info['long_logo'] = $info['advs'][0];

                if(empty($info['stock'])){
                    $info['stock'] = 999;
                }
                return $info;
                break;//积分商品
            case 9:
                $goods = pdo_fetch("SELECT a.id,a.vipprice,a.optionstatus,a.threeurl,IFNULL(a.pv,0) as pv,a.advs as adv,'9' as type,b.logo as shop_logo,a.status,a.id,a.thumb as logo,a.title as goods_name,a.price,a.maxpeoplenum as totalnum,b.storename,b.id as sid,a.activestarttime,a.activeendtime,a.viparray,a.vipstatus,a.addresstype,a.address as acaddress,b.address,c.name as catename FROM "
                    . tablename(PDO_NAME . "activitylist")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id LEFT JOIN ". tablename(PDO_NAME . "activity_category")
                    . " c ON a.cateid = c.id WHERE a.id = {$id} ");
                $goods['plugin'] = 'activity';
                $goods['postertype'] = '9';
                if($goods['addresstype'] > 0){
                    $goods['address'] = $goods['acaddress'];
                }
                if($goods['optionstatus'] > 0 ){
                    $Aoptions = pdo_getall('wlmerchant_activity_spec',array('activityid' => $goods['id']),array('price'));
                    $Parray = array_column($Aoptions,'price');
                    $goods['price'] = min($Parray).'-'.max($Parray);
                }
                break;//同城活动
            case 10:
                //获取信息
                $field = "id,name as goods_name,price,thumb as logo,thumbs as adv,fictitiousnum,status,allstock";
                $goods = pdo_fetch("SELECT {$field} FROM "
                    . tablename(PDO_NAME . "delivery_activity")
                    ." WHERE id = {$id} ");
                //处理信息
                $goods['logo'] = tomedia($goods['logo']);
                $goods['long_logo'] = tomedia(unserialize($goods['adv'])[0]);
                $goods['plugin'] = 'citydelivery';
                //获取销量
                $stopBuyNum = pdo_fetchcolumn("SELECT sum(num) FROM "
                    . tablename(PDO_NAME . "delivery_order")
                    . " WHERE gid = {$goods['id']}  AND uniacid = {$_W['uniacid']} AND status IN (1,2)");
                $goods['buy_num'] = $stopBuyNum ? $stopBuyNum : 0;//获取已售数量
                $goods['buy_num'] = $goods['buy_num'] + $goods['fictitiousnum'];//添加虚拟销量
                //获取库存
                $goods['stk'] = $goods['allstock'] > 0 ? $goods['allstock'] - $goods['buy_num'] : 0;//-1则代表不限量
                unset($goods['adv'],$goods['fictitiousnum']);

                return $goods;
                break;//配送商品
        }
        #2、获取商品销量
        $goods['logo'] = tomedia($goods['logo']);
        $goods['shop_logo'] = tomedia($goods['shop_logo']);
        $advs = unserialize($goods['adv']);
        $goods['long_logo'] = tomedia($advs[0]);
        if ($plugin == 1) {
            //抢购商品的销量
            $stopBuyNum = WeliamWeChat::getSalesNum(1,$id,0,1,0,0,0,$goods['pftid']);
        } else if ($plugin == 5) {
            $stopBuyNum = WeliamWeChat::getSalesNum(4,$id,0,1);
        } else if ($plugin == 3) {
            $stopBuyNum = WeliamWeChat::getSalesNum(3,$id,0,1);
        } else if ($plugin == 7) {
            $stopBuyNum = WeliamWeChat::getSalesNum(5,$id,0,1);
        } else if ($plugin == 2) {
            $stopBuyNum = WeliamWeChat::getSalesNum(2,$id,0,1,0,0,0,$goods['pftid']);
        } else if ($plugin == 9) {
            $stopBuyNum = WeliamWeChat::getSalesNum(6,$id,0,1);
        } else {
            $stopBuyNum = pdo_fetchcolumn("SELECT sum(num) FROM "
                . tablename(PDO_NAME . "order")
                . " WHERE  fkid = {$goods['id']} AND plugin = '{$pluginType}'  AND uniacid = {$_W['uniacid']} AND status IN (0,1,2,3,6,9,4,8)");
        }
        #3、加上虚拟销量
        if ($goods['allsalenum'] && empty($storeflag)) {
            $stopBuyNum = intval($stopBuyNum) + intval($goods['allsalenum']);
            $goods['totalnum'] = $goods['totalnum'] + $goods['allsalenum'];
        }
        $purchaseUser = WeliamWeChat::PurchaseUser($plugin, $goods['id']);
        $goods['user_list'] = array_column($purchaseUser['info'], 'avatar');//购买当前商品的用户的头像
        $goods['user_num'] = $stopBuyNum;//$purchaseUser['count'];//已参与人数
        $goods['buy_num'] = $stopBuyNum ? $stopBuyNum : 0;//获取已售数量
        //获取当前商品的浏览记录
        //$browseRecord = array_column(self::getBrowseRecord($plugin, $goods['id']), 'avatar');
        //$goods['user_list'] = is_array($browseRecord) ? $browseRecord :[];
        //$goods['user_list'] = is_array($browseRecord) ? $browseRecord :[];
        #4、已销售数量的百分比
        if($goods['buy_num'] > 0 && $goods['totalnum'] > 0){
            $goods['buy_percentage'] = sprintf("%.2f", ($goods['buy_num'] / $goods['totalnum']) * 100);
            if($goods['buy_percentage']>100){
                $goods['buy_percentage'] = 100.00;
            }
        }else{
            $goods['buy_percentage'] = 0.00;
        }
        $goods['stk'] = $goods['totalnum'] - $stopBuyNum;
        if($goods['stk']<0){$goods['stk'] = 0;}
        #5、判断用户会员信息 获取商品基础价格
        $goods['pay_state'] = 0;//购买状态 0=可以购买
        if($mid){
            $usercard = WeliamWeChat::VipVerification($mid);
            if($usercard['id'] > 0){
                $goods['is_vip'] = $usercard['id'];//获取当前用户的会员卡id  等于0则不是会员
                $usercardlevel = $usercard['levelid'];
                if($usercardlevel > 0){
                    $levelinfo = pdo_get(PDO_NAME.'halflevel',array('id'=>$usercardlevel),['name','army']);
                    $goods['levelname'] = $levelinfo['name'];
                    $goods['levelarmy'] = $levelinfo['army'];
                }else{
                    $goods['levelname'] = $_W['wlsetting']['halflevel']['name'];
                    $goods['levelarmy'] = 0;
                }
            }else{
                $goods['is_vip'] = 0;
                $usercardlevel = -1;
                $goods['levelname'] = '会员最多';
                $goods['levelarmy'] = 0;
            }
            if($goods['vipstatus'] > 0){
                if($goods['vipstatus'] == 2 && ($goods['is_vip'] <= 0 || empty($goods['is_vip']))){
                    $goods['pay_state'] = 1;//购买状态 1=会员特供，用户不是会员，不可购买
                }
            }
        }else{
            $usercardlevel = -1;
            $goods['levelname'] = '会员最多';
            $goods['levelarmy'] = 0;
        }
        #7、商品为抢购商品时 并且状态为未开始时 判断用户是否关注当前抢购商品  0=未关注，1=已关注
        $goods['is_follow'] = 0;
        if($plugin == 1 && $goods['status'] == 1){
            $isFollow = pdo_getcolumn(PDO_NAME.'rush_follows',['mid'=>$mid,'actid'=>$id],'id');
            if($isFollow) $goods['is_follow'] = 1;
        }
        #9、获取商品的优惠金额  Discount
        if($goods['vipstatus'] == 1){
            if($plugin == 1 || $plugin == 2 || $plugin == 3 || $plugin == 5 || $plugin == 7 || $plugin == 9){
                $goods['discount_price'] = self::getVipDiscount($goods['viparray'],$usercardlevel);
            }else{
                $goods['discount_price'] = sprintf("%.2f",$goods['price'] - $goods['vipprice']);
            }
        }else{
            $goods['discount_price'] = 0;
        }
        #8、判断是否为多规格商品
        if($goods['optionstatus'] > 0){
            if($plugin == 9){
                $specinfo = WeliamWeChat::getActivitySpec($id);
                $goods['spec'] = $specinfo['info'];
                if($goods['vipstatus'] == 1){
                    foreach($goods['spec'] as &$spb){
                        $spb['discount_price'] = self::getVipDiscount($spb['viparray'],$usercardlevel);
                        if($spb['discount_price'] < 0.01){
                            $spb['discount_price'] = $goods['discount_price'];
                        }
                    }
                }
                $goods['minprice'] = $specinfo['minprice'];
                $goods['maxprice'] = $specinfo['maxprice'];
                $goods['stk'] = $specinfo['stk'];
            }else{
                $goods['spec'] = WeliamWeChat::getSpec($id, $plugin,$goods['vipstatus']);
                //从新获取库存信息
                foreach ($goods['spec']['info'] as &$sp){
                    if ($plugin == 1) {
                        //抢购商品的销量
                        $stopBuyNum = WeliamWeChat::getSalesNum(1,$goods['id'],$sp['id'],1,0,0,0,$goods['pftid']);
                        $sp['stock'] = $sp['stock'] - intval($stopBuyNum);
                    }else if($plugin == 2 || $plugin == 3){
                        $stopBuyNum = WeliamWeChat::getSalesNum($plugin,$goods['id'],$sp['id'],1,0,0,0,$goods['pftid']);
                        $sp['stock'] = $sp['stock'] - intval($stopBuyNum);
                    }
                    $sp['thumb'] = tomedia($sp['thumb']);
                    if($goods['vipstatus'] == 1){
                        $sp['discount_price'] = self::getVipDiscount($sp['viparray'],$usercardlevel);
                        if($sp['discount_price'] < 0.01 ){
                            $sp['discount_price'] = $goods['discount_price'];
                        }
                    }
                }
                $stkList = array_column($goods['spec']['info'],'stock');
                $goods['stk'] = array_sum($stkList);
            }
        }

        unset($goods['viparray']);
        return $goods;
    }
    /**
     * Comment: 首页获取头条信息
     * Author: zzw
     * Date: 2019/8/29 17:18
     * @param $id
     * @return bool
     */
    static function getHomeLine($id) {
        $line = pdo_fetch("SELECT id,title,summary,display_img,author,author_img,browse,one_id,two_id FROM "
            . tablename(PDO_NAME . "headline_content")
            . " WHERE id = {$id} ");
        if(!$line) return '';
        $line['display_img'] = tomedia($line['display_img']);
        $line['author_img'] = tomedia($line['author_img']);
        $line['one_name'] = pdo_getcolumn(PDO_NAME . 'headline_class', ['id' => $line['one_id']], 'name') ? : '';
        $line['two_name'] = pdo_getcolumn(PDO_NAME . 'headline_class',['id' => $line['two_id']], 'name') ? : '';
        unset($line['one_id']);
        unset($line['two_id']);
        return $line;
    }
    /**
     * Comment: 根据信息获取页面信息，菜单信息，广告信息
     * Author: zzw
     * Date: 2019/8/29 17:21
     * @param $pageInfo
     * @param $menuid
     * @param $advid
     * @return mixed
     */
    static function getPageInfo($pageInfo, $menuid, $advid) {
        global $_W, $_GPC;
        $page['title'] = $pageInfo['title'];
        $page['background'] = $pageInfo['background'];
        $page['share_title'] = $pageInfo['share_title'];
        $page['share_image'] = tomedia($pageInfo['share_image']);
        if ($menuid > 0) {
            $menudata = Diy::getMenu($menuid)['data'];
        }
        if ($advid > 0) {
            $advdata = Diy::BeOverdue($advid, false)['data'];
        }
        //信息拼装
        $data['page'] = $page;//本页面配置信息
        $data['menu'] = $menudata;//菜单配置信息
        $data['adv'] = $advdata;//广告配置信息
        return $data;
    }

    /**
     * Comment: 核销码发送
     * Author: wlf
     * Date: 2021/08/13 10:04
     * @param $code
     * @param $mobile
     * @return array
     */
    static function smsHXM($code,$goodname,$num = 1,$time,$mobile,$mid,$nickname='',$storename = '') {
        global $_W;
        $smsset = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'smsset','uniacid'=>$_W['uniacid']), 'value'));
        $baseset = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'base','uniacid'=>$_W['uniacid']), 'value'));
        if($smsset['dy_hxm'] > 0){
            $smses = pdo_get(PDO_NAME . "smstpl", array('uniacid' => $_W['uniacid'], 'id' => $smsset['dy_hxm']));
            $param = unserialize($smses['data']);
            $nickname = !empty($nickname) ? $nickname : pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'nickname');

            $datas = array(
                array('name' => '系统名称', 'value' => $baseset['name']),
                array('name' => '版权信息', 'value' => $baseset['copyright']),
                array('name' => '核销码', 'value' => $code),
                array('name' => '昵称', 'value' => $nickname),
                array('name' => '商品名', 'value' => $goodname),
                array('name' => '数量', 'value' => $num),
                array('name' => '过期时间', 'value' => $time),
                array('name' => '店铺名', 'value' => $storename)
            );
            foreach ($param as $d) {
                $params[$d['data_temp']] = self::replaceTemplate($d['data_shop'], $datas);
            }
            return self::sendSms($smses, $params, $mobile,$mid);
        }
    }

    /**
     * Comment: 验证码发送组一
     * Author: zzw
     * Date: 2019/8/29 17:22
     * @param $code
     * @param $mobile
     * @return array
     */
    static function smsSF($code, $mobile,$mid) {
        global $_W;
        $smsset = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'smsset','uniacid'=>$_W['uniacid']), 'value'));
        $baseset = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'base','uniacid'=>$_W['uniacid']), 'value'));
        $smses = pdo_get(PDO_NAME . "smstpl", array('uniacid' => $_W['uniacid'], 'id' => $smsset['dy_sf']));
        $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'nickname');
        $param = unserialize($smses['data']);
        $datas = array(
            array('name' => '系统名称', 'value' => $baseset['name']),
            array('name' => '版权信息', 'value' => $baseset['copyright']),
            array('name' => '验证码', 'value' => $code),
            array('name' => '昵称', 'value' => $nickname)
        );
        foreach ($param as $d) {
            $params[$d['data_temp']] = self::replaceTemplate($d['data_shop'], $datas);
        }
        return self::sendSms($smses, $params, $mobile,$mid);
    }
    /**
     * Comment: 验证码发送组二
     * Author: zzw
     * Date: 2019/8/29 17:22
     * @param       $str
     * @param array $datas
     * @return mixed
     */
    static function replaceTemplate($str, $datas = array()) {
        foreach ($datas as $d) {
            $str = str_replace('【' . $d['name'] . '】', $d['value'], $str);
        }
        return $str;
    }
    /**
     * Comment: 验证码发送组三
     * Author: zzw
     * Date: 2019/8/29 17:22
     * @param        $smstpl
     * @param        $param
     * @param        $mobile
     * @param string $mid
     * @return array
     */
    static function sendSms($smstpl, $param, $mobile, $mid = '') {
        global $_W;
        $smsset = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'sms','uniacid'=>$_W['uniacid']), 'value'));
        if ($smstpl['type'] == 'aliyun') {
            include PATH_CORE . 'library/aliyun/Config.php';
            $profile = DefaultProfile::getProfile("cn-hangzhou", $smsset['note_appkey'], $smsset['note_secretKey']);
            DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");
            $acsClient = new DefaultAcsClient($profile);
            m('aliyun/sendsmsrequest')->setSignName($smsset['note_sign']);
            m('aliyun/sendsmsrequest')->setTemplateParam(json_encode($param));
            m('aliyun/sendsmsrequest')->setTemplateCode($smstpl['smstplid']);
            m('aliyun/sendsmsrequest')->setPhoneNumbers($mobile);
            $resp = $acsClient->getAcsResponse(m('aliyun/sendsmsrequest'));
            $res = Util::object_array($resp);
            if ($res['Code'] == 'OK') {
                self::create_apirecord(-1, '', $mid, $mobile, 1, '阿里云身份验证');
                $recode = array("result" => 1);
            } else {
                $recode = array("result" => 2, "msg" => $res['Message']);
            }
        } else {
            m('alidayu/topclient')->appkey = $smsset['note_appkey'];
            m('alidayu/topclient')->secretKey = $smsset['note_secretKey'];
            m('alidayu/smsnum')->setSmsType("normal");
            m('alidayu/smsnum')->setSmsFreeSignName($smsset['note_sign']);
            m('alidayu/smsnum')->setSmsParam(json_encode($param));
            m('alidayu/smsnum')->setRecNum($mobile);
            m('alidayu/smsnum')->setSmsTemplateCode($smstpl['smstplid']);
            $resp = m('alidayu/topclient')->execute(m('alidayu/smsnum'), '6100e23657fb0b2d0c78568e55a3031134be9a3a5d4b3a365753805');
            $res = Util::object_array($resp);
            if ($res['result']['success'] == 1) {
                self::create_apirecord(-1, '', $mid, $mobile, 1, '阿里大于身份验证');
                $recode = array("result" => 1);
            } else {
                $recode = array("result" => 2, "msg" => $res['sub_msg']);
            }
        }


        return $recode;
    }
    /**
     * Comment: 验证码发送组四
     * Author: zzw
     * Date: 2019/8/29 17:22
     * @param        $sendmid
     * @param string $sendmobile
     * @param        $takemid
     * @param        $takemobile
     * @param        $type
     * @param        $remark
     */
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
    /**
     * Comment: 获取推荐商品信息列表
     * Author: zzw
     * Date: 2019/8/13 17:11
     * @param string $num  获取的数量
     * @param int $type 当前商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品
     * @param int $id   当前商品id，存在时不会获取该商品
     * @return mixed
     */
    public static function getRecommendGoods($num,$type = 0,$id = 0){
        global $_W,$_GPC;
        //获取设置选项
        $set = Setting::wlsetting_read('base');
        $recommendType = $set['recommend_type'] ? : 0;//0=同商家同类型,1=同商家,2=同类型,3=随机
        //获取商品列表
        switch ($recommendType){
            case 0: $list = self::sameStoreType($num,$type,$id);break;//同商家同类型
            case 1: $list = self::sameStore($num,$type,$id);break;//同商家
            case 2: $list = self::sameType($num,$type,$id);break;//同类型
            case 3: $list = self::sameRandom($num,$type,$id);break;//随机
        }
        //处理list
        if(is_array($list) && count($list) > 0){
            if(count($list) > $num){
                //总数量大于需要数量  随机获取
                for($i=0;$i<$num;$i++){
                    if(count($list) > 0){
                        $key = array_rand($list);
                        $newList[$i] = self::getHomeGoods($list[$key]['type'], $list[$key]['id']);
                        //链接生成
                        $newList[$i]['url'] = h5_url('pages/subPages/goods/index',['id'=>$newList[$i]['id'],'type'=>$newList[$i]['type']]);
                        unset($list[$key]);
                        unset($newList[$i]['user_list']);
                        unset($newList[$i]['user_num']);
                        unset($newList[$i]['stk']);
                        unset($newList[$i]['buy_percentage']);
                        unset($newList[$i]['is_vip']);
                        unset($newList[$i]['address']);
                        unset($newList[$i]['status']);
                        unset($newList[$i]['totalnum']);
                        unset($newList[$i]['storename']);
                        unset($newList[$i]['sid']);
                        unset($newList[$i]['starttime']);
                        unset($newList[$i]['endtime']);
                        unset($newList[$i]['vipprice']);
                        unset($newList[$i]['vipstatus']);
                        unset($newList[$i]['buy_limit']);
                        unset($newList[$i]['peoplenum']);
                        unset($newList[$i]['realsalenum']);
                        unset($newList[$i]['allsalenum']);
                        unset($newList[$i]['buy_num']);
                        unset($newList[$i]['user_limit_num']);
                    }else{
                        continue;
                    }
                }
            }else{
                //总数量小于或者等于需要数量  直接返回
                foreach($list as $key => &$val){
                    $val = self::getHomeGoods($val['type'], $val['id']);
                    //链接生成
                    $val['url'] = h5_url('pages/subPages/goods/index',['id'=>$val['id'],'type'=>$val['type']]);
                    unset($val['user_list']);
                    unset($val['user_num']);
                    unset($val['stk']);
                    unset($val['buy_percentage']);
                    unset($val['is_vip']);
                    unset($val['address']);
                    unset($val['status']);
                    unset($val['totalnum']);
                    unset($val['storename']);
                    unset($val['sid']);
                    unset($val['starttime']);
                    unset($val['endtime']);
                    unset($val['vipprice']);
                    unset($val['vipstatus']);
                    unset($val['buy_limit']);
                    unset($val['peoplenum']);
                    unset($val['realsalenum']);
                    unset($val['allsalenum']);
                    unset($val['buy_num']);
                    unset($val['user_limit_num']);
                }
                $newList = $list;
            }
        }else{
            $newList = [];
        }

        return $newList;
    }
    /**
     * Comment: 推荐商品 —— 同商家同类型
     * Author: zzw
     * Date: 2021/1/4 10:32
     * @param $num
     * @param $type
     * @param $id
     * @return array|false|mixed
     */
    private static function sameStoreType($num,$type,$id){
        global $_W,$_GPC;
        //基本条件生成
        $where = " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND id != {$id} ";
        //获取商户id、表信息
        switch ($type) {
            case 1:
                $sid   = pdo_getcolumn(PDO_NAME."rush_activity",['id' => $id],'sid');
                $table = tablename(PDO_NAME."rush_activity");
                $where .= " AND sid = {$sid} AND status = 2";
                $field = " id,'1' as type ";
                break;//抢购商品表
            case 2:
                $sid   = pdo_getcolumn(PDO_NAME."groupon_activity",['id' => $id],'sid');
                $table = tablename(PDO_NAME."groupon_activity");
                $where .= " AND sid = {$sid} AND status = 2";
                $field = " id,'2' as type ";
                break;//团购商品表
            case 3:
                $sid   = pdo_getcolumn(PDO_NAME."fightgroup_goods",['id' => $id],'merchantid');
                $table = tablename(PDO_NAME."fightgroup_goods");
                $where .= " AND merchantid = {$sid} AND status = 2";
                $field = " id,'3' as type ";
                break;//拼团商品表
            case 5:
                $sid   = pdo_getcolumn(PDO_NAME."couponlist",['id' => $id],'merchantid');
                $table = tablename(PDO_NAME."couponlist");
                $where .= " AND merchantid = {$sid} AND status = 2";
                $field = " id,'5' as type ";
                break;//拼团商品表
            case 7:
                $sid   = pdo_getcolumn(PDO_NAME."bargain_activity",['id' => $id],'sid');
                $table = tablename(PDO_NAME."bargain_activity");
                $where .= " AND sid = {$sid} AND status = 2";
                $field = " id,'7' as type ";
                break;//砍价商品表
        }
        $list = pdo_fetchall("SELECT {$field} FROM ".$table.$where." LIMIT 100 ");

        return $list ? : [];
    }
    /**
     * Comment: 推荐商品 —— 同商家
     * Author: zzw
     * Date: 2021/1/4 11:17
     * @param $num
     * @param $type
     * @param $id
     * @return array
     */
    private static function sameStore($num,$type,$id){
        global $_W,$_GPC;
        //获取商户id
        switch ($type) {
            case 1:
                $sid = pdo_getcolumn(PDO_NAME."rush_activity",['id' => $id],'sid');
                break;//抢购商品表
            case 2:
                $sid = pdo_getcolumn(PDO_NAME."groupon_activity",['id' => $id],'sid');
                break;//团购商品表
            case 3:
                $sid = pdo_getcolumn(PDO_NAME."fightgroup_goods",['id' => $id],'merchantid');
                break;//拼团商品表
            case 5:
                $sid   = pdo_getcolumn(PDO_NAME."couponlist",['id' => $id],'merchantid');
                break;//拼团商品表
            case 7:
                $sid = pdo_getcolumn(PDO_NAME."bargain_activity",['id' => $id],'sid');
                break;//砍价商品表
        }
        //商品信息获取
        $rush    = tablename(PDO_NAME."rush_activity");//抢购商品表        1
        $group   = tablename(PDO_NAME."groupon_activity");//团购商品表     2
        $fight   = tablename(PDO_NAME."fightgroup_goods");//拼团商品表     3
        $coupon  = tablename(PDO_NAME."couponlist");//卡卷商品表           5
        $bargain = tablename(PDO_NAME."bargain_activity");//砍价商品表     7
        $where = " WHERE aid ={$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        $noId  = " AND id != {$id} ";
        //抢购商品信息
        if($type == 1) $rushList = pdo_fetchall("SELECT id,'1' as type FROM ".$rush.$where.$noId." AND status = 2 AND sid = {$sid} LIMIT 100");
        else $rushList = pdo_fetchall("SELECT id,'1' as type FROM ".$rush.$where." AND status = 2 AND sid = {$sid} LIMIT 100");
        //团购商品信息
        if($type == 2) $groupList = pdo_fetchall("SELECT id,'2' as type FROM ".$group.$where.$noId." AND status = 2  AND sid = {$sid} LIMIT 100");
        else $groupList = pdo_fetchall("SELECT id,'2' as type FROM ".$group.$where." AND status = 2 AND sid = {$sid} LIMIT 100");
        //拼团商品信息
        if($type == 3) $fightList = pdo_fetchall("SELECT id,'3' as type FROM ".$fight.$where.$noId." AND status = 2  AND merchantid = {$sid} LIMIT 100");
        else $fightList = pdo_fetchall("SELECT id,'3' as type FROM ".$fight.$where." AND status = 2 AND merchantid = {$sid} LIMIT 100");
        //卡卷商品信息
        if($type == 5) $couponList = pdo_fetchall("SELECT id,'5' as type FROM ".$coupon.$where.$noId." AND status = 2  AND merchantid = {$sid} LIMIT 100");
        else $couponList = pdo_fetchall("SELECT id,'5' as type FROM ".$coupon.$where." AND status = 2 AND merchantid = {$sid} LIMIT 100");
        //砍价商品信息
        if($type == 7) $bargainList = pdo_fetchall("SELECT id,'7' as type FROM ".$bargain.$where.$noId." AND status = 2  AND sid = {$sid} LIMIT 100");
        else $bargainList = pdo_fetchall("SELECT id,'7' as type FROM ".$bargain.$where." AND status = 2 AND sid = {$sid} LIMIT 100");
        #3、随机获取商品信息
        $list = array_merge($rushList,$groupList,$fightList,$couponList,$bargainList);

        return $list ? : [];
    }
    /**
     * Comment: 推荐商品 —— 同类型
     * Author: zzw
     * Date: 2021/1/4 11:21
     * @param $num
     * @param $type
     * @param $id
     * @return array|false|mixed
     */
    private static function sameType($num,$type,$id){
        global $_W,$_GPC;
        //基本条件生成
        $where = " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND id != {$id} ";
        //获取商户id、表信息
        switch ($type) {
            case 1:
                $table = tablename(PDO_NAME."rush_activity");
                $where .= " AND status = 2";
                $field = " id,'1' as type ";
                break;//抢购商品表
            case 2:
                $table = tablename(PDO_NAME."groupon_activity");
                $where .= " AND status = 2";
                $field = " id,'2' as type ";
                break;//团购商品表
            case 3:
                $table = tablename(PDO_NAME."fightgroup_goods");
                $where .= " AND status = 2";
                $field = " id,'3' as type ";
                break;//拼团商品表
            case 5:
                $table = tablename(PDO_NAME."couponlist");
                $where .= " AND status = 2";
                $field = " id,'5' as type ";
                break;//拼团商品表
            case 7:
                $table = tablename(PDO_NAME."bargain_activity");
                $where .= " AND status = 2";
                $field = " id,'7' as type ";
                break;//砍价商品表
        }
        $list = pdo_fetchall("SELECT {$field} FROM ".$table.$where." LIMIT 100 ");

        return $list ? : [];
    }
    /**
     * Comment: 推荐商品 —— 随机
     * Author: zzw
     * Date: 2021/1/4 11:26
     * @param $num
     * @param $type
     * @param $id
     * @return array
     */
    private static function sameRandom($num,$type,$id){
        global $_W,$_GPC;
        //商品信息获取
        $rush    = tablename(PDO_NAME."rush_activity");//抢购商品表        1
        $group   = tablename(PDO_NAME."groupon_activity");//团购商品表     2
        $fight   = tablename(PDO_NAME."fightgroup_goods");//拼团商品表     3
        $coupon  = tablename(PDO_NAME."couponlist");//卡卷商品表           5
        $bargain = tablename(PDO_NAME."bargain_activity");//砍价商品表     7
        $where = " WHERE aid ={$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        $noId  = " AND id != {$id} ";
        //抢购商品信息
        if($type == 1) $rushList = pdo_fetchall("SELECT id,'1' as type FROM ".$rush.$where.$noId." AND status = 2 LIMIT 100");
        else $rushList = pdo_fetchall("SELECT id,'1' as type FROM ".$rush.$where." AND status = 2 LIMIT 100");
        //团购商品信息
        if($type == 2) $groupList = pdo_fetchall("SELECT id,'2' as type FROM ".$group.$where.$noId." AND status = 2 LIMIT 100");
        else $groupList = pdo_fetchall("SELECT id,'2' as type FROM ".$group.$where." AND status = 2 LIMIT 100");
        //拼团商品信息
        if($type == 3) $fightList = pdo_fetchall("SELECT id,'3' as type FROM ".$fight.$where.$noId." AND status = 2 LIMIT 100");
        else $fightList = pdo_fetchall("SELECT id,'3' as type FROM ".$fight.$where." AND status = 2 LIMIT 100");
        //卡卷商品信息
        if($type == 5) $couponList = pdo_fetchall("SELECT id,'5' as type FROM ".$coupon.$where.$noId." AND status = 2 LIMIT 100");
        else $couponList = pdo_fetchall("SELECT id,'5' as type FROM ".$coupon.$where." AND status = 2 LIMIT 100");
        //砍价商品信息
        if($type == 7) $bargainList = pdo_fetchall("SELECT id,'7' as type FROM ".$bargain.$where.$noId." AND status = 2 LIMIT 100");
        else $bargainList = pdo_fetchall("SELECT id,'7' as type FROM ".$bargain.$where." AND status = 2 LIMIT 100");
        #3、随机获取商品信息
        $list = array_merge($rushList,$groupList,$fightList,$couponList,$bargainList);

        return $list ? : [];
    }
    /**
     * Comment: 开启事务处理
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public static function startTrans(){
        return pdo_query(" BEGIN ");
    }
    /**
     * Comment: 提交事务处理
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public static function commit(){
        return pdo_query(" COMMIT ");
    }
    /**
     * Comment: 事务回滚
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public static function rollback(){
        return pdo_query(" ROLLBACK ");
    }
    /**
     * Comment: 获取二维码图片base64格式
     * Author: zzw
     * Date: 2019/8/20 18:09
     * @param $url
     * @return string
     */
    public static function getQrCode($url){
        global $_W,$_GPC;
        #1、长链接转短连接
        $result = Util::long2short($url);
        if (!is_error($result)) $url = $result['short_url'];
        #2、生成二维码
        require_once '../library/qrcode/QRcode.lib.php';
        ob_start();
        QRcode::png($url, false, QR_ECLEVEL_L, 16, 1,false,true);
        $image_data = base64_encode(ob_get_contents());
        ob_end_clean();
        $image_data = "data:image/png;base64," . $image_data;

        return $image_data;
    }
    /**
     * Comment: 获取对应渠道的access_token（默认：公众号）
     * Author: zzw
     * Date: 2019/10/24 10:55
     * @param bool $new     是否获取最新的token，默认false
     * @param int  $source  渠道：1=公众号（默认）；2=h5；3=小程序
     * @return mixed
     */
    public static function getAccessToken($new = false,$source = 1){
        global $_W;
        $name = 'accesstoken_source'.$source;
        session_start();
        #1、判断是否存在accessToken  存在则直接获取，不存在则从新获取
        $tokenCacheInfo = json_decode($_SESSION[$name],true);
        if($tokenCacheInfo && time() < $tokenCacheInfo['end_time'] && !$new){
            $accessToken = $tokenCacheInfo['access_token'];
        }else{
            //根据渠道获取配置信息  并且获取对应的信息
            switch ($source){
                case 1:case 2:
                //获取配置信息 初始化EasyWeChat
                $params = Util::object_array($_W['account']);
                $config = [
                    'app_id' => trim($params['key']),
                    'secret' => trim($params['secret']),
                    'token' => trim($params['token']),
                    'response_type' => 'array',
                ];
                $app = Factory::officialAccount($config);
                $tokenObj = $app->access_token;
                $token = $tokenObj->getToken($new); // token 数组  token['access_token'] 字符串
                break;
                case 3:
                    //获取配置信息 初始化EasyWeChat
                    $params = Setting::wlsetting_read('wxapp_config');
                    $config = [
                        'app_id' => trim($params['appid']),
                        'secret' => trim($params['secret']),
                        'response_type' => 'array',
                    ];
                    $app = Factory::miniProgram($config);
                    $tokenObj = $app->access_token;
                    $token = $tokenObj->getToken($new); // token 数组  token['access_token'] 字符串
                    break;
            }
            //获取token信息  判断有效时间
            $accessToken = $token['access_token'];
            $endTime = (time() + $token['expires_in']) - 100;
            //记录进入session中
            $_SESSION[$name] = json_encode(['access_token'=>$accessToken,'end_time'=>$endTime]);
        }
        #2、返回accessToken
        return $accessToken;
    }
    /**
     * Comment: 判断当前商品是否显示分销助手/获取当前商品的分销佣金
     * Author: zzw
     * Date: 2019/11/14 11:35
     * @param $type int 商品类型：1=抢购  2=团购  3=拼团 5=优惠券 7=砍价商品 8=积分商品
     * @param $id   int 商品id
     * @return mixed|array
     */
    public static function getDisInfo($type,$id){
        global $_W;
        #1、获取基本设置信息
        $data['is_show'] = $data['tipflag'] = 0;//是否显示分销助手 0=不显示；1=显示
        $disSet = $_W['wlsetting']['distribution'];
        #1、判断是否开启分销商   判断当前商品是否参与分销
        $disflag = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$_W['wlmember']['distributorid']),'disflag');
        if($disSet['switch'] > 0) {
            //获取分销商佣金比例
            if($_W['wlmember']['distributorid'] > 0){
            	$distriborinfo = pdo_get(PDO_NAME."distributor",['id'=>$_W['wlmember']['distributorid']],array('dislevel','shareholder','grouplevel'));
                $lv_id = $distriborinfo['dislevel'];
				$group_lv_id = $distriborinfo['grouplevel'];
                $rate = pdo_getcolumn(PDO_NAME."dislevel",['id'=>$lv_id,'uniacid'=>$_W['uniacid']],'onecommission');
            }
            //用户不是分销商  获取默认佣金比例
            if(!$lv_id){
                $defaleve = pdo_get(PDO_NAME."dislevel",['isdefault'=>1,'uniacid'=>$_W['uniacid']],['onecommission','id']);
                $lv_id = $defaleve['id'];
                $rate = $defaleve['onecommission'];
            }
//			if(!$group_lv_id){
//              $groupdefaleve = pdo_get(PDO_NAME."grouplevel",['isdefault'=>1,'uniacid'=>$_W['uniacid']],['onecommission','id']);
//              $group_lv_id = $groupdefaleve['id'];
//          }
			
            $rate = $rate ? $rate / 100 : 0;
            //获取对应商品类型的佣金   商品类型：1=抢购  2=团购  3=拼团 5=优惠券 7=砍价商品 8=积分商品
            switch ($type) {
                case 1:
                    $goods = pdo_get('wlmerchant_rush_activity',array('id' => $id),array('disarray','isdistri','price','optionstatus','isdistristatus','disgroup','disgroupstatus','grouparray','shareholdermoney'));
                    if($goods['isdistri'] != 1){
                        if($goods['optionstatus'] > 0){
                            $options = pdo_getall('wlmerchant_goods_option',array('type' => 1,'goodsid' => $id),array('price','disarray','grouparray','shareholdermoney'));
                            foreach($options as &$opp){
                                $disarray = self::mergeDisArray($opp['disarray'],$goods['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$opp['price'],$rate);
								//团长分红和股东
								if($goods['disgroup'] > 0){
									$grouparray = self::mergeGroupArray($opp['grouparray'], $goods['grouparray']);
									$opp['groupmoney'] = Distribution::getGroupMoney($grouparray,$group_lv_id,$opp['price'],1,$goods['disgroupstatus'],1);
								}
                            }
                            $prices = array_column($options,'dismoney');
                            $goods['commission'] = max($prices);
							
							$groupprice = array_column($options,'groupmoney');
							$goods['group_commission'] = max($groupprice);
							$shareprice = array_column($options,'shareholdermoney');
							$goods['share_commission'] = max($shareprice);
							
                        }else{
                            $disarray = unserialize($goods['disarray']);
                            $goods['commission'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$goods['price'],$rate);
							if($goods['disgroup'] > 0){
								$goods['group_commission'] = Distribution::getGroupMoney($goods['grouparray'],$group_lv_id,$goods['price'],1,$goods['disgroupstatus'],1);
								$goods['share_commission'] = $goods['shareholdermoney'];
							}
                        }
                    }
                    break;//抢购
                case 2:
                    $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $id),array('disarray','isdistri','price','optionstatus','isdistristatus'));
                    if($goods['isdistri'] != 1){
                        if($goods['optionstatus'] > 0){
                            $options = pdo_getall('wlmerchant_goods_option',array('type' => 3,'goodsid' => $id),array('price','disarray'));
                            foreach($options as &$opp){
                                $disarray = self::mergeDisArray($opp['disarray'],$goods['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$opp['price'],$rate);
                            }
                            $prices = array_column($options,'dismoney');
                            $goods['commission'] = max($prices);
                        }else{
                            $disarray = unserialize($goods['disarray']);
                            $goods['commission'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$goods['price'],$rate);
                        }
                    }
                    break;//团购
                case 3:
                    $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $id),array('disarray','isdistri','price','specstatus','isdistristatus','disgroup','disgroupstatus','grouparray','shareholdermoney'));
                    if($goods['isdistri'] != 1){
                        if($goods['specstatus'] > 0){
                            $options = pdo_getall('wlmerchant_goods_option',array('type' => 2,'goodsid' => $id),array('price','disarray','grouparray','shareholdermoney'));
                            foreach($options as &$opp){
                                $disarray = self::mergeDisArray($opp['disarray'],$goods['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$opp['price'],$rate);
								//团长分红和股东
								if($goods['disgroup'] > 0){
									$grouparray = self::mergeGroupArray($opp['grouparray'], $goods['grouparray']);
									$opp['groupmoney'] = Distribution::getGroupMoney($grouparray,$group_lv_id,$opp['price'],1,$goods['disgroupstatus'],1);
								}
                            }
                            $prices = array_column($options,'dismoney');
                            $goods['commission'] = max($prices);
							
							$groupprice = array_column($options,'groupmoney');
							$goods['group_commission'] = max($groupprice);
							$shareprice = array_column($options,'shareholdermoney');
							$goods['share_commission'] = max($shareprice);
                        }else{
                            $disarray = unserialize($goods['disarray']);
                            $goods['commission'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$goods['price'],$rate);
							
							if($goods['disgroup'] > 0){
								$goods['group_commission'] = Distribution::getGroupMoney($goods['grouparray'],$group_lv_id,$goods['price'],1,$goods['disgroupstatus'],1);
								$goods['share_commission'] = $goods['shareholdermoney'];
							}
                        }
                    }
                    break;//拼团
                case 5:
                    $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $id),array('disarray','isdistri','price','isdistristatus','disgroup','disgroupstatus','grouparray','shareholdermoney'));
                    if($goods['isdistri'] != 1){
                        $disarray = unserialize($goods['disarray']);
                        $goods['commission'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$goods['price'],$rate);
						
						if($goods['disgroup'] > 0){
							$goods['group_commission'] = Distribution::getGroupMoney($goods['grouparray'],$group_lv_id,$goods['price'],1,$goods['disgroupstatus'],1);
							$goods['share_commission'] = $goods['shareholdermoney'];
						}
                    }
                    break;//优惠券
                case 7:
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $id),array('disarray','isdistri','price','isdistristatus'));
                    if($goods['isdistri'] != 1){
                        $disarray = unserialize($goods['disarray']);
                        $goods['commission'] = self::getDismoney($disarray,$lv_id,$goods['isdistristatus'],$goods['price'],$rate);
                    }
                    break;//砍价商品
                case 8:
                    $sql = "SELECT CASE WHEN onedismoney > 0 THEN onedismoney
                                        ELSE (`use_credit2`*{$rate})
                                   END as commission,isdistri FROM ".tablename(PDO_NAME.'consumption_goods')." WHERE id = {$id} ";
                    $goods = pdo_fetch($sql);
                    break;//积分商品
            }
            $data['max_commission'] = sprintf("%.2f" , $goods['commission']);
			$data['group_commission'] = $goods['group_commission'] && $group_lv_id > 0 ? sprintf("%.2f" , $goods['group_commission']) : 0;
			$data['shaer_commission'] = $goods['share_commission'] && $distriborinfo['shareholder'] > 0 ? sprintf("%.2f" , $goods['share_commission']) : 0;
			if($data['shaer_commission'] > 0){
				$sharenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE uniacid = {$_W['uniacid']} AND	shareholder = 1");
				if($sharenum > 1){
					$data['shaer_commission'] = sprintf("%.2f" , $goods['share_commission']/$sharenum);
				}
			}
            //商品参与分销
            if($type == 8){
                $goods['isdistri'] = $goods['isdistri'] ? 0:1;
            }
            if($goods['isdistri'] != 1 && $data['max_commission'] > 0 && empty($disSet['disgoods'])){
                $data['is_show'] = 1;
            }
            //判断用户
            if($disflag == 0){
                if($disSet['dishelp'] > 0){
                    $data['tipflag'] = 1;
                }
                $data['is_show'] = 0;
            }
            //定制
            if(Customized::init('pocket140') > 0){
                $data['korea'] = 1;
            }else{
                $data['korea'] = 0;
            }
        }

        return $data;
    }
    /**
     * Comment: 获取应该的分销佣金
     * Author: wlf
     * Date: 2020/11/25 18:48
     */
    public static function getDismoney($disarray,$lv_id,$isdistristatus,$price,$rate){
        global $_W;
        if($disarray[$lv_id]['onedismoney'] > 0){
            if($isdistristatus > 0){
                $dismoney = $disarray[$lv_id]['onedismoney'];
            }else{
                $dismoney = sprintf("%.2f" , $price * $disarray[$lv_id]['onedismoney'] /100);
            }
        }else{
            $dismoney = sprintf("%.2f" , $price * $rate);
        }
        return $dismoney;
    }
    /**
     * Comment: 记录用户浏览信息
     * Author: zzw
     * Date: 2019/11/14 14:34
     * @param $type int 商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
     * @param $id   int 商品id
     */
    public static function browseRecord($type, $id){
        global $_W;
        #商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
        if($_W['mid']){
            $data = [
                'type'     => $type ,
                'mid'      => $_W['mid'] ,
                'goods_id' => $id ,
                'uniacid'  => $_W['uniacid']
            ];
            #1、判断用户是否已经浏览当前商品
            $isBrowse = pdo_get(PDO_NAME."browse_record",$data);
            if($isBrowse){
                //已经浏览  修改最近浏览时间
                pdo_update(PDO_NAME."browse_record",['updade_time'=>time()],$data);
            }else{
                $data['create_time'] = time();
                $data['updade_time'] = time();
                pdo_insert(PDO_NAME."browse_record",$data);
            }
        }
    }
    /**
     * Comment: 获取某个商品的浏览记录
     * Author: zzw
     * Date: 2019/11/14 14:50
     * @param int $type     商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
     * @param int $id       商品id
     * @param int $limit    查询的最大数量
     * @return array
     */
    public static function getBrowseRecord($type, $id,$limit = 5){
        global $_W;
        #1、条件生成
        //$where = " a.type = {$type} AND a.goods_id = {$id} AND a.uniacid = {$_W['uniacid']} ";
        // if($_W['mid']) $where .= " AND mid != {$_W['mid']} ";
        #1、获取当前商品的浏览记录
//        $sql = "SELECT a.id,b.avatar,b.nickname FROM ".tablename(PDO_NAME."browse_record")
//            . " as a RIGHT JOIN ".tablename(PDO_NAME."member")
//            ." as b ON a.mid = b.id WHERE {$where} ORDER BY a.updade_time DESC LIMIT {$limit}";
//        $list = pdo_fetchall($sql);
        if(empty($_W['mid'])) $_W['mid'] = 0;
        $list = pdo_fetchall("SELECT distinct mid FROM ".tablename(PDO_NAME."browse_record")."WHERE uniacid = {$_W['uniacid']} AND mid != {$_W['mid']} AND type = {$type} AND goods_id = {$id} ORDER BY updade_time DESC LIMIT {$limit}");
        if($list){
            foreach ($list as &$li){
                $member = pdo_get(PDO_NAME."member",array('id' => $li['mid']),array('avatar','nickname'));
                $li['nickname'] = $member['nickname'];
                $li['avatar'] = tomedia($member['avatar']);
            }
        }
        return is_array($list) ? $list : [];
    }
    /**
     * Comment: 获取某个商品或规格的销量
     * Author: wlf
     * Date: 2020/06/28 18:30
     * @param int $plugin     商品类型：1=抢购  2=团购  3=拼团 4=卡券 5=砍价 6=活动
     * @param int $id       商品id
     * @param int $specid   规格id
     * @param int $type     类型: 1=已下单 2=已支付 3=已完成
     * @param int $mid      买家ID
     * @param int $starttime   起始时间
     * @param int $endtime     结束时间
     * @param int $endtime     三方商品id
     * @return array
     */
    public static function getSalesNum($plugin,$id,$specid = 0,$type = 1,$mid = 0,$starttime = 0,$endtime = 0,$threeid = 0){
        global $_W;
        if($threeid > 0){
            $salesnum = Pftapimod::getThreeSalesNum($plugin,$id,$type,$mid,$starttime,$endtime);
        }else{
            if($plugin == 1){
                $orderwhere = " activityid = {$id}";
                $smallorderwhere = " gid = {$id} AND plugin = 'rush'";
                if($specid > 0){
                    $orderwhere .= " AND optionid = {$specid}";
                    $smallorderwhere .= " AND specid = {$specid}";
                }
                if($mid > 0){
                    $orderwhere .= " AND mid = {$mid}";
                    $smallorderwhere .= " AND mid = {$mid}";
                }
                if($starttime > 0){
                    $orderwhere .= " AND createtime > {$starttime}";
                    $smallorderwhere .= " AND createtime > {$starttime}";
                }
                if($endtime > 0){
                    $orderwhere .= " AND createtime < {$endtime}";
                    $smallorderwhere .= " AND createtime < {$endtime}";
                }
                if($type == 1){
                    //未支付的
                    $nopayorderwhere = $orderwhere." AND status = 0";
                    $nopaynum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$nopayorderwhere}"));
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3,6,4,8,9)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                    //核销的
                    $smallorderwhere .= " AND status != 3";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $salesnum = $nopaynum + $expressnum + $hexiaonum;
                }else if($type == 2){
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3,6,4,8,9)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                    //核销的
                    $smallorderwhere .= " AND status != 3";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $salesnum = $expressnum + $hexiaonum;
                }else if($type == 3){
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                    //核销的
                    $smallorderwhere .= " AND status = 2";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $salesnum = $expressnum + $hexiaonum;
                }
            }
            else{
                $orderwhere = " fkid = {$id}";
                $smallorderwhere = " gid = {$id}";
                switch($plugin){
                    case 2:
                        $orderwhere .= " AND plugin = 'groupon'";
                        $smallorderwhere .= " AND plugin = 'groupon'";
                        break;
                    case 3:
                        $orderwhere .= " AND plugin = 'wlfightgroup'";
                        $smallorderwhere .= " AND plugin = 'wlfightgroup'";
                        break;
                    case 4:
                        $orderwhere .= " AND plugin = 'coupon'";
                        $smallorderwhere .= " AND plugin = 'coupon'";
                        break;
                    case 5:
                        $orderwhere .= " AND plugin = 'bargain'";
                        $smallorderwhere .= " AND plugin = 'bargain'";
                        break;
                    case 6:
                        $orderwhere .= " AND plugin = 'activity'";
                        $smallorderwhere .= " AND plugin = 'activity'";
                        break;
                }
                if($specid > 0){
                    $orderwhere .= " AND specid = {$specid}";
                    $smallorderwhere .= " AND specid = {$specid}";
                }
                if($mid > 0){
                    $orderwhere .= " AND mid = {$mid}";
                    $smallorderwhere .= " AND mid = {$mid}";
                }
                if($starttime > 0){
                    $orderwhere .= " AND createtime > {$starttime}";
                    $smallorderwhere .= " AND createtime > {$starttime}";
                }
                if($endtime > 0){
                    $orderwhere .= " AND createtime < {$endtime}";
                    $smallorderwhere .= " AND createtime < {$endtime}";
                }
                if($type == 1){
                    //未支付的
                    $nopayorderwhere = $orderwhere." AND status = 0";
                    $nopaynum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "order") . " WHERE {$nopayorderwhere}"));
                    $nopaynum = $nopaynum ? : 0;
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3,6,4,8,9)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME."order") . " WHERE {$expresswhere}"));
                    $expressnum = $expressnum ? : 0;
                    //核销的
                    $smallorderwhere .= " AND status != 3";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $hexiaonum = $hexiaonum ? : 0;
                    $salesnum = $nopaynum + $expressnum + $hexiaonum;
                    //拼团的特殊处理(拼团中的订单)
                    if($plugin == 3){
                        $fightorderwhere = " a.fkid = {$id} AND a.fightstatus = 1 AND a.status = 1 AND b.status = 1";
                        if($mid > 0){
                            $fightorderwhere .= " AND a.mid = {$mid}";
                        }
                        if($specid > 0){
                            $fightorderwhere .= " AND a.specid = {$specid}";
                        }
                        $fightnum = implode(pdo_fetch("SELECT sum(a.num) FROM " . tablename(PDO_NAME . "order"). " as a LEFT JOIN ".tablename(PDO_NAME . "fightgroup_group") . " as b ON a.fightgroupid = b.id  WHERE {$fightorderwhere}"));
                        $salesnum = $salesnum + $fightnum;
                    }
                }else if($type == 2){
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3,6,4,8,9)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME."order") . " WHERE {$expresswhere}"));
                    //核销的
                    $smallorderwhere .= " AND status != 3";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $salesnum = $expressnum + $hexiaonum;
                    //拼团的特殊处理(拼团中的订单)
                    if($plugin == 3){
                        $fightorderwhere = " a.fkid = {$id} AND a.fightstatus = 1 AND a.status = 1 AND b.status = 1";
                        if($mid > 0){
                            $fightorderwhere .= " AND a.mid = {$mid}";
                        }
                        if($specid > 0){
                            $fightorderwhere .= " AND a.specid = {$specid}";
                        }
                        $fightnum = implode(pdo_fetch("SELECT sum(a.num) FROM " . tablename(PDO_NAME . "order"). " as a LEFT JOIN ".tablename(PDO_NAME . "fightgroup_group") . " as b ON a.fightgroupid = b.id  WHERE {$fightorderwhere}"));
                        $salesnum = $salesnum + $fightnum;
                    }
                }else if($type == 3){
                    //快递的
                    $expresswhere = $orderwhere." AND expressid > 0 AND status IN (2,3)";
                    $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME."order") . " WHERE {$expresswhere}"));
                    //核销的
                    $smallorderwhere .= " AND status = 2";
                    $hexiaonum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE {$smallorderwhere}");
                    $salesnum = $expressnum + $hexiaonum;
                }
            }
        }
        return $salesnum;
    }
    /**
     * Comment: 检查商品当天是否可以购买
     * Author: wlf
     * Date: 2020/09/21 17:48
     */
    public static function checkUseDateStatus($status = 0,$week = '',$day = ''){
        global $_W;
        $time = time();//时间筛选
        $toWeek = date("w", $time);//当前时间的星期
        if ($toWeek == 0) $toWeek = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期
        if($status == 1){
            $week = unserialize($week);
            if(in_array($toWeek,$week)){
                return 1;
            }
        }else if($status == 2){
            $day = unserialize($day);
            if(in_array($toDay,$day)){
                return 1;
            }
        }
        return 0;
    }
    /**
     * Comment: 获取当前会员等级的会员价
     * Author: wlf
     * Date: 2020/11/12 16:46
     */
    public static function getVipDiscount($viparray,$levelid = 0){
        global $_W;
        $viparray = unserialize($viparray);
        if($levelid == -1){
            $prices = array_column($viparray,'vipprice');
            $discount = max($prices);
        }else{
            $discount = $viparray[$levelid]['vipprice'];
        }
        return $discount ? : 0;
    }
    /**
     * Comment: 合并规格和商品会员减免数组
     * Author: wlf
     * Date: 2020/11/20 14:13
     */
    public static function mergeVipArray($optionviparray,$goodsviparray){
        global $_W;
        $optionviparray = unserialize($optionviparray);
        $goodsviparray = unserialize($goodsviparray);
        foreach($optionviparray as $optk => &$opvip){
            if($opvip['vipprice'] < 0.01){
                $opvip['vipprice'] = $goodsviparray[$optk]['vipprice'];
            }
            if($opvip['storeset'] < 0.01){
                $opvip['storeset'] = $goodsviparray[$optk]['storeset'];
            }
        }
        $viparray = serialize($optionviparray);
        return $viparray ? : 0;
    }
    /**
     * Comment: 合并规格和商品会员分销数组
     * Author: wlf
     * Date: 2020/11/25 11:48
     */
    public static function mergeDisArray($optiondisarray,$goodsdisarray){
        global $_W;
        $optiondisarray = unserialize($optiondisarray);
        $goodsdisarray = unserialize($goodsdisarray);
        foreach($optiondisarray as $optk => &$opvip){
            if($opvip['onedismoney'] < 0.01){
                $opvip['onedismoney'] = $goodsdisarray[$optk]['onedismoney'];
            }
            if($opvip['twodismoney'] < 0.01){
                $opvip['twodismoney'] = $goodsdisarray[$optk]['twodismoney'];
            }
        }
        $disarray = serialize($optiondisarray);
        return $disarray ? : 0;
    }

    /**
     * Comment: 合并规格和商品团长分红数组
     * Author: wlf
     * Date: 2022/10/24 09:35
     */
    public static function mergeGroupArray($optiongrouparray,$goodsgrouparray){
        global $_W;
        $optiongrouparray = unserialize($optiongrouparray);
        $goodsgrouparray = unserialize($goodsgrouparray);
        foreach($optiongrouparray as $optk => &$opvip){
            if($opvip['onegroupmoney'] < 0.01){
                $opvip['onegroupmoney'] = $goodsgrouparray[$optk]['onegroupmoney'];
            }
            if($opvip['twogroupmoney'] < 0.01){
                $opvip['twogroupmoney'] = $goodsgrouparray[$optk]['twogroupmoney'];
            }
        }
        $disarray = serialize($optiongrouparray);
        return $disarray ? : 0;
    }

}



