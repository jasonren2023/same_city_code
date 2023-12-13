<?php
defined('IN_IA') or exit('Access Denied');

class AttestationModuleUniapp extends Uniapp {
    /**
     * Comment: 获取个人 / 商户的认证信息
     * Author: zzw
     * Date: 2019/11/6 11:51
     */
    public function getInfo(){
        global $_W,$_GPC;
        #1、参数获取
        $type = $_GPC['type'] ? : 1;//1个人认证 2商户认证
        $id = $_GPC['sid'] ? : $_W['mid'];//id（商户id|用户id）
        #2、条件生成
        $where['uniacid'] = $_W['uniacid'];
        $where['status'] = 1;
        if($type == 1){
            $where['mid'] = $id;
            $where['type'] = 1;
        }else{
            $where['storeid'] = $id;
        }
        #3、获取认证信息
        $isAttestation = pdo_getall(PDO_NAME."attestation_list",$where,array('remake','checkstatus'),'','ID DESC');
        $isMoney = pdo_getcolumn(PDO_NAME."attestation_money",$where,'money');
        #4、认证状态判断
        $data['is_attestation'] = intval($isAttestation[0]['checkstatus'] > 0 ? $isAttestation[0]['checkstatus'] : 0);
        $data['is_money'] = $isMoney > 0 ? $isMoney : 0;
        if($data['is_attestation'] == 3){
            $data['reason'] = $isAttestation[0]['remake'];
        }
        #5、获取设置信息
        $set = Setting::wlsetting_read('attestation');
        $set['type'] = is_array(unserialize($set['type'])) ? unserialize($set['type']) : [];
        //判断认证功能是否开启
        $typeStr = $type == 1 ? 'member' : 'store';
        //认证功能：1=开启；0=关闭
        if($set['switch'] == 1 && in_array($typeStr,$set['type'])) $data['switch'] = 1;
            else  $data['switch'] = 0;
        //保证金功能：1=开启；0=关闭
        if($type == 1 && $set['moneyswitch'] == 1 && $set['memberstatus'] == 1){
            $data['money_switch'] = 1;
            if($data['is_money'] > 0){
                $data['money'] = $data['is_money'];
            }else{
                if(Customized::init('distributionText') > 0){
                    $membermoney = unserialize($set['membermoneyarray']);
                    $data['money'] = min($membermoney).'-'.max($membermoney);
                }else{
                    $data['money'] = $set['membermoney'];
                }
            }
        }else if($type == 2 && $set['moneyswitch'] == 1 && $set['storestatus'] == 1){
            $data['money_switch'] = 1;
            if($data['is_money'] > 0){
                $data['money'] = $data['is_money'];
            }else{
                if(Customized::init('distributionText') > 0){
                    $storemoney = unserialize($set['storemoneyarray']);
                    $data['money'] = min($storemoney).'-'.max($storemoney);
                }else{
                    $data['money'] = $set['storemoney'];
                }
            }
        }else{
            $data['money_switch'] = 0;
        }
        #6、返回认证信息
        $tips = $type == 1 ? '个人': '商户';
        $this->renderSuccess($tips.'认证信息',$data);
    }
    /**
     * Comment: 信息设置接口
     * Author: zzw
     * Date: 2019/11/6 12:00
     */
    public function infoSet(){
        global $_W,$_GPC;
        #1、参数获取
        $type = $_GPC['type'] ? : 1;//1个人认证 2商户认证
        $id = $_GPC['sid'] ? : $_W['mid'];//id（商户id|用户id）
        $operation = $_GPC['operation'] ? : 'get' ;//操作类型：get=获取信息；set=修改信息
        $pic = $_GPC['pic'] ? serialize(explode(',',$_GPC['pic'])) : '' ;
        $info = [
            'name'    => $_GPC['name'] ? : '' ,
            'phone'   => $_GPC['phone'] ? : '' ,
            'pic'     => $pic,
            'cardnum' => $_GPC['cardnum'] ? : '' ,
        ];
        $attId = $_GPC['att_id'] ? : -1;
        #2、获取设置信息
        if(is_array($_W['wlsetting']['attestation']) && count($_W['wlsetting']['attestation']) > 0) $set = $_W['wlsetting']['attestation'];
        else $set = Setting::wlsetting_read('attestation');
        #3、根据操作类型进行不同的操作
        if($operation == 'set'){
            WeliamWeChat::startTrans();
            //信息拼装
            $info['uniacid'] = $_W['uniacid'];
            $info['aid'] = $_W['aid'];
            if($type == 1) {
                $info['mid'] = $id;
                //修改用户信息
                $memberData = [
                    'realname' => $info['name'],
                    'mobile'   => $info['phone']
                ];
                $moreformid = $set['personmoreformid'];
                pdo_update(PDO_NAME."member",$memberData,['id'=>$info['mid']]);
            } else{
                $info['storeid'] = $id;
                $info['mid'] = $_W['mid'];
                //修改商户信息
                $moreformid = $set['storemoreformid'];
                pdo_update(PDO_NAME."merchantdata",[ 'tel'   => $info['phone']],['id'=>$info['storeid']]);
            }
            //获取更多信息
            if($moreformid > 0){
                $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
                $diyFormSet  = pdo_getcolumn(PDO_NAME."diyform",['id'=>$moreformid],'info');
                $diyFormSet  = array_values(json_decode(base64_decode($diyFormSet), true)['list']);//页面的配置信息
                foreach($diyFormInfo as $formKey => &$formVal){
                    $formVal['att_show'] = $diyFormSet[$formKey]['data']['att_show'];
                }
                $info['moreinfo'] = serialize($diyFormInfo);
            }
            $info['type'] = $type;
            $info['updatetime'] = time();
            $info['status'] = 1;
            $info['checkstatus'] = $set['audits'] == 1 ? 2 : 1;
            $info['subjectname'] = $info['name'];
            $info['atttel'] = $info['phone'];
            unset($info['name']);
            unset($info['phone']);
            //修改 | 添加认证信息
            if($attId > 0){
                $res = pdo_update(PDO_NAME."attestation_list",$info,['id'=>$attId]);
            }else{
                //判断是否已经申请
                $selectData = [
                    'mid'     => $info['mid'] ,
                    'storeid' => intval($info['storeid']) ,
                    'type'    => $info['type'] ,
                ];
                $isHave = pdo_get(PDO_NAME."attestation_list",$selectData);
                if($isHave) {
                    WeliamWeChat::rollback();
                    $this->renderError('请勿重复提交认证信息！');
                }
                //添加认证信息
                $info['createtime'] = time();
                $res = pdo_insert(PDO_NAME."attestation_list",$info);
            }
            //判断操作是否成功
            if(!$res){
                WeliamWeChat::rollback();
                $this->renderError('申请失败，请刷新重试!');
            } else {
                WeliamWeChat::commit();
                if(empty($set['audits'])){  //审核 发送模板消息
                    if($type == 1){
                        $first   = "用户【{$info['subjectname']}】提交了一个认证申请";//消息头部
                        $type2   = "用户认证审核通知";//业务类型
                    }else{
                        $first   = "商户【{$info['subjectname']}】提交了一个认证申请";//消息头部
                        $type2   = "商户认证审核通知";//业务类型
                    }
                    $content = '认证中心审核';//业务内容
                    $status  = "待审核";//处理结果
                    $remark  = "请尽快前往后台审核！";//备注信息
                    $time    = time();//操作时间
                    News::noticeAgent('attestation' , -1 , $first , $type2 , $content , $status , $remark , $time);
                }
                $this->renderSuccess('申请成功,请等待审核结果!');
            }
        }else{
            $getWhere = " WHERE uniacid = {$_W['uniacid']} ";
            $sql = "SELECT id,cardnum,pic,moreinfo,subjectname,atttel FROM ".tablename(PDO_NAME."attestation_list");
            if($attId > 0) $getWhere .= " AND id = {$attId} ";
            if($type == 1){
                //获取用户认证信息
                $setInfo1 = pdo_fetch("SELECT realname as name,mobile as phone FROM ".tablename(PDO_NAME."member")." WHERE id = {$id} ");
                $getWhere .= " AND mid = {$id} AND type = 1 ";
                $setInfo2 = pdo_fetch($sql.$getWhere);
                $moreformid = $set['personmoreformid'];
            }else{
                //获取商户认证信息
                $setInfo1 = pdo_fetch("SELECT storename,tel FROM ".tablename(PDO_NAME."merchantdata")." WHERE id = {$id} ");
                $getWhere .= " AND storeid = {$id} AND type = 2 ";
                $setInfo2 = pdo_fetch($sql.$getWhere);
                $moreformid = $set['storemoreformid'];
            }
            //处理基本信息
            $setInfo2['pic'] = unserialize($setInfo2['pic']);
            if(empty($setInfo2['pic'])){
                $setInfo2['pic'] = [];
            }
            $setInfo2['cardnum'] = $setInfo2['cardnum'] ? : '';
            $setInfo2['att_id']  = $setInfo2['id'] ? : -1;
            $setInfo = array_merge($setInfo1,$setInfo2);
            $setInfo['name'] = !empty($setInfo['subjectname'])? $setInfo['subjectname'] : $setInfo['storename'];
            $setInfo['phone'] = !empty($setInfo['atttel'])? $setInfo['atttel'] : $setInfo['tel'];
            unset($setInfo['subjectname']);
            unset($setInfo['storename']);
            unset($setInfo['tel']);
            unset($setInfo['atttel']);
            //处理图片信息
            if(is_array($setInfo['pic'])){
                foreach($setInfo['pic'] as &$val){
                    $val = tomedia($val);
                }
            }
            //获取设置信息
            $setInfo['agreement'] = htmlspecialchars_decode($set['agreement']);
            //额外内容
            if($moreformid > 0){
                $diyFromInfo       = pdo_getcolumn(PDO_NAME . 'diyform' , ['id' => $moreformid] , 'info');
                $setInfo['diyform']   = json_decode(base64_decode($diyFromInfo) , true);//页面的配置信息
                $setInfo['diyformid'] = $moreformid;
                if(!empty($setInfo['moreinfo'])){
                    $moreinfo = unserialize($setInfo['moreinfo']);
                    foreach($setInfo['diyform']['list'] as $key => &$ccinfo){
                        foreach($moreinfo as $mminfo){
                            if($ccinfo['data']['title'] == $mminfo['title']){
                                $ccinfo['data']['value'] = $mminfo['data'];
                            }
                        }
                    }
                }
            }
            $this->renderSuccess('获取认证信息',$setInfo);
        }
    }
    /**
     * Comment: 获取保证金设置信息
     * Author: zzw
     * Date: 2019/11/7 11:58
     */
    public function attMoney(){
        global $_W,$_GPC;
        #1、参数获取
        $type = $_GPC['type'] ? : 1;//1个人认证 2商户认证
        $id = $_GPC['sid'] ? : $_W['mid'];//id（商户id|用户id）
        #2、判断是否缴纳保证金
        $where['uniacid'] = $_W['uniacid'];
        if($type == 1){
            $where['mid'] = $id;
            $where['type'] = 1;
        } else{
            $where['storeid'] = $id;
        }
        $isMoney = pdo_getcolumn(PDO_NAME."attestation_money",$where,'status');
        $data['is_money'] = intval($isMoney > 0 ? $isMoney : 0);
        #3、获取设置信息
        if(is_array($_W['wlsetting']['attestation']) && count($_W['wlsetting']['attestation']) > 0) $set = $_W['wlsetting']['attestation'];
            else $set = Setting::wlsetting_read('attestation');
        #2、获取保证金内容
        if($type == 1){
            if(Customized::init('distributionText') > 0){
                $membermoney = unserialize($set['membermoneyarray']);
                $data['money'] = min($membermoney).'-'.max($membermoney);
                $data['moneyarray'] = $membermoney;
            }else{
                $data['money'] = $set['membermoney'] ? : 0;
            }
        }else{
            if(Customized::init('distributionText') > 0){
                $storemoney = unserialize($set['storemoneyarray']);
                $data['money'] = min($storemoney).'-'.max($storemoney);
                $data['moneyarray'] = $storemoney;
            }else{
                $data['money'] = $set['storemoney'] ? : 0;
            }
        }
        #2、获取基本设置信息
        $data['bonddescription'] = htmlspecialchars_decode($set['bonddescription']);
        $data['bondagreement']   = htmlspecialchars_decode($set['bondagreement']);

        $this->renderSuccess('保证金设置信息',$data);
    }


