<?php
defined('IN_IA') or exit('Access Denied');

class PftapimodModuleUniapp extends Uniapp {

    /**
     * Comment: 票付通订单预提交
     * Author: wlf
     * Date: 2021/08/05 17:39
     */

    public function pftOrderPreCheck(){
        global $_GPC, $_W;
        //校验身份信息
        $cardidlist = trim($_GPC['cardidlist']);
        if(!empty($cardidlist)){
            $checkPer = Pftapimod::checkPersonID($cardidlist);
            if($checkPer['error'] > 0){
                $this->renderError($checkPer['msg']);
            }
        }
        //获取门票信息
        $ticketinfo = Pftapimod::getTicketDetail($_GPC['pftid'],$_GPC['pftuid']);
        $UUaid = $ticketinfo[0]['UUaid'];
        $UUpid = $ticketinfo[0]['UUpid'];
        //获取日历价格库存信息
        $realTimeData = [
            'aid' => $UUaid,
            'pid' => $UUpid,
            'start_date' => $_GPC['playtime'],
            'end_date' => date('Y-m-d',strtotime($_GPC['playtime'])+86400*30)
        ];
        $realTimeInfo = Pftapimod::GetRealTimeStorage($realTimeData);
        $tprice = $realTimeInfo['buy_price'];
        //订单预提交
        $data = [
            'tid' => $_GPC['pftuid'],
            'tnum'  => $_GPC['num'],
            'playtime'  => $_GPC['playtime'] ? : date('Y-m-d', time()),
            'ordertel'  => $_GPC['tel'],
            'ordername' => trim($_GPC['namelist']),
            'm' => $UUaid,
            'paymode' => 0,
            'personid' => $cardidlist,
            'tprice' => $tprice
        ];
        $orderCheck = Pftapimod::getOrderPreCheck($data);
        if($orderCheck['UUdone'] == 100){
            $data['lid'] = $ticketinfo[0]['UUlid'];
            $data['tid'] = $ticketinfo[0]['UUid'];
            $data['aid'] = $ticketinfo[0]['UUaid'];

            $data['contactTEL'] = trim($_GPC['contactTEL']);

            $this->renderSuccess('没问题',$data);
        }else{
            Util::wl_log('PftApi.log',PATH_DATA,$orderCheck); //写入异步日志记录
            $this->renderError($orderCheck['UUerrorinfo']);
        }
    }

}