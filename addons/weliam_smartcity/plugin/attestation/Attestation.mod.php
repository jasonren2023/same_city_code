<?php
defined('IN_IA') or exit('Access Denied');

class Attestation {

    /**
     * Comment: 保证金缴纳回调
     * Author: wlf
     * Date: 2019/11/6 18:19
     */
    static function payBondNotify($params){
        global $_W;
        Util::wl_log('notify', PATH_DATA . "attestation/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'attestation_money') . "where orderno='{$params['tid']}'");
        $_W['uniacid'] = $order_out['uniacid'];
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        pdo_update(PDO_NAME . 'attestation_money', $data, array('orderno' => $params['tid'])); //更新订单状态
        //通知管理员
        if($order_out['type'] == 1){
            $type = '个人';
            $member = pdo_getcolumn(PDO_NAME.'member',array('id'=>$order_out['mid']),'nickname');
            $content = '认证人:['.$member.']';
        }else{
            $type = '商户';
            $storename = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order_out['storeid']),'storename');
            $content = '商户:['.$storename.']';
        }
        $modelData = [
            'first'   => '您好，有一笔'.$type.'认证金已缴纳。',
            'type'    => '认证金缴纳' ,//业务类型
            'content' => $content,//业务内容
            'status'  => '已缴纳' ,//处理结果
            'time'    => date("Y-m-d H:i:s",time()) ,//操作时间$store['createtime']
            'remark'  => '请尽快前往系统后台审核认证!'
        ];
        TempModel::sendInit('service',-1,$modelData,$_W['source']);
    }

    /**
     * Comment: 验证用户/商户认证情况
     * Author: wlf
     * Date: 2019/11/7 15:16
     */
    static function checkAttestation($type,$id){
        global $_W;
        $set = Setting::wlsetting_read('attestation');
        if($set['switch'] > 0){
            if($type == 1){  //个人认证
                $attestation = pdo_getall(PDO_NAME.'attestation_list',array('uniacid'=>$_W['uniacid'],'mid'=>$id,'status'=>1,'checkstatus'=>2,'type'=>1),array('id'),'','ID DESC');
                $data['bondflag'] = pdo_getcolumn(PDO_NAME.'attestation_money',array('uniacid'=>$_W['uniacid'],'mid'=>$id,'status'=>1,'type'=>1),'id');
                $data['attestation'] = $attestation[0]['id']?1:0;
            }else{
                $attestation = pdo_getall(PDO_NAME.'attestation_list',array('uniacid'=>$_W['uniacid'],'storeid'=>$id,'status'=>1,'type'=>2),array('checkstatus'),'','ID DESC');
                $data['bondflag'] = pdo_getcolumn(PDO_NAME.'attestation_money',array('uniacid'=>$_W['uniacid'],'storeid'=>$id,'status'=>1,'type'=>2),'id');
                $data['attestation'] = $attestation[0]['checkstatus']?$attestation[0]['checkstatus']:0;
            }
            $data['bondflag'] = $data['bondflag']?1:0;
        }else{
            $data = [];
        }

        return $data;
    }


}