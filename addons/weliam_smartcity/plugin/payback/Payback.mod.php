<?php
defined('IN_IA') or exit('Access Denied');

class Payback {
    /**
     * Comment: 支付返现操作
     * Author: wlf
     * Date: 2020/06/04 15:52
     * @param number    $sid     商户id
     * @param number    $mid     买家id
     * @param number    $doid     操作员id
     * @param string    $plugin 模块信息
     * @param number    $price  结算金额
     * @param number    $orderno  订单编号
     * @return array
     */
    static function payCore($sid,$mid,$doid,$plugin,$price,$orderno,$orderid,$uniacid,$aid,$checkcode=0,$storeremark = ''){
        global $_W;
        $pluginarray = ['rush','wlfightgroup','coupon','groupon','bargain','halfcard','sys','store'];
        if(in_array($plugin,$pluginarray)){
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('paybackstatus','payback','storename'));
            if($store['paybackstatus'] > 0 || $plugin == 'sys'){
                $payback = unserialize($store['payback']);
                if($plugin == 'sys'){   //银行卡返现
                    $storeremark = self::transformation($storeremark);
                    $backmoney = $price;
                    $remark = '['.$storeremark.']银行卡支付返现';
                    $remark2 = $remark;
                }else if($plugin == 'store'){  //店员修改
                    $backmoney = $price;
                    $remark = '店员操作修改用户余额:'.$storeremark;
                    $remark2 = '商户['.$store['storename'].']店员修改余额:'.$storeremark;
                }else if($payback['online'] > 0 && $plugin == 'halfcard'){
                    $backmoney = sprintf("%.2f",$price * $payback['online'] / 100);
                    $remark = '在线买单'.$price.'元返现';
                    $remark2 = '商户['.$store['storename'].']在线买单返现';
                }else if($payback['goods'] > 0){
                    $backmoney = sprintf("%.2f",$price * $payback['goods'] / 100);
                    $remark2 = '商户['.$store['storename'].']在线买单返现';
                    switch ($plugin) {
                        case 'rush':
                            $order = pdo_get('wlmerchant_rush_order',array('id' => $orderid),array('activityid'));
                            $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('name'));
                            $remark = '购买抢购商品['.$goods['name'].']返现';
                            break;//抢购商品
                        case 'groupon':
                            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('fkid'));
                            $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('name'));
                            $remark = '购买团购商品['.$goods['name'].']返现';
                            break;//团购商品
                        case 'wlfightgroup':
                            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('fkid'));
                            $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('name'));
                            $remark = '购买拼团商品['.$goods['name'].']返现';
                            break;//拼团商品
                        case 'coupon':
                            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('fkid'));
                            $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('title'));
                            $remark = '购买卡券['.$goods['title'].']返现';
                            break;//优惠券
                        case 'bargain':
                            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('fkid'));
                            $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('name'));
                            $remark = '购买砍价商品['.$goods['name'].']返现';
                            break;//砍价商品
                    }
                }
                if(abs($backmoney) > 0){
                    //写记录
                    $data = [
                        'uniacid' => $uniacid,
                        'sid'     => $sid,
                        'doid'    => $doid,
                        'mid'     => $mid,
                        'plugin'  => $plugin,
                        'backmoney' => $backmoney,
                        'orderno'  => $orderno,
                        'remark'  => $remark,
                        'checkcode' => $checkcode,
                        'createtime' => time()
                    ];
                    MysqlFunction::setTrans(4);
                    MysqlFunction::startTrans();
                    $res = pdo_insert(PDO_NAME . 'payback_record', $data);
                    $backid = pdo_insertid();
                    if($res){
                        //扣除商户余额
                        if($sid > 0){
                            $nowmoney = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid),'nowmoney');
                            if($nowmoney > $backmoney){
                                $res2 = pdo_update('wlmerchant_merchantdata', array('nowmoney -=' => $backmoney), array('id' => $sid));
                            }else{
                                $data['errortitle'] = '商户现有余额不足';
                                Util::wl_log('paybackerror' , PATH_DATA . "rush/data/" ,$data); //写入异步日志记录
                                MysqlFunction::rollback();
                                return false;
                            }
                        }else{
                            $res2 = 1;
                        }
                        if($res2){
                            if($sid > 0){
                                $merchantnowmoney = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid),'nowmoney');
                                Store::addcurrent(1,15,$sid,-$backmoney,$merchantnowmoney,$backid,$checkcode,$uniacid,$aid);
                            }
                            $res3 = Member::credit_update_credit2($mid,$backmoney,$remark2);
                            if(!is_error($res3)) {
                                MysqlFunction::commit();
                                return true;
                            }else{
                                $data['errortitle'] = '修改用户余额失败';
                                Util::wl_log('paybackerror' , PATH_DATA . "rush/data/" ,$res3); //写入异步日志记录
                                MysqlFunction::rollback();
                                return false;
                            }
                        }else{
                            $data['errortitle'] = '修改商户金额失败';
                            Util::wl_log('paybackerror' , PATH_DATA . "rush/data/" ,$data); //写入异步日志记录
                            MysqlFunction::rollback();
                            return false;
                        }
                    }else{
                        $data['errortitle'] = '写入返现记录失败';
                        Util::wl_log('paybackerror' , PATH_DATA . "rush/data/" ,$data); //写入异步日志记录
                        MysqlFunction::rollback();
                        return false;
                    }
                }
            }
        }
        return false;
    }

    static function transformation($banknum){
        $bankTelmList = [];
        $bankTelmList[] = ['name' => '工商银行（借记卡）','bank' => 'ICBC_DEBIT'];
        $bankTelmList[] = ['name' => '工商银行（信用卡）','bank' => 'ICBC_CREDIT'];
        $bankTelmList[] = ['name' => '农业银行（借记卡）','bank' => 'ABC_DEBIT'];
        $bankTelmList[] = ['name' => '农业银行（信用卡）','bank' => 'ABC_CREDIT'];
        $bankTelmList[] = ['name' => '邮储银行（信用卡）','bank' => 'PSBC_CREDIT'];
        $bankTelmList[] = ['name' => '邮储银行（借记卡）','bank' => 'PSBC_DEBIT'];
        $bankTelmList[] = ['name' => '建设银行（借记卡）','bank' => 'CCB_DEBIT'];
        $bankTelmList[] = ['name' => '建设银行（信用卡）','bank' => 'CCB_CREDIT'];
        $bankTelmList[] = ['name' => '招商银行（借记卡）','bank' => 'CMB_DEBIT'];
        $bankTelmList[] = ['name' => '招商银行（信用卡）','bank' => 'CMB_CREDIT'];
        $bankTelmList[] = ['name' => '中国银行（借记卡）','bank' => 'BOC_DEBIT'];
        $bankTelmList[] = ['name' => '中国银行（信用卡）','bank' => 'BOC_CREDIT'];
        $bankTelmList[] = ['name' => '交通银行（借记卡）','bank' => 'COMM_DEBIT'];
        $bankTelmList[] = ['name' => '交通银行（信用卡）','bank' => 'COMM_CREDIT'];
        $bankTelmList[] = ['name' => '浦发银行（借记卡）','bank' => 'SPDB_DEBIT'];
        $bankTelmList[] = ['name' => '浦发银行（信用卡）','bank' => 'SPDB_CREDIT'];
        $bankTelmList[] = ['name' => '广发银行（借记卡）','bank' => 'GDB_DEBIT'];
        $bankTelmList[] = ['name' => '广发银行（信用卡）','bank' => 'GDB_CREDIT'];
        $bankTelmList[] = ['name' => '民生银行（借记卡）','bank' => 'CMBC_DEBIT'];
        $bankTelmList[] = ['name' => '民生银行（信用卡）','bank' => 'CMBC_CREDIT'];
        $bankTelmList[] = ['name' => '平安银行（借记卡）','bank' => 'PAB_DEBIT'];
        $bankTelmList[] = ['name' => '平安银行（信用卡）','bank' => 'PAB_CREDIT'];
        $bankTelmList[] = ['name' => '光大银行（借记卡）','bank' => 'CEB_DEBIT'];
        $bankTelmList[] = ['name' => '光大银行（信用卡）','bank' => 'CEB_CREDIT'];
        $bankTelmList[] = ['name' => '兴业银行（借记卡）','bank' => 'CIB_DEBIT'];
        $bankTelmList[] = ['name' => '兴业银行（信用卡）','bank' => 'CIB_CREDIT'];
        $bankTelmList[] = ['name' => '中信银行（借记卡）','bank' => 'CITIC_DEBIT'];
        $bankTelmList[] = ['name' => '中信银行（信用卡）','bank' => 'CITIC_CREDIT'];
        $bankTelmList[] = ['name' => '四川农信（借记卡）','bank' => 'SCNX_DEBIT'];
        $bankTelmList[] = ['name' => '四川天府银行（借记卡）','bank' => 'NCCB_DEBIT'];
        foreach ($bankTelmList as $bankTelm){
            if($bankTelm['bank'] == $banknum){
                $result = $bankTelm['name'];
            }
        }
        return $result;
    }


}