<?php
defined('IN_IA') or exit('Access Denied');

class Salesman {

    static function saleCore($sid,$plugin){
        global $_W;
        $salemans = pdo_getall('wlmerchant_merchantuser',array('ismain' => 4,'storeid'=> $sid,'enabled'=>1),array('aid','id','mid','alone','scale','alone_plugin','sales_plugin'));
        $salearray = [];
        if(!empty($salemans)){
            foreach ($salemans as $sale){
                $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$sale['aid'],'key' => 'salesman'),array('value'));
                $setting = unserialize($setting['value']);
                if($setting['isopen']){
                    $saleinfo = [];
                    if($sale['alone_plugin']){
                        $piugins = unserialize($sale['sales_plugin']);
                    }else{
                        $piugins = unserialize($setting['plugin']);
                    }
                    if(in_array($plugin,$piugins) || empty($piugins)){
                        if($sale['alone']){
                            $saleinfo['scale'] = $sale['scale'];
                        }else{
                            $saleinfo['scale'] = $setting['scale'];
                        }
                        $saleinfo['mid'] = $sale['mid'];
                        if($saleinfo['scale']>0){
                            $salearray[] = $saleinfo;
                        }
                    }
                }
            }
        }

        if(!empty($salearray)){$salearray = serialize($salearray);}
        return $salearray;
    }

    static function doTask(){
        global $_W;
        //结算佣金(废弃)
//        $h = date(G,time());
//        $w = date(w,time());
//        $d = date(d,time());
//        $start = strtotime(date('Y-m-d'));
//        $last = $start - 86400;
//        if($h > 2 && $h < 9) {  //每天凌晨两点生成任务记录
//            $stores = pdo_fetchall('select distinct storeid,uniacid from ' . tablename(PDO_NAME . 'merchantuser') . " WHERE ismain = 4 AND enabled = 1");
//            if ($stores) {
//                foreach ($stores as $store) {
//                    $flag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_report') . "WHERE storeid = {$store['storeid']}  AND createtime > {$start} ");
//                    if (empty($flag)) {
//                        $data = array(
//                            'uniacid' => $store['uniacid'],
//                            'storeid' => $store['storeid'],
//                            'createtime' => time(),
//                            'status' => 0
//                        );
//                    }
//                    pdo_insert(PDO_NAME . 'report', $data);
//                }
//            }
//        }
//
//        //开始填充报表
//        $reports = pdo_fetchall('select * from ' . tablename(PDO_NAME . 'report') . " WHERE status = 0 LIMIT 3");
//        if($reports){
//            foreach ($reports as $report){
//                $rush_order_money = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0  ");
//                $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0 AND plugin != 'store' ");
//                $rush_order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0  ");
//                $order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0 AND plugin != 'store' ");
//                $money = sprintf("%.2f",$rush_order_money + $order_money);
//                $num = $rush_order_num + $order_num;
//                $_W['uniacid'] = $report['uniacid'];
//                $setting = Setting::wlsetting_read('salesman');
//                $upg = array(
//                    'money'    => $money,
//                    'num'      => $num,
//                    'status'   => 1,
//                    'setttype' => $setting['settle']
//                );
//                $res = pdo_update('wlmerchant_report',$upg,array('id' => $report['id']));
//                if($res){
//                    pdo_fetchall("update" . tablename('wlmerchant_rush_order') . "SET reportid = {$report['id']}  WHERE  sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0");
//                    pdo_fetchall("update" . tablename('wlmerchant_order') . "SET reportid = {$report['id']}  WHERE sid = {$report['storeid']} AND settletime < {$start} AND settletime > {$last} AND reportid = 0 AND plugin != 'store'");
//                }
//            }
//        }
//
//        //开始结算报表
//        if($h > 2 && $h < 9){
//            $settreports1 = pdo_fetchall('select * from ' . tablename(PDO_NAME . 'report') . " WHERE status = 1 AND setttype = 0 AND createtime < {$start} LIMIT 3");
//            if($settreports1){
//                foreach ($settreports1 as $sett){
//                    self::salesettle($sett);
//                }
//            }
//        }
//        if($h > 2 && $h < 9 && $w == 1){
//            $settreports2 = pdo_fetchall('select * from ' . tablename(PDO_NAME . 'report') . " WHERE status = 1 AND setttype = 1 AND createtime < {$start} LIMIT 3");
//            if($settreports2){
//                foreach ($settreports2 as $sett2){
//                    self::salesettle($sett2);
//                }
//            }
//        }
//        if($h > 2 && $h < 9 && $d == 01){
//            $settreports3 = pdo_fetchall('select * from ' . tablename(PDO_NAME . 'report') . " WHERE status = 1 AND setttype = 2 AND createtime < {$start} LIMIT 3");
//            if($settreports3){
//                foreach ($settreports3 as $sett3){
//                    self::salesettle($sett3);
//                }
//            }
//        }
    }

    static function salesettle($report){
        global $_W;
        $_W['uniacid'] = $report['uniacid'];
        $setting = Setting::wlsetting_read('salesman');
        $sales = pdo_getall('wlmerchant_merchantuser',array('storeid' => $report['storeid'],'ismain'=>4),array('mid','alone','scale'));
        foreach ($sales as $sale){
            if($sale['alone']){
                $scale = $sale['scale'];
            }else{
                $scale = $setting['scale'];
            }
            $reportmoney = sprintf("%.2f",$report['money'] * $scale/100);
            if($reportmoney > 0){
                pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$reportmoney},nowmoney=nowmoney+{$reportmoney} WHERE mid = {$sale['mid']}");
                $onenowmoney = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=> $sale['mid']),'nowmoney');
                Distribution::adddisdetail($report['id'],$sale['mid'],$report['storeid'],1,$reportmoney,'salesman',1,'业务员佣金结算',$onenowmoney,$report['id']);
            }
        }
        pdo_update('wlmerchant_report',array('status'=>2),array('id' => $report['id']));
    }


}