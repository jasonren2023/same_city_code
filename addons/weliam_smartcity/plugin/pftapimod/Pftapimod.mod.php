<?php
defined('IN_IA') or exit('Access Denied');
libxml_disable_entity_loader(false);//在所有SOAPClient初始化前（页面顶部），加上这句 libxml_disable_entity_loader(false);

class Pftapimod {
    /**
     * 获取设置信息
     */
    public function getSetting(){
        global $_W,$_GPC;
        $settings = Setting::agentsetting_read('pftapi');
        if(empty($settings['pftswitch'])){
            $settings = Setting::wlsetting_read('pftapi');
        }
        if(empty($settings['pftswitch'])) {
            $settings = [];
        }
        return $settings;
    }


    /**
     * 获取商品列表
     */
    public function getGoodsList($n,$m){
        global $_W,$_GPC;
        $settings = self::getSetting();
        if(empty($settings['pftswitch'])){
            $newlist = ['error' => 1,'msg' => '功能未开启'];
        }else{
            $s1 = self::getPftUrl($settings['environment']);
            //查询景区列表
            $xmldata = $s1->__soapCall("Get_ScenicSpot_List",array("ac"=>$settings['account'],"pw"=>$settings['pwd'],"n"=>$n,"m"=>$m));
            $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
            $value_array = json_decode(json_encode($xmlstring),true);
            $list = $value_array['Rec'];
            if($list['UUerrorcode'] > 0){
                $newlist = ['error' => 1,'msg' => $list['UUerrorinfo']];
            }else{
                if(empty($list)){
                    $newlist = [];
                }else{
                    if(empty($list[1])){
                        $newlist[] = $list;
                    }else{
                        $newlist = $list;
                    }
                }
            }
        }
        return $newlist;
    }
    /**
     * 获取商品详情
     */
    public function getGoodsDetail($id){
        global $_W,$_GPC;
        $settings = self::getSetting();
        $s1 = self::getPftUrl($settings['environment']);
        $xmldata = $s1->__soapCall("Get_ScenicSpot_Info",array("ac"=>$settings['account'],"pw"=>$settings['pwd'],"n"=>$id));
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $goods = $value_array['Rec'];

        return $goods;
    }

    /**
     * 获取门票详情
     */
    public function getTicketDetail($id,$tid = 0){
        global $_W,$_GPC;
        $settings = self::getSetting();

        $s1 = self::getPftUrl($settings['environment']);
        $xmldata = $s1->__soapCall("Get_Ticket_List",array("ac"=>$settings['account'],"pw"=>$settings['pwd'],'n'=>$id,"m"=>$tid));
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $ticket = $value_array['Rec'];
        if(empty($ticket)){
            $newlist = [];
        }else{
            if(empty($ticket[1])){
                $newlist[] = $ticket;
            }else{
                $newlist = $ticket;
            }
        }
        return $newlist;
    }

    /**
     * 日历价格库存接口
     */
    public function GetRealTimeStorage($data){
        global $_W,$_GPC;
        $settings = self::getSetting();

        $s1 = self::getPftUrl($settings['environment']);
        $data1['ac'] = $settings['account'];
        $data1['pw'] = $settings['pwd'];
        $data1 = array_merge($data1,$data);
        $xmldata = $s1->__soapCall("GetRealTimeStorage",$data1);
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $allinfo = $value_array['items'];
        foreach ($allinfo as $llin){
            if($data['start_date'] == $llin['date'] ){
                $info = $llin;
            }
        }
        return $info;
    }

    /**
     * 身份证验证接口
     */
    public function checkPersonID($personId){
        global $_W,$_GPC;
        $settings = self::getSetting();
        $dataAC['ac'] = $settings['account'];
        $dataAC['pw'] = $settings['pwd'];

        $s1 = self::getPftUrl($settings['environment']);
        $perdata = $dataAC;
        $personIdArray = explode(",",$personId);
        foreach($personIdArray as $pers){
            $perdata['personId'] = $pers;
            $xmldata = $s1->__soapCall("Check_PersonID",$perdata);
            $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
            $value_array = json_decode(json_encode($xmlstring),true);
            $info = $value_array['Rec'];
            if($info['UUdone'] != 100){
                $msg = '身份证号['.$pers.']验证错误';
            }
        }
        if(empty($msg)){
            return array('error' => 0);
        }else{
            return array('error' => 1,'msg' => $msg);
        }
    }

