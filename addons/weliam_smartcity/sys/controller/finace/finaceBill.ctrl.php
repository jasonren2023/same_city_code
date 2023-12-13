<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 账单明细
 * Author: zzw
 * Date: 2021/1/7 17:43
 * Class newCash_WeliamController
 */
class FinaceBill_WeliamController {
    //账单明细
    public function cashrecord() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = is_agent() ? array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']) : array('uniacid' => $_W['uniacid']);
        if(is_store()){
            $where['merchantid'] = $_W['storeid'];
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch($_GPC['keywordtype']) {
                    case 1 :
                        $where['@orderno@'] = $_GPC['keyword'];
                        break;
                    case 2 :
                        $where['orderprice>'] = $_GPC['keyword'];
                        break;
                    case 3 :
                        $where['orderprice<'] = $_GPC['keyword'];
                        break;
                    case 4 :
                        $where['agentmoney>'] = $_GPC['keyword'];
                        break;
                    case 5 :
                        $where['agentmoney<'] = $_GPC['keyword'];
                        break;
                    default :
                        break;
                }
                if ($_GPC['keywordtype'] == 6) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $stores = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND storename LIKE :storename", $params);
                    if ($stores) {
                        $storesids = "(";
                        foreach ($stores as $key => $v) {
                            if ($key == 0) {
                                $storesids .= $v['id'];
                            } else {
                                $storesids .= "," . $v['id'];
                            }
                        }
                        $storesids .= ")";
                        $where['merchantid#'] = $storesids;
                    } else {
                        $where['merchantid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 7) {
                    $keyword = $_GPC['keyword'];
                    if($keyword == '总后台'){
                        $where['aid'] = 0;
                    }else{
                        $params[':agentname'] = "%{$keyword}%";
                        $agents = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_agentusers') . "WHERE uniacid = {$_W['uniacid']} AND agentname LIKE :agentname", $params);
                        if ($agents) {
                            $agentids = "(";
                            foreach ($agents as $key => $v) {
                                if ($key == 0) {
                                    $agentids .= $v['id'];
                                } else {
                                    $agentids .= "," . $v['id'];
                                }
                            }
                            $agentids .= ")";
                            $where['aid#'] = $agentids;
                        } else {
                            $where['aid'] = "-1";
                        }
                    }
                }
            }
        }
        $cashtype = trim($_GPC['cashtype']);
        if (!empty($cashtype)) {
            $where['type'] = $cashtype;
        }
        $where['type>'] = 1;
        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time()+86400;
        }

        if ($_GPC['export'] != '') {
            $this -> export($where);
        }
        $records = Util::getNumData('*', 'wlmerchant_autosettlement_record', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $records[1];
        $records = $records[0];
        foreach ($records as $key => &$va) {
            $va['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $va['merchantid']), 'storename');
            if($va['aid'] > 0){
                $va['agentuser'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $va['aid']), 'agentname');
            }else{
                $va['agentuser'] = '总后台';
            }
            if (empty($va['orderno'])) {
                $va['orderno'] = $va['orderid'];
            }
            if ($va['type'] == 1) {
                $va['typename'] = '抢购';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $va['goodsid']), 'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 1, 'orderid' => $va['orderid']));
            }else if($va['type'] == 10) {
                $va['typename'] = '团购';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $va['goodsid']), 'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 10, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 2) {
                $va['typename'] = '拼团';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $va['goodsid']), 'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 2, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 3) {
                $va['typename'] = '卡券';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $va['goodsid']), 'title');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 3, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 4) {
                $va['typename'] = '一卡通';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'halfcard_type', array('id' => $va['goodsid']), 'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 4, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 5) {
                $va['typename'] = '掌上信息';
                $va['title'] = '付费发帖';
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 5, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 6) {
                $va['typename'] = '付费入驻';
                $va['title'] = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $va['goodsid']), 'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 6, 'orderid' => $va['orderid']));
            } else if ($va['type'] == 7) {
                if($va['merchantid']){
                    if ($va['merchantmoney'] < 0) {
                        $va['typename'] = '提现申请';
                        $va['title'] = '商户申请提现';
                    } else {
                        $va['typename'] = '提现申请驳回';
                        $va['title'] = '商户申请被驳回';
                    }
                }else{
                    if ($va['agentmoney'] < 0) {
                        $va['typename'] = '提现申请';
                        $va['title'] = '代理申请提现';
                    } else {
                        $va['typename'] = '提现申请驳回';
                        $va['title'] = '代理申请被驳回';
                    }
                }
                if(is_agent()){
                    $va['orderurl'] = web_url('finace/finaceWithdrawal/cashApplyAgentRecord',array('orderid' => $va['orderid']));
                }else{
                    $va['orderurl'] = web_url('finace/finaceWithdrawalApply/cashApply',array('orderid' => $va['orderid']));
                }
            }else if ($va['type'] == 8) {
                if(Customized::init('distributionText') > 0){
                    $va['typename'] = '共享股东';
                    $va['title'] = '付费成为共享股东';
                }else{
                    $va['typename'] = '分销合伙人';
                    $va['title'] = '付费成为分销商';
                }
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 8, 'orderid' => $va['orderid']));
            }else if ($va['type'] == 9) {
                $va['typename'] = '商户活动';
                $va['title'] = pdo_getcolumn(PDO_NAME.'activitylist',array('id' => $va['goodsid']),'title');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 9, 'orderid' => $va['orderid']));
            }else if ($va['type'] == 11) {
                $va['typename'] = '在线买单';
                $va['title'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('id' => $va['goodsid']),'title');
                $va['title'] = $va['title']?$va['title']:'买家在线买单';
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 11, 'orderid' => $va['orderid']));
            }else if ($va['type'] == 12) {
                $va['typename'] = '砍价商品';
                $va['title'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id' => $va['goodsid']),'name');
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 12, 'orderid' => $va['orderid']));
            }else if ($va['type'] == 13) {
                $va['typename'] = '同城名片';
                $fightstatus = pdo_getcolumn(PDO_NAME.'order',array('id' => $va['orderid']),'fightstatus');
                if($fightstatus == 1){
                    $va['title'] = pdo_getcolumn(PDO_NAME.'citycard_meals',array('id' => $va['goodsid']),'name');
                }else{
                    $va['title'] = pdo_getcolumn(PDO_NAME.'citycard_tops',array('id' => $va['goodsid']),'name');
                }
                $va['orderurl'] = web_url('citycard/citycard/order_lists', array('keywordtype' => 3, 'keyword' => $va['orderno']));
            }else if ($va['type'] == 14) {
                $va['typename'] = '同城配送';
                $smallorder = pdo_getall('wlmerchant_delivery_order',array('tid'=>$va['orderno']),array('gid','num','specid'));
                $va['title'] = '';
                foreach ($smallorder  as $ke => &$orr){
                    $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name'));
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goods['name'] .= '/'.$specname;
                    }
                    $va['title'] .= '['.$goods['name'].'X'.$orr['num'].']';
                }
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 14, 'orderid' => $va['orderid']));
            }else if($va['type'] == 15){
                $va['typename'] = '黄页114';
                $order = pdo_get('wlmerchant_order',array('id' => $va['orderid']),array('fightstatus'));
                $yellow = pdo_get('wlmerchant_yellowpage_lists' , ['id' => $va['goodsid']],['name']);
                switch ($order['fightstatus']) {
                    case '1':
                        $va['title'] = '页面['.$yellow['name'].']认领';
                        break;
                    case '2':
                        $va['title'] = '页面['.$yellow['name'].']查阅';
                        break;
                    case '3':
                        $va['title'] = '页面['.$yellow['name'].']入驻';
                        break;
                    case '4':
                        $va['title'] = '页面['.$yellow['name'].']续费';
                        break;
                }
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 15, 'orderid' => $va['orderid']));
            }else if($va['type'] == 16){
                $va['typename'] = '求职招聘';
                $order = pdo_get('wlmerchant_order',array('id' => $va['orderid']),array('fightstatus'));
                switch ($order['fightstatus']) {
                    case '1':
                        $va['title'] = '招聘信息发布';
                        break;
                    case '2':
                        $va['title'] = '招聘信息置顶';
                        break;
                }
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 16, 'orderid' => $va['orderid']));
            }
            else if($va['type'] == 18){
                $va['typename'] = '房产信息';
                $order = pdo_get('wlmerchant_order',array('id' => $va['orderid']),array('fightstatus'));
                switch ($order['fightstatus']) {
                    case '1':
                        $va['title'] = '新房发布';
                        break;
                    case '2':
                        $va['title'] = '二手房发布';
                        break;
                    case '3':
                        $va['title'] = '出租房发布';
                        break;
                }
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 18, 'orderid' => $va['orderid']));
            }else if($va['type'] == 19){
                $va['typename'] = '酒店预约';
                $va['title'] = pdo_getcolumn(PDO_NAME.'hotel_room',array('id' => $va['goodsid']),'name');;
                $va['orderurl'] = web_url('order/wlOrder/orderdetail', array('type' => 17, 'orderid' => $va['orderid']));
            }
        }
        include  wl_template('finace/cashrecord');
    }
    //导出账单明细
    public function export($where) {

        if (empty($where)){
            return FALSE;
        }
        set_time_limit(0);
        $records = Util::getNumData('*', 'wlmerchant_autosettlement_record', $where, 'ID DESC', 0, 0, 1);
        $records = $records[0];
        foreach ($records as $key => &$rec) {
            $rec['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $rec['merchantid']), 'storename');
            if($rec['aid'] > 0){
                $rec['agentname'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $rec['aid']), 'agentname');
            }else{
                $rec['agentname'] = '总后台';
            }
            if (empty($rec['orderno'])) {
                $rec['orderno'] = $rec['orderid'];
            }
            if ($rec['type'] == 1) {
                $rec['typename'] = '抢购';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $rec['goodsid']), 'name');
            } else if ($rec['type'] == 2) {
                $rec['typename'] = '拼团';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $rec['goodsid']), 'name');
            } else if ($rec['type'] == 3) {
                $rec['typename'] = '卡券';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $rec['goodsid']), 'title');
            } else if ($rec['type'] == 4) {
                $rec['typename'] = '一卡通';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'halfcard_type', array('id' => $rec['goodsid']), 'name');
            } else if ($rec['type'] == 5) {
                $rec['typename'] = '掌上信息';
                $rec['title'] = '付费发帖';
            } else if ($rec['type'] == 6) {
                $rec['typename'] = '付费入驻';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $rec['goodsid']), 'name');
            } else if ($rec['type'] == 7) {
                if($rec['merchantid']){
                    if ($rec['merchantmoney'] < 0) {
                        $rec['typename'] = '提现申请';
                        $rec['title'] = '商户申请提现';
                    } else {
                        $rec['typename'] = '提现申请驳回';
                        $rec['title'] = '商户申请被驳回';
                    }
                }else{
                    if ($rec['agentmoney'] < 0) {
                        $rec['typename'] = '提现申请';
                        $rec['title'] = '代理申请提现';
                    } else {
                        $rec['typename'] = '提现申请驳回';
                        $rec['title'] = '代理申请被驳回';
                    }
                }
            }else if ($rec['type'] == 8) {
                if(Customized::init('distributionText') > 0){
                    $rec['typename'] = '共享股东';
                    $rec['title'] = '付费成为共享股东';
                }else{
                    $rec['typename'] = '分销合伙人';
                    $rec['title'] = '付费成为分销商';
                }
            }else if ($rec['type'] == 9) {
                $rec['typename'] = '商户活动';
                $rec['title'] = pdo_getcolumn(PDO_NAME.'activitylist',array('id' => $rec['goodsid']),'title');
            }else if($rec['type'] == 10) {
                $rec['typename'] = '团购';
                $rec['title'] = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $rec['goodsid']), 'name');
            } else if ($rec['type'] == 11) {
                $rec['typename'] = '在线买单';
                $rec['title'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('id' => $rec['goodsid']),'title');
                $rec['title'] = $rec['title']?$rec['title']:'买家在线买单';
            }else if ($rec['type'] == 12) {
                $rec['typename'] = '砍价商品';
                $rec['title'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id' => $rec['goodsid']),'name');
            }else if ($rec['type'] == 13) {
                $rec['typename'] = '同城名片';
                $fightstatus = pdo_getcolumn(PDO_NAME.'order',array('id' => $rec['orderid']),'fightstatus');
                if($fightstatus == 1){
                    $rec['title'] = pdo_getcolumn(PDO_NAME.'citycard_meals',array('id' => $rec['goodsid']),'name');
                }else{
                    $rec['title'] = pdo_getcolumn(PDO_NAME.'citycard_tops',array('id' => $rec['goodsid']),'name');
                }
            }else if ($rec['type'] == 14) {
                $rec['typename'] = '同城配送';
                $smallorder = pdo_getall('wlmerchant_delivery_order',array('tid'=>$rec['orderno']),array('gid','specid','num'));
                $rec['title'] = '';
                foreach ($smallorder  as $ke => &$orr){
                    $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name'));
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goods['name'] .= '/'.$specname;
                    }
                    $rec['title'] .= '['.$goods['name'].'X'.$orr['num'].']';
                }
            }else if($rec['type'] == 18){
                $rec['typename'] = '房产信息';
                $order = pdo_get('wlmerchant_order',array('id' => $rec['orderid']),array('fightstatus'));
                switch ($order['fightstatus']) {
                    case '1':
                        $rec['title'] = '新房发布';
                        break;
                    case '2':
                        $rec['title'] = '二手房发布';
                        break;
                    case '3':
                        $rec['title'] = '出租房发布';
                        break;
                }
            }
            $rec['createtime'] = date('Y-m-d H:i:s', $rec['createtime']);
            $rec['title'] = str_replace(",","，",$rec['title']);
            //核销员
            if(!empty($rec['checkcode'])){
                $hexuid = pdo_get('wlmerchant_smallorder',array('checkcode' => $rec['checkcode']),['hxuid','hexiaotype']);
                $hxmember = pdo_get('wlmerchant_merchantuser',array('id' => $hexuid['hxuid']),array('name','mid'));
                $rec['checkmid'] = $hxmember['mid'];
                $rec['checkuser'] = $hxmember['name'];
                if($hexuid['hexiaotype'] == 1){
                    $rec['hexiaotype'] = '输码核销';
                }else if($hexuid['hexiaotype'] == 2){
                    $rec['hexiaotype'] = '扫码核销';
                }else if($hexuid['hexiaotype'] == 3){
                    $rec['hexiaotype'] = '后台核销';
                }else if($hexuid['hexiaotype'] == 4){
                    $rec['hexiaotype'] = '密码核销';
                }

            }
        }

        /* 输入到CSV文件 */
        $html = "\xEF\xBB\xBF";
        /* 输出表头 */
        $filter['orderno']       = '订单号';
        $filter['title']         = '商品名称';
        $filter['agentname']     = '代理名称';
        $filter['storename']     = '所属商家';
        $filter['typename']      = '结算类型';
        $filter['orderprice']    = '订单金额';
        $filter['merchantmoney'] = '商户收入';
        if(Customized::init('distributionText') > 0){
            $filter['distrimoney'] = '共享股东佣金';
        }else{
            $filter['distrimoney'] = '分销佣金';
        }
        if(p('salesman')) $filter['salesmoney'] = '业务员佣金';
        $filter['agentmoney']    = '代理收入';
        $filter['agentnowmoney'] = '结算后代理余额';
        $filter['createtime']    = '结算时间';
        $filter['checkcode']    = '核销码';
        $filter['hexiaotype']    = '核销方式';
        $filter['checkmid']     = '核销员mid';
        $filter['checkuser']    = '核销员姓名';


        foreach ($filter as $key => $title) {
            $html .= $title . "\t,";
        }
        $html .= "\n";

        foreach ($records as $k => $v) {
            foreach ($filter as $key => $title) {
                if ($key == 'orderprice' || $key == 'merchantmoney' || $key == 'agentmoney' || $key == 'agentnowmoney') {
                    $html .= trim($v[$key]) . trim("\t") . ",";
                } else {
                    $html .= $v[$key] . "\t, ";
                }
            }
            $html .= "\n";
        }
        /* 输出CSV文件 */
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=结算记录.csv");
        echo $html;
        exit();
    }


}
