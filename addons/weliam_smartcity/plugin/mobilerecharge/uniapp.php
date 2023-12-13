<?php
defined('IN_IA') or exit('Access Denied');

class MobilerechargeModuleUniapp extends Uniapp {

    /**
     * Comment: 充值页面初始化
     * Author: wlf
     * Date: 2021/11/02 11:39
     */

    public function rechargePageInfo(){
        global $_GPC, $_W;
        $set = Setting::wlsetting_read('mobilerecharge');
        if(empty($set['rechargePlatform'])){
            $this->renderError("功能已关闭",['url'=>h5_url('pages/mainPages/index/index')]);
        }
        //支付项目
        if($set['rechargePlatform'] == 1){
            $pricediv = [];
            if($set['slowjing50price'] > 0){
                $pricediv[1][] = [
                    'money' => 50,
                    'prcie' => $set['slowjing50price'],
                    'vipprice' => $set['slowjing50vip']
                ];
            }
            if($set['slowjing100price'] > 0) {
                $pricediv[1][] = [
                    'money' => 100,
                    'prcie' => $set['slowjing100price'],
                    'vipprice' => $set['slowjing100vip']
                ];
            }
            if($set['slowjing200price'] > 0) {
                $pricediv[1][] = [
                    'money' => 200,
                    'prcie' => $set['slowjing200price'],
                    'vipprice' => $set['slowjing200vip']
                ];
            }

            if($set['fastjing50price'] > 0) {
                $pricediv[2][] = [
                    'money' => 50,
                    'prcie' => $set['fastjing50price'],
                    'vipprice' => $set['fastjing50vip']
                ];
            }
            if($set['fastjing100price'] > 0) {
                $pricediv[2][] = [
                'money' => 100,
                'prcie' => $set['fastjing100price'],
                'vipprice' => $set['fastjing100vip']
            ];
            }
            if($set['fastjing200price'] > 0) {
                $pricediv[2][] = [
                    'money' => 200,
                    'prcie' => $set['fastjing200price'],
                    'vipprice' => $set['fastjing200vip']
                ];
            }
            if($set['mostjing50price'] > 0) {
                $pricediv[3][] = [
                    'money' => 50,
                    'prcie' => $set['mostjing50price'],
                    'vipprice' => $set['mostjing50vip']
                ];
            }
            if($set['mostjing100price'] > 0) {
                $pricediv[3][] = [
                    'money' => 100,
                    'prcie' => $set['mostjing100price'],
                    'vipprice' => $set['mostjing100vip']
                ];
            }
            if($set['mostjing200price'] > 0) {
                $pricediv[3][] = [
                    'money' => 200,
                    'prcie' => $set['mostjing200price'],
                    'vipprice' => $set['mostjing200vip']
                ];
            }
            $data['pricediv'] = $pricediv;
            $data['slowre'] = $set['slowre'] ? : 0 ;
            $data['slowtext'] = $set['slowtext'];
            $data['fastre'] = $set['fastre'] ? : 0 ;
            $data['fasttext'] = $set['fasttext'];
            $data['mostre'] = $set['mostre'] ? : 0 ;
            $data['mosttext'] = $set['mosttext'];

        }
        //会员状态
        $data['vipflag'] = WeliamWeChat::VipVerification($_W['mid'] , true);
        //幻灯片
        $advs = pdo_getall('wlmerchant_adv',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type'=> 19 ,'enabled'=>1),array('thumb','link'),'','displayorder DESC');
        if(empty($advs) && $_W['aid'] > 0 ){
            $advs = pdo_getall('wlmerchant_adv',array('uniacid' => $_W['uniacid'],'aid' => 0,'type'=> 19 ,'enabled'=>1),array('thumb','link'),'','displayorder DESC');
        }
        if(!empty($advs)){
            foreach ($advs as &$ad) {
                $ad['thumb'] = tomedia($ad['thumb']);
            }
        }
        $data['advs'] = $advs;
        //二维码
        $source = $_W['source'];
        if($set['qrcodetype'] > 0 ){
            //公众号关注路径
            $qrcode = Mobilerecharge::getgzqrcode($_W['mid']);
            $path = $qrcode['url'];
        }else{
            $path = 'pages/subPages2/voucherCenter/voucherCenter?head_id='.$_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        $filename = md5('mid' . $_W['mid'] . 'source' . $source);//保证图片唯一性，每种渠道，类型海报二维码都不一致
        if ($source == 3 && $set['qrcodetype'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        } else {
            //公众号/H5
            $qrCodeLink = Poster::qrcodeimg($path , $filename);
        }
        $data['qrimg'] = tomedia($qrCodeLink);

        $this->renderSuccess('话费充值页面初始化',$data);
    }

    /**
     * Comment: 订单提交接口
     * Author: wlf
     * Date: 2021/11/02 13:39
     */
    public function createMrechargeOrder(){
        global $_GPC, $_W;
        $mobile = $_GPC['mobile']; //充值电话
        $type = $_GPC['type']; //充值方式
        $money = $_GPC['money']; //充值面额
        if(empty($mobile)){
            $this->renderError("请输入充值手机号");
        }
        if(empty($type) || empty($money)){
            $this->renderError("参数错误，请刷新重试");
        }
        $set = Setting::wlsetting_read('mobilerecharge');
        if(empty($set['rechargePlatform'])){
            $this->renderError("功能已关闭",['url'=>h5_url('pages/mainPages/index/index')]);
        }
        //计算充值金额
        if($set['rechargePlatform'] == 1){
            //判断渠道
            $checkch = Mobilerecharge::sljOrderStatus($mobile,$type,$money);
            if($checkch['error'] > 0){
                $this->renderError($checkch['msg']);
            }
            if($type == 1){
                $jing50price = $set['slowjing50price'];
                $jing50vip = $set['slowjing50vip'];
                $jing100price = $set['slowjing100price'];
                $jing100vip = $set['slowjing100vip'];
                $jing200price = $set['slowjing200price'];
                $jing200vip = $set['slowjing200vip'];
            }else if($type == 2){
                $jing50price = $set['fastjing50price'];
                $jing50vip = $set['fastjing50vip'];
                $jing100price = $set['fastjing100price'];
                $jing100vip = $set['fastjing100vip'];
                $jing200price = $set['fastjing200price'];
                $jing200vip = $set['fastjing200vip'];
            }else if($type == 3){
                $jing50price = $set['mostjing50price'];
                $jing50vip = $set['mostjing50vip'];
                $jing100price = $set['mostjing100price'];
                $jing100vip = $set['mostjing100vip'];
                $jing200price = $set['mostjing200price'];
                $jing200vip = $set['mostjing200vip'];
            }
            if($money == 50){
                $price = $jing50price;
                $vipprice = $jing50vip;
            }else if($money == 100){
                $price = $jing100price;
                $vipprice = $jing100vip;
            }else if($money == 200){
                $price = $jing200price;
                $vipprice = $jing200vip;
            }
            if($vipprice > 0){
                $halfflag = WeliamWeChat::VipVerification($_W['mid'] , true);
                if($halfflag > 0){
                    $price = $vipprice;
                }
            }
        }
        //校验金额
        if($price < 0.01){
            $this->renderError("充值金额错误，请刷新重试");
        }
        $orderdata = [
            'uniacid' => $_W['uniacid'],
            'aid' => $_W['aid'],
            'orderno' => createUniontid(),
            'money' => $money,
            'channel' => $set['rechargePlatform'],
            'type' => $type,
            'price' => $price,
            'status' => 0,
            'createtime' => time(),
            'mid' => $_W['mid'],
            'mobile' => $mobile
        ];
        pdo_insert(PDO_NAME.'mrecharge_order',$orderdata);
        $orderid = pdo_insertid();
        if (empty($orderid)) {
            $this->renderError('创建订单失败，请刷新重试');
        }
        $this->renderSuccess('订单信息',['orderid' => $orderid]);
    }

    /**
     * Comment: 用户订单列表
     * Author: wlf
     * Date: 2021/11/02 13:39
     */
    public function mrechargeOrderList(){
        global $_GPC, $_W;
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 10 - 10;
        $status = $_GPC['status'];
        $whewe = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']}";
        if(empty($status)){
            $whewe .= " AND status > 0 ";
        }else{
            $whewe .= " AND status = {$status} ";
        }
        $records = pdo_fetchall("SELECT orderno,type,price,money,reason,mobile,status FROM " . tablename("wlmerchant_mrecharge_order") . $whewe ."  ORDER BY createtime DESC LIMIT {$page_start},10");
        $allnum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_mrecharge_order') .$whewe);

        if (!empty($records)) {
            foreach ($records as &$red){
                $isChinaMobile = "/^134[0-8]\d{7}$|^(?:13[5-9]|147|15[0-27-9]|178|18[2-478])\d{8}$/"; //移动方面最新答复
                $isChinaUnion = "/^(?:13[0-2]|145|15[56]|176|18[56])\d{8}$/"; //向联通微博确认并未回复
                $isChinaTelcom = "/^(?:133|153|177|173|18[019])\d{8}$/"; //1349号段 电信方面没给出答复，视作不存在
                if(preg_match($isChinaMobile, $red['mobile'])){
                    $red['operator'] = '1';
                }else if(preg_match($isChinaUnion, $red['mobile'])){
                    $red['operator'] = '2';
                }else if(preg_match($isChinaTelcom, $red['mobile'])){
                    $red['operator'] = '3';
                }else{
                    $red['operator'] = '4';
                }
            }
            $records = array_values($records);
        }
        $data['list']      = $records;
        $data['pagetotal'] = ceil($allnum / 10);

        $this->renderSuccess('充值记录' , $data);
    }


}