        /**
     * 订单预提交接口
     */
    public function getOrderPreCheck($data){
        global $_W,$_GPC;
        $settings = self::getSetting();
        $dataAC['ac'] = $settings['account'];
        $dataAC['pw'] = $settings['pwd'];

        $s1 = self::getPftUrl($settings['environment']);
        $data1 = array_merge($dataAC,$data);
        $xmldata = $s1->__soapCall("OrderPreCheck",$data1);
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $info = $value_array['Rec'];

        return $info;

    }

    /**
     * 订单提交接口
     */
    public function pftOrderSubmit($data){
        global $_W,$_GPC;
        $settings = self::getSetting();
        //提交数据
        $OrderSubmitData = [
            'lid' => $data['lid'],
            'tid' => $data['tid'],
            'remotenum' => $data['remotenum'],
            'tprice' => $data['tprice'],
            'tnum' => $data['tnum'],
            'playtime' => $data['playtime'],
            'ordername' => $data['ordername'],
            'ordertel'  => $data['ordertel'],
            'contactTEL' => $data['contactTEL'],
            'smsSend' => 0,
            'paymode' => 0,
            'ordermode' => 0,
            'assembly' => '',
            'series' => '',
            'concatID' => 0,
            'pCode' => 0,
            'm' => $data['aid'],
            'personID' => $data['personid'],
            'memo' => $data['remark'],
            'callbackUrl' => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/pftapimod/pftAsyNotify.php'
        ];

        $data1['ac'] = $settings['account'];
        $data1['pw'] = $settings['pwd'];
        $data1 = array_merge($data1,$OrderSubmitData);

        $s1 = self::getPftUrl($settings['environment']);
        $xmldata = $s1->__soapCall("PFT_Order_Submit",$data1);
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        file_put_contents(PATH_DATA . "pftorderinfo.log", var_export($value_array, true) . PHP_EOL, FILE_APPEND);
        $info = $value_array['Rec'];

        return $info;
    }

    /**
     * 订单查询接口
     */
    public function pftOrderQuery($pftOrdernum){
        global $_W,$_GPC;
        $settings = self::getSetting();
        //提交数据
        $data = [
            'ac' => $settings['account'],
            'pw' =>  $settings['pwd'],
            'pftOrdernum' => $pftOrdernum
        ];
        $s1 = self::getPftUrl($settings['environment']);
        $xmldata = $s1->__soapCall("OrderQuery",$data);
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $info = $value_array['Rec'];

        return $info;
    }
    /**
     * 订单取消接口
     */
    public function pftOrderRefund($data){
        global $_W,$_GPC;
        $settings = self::getSetting();
        $data1['ac'] = $settings['account'];
        $data1['pw'] = $settings['pwd'];
        $data1 = array_merge($data1,$data);

        $s1 = self::getPftUrl($settings['environment']);
        $xmldata = $s1->__soapCall("Order_Change_Pro",$data1);
        $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
        $value_array = json_decode(json_encode($xmlstring),true);
        $info = $value_array['Rec'];

        return $info;
    }

    /**
     * 环境切换
     */
    public function getPftUrl($environment){
        if($environment > 0){
            $soap = new SoapClient("http://open.12301.cc/openService/MXSE.wsdl",array('encoding' =>'UTF-8','cache_wsdl' => 0));//票付通接口地址
        }else{
            $soap = new SoapClient("http://open.12301dev.com/openService/MXSE_beta.wsdl",array('encoding' =>'UTF-8','cache_wsdl' => 0));//票付通接口地址
        }
        return $soap;
    }

    /**
     * 获取设置信息(亿奇达)
     */
    public function getYqdSetting(){
        global $_W,$_GPC;
        $settings = Setting::agentsetting_read('pftapi');
        if(empty($settings['yqdswitch'])){
            $settings = Setting::wlsetting_read('pftapi');
        }
        if(empty($settings['yqdswitch'])) {
            $settings = [];
        }
        return $settings;
    }

    /**
     * 获取商品列表(亿奇达)
     */
    public function getYqdGoodList($page){
        global $_W,$_GPC;
        $set = self::getYqdSetting();
        if(empty($set['yqdswitch'])){
            $yqdInfo = ['error' => 1,'msg' => '功能未开启'];
        }else{
            $time = time();
            $admin = $set['yqduser'];
            $key = $set['yqdsecret'];
            $data = [
                'page' => $page,
                'size' => 20
            ];
            $data = json_encode($data);
            $sign = md5($time.$data.$key);
            $url = "http://open.yiqida.cn/api/UserCommdity/GetCommodityList?timestamp={$time}&userName={$admin}&sign={$sign}";
            $yqdInfo = curlPostRequest($url,$data);
            if($yqdInfo['code'] != '200'){
                file_put_contents(PATH_DATA . "yqderror.log", var_export($yqdInfo, true) . PHP_EOL, FILE_APPEND);
            }
        }
        return $yqdInfo;
    }

