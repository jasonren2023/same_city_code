<?php
defined('IN_IA') or exit('Access Denied');
set_time_limit(0);

class Database_WeliamController
{

    public function __construct()
    {
        global $_W;
        if (!$_W['isfounder']) {
            wl_message('无权访问!');
        }
    }

    public function settlement_error()
    {
        global $_W, $_GPC;
        $allmoney = array(
            'allagentmoney' => 0,
            'alldismoney'   => 0,
            'allstoremoney' => 0,
        );
        $commentSql = "select checkcode,merchantid,type,orderno,goodsid,goodsid,agentmoney,createtime,merchantmoney,distrimoney,aid,count(id) as count from" . tablename(PDO_NAME . "autosettlement_record") . "group by checkcode having count > 1 AND checkcode > 0 ";
        $lists = pdo_fetchall($commentSql);
        if (!empty($lists)) {
            foreach ($lists as $key => &$va) {
                $va['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $va['merchantid']), 'storename');
                $va['agentname'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $va['aid']), 'agentname');
                $va['count'] = $va['count'] - 1;  //重复核销次数
                $va['allstoremoney'] = $va['merchantmoney'] * $va['count']; //总的结算给商户的错误金额
                $va['alldismoney'] = $va['distrimoney'] * $va['count'];//总的结算给分销商的错误金额
                $va['allagentmoney'] = $va['agentmoney'] * $va['count'];//总的结算给代理的错误金额
                $va['nowstoremoney'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $va['merchantid']), 'nowmoney');  //目前商户的金额
                $va['nowagentmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $va['aid']), 'nowmoney');   //目前代理的金额
                if ($va['alldismoney'] > 0) {
                    $checkcode = pdo_get('wlmerchant_smallorder', array('checkcode' => $va['checkcode']), array('plugin', 'oneleadid', 'twoleadid', 'onedismoney', 'twodismoney'));
                    if ($checkcode['plugin'] == 'coupon') {
                        $dissettime = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $va['goodsid']), 'dissettime');
                    } else if ($checkcode['plugin'] == 'wlfightgroup') {
                        $dissettime = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $va['goodsid']), 'dissettime');
                    } else if ($checkcode['plugin'] == 'groupon') {
                        $dissettime = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $va['goodsid']), 'dissettime');
                    } else if ($checkcode['plugin'] == 'rush') {
                        $dissettime = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $va['goodsid']), 'dissettime');
                    } else if ($checkcode['plugin'] == 'bargain') {
                        $dissettime = pdo_getcolumn(PDO_NAME . 'bargain_activity', array('id' => $va['goodsid']), 'dissettime');
                    }
                    $va['oneleadid'] = $checkcode['oneleadid'];
                    $va['twoleadid'] = $checkcode['twoleadid'];
                    $va['onedisname'] = pdo_getcolumn(PDO_NAME . 'member', array('distributorid' => $checkcode['oneleadid']), 'nickname');
                    if ($checkcode['twoleadid'] > 0) {
                        $va['twodisname'] = pdo_getcolumn(PDO_NAME . 'member', array('distributorid' => $checkcode['twoleadid']), 'nickname');
                    } else {
                        $va['twodisname'] = '无二级分销商';
                    }
                    if ($dissettime) {
                        $va['alldismoney'] = 0;
                        $va['oneerrormoney'] = $va['twoerrormoney'] = 0;
                        $va['onenowmoney'] = $va['twonowmoney'] = 0;
                        $va['tip'] = '佣金支付时结算，无错误';
                    } else {
                        $va['oneerrormoney'] = $checkcode['onedismoney'] * $va['count'];
                        $va['twoerrormoney'] = $checkcode['twodismoney'] * $va['count'];
                        $va['onenowmoney'] = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $checkcode['oneleadid']), 'nowmoney');
                        $va['twonowmoney'] = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $checkcode['twoleadid']), 'nowmoney');
                    }
                } else {
                    $va['alldismoney'] = 0;
                    $va['oneleadid'] = $va['twoleadid'] = 0;
                    $va['onedisname'] = $va['twodisname'] = '无';
                    $va['oneerrormoney'] = $va['twoerrormoney'] = 0;
                    $va['onenowmoney'] = $va['twonowmoney'] = 0;
                }
                $allmoney['allstoremoney'] += $va['allstoremoney'];
                $allmoney['alldismoney'] += $va['alldismoney'];
                $allmoney['allagentmoney'] += $va['allagentmoney'];
            }
        }
        include wl_template('cloud/settlement_error');
    }

    public function repair_settlement_error()
    {
        global $_W, $_GPC;
        $checkcode = $_GPC['checkcode'];
        $commentSql = "select id,checkcode,merchantid,type,orderno,goodsid,goodsid,agentmoney,createtime,merchantmoney,distrimoney,aid,count(id) as count from" . tablename(PDO_NAME . "autosettlement_record") . "WHERE checkcode = '{$checkcode}'";
        $record = pdo_fetch($commentSql);
        $record['count'] = $record['count'] - 1;  //重复核销次数

        //修复商户金额
        $record['allstoremoney'] = $record['merchantmoney'] * $record['count']; //总的结算给商户的错误金额
        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney-{$record['allstoremoney']},nowmoney=nowmoney-{$record['allstoremoney']} WHERE id = {$record['merchantid']}");
        $firstsid = pdo_getcolumn(PDO_NAME . 'current', array('remark' => $checkcode, 'status' => 1, 'objid' => $record['merchantid']), 'id');
        pdo_delete(PDO_NAME . 'current', array('id >' => $firstsid, 'remark' => $checkcode, 'status' => 1, 'objid' => $record['merchantid']));


        //修复代理金额
        $record['allagentmoney'] = $record['agentmoney'] * $record['count'];//总的结算给代理的错误金额
        pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney-{$record['allagentmoney']},nowmoney=nowmoney-{$record['allagentmoney']} WHERE id = {$record['aid']}");
        $firstaid = pdo_getcolumn(PDO_NAME . 'current', array('remark' => $checkcode, 'status' => 2, 'objid' => $record['aid']), 'id');
        pdo_delete(PDO_NAME . 'current', array('id >' => $firstaid, 'remark' => $checkcode, 'status' => 2, 'objid' => $record['aid']));


        //修复分销金额
        $record['alldismoney'] = $record['distrimoney'] * $record['count'];//总的结算给分销商的错误金额
        if ($record['alldismoney'] > 0) {
            $smallorder = pdo_get('wlmerchant_smallorder', array('checkcode' => $record['checkcode']), array('plugin', 'oneleadid', 'twoleadid', 'onedismoney', 'twodismoney'));
            if ($smallorder['plugin'] == 'coupon') {
                $dissettime = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $record['goodsid']), 'dissettime');
            } else if ($smallorder['plugin'] == 'wlfightgroup') {
                $dissettime = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $record['goodsid']), 'dissettime');
            } else if ($smallorder['plugin'] == 'groupon') {
                $dissettime = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $record['goodsid']), 'dissettime');
            } else if ($smallorder['plugin'] == 'rush') {
                $dissettime = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $record['goodsid']), 'dissettime');
            } else if ($smallorder['plugin'] == 'bargain') {
                $dissettime = pdo_getcolumn(PDO_NAME . 'bargain_activity', array('id' => $record['goodsid']), 'dissettime');
            }
            if (empty($dissettime)) {
                $oneerrormoney = $smallorder['onedismoney'] * $record['count'];
                pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney-{$oneerrormoney},nowmoney=nowmoney-{$oneerrormoney} WHERE id = {$smallorder['oneleadid']}");
                $onemid = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $smallorder['oneleadid']), 'mid');
                $firstoneid = pdo_getcolumn(PDO_NAME . 'disdetail', array('checkcode' => $checkcode, 'leadid' => $onemid), 'id');
                pdo_delete(PDO_NAME . 'disdetail', array('id >' => $firstoneid, 'checkcode' => $checkcode, 'leadid' => $onemid));
                if ($smallorder['twoleadid']) {
                    $twoerrormoney = $smallorder['twodismoney'] * $record['count'];
                    pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney-{$twoerrormoney},nowmoney=nowmoney-{$twoerrormoney} WHERE id = {$smallorder['twoleadid']}");
                    $twomid = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $smallorder['twoleadid']), 'mid');
                    $firsttwoid = pdo_getcolumn(PDO_NAME . 'disdetail', array('checkcode' => $checkcode, 'leadid' => $twomid), 'id');
                    pdo_delete(PDO_NAME . 'disdetail', array('id >' => $firsttwoid, 'checkcode' => $checkcode, 'leadid' => $twomid));
                }
            }
        }
        pdo_delete(PDO_NAME . 'autosettlement_record', array('id >' => $record['id'], 'checkcode' => $checkcode));

        show_json(1, array('url' => referer()));
    }

    public function updateOpenid()
    {
        global $_W, $_GPC;
        $account_api = WeAccount::create();
        $token = $account_api->getAccessToken();
        $url = 'http://api.weixin.qq.com/cgi-bin/changeopenid?access_token=' . $token;
        $res = $fail = 0;
        for ($page = 0; $page < 10; $page++) {
            $sql = "select openid from ims_wlmerchant_member where uniacid = 1 AND openid LIKE '%oR9%' AND changeflag = 0 ORDER BY `id` DESC limit 0,100;";
            $openidlist = pdo_fetchall($sql);
            foreach ($openidlist as $row) {
                $openid[] = $row['openid'];
            }
            if (!empty($openid)) {
                $data = json_encode(array('from_appid' => 'wxdef947e212d31c82', 'openid_list' => $openid));
                $resarray = ihttp_post($url, $data);
                $resarray = json_decode($resarray['content'], true);
                $resarray = $resarray['result_list'];
                foreach ($resarray as $item) {
                    if ($item['err_msg'] == 'ok') {
                        pdo_update('wlmerchant_member', array('openid' => $item['new_openid']), array('openid' => $item['ori_openid']));
                        pdo_update('mc_mapping_fans', array('openid' => $item['new_openid']), array('openid' => $item['ori_openid']));
                        $res++;
                    } else {
                        file_put_contents(PATH_DATA . "error_openid.log", var_export($item, true) . PHP_EOL, FILE_APPEND);
                        pdo_update('wlmerchant_member', array('changeflag' => 1), array('openid' => $item['ori_openid']));
                        $fail++;
                    }
                }
            }
        }
        echo('成功' . $res . '失败' . $fail);
    }

    function sendSelfFormatOrderInfo($device_no, $key, $times = 1, $orderInfo)
    { // $times打印次数
        $selfMessage = array(
            'deviceNo'     => $device_no,
            'printContent' => $orderInfo,
            'key'          => $key,
            'times'        => $times
        );
        $url = "http://open.printcenter.cn:8080/addOrder";
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded ",
                'method'  => 'POST',
                'content' => http_build_query($selfMessage),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }


    function test($arr,$A,$B,$C,$D)
    {
        $orderInfo = '<CB>飞鹅云测试</CB><BR>';
        $orderInfo .= '名称           单价  数量 金额<BR>';
        $orderInfo .= '--------------------------------<BR>';
        foreach ($arr as $k5 => $v5) {
            $name = $v5['title'];
            $price = $v5['price'];
            $num = $v5['num'];
            $prices = $v5['price']*$v5['num'];
            $kw3 = '';
            $kw1 = '';
            $kw2 = '';
            $kw4 = '';
            $str = $name;
            $blankNum = $A;//名称控制为14个字节
            $lan = mb_strlen($str,'utf-8');
            $m = 0;
            $j=1;
            $blankNum++;
            $result = array();
            if(strlen($price) < $B){
                $k1 = $B - strlen($price);
                for($q=0;$q<$k1;$q++){
                    $kw1 .= ' ';
                }
                $price = $price.$kw1;
            }
            if(strlen($num) < $C){
                $k2 = $C - strlen($num);
                for($q=0;$q<$k2;$q++){
                    $kw2 .= ' ';
                }
                $num = $num.$kw2;
            }
            if(strlen($prices) < $D){
                $k3 = $D - strlen($prices);
                for($q=0;$q<$k3;$q++){
                    $kw4 .= ' ';
                }
                $prices = $prices.$kw4;
            }
            for ($i=0;$i<$lan;$i++){
                $new = mb_substr($str,$m,$j,'utf-8');
                $j++;
                if(mb_strwidth($new,'utf-8')<$blankNum) {
                    if($m+$j>$lan) {
                        $m = $m+$j;
                        $tail = $new;
                        $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                        $k = $A - strlen($lenght);
                        for($q=0;$q<$k;$q++){
                            $kw3 .= ' ';
                        }
                        if($m==$j){
                            $tail .= $kw3.' '.$price.' '.$num.' '.$prices;
                        }else{
                            $tail .= $kw3.'<BR>';
                        }
                        break;
                    }else{
                        $next_new = mb_substr($str,$m,$j,'utf-8');
                        if(mb_strwidth($next_new,'utf-8')<$blankNum) continue;
                        else{
                            $m = $i+1;
                            $result[] = $new;
                            $j=1;
                        }
                    }
                }
            }
            $head = '';
            foreach ($result as $key=>$value) {
                if($key < 1){
                    $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                    $v_lenght = strlen($v_lenght);
                    if($v_lenght == 13) $value = $value." ";
                    $head .= $value.' '.$price.' '.$num.' '.$prices;
                }else{
                    $head .= $value.'<BR>';
                }
            }
            $orderInfo .= $head.$tail;
            @$nums += $prices;
        }
        $time = date('Y-m-d H:i:s',time());
        $orderInfo .= '--------------------------------<BR>';
        $orderInfo .= '合计：'.number_format($nums, 1).'元<BR>';
        $orderInfo .= '送货地点：广州市南沙区xx路xx号<BR>';
        $orderInfo .= '联系电话：020-39004606<BR>';
        $orderInfo .= '订餐时间：'.$time.'<BR>';
        $orderInfo .= '备注：加辣<BR><BR>';
        $orderInfo .= '<QR>http://www.feieyun.com</QR>';//把解析后的二维码生成的字符串用标签套上即可自动生成二维码
        return $orderInfo;
    }



    public function run()
    {
        global $_W, $_GPC;

        //获取分组信息
        //$sign = md5($time.$key);
        //$url = "http://open.yiqida.cn/api/UserCommdity/GetCatalogList?timestamp={$time}&userName={$admin}&sign={$sign}";
        //$yqdInfo = curlPostRequest($url,[]);

        //获取分组商品列表
//        $data = [
//            'page' => 1,
//            'size' => 60,
//            'catalogId' => 10625
//        ];
//        $data = json_encode($data);
//        $sign = md5($time.$data.$key);
//        $url = "http://open.yiqida.cn/api/UserCommdity/GetCommodityList?timestamp={$time}&userName={$admin}&sign={$sign}";
//        $yqdInfo = curlPostRequest($url,$data);

        //获取商品信息
//        $data = [
//          'id' => 30181
//        ];
//        $data = json_encode($data);
//        $sign = md5($time.$data.$key);
//        $url = "http://open.yiqida.cn/api/UserCommdity/GetCommodityInfo?timestamp={$time}&userName={$admin}&sign={$sign}";
//        $yqdInfo = curlPostRequest($url,$data);

        //订单提交
//        $template = [urlencode('13658059596')];
//        $callurl = $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/pftapimod/yqdAsyNotify.php';
//        $orderno = 'wl_'.'2022030310300900013918966342';
//        $data = [
//            'commodityId' => 30230,
//            'external_orderno' => $orderno,
//            'buyCount' => 1,
//            'remark' => 'okok',
//            'callbackUrl' => $callurl,
//            'externalSellPrice' => 3,
//            'template' => $template
//        ];
//
//        $data = json_encode($data);
//        $sign = md5($time.$data.$key);
//        $url = "http://open.yiqida.cn/api/UserOrder/CreateOrder?timestamp={$time}&userName={$admin}&sign={$sign}";
        //$yqdInfo = curlPostRequest($url,$data);
        //wl_debug($yqdInfo);



        //飞鹅测试
//        $keyword = '株洲县';
//        $city_name = '株洲市';
//        $res = MapService::guide_search($keyword , "region(" . urlencode($city_name) . ",0)");
//        wl_debug($res);
//        $url = 'http://api.feieyun.cn/Api/Open/';
//        $user = '937991452@qq.com';
//        $ukey = '4k9xB8eE6QDvV3b3';
//        $SN = '917508610';
//        $time = time();
//        $sig = sha1($user.$ukey.$time);
//
//        $arr[] = [
//            'title' => '苹果',
//            'price' => 10,
//            'num' => 5
//        ];
//
//        $arr[] = [
//            'title' => '梨子',
//            'price' => 5,
//            'num' => 3
//        ];
//
//        $arr[] = [
//            'title' => '橘子',
//            'price' => 6,
//            'num' => 4
//        ];
//        $content = $this->test($arr,14,6,3,6);//名称14 单价6 数量3 金额6-->这里的字节数可按自己需求自由改写，14+6+3+6再加上代码写的3个空格就是32了，58mm打印机一行总占32字节
//
//        $data = [
//            'user' =>  $user,
//            'stime' => $time,
//            'sig'  => $sig,
//            'apiname' => 'Open_printMsg',
//            'debug' => 1,
//            'sn' => $SN,
//            'content' => $content
//        ];
//
//
//        $dadaInfo = curlPostRequest($url,$data);
//        wl_debug($dadaInfo);




//        $res = Pftapimod::getTicketDetail(298611,1200053);
//            wl_debug($res);
        //36鲸接口测试
//        $jnumber = '30000451';
//        $appkey = 'OWFtzFJjCtt0kIf_kqusxpX6tylsN4ON';
//        $admin = 'http://81.69.6.74:9099';
//
//        //下单
//        $sloworderurl = '/v1/mobile/sloworder';
//        $posturl = $admin.$sloworderurl;
//
//        $data = [
//            'appKey' => $jnumber,
//            'orderId' => '2021110114372400013969664264',
//            'mobile' => '13658059596',
//            'amount' => 5.12,
//            'notifyUrl' => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/pftapimod/pftAsyNotify.php'
//        ];
//
//        //签名
//        ksort($data);
//        $arr = [];
//        foreach ($data as $key => $value) {
//            $arr[] = $key.'='.$value;
//        }
//
//        $arr[] = 'key='.$appkey;
//        $str = implode('&', $arr);
//        $str = strtoupper(md5($str));
//        $data['sign'] = $str;
//
//        $Info = curlPostRequest($posturl,$data);
//        wl_debug($Info);



//        $orders = pdo_getall('wlmerchant_smallorder',array('status' => 2,'dissettletime'=> 0,'disorderid >'=>0,'settletime >' => 1634608805));
//        wl_debug($orders);
//        foreach ($orders as $order){
//            if($order['disorderid']){
//                $disorder = pdo_get('wlmerchant_disorder',array('id' => $order['disorderid']));
//                $nosetflag = pdo_getcolumn('wlmerchant_disdetail',array('checkcode' => $order['checkcode'],'status'=>0),'id');
//                if(empty($nosetflag)){
//                    if($order['onedismoney'] > 0){
//                        $oneres = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['onedismoney']},nowmoney=nowmoney+{$order['onedismoney']} WHERE id = {$order['oneleadid']}");
//                        if($oneres){
//                            $leadid = pdo_getcolumn('wlmerchant_distributor',array('id'=> $order['oneleadid']),'mid');
//                            $onenowmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $order['oneleadid']),'nowmoney');
//                            Distribution::adddisdetail($order['disorderid'],$leadid,$disorder['buymid'],1,$order['onedismoney'],$disorder['plugin'],1,'分销订单结算',$onenowmoney,$order['checkcode'],0,$order['aid']);
//                        }
//                    }
//                    if($order['twodismoney'] > 0){
//                        $twores = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['twodismoney']},nowmoney=nowmoney+{$order['twodismoney']} WHERE id = {$order['twoleadid']}");
//                        if($twores){
//                            $leadid = pdo_getcolumn('wlmerchant_distributor',array('id'=> $order['twoleadid']),'mid');
//                            $twonowmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $order['twoleadid']),'nowmoney');
//                            Distribution::adddisdetail($order['disorderid'],$leadid,$disorder['buymid'],1,$order['twodismoney'],$disorder['plugin'],2,'分销订单结算',$twonowmoney,$order['checkcode'],0,$order['aid']);
//                        }
//                    }
//                    $sdata['dissettletime'] = time();
//                }
//            }
//            pdo_update('wlmerchant_smallorder',$sdata,array('id' => $order['id']));
//        }

//        $merchantNo = '94734160742A3RD';
//        $terminalNo = '72002165';
//        $key = '3B3B79F217DD9705DEE9EF3C496B6134';
//        //签到接口调试
//        //退款
//        $url = 'https://wxtest2.ahrcu.com:3443/cposp/pay/refund';
//        $data['merchantNo'] = $merchantNo;
//        $data['terminalNo'] = $terminalNo;
//        $yun_signIn = Payment::yunSignIn($merchantNo,$terminalNo);
//        $data['batchNo'] = $yun_signIn['batchNo'];
//        $data['traceNo'] = $yun_signIn['traceNo'];
//        $data['itpOrderId'] = '11002021072378214482';
//        $data['nonceStr'] = random(16);
//        $data['refundAmount'] = 2;
//        $data['mchtRefundNo'] = 'R'.rand(0,9).'2021072318584100001842462671';
//        $data['sign'] = Payment::getYunSign($data,$key);
//        $data = json_encode($data);
//        $res = curlPostRequest($url,$data,["Content-type: application/json;charset='utf-8'"]);
//        wl_debug($res);
//
//        //退款查询
//        $url = 'https://wxtest2.ahrcu.com:3443/cposp/pay/refundQuery';
//        $data['merchantNo'] = $merchantNo;
//        $data['terminalNo'] = $terminalNo;
//        $yun_signIn = Payment::yunSignIn($merchantNo,$terminalNo);
//        $data['batchNo'] = $yun_signIn['batchNo'];
//        $data['traceNo'] = $yun_signIn['traceNo'];
//        $data['itpOrderId'] = '11002021072378214482';
//        $data['nonceStr'] = random(16);
//
//        $data['sign'] = Payment::getYunSign($data,$key);
//        $data = json_encode($data);
//        $res = curlPostRequest($url,$data,["Content-type: application/json;charset='utf-8'"]);
//        wl_debug($res);






        //查询零点用户数据/导入
//        $users = pdo_fetchall("SELECT session_key,user_id as zeroid,open_id as wechat_openid,nickName as nickname,avatarUrl as avatar,mobile  FROM ".tablename('weliam_user')."WHERE wxapp_id = 3 ORDER BY user_id DESC limit 500");
//        foreach ($users as $rrr){
//            $rrr['isvip'] = 1;
//            $rrr['createtime'] =time();
//            $rrr['dotime'] =time();
//            $rrr['tokey'] = strtoupper(MD5(sha1(time() . random(12))));
//            $rrr['uniacid'] = 1;
//            $res = pdo_insert('wlmerchant_member', $rrr);
//            if($res){
//                pdo_update('weliam_user',array('wxapp_id' => 300),array('user_id' => $rrr['zeroid']));
//            }
//        }

        //查询零点分销数据/导入
        // $users = pdo_fetchall("SELECT user_id as zeroid,create_time as createtime,update_time as updatetime,real_name as realname  FROM ".tablename('weliam_dealer_user')."WHERE wxapp_id = 3 ORDER BY user_id DESC");

        // foreach ($users as &$us){
        // 	$us['uniacid'] = 1;
        // 	$member = pdo_get('wlmerchant_member',array('zeroid' => $us['zeroid']),array('nickname','id','mobile'));
        // 	$us['disflag'] = 1;
        // 	$us['mid'] = $member['id'];
        // 	$us['nickname'] = $member['nickname'];
        // 	$us['mobile'] = $member['mobile'];
        // 	pdo_insert('wlmerchant_distributor', $us);
        // }
        // wl_debug($users);


        //插入关系表
//        $users = pdo_fetchall("SELECT dealer_id,user_id,create_time,id  FROM ".tablename('weliam_dealer_referee')."WHERE wxapp_id = 3 ORDER BY user_id DESC limit 300");
//        foreach ($users as $ues){
//            $dis = pdo_getcolumn('wlmerchant_distributor',array('zeroid' => $ues['user_id']),'id');
//            wl_debug($dis);
//            if($dis > 0){
//                $leadid = pdo_getcolumn('wlmerchant_member',array('zeroid' => $ues['dealer_id']),'id');
//                pdo_update('wlmerchant_distributor',array('leadid' => $leadid),array('id' => $dis));
//            }else{
//                $leadid = pdo_getcolumn('wlmerchant_member',array('zeroid' => $ues['dealer_id']),'id');
//                $member = pdo_get('wlmerchant_member',array('zeroid' => $ues['user_id']),array('mobile','nickname','realname','id',));
//                $data = [
//                    'uniacid' => 1,
//                    'mid' => $member['id'],
//                    'leadid' => $leadid,
//                    'nickname' => $member['nickname'],
//                    'realname' => $member['realname'],
//                    'mobile' => $member['mobile'],
//                    'createtime' => time()
//                ];
//                pdo_insert('wlmerchant_distributor',$data);
//            }
//            pdo_update('weliam_dealer_referee',array('wxapp_id' => 300),array('id' => $ues['id']));
//        }
//        wl_debug($users);
//    $users = pdo_fetchall("SELECT id,nickname FROM ".tablename('wlmerchant_member')."WHERE uid = 0 ORDER BY id DESC limit 500");
//    foreach ($users as $uss){
//        $uid = Member::createUserInfo($uss['nickname']);
//        pdo_update('wlmerchant_member',array('uid' => $uid),array('id' => $uss['id']));
//    }



//        $info = [
//            'type'           => 2 ,//支付方式
//            'tid'            => '2021042720491900009167214842' ,//订单号
//            'transaction_id' => '4200001036202104275279675636',
//            'time'           => '20210427204920',
//            'pay_order_no'   => '2021042720491900009167214842',
//            'bank_type'      => 'OTHERS',
//        ];
//        PayResult::main($info);//调用方法处理订单

//        $order = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_order')."WHERE orderno = 'undefined' ORDER BY id DESC");
//        foreach($order as $or){
//            $moduleid = pdo_getcolumn("modules", array('name' => 'weliam_smartcity'), 'mid');
//            $moduleid = empty($moduleid) ? '000000' : sprintf("%06d", $moduleid);
//            $uniontid = date('YmdHis',$or['createtime']) . $moduleid . random(8, 1);
//            pdo_update('wlmerchant_order',array('orderno' => $uniontid),array('id' => $or['id']));
//            pdo_update('wlmerchant_delivery_order',array('tid' => $uniontid),array('orderid' => $or['id']));
//        }

//        $weixin = NEW WeixinPay();
//        $res = $weixin->allocationMulti(6717,1,'',0);
//        wl_debug($res);
        //分销商重复数据
//        $commentSql = "select mid,count(*) as count from" . tablename(PDO_NAME . "distributor") . "group by mid having count > 1 AND mid > 0";
//        $comment = pdo_fetchall($commentSql);
//        wl_debug($comment);
//        foreach ($comment as $com){
//            $member = pdo_get('wlmerchant_member',array('id' => $com['mid']),array('distributorid'));
//            pdo_delete('wlmerchant_distributor',array('mid'=>$com['mid'],'id !=' => $member['distributorid'],'nowmoney <'=> '0.01'));
//        }

//        $commentSql = "select checkcode,count(*) as count from".tablename(PDO_NAME."autosettlement_record")."group by checkcode having count > 1 AND checkcode > 0 ";
//        $comment = pdo_fetchall($commentSql);
//        foreach ($comment as $com){
//            $list = pdo_fetchall("SELECT * FROM ".tablename(PDO_NAME.'autosettlement_record')."WHERE checkcode = {$com['checkcode']} ORDER BY id DESC");
//            $num = count($list) - 1;
//            for ($i=0;$i<$num;$i++){
//                if($list[$i]['merchantmoney']>0){
//                    pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney-{$list[$i]['merchantmoney']},nowmoney=nowmoney-{$list[$i]['merchantmoney']} WHERE id = {$list[$i]['merchantid']}");
//                }
//                if($list[$i]['agentmoney']>0){
//                    pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$list[$i]['agentmoney']},nowmoney=nowmoney+{$list[$i]['agentmoney']} WHERE id = {$list[$i]['aid']}");
//                }
//                pdo_delete('wlmerchant_autosettlement_record',array('id'=>$list[$i]['id']));
//            }
//        }
//        $commentSql =  "SELECT a.id,a.mid,a.uniacid FROM ". tablename(PDO_NAME."distributor")
//            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
//            ." as b ON a.mid = b.id WHERE
//        CASE
//             WHEN a.uniacid != b.uniacid THEN 1
//             ELSE 0
//         END = 1 ORDER BY id DESC";
//        $comment = pdo_fetchall($commentSql);
//        wl_debug($comment);
//        foreach ($comment as $com){
//            $uniacid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$com['mid']),'uniacid');
//            pdo_update(PDO_NAME."distributor",array('uniacid' => $uniacid),array('id' => $com['id']));
//        }
//        set_time_limit(0);
//        $times = pdo_fetchall("SELECT id,merchantid,aid FROM ".tablename('wlmerchant_timecardrecord')."WHERE createtime > 1559318400");
//        foreach ($times as $key => $time){
//            $aid = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$time['merchantid']),'aid');
//            if($aid!=$time['aid']){
//                pdo_update('wlmerchant_timecardrecord',array('aid' => $aid),array('id' => $time['id']));
//            }
//        }

//        $success = 0;
//        $current = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_current')."WHERE status = 2 AND type = 1 AND fee > 0 ORDER BY id DESC");
//        foreach ($current as $key => $curr) {
//            $order = pdo_get('wlmerchant_rush_order',array('id' => $curr['orderid']),array('aid'));
//            if($order['aid'] != $curr['aid']){
//                $curr['taid'] = $order['aid'];
//                $res1 = pdo_update('wlmerchant_agentusers', array('allmoney -=' => $curr['fee'],'nowmoney -=' => $curr['fee']), array('id' =>$curr['aid']));
//                $res2 = pdo_update('wlmerchant_agentusers', array('allmoney +=' => $curr['fee'],'nowmoney +=' => $curr['fee']), array('id' =>$curr['taid']));
//                if($res1 && $res2){
//                    $res3 = pdo_delete('wlmerchant_current',array('id'=>$curr['id']));
//                    $success ++;
//                }
//                if($res3){
//                    $nowmoney = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$curr['taid']),'nowmoney');
//                    $data = array(
//                        'uniacid'    =>  $curr['uniacid'],
//                        'status'     =>  2,
//                        'type'       =>  1,
//                        'objid'      =>  $curr['taid'],
//                        'fee'        =>  $curr['fee'],
//                        'nowmoney'   =>  $nowmoney,
//                        'orderid'    =>  $curr['orderid'],
//                        'remark'     =>  '错误结算到其他代理，重结算',
//                        'createtime' =>  time(),
//                        'aid'        =>  $curr['taid'],
//                    );
//                    pdo_insert(PDO_NAME.'current',$data);
//                }
//            }
//        }
//        wl_debug($success);


        if (checksubmit()) {
            $sql = $_POST['sql'];
            pdo_run($sql);
            wl_message('查询执行成功.', 'refresh');
        }
        include wl_template('cloud/database');
    }

    public function data_fix()
    {
        global $_W, $_GPC;
        wl_debug($_GPC);

//        $fix_orders = [];
//        $orders = pdo_getall('wlmerchant_smallorder', array('uniacid' => 141, 'twoleadid !=' => 0, 'status' => 1), ['orderno', 'orderid', 'oneleadid', 'twoleadid', 'checkcode']);
//        foreach ($orders as $order) {
//            $distor = pdo_get('wlmerchant_distributor', ['id' => $order['twoleadid']], ['leadid']);
//            if ($order['oneleadid'] != $distor['leadid']) {
//                $order['one'] = $distor['leadid'];
//                $fix_orders[$order['orderid']][] = $order;
//            }
//        }
//        wl_debug(['data' => $fix_orders, 'count' => count($fix_orders)]);
        //Order::createSmallorder(62704,1);
    }

    public function upgrade()
    {
        global $_W, $_GPC;
        $tables = [];
        $tablenames = WeliamDb::get_tables_name('wlmerchant', $_W['config']['db']['tablepre']);
        foreach ($tablenames as $tablename) {
            $tableinfo = WeliamDb::get_table_schema($tablename);
            $tables[] = $tableinfo;
        }
        wl_debug($tables);
    }

    public function upgrade_file()
    {
        global $_W;
        $tables = [];
        $tablenames = WeliamDb::get_tables_name($_W['config']['db']['tablepre'], $_W['config']['db']['tablepre']);
        foreach ($tablenames as $tablename) {
            $tables[] = WeliamDb::get_table_schema($tablename);
        }
        file_put_contents(PATH_CORE . "common/dbfile.php", base64_encode(json_encode($tables)));
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        wl_message('数据更新文件生成成功', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');

    }

    public function datemana()
    {
        global $_W, $_GPC;
        include wl_template('cloud/datemana');
    }

    public function areadata()
    {
        global $_W, $_GPC, $GLOBALS;
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        $type = !empty($_GPC['type']) ? $_GPC['type'] : 'install';
        if ($type == 'install') {
            $this->areadatainit();
            wl_message('地区数据安装成功.', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');
        }
        if ($type == 'clear') {
            $id = pdo_getcolumn(PDO_NAME . 'area', array('id' => 110000), 'id');
            if (empty($id)) {
                wl_message('不存在地区数据，无需再清除.', web_url('cloud/database/datemana',['lct'=> $lct]), 'warning');
            }
            pdo_query("TRUNCATE TABLE " . tablename('wlmerchant_area') . ";");
            wl_message('地区数据清除成功.', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');
        }
    }

    public function areadataup()
    {
        $t1 = microtime(true);
        ini_set('display_errors', '1');
        error_reporting(E_ALL ^ E_NOTICE);
        $this->areadatainit();
        $t2 = microtime(true);
        wl_debug('执行完成，耗时' . round($t2 - $t1, 3) . '秒');
    }

    public function permission()
    {
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        $permission_file = IA_ROOT . '/web/common/permission.inc.php';
        $permission = require $permission_file;
        if (!in_array('file', $permission['utility']['direct'])) {
            $permission['utility']['direct'][] = 'file';
            $permission_str = var_export($permission, true);
            $verdat = <<<VER
<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

\$we7_file_permission = {$permission_str};
return \$we7_file_permission;
VER;
            file_put_contents($permission_file, trim($verdat));
        }
        wl_message('修复成功.', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');
    }

    /**
     * 迁移公众号数据
     */
    public function movedata()
    {
        global $_W, $_GPC;
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        if (checksubmit('submit')) {
            $from = intval($_GPC['from']);
            $to = intval($_GPC['to']);
            if (empty($from) || empty($to)) {
                wl_message('请填写公众号ID', referer(), 'warning');
            }

            $tablenames = pdo_fetchall("SHOW TABLES LIKE :tablename", array(":tablename" => "%wlmerchant%"));
            foreach ($tablenames as $tablename) {
                $table = str_replace($_W['config']['db']['tablepre'], '', end($tablename));
                pdo_update($table, array('uniacid' => $to), array('uniacid' => $from));
            }
            wl_message('数据迁移成功.', web_url('cloud/database/movedata',['lct'=> $lct]), 'success');
        }
        include wl_template('cloud/movedata');
    }

    public function backup()
    {
        global $_W, $_GPC;
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        load()->func('db');
        if ($_GPC['status']) {
            $sql = "SHOW TABLE STATUS LIKE '{$_W['config']['db']['tablepre']}wlmerchant_%'";
            $tables = pdo_fetchall($sql);
            if (empty($tables)) {
                itoast('数据已经备份完成', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');
            }
            $series = max(1, intval($_GPC['series']));
            $volume_suffix = md5(complex_authkey());
            if (!empty($_GPC['folder_suffix']) && !preg_match('/[^0-9A-Za-z-_]/', $_GPC['folder_suffix'])) {
                $folder_suffix = $_GPC['folder_suffix'];
            } else {
                $folder_suffix = TIMESTAMP . '_' . random(8);
            }
            $bakdir = IA_ROOT . '/data/' . MODULE_NAME . '/backup/' . $folder_suffix;
            if (trim($_GPC['start'])) {
                $result = mkdirs($bakdir);
            }
            $size = 300;
            $volumn = 1024 * 1024 * 2;
            $dump = '';
            if (empty($_GPC['last_table'])) {
                $last_table = '';
                $catch = true;
            } else {
                $last_table = $_GPC['last_table'];
                $catch = false;
            }
            foreach ($tables as $table) {
                $table = array_shift($table);
                if (!empty($last_table) && $table == $last_table) {
                    $catch = true;
                }
                if (!$catch) {
                    continue;
                }
                if (!empty($dump)) {
                    $dump .= "\n\n";
                }
                if ($table != $last_table) {
                    $row = db_table_schemas($table);
                    $dump .= $row;
                }
                $index = 0;
                if (!empty($_GPC['index'])) {
                    $index = intval($_GPC['index']);
                    $_GPC['index'] = 0;
                }
                while (true) {
                    $start = $index * $size;
                    $result = WeliamDb::get_table_insert_sql($table, $_W['uniacid'], $start, $size);
                    if (!empty($result)) {
                        $dump .= $result['data'];
                        if (strlen($dump) > $volumn) {
                            $bakfile = $bakdir . "/volume-{$volume_suffix}-{$series}.sql";
                            $dump .= "\n\n";
                            file_put_contents($bakfile, $dump);
                            ++$series;
                            ++$index;
                            $current = array(
                                'last_table'    => $table,
                                'index'         => $index,
                                'series'        => $series,
                                'folder_suffix' => $folder_suffix,
                                'status'        => 1,
                            );
                            $current_series = $series - 1;
                            message('正在导出数据, 请不要关闭浏览器, 当前第 ' . $current_series . ' 卷.', web_url('cloud/database/backup/', $current), 'info');
                        }
                    }
                    if (empty($result) || count($result['result']) < $size) {
                        break;
                    }
                    ++$index;
                }
            }
            $bakfile = $bakdir . "/volume-{$volume_suffix}-{$series}.sql";
            $dump .= "\n\n----Weliam MySQL Dump End";
            file_put_contents($bakfile, $dump);
            itoast('数据已经备份完成', web_url('cloud/database/datemana',['lct'=> $lct]), 'success');
        }
    }


    private function areadatainit()
    {
        $address = json_decode(file_get_contents(PATH_WEB . 'resource/download/aliarea.json'), true);
        $locations = json_decode(file_get_contents(PATH_WEB . 'resource/download/locations.json'), true);

        foreach ($address['children'] as $province) {
            $province['divisionLevel'] == 2 && $this->aliareainsert($province, $locations);

            foreach ($province['children'] as $city) {
                if ($city['divisionLevel'] == 4) {
                    $city['divisionLevel'] = 3;
                }
                $this->aliareainsert($city, $locations);
                if ($city['divisionCode'] != $city['divisionId'] && $city['divisionLevel'] == 3) {
                    $errnoarray[] = $city;
                }
                if (!empty($city['children'])) {
                    foreach ($city['children'] as $district) {
                        $district['divisionLevel'] == 4 && $this->aliareainsert($district, $locations);
                    }
                }
            }
        }
        foreach ($errnoarray as $errcity) {
            pdo_update('wlmerchant_area', array('pid' => $errcity['divisionCode']), array('pid' => $errcity['divisionId']));
        }
    }

    private function aliareainsert($item, $location)
    {
        $name = pdo_getcolumn('wlmerchant_area', array('id' => $item['divisionCode']), 'name');
        $data = array(
            'id'      => $item['divisionCode'],
            'pid'     => $item['parentId'] == 1 ? 0 : $item['parentId'],
            'name'    => $item['divisionName'],
            'visible' => 2,
            'level'   => $item['divisionLevel'] - 1,
            'lat'     => $location[$item['divisionCode']]['lat'],
            'lng'     => $location[$item['divisionCode']]['lng'],
            'pinyin'  => $item['pinyin'] ? str_replace(' ', '', $item['pinyin']) : '',
            'initial' => $item['pinyin'] ? strtoupper(substr($item['pinyin'], 0, 1)) : ''
        );
        if (empty($name)) {
            pdo_insert('wlmerchant_area', $data);
        } else {
            pdo_update('wlmerchant_area', $data, array('id' => $item['divisionCode']));
        }
    }
}
