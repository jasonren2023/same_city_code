<?php
defined('IN_IA') or exit('Access Denied');

class SalesmanModuleUniapp extends Uniapp {
    /**
     * Comment: 我的商家列表
     * Author: hexin
     * Date: 2019/8/14 23:51
     */
    public function myStore() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $stores = pdo_fetchall("SELECT a.alone,a.alone_plugin,a.sales_plugin,a.scale,b.id,a.createtime,a.aid,b.storename,b.logo,b.realname,b.tel,b.endtime FROM "
            . tablename(PDO_NAME . "merchantuser")
            . " as a Left JOIN "
            . tablename(PDO_NAME . "merchantdata")
            . " as b ON a.storeid = b.id WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} AND a.ismain = 4 AND a.enabled = 1 ORDER BY b.id DESC Limit " . ($pindex - 1) * 10 . ',' . 10);
        foreach ($stores as &$store) {
            $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$store['aid'],'key' => 'salesman'),array('value'));
            $setting = unserialize($setting['value']);
            $store['logo'] = tomedia($store['logo']);
            $store['endtime'] = date('Y-m-d', $store['endtime']);
            if($store['alone'] == 1){
                $store['scale'] = $store['scale'];
            }else{
                $store['scale'] = $setting['scale'];
            }
            //权限
            if($store['alone_plugin']){
                $sales_plugin = unserialize($store['sales_plugin']);
            }else{
                $sales_plugin = unserialize($setting['plugin']);
            }
            //统计
            if(in_array('rush',$sales_plugin) || empty($sales_plugin)){
                $rush_order_money = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$store['id']} AND settletime > {$store['createtime']} ");
                $rush_order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$store['id']} AND settletime > {$store['createtime']}   ");
            }else{
                $rush_order_money = 0;
                $rush_order_num = 0;
            }
            if(!empty($sales_plugin)){
                $whereplugin = "(";
                foreach ($sales_plugin as $key => $v) {
                    if($v == 'payonline'){
                        $v = 'halfcard';
                    }
                    if($v == 'fightgroup'){
                        $v = 'wlfightgroup';
                    }
                    if ($key == 0) {
                        $whereplugin .= "'".$v."'";
                    } else {
                        $whereplugin .= ",'" . $v."'";
                    }
                }
                $whereplugin .= ")";
                $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$store['id']} AND settletime > {$store['createtime']} AND plugin IN {$whereplugin}");
                $order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$store['id']} AND  settletime > {$store['createtime']} AND plugin IN {$whereplugin}");
            }else{
                $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$store['id']} AND settletime > {$store['createtime']} AND plugin IN ('groupon','coupon','wlfightgroup','bargain') ");
                $order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$store['id']} AND  settletime > {$store['createtime']} AND plugin IN ('groupon','coupon','wlfightgroup','bargain')");
            }
            $store['ordermoney'] = sprintf("%.2f",$rush_order_money + $order_money);
            $store['ordernum'] = $rush_order_num + $order_num;

            if(empty($store['ordermoney'])){
                $store['ordermoney'] = 0;
            }
            if(empty($store['ordernum'])){
                $store['ordernum'] = 0;
            }
        }
        $data['stores'] = $stores;
        //计算分页
        $total =  pdo_fetchcolumn("SELECT count(b.id) FROM "
            . tablename(PDO_NAME . "merchantuser")
            . " as a Left JOIN "
            . tablename(PDO_NAME . "merchantdata")
            . " as b ON a.storeid = b.id WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} AND a.ismain = 4 AND a.enabled = 1");
        $data['pagetotal'] = ceil($total/10);
        if($_GPC['initflag']){
            $data['settle'] = $setting['settle'];
            //累计交易额
            $allmoney = 0;
            $sealstore = pdo_getall('wlmerchant_merchantuser',array('mid' => $_W['mid'],'ismain' => 4, 'enabled' => 1),array('alone_plugin','sales_plugin','storeid','alone','scale','createtime'));
            //今日/本周/本月预估收益 与 昨日/上周/上月收益
            $estimatemoney = 0;
            $start = strtotime(date('Y-m-d'));
            $last = $start - 86400;
//            else if($data['settle'] == 1){
//                $start = strtotime(date('Y-m-d', strtotime("this week Monday", time())));
//                $last = $start - 86400 * 7;
//            }else if($data['settle'] == 2){
//                $start = mktime(0, 0, 0, date('m'), 1, date('Y'));
//                $last = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
//            }
            foreach ($sealstore as $seal){
                $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$store['aid'],'key' => 'salesman'),array('value'));
                $setting = unserialize($setting['value']);
                if($store['alone_plugin']){
                    $sales_plugin = unserialize($store['sales_plugin']);
                }else{
                    $sales_plugin = unserialize($setting['plugin']);
                }
                if(in_array('rush',$sales_plugin) || empty($sales_plugin)) {
                    $rush_order_money = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$seal['storeid']} AND settletime > {$seal['createtime']} ");
                    $rushordermoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$seal['storeid']} AND settletime > {$start}");
                }else{
                    $rush_order_money = 0;
                    $rushordermoney = 0;
                }
                if(!empty($sales_plugin)){
                    $whereplugin = "(";
                    foreach ($sales_plugin as $key => $v) {
                        if($v == 'payonline'){
                            $v = 'halfcard';
                        }
                        if($v == 'fightgroup'){
                            $v = 'wlfightgroup';
                        }
                        if ($key == 0) {
                            $whereplugin .= "'".$v."'";
                        } else {
                            $whereplugin .= ",'" . $v."'";
                        }
                    }
                    $whereplugin .= ")";
                    $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$seal['storeid']} AND  settletime > {$seal['createtime']} AND plugin IN {$whereplugin} ");
                    $ordermoney = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$seal['storeid']} AND settletime > {$start} AND plugin IN {$whereplugin}");
                }else{
                    $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$seal['storeid']} AND  settletime > {$seal['createtime']} AND plugin IN ('groupon','coupon','wlfightgroup','bargain') ");
                    $ordermoney = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$seal['storeid']} AND settletime > {$start} AND plugin IN ('groupon','coupon','wlfightgroup','bargain') ");
                }
                $allmoney += $rush_order_money + $order_money ;
                $scale = $seal['alone']?$seal['scale']:$setting['scale'];
                $nowmoney = sprintf("%.2f",($rushordermoney+$ordermoney)*$scale/100);
                $estimatemoney += $nowmoney;
            }
            $data['allmoney'] = sprintf("%.2f",$allmoney);
            $lastmoney = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."disdetail")." WHERE leadid = {$_W['mid']} AND status = 1  AND createtime < {$start} AND createtime > {$last} ");
            $data['estimatemoney'] = sprintf("%.2f",$estimatemoney);
            $data['lastmoney'] =  sprintf("%.2f",$lastmoney);
            //累计收益
            $profit = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."disdetail")." WHERE leadid = {$_W['mid']} AND status = 1");
            $data['profitmoney'] = sprintf("%.2f",$profit);
        }
        $data['settle'] = 0;
        $this->renderSuccess('我的商家列表', $data);
    }
    /**
     * Comment: 商家详情
     * Author: hexin
     * Date: 2019/8/15 00:22
     */
    public function storeDetail() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $store = pdo_fetch("SELECT a.alone,a.scale,a.alone_plugin,a.sales_plugin,a.aid,b.id,b.storename,a.createtime,b.logo,b.endtime FROM "
            . tablename(PDO_NAME . "merchantuser")
            . " as a Left JOIN "
            . tablename(PDO_NAME . "merchantdata")
            . " as b ON a.storeid = b.id WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} AND b.id = {$id} AND ismain = 4");

        $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$store['aid'],'key' => 'salesman'),array('value'));
        $setting = unserialize($setting['value']);
        //权限
        if($store['alone_plugin']){
            $sales_plugin = unserialize($store['sales_plugin']);
        }else{
            $sales_plugin = unserialize($setting['plugin']);
        }

        $store['logo'] = tomedia($store['logo']);
        $store['endtime'] = date('Y-m-d', $store['endtime']);
        $store['scale'] = $store['alone'] == 1 ? $store['scale'] : $_W['wlsetting']['salesman']['scale'];
        if($store['alone'] != 1){
            $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$store['aid'],'key' => 'salesman'),array('value'));
            $setting = unserialize($setting['value']);
            $store['scale'] = $setting['scale'];
        }

        if(in_array('rush',$sales_plugin) || empty($sales_plugin)) {
            $rush_order_money = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$id} AND settletime > {$store['createtime']} ");
            $rush_order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."rush_order")." WHERE sid = {$id} AND settletime > {$store['createtime']}   ");
        }else{
            $rush_order_money = 0;
            $rush_order_num = 0;
        }

        if(!empty($sales_plugin)){
            $whereplugin = "(";
            foreach ($sales_plugin as $key => $v) {
                if($v == 'payonline'){
                    $v = 'halfcard';
                }
                if($v == 'fightgroup'){
                    $v = 'wlfightgroup';
                }
                if ($key == 0) {
                    $whereplugin .= "'".$v."'";
                } else {
                    $whereplugin .= ",'" . $v."'";
                }
            }
            $whereplugin .= ")";
            $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$id} AND settletime > {$store['createtime']} AND plugin IN {$whereplugin} ");
            $order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$id} AND  settletime > {$store['createtime']} AND plugin IN {$whereplugin}");

        }else{
            $order_money = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$id}  settletime > {$store['createtime']} AND plugin IN ('groupon','coupon','wlfightgroup','bargain') ");
            $order_num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME."order")." WHERE sid = {$id}  settletime > {$store['createtime']} AND plugin IN ('groupon','coupon','wlfightgroup','bargain')");
        }
        $store['turnover'] = sprintf("%.2f",$rush_order_money + $order_money);
        $store['ordernum'] = $rush_order_num + $order_num;
        if(empty($store['turnover'])){
            $store['turnover'] = 0;
        }
        if(empty($store['ordernum'])){
            $store['ordernum'] = 0;
        }
        $this->renderSuccess('商家详情', $store);
    }
    /**
     * Comment: 商家每日报表
     * Author: hexin
     * Date: 2019/8/15 00:22
     */
    public function storeReport() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $pindex = max(1, intval($_GPC['page']));
        $report = pdo_fetchall("SELECT status,money,num,createtime FROM ".tablename('wlmerchant_report')."WHERE storeid = {$id} ORDER BY id DESC LIMIT " .($pindex - 1) * 20 .','. 20 );
        //计算分页
        $total =  pdo_fetchcolumn("SELECT count(id) FROM ".tablename('wlmerchant_report')."WHERE storeid = {$id}");
        $data['pagetotal'] = ceil($total/20);
        $data['reportlist'] = $report;
        $this->renderSuccess('商家每日报表', $data);
    }
    /**
     * Comment: 商家结算记录
     * Author: hexin
     * Date: 2019/8/15 00:22
     */
    public function storeSettle() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $pindex = max(1, intval($_GPC['page']));
        $disdetail = pdo_fetchall("SELECT price,createtime,plugin FROM ".tablename('wlmerchant_disdetail')."WHERE leadid = {$_W['mid']} AND status = 1 AND buymid = {$id} ORDER BY id DESC LIMIT " .($pindex - 1) * 20 .','. 20 );
        foreach ($disdetail as &$dis){
            switch ($dis['plugin']) {
                case 'rush':
                    $dis['pluginname'] = '抢购订单';
                    break;
                case 'groupon':
                    $dis['pluginname'] = '团购订单';
                    break;
                case 'fightgroup':
                    $dis['pluginname'] = '拼团订单';
                    break;
                case 'coupon':
                    $dis['pluginname'] = '卡券订单';
                    break;
                case 'bargain':
                    $dis['pluginname'] = '砍价活动';
                    break;
                case 'payonline':
                    $dis['pluginname'] = '在线买单';
                    break;
                case 'citydelivery':
                    $dis['pluginname'] = '同城配送';
                    break;
                default:
                    $dis['pluginname'] = '未知插件';
                    break;
            }
            $dis['createtime'] = date('Y-m-d H:i:s',$dis['createtime']);
        }
        $data['dislist'] = $disdetail;
        //计算分页
        $total =  pdo_fetchcolumn("SELECT count(id) FROM ".tablename('wlmerchant_disdetail')."WHERE leadid = {$_W['mid']} AND status = 1 AND buymid = {$id}");
        $data['pagetotal'] = ceil($total/20);

        $this->renderSuccess('商家结算记录', $data);
    }

    /**
     * Comment: 获取业务员商户入驻二维码
     * Author: wlf
     * Date: 2020/04/21 15:56
     */
    public function getSaleQr(){
        global $_W, $_GPC;
        $source = $_W['source'];
        //使用默认二维码
        $path = 'pages/mainPages/Settled/Settled?sale_id=' . $_W['mid'].'&head_id='.$_W['mid'];//基本路径，也是小程序路径
        if ($source != 3){
            $path = h5_url($path);
        } //非小程序渠道  基本路径转超链接
        #3、二维码生成
        $filename = md5('sale_id'.$_W['mid'].'source' .$source.'path'.$path);
        if ($source == 3) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = Poster::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = Poster::qrcodeimg($path , $filename);
        }
        $qrCodeLink = tomedia($qrCodeLink);
        $this->renderSuccess('推广二维码',array('imgurl'=>$qrCodeLink));
    }

}