    /**
     * 获取商品信息(亿奇达)
     */
    public function getYqdGoodInfo($id){
        global $_W,$_GPC;
        $set = self::getYqdSetting();

        $time = time();
        $admin = $set['yqduser'];
        $key = $set['yqdsecret'];
        $data = [
            'id' => $id,
        ];
        $data = json_encode($data);
        $sign = md5($time.$data.$key);
        $url = "http://open.yiqida.cn/api/UserCommdity/GetCommodityInfo?timestamp={$time}&userName={$admin}&sign={$sign}";
        $yqdInfo = curlPostRequest($url,$data);
        if($yqdInfo['code'] != '200'){
            file_put_contents(PATH_DATA . "yqderror.log", var_export($yqdInfo, true) . PHP_EOL, FILE_APPEND);
        }
        return $yqdInfo;
    }
    /**
     * 订单提交(亿奇达)
     */
    public function yqdOrderSubmit($data){
        global $_W,$_GPC;
        $set = self::getYqdSetting();

        $time = time();
        $admin = $set['yqduser'];
        $key = $set['yqdsecret'];

        $data = json_encode($data);
        $sign = md5($time.$data.$key);

        $url = "http://open.yiqida.cn/api/UserOrder/CreateOrder?timestamp={$time}&userName={$admin}&sign={$sign}";
        $yqdInfo = curlPostRequest($url,$data);
        if($yqdInfo['code'] != '200'){
            file_put_contents(PATH_DATA . "yqderror.log", var_export($yqdInfo, true) . PHP_EOL, FILE_APPEND);
        }
        return $yqdInfo;
    }


    /**
     * Comment: 获取某个商品或规格的销量
     * Author: wlf
     * Date: 2020/06/28 18:30
     * @param int $plugin     商品类型：1=抢购  2=团购  3=拼团
     * @param int $id       商品id
     * @param int $type     类型: 1=已下单 2=已支付 3=已完成
     * @param int $mid      买家ID
     * @param int $starttime   起始时间
     * @param int $endtime     结束时间
     * @return array
     */
    public function getThreeSalesNum($plugin,$id,$type,$mid = 0,$starttime = 0,$endtime = 0){
        global $_W,$_GPC;
        if($plugin == 1){
            $orderwhere = " activityid = {$id}";
            if($mid > 0){
                $orderwhere .= " AND mid = {$mid}";
            }
            if($starttime > 0){
                $orderwhere .= " AND createtime > {$starttime}";
            }
            if($endtime > 0){
                $orderwhere .= " AND createtime < {$endtime}";
            }
            if($type == 1){
                $expresswhere = $orderwhere." AND status IN (0,1,2,3,6)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }else if($type == 2){
                //快递的
                $expresswhere = $orderwhere." AND status IN (1,2,3,6)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }else if($type == 3){
                //快递的
                $expresswhere = $orderwhere." AND status IN (2,3)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }
        }
        else{
            $orderwhere = " fkid = {$id}";
            switch($plugin){
                case 2:
                    $orderwhere .= " AND plugin = 'groupon'";
                    break;
                case 3:
                    $orderwhere .= " AND plugin = 'wlfightgroup'";
                    break;
            }
            if($mid > 0){
                $orderwhere .= " AND mid = {$mid}";
            }
            if($starttime > 0){
                $orderwhere .= " AND createtime > {$starttime}";
            }
            if($endtime > 0){
                $orderwhere .= " AND createtime < {$endtime}";
            }
            if($type == 1){
                $expresswhere = $orderwhere." AND status IN (0,1,2,3,6)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }else if($type == 2){
                //快递的
                $expresswhere = $orderwhere." AND status IN (1,2,3,6)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }else if($type == 3){
                //快递的
                $expresswhere = $orderwhere." AND status IN (2,3)";
                $expressnum = implode(pdo_fetch("SELECT sum(num) FROM " . tablename(PDO_NAME . "order") . " WHERE {$expresswhere}"));
                $salesnum = $expressnum;
            }
        }
        return $salesnum;
    }
}