    /**
     * Comment: 保证金缴纳订单接口
     * Author: wlf
     * Date: 2019/11/6 17:56
     */
    public function bondOrder(){
        global $_W,$_GPC;
        $set = Setting::wlsetting_read('attestation');
        $type = $_GPC['type'];
        $storeid = $_GPC['storeid'];
        $money = $_GPC['money'];
        if($type == 1){
            if(Customized::init('distributionText') > 0) {
                $membermoney = unserialize($set['membermoneyarray']);
                if(!in_array($money,$membermoney)){
                    $money = 0;
                }
            }else{
                $money = $set['membermoney'];
            }
        }else if($type == 2){
            if(Customized::init('distributionText') > 0) {
                $storemoney = unserialize($set['storemoneyarray']);
                if(!in_array($money,$storemoney)){
                    $money = 0;
                }
            }else{
                $money = $set['storemoney'];
            }
        }
        if($money < 0.01){
            $this -> renderError('保证金金额错误，请联系管理员');
        }
        $orderinfo = array(
            'uniacid'  => $_W['uniacid'],
            'mid'      => $_W['mid'],
            'storeid'  => $storeid,
            'type'     => $type,
            'money'    => $money,
            'orderno'  => createUniontid(),
            'createtime' => time()
        );
        pdo_insert(PDO_NAME .'attestation_money', $orderinfo);
        $orderid = pdo_insertid();
        $data['orderid'] = $orderid;
        $this -> renderSuccess('订单id',$data);
    }

}