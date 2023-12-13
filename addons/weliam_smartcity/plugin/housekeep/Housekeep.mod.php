<?php
defined('IN_IA') or exit('Access Denied');


class Housekeep
{
    /**
     * Comment: 默认服务类型列表
     * Author: wlf
     * Date: 2021/03/31 15:33
     * @return array
     */
    public static function defaultType()
    {
        global $_W;
        return [
            [
                'title' => '殡葬服务',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/bzfw.png',
                'list'  => [
                    '墓地',
                    '殡葬用品',
                    '白事承办'
                ],
            ],
            [
                'title' => '管道疏通',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/gdst.png',
                'list'  => [
                    '马桶疏通',
                    '下水道疏通',
                    '管道安装改造',
                    '打捞',
                    '化粪池清理',
                ],
            ],
            [
                'title' => '门锁服务',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/msfw.png',
                'list'  => [
                    '开锁',
                    '换锁',
                    '修锁',
                    '配钥匙',
                ],
            ],
            [
                'title' => '房屋维护',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/fwwx.png',
                'list'  => [
                    '卫浴安装维修',
                    '灶具安装维修',
                    '灯具安装维修',
                    '防水补漏',
                    '电路安装维修',
                    '打孔',
                    '粉刷防腐',
                    '门窗安装维修',
                    '暖气安装维修',
                ],
            ],
            [
                'title' => '家电维修',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/jdwx.png',
                'list'  => [
                    '电视维修',
                    '冰箱维修',
                    '空调维修',
                    '洗衣机维修',
                    '厨房家电维修',
                    '热水器维修',
                ],
            ],
            [
                'title' => '衣服洗护',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/ywxf.png',
                'list'  => [
                    '洗衣店',
                    '皮具养护',
                    '衣服鞋包改制',
                ],
            ],
            [
                'title' => '生活配送',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/shpt.png',
                'list'  => [
                    '跑腿服务',
                    '蔬菜水果',
                    '粮油副食',
                    '液化气煤气',
                    '桶装水',
                    '医院挂号',
                    '代排队',
                    '派发传单',
                    '机场接送',
                    '专人专送',
                ],
            ],
            [
                'title' => '鲜花绿植',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/xhlz.png',
                'list'  => [
                    '鲜花',
                    '绿植盆栽',
                    '园林园艺',
                    '仿真花饰',
                ],
            ],
            [
                'title' => '二手回收',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/eshs.png',
                'list'  => [
                    '手机回收',
                    '电器回收',
                    '家具回收',
                    '数码回收',
                    '金银回收',
                    '奢侈品回收',
                    '设备回收',
                    '建筑废料',
                    '库存积压',
                    '纺织皮革',
                ],
            ],
            [
                'title' => '搬家搬运',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/bjby.png',
                'list'  => [
                    '居民搬家',
                    '空调移机',
                    '公司搬家',
                    '搬家搬场',
                    '长途搬家',
                    '设备搬迁',
                    '起重吊装',
                ],
            ],
            [
                'title' => '保姆月嫂',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/bmys.png',
                'list'  => [
                    '保姆',
                    '钟点工',
                    '月嫂',
                    '陪护',
                    '育婴育儿师',
                    '催乳师',
                ],
            ],
            [
                'title' => '保洁清洗',
                'img'   => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/housekeep/web/resource/image/bjqx.png',
                'list'  => [
                    '家庭保洁',
                    '物业保洁',
                    '开荒保洁',
                    '高空清洗',
                    '灯具清洗',
                    '油烟机清洗',
                    '地毯清洗',
                    '空调清洗',
                    '沙发清洗',
                    '地板打蜡',
                    '石材养护翻新',
                    '玻璃清洗',
                    '墙纸清洗',
                    '除虫除蚁',
                    '空气净化',
                ],
            ],
        ];
    }
    /**
     * Comment: 获取服务类目列表
     * Author: wlf
     * Date: 2021/04/12 10:54
     * @return array
     */
    public static function getCategory()
    {
        global $_W;
        //一级分类获取
        $categoryes = pdo_getall(PDO_NAME.'housekeep_type',[
            'uniacid'    => $_W['uniacid'],
            'aid'        => $_W['aid'],
            'status'     => 1,
            'onelevelid' => 0
        ],['id','title'],'','sort DESC,id DESC');
        //二级分类信息获取
        foreach ($categoryes as $key => &$val) {
            $val['list'] = pdo_getall(PDO_NAME.'housekeep_type',[
                'uniacid'    => $_W['uniacid'],
                'aid'        => $_W['aid'],
                'status'     => 1,
                'onelevelid' => $val['id'],
            ],['id','title'],'','sort DESC,id DESC');
            //兼容 删除没有二级分类的一级分类信息
            if (!$val['list']) {
                unset($categoryes[$key]);
            }
        }
        $newcategoryes = [];
        foreach ($categoryes as $ca){
            $newcategoryes[] = $ca;
        }
        return $newcategoryes;
    }
    /**
     * Comment: 获取商户服务类目列表
     * Author: wlf
     * Date: 2021/05/24 10:54
     * @return array
     */
    public static function getStoreCategory($sid,$type){
        global $_W;
        if($type == 1){
            $type = 3;
        }
        //一级获取
        $categoryes = pdo_fetchall("SELECT distinct a.id,a.title FROM ".tablename(PDO_NAME."housekeep_type")." a LEFT JOIN".tablename('wlmerchant_housekeep_relation')." b ON a.id = b.onelevelid WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 1 AND a.onelevelid = 0 AND b.type = {$type} AND b.objid = {$sid} ORDER BY a.sort DESC,a.id DESC");
        //二级分类信息获取
        foreach ($categoryes as $key => &$val) {
            $val['list'] = pdo_fetchall("SELECT distinct a.id,a.title FROM ".tablename(PDO_NAME."housekeep_type")." a LEFT JOIN".tablename('wlmerchant_housekeep_relation')." b ON a.id = b.twolevelid WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 1 AND a.onelevelid = {$val['id']} AND b.type = {$type} AND b.objid = {$sid} ORDER BY a.sort DESC,a.id DESC");
            //兼容 删除没有二级分类的一级分类信息
            if (!$val['list']) {
                unset($categoryes[$key]);
            }
        }
        return $categoryes;
    }

