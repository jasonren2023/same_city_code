<?php
defined('IN_IA') or exit('Access Denied');

class Logistics {
    /**
     * Comment: 通过物流id获取物流信息
     * Author: zzw
     * Date: 2019/10/8 14:31
     * @param $id   int     物流信息id（express表的id）
     * @return mixed
     */
    public static function orderLogisticsInfo($id){
        #1、获取设置信息
        $set = Setting::wlsetting_read('api')['logistics'];
        if(!$set) Commons::sRenderError('平台暂未开启物流查询');
        #1、根据物流类型调用物流信息查询方法
        switch ($set['type']){
            case 1;
                if($set['kdntype'] > 0){
                    $res = self::logisticsSelectFunction_1($set,$id,8001);
                }else{
                    $res = self::logisticsSelectFunction_1($set,$id);
                }
                break;//快递鸟
            case 2;
                $res = self::logisticsSelectFunction_2($set,$id);
                break;//阿里云
            case 3;break;//快递100
            default:Commons::sRenderError('平台暂未开启物流查询');break;
        }

        return $res;
    }
    /**
     * Comment: 物流信息查询方法一(快递鸟)
     * Author: zzw
     * Date: 2019/10/8 16:15
     * @param $set  array   设置信息
     * @param $id   int     物流id
     * @param int $requestType  1002=免费版本；8001=付费版本
     * @return mixed
     */
    protected static function logisticsSelectFunction_1($set,$id,$requestType = 1002){
        #1、获取设置信息
        $userId = trim($set['id']);//电商ID
        $AppKey = trim($set['app_key']);//电商加密私钥
        $ReqURL = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';//请求url
        #2、获取物流信息
        $info = pdo_get(PDO_NAME."express",['id'=>$id],['expressname','expresssn','tel']) OR Commons::sRenderError('暂无物流信息');
        $logisticsInfo = self::codeComparisonTable($info['expressname'],'alias');
        #3、生成请求内容
        $requestDataParams = [
            'OrderCode'    => '' ,//订单编号
            'ShipperCode'  => trim($logisticsInfo['code']) ,//快递公司编码
            'LogisticCode' => trim($info['expresssn']) ,//物流单号
        ];
        if(trim($logisticsInfo['code']) == 'SF') $requestDataParams['CustomerName'] = substr($info['tel'],-4);
        if(trim($logisticsInfo['code']) == 'JD') $requestDataParams['CustomerName'] = trim($set['jd_code']);
        $requestData = json_encode($requestDataParams);
        $data = [
            'EBusinessID' => $userId,
            'RequestType' => $requestType,//1002=免费版本；8001=付费版本
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        ];
        #4、生成请求签名
        $data['DataSign'] = urlencode(base64_encode(md5($requestData.$AppKey)));
        #4、生成请求header头部信息
        $temps = array();
        foreach ($data as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($ReqURL);
        if(empty($url_info['port'])) {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        #4、请求获取物流信息
        return curlPostRequest($ReqURL,$data,$httpheader);
    }


    /**
     * Comment: 物流信息查询方法二(阿里云)
     * Author: wlf
     * Date: 2022/09/27 16:33
     * @param $set  array   设置信息
     * @param $id   int     物流id
     * @return mixed
     */
    protected static function logisticsSelectFunction_2($set,$id){
        #1、获取设置信息
        $AppCode = trim($set['appcode']);//电商加密私钥
        #2、获取物流信息
        $info = pdo_get(PDO_NAME."express",['id'=>$id],['expressname','expresssn','tel','aliyuninfo','overtime']) OR Commons::sRenderError('暂无物流信息');
        if($info['overtime'] > time()){
            $res = unserialize($info['aliyuninfo']);
        }else{
            $logisticsInfo = self::codeComparisonTable($info['expressname'],'alias');
            if(trim($logisticsInfo['code']) == 'SF'){
                $n = $info['expresssn'].':'.substr($info['tel'],-4);
            }else{
                $n = $info['expresssn'];
            }
            $ReqURL = 'https://wdexpress.market.alicloudapi.com/gxali?n='.$n;//请求url
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $AppCode);
            $res = curlGetRequest($ReqURL,$headers);
            if($res['Success']){
                $aliyuninfo = serialize($res);
                if($set['datatime'] > 0){
                    $overtime = time() + $set['datatime'] * 3600;
                }else{
                    $overtime = time() + 3600;
                }
                pdo_update(PDO_NAME."express",array('aliyuninfo' => $aliyuninfo,'overtime' => $overtime),array('id' => $id));
            }
        }
        return $res;
    }

    /**
     * Comment: 通过类型匹配物流公司信息
     * Author: zzw
     * Date: 2019/10/8 16:56
     * @param $name     string  物流公司配置名称（name=匹配名称，alias=匹配别名，code=匹配编码）
     * @param $type     int     匹配类型
     * @param $getArray bool    是否返回所有物流公司信息数组
     * @return array
     */
    public static function codeComparisonTable($name = '',$type = 0,$getArray = false){
        #1、生成匹配项数组
        $data = [
            [ 'name' => '顺丰' , 'alias' => 'shunfeng' , 'code' => 'SF' ] ,
            [ 'name' => '百世快递' , 'alias' => 'baishikuaidi' , 'code' => 'HTKY' ] ,
            [ 'name' => '中通快递' , 'alias' => 'zhongtong' , 'code' => 'ZTO' ] ,
            [ 'name' => '申通快递' , 'alias' => 'shentong' , 'code' => 'STO' ] ,
            [ 'name' => '圆通速递' , 'alias' => 'yuantong' , 'code' => 'YTO' ] ,
            [ 'name' => '韵达速递' , 'alias' => 'yundasd' , 'code' => 'YD' ] ,
            [ 'name' => '邮政快递包裹' , 'alias' => 'youzhengguonei' , 'code' => 'YZPY' ] ,
            [ 'name' => 'EMS' , 'alias' => 'ems' , 'code' => 'EMS' ] ,
            [ 'name' => '天天快递' , 'alias' => 'tiantian' , 'code' => 'HHTT' ] ,
            [ 'name' => '京东快递' , 'alias' => 'jd' , 'code' => 'JD' ] ,
            [ 'name' => '优速快递' , 'alias' => 'youshuwuliu' , 'code' => 'UC' ] ,
            [ 'name' => '德邦快递' , 'alias' => 'debangwuliu' , 'code' => 'DBL' ] ,
            [ 'name' => '极兔快递' , 'alias' => 'JTSD' , 'code' => 'JTSD' ] ,
            [ 'name' => '宅急送' , 'alias' => 'zhaijisong' , 'code' => 'ZJS' ] ,
            [ 'name' => 'aae全球专递' , 'alias' => 'aae' , 'code' => 'AAE' ] ,
            [ 'name' => '安捷快递' , 'alias' => 'anjie' , 'code' => 'AJ' ] ,
            [ 'name' => 'bht' , 'alias' => 'bht' , 'code' => 'BHT' ] ,
            [ 'name' => '百福东方国际物流' , 'alias' => 'baifudongfang' , 'code' => 'BFDF' ] ,
            [ 'name' => '中国东方（COE）' , 'alias' => 'coe' , 'code' => 'COE' ] ,
            [ 'name' => '中国东方(COE)' , 'alias' => 'coe' , 'code' => 'COE' ] ,
            [ 'name' => '大田物流' , 'alias' => 'datianwuliu' , 'code' => 'DTWL' ] ,
            [ 'name' => 'dhl' , 'alias' => 'dhl' , 'code' => 'DHL' ] ,
            [ 'name' => 'dpex' , 'alias' => 'dpex' , 'code' => 'DPEX' ] ,
            [ 'name' => 'd速快递' , 'alias' => 'dsukuaidi' , 'code' => 'DSWL' ] ,
            [ 'name' => '递四方' , 'alias' => 'disifang' , 'code' => 'D4PX' ] ,
            [ 'name' => 'fedex（国外）' , 'alias' => 'fedex' , 'code' => 'FEDEX_GJ' ] ,
            [ 'name' => 'fedex(国外)' , 'alias' => 'fedex' , 'code' => 'FEDEX_GJ' ] ,
            [ 'name' => '飞康达物流' , 'alias' => 'feikangda' , 'code' => 'FKD' ] ,
            [ 'name' => '广东邮政物流' , 'alias' => 'guangdongyouzhengwuliu' , 'code' => 'GDEMS' ] ,
            [ 'name' => '共速达' , 'alias' => 'gongsuda' , 'code' => 'GSD' ] ,
            [ 'name' => '恒路物流' , 'alias' => 'hengluwuliu' , 'code' => 'HLWL' ] ,
            [ 'name' => '华夏龙物流' , 'alias' => 'huaxialongwuliu' , 'code' => 'HXLWL' ] ,
            [ 'name' => '佳怡物流' , 'alias' => 'jiayiwuliu' , 'code' => 'JYWL' ] ,
            [ 'name' => '京广速递' , 'alias' => 'jinguangsudikuaijian' , 'code' => 'JGSD' ] ,
            [ 'name' => '急先达' , 'alias' => 'jixianda' , 'code' => 'JXD' ] ,
            [ 'name' => '佳吉物流' , 'alias' => 'jjwl' , 'code' => 'CNEX' ] ,
            [ 'name' => '加运美物流' , 'alias' => 'jymwl' , 'code' => 'JYM' ] ,
            [ 'name' => '晋越快递' , 'alias' => 'jykd' , 'code' => 'JYKD' ] ,
            [ 'name' => '快捷速递' , 'alias' => 'kuaijiesudi' , 'code' => 'DJKJWL' ] ,
            [ 'name' => '联邦快递（国内）' , 'alias' => 'lianb' , 'code' => 'FEDEX' ] ,
            [ 'name' => '联邦快递(国内)' , 'alias' => 'lianb' , 'code' => 'FEDEX' ] ,
            [ 'name' => '联昊通物流' , 'alias' => 'lianhaowuliu' , 'code' => 'LHT' ] ,
            [ 'name' => '龙邦物流' , 'alias' => 'longbanwuliu' , 'code' => 'LB' ] ,
            [ 'name' => '立即送' , 'alias' => 'lijisong' , 'code' => 'LJSKD' ] ,
            [ 'name' => '民航快递' , 'alias' => 'minghangkuaidi' , 'code' => 'MHKD' ] ,
            [ 'name' => '门对门' , 'alias' => 'menduimen' , 'code' => 'MDM' ] ,
            [ 'name' => 'OCS' , 'alias' => 'ocs' , 'code' => 'OCS' ] ,
            [ 'name' => '全晨快递' , 'alias' => 'quanchenkuaidi' , 'code' => 'QCKD' ] ,
            [ 'name' => '全日通快递' , 'alias' => 'quanritongkuaidi' , 'code' => 'QRT' ] ,
            [ 'name' => '全一快递' , 'alias' => 'quanyikuaidi' , 'code' => 'UAPEX' ] ,
            [ 'name' => '如风达' , 'alias' => 'rufengda' , 'code' => 'RFD' ] ,
            [ 'name' => '速尔物流' , 'alias' => 'sue' , 'code' => 'SURE' ] ,
            [ 'name' => '盛丰物流' , 'alias' => 'shengfeng' , 'code' => 'SFWL' ] ,
            [ 'name' => '赛澳递' , 'alias' => 'saiaodi' , 'code' => 'SAD' ] ,
            [ 'name' => '天地华宇' , 'alias' => 'tiandihuayu' , 'code' => 'HOAU' ] ,
            [ 'name' => 'tnt' , 'alias' => 'tnt' , 'code' => 'TNT' ] ,
            [ 'name' => 'ups' , 'alias' => 'ups' , 'code' => 'UPS' ] ,
            [ 'name' => '万家物流' , 'alias' => 'wanjiawuliu' , 'code' => 'WJWL' ] ,
            [ 'name' => '万象物流' , 'alias' => 'wxwl' , 'code' => 'WXWL' ] ,
            [ 'name' => '信丰物流' , 'alias' => 'xinfengwuliu' , 'code' => 'XFEX' ] ,
            [ 'name' => '亚风速递' , 'alias' => 'yafengsudi' , 'code' => 'YFSD' ] ,
            [ 'name' => '邮政国际包裹挂号信' , 'alias' => 'youzhengguoji' , 'code' => 'GJYZ' ] ,
            [ 'name' => '远成物流' , 'alias' => 'yuanchengwuliu' , 'code' => 'YCWL' ] ,
            [ 'name' => '运通快递' , 'alias' => 'yuntongkuaidi' , 'code' => 'YTKD' ] ,
            [ 'name' => '源安达' , 'alias' => 'yad' , 'code' => 'YADEX' ] ,
            [ 'name' => '中铁快运' , 'alias' => 'zhongtiekuaiyun' , 'code' => 'ZTWL' ] ,
            [ 'name' => '中邮物流' , 'alias' => 'zhongyouwuliu' , 'code' => 'ZYKD' ] ,
            [ 'name' => '芝麻开门' , 'alias' => 'zhimakaimen' , 'code' => 'ZMKM' ] ,
            [ 'name' => '韵达快运' , 'alias' => 'yunda' , 'code' => 'YDKY' ] ,
            [ 'name' => '其他快递' , 'alias' => '' , 'code' => '' ] ,
        ];
        if($getArray) return $data;//返回所有物流公司信息数组
        #2、根据匹配类型进行数组转码，将对应类型的键值转为键名
        $key = array_column($data,trim($type));
        $newData = array_combine($key,$data);
        #2、返回匹配内容
        return $newData[$name];
    }

    /**
     * Comment: 页面分享信息
     * Author: wlf
     * Date: 2022/08/08 10:29
     * @param $id   int     物流信息id（express表的id）
     * @return mixed
     */
    public static function getShareInfo($pageinfo){
        global $_W;
        $data     = [];
        $nickname = $_W['wlmember']['nickname'];
        $time     = date("Y-m-d H:i:s" , time());
        $sysname  = $_W['wlsetting']['base']['name'];
        //初始化参数
        if(strpos($pageinfo,'?') !== false ){
            $pagetype      = strstr($pageinfo , '?' , true);
            $pageparameter = strstr($pageinfo , '?');
        }else{
            $pagetype = $pageinfo;
            $pageparameter = [];
        }
        $pageparameter = substr($pageparameter , 1);
        $parameter     = explode("&" , $pageparameter);
        foreach ($parameter as $param) {
            $t             = explode('=' , $param);
            $newArr[$t[0]] = $t[1];
        }
        $parameter = $newArr;
        $type      = $parameter['type'];
        $id        = $parameter['id'];
        //积分商品特殊处理
        $gtype = $parameter['goodsType'];
        if(empty($id)){
            $id = $parameter['goods_id'];
            if(!empty($parameter['goods_id'])){
                $type = $parameter['goodsType'];
            }
        }
        if(empty($type)){
            $type = 2;
        }
        if (empty($pagetype)){
            $pagetype = 'pages/mainPages/index/index';
        }
        //根据路径 获取对应的分享信息
        switch ($pagetype) {
            case 'pages/subPages/goods/index':
                if ($gtype == 'integral' || $type == 8) {
                    $gid            = $parameter['goods_id'];
                    $goods          = Consumption::creditshop_goods_get($gid);
                    $consumptionset = $_W['wlsetting']['consumption'];
                    if ($consumptionset['goods_title']) {
                        $title         = $consumptionset['goods_title'];
                        $title         = str_replace('[昵称]',$nickname,$title);
                        $title         = str_replace('[时间]',$time,$title);
                        $title         = str_replace('[系统名称]',$sysname,$title);
                        $title         = str_replace('[商品名称]',$goods['title'],$title);
                        $title         = str_replace('[原价]',$goods['old_price'],$title);
                        $title         = str_replace('[所需积分]',$goods['use_credit1'],$title);
                        $title         = str_replace('[所需金额]',$goods['use_credit2'],$title);
                        $data['title'] = $title;
                    } else {
                        $data['title'] = $goods['title'];
                    }
                    if ($consumptionset['goods_desc']) {
                        $desc         = $consumptionset['goods_desc'];
                        $desc         = str_replace('[昵称]',$nickname,$desc);
                        $desc         = str_replace('[时间]',$time,$desc);
                        $desc         = str_replace('[系统名称]',$sysname,$desc);
                        $desc         = str_replace('[商品名称]',$goods['title'],$desc);
                        $desc         = str_replace('[原价]',$goods['old_price'],$desc);
                        $desc         = str_replace('[所需积分]',$goods['use_credit1'],$desc);
                        $desc         = str_replace('[所需金额]',$goods['use_credit2'],$desc);
                        $data['desc'] = $desc;
                    }
                    $data['img'] = !empty($consumptionset['goods_image']) ? $consumptionset['goods_image'] : $goods['thumb'];
                }  //积分商品
                else if ($type == 1) {
                    $set                  = Setting::agentsetting_read('rush');
                    $goods                = pdo_get('wlmerchant_rush_activity',['id' => $id]);
                    $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],-1);
                    $goods['vipprice']    = sprintf("%.2f",$goods['price'] - $goods['vipdiscount']);
                    $merchant             = pdo_get('wlmerchant_merchantdata',['id' => $goods['sid']],['storename']);
                    if ($goods['share_title'] || $goods['share_desc']) {
                        if ($goods['vipstatus'] == 1) {
                            $vipstatus = '会员特价';
                        } else if ($goods['vipstatus'] == 2) {
                            $vipstatus = '会员特供';
                        } else {
                            $vipstatus = '';
                        }
                        if ($goods['share_title']) {
                            $title = $goods['share_title'];
                            $title = str_replace('[昵称]',$nickname,$title);
                            $title = str_replace('[时间]',$time,$title);
                            $title = str_replace('[商品名称]',$goods['name'],$title);
                            $title = str_replace('[商户名称]',$merchant['storename'],$title);
                            $title = str_replace('[活动价]',$goods['price'],$title);
                            $title = str_replace('[特权类型]',$vipstatus,$title);
                            $title = str_replace('[会员价]',$goods['vipprice'],$title);
                            $title = str_replace('[原价]',$goods['oldprice'],$title);
                        }
                        if ($goods['share_desc']) {
                            $desc = $goods['share_desc'];
                            $desc = str_replace('[昵称]',$nickname,$desc);
                            $desc = str_replace('[时间]',$time,$desc);
                            $desc = str_replace('[商品名称]',$goods['name'],$desc);
                            $desc = str_replace('[商户名称]',$merchant['storename'],$desc);
                            $desc = str_replace('[活动价]',$goods['price'],$desc);
                            $desc = str_replace('[特权类型]',$vipstatus,$desc);
                            $desc = str_replace('[会员价]',$goods['vipprice'],$desc);
                            $desc = str_replace('[原价]',$goods['oldprice'],$desc);
                        }
                    }
                    if (empty($desc)) {
                        $desc = $set['share_desc'];
                        $desc = str_replace('[昵称]',$nickname,$desc);
                        $desc = str_replace('[时间]',$time,$desc);
                    }
                    $data['title'] = !empty($title) ? $title : $goods['name'];
                    $data['desc']  = $desc;
                    //1=公众号（默认）；2=h5；3=小程序
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($goods['share_wxapp_image']) ? $goods['share_wxapp_image'] : $goods['thumb'];
                    } else {
                        $data['img'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['thumb'];
                    }
                }       //抢购
                else if ($type == 2) {
                    $config               = Setting::agentsetting_read('groupon');
                    $goods                = pdo_get('wlmerchant_groupon_activity',['id' => $id]);
                    $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],-1);
                    $goods['vipprice']    = sprintf("%.2f",$goods['price'] - $goods['vipdiscount']);
                    $merchant             = pdo_get('wlmerchant_merchantdata',['id' => $goods['sid']],['storename']);
                    if ($goods['share_title'] || $goods['share_desc']) {
                        if ($goods['vipstatus'] == 1) {
                            $vipstatus = '会员特价';
                        } else if ($goods['vipstatus'] == 2) {
                            $vipstatus = '会员特供';
                        } else {
                            $vipstatus = '';
                        }
                        if ($goods['share_title']) {
                            $title = $goods['share_title'];
                            $title = str_replace('[昵称]',$nickname,$title);
                            $title = str_replace('[时间]',$time,$title);
                            $title = str_replace('[商品名称]',$goods['name'],$title);
                            $title = str_replace('[商户名称]',$merchant['storename'],$title);
                            $title = str_replace('[活动价]',$goods['price'],$title);
                            $title = str_replace('[特权类型]',$vipstatus,$title);
                            $title = str_replace('[会员价]',$goods['vipprice'],$title);
                            $title = str_replace('[原价]',$goods['oldprice'],$title);
                            $title = str_replace('[副标题]',$goods['subtitle'],$title);
                        }
                        if ($goods['share_desc']) {
                            $desc = $goods['share_desc'];
                            if (empty($desc)) {
                                $desc = $config['share_desc'];
                                $desc = str_replace('[系统名称]',$sysname,$desc);
                            }
                            $desc = str_replace('[昵称]',$nickname,$desc);
                            $desc = str_replace('[时间]',$time,$desc);
                            $desc = str_replace('[商品名称]',$goods['name'],$desc);
                            $desc = str_replace('[商户名称]',$merchant['storename'],$desc);
                            $desc = str_replace('[活动价]',$goods['price'],$desc);
                            $desc = str_replace('[特权类型]',$vipstatus,$desc);
                            $desc = str_replace('[会员价]',$goods['vipprice'],$desc);
                            $desc = str_replace('[原价]',$goods['oldprice'],$desc);
                            $desc = str_replace('[副标题]',$goods['subtitle'],$desc);
                        }
                    }
                    if (empty($desc)) {
                        $desc = $goods['subtitle'];
                    }
                    $data['title'] = !empty($title) ? $title : $goods['name'];
                    $data['desc']  = $desc;
                    //1=公众号（默认）；2=h5；3=小程序
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($goods['share_wxapp_image']) ? $goods['share_wxapp_image'] : $goods['thumb'];
                    } else {
                        $data['img'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['thumb'];
                    }
                }       //团购
                else if ($type == 3) {
                    $config   = Setting::agentsetting_read('fightgroup');
                    $goods    = pdo_get('wlmerchant_fightgroup_goods',['id' => $id]);
                    $merchant = pdo_get('wlmerchant_merchantdata',['id' => $goods['merchantid']],['storename']);
                    if ($goods['share_title']) {
                        $title = $goods['share_title'];
                        $title = str_replace('[昵称]',$nickname,$title);
                        $title = str_replace('[时间]',$time,$title);
                        $title = str_replace('[商品名称]',$goods['name'],$title);
                        $title = str_replace('[商户名称]',$merchant['storename'],$title);
                        $title = str_replace('[拼团价]',$goods['price'],$title);
                        $title = str_replace('[原价]',$goods['oldprice'],$title);
                        $title = str_replace('[单购价]',$goods['aloneprice'],$title);
                        $title = str_replace('[会员减免金额]',$goods['vipdiscount'],$title);
                        $title = str_replace('[开团人数]',$goods['peoplenum'],$title);
                    }
                    if ($goods['share_desc']) {
                        $desc = $goods['share_desc'];
                        if (empty($desc)) {
                            $desc = $config['share_desc'];
                            $desc = str_replace('[系统名称]',$sysname,$desc);
                        }
                        $desc = str_replace('[昵称]',$nickname,$desc);
                        $desc = str_replace('[时间]',$time,$desc);
                        $desc = str_replace('[商品名称]',$goods['name'],$desc);
                        $desc = str_replace('[商户名称]',$merchant['storename'],$desc);
                        $desc = str_replace('[拼团价]',$goods['price'],$desc);
                        $desc = str_replace('[原价]',$goods['oldprice'],$desc);
                        $desc = str_replace('[单购价]',$goods['aloneprice'],$desc);
                        $desc = str_replace('[会员减免金额]',$goods['vipdiscount'],$desc);
                        $desc = str_replace('[开团人数]',$goods['peoplenum'],$desc);
                    }
                    if (empty($desc)) {
                        $desc = $config['share_desc'];
                        $desc = str_replace('[昵称]',$nickname,$desc);
                        $desc = str_replace('[时间]',$time,$desc);
                    }
                    $data['title'] = !empty($title) ? $title : $goods['name'];
                    $data['desc']  = $desc;
                    //1=公众号（默认）；2=h5；3=小程序
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($goods['share_wxapp_image']) ? $goods['share_wxapp_image'] : $goods['logo'];
                    } else {
                        $data['img'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['logo'];
                    }
                }       //拼团
                else if ($type == 5) {
                    $config               = Setting::agentsetting_read('coupon');
                    $goods                = pdo_get('wlmerchant_couponlist',['id' => $id]);
                    $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],-1);
                    $goods['vipprice']    = sprintf("%.2f",$goods['price'] - $goods['vipdiscount']);
                    $data['title']        = !empty($goods['title']) ? $goods['title'] : $config['share_title'];
                    $data['desc']         = !empty($goods['sub_title']) ? $goods['sub_title'] : $config['share_desc'];
                    //1=公众号（默认）；2=h5；3=小程序
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($goods['wxapp_shareimg']) ? $goods['wxapp_shareimg'] : $goods['logo'];
                    } else {
                        $data['img'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['logo'];
                    }
                }       //卡券
                else if ($type == 7) {
                    $goods                = pdo_get('wlmerchant_bargain_activity',['id' => $id]);
                    $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],-1);
                    $goods['vipprice']    = sprintf("%.2f",$goods['price'] - $goods['vipdiscount']);
                    $merchant             = pdo_get('wlmerchant_merchantdata',['id' => $goods['sid']],['storename']);
                    if ($goods['share_title'] || $goods['share_desc']) {
                        if ($goods['vipstatus'] == 1) {
                            $vipstatus = '会员特价';
                        } else if ($goods['vipstatus'] == 2) {
                            $vipstatus = '会员特供';
                        } else {
                            $vipstatus = '';
                        }
                        if ($goods['share_title']) {
                            $title = $goods['share_title'];
                            $title = str_replace('[昵称]',$nickname,$title);
                            $title = str_replace('[时间]',$time,$title);
                            $title = str_replace('[商品名称]',$goods['name'],$title);
                            $title = str_replace('[商户名称]',$merchant['storename'],$title);
                            $title = str_replace('[原价]',$goods['oldprice'],$title);
                            $title = str_replace('[底价]',$goods['price'],$title);
                            $title = str_replace('[特权类型]',$vipstatus,$title);
                            $title = str_replace('[会员底价]',$goods['vipprice'],$title);
                        }
                        if ($goods['share_desc']) {
                            $desc = $goods['share_desc'];
                            $desc = str_replace('[昵称]',$nickname,$desc);
                            $desc = str_replace('[时间]',$time,$desc);
                            $desc = str_replace('[商品名称]',$goods['name'],$desc);
                            $desc = str_replace('[商户名称]',$merchant['storename'],$desc);
                            $desc = str_replace('[原价]',$goods['oldprice'],$desc);
                            $desc = str_replace('[底价]',$goods['price'],$desc);
                            $desc = str_replace('[特权类型]',$vipstatus,$desc);
                            $desc = str_replace('[会员底价]',$goods['vipprice'],$desc);
                        }
                    }
                    $data['title'] = !empty($title) ? $title : $goods['name'];
                    $data['desc']  = $desc;
                    //1=公众号（默认）；2=h5；3=小程序
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($goods['share_wxapp_image']) ? $goods['share_wxapp_image'] : $goods['thumb'];
                    } else {
                        $data['img'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['thumb'];
                    }
                }       //砍价
                break;//商品详情分享
            case 'pages/mainPages/index/diypage':
            case 'pages/mainPages/index/index':
                //页面类型：1=自定义页面;2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页;13=名片首页
                $diyset = Setting::agentsetting_read('diypageset');//装修设置信息
                switch ($type) {
                    //case 1: break;//自定义页面
                    case 2:
                        $id = $diyset['page_index'];
                        break;//商城首页
                    case 3:
                        $id = $diyset['page_rush'];
                        break;//抢购首页
                    case 4:
                        $id = $diyset['page_groupon'];
                        break;//团购首页
                    case 5:
                        $id = $diyset['page_wlcoupon'];
                        break;//卡券首页
                    case 6:
                        $id = $diyset['page_wlfightgroup'];
                        break;//拼团首页
                    case 7:
                        $id = $diyset['page_bargain'];
                        break;//砍价首页
                    case 8:
                        $id = $diyset['page_shop'];
                        break;//好店首页
                    //case 13:break;//名片首页
                    case 15:
                        $id = $diyset['page_recruit'];
                        break;//求职招聘
                    case 18:
                        $id = $diyset['page_housekeep'];
                        break;//家政服务
                }
                //判断id是否存在
                if ($id > 0) {
                    //通过id获取信息
                    $info          = Diy::getPage($id,false);
                    $data['title'] = $info['data']['page']['share_title'];
                    $data['desc']  = $info['data']['page']['share_description'];
                    $data['img']   = $info['data']['page']['share_image'];
                }
                if (empty($data['title'])) {
                    switch ($type) {
                        case 3:
                            $set           = Setting::agentsetting_read('rush');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//抢购首页
                        case 4:
                            $set           = Setting::agentsetting_read('groupon');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//团购首页
                        case 5:
                            $set           = Setting::agentsetting_read('coupon');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//卡券首页
                        case 6:
                            $set           = Setting::agentsetting_read('fightgroup');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//拼团首页
                        case 7:
                            $set           = Setting::agentsetting_read('bargainset');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//砍价首页
                        case 8:
                            $set           = Setting::wlsetting_read('agentsStoreSet');
                            $data['title'] = $set['merlist_title'];
                            $data['desc']  = $set['merlist_desc'];
                            $data['img']   = $set['merlist_image'];
                            break;//好店首页
                        case 13:
                            $set           = Setting::agentsetting_read('citycard');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//名片首页
                        case 15:
                            $set           = Setting::agentsetting_read('recruit_set');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            if($_W['source'] == 3){
                                $data['img']   = $set['share_wa_image'];
                            }else{
                                $data['img']   = $set['share_image'];
                            }
                            break;//求职招聘
                        case 16:
                            $set = Setting::wlsetting_read('dating_set');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            $data['img']   = $set['share_image'];
                            break;//相亲交友
                        case 18:
                            $set = Setting::agentsetting_read('housekeep');
                            $data['title'] = $set['share_title'];
                            $data['desc']  = $set['share_desc'];
                            if ($_W['source'] == 3) {
                                $data['img'] = $set['share_wxapp_image'];
                            }else{
                                $data['img'] = $set['share_image'];
                            }
                            break;//家政服务
                    }
                }
                break;//自定义页面分享
            case 'pages/subPages/integral/integralShop/integralShop':
                $set           = Setting::wlsetting_read('consumption');
                $data['title'] = $set['share_title'];
                $data['desc']  = $set['share_desc'];
                $data['img']   = $set['share_image'];
                break;//积分商城首页
            case 'pages/subPages/signdesk/index/index':
                $set           = Setting::wlsetting_read('wlsign');
                $data['title'] = $set['share_title'];
                $data['desc']  = $set['share_desc'];
                $data['img']   = $set['share_image'];
                break;//积分签到页面
            case 'pages/subPages/dealer/index/index':
            case 'pages/subPages/dealer/apply/apply':
                $set           = Setting::wlsetting_read('distribution');
                $data['title'] = $set['share_title'];
                $data['desc']  = $set['share_desc'];
                $data['img']   = $set['share_image'];
                break;//分销中心页面
            case 'pages/mainPages/store/index':
            case 'pages/subPages2/businessCenter/foodList/foodList':
			case 'pagesA/hotelhomepage/Hoteldetails/Hoteldetails':
                $set           = Setting::wlsetting_read('agentsStoreSet');
                $id            = $parameter['sid'] ? $parameter['sid'] : $parameter['storeid'];
				if(empty($id)){
					$id = $parameter['hotelid'];
				}
                $store         = pdo_get('wlmerchant_merchantdata',['id' => $id],[
                    'storename',
                    'logo',
                    'mobile',
                    'twolevel',
                    'address',
                    'describe',
                    'wxapp_shareimg'
                ]);
                $data['title'] = $set['merdetail_title'];
                $data['desc']  = $store['describe'] ? $store['describe'] : $set['merdetail_desc'];
                $data['img']   = $set['merdetail_image'];
                if (empty($data['img'])) {
                    if ($_W['source'] == 3) {
                        $data['img'] = !empty($store['wxapp_shareimg']) ? $store['wxapp_shareimg'] : $store['logo'];
                    } else {
                        $data['img'] = $store['logo'];
                    }
                }
                if ($data['title']) {
                    $data['title'] = str_replace('[昵称]',$nickname,$data['title']);
                    $data['title'] = str_replace('[时间]',$time,$data['title']);
                    $data['title'] = str_replace('[商户名称]',$store['storename'],$data['title']);
                    $data['title'] = str_replace('[商户电话]',$store['mobile'],$data['title']);
                } else {
                    $data['title'] = $store['storename'];
                }
                if ($data['desc']) {
                    $data['desc'] = str_replace('[昵称]',$nickname,$data['desc']);
                    $data['desc'] = str_replace('[时间]',$time,$data['desc']);
                    $data['desc'] = str_replace('[商户名称]',$store['storename'],$data['desc']);
                    $data['desc'] = str_replace('[商户电话]',$store['mobile'],$data['desc']);
                } else {
                    $data['desc'] = $store['address'];
                }
                break;//商户详情页面
            case 'pages/subPages/postDetails/postDetails':
            case 'pages/subPages/postDetails/postDetailscustomized':
                $set    = Setting::agentsetting_read('pocket');
                $inform = pdo_get('wlmerchant_pocket_informations',['id' => $id]);
                if ($inform['type']) {
                    $typename = pdo_getcolumn(PDO_NAME.'pocket_type',['id' => $inform['type']],'title');
                } else {
                    $typename = '官方公告';
                }
                if (empty($inform['avatar'])) {
                    if ($inform['mid']) {
                        $avatar = pdo_getcolumn(PDO_NAME.'member',['id' => $inform['mid']],'avatar');
                    } else {
                        $avatar = tomedia($set['kefu_avatar']);
                    }
                } else {
                    $avatar = $inform['avatar'];
                }
                $sharecontent  = str_replace("\r\n","",$inform['content']);
                $sharecontent  = str_replace("\n","",$sharecontent);
                $inform['img'] = unserialize($inform['img']);
                if (is_array($inform['img'])) {
                    $shareimg = tomedia($inform['img'][0]);
                }
                if ($inform['share_title']) {
                    $data['title'] = $inform['share_title'];
                } else if(Customized::init('pocket140') > 0) {
                    $data['title'] = '     ';
                } else {
                    $data['title'] = $inform['nickname'].'发布的'.$typename.'信息';
                }
                $data['desc'] = $sharecontent;
                if($_W['source'] == 3){
                    $data['img']  = $shareimg ? $shareimg : 'default';
                }else{
                    $data['img']  = $shareimg ? $shareimg : $avatar;
                }
                break;//掌上信息帖子分享
            case 'pages/mainPages/pocketIInformant/pocketIInformant':
            case 'pagesA/econdaryClassification/econdaryClassification':
            case 'pagesA/hotelhomepage/handheldsharing/handheldsharing':
                $set           = Setting::agentsetting_read('pocket');
                $data['title'] = $set['share_title'];
                $data['desc']  = $set['share_desc'];
                $data['img']   = $set['share_image'];
                break;//掌上信息首页分享
            case 'pages/subPages/bargin/barginDetail/barginDetail':
                $userid   = $parameter['bargin_id'];
                $userlist = pdo_get('wlmerchant_bargain_userlist',['id' => $userid],['activityid']);
                $activity = pdo_get('wlmerchant_bargain_activity',['id' => $userlist['activityid']]);
                $merchant = pdo_get('wlmerchant_merchantdata',['id' => $activity['sid']],['storename','enabled']);
                if ($activity['share_title'] || $activity['share_desc']) {
                    if ($activity['vipstatus'] == 1) {
                        $vipstatus = '会员特价';
                    } else if ($activity['vipstatus'] == 2) {
                        $vipstatus = '会员特供';
                    } else {
                        $vipstatus = '';
                    }
                    if ($activity['share_title']) {
                        $title = $activity['share_title'];
                        $title = str_replace('[昵称]',$nickname,$title);
                        $title = str_replace('[时间]',$time,$title);
                        $title = str_replace('[商品名称]',$activity['name'],$title);
                        $title = str_replace('[商户名称]',$merchant['storename'],$title);
                        $title = str_replace('[原价]',$activity['oldprice'],$title);
                        $title = str_replace('[底价]',$activity['price'],$title);
                        $title = str_replace('[特权类型]',$vipstatus,$title);
                        $title = str_replace('[会员底价]',$activity['vipprice'],$title);
                    }
                    if ($activity['share_desc']) {
                        $desc = $activity['share_desc'];
                        $desc = str_replace('[昵称]',$nickname,$desc);
                        $desc = str_replace('[时间]',$time,$desc);
                        $desc = str_replace('[商品名称]',$activity['name'],$desc);
                        $desc = str_replace('[商户名称]',$merchant['storename'],$desc);
                        $desc = str_replace('[原价]',$activity['oldprice'],$desc);
                        $desc = str_replace('[底价]',$activity['price'],$desc);
                        $desc = str_replace('[特权类型]',$vipstatus,$desc);
                        $desc = str_replace('[会员底价]',$activity['vipprice'],$desc);
                    }
                }
                $data['title'] = !empty($title) ? $title : $activity['name'];
                $data['desc']  = $desc;
                $data['img']   = !empty($activity['share_image']) ? $activity['share_image'] : $activity['thumb'];
                break;//砍价活动详情页面
            case 'pages/mainPages/memberCard/memberCard':
                $data['title'] = $_W['wlsetting']['halfcard']['share_title'];
                $data['desc']  = $_W['wlsetting']['halfcard']['share_desc'];
                $data['img']   = $_W['wlsetting']['halfcard']['share_image'];
                break;//一卡通首页
            case 'pages/subPages/group/assemble/assemble':
                $orderid = $parameter['orderid'];
                $groupid = $parameter['group_id'];
                $goods   = pdo_get('wlmerchant_fightgroup_goods',['id' => $id]);
                if ($orderid) {
                    $groupid = pdo_getcolumn(PDO_NAME.'order',['id' => $orderid],'fightgroupid');
                }
                $group  = pdo_get('wlmerchant_fightgroup_group',['id' => $groupid]);
                $config = Setting::agentsetting_read('fightgroup');
                if ($config['group_share_title']) {
                    $title = $config['group_share_title'];
                    $title = str_replace('[昵称]',$nickname,$title);
                    $title = str_replace('[时间]',$time,$title);
                    $title = str_replace('[商品名称]',$goods['name'],$title);
                    $title = str_replace('[组团价]',$goods['price'],$title);
                    $title = str_replace('[原价]',$goods['oldprice'],$title);
                    $title = str_replace('[组团人数]',$goods['peoplenum'],$title);
                    $title = str_replace('[缺少人数]',$group['lacknum'],$title);
                }
                if ($config['group_share_desc']) {
                    $desc = $config['group_share_desc'];
                    $desc = str_replace('[昵称]',$nickname,$desc);
                    $desc = str_replace('[时间]',$time,$desc);
                    $desc = str_replace('[商品名称]',$goods['name'],$desc);
                    $desc = str_replace('[组团价]',$goods['price'],$desc);
                    $desc = str_replace('[原价]',$goods['oldprice'],$desc);
                    $desc = str_replace('[组团人数]',$goods['peoplenum'],$desc);
                    $desc = str_replace('[缺少人数]',$group['lacknum'],$desc);
                }
                $data['title'] = !empty($title) ? $title : $goods['name'];
                $data['desc']  = $desc;
                $data['img']   = !empty($config['group_share_image']) ? $config['group_share_image'] : $goods['logo'];
                break;//拼团团详情页面
            case 'pages/subPages/businesscard/carddetail/carddetail':
                $cardid        = $parameter['cardid'];
                $cardinfo      = pdo_get('wlmerchant_citycard_lists',['id' => $cardid],[
                    'name',
                    'logo',
                    'company',
                    'branch',
                    'position',
                    'desc',
                    'one_class',
                    'two_class'
                ]);
                $onelevelname  = pdo_getcolumn(PDO_NAME.'citycard_cates',['id' => $cardinfo['one_class']],'name');
                $twolevelname  = pdo_getcolumn(PDO_NAME.'citycard_cates',['id' => $cardinfo['two_class']],'name');
                $set           = Setting::agentsetting_read('citycard');
                $data['title'] = $set['share_detail_title'];
                $data['desc']  = $set['share_detail_desc'];
                $data['img']   = $set['share_detail_image'];
                if ($data['title']) {
                    $title = $data['title'];
                    $title = str_replace('[昵称]',$nickname,$title);
                    $title = str_replace('[时间]',$time,$title);
                    $title = str_replace('[系统名称]',$sysname,$title);
                    $title = str_replace('[名片名称]',$cardinfo['name'],$title);
                    $title = str_replace('[公司]',$cardinfo['company'],$title);
                    $title = str_replace('[部门]',$cardinfo['branch'],$title);
                    $title = str_replace('[职务]',$cardinfo['position'],$title);
                    $title = str_replace('[介绍]',$cardinfo['desc'],$title);
                    $title = str_replace('[一级分类]',$onelevelname,$title);
                    $title = str_replace('[二级分类]',$twolevelname,$title);
                }
                if ($data['desc']) {
                    $desc = $data['desc'];
                    $desc = str_replace('[昵称]',$nickname,$desc);
                    $desc = str_replace('[时间]',$time,$desc);
                    $desc = str_replace('[系统名称]',$sysname,$desc);
                    $desc = str_replace('[名片名称]',$cardinfo['name'],$desc);
                    $desc = str_replace('[公司]',$cardinfo['company'],$desc);
                    $desc = str_replace('[部门]',$cardinfo['branch'],$desc);
                    $desc = str_replace('[职务]',$cardinfo['position'],$desc);
                    $desc = str_replace('[介绍]',$cardinfo['desc'],$desc);
                    $desc = str_replace('[一级分类]',$onelevelname,$desc);
                    $desc = str_replace('[二级分类]',$twolevelname,$desc);
                }
                $data['title'] = $title;
                $data['desc']  = $desc;
                $data['img']   = $data['img'] ? tomedia($data['img']) : tomedia($cardinfo['logo']);
                break;//名片详情分享
            case 'pages/mainPages/headline/headlineDetail':
                $headlineId = $parameter['headline_id'] ? : $parameter['id'];
                $headline   = pdo_get(PDO_NAME."headline_content",['id' => $headlineId],[
                    'title',
                    'display_img',
                    'summary'
                ]);
                $data['title'] = $headline['title'];
                $data['desc']  = $headline['summary'];
                $data['img']   = tomedia($headline['display_img']);
                break;//头条分享信息
            case 'pages/subPages/special/rushspeci/rushspeci':
                $rushspeci     = pdo_get(PDO_NAME."rush_special",['id' => $parameter['id']],[
                    'share_title',
                    'share_desc',
                    'thumb','share_img'
                ]);
                $data['title'] = $rushspeci['share_title'];
                $data['desc']  = $rushspeci['share_desc'];
                $data['img']   = empty($rushspeci['share_img']) ? tomedia($rushspeci['thumb']) : tomedia($rushspeci['share_img']);
                break;//抢购专题页面
            case 'pages/subPages2/businessCenter/businessCenter':
                $deliverybase  = Setting::agentsetting_read('citydelivery');
                $data['title'] = $deliverybase['share_title'];
                $data['desc']  = $deliverybase['share_desc'];
                $data['img']   = $deliverybase['share_image'];
                break;//同城配送首页
            case 'pages/subPages2/businessCenter/foodIntroduced/foodIntroduced':
                $goods = pdo_get('wlmerchant_delivery_activity',['id' => $parameter['id']],[
                    'name',
                    'price',
                    'oldprice',
                    'thumb',
                    'vipstatus',
                    'vipdiscount',
                    'share_title',
                    'share_image',
                    'share_desc'
                ]);
                if ($goods['vipstatus'] == 1) {
                    $vipstatus = '会员特价';
                } else if ($goods['vipstatus'] == 2) {
                    $vipstatus = '会员特供';
                } else {
                    $vipstatus = '';
                }
                //标题
                if (empty($goods['share_title'])) {
                    $data['title'] = $goods['name'];
                } else {
                    $data['title'] = $goods['share_title'];
                    $data['title'] = str_replace('[昵称]',$nickname,$data['title']);
                    $data['title'] = str_replace('[时间]',$time,$data['title']);
                    $data['title'] = str_replace('[商品名]',$goods['name'],$data['title']);
                    $data['title'] = str_replace('[活动价]',$goods['price'],$data['title']);
                    $data['title'] = str_replace('[特权类型]',$vipstatus,$data['title']);
                    $data['title'] = str_replace('[特权折扣]',$goods['vipdiscount'],$data['title']);
                    $data['title'] = str_replace('[市场价]',$goods['oldprice'],$data['title']);
                }
                //图片
                if (empty($goods['share_image'])) {
                    $data['img'] = tomedia($goods['thumb']);
                } else {
                    $data['img'] = tomedia($goods['share_image']);
                }
                //描述
                if (empty($goods['share_desc'])) {
                    $data['desc'] = '快来购买吧~';
                } else {
                    $data['desc'] = $goods['share_desc'];
                    $data['desc'] = str_replace('[昵称]',$nickname,$data['desc']);
                    $data['desc'] = str_replace('[时间]',$time,$data['desc']);
                    $data['desc'] = str_replace('[商品名]',$goods['name'],$data['desc']);
                    $data['desc'] = str_replace('[活动价]',$goods['price'],$data['desc']);
                    $data['desc'] = str_replace('[特权类型]',$vipstatus,$data['desc']);
                    $data['desc'] = str_replace('[特权折扣]',$goods['vipdiscount'],$data['desc']);
                    $data['desc'] = str_replace('[市场价]',$goods['oldprice'],$data['desc']);
                }
                break;//同城配送商品
            case 'pages/subPages2/phoneBook/logistics/logistics':
                $yellowpage    = pdo_get('wlmerchant_yellowpage_lists',['id' => $parameter['id']],[
                    'name',
                    'logo',
                    'desc'
                ]);
                $data['title'] = $yellowpage['name'];
                $data['desc']  = $yellowpage['desc'];
                $data['img']   = tomedia($yellowpage['logo']);
                break;//黄页114详情
            case 'pages/subPages2/phoneBook/phoneBook':
            case 'pages/subPages2/phoneBook/phoneClass/phoneClass':
                $yellowbase    = Setting::agentsetting_read('yellowpage');
                $data['title'] = $yellowbase['share_title'];
                $data['desc']  = $yellowbase['share_desc'];
                $data['img']   = $yellowbase['share_image'];
                break;//黄页114首页
            case 'pages/subPages2/drawGame/drawGame':
                $info          = pdo_get(PDO_NAME."draw",['id' => $id],['share_title','share_desc','share_img','title']);
                $data['title'] = $info['share_title'] ?  :  $info['title'];
                $data['img']   = tomedia($info['share_img']);
                $data['desc']  = $info['share_desc'];
                break;//抽奖详情页
            case 'pages/subPages2/coursegoods/coursegoods':
                $activity = pdo_get('wlmerchant_activitylist',['id' => $id],[
                    'share_title',
                    'share_desc',
                    'share_image',
                    'title',
                    'thumb',
                    'vipstatus',
                    'vipprice',
                    'sid',
                    'optionstatus',
                    'price'
                ]);
                $merchant = pdo_get('wlmerchant_merchantdata',['id' => $activity['sid']],['storename']);
                if (!empty($activity['share_title']) || !empty($activity['share_desc'])) {
                    if ($activity['vipstatus'] == 1) {
                        $vipstatus = '会员特价';
                    } else if ($activity['vipstatus'] == 2) {
                        $vipstatus = '会员特供';
                    } else {
                        $vipstatus = '';
                    }
                    if ($activity['optionstatus'] > 0) {
                        $specs             = pdo_getall('wlmerchant_activity_spec',['activityid' => $id],['price']);
                        $prices            = array_column($specs,'price');
                        $activity['price'] = min($prices).'起';
                    }
                    if (!empty($activity['share_title'])) {
                        $title         = $activity['share_title'];
                        $title         = str_replace('[昵称]',$nickname,$title);
                        $title         = str_replace('[时间]',$time,$title);
                        $title         = str_replace('[活动名称]',$activity['title'],$title);
                        $title         = str_replace('[商户名称]',$merchant['storename'],$title);
                        $title         = str_replace('[报名价]',$activity['price'],$title);
                        $title         = str_replace('[特权类型]',$vipstatus,$title);
                        $title         = str_replace('[会员减免]',$activity['vipprice'],$title);
                        $data['title'] = $title;
                    }
                    if (!empty($activity['share_desc'])) {
                        $desc         = $activity['share_desc'];
                        $desc         = str_replace('[昵称]',$nickname,$desc);
                        $desc         = str_replace('[时间]',$time,$desc);
                        $desc         = str_replace('[活动名称]',$activity['title'],$desc);
                        $desc         = str_replace('[商户名称]',$merchant['storename'],$desc);
                        $desc         = str_replace('[报名价]',$activity['price'],$desc);
                        $desc         = str_replace('[特权类型]',$vipstatus,$desc);
                        $desc         = str_replace('[会员减免]',$activity['vipprice'],$desc);
                        $data['desc'] = $desc;
                    }
                }
                if (empty($data['title'])) {
                    $data['title'] = $activity['title'];
                }
                $data['img'] = !empty($activity['share_image']) ? tomedia($activity['share_image']) : tomedia($activity['thumb']);
                break;//活动详情页面
            case 'pages/subPages2/coursegoods/localindex/localindex':
                $settings      = Setting::agentsetting_read('activity');
                $data['title'] = $settings['share_title'];
                $data['desc']  = $settings['share_desc'];
                $data['img']   = $settings['share_image'];
                break;//活动列表页面
            case 'pages/subPages2/hirePlatform/recruitmentDetails/recruitmentDetails':
                $set           = Setting::agentsetting_read('recruit_set');
                $recruit = pdo_get(PDO_NAME."recruit_recruit",['id' => $id],[
                    'title',
                    'recruitment_type',
                    'release_mid',
                    'release_sid',
                    'job_description'
                ]);
                //获取发布方信息
                if ($recruit['recruitment_type'] == 1) $logo = pdo_getcolumn(PDO_NAME."member",['id' => $recruit['release_mid']],'avatar');
                else $logo = pdo_getcolumn(PDO_NAME."merchantdata",['id' => $recruit['release_sid']],'logo');    //企业招聘

                if(!empty($set['info_share_title'])){
                    $title         = $set['info_share_title'];
                    $title         = str_replace('[昵称]',$nickname,$title);
                    $title         = str_replace('[时间]',$time,$title);
                    $title         = str_replace('[系统名称]',$sysname,$title);
                    $title         = str_replace('[岗位名]',$recruit['title'],$title);
                    $title         = str_replace('[岗位描述]',$recruit['job_description'],$title);
                    $data['title'] = $title;
                }else{
                    $data['title'] = $recruit['title'].'——'.$sysname.'招聘';
                }
                if(!empty($set['info_share_desc'])){
                    $desc         = $set['info_share_desc'];
                    $desc         = str_replace('[昵称]',$nickname,$desc);
                    $desc         = str_replace('[时间]',$time,$desc);
                    $desc         = str_replace('[系统名称]',$sysname,$desc);
                    $desc         = str_replace('[岗位名]',$recruit['title'],$desc);
                    $desc         = str_replace('[岗位描述]',$recruit['job_description'],$desc);
                    $data['desc'] = $desc;
                }else{
                    $data['desc']  = $recruit['job_description'];
                }
                if($_W['source'] == 3){
                    $data['img'] = !empty($set['info_share_wa_image']) ? tomedia($set['info_share_wa_image']) : tomedia($logo);
                }else{
                    $data['img'] = !empty($set['info_share_image']) ? tomedia($set['info_share_image']) : tomedia($logo);
                }
                break;//招聘详情
            case 'pages/subPages2/blindDate/member/detail':
                $dating = pdo_get(PDO_NAME."dating_member",['id' => $id],['mid','introduce']);
                [$dating['nickname'],$dating['avatar']] = Dating::handleUserInfo($dating['mid']);
                //获取发布方信息
                $data['title'] = $dating['nickname'];
                $data['desc']  = $dating['introduce'] ? : '这个人很懒，未留下自我介绍！';
                $data['img']   = tomedia($dating['avatar']);
                break;//相亲交友相亲
            case 'pages/subPages2/homemaking/homemakingDetails/homemakingDetails':
                $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('share_image','thumb','share_wxapp_image','share_title','share_desc','title','pricetype','price','unit'));
                if($service['pricetype'] == 0){
                    $price = '价格面议';
                }else if($service['pricetype'] == 1){
                    $price = '预约金:￥'.$service['price'].'/'.$service['unit'];
                }else if($service['pricetype'] == 2){
                    $price = '￥'.$service['price'].'/'.$service['unit'];
                }
                if ($_W['source'] == 3) {
                    $data['img'] = !empty($service['share_wxapp_image']) ? $service['share_wxapp_image'] : $service['thumb'];
                } else {
                    $data['img'] = !empty($service['share_image']) ? $service['share_image'] : $service['thumb'];
                }
                if ($service['share_title']) {
                    $title = $service['share_title'];
                    $title = str_replace('[昵称]',$nickname,$title);
                    $title = str_replace('[时间]',$time,$title);
                    $title = str_replace('[项目名称]',$service['title'],$title);
                    $title = str_replace('[价格]',$price,$title);
                }
                if ($service['share_desc']) {
                    $desc = $service['share_desc'];
                    $desc = str_replace('[昵称]',$nickname,$desc);
                    $desc = str_replace('[时间]',$time,$desc);
                    $desc = str_replace('[项目名称]',$service['title'],$desc);
                    $desc = str_replace('[价格]',$price,$desc);
                }
                $data['title'] = !empty($title) ? $title : $service['title'];
                $data['desc']  = !empty($desc) ? $desc  : '快来看看吧~';
                break;//家政服务详情页面
            case 'pages/subPages2/hitchRide/index/index':
                $deliverybase  = Setting::agentsetting_read('vehicle_set');
                $data['title'] = $deliverybase['share_title'];
                $data['desc']  = $deliverybase['share_desc'];
                $data['img']   = $deliverybase['share_image'];
                break;//顺风车首页
            case 'pages/subPages2/hitchRide/hitchRideDetails/hitchRideDetails':
                $vehicle = pdo_get('wlmerchant_vehicle',array('id' => $id),array('start_address','end_address','start_time','mid'));
                $avatar =  pdo_getcolumn(PDO_NAME.'member',array('id'=>$vehicle['mid']),'avatar');
                $data['title'] = '目的地:'.$vehicle['end_address'];
                $data['desc']  = date('m-d H:i',$vehicle['start_time']).'从'.$vehicle['start_address'].'前往'.$vehicle['end_address'];
                if($_W['source'] == 3){
                    $data['img']   = 'default';
                }else{
                    $data['img']   = tomedia($avatar);
                }
                break;//顺风车详情
            case 'pages/subPages/redpacket/redsquare':  //红包广场
            case 'pages/subPages/redpacket/myredpacket':
                $redset = Setting::wlsetting_read('red_pack_set');
                $data['title'] = $redset['share_title'];
                $data['desc']  = $redset['share_desc'];
                $data['img']   = $redset['share_image'];
                break;//我的红包
            case 'pages/subPages2/voucherCenter/voucherCenter':
                $redset = Setting::wlsetting_read('mobilerecharge');
                $data['title'] = $redset['share_title'];
                $data['desc']  = $redset['share_desc'];
                if ($_W['source'] == 3) {
                    $data['img'] = !empty($redset['share_wxapp_image']) ? $redset['share_wxapp_image'] : $redset['share_image'];
                } else {
                    $data['img']   = $redset['share_image'];
                }
                break;//话费充值
            case 'pages/subPages2/lottery/lotteryIndex/lotteryIndex':
            case 'pages/subPages2/lottery/lotteryList/lotteryList':
                $draw = pdo_get('wlmerchant_luckydraw',array('id' => $id),array('title','share_title','share_desc','share_image','share_wxapp_image'));;
                $data['title'] = $draw['share_title'] ? : $draw['title'];
                $data['desc']  = $draw['share_desc'];
                if ($_W['source'] == 3) {
                    $data['img'] = !empty($draw['share_wxapp_image']) ? $draw['share_wxapp_image'] : $draw['share_image'];
                } else {
                    $data['img']   = $draw['share_image'];
                }
                if ($data['title']) {
                    $data['title'] = str_replace('[活动名称]',$draw['title'],$data['title']);
                }
                if ($data['desc']) {
                    $data['desc'] = str_replace('[活动名称]',$draw['title'],$data['desc']);
                }
                break;//锦鲤抽奖
            case 'pages/subPages2/houseproperty/realestatedetails/realestatedetails':
                $draw = pdo_get('wlmerchant_new_house',array('id' => $parameter['house_id']),array('title','cover_image','share_title','share_logo','share_describe'));
                $data['title'] = $draw['share_title'] ? : $draw['title'];
                $data['desc']  = $draw['share_describe'];
                $data['img'] = !empty($draw['share_logo']) ? $draw['share_logo'] : $draw['cover_image'];
                break;//房产新房
            case 'pages/subPages2/houseproperty/secondhanddetails/secondhanddetails':
                $draw = pdo_get('wlmerchant_old_house',array('id' => $parameter['house_id']),array('title','cover_image','share_title','share_logo','share_describe'));;
                $data['title'] = $draw['share_title'] ? : $draw['title'];
                $data['desc']  = $draw['share_describe'];
                $data['img'] = !empty($draw['share_logo']) ? $draw['share_logo'] : $draw['cover_image'];
                break;//房产二手房
            case 'pages/subPages2/houseproperty/rentaldetailspage/rentaldetailspage':
                $draw = pdo_get('wlmerchant_renting',array('id' => $parameter['house_id']),array('title','cover_image','share_title','share_logo','share_describe'));;
                $data['title'] = $draw['share_title'] ? : $draw['title'];
                $data['desc']  = $draw['share_describe'];
                $data['img'] = !empty($draw['share_logo']) ? $draw['share_logo'] : $draw['cover_image'];
                break;//房产出租房
            case 'pages/subPages2/houseproperty/houseproperty':
            case 'pages/subPages2/houseproperty/anewhouse/anewhouse':
            case 'pages/subPages2/houseproperty/secondhandhouse/secondhandhouse':
            case 'pages/subPages2/houseproperty/rentahouse/rentahouse':
            case 'pages/subPages2/houseproperty/personalcenter/personalcenter':
                $deliverybase  = Setting::agentsetting_read('house');
                $data['title'] = $deliverybase['share_title'];
                $data['desc']  = $deliverybase['share_desc'];
                $data['img']   = $deliverybase['share_image'];
                break;//房产首页
            case 'pages/subPages/friendshelp/friendshelp':
                $call = pdo_get('wlmerchant_call',array('id' => $parameter['id']),array('title','share_image','share_wxapp_image','share_title','share_desc'));
                $data['title'] = $call['share_title'] ? : $call['title'];
                $data['desc']  = $call['share_desc'];
                $data['img'] = $_W['source'] != 3 ? $call['share_image'] : $call['share_wxapp_image'];
                //文本替换
                $data['title'] = str_replace('[活动名称]',$call['title'],$data['title']);
                $data['desc'] = str_replace('[活动名称]',$call['title'],$data['desc']);
                break;//分享有礼
        }
        //获取默认分享设置
        if($_W['aid'] > 0) $settings = Setting::agentsetting_read('share_set');//代理商分享信息
        if(!$settings['share_title']) $settings = Setting::wlsetting_read('share');//不存在代理商分享信息时获取平台分享信息
        if (empty($data['title'])) {
            $data['title'] = $settings['share_title'];
        } else {
            $data['title'] = str_replace('[昵称]' , $nickname , $data['title']);
            $data['title'] = str_replace('[时间]' , $time , $data['title']);
            $data['title'] = str_replace('[系统名称]' , $sysname , $data['title']);
        }
        if (empty($data['desc'])) {
            $data['desc'] = $settings['share_desc'];
        } else {
            $data['desc'] = str_replace('[昵称]' , $nickname , $data['desc']);
            $data['desc'] = str_replace('[时间]' , $time , $data['desc']);
            $data['desc'] = str_replace('[系统名称]' , $sysname , $data['desc']);
        }
        if (empty($data['img'])) {
            $data['img'] = tomedia($settings['share_image']);
            if ($_W['source'] == 3) {
                $data['img'] = !empty($settings['wxapp_share_image']) ? tomedia($settings['wxapp_share_image']) : tomedia($settings['share_image']);;
            } else {
                $data['img'] = tomedia($settings['share_image']);
            }
        }else if($data['img'] == 'default'){
            $data['img'] = '';
        } else {
            $data['img'] = tomedia($data['img']);
        }
        $data['mpurl'] = $pageinfo;

        return $data;
    }

}
