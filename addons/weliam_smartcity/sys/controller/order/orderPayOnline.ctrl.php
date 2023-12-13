<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 在线买单信息
 * Author: zzw
 * Date: 2021/1/7 16:41
 * Class OrderPayOnline_WeliamController
 */
class OrderPayOnline_WeliamController {
    //在线买单信息列表
    public function payonlinelist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $store_where = is_agent() ? array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']) : array('uniacid' => $_W['uniacid']);
        $stores = pdo_getall('wlmerchant_merchantdata', $store_where, array('id', 'storename'));

        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['plugin'] = 'halfcard';
        $where['status>'] = 1;
        $where['status!='] = 5;
        if(is_agent()){
            $where['aid'] = $_W['aid'];
        }
        if(is_store()){
            $where['sid'] = $_W['storeid'];
        }else{
            $sid = intval($_GPC['sid']);
            if($sid){
                $where['sid'] = $sid;
            }
        }
        if($_GPC['orderid'] > 0){
            $where['id'] = $_GPC['orderid'];
        }
        if($_GPC['paytype']){
            $where['paytype'] = $_GPC['paytype'];
        }

        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }
            }else if($_GPC['keywordtype'] == 2){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }
            }
        }

        if($_GPC['time_limit'] && $_GPC['timetype'] > 0){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['paytime>'] = $starttime;
            $where['paytime<'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        //导出
        if($_GPC['export']){
            $this -> exportpayonline($where);
        }

        $payonlinelist = Util::getNumData('*','wlmerchant_order',$where,'paytime DESC',$pindex,$psize,1);
        $pager = $payonlinelist[1];
        $list = $payonlinelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','nickname'));
            $li['avatar'] = tomedia($member['avatar']);
            $li['nickname'] = $member['nickname'];
            $store = pdo_get(PDO_NAME.'merchantdata',array('uniacid'=>$_W['uniacid'],'id'=>$li['sid']),array('logo','storename'));
            $li['logo'] = tomedia($store['logo']);
            if($li['fkid']){
                $li['title'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('uniacid'=>$_W['uniacid'],'id'=>$li['fkid']),'title');
            }else{
                $li['title'] = $store['storename'].'买家在线买单';
            }
            $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
        }

        include  wl_template('order/payonlinelist');
    }
    //导出买单信息列表
    public function exportpayonline($where){
        global $_W, $_GPC;
        $payonlinelist = Util::getNumData('*','wlmerchant_order',$where,'paytime DESC',0,0,1);
        $pager = $payonlinelist[1];
        $list = $payonlinelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('mobile','nickname'));
            $li['mobile'] = $member['mobile'];
            $li['nickname'] = $member['nickname'];
            $store = pdo_get(PDO_NAME.'merchantdata',array('uniacid'=>$_W['uniacid'],'id'=>$li['sid']),array('logo','storename'));
            if($li['fkid']){
                $li['title'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('uniacid'=>$_W['uniacid'],'id'=>$li['fkid']),'title');
            }else{
                $li['title'] = $store['storename'].'买家在线买单';
            }
            $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
            $li['orderno'] = "\t".$li['orderno']."\t";
        }

        /* 输出表头 */
        $filter = array(
            'orderno' => '订单号',
            'title' => '所属商家',
            'nickname' => '买家昵称',
            'mobile' => '买家电话',
            'goodsprice' => '订单金额',
            'oprice' => '不可优惠金额',
            'spec' => '优惠折扣',
            'card_fee' => '已优惠金额',
            'price' => '实付金额',
            'paytype' => '支付方式',
            'paytime' => '支付时间'
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                if($key == 'paytype'){
                    switch ($list[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '余额支付';
                            break;
                        case '2':
                            $data[$i][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$i][$key] = '支付宝支付';
                            break;
                        case '4':
                            $data[$i][$key] = '货到付款';
                            break;
                        default:
                            $data[$i][$key]  = '其他或未支付';
                            break;
                    }
                }else if($key == 'spec'){
                    if($list[$i][$key] < 10){
                        $data[$i][$key] = $list[$i][$key].'折';
                    }else{
                        $data[$i][$key] = '无折扣';
                    }
                } else {
                    $data[$i][$key] = $list[$i][$key];
                }

            }
        }
        util_csv::export_csv_2($data,$filter,'在线买单记录表.csv');
        exit();

    }


}