    /**
     * Comment: 处理图片集
     * Author: wlf
     * Date: 2021/04/28 15:16
     * @return array
     */
    public static function beautifyImgInfo($imgs)
    {
        global $_W;
        $imgs = unserialize($imgs);
        if (empty($imgs)) {
            $imgs = [];
        } else {
            foreach ($imgs as &$th) {
                $th = tomedia($th);
            }
        }
        return $imgs;
    }


    /**
     * Comment: 查询关联服务类目
     * Author: wlf
     * Date: 2021/05/06 15:29
     * @return array
     */
    public static function getRelation($id,$type,$more = 0){
        if(empty($id)){
            $catearray = [];
        }else{
            if($more > 0){
                $catearray = pdo_fetchall("SELECT b.id,b.title FROM ".tablename('wlmerchant_housekeep_relation')." a LEFT JOIN".tablename('wlmerchant_housekeep_type')." b ON a.twolevelid = b.id WHERE a.objid = {$id} AND a.type = {$type} ORDER BY b.sort DESC,b.id DESC");
            }else{
                $catearray = pdo_fetchall("SELECT b.title FROM ".tablename('wlmerchant_housekeep_relation')." a LEFT JOIN".tablename('wlmerchant_housekeep_type')." b ON a.twolevelid = b.id WHERE a.objid = {$id} AND a.type = {$type} ORDER BY b.sort DESC,b.id DESC");
                $catearray = array_column($catearray,'title');
            }
        }
        return $catearray;
    }

    /**
     * Comment: 查询评论详细信息
     * Author: wlf
     * Date: 2021/05/08 18:55
     * @return array
     */
    public static function getCommentInfo($com){
        global $_W;
        $member = pdo_get('wlmerchant_member',array('id' => $com['mid']),array('nickname','avatar'));
        $com['nickname'] = $member['nickname'];
        $com['avatar'] = tomedia($member['avatar']);
        $com['createtime'] = date('m-d H:i',$com['createtime']);
        $com['pic'] = Housekeep::beautifyImgInfo($com['pic']);
        $com['replypicone'] = Housekeep::beautifyImgInfo($com['replypicone']);
        $com['replytotal'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_housekeep_reply')." WHERE cid = {$com['id']}");
        $com['praisetotal'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_housekeep_praise')." WHERE cid = {$com['id']}");
        $com['replylist'] = pdo_fetchall("SELECT a.id,a.content,b.nickname FROM ".tablename(PDO_NAME."housekeep_reply")." a LEFT JOIN".tablename('wlmerchant_member')." b ON a.smid = b.id WHERE cid = {$com['id']} LIMIT 5");
        $com['praiseflag'] = pdo_getcolumn(PDO_NAME.'housekeep_praise',array('cid'=>$com['id'],'mid'=>$_W['mid']),'id');
        $com['praiseflag'] = $com['praiseflag'] ? 1 : 0;
        return $com;
    }

    /**
     * Comment: 判断是否需要审核
     * Author: wlf
     * Date: 2021/05/19 17:31
     * $type 1=商户 2个人
     * $$project 1=需求 2=服务
     * @return array
     */
    public static function getStatus($type,$objid,$set,$project){
        //判断权限以及是否需要审核
        $objname = '';
        if($type == 1){
            if($project == 1){
                if($set['demandpass'] == 2){
                    $attinfo = Attestation::checkAttestation(2,$objid);
                    if(empty($attinfo['attestation'])){
                        $check = 1;
                    }
                }else if(empty($set['demandpass'])){
                    $check = 1;
                }
            }else if($project == 2){
                $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $objid),array('storename','groupid'));
                $objname = $merchant['storename'];
                if($set['servicepass'] == 2){
                    $attinfo = Attestation::checkAttestation(2,$objid);
                    if(empty($attinfo['attestation'])){
                        $check = 1;
                    }
                }else if(empty($set['servicepass'])){
                    $check = 1;
                }
            }
        }else if($type == 2){
            if($project == 1){
                if($set['demandpass'] == 2){
                    $attinfo = Attestation::checkAttestation(1,$objid);
                    if(empty($attinfo['attestation'])){
                        $check = 1;
                    }
                }else if(empty($set['demandpass'])){
                    $check = 1;
                }
            }else if($project == 2){
                $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $objid),array('mid','name','mealid'));
                $objname = $artificer['name'];
                $objid = $artificer['mid'];
                if($set['servicepass'] == 2){
                    $attinfo = Attestation::checkAttestation(1,$objid);
                    if(empty($attinfo['attestation'])){
                        $check = 1;
                    }
                }else if(empty($set['servicepass'])){
                    $check = 1;
                }
            }
        }
        if($check > 0){
            $status = 5;
        }else{
            $status = 1;
        }
        return ['status' => $status,'objname'=> $objname];
    }


    /**
     * Comment: 判断认证权限
     * Author: wlf
     * Date: 2021/05/28 17:50
     * $type 1=个人 2商户
     * $$project 1=需求 2=服务
     * @return array
     */
    public static function getJurisdiction($type,$objid,$project){
        global $_W;
        $set = Setting::agentsetting_read('housekeep');
        $attestationRight = unserialize($set['attestationRight']);
        if(in_array($project,$attestationRight)){
            $attinfo = Attestation::checkAttestation($type,$objid);
            if(empty($attinfo['attestation'])){
                return 1;
            }
        }
        return 0;
    }


    /**
     * Comment: 判断会员权限
     * Author: wlf
     * Date: 2022/08/17 11:21
     * @return array
     */
    public static function getVipRight($mid,$project){
        global $_W;
        $set = Setting::agentsetting_read('housekeep');
        $halfRight = unserialize($set['halfRight']);
        if(in_array($project,$halfRight)){
            $halfcard = WeliamWeChat::VipVerification($mid, true); //会员状态
            if(empty($halfcard)){
                return 1;
            }
        }
        return 0;
    }


    /**
     * Comment: 获取服务项目基础信息
     * Author: wlf
     * Date: 2021/05/24 13:48
     * @return array
     */
    public static function getServiceBaseInfo($id){
        $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('id','title','thumb','pricetype','price','salenum','unit'));
        $service['thumb'] = tomedia($service['thumb']);
        $service['price'] = self::getServicePrice($service['price'],$service['pricetype'],$service['unit']);
        return $service;
    }

    /**
     * Comment: 服务金额优化
     * Author: wlf
     * Date: 2021/05/24 13:58
     * @return array
     */
    public static function getServicePrice($price,$pricetype,$unit){
        if($pricetype == 1){
            $price = '预约金￥'.$price.'/'.$unit;
        }else if($pricetype == 2){
            $price = '￥'.$price.'/'.$unit;
        }else{
            $price = '价格面议';
        }
        return $price;
    }


    /**
     * Comment: 计算结算金额
     * Author: wlf
     * Date: 2021/05/07 16:55
     * @return array
     */
    public static function getSettlementMoney($price,$pricetype,$type,$objid){
        global $_W;
        if($type == 1){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $objid),array('groupid','settlementtext'));
            $settlementtext = unserialize($merchant['settlementtext']);
            if($pricetype == 1){
                $pro = $settlementtext['appsettpro'];
            }else if($pricetype == 2){
                $pro = $settlementtext['truesettpro'];
            }
            if($pro < 0.01){
                $pro = pdo_getcolumn(PDO_NAME.'chargelist',array('id'=>$merchant['groupid']),'defaultrate');
            }
        }else{
            $mealid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$objid),'mealid');
            $meal = pdo_get(PDO_NAME.'housekeep_meals',array('id' => $mealid),array('appsettpro','truesettpro'));
            if($pricetype == 1){
                $pro = $meal['appsettpro'];
            }else if($pricetype == 2){
                $pro = $meal['truesettpro'];
            }
        }
        $settlementmoney = sprintf("%.2f",$price * $pro / 100);
        return $settlementmoney;
    }

    /**
     * Comment: 订单结算
     * Author: wlf
     * Date: 2021/05/07 17:20
     * @return array
     */
    public static function settlementOrder($id){
        global $_W;
        $order = pdo_get('wlmerchant_order',array('id' => $id),array('fkid','price','settlementmoney','orderno','issettlement','specid','sid','aid'));
        if (empty($order['issettlement'])){
            $flag = pdo_getcolumn(PDO_NAME.'autosettlement_record',['orderno' => $order['orderno']],'id'); //判断是否已结算
            if (!empty($flag)){$res = 1;}
            $agentmoney = sprintf("%.2f",$order['price'] - $order['settlementmoney']);
            $settlementmoney = $order['settlementmoney'];
            $data = [
                'uniacid'       => $_W['uniacid'] ,
                'aid'           => $order['aid'] ,
                'type'          => 17,
                'merchantid'    => $order['sid'] ,
                'orderid'       => $id ,
                'orderno'       => $order['orderno'] ,
                'goodsid'       => $order['fkid'] ,
                'orderprice'    => $order['price'] ,
                'agentmoney'    => $agentmoney,
                'merchantmoney' => $settlementmoney,
                'createtime'    => time()
            ];
            $res          = pdo_insert(PDO_NAME . 'autosettlement_record' , $data);
            $settlementid = pdo_insertid();
            if ($res) {
                if (abs($settlementmoney) > 0) {  //结算给商户
                    if ($order['specid'] == 1) {
                        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney+{$settlementmoney},nowmoney=nowmoney+{$settlementmoney} WHERE id = {$order['sid']}");
                        $change['merchantnowmoney'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $data['merchantid']] , 'nowmoney');
                        Store::addcurrent(1 , 17 , $order['sid'] , $settlementmoney , $change['merchantnowmoney'] , $order['id'] , '' , $_W['uniacid'] , $order['aid']);
                    }else {
                        $mid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$order['sid']),'mid');
                        Member::credit_update_credit2($mid,$settlementmoney,'家政服务项目结算');
                    }
                }
                if (abs($data['agentmoney']) > 0) {
                    pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$data['agentmoney']},nowmoney=nowmoney+{$data['agentmoney']} WHERE id = {$data['aid']}");
                    $change['agentnowmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers' , ['id' => $data['aid']] , 'nowmoney');
                    Store::addcurrent(2 , 17 , $data['aid'] , $data['agentmoney'] , $change['agentnowmoney'] , $order['id'] , '' , $_W['uniacid'] , $order['aid']);
                }
                pdo_update('wlmerchant_autosettlement_record' , $change , ['id' => $settlementid]);
                pdo_update('wlmerchant_order',['issettlement' => 1,'settletime' => time()],['id' => $order['id']]);
            }
        }else{
            $res = 1;
        }
        return $res;
    }

    /**
     * Comment: 订单退款
     * Author: wlf
     * Date: 2021/05/07 18:25
     * @return array
     */
    static function refund($id, $money = 0, $unline = '') {
        $order = pdo_get(PDO_NAME . 'order', array('id' => $id));
        if($money < $order['blendcredit']){
            $blendcredit = $money;
            $money = 0;
        }else if($order['blendcredit'] > 0){
            $blendcredit = $order['blendcredit'];
            $money = sprintf("%.2f",$money - $blendcredit);
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '家政订单退款', 'housekeep', 2,$blendcredit);
        }
        if ($res['status']) {
            //修改服务项目销量
            pdo_fetch("update" . tablename('wlmerchant_housekeep_service') . "SET salenum=salenum-{$order['num']} WHERE id = {$order['fkid']}");
            //修改退款申请记录
            pdo_update('wlmerchant_aftersale',array('status' => 2),array('orderid' => $id,'plugin' => 'housekeep'));
            if ($order['applyrefund']) {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $order['id']));
                $reason = '买家申请退款。';
            } else {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $order['id']));
                $reason = '家政系统退款。';
            }

            News::refundNotice($id,'housekeep',$money,$reason);
        } else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }


    /**
     * Comment: 支付回调
     * Author: wlf
     * Date: 2021/04/25 16:40
     * @return array
     */
    static function payHousekeepOrderNotify($params)
    {
        Util::wl_log('payResult_notify',PATH_PLUGIN."housekeep/data/",$params); //写入异步日志记录
        $order           = pdo_get('wlmerchant_order',['orderno' => $params['tid']],[
            'id',
            'fightstatus',
            'mid',
            'uniacid',
            'num',
            'specid',
            'price',
            'orderno',
            'fkid',
            'aid',
            'status'
        ]);
        $_W['uniacid']   = $order['uniacid'];
        $_W['aid']       = $order['aid'];
        $data            = [
            'status'  => 3,
            //'disorderid' => $disorderid,
            'paytime' => time()
        ];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        //业务逻辑
        if($order['fightstatus'] == 1){
            $data['status'] = 1;
            //修改服务项目销量
            pdo_fetch("update" . tablename('wlmerchant_housekeep_service') . "SET salenum = salenum + {$order['num']} WHERE id = {$order['fkid']}");

            $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$order['mid']),'nickname');
            $service = pdo_get('wlmerchant_housekeep_service',['id' => $order['fkid']],['type','objid','title']);
            $first   = "用户【{$nickname}】支付了[{$service['title']}]的家政服务";//消息头部
            $type    = "家政服务";//业务类型
            $content = $service['title'];//业务内容
            $status  = "待处理";//处理结果
            $remark  = "请尽快联系客户处理!";//备注信息
            $time    = $data['paytime'];//操作时间
            if($service['type'] == 1){
                News::noticeShopAdmin($service['objid'], $first , $type , $content , $status , $remark , $time);
            }else{
                $mid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$service['objid']),'mid');
                News::jobNotice($mid,$first,$type,$content,$status,$remark,$time);
            }
        }else if ($order['fightstatus'] == 2) { //付费入驻
            $meal      = pdo_get('wlmerchant_housekeep_meals',['id' => $order['specid']],['day','check']);
            $artificer = pdo_get('wlmerchant_housekeep_artificer',['id' => $order['fkid']],['name','endtime']);
            if ($meal['check'] > 0) {
                $newinfo['status'] = 5;
            } else {
                $newinfo['status'] = 1;
            }
            //计算时间
            if ($artificer['endtime'] > time()) {
                $newinfo['endtime'] = $artificer['endtime'] + $meal['day'] * 86400;
            } else {
                $newinfo['endtime'] = time() + $meal['day'] * 86400;
            }
            pdo_update('wlmerchant_housekeep_artificer',$newinfo,['id' => $order['fkid']]);
            if ($newinfo['status'] == 5) {
                $membername = pdo_getcolumn(PDO_NAME.'member',['id' => $order['mid']],'nickname');
                //发送模板消息
                $first   = '您好，一个家政服务者入驻申请待审核';
                $type    = '审核家政服务者入驻信息';
                $content = '服务者姓名:'.$artificer['name'];
                $status  = '待审核';
                $remark  = "微信用户[".$membername."]入驻家政服务者申请,请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
            }
        } else if ($order['fightstatus'] == 3) {  //付费发布
            $set = Setting::agentsetting_read('housekeep');
            $demand = pdo_get('wlmerchant_housekeep_demand',['id' => $order['fkid']],['mid','topendtime','type']);
            $getstatus = self::getStatus(2,$demand['mid'],$set,1);
            if ($getstatus['status'] == 1) {  //免审核
                pdo_update('wlmerchant_housekeep_demand',[
                    'updatetime' => time(),
                    'status'     => 1,
                    'createtime' => time()
                ],['id' => $order['fkid']]);
            } else {
                pdo_update('wlmerchant_housekeep_demand',[
                    'updatetime' => time(),
                    'status'     => 5,
                    'createtime' => time()
                ],['id' => $order['fkid']]);
                $typetitle  = pdo_getcolumn(PDO_NAME.'housekeep_type',['id' => $demand['type']],'title');
                $membername = pdo_getcolumn(PDO_NAME.'member',['id' => $order['mid']],'nickname');
                //发送模板消息
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核用户家政需求';
                $content = '需求类目:'.$typetitle;
                $status  = '待审核';
                $remark  = "用户[".$membername."]发布了一个商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$order['aid'],$first,$type,$content,$status,$remark,time(),'');
            }
        } else if ($order['fightstatus'] == 4) {  //置顶
            $demand = pdo_get('wlmerchant_housekeep_demand',['id' => $order['fkid']],['topendtime','type']);
            if ($demand['topendtime'] > time()) {
                $newtime = $demand['topendtime'] + 86400 * $order['num'];
            } else {
                $newtime = time() + 86400 * $order['num'];
            }
            pdo_update('wlmerchant_housekeep_demand',[
                'updatetime' => time(),
                'topflag'    => 1,
                'topendtime' => $newtime
            ],['id' => $order['fkid']]);
        } else if ($order['fightstatus'] == 5) {  //刷新
            pdo_update('wlmerchant_housekeep_demand',['updatetime' => time()],['id' => $order['fkid']]);
        }
        //结算
        $res = pdo_update('wlmerchant_order',$data,['id' => $order['id']]);
        if ($res && $order['fightstatus'] != 1) {
            Store::ordersettlement($order['id']);
        }
    }
    //计划任务
    static function doTask()
    {
        global $_W;
        //置顶时间过期，自动下线
        pdo_update('wlmerchant_housekeep_demand',['topflag' => 0],['topflag' => 1,'topendtime <' => time()]);
        //删除过期订单
        $time = time() - 43200;
        pdo_delete('wlmerchant_order',[
            'plugin'        => 'housekeep',
            'status'        => 0,
            'fightstatus >' => 2,
            'createtime <'  => $time
        ]);
    }


    /**
     * Comment: 根据条件获取对应的列表
     * Author: zzw
     * Date: 2021/5/6 13:57
     * @param int    $serviceType   服务类型：0=全部,1=商户服务商,2=个人服务商,3=客户需求,4=服务项目
     * @param int    $page  当前页
     * @param int    $pageIndex 每页的数量
     * @param string $shopWhere 商户服务商条件
     * @param string $artificerWhere    个人服务商条件
     * @param string $demandWhere   客户需求条件
     * @param string $serviceWhere  服务项目条件
     * @param int $order    排序方式
     *  -- service_type=0：1=推荐
     *  -- service_type=1：1=推荐，2=时间，3=距离，4=销量
     *  -- service_type=2：1=推荐，2=时间，3=距离
     *  -- service_type=3：1=发布时间，2=上门时间，3=距离
     *  -- service_type=4：1=推荐，2=时间，3=距离，4=销量
     * @param int    $lng   经度
     * @param int    $lat   纬度
     * @return array
     */
    public static function getList(int $serviceType = 0,$page = 1,$pageIndex = 10,$shopWhere = '',$artificerWhere = '',
                                   $demandWhere = '',$serviceWhere = '',$order = 1,$lng = 0,$lat = 0,$aid = 0){
        global $_W;
        $_W['contact'] = 0;
        //基本条件生成
        if(empty($aid)){$aid = $_W['aid'];}
        $publicWhere = " WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} ";
        $pageStart = $page * $pageIndex - $pageIndex;
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $distances = getDistancesSql($lat,$lng,'lat','lng');
        //根据类型生成对应的sql语句和排序方式 服务类型：0=全部,1=商户服务商,2=个人服务商,3=客户需求,4=服务项目
        switch ($serviceType){
            case 0:
                //排序方式：1=推荐
                $orderBy = " ORDER BY createtime DESC ";
                //sql语句
                $field = "         *";
                $sql = "SELECT {$field} FROM (SELECT id,createtime,{$distances} as distances,'1' as service_type FROM ".tablename(PDO_NAME."merchantdata")
                    ." {$publicWhere} {$shopWhere} "
                    ." UNION ALL SELECT id,createtime,{$distances} as distances,'2' as service_type FROM ".tablename(PDO_NAME."housekeep_artificer")
                    ." {$publicWhere} {$artificerWhere} "
                    ." UNION ALL SELECT id,createtime,{$distances} as distances,'3' as service_type FROM ".tablename(PDO_NAME."housekeep_demand")
                    ." {$publicWhere} {$demandWhere} "
                    ." UNION ALL SELECT id,createtime,{$distances} as distances,'4' as service_type FROM ".tablename(PDO_NAME."housekeep_service")
                    ." {$publicWhere} {$serviceWhere}) as a";
                break;//获取全部
            case 1:
                //排序方式：1=推荐，2=时间，3=距离，4=销量
                switch ($order){
                    case 1: $orderBy = " ORDER BY listorder DESC,id DESC ";break;
                    case 2: $orderBy = " ORDER BY createtime DESC ";break;
                    case 3: $orderBy = " ORDER BY distances ASC,createtime DESC ";break;
                    case 4: $orderBy = " ORDER BY salenum DESC ";break;
                }
                //sql语句
                $salenum = "SELECT sum(salenum) FROM ".tablename(PDO_NAME."housekeep_service")
                    ." {$publicWhere} AND `type` = 1 and objid = shop_id ";
                $field = "id,createtime,{$distances} as distances,'1' as service_type,id as shop_id,IFNULL(({$salenum}),0) as salenum";
                $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata")
                    ." {$publicWhere} {$shopWhere} ";
                break;//获取商户服务商
            case 2:
                //排序方式：1=推荐，2=时间，3=距离
                switch ($order){
                    case 1:$orderBy = " ORDER BY sort DESC ";break;
                    case 2:$orderBy = " ORDER BY createtime DESC ";break;
                    case 3:$orderBy = " ORDER BY distances ASC,createtime DESC ";break;
                    case 4:$orderBy = " ORDER BY salenum DESC ";break;
                }
                //sql语句
                $salenum = "SELECT sum(salenum) FROM ".tablename(PDO_NAME."housekeep_service")
                    ." {$publicWhere} AND `type` = 2 and objid = id ";
                $field = "id,createtime,{$distances} as distances,'2' as service_type,IFNULL(({$salenum}),0) as salenum";
                $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_artificer")
                    ." {$publicWhere} {$artificerWhere} ";
                break;//获取个人服务商
            case 3:
                $set = Setting::agentsetting_read('housekeep');
                //判断用户权限
                if($set['contact'] > 0){
                    $_W['contact'] = Housekeep::checkcontact($_W['mid']);
                }else{
                    $_W['contact'] = 0;
                }
                //排序方式：1=推荐，2=发布时间，3=上门时间，4=距离
                switch ($order){
                    case 1:$orderBy = " ORDER BY topflag DESC,updatetime DESC ";break;
                    case 2:$orderBy = " ORDER BY topflag DESC,createtime DESC ";break;
                    case 3:$orderBy = " ORDER BY topflag DESC,visitingtime DESC ";break;
                    case 4:$orderBy = " ORDER BY topflag DESC,distances ASC ";break;
                }
                //sql语句
                $field = "id,createtime,{$distances} as distances,'3' as service_type,lat,lng";
                $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_demand")
                    ." {$publicWhere} {$demandWhere} ";
                break;//获取客户需求
            case 4:
                //排序方式：1=推荐，2=时间，3=距离，4=销量
                switch ($order){
                    case 1:$orderBy = " ORDER BY sort DESC ";break;
                    case 2:$orderBy = " ORDER BY createtime DESC ";break;
                    case 3:$orderBy = " ORDER BY distances ASC,createtime DESC ";break;
                    case 4:$orderBy = " ORDER BY salenum DESC ";break;
                }
                //sql语句
                $field = "id,createtime,{$distances} as distances,'4' as service_type";
                $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_service")
                    ." {$publicWhere} {$serviceWhere} ";
                break;//获取服务项目
        }
        //运行sql语句获取信息列表
        $list = pdo_fetchall($sql.$orderBy.$limit);
        $list = array_map(function ($item){
            //信息处理
            $itemRes = Housekeep::getDesc($item['id'],$item['service_type']);
            //距离处理
            if($item['distances'] > 0){
                if($item['distances'] < 1000) $itemRes['distances'] = $item['distances'].'m';
                else $itemRes['distances'] = sprintf("%.2f",$item['distances'] / 1000).'km';
            }
            return $itemRes;
        },$list);
        //获取总数信息  根据分页信息获取
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql);
        //信息拼装
        $data = [
            'list'        => $list,
            'page'        => $page,
            'page_number' => ceil($total / $pageIndex)
        ];

        return $data;
    }
    /**
     * Comment: 获取服务详细信息
     * Author: zzw
     * Date: 2021/4/30 17:45
     * @param int $id
     * @param int $serviceType
     * @return false|mixed
     */
    public static function getDesc($id,$serviceType){
        global $_W;
        $isAuthentication = intval(0);//默认未认证
        $isBond = intval(0);//默认未交保证金
        //根据类型生成查询相关信息    类型:1=商户服务商,2=个人服务商,3=客户需求,4=服务项目  logo,thumbs,name,address,
        switch ($serviceType){
            case 1:
                $field = "logo,adv as thumbs,storename as name,address";
                $table = tablename(PDO_NAME."merchantdata");
                $tip   = '商户服务商';
                //获取分类列表
                $labelList = self::getType(3,$id);
                //获取服务项目
                $serviceList = self::getService(1,$id);
                //判断是否获取保证金
                if(p('attestation')){
                    $attestation = Attestation::checkAttestation(2,$id);
                    $isAuthentication = $attestation['attestation'] > 0 ? 1 : 0;//是否认证
                    $isBond = $attestation['bondflag'] > 0 ? 1 : 0;//是否提交保证金
                }

                break;//商户服务商
            case 2:
                $field = "thumb as logo,thumbs,name,address,mid";
                $table = tablename(PDO_NAME."housekeep_artificer");
                $tip   = '个人服务商';
                $labelList = self::getType(2,$id);
                //获取服务项目
                $serviceList = self::getService(2,$id);
                //判断是否获取保证金
                if(p('attestation')){
                    $mid = pdo_getcolumn(PDO_NAME."housekeep_artificer",['id'=>$id],'mid');
                    $attestation = Attestation::checkAttestation(1,$mid);
                    $isAuthentication = $attestation['attestation'] > 0 ? 1 : 0;//是否认证
                    $isBond = $attestation['bondflag'] > 0 ? 1 : 0;//是否提交保证金
                }
                break;//个人服务商
            case 3:
                $field = "'' as logo,thumbs,type as name,address,visitingtime,topflag,mid,detail,mobile,lat,lng";
                $table = tablename(PDO_NAME."housekeep_demand");
                $tip   = '客户需求';
                $labelList = [];
                break;//客户需求
            case 4:
                $field = "thumb as logo,adv as thumbs,title as name,type as address,price,pricetype,unit,salenum";
                $table = tablename(PDO_NAME."housekeep_service");
                $tip   = '服务项目';
                $labelList = self::getType(1,$id);
                break;//服务项目
        }
        //运行sql进行查询
        $info                      = pdo_fetch("SELECT {$field} FROM ".$table." WHERE id = {$id} ");
        $info['id']                = $id;
        $info['service_type']      = $serviceType;
        $info['tip']               = $tip;
        $info['label']             = $labelList ? : [];//分类列表
        $info['service']           = $serviceList ? : [];//服务项目列表
        $info['is_authentication'] = $isAuthentication;//是否认证
        $info['is_bond']           = $isBond;//是否提交保证金
        //公共信息处理
        $info['logo'] = tomedia($info['logo']);
        $thumbs = unserialize($info['thumbs']);
        $info['long_logo'] = is_array($thumbs) ? tomedia($thumbs[0]) : '';
        //私有信息处理
        switch ($serviceType){
            case 3:
                //logo=long_logo
                $info['logo'] = $info['long_logo'];
                $info['thumbs'] = self::beautifyImgInfo($info['thumbs']);
                $info['visitingtime'] = date('Y-m-d H:i',$info['visitingtime']);
                //name = 需求类型标题
                $info['name'] = pdo_getcolumn(PDO_NAME."housekeep_type",['id'=>$info['name']],'title');
                $member = pdo_get('wlmerchant_member',array('id' => $info['mid']),array('nickname','avatar'));
                $info['nickname'] = $member['nickname'];
                $info['avatar'] = tomedia($member['avatar']);
                if($_W['contact'] > 0){
                    $info['mobile'] = '';
                    $info['hideprivate'] = 1;
                }
                break;//客户需求
            case 4:
                //address = 发布类型
                if($info['type'] == 1){
                    $info['address'] = '商户发布';
                } else{
                    $info['address'] = '个人发布';
                }
                $info['price'] = self::getServicePrice($info['price'],$info['pricetype'],$info['unit']);
                break;//服务项目
        }
        //删除多余的字段

        return $info;
    }
    /**
     * Comment: 根据类型获取所有二级分类
     * Author: zzw
     * Date: 2021/5/6 10:11
     * @param int $type 类型：1=服务项目 2=个人服务商 3=商户服务商
     * @param int $id
     * @return array
     */
    public static function getType(int $type,int $id){
        global $_W;
        //获取二级分类id列表
        $idList = pdo_getall(PDO_NAME."housekeep_relation",['type'=>$type,'objid'=>$id],['twolevelid']);
        if(!is_array($idList) || count($idList) <= 0) return [];
        //获取二级分类标题列表
        $ids = array_column($idList,'twolevelid');
        $list = pdo_getall(PDO_NAME."housekeep_type",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'id IN'=>$ids],['title']);

        return is_array($list) && count($list) > 0 ? array_column($list,'title') : [];
    }
    /**
     * Comment: 根据条件获取服务项目信息列表
     * Author: zzw
     * Date: 2021/5/6 16:54
     * @param int $type 1=商户发布 2=个人发布
     * @param int $id   商户/个人服务商id
     * @param string $order 排序
     * @param int    $page  当前页
     * @param int    $pageIndex 每页的数量
     * @return array|false|mixed
     */
    public static function getService(int $type,int $id,$order = 'salenum DESC',$page = 1,$pageIndex = 5){
        $field = ['id','thumb','title','salenum','pricetype','price','unit'];
        $list = pdo_getall(PDO_NAME."housekeep_service",['type'=>$type,'objid'=>$id,'status'=>1],$field,'',$order,[$page,$pageIndex]);
        foreach($list as $key => &$val){
            //处理基本信息
            $val['thumb'] = tomedia($val['thumb']);
            //处理价格  金额类型 0无金额 1预约金 2实价
            $val['price_text'] = self::getServicePrice($val['price'],$val['pricetype'],$val['unit']);
        }
        return $list;
    }

    /**
     * Comment: 判断用户是否是入驻服务人员
     * Author: wlf
     * Date: 2022/08/01 10:14
     */
    public static function checkcontact($mid){
        global $_W;
        $contact = 1;
        //判断是否是单独入驻服务者
        $perfalg = pdo_get('wlmerchant_housekeep_artificer',array('mid' => $mid,'uniacid' => $_W['uniacid'],'status' => 1),array('id'));
        if(!empty($perfalg)){
            $contact = 0;
        }else{
            //判断商户服务者
            $stores = pdo_getall('wlmerchant_merchantuser',array('mid' => $mid,'uniacid' => $_W['uniacid'],'status' => 2,'enabled' => 1),array('storeid'));
            if(!empty($stores)){
                foreach ($stores as $sid){
                    $storeflag =  pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid['storeid']),'housekeepstatus');
                    if($storeflag > 0){
                        $contact = 0;
                    }
                }
            }
        }
        return $contact;
    }


}
