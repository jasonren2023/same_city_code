<?php
/**
 * Comment: 卡片建立的操作
 * Author: ZZW
 * Date: 2018/11/30
 * Time: 15:17
 */
defined('IN_IA') or exit('Access Denied');

class Poster
{
    /**
     * Comment: 分销邀请购买商品海报生成【1】
     * Author: zzw
     * Date: 2019/11/8 10:09
     * @param int    $id        商品id
     * @param int    $source    渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg     背景
     * @param int    $goodsType 商品类型
     * @return string
     */
    public static function createDistriPoster($id , $source , $bgimg = '' , $goodsType)
    {
        global $_W;
        #1、基本信息获取
        $disSet = is_array($_W['wlsetting']['distribution']) ? $_W['wlsetting']['distribution'] : Setting::wlsetting_read("distribution");
        #2、生成链接信息
        if ($disSet['posterqr'] == 1) {
            //公众号关注路径
            $path = Distribution::getgzqrcode($_W['mid'])['url'] ? : '';
        }
        else {
            //普通二维码路径
            if ($goodsType == 8) {
                //积分商品   pages/subPages/goods/index?goods_id=18&goodsType=integral&i=26
                $path = 'pages/subPages/goods/index?goods_id=' . $id . '&goodsType=integral&head_id='.$_W['mid'];//基本路径，也是小程序路径
            }
            else {
                //其他商品   pages/subPages/goods/index?i=26&i=26&id=84&type=1
                $path = 'pages/subPages/goods/index?id=' . $id . '&type=' . $goodsType.'&head_id='.$_W['mid'];//基本路径，也是小程序路径
            }
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'posterqr' . $disSet['posterqr'] . 'goods_type' . $goodsType . 'bgimg' . $bgimg);//保证图片唯一性，每种渠道，类型海报二维码都不一致
        if ($source == 3 && $disSet['posterqr'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $defaultBg = URL_H5_RESOURCE . 'poster/distposterbg.jpg';
        $poster    = [
            'bg'       => $defaultBg ,
            'qrimg'    => tomedia($qrCodeLink) ,
            'nickname' => $_W['wlmember']['nickname'] ? : '' ,
            'avatar'   => $_W['wlmember']['avatar'] ? : '' ,
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        if (p('diyposter') && !empty($_W['wlsetting']['diyposter']['distpid'])) {
            //使用自定义海报信息
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $_W['wlsetting']['diyposter']['distpid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'posterqr' . $disSet['posterqr'] . 'goods_type' . $goodsType . 'bgimg' . $bgimg . 'disposter_id' . $_W['wlsetting']['diyposter']['distpid'] . 'flag');
        }
        else {
            //使用默认海报
            $poster['data'] = '[{"left":"115px","top":"93px","type":"head","width":"86px","height":"86px"},
            {"left":"115px","top":"186px","type":"nickname","width":"86px","height":"29px","size":"10px","color":"#999999","words":"昵称","align":"center"},
            {"left":"115px","top":"406px","type":"qr","width":"90px","height":"90px","size":""}]';
        }
        #5、生成海报信息
        $res = Tools::createPoster($poster , $filename , $_W['wlmember']);
        return $res;
    }
    /**
     * Comment: 分销合伙人邀请会员海报生成【2】
     * Author: zzw
     * Date: 2019/11/7 18:09
     * @param int    $id     用户id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createInvitevipPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $disSet = is_array($_W['wlsetting']['distribution']) ? $_W['wlsetting']['distribution'] : Setting::wlsetting_read("distribution");
        #2、生成路径信息
        if ($disSet['posterqr'] == 1) {
            $path = Distribution::getgzqrcode($_W['mid'])['url'] ? : ''; //关注路径
        }
        else {
            //生成普通二维码进入地址
            if ($disSet['qrcodeurlstatus'] == 1) {
                //平台首页
                $path = 'pages/mainPages/index/index?head_id=' . $id;
            }
            else {
                //分销商申请页
                $path = 'pages/subPages/dealer/apply/apply?head_id=' . $id;
            }
            //判断当前渠道是否为小程序
            if ($source != 3) {
                $path = h5_url($path);//非小程序渠道  基本路径转超链接
            }
        }
        #3、二维码生成
        $filename = md5($id . $source . $path . 'distpid' . $bgimg);
        if ($source == 3 && $disSet['posterqr'] != 1) {
            //小程序普通二维码
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5普通二维码 & 关注二维码生成
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $defaultBg = URL_H5_RESOURCE . 'poster/invitevipbg.jpg';
        $poster    = [
            'bg'       => $defaultBg ,
            'qrimg'    => tomedia($qrCodeLink) ,
            'nickname' => $_W['wlmember']['nickname'] ? : '' ,
            'avatar'   => $_W['wlmember']['avatar'] ? : '' ,
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        if (p('diyposter') && !empty($_W['wlsetting']['diyposter']['distpid'])) {
            //使用自定义海报信息
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $_W['wlsetting']['diyposter']['distpid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5($id . $source . $_W['wlsetting']['diyposter']['distpid'] . $bgimg);
        }
        else {
            //使用默认海报
            $poster['data'] = '[{"left":"115px","top":"93px","type":"head","width":"86px","height":"86px"},
                    {"left":"115px","top":"186px","type":"nickname","width":"86px","height":"29px","size":"10px","color":"#999999","words":"昵称","align":"center"},
                    {"left":"115px","top":"406px","type":"qr","width":"90px","height":"90px","size":""}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 抢购商品独立海报【3】
     * Author: zzw
     * Date: 2019/11/8 11:41
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createRushPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = Rush::getSingleActive($id , "*");
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['sid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":rush:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径    pages/subPages/goods/index?i=26&i=26&id=84&type=1
            $path = 'pages/subPages/goods/index?id=' . $id . '&type=1&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('rush_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . 'poster/rushposterbg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
            'title'        => $goods['name'] ,
            'thumb'        => $goods['thumb'] ,
            'marketprice'  => $goods['price'] ,
            'productprice' => '￥' . $goods['oldprice'] ,
            'shopTitle'    => $store['storename'] ,
            'shopThumb'    => tomedia($store['logo']) ,
            'shopAddress'  => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'    => $store['mobile']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['rushpid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl              = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']           = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data']         = $postertpl['data'];
            $poster['productprice'] = '￥' . $goods['oldprice'];
            $poster['vip_price']    = '￥' . $goods['vipprice'];
            $filename               = md5('rush_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                                {"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                                {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                                {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                                {"left":"30px","top":"480px","type":"productprice","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"原价","align":"left"},';
            if ($goods['vipstatus'] != 0) {
                $poster['data'] .= '{"left":"30px","top":"495px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"会员价","align":"left"},';
            }
            $poster['data'] .= '{"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                                {"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                                {"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                                {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $goods['price'] . '","align":"left"},
                                {"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 团购商品独立海报【4】
     * Author: zzw
     * Date: 2019/11/8 13:50
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createGrouponPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = Groupon::getSingleActive($id , "*");
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['sid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":groupon:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径    pages/subPages/goods/index?i=26&i=26&id=84&type=1
            $path = 'pages/subPages/goods/index?id=' . $id . '&type=2&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('groupon_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . 'poster/grouponposterbg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
            'title'        => $goods['name'] ,
            'thumb'        => $goods['thumb'] ,
            'marketprice'  => $goods['price'] ,
            'productprice' => '原价:￥' . $goods['oldprice'] ,
            'shopTitle'    => $store['storename'] ,
            'shopThumb'    => tomedia($store['logo']) ,
            'shopAddress'  => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'    => $store['mobile'] ,
            'vip_price'    => '会员价:￥' . $goods['vipprice']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['grouponid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl              = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']           = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data']         = $postertpl['data'];
            $poster['productprice'] = '￥' . $goods['oldprice'];
            $poster['vip_price']    = '￥' . $goods['vipprice'];
            $filename               = md5('groupon_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                                {"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                                {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                                {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                                {"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                                {"left":"30px","top":"480px","type":"productprice","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"原价","align":"left"},';
            if ($goods['vipstatus'] != 0) {
                $poster['data'] .= '{"left":"30px","top":"495px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"会员价","align":"left"},';
            }
            $poster['data'] .= '{"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                                {"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                                {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $goods['price'] . '","align":"left"},
                                {"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 卡券商品独立海报【5】
     * Author: zzw
     * Date: 2019/11/8 14:51
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createCouponPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = wlCoupon::getSingleCoupons($id , '*');
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['merchantid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":wlcoupon:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径
            $path = 'pages/subPages/goods/index?id=' . $id . '&type=5&head_id='.$_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('coupon_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg     = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg    = URL_H5_RESOURCE . '/poster/couponposterbg.png';
        $coupon_price = $goods['is_charge'] == 1 ? $goods['price'] : '免费领';
        $poster       = [
            'bg'          => tomedia($posterBg) ,
            'qrimg'       => tomedia($qrCodeLink) ,
            'nickname'    => $_W['wlmember']['nickname'] ,
            'avatar'      => $_W['wlmember']['avatar'] ,
            'title'       => $goods['title'] ,
            'sub_title'   => $goods['sub_title'] ,
            'thumb'       => tomedia($goods['logo']) ,
            'marketprice' => $coupon_price ,
            'shopTitle'   => $store['storename'] ,
            'shopThumb'   => tomedia($store['logo']) ,
            'shopAddress' => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'   => $store['mobile']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['cardpid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl              = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']           = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data']         = $postertpl['data'];
            $poster['productprice'] = '￥' . $goods['oldprice'];
            $poster['vip_price']    = '￥' . $goods['vipprice'];
            if ($goods['vipstatus'] != 0 && $goods['is_charge'] == 1) {
                $poster['vip_price'] = '￥' . $goods['vipprice'];
            }
            $filename = md5('coupon_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                    {"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                    {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                    {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                    {"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                    {"left":"30px","top":"490px","type":"text","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"","align":"left"},
                    {"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},';
            if ($coupon_price == '免费领') {
                $poster['data'] .= '{"left":"85px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $coupon_price . '","align":"left"},';
            }
            else {
                $poster['data'] .= '{"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                        {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $coupon_price . '","align":"left"},';
            }
            if ($goods['vipstatus'] != 0 && $goods['is_charge'] == 1) {
                $poster['data'] .= '{"left":"30px","top":"490px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"会员价:￥' . $goods['vipprice'] . '","align":"left"},';
            }
            $poster['data'] .= '{"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 拼团商品独立海报【6】
     * Author: zzw
     * Date: 2019/11/8 15:32
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createFightgroupPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = Wlfightgroup::getSingleGood($id , '*');
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['merchantid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":wlfightgroup:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径
            $path = 'pages/subPages/goods/index?id=' . $id . '&type=3&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('fightgroup_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . '/poster/fgroupposterbg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
            'title'        => $goods['name'] ,
            'thumb'        => $goods['logo'] ,
            'marketprice'  => $goods['price'] ,
            'productprice' => $goods['aloneprice'] ,
            'shopTitle'    => $store['storename'] ,
            'shopThumb'    => tomedia($store['logo']) ,
            'shopAddress'  => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'    => $store['mobile']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['fgrouppid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('fightgroup_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                    {"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                    {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                    {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                    {"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                    {"left":"30px","top":"490px","type":"text","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"单买价:￥' . $goods['aloneprice'] . '","align":"left"},
                    {"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                    {"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                    {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $goods['price'] . '","align":"left"},
                    {"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 砍价商品独立海报【7】
     * Author: zzw
     * Date: 2019/11/8 15:44
     * @param int    $id     商户id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createBargainPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = pdo_get(PDO_NAME . 'bargain_activity' , ['id' => $id , 'uniacid' => $_W['uniacid']]);
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['sid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":bargain:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径
            $path = 'pages/subPages/goods/index?id=' . $id . '&type=7&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('bargain_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . '/poster/bargainbg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
            'title'        => $goods['name'] ,
            'thumb'        => $goods['thumb'] ,
            'marketprice'  => $goods['price'] ,
            'productprice' => '原价:￥' . $goods['oldprice'] ,
            'shopTitle'    => $store['storename'] ,
            'shopThumb'    => tomedia($store['logo']) ,
            'shopAddress'  => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'    => $store['mobile']
        ];
        if ($goods['vipstatus'] != 0) $poster['vip_price'] = '会员低价:￥' . $goods['vipprice'];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['bargainid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl              = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']           = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data']         = $postertpl['data'];
            $poster['productprice'] = '￥' . $goods['oldprice'];
            if ($goods['vipstatus'] != 0) $poster['vip_price'] = '￥' . $goods['vipprice'];
            $filename = md5('fightgroup_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                    {"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                    {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                    {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                    {"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                    {"left":"30px","top":"480px","type":"productprice","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"原价","align":"left"},';
            if ($goods['vipstatus'] != 0) {
                $poster['data'] .= '{"left":"30px","top":"495px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"会员底价","align":"left"},';
            }
            $poster['data'] .= '{"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                    {"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                    {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $goods['price'] . '","align":"left"},
                    {"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 店铺独立海报【8】
     * Author: zzw
     * Date: 2019/11/8 16:13
     * @param int    $id     店铺id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createStorePoster($id , $source , $bgimg = '',$redid = 0)
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $store     = Store::getSingleStore($id);
        #2、生成路径信息  判断是否存在商户二维码 存在使用商户二维码，不存在使用默认二维码
        if($redid > 0){
        	$redinfo = pdo_get('wlmerchant_store_redpack',array('id' => $redid),array('diypageid','posterid'));
        }
		if($redid > 0){
			//使用默认二维码
            $path = 'pagesA/formmerchantpage/formmerchantpage?sid=' . $id . '&head_id=' . $_W['mid']. '&id=' . $redinfo['diypageid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
		}else{
			if(($source == 3 && $posterSet['wxapp_poster']) || ($source != 3 && $posterSet['h5_poster'] )){
	            //使用商户二维码
	            $qrid = pdo_getcolumn(PDO_NAME . 'qrcode' , ['sid' => $id , 'status' => 2] , 'qrid');
	            if(empty($qrid)){
	                $qrid = Storeqr::create_storeqr($id);
	            }
	            $path = pdo_getcolumn('qrcode' , ['id' => $qrid] , 'url');
	//            $qrid = pdo_getcolumn(PDO_NAME . 'qrcode' , ['sid' => $id , 'status' => 2] , 'qrid');
	//            $qrCodeInfo = pdo_get('qrcode' , ['id' => $qrid]);
	//            if(strpos($qrCodeInfo['name'],':') == false) pdo_update('qrcode' , ['name' => '商户关注二维码:Storeqr'] , ['id' => $qrid]);
	//            $path = $qrCodeInfo['url'];
	        }else{
	            //使用默认二维码
	            $path = 'pages/mainPages/store/index?sid=' . $id . '&head_id=' . $_W['mid'];//基本路径，也是小程序路径
	            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
	        }
		}
        
        #3、二维码生成
        $filename = md5('store_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && empty($posterSet['wxapp_poster']) ) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $defaultBg = URL_H5_RESOURCE . '/poster/storeposterbg.jpg';
        $poster    = [
            'bg'          => $defaultBg ,
            'qrimg'       => tomedia($qrCodeLink) ,
            'nickname'    => $_W['wlmember']['nickname'] ,
            'avatar'      => $_W['wlmember']['avatar'] ,
            'shopTitle'   => $store['storename'] ,
            'shopThumb'   => tomedia($store['logo']) ,
            'shopAddress' => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'   => $store['mobile']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        if($redinfo['posterid'] > 0){
        	$postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $redinfo['posterid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('redpack_id' . $redid . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $posterSet['storepid'] . $path);
        }else if($store['posterid']>0){
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $store['posterid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('store_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $posterSet['storepid'] . $path);
        }else if(!empty($posterSet['storepid'])){
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $posterSet['storepid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('store_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $posterSet['storepid'] . $path);
        }else {
            $poster['data'] = '[{"left":"117px","top":"95px","type":"shopTitle","width":"148px","height":"38px","size":"12px","color":"#333"},
                    {"left":"55px","top":"84px","type":"shopThumb","width":"57px","height":"57px"},
                    {"left":"64px","top":"379px","type":"qr","width":"63px","height":"63px","size":""}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 积分商品海报【10】
     * Author: zzw
     * Date: 2019/12/4 10:58
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createConsumptionPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = pdo_get(PDO_NAME . "consumption_goods" , ['id' => $id]);
        $goods     = self::checkprice($goods);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [
                    1 ,
                    2
                ])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":consumption:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径  pages/subPages/goods/index?goods_id=19&goodsType=integral&i=26
            $path = 'pages/subPages/goods/index?goods_id=' . $id . '&goodsType=integral&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('consumption_goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信
        $posterBg   = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg  = URL_H5_RESOURCE . '/poster/couponposterbg.png';
        $diyText    = Setting::wlsetting_read('trade');
        $credittext = $diyText['credittext'] ? $diyText['credittext'] : '积分';
        $price      = $goods['use_credit1'] . $credittext . '+￥' . $goods['use_credit2'];
        $vipPrice   = $goods['vipcredit1'] . $credittext . '+￥' . $goods['vipcredit2'];
        $poster     = [
            'bg'          => tomedia($posterBg) ,
            'qrimg'       => tomedia($qrCodeLink) ,
            'nickname'    => $_W['wlmember']['nickname'] ,
            'avatar'      => $_W['wlmember']['avatar'] ,
            'title'       => $goods['title'] ,
            'thumb'       => tomedia($goods['thumb']) ,
            'marketprice' => $price ,
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['diyposter'] ? $goods['diyposter'] : $posterSet['consumption_id'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            if ($goods['vipstatus'] == 1) {
                $poster['vip_price'] = $goods['vipcredit1'] . $credittext . '+￥' . $goods['vipcredit2'];
            }
            $filename = md5('coupon_goods_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                    {"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                    {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                    {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},
                    {"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"商品名称","align":"left"},
                    {"left":"30px","top":"490px","type":"text","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"","align":"left"},
                    {"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                    {"left":"75px","top":"463px","type":"text","width":"150px","height":"45px","line":"1","size":"8px","color":"#ff4744","words":"' . $price . '","align":"left"},';
            if (intval($goods['old_price'])) {
                $poster['productprice'] = '原价￥:' . $goods['old_price'];
                $poster['data']         .= '{"left":"30px","top":"480px","type":"productprice","width":"101px","height":"24px","line":"1","size":"7px","color":"#878787","words":"原价￥:' . $goods['old_price'] . '","align":"left"},';
            }
            if ($goods['vipstatus'] == 1) {
                $poster['vip_price'] = $vipPrice;
                $poster['data']      .= '{"left":"30px","top":"495px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"7px","color":"#878787","words":"会员价:￥' . $vipPrice . '","align":"left"},';
            }
            $poster['data'] .= '{"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这款商品","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 个人名片海报【11】
     * Author: zzw
     * Date: 2019/12/18 15:10
     * @param int    $id
     * @param int    $source
     * @param string $bgimg
     * @return string
     */
    public static function createUserCardPoster($id , $source , $bgimg = ''){
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $info      = pdo_get(PDO_NAME . "citycard_lists" , ['id' => $id]);
        $member    = pdo_get(PDO_NAME . "member" , ['id' => $info['mid']] , ['realname' , 'nickname' , 'avatar']);
        if (!$info) Commons::sRenderError('名片不存在!');
        if(!strstr($info['address'], '省') && !strstr($info['address'], '市') && !strstr($info['address'], '县') &&  !strstr($info['address'], '自治区')){
            $proName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['pro_code']),'name');
            $cityName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['city_code']),'name');
            $areaName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['area_code']),'name');
            $info['address'] = $proName.$cityName.$areaName.$info['address'];
        }
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [1 , 2])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":citycard:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径
            $path = "pages/subPages/businesscard/carddetail/carddetail?cardid={$id}";//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('citycard_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
//            $avatar = tomedia($member['avatar']);
//            $pathimg = IA_ROOT . '/addons/' . MODULE_NAME . '/data/poster/' . $_W['uniacid'].'/newqrcode_' . $filename . '.png';
//            $qrCodeLink = self::codeImg($avatar,$qrCodeLink,$pathimg);
        }else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认值
        $posterBg    = URL_H5_RESOURCE . 'poster/cardbg.png';
        $defaultBg   = URL_H5_RESOURCE . '/poster/carddefaultbg.png';
        $iconAddress = URL_H5_RESOURCE . '/poster/address.png';
        $iconMobile  = URL_H5_RESOURCE . '/poster/mobile.png';
        $iconWechat  = URL_H5_RESOURCE . '/poster/wechat.png';
        $iconDesc    = URL_H5_RESOURCE . '/poster/desc.png';
        $userName    = !empty($member['realname']) ? $member['realname'] : $member['nickname'];
        $filename    = md5('citycard_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path . 'username' . $userName);
        $poster      = [
            'bg'       => tomedia($posterBg) ,
            'qrimg'    => tomedia($qrCodeLink) ,
            'name'     => $info['name'] ? $info['name'] : $member['nickname'] ,//名片名称
            'position' => $info['position'] ? $info['position'] : '' ,//职位
            'logo'     => $info['logo'] ? tomedia($info['logo']) : $member['avatar'] ,//logo
            'address'  => $info['address'] ? $info['address'] : '' ,//地址
            'mobile'   => $info['mobile'] ? $info['mobile'] : '' ,//电话
            'wechat'   => $info['wechat'] ? $info['wechat'] : '' ,//微信号
            'desc'     => $info['desc'] ? $info['desc'] : '' ,//介绍
        ];
        if ($info['company'] && $info['branch']) {
            $poster['company_branch'] = $info['company'] . " | " . $info['branch'];
        }
        else if ($info['company']) {
            $poster['company_branch'] = $info['company'];
        }
        else if ($info['branch']) {
            $poster['company_branch'] = $info['branch'];
        }
        //手机号处理
        if ($info['show_mobile'] != 1) {
            $replaceStr       = substr($poster['mobile'] , 3 , (strlen($poster['mobile']) - 7));
            $poster['mobile'] = str_replace($replaceStr , '***' , $poster['mobile']);
        }
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $posterSet['user_card_id'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('citycard_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'user_card_id' . $diytpl . $path);
        }
        else {
            $listKey        = 100;
            $poster['data'] = '[{"left":"0px","top":"0px","type":"img","width":"424px","height":"444px","src":"' . tomedia($defaultBg) . '","appoint":"1"},
                    {"left":"17px","top":"26px","type":"name","width":"103px","height":"34px","line":"1","size":"17px","color":"#333333","words":"名片名称","align":"left"},
                    {"left":"138px","top":"26px","type":"position","width":"138px","height":"24px","line":"1","size":"14px","color":"#999999","words":"职位","align":"left"},
                    {"left":"17px","top":"60px","type":"company_branch","width":"273px","height":"34px","line":"1","size":"12px","color":"#333333","words":"公司|部门","align":"left"},
                    {"left":"282px","top":"21px","type":"logo","width":"60px","border":"circle"},';
            if ($info['show_addr'] == 1) {
                $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"address","width":"259px","height":"34px","line":"1","size":"8px","color":"#333333","words":"地址","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconAddress) . '"},';
                $listKey        = $listKey + 28;
            }
            $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"mobile","width":"259px","height":"34px","line":"1","size":"8px","color":"#333333","words":"电话","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconMobile) . '"},';
            $listKey        = $listKey + 28;
            if ($info['show_wechat'] == 1) {
                $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"wechat","width":"259px","height":"20px","line":"1","size":"8px","color":"#333333","words":"微信号","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconWechat) . '"},';
                $listKey        = $listKey + 28;
            }
            $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"desc","width":"210px","height":"20px","line":"2","size":"8px","color":"#333333","words":"介绍","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconDesc) . '"},
                    {"left":"17px","top":"264px","type":"qr","width":"86px","height":"86px","size":""},
                    {"left":"120px","top":"282px","type":"text","width":"245px","height":"20px","line":"2","size":"12px","color":"#999999","words":"您好,这是' . $userName . '的名片，请惠存","align":"left"},
                    {"left":"120px","top":"326px","type":"text","width":"245px","height":"20px","line":"1","size":"12px","color":"#3388FF","words":"长按或扫码进入","align":"left"}]';
        }
        #5、生成海报信息
        $filename = time();
        return Tools::createPoster($poster , $filename , $member , 720);
    }
    /**
     * Comment: 黄页114海报【12】
     * Author: wlf
     * Date: 2020/08/19 15:23
     * @param int    $id
     * @param int    $source
     * @param string $bgimg
     * @return string
     */
    public static function createYellowPoster($id , $source , $bgimg = ''){
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $info      = pdo_get(PDO_NAME . "yellowpage_lists" , ['id' => $id]);
        if (!$info) Commons::sRenderError('页面不存在!');
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [1 , 2])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":yellow:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径 (暂未修改)
            $path = "pages/subPages2/phoneBook/logistics/logistics?id={$id}";//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('yellowpage_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认值
        $posterBg    = URL_H5_RESOURCE . 'poster/cardbg.png';
        $defaultBg   = URL_H5_RESOURCE . '/poster/carddefaultbg.png';
        $iconAddress = URL_H5_RESOURCE . '/poster/address.png';
        $iconMobile  = URL_H5_RESOURCE . '/poster/mobile.png';
        $iconDesc    = URL_H5_RESOURCE . '/poster/desc.png';
        $userName    = $info['name'];
        $filename    = md5('yellowpage_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path . 'name' . $userName);
        $poster      = [
            'bg'       => tomedia($posterBg) ,
            'qrimg'    => tomedia($qrCodeLink) ,
            'name'     => $info['name'],//名片名称
            // 'position' => $info['position'] ? $info['position'] : '' ,
            'logo'     => tomedia($info['logo']),//logo
            'address'  => $info['address'] ? $info['address'] : '' ,//地址
            'mobile'   => $info['mobile'] ? $info['mobile'] : '' ,//电话
            // 'wechat'   => $info['wechat'] ? $info['wechat'] : '' ,
            'desc'     => $info['desc'] ? $info['desc'] : '' ,//介绍
        ];
        //查询黄页分类
        $one_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$info['one_class']),array('name','querymoney'));
        $two_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$info['two_class']),array('name','querymoney'));
        $querymoney = !empty($two_class) ? $two_class['querymoney'] : $one_class['querymoney'];
        $poster['company_branch'] = $one_class['name'];
        if(!empty($two_class)){
            $poster['company_branch'] .= " | " .$two_class['name'];
        }
        //手机号处理
        if ($querymoney > 0) {
            $replaceStr       = substr($poster['mobile'] , 3 , (strlen($poster['mobile']) - 7));
            $poster['mobile'] = str_replace($replaceStr , '***' , $poster['mobile']);
        }
        //判断是使用自定义海报  还是使用默认海报
        $diytpl = $posterSet['yellow_id'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5('yellowpage_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'user_card_id' . $diytpl . $path);
        }
        else {
            $listKey        = 100;
            $poster['data'] = '[{"left":"0px","top":"0px","type":"img","width":"424px","height":"444px","src":"' . tomedia($defaultBg) . '","appoint":"1"},
                    {"left":"17px","top":"26px","type":"name","width":"250px","height":"34px","line":"1","size":"17px","color":"#333333","words":"页面名称","align":"left"},
                    {"left":"17px","top":"60px","type":"company_branch","width":"273px","height":"34px","line":"1","size":"12px","color":"#333333","words":"分类","align":"left"},
                    {"left":"282px","top":"21px","type":"logo","width":"60px","border":"circle"},';

            $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"address","width":"259px","height":"34px","line":"1","size":"8px","color":"#333333","words":"地址","align":"left"},
                {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconAddress) . '"},';
            $listKey        = $listKey + 28;

            $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"mobile","width":"259px","height":"34px","line":"1","size":"8px","color":"#333333","words":"电话","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconMobile) . '"},';
            $listKey        = $listKey + 28;

            $poster['data'] .= '{"left":"43px","top":"' . $listKey . 'px","type":"desc","width":"210px","height":"20px","line":"2","size":"8px","color":"#333333","words":"介绍","align":"left"},
                    {"left":"17px","top":"' . ($listKey - 5) . 'px","type":"img","width":"20px","height":"20px","src":"' . tomedia($iconDesc) . '"},
                    {"left":"17px","top":"264px","type":"qr","width":"86px","height":"86px","size":""},
                    {"left":"120px","top":"282px","type":"text","width":"245px","height":"20px","line":"2","size":"12px","color":"#999999","words":"您好,这是[' . $userName . ']的主页，请查阅","align":"left"},
                    {"left":"120px","top":"326px","type":"text","width":"245px","height":"20px","line":"1","size":"12px","color":"#3388FF","words":"长按或扫码进入","align":"left"}]';
        }
        //生成海报信息
        $filename = time();
        return Tools::createPoster($poster , $filename , $member , 720);
    }
    /**
     * Comment: 生成抽奖海报【13】
     * Author: zzw
     * Date: 2020/9/25 14:15
     * @param $id
     * @param $source
     * @return string
     */
    public static function createDrawPoster($id , $source ){
        global $_W;
        //基本信息获取
        //$info = Draw::getDrawActivityInfo($id);
        $posterSet = Setting::wlsetting_read("diyposter");
        $set = Setting::agentsetting_read('draw_set');
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [1 ,2])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":draw:" . $source);
            $path      = $disqrcode['url'];
        }else {
            //普通二维码路径    pages/subPages/goods/index?i=26&i=26&id=84&type=1
            $path = 'pages/subPages2/drawGame/drawGame?id=' . $id.'&head_id='.$_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        //二维码生成
        $filename = md5('draw_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        //生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . 'poster/draw_bg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
        ];
        $diyPosterBg = pdo_getcolumn(PDO_NAME . "draw" , ['id' => $id],'poster_bg');
        $posterBg = $set['poster_bg'] ? tomedia($set['poster_bg']) : $defaultBg ;
        $bgImage = $diyPosterBg ? tomedia($diyPosterBg) : $posterBg ;
        #5、判断是使用自定义海报  还是使用默认海报
        $poster['data'] = '[{"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($bgImage) . '"},
                            {"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                            {"left":"11.5px","top":"10px","type":"head","width":"50px","height":"50px","border":"circle"},
                            {"left":"65px","top":"20px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#FFFFFF","words":"昵称","align":"left"},
                            {"left":"65px","top":"40px","type":"text","width":"200px","height":"23px","line":"1","size":"8px","color":"#FFFFFF","words":"发现一个好东西，想跟您分享一下~","align":"left"},
                            {"left":"20px","top":"434px","type":"qr","width":"120px","height":"120px","size":""}]';
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 活动商品独立海报【14】
     * Author: wlf
     * Date: 2020/11/5 16:38
     * @param int    $id     商品id
     * @param int    $source 渠道：1=公众号（默认）；2=h5；3=小程序
     * @param string $bgimg  背景图片
     * @return string
     */
    public static function createActivityPoster($id , $source , $bgimg = '')
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");
        $goods     = pdo_get('wlmerchant_activitylist',array('id' => $id),array('price','title','thumb','optionstatus','vipprice','pv','addresstype','posterid','vipstatus','sid'));
        if($goods['optionstatus'] > 0){
            $options = pdo_getall('wlmerchant_activity_spec',array('activityid' => $id),array('price'));
            $prices = array_column($options,'price');
            $goods['price'] = min($prices);
        }
        if(empty($goods['addresstype'])){
            $goods['address'] = $store['address'];
        }
        $goods     = self::checkprice($goods);
        $store     = Store::getSingleStore($goods['sid']);
        #2、生成路径信息  渠道：1=公众号（默认）；2=h5；3=小程序
        if (($posterSet['h5_poster'] == '1' && in_array($source , [1,2])) || ($posterSet['wxapp_poster'] == '1' && $source == 3)) {
            //公众号关注二维码路径
            $disqrcode = Diyposter::getgzqrcode($id , $_W['mid'] . ":activity:" . $source);
            $path      = $disqrcode['url'];
        }
        else {
            //普通二维码路径    pages/subPages/goods/index?i=26&i=26&id=84&type=1
            $path = 'pages/subPages2/coursegoods/coursegoods?id=' . $id . '&type=1&head_id=' . $_W['mid'];//基本路径，也是小程序路径
            if ($source != 3) $path = h5_url($path);//非小程序渠道  基本路径转超链接
        }
        #3、二维码生成
        $filename = md5('activity_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'path' . $path);
        if ($source == 3 && $posterSet['wxapp_poster'] != 1) {
            //小程序
            $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
            if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        else {
            //公众号/H5
            $qrCodeLink = self::qrcodeimg($path , $filename);
        }
        #4、生成默认信息
        $posterBg  = URL_H5_RESOURCE . 'poster/posterbg.jpg';
        $defaultBg = URL_H5_RESOURCE . 'poster/activitybg.png';
        $poster    = [
            'bg'           => tomedia($posterBg) ,
            'qrimg'        => tomedia($qrCodeLink) ,
            'nickname'     => $_W['wlmember']['nickname'] ,
            'avatar'       => $_W['wlmember']['avatar'] ,
            'title'        => $goods['title'] ,
            'thumb'        => $goods['thumb'] ,
            'marketprice'  => $goods['price'] ,
            'shopTitle'    => $store['storename'] ,
            'shopThumb'    => tomedia($store['logo']) ,
            'shopAddress'  => self::getAddress($store['distid'] , $store['address']) ,
            'shopPhone'    => $store['mobile'],
            'activityaddress' => $goods['address']
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        $diytpl = $goods['posterid'] ? $goods['posterid'] : $posterSet['activityid'];
        if (p('diyposter') && !empty($diytpl)) {
            $postertpl              = pdo_get(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'id' => $diytpl] , [
                'data' ,
                'bg'
            ]);
            $poster['bg']           = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data']         = $postertpl['data'];
            $poster['vip_price']    = '￥' . $goods['vipprice'];
            $filename               = md5('activity_id' . 'goods_id' . $id . 'mid' . $_W['mid'] . 'source' . $source . 'bgimg' . $bgimg . 'disposter_id' . $diytpl . $path);
        }
        else {
            $poster['data'] = '[{"left":"0px","top":"0px","type":"img","width":"320px","height":"578.5px","src":"' . tomedia($defaultBg) . '"},
                                {"left":"0px","top":"0px","type":"thumb","width":"320px","height":"320px","position":"cover"},
                                {"left":"21.3px","top":"304.6px","type":"head","width":"55px","height":"55px","border":""},
                                {"left":"93px","top":"332px","type":"nickname","width":"200px","height":"23px","line":"1","size":"9px","color":"#343434","words":"昵称","align":"left"},';
            if ($goods['vipstatus'] == 1) {
                $poster['data'] .= '{"left":"30px","top":"495px","type":"vip_price","width":"101px","height":"24px","line":"1","size":"9px","color":"#878787","words":"会员减免","align":"left"},';
            }
            $poster['data'] .= '{"left":"25px","top":"380px","type":"title","width":"266px","height":"75px","line":"3","size":"11px","color":"#343434","words":"活动名称","align":"left"},
                                {"left":"197px","top":"450px","type":"qr","width":"85px","height":"85px","size":""},
                                {"left":"75px","top":"466px","type":"text","width":"10px","height":"26px","line":"1","size":"10px","color":"#ff4744","words":"￥","align":"left"},
                                {"left":"88px","top":"453px","type":"text","width":"150px","height":"40px","line":"1","size":"24px","color":"#ff4744","words":"' . $goods['price'] . '","align":"left"},
                                {"left":"35px","top":"539px","type":"text","width":"150px","height":"18px","line":"1","size":"8px","color":"#343434","words":"已有' . $goods['pv'] . '人喜欢这次活动","align":"left"}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 相亲交友 - 红娘要求函海报【15】
     * Author: zzw
     * Date: 2021/3/23 11:01
     * @param        $id
     * @param        $source
     * @param string $bgimg
     * @return string
     */
    public static function createDatingPoster($id , $source , $bgimg = ''){
        global $_W;
        //基本信息获取
        $set = Setting::wlsetting_read('dating_set');
        $tipStr = $set['invitation_speech'] ? : '';
        $diyPoster = Setting::wlsetting_read('diyposter');
        //生成路径信息   1=公众号（默认）；2=h5；3=小程序
        if($_W['source'] == 3){
            //小程序  1=关注二维码，0=小程序码
            if($diyPoster['wxapp_poster'] == 1) {
                //关注二维码
                $path = Diyposter::getgzqrcode($id , $_W['mid'] . ":dating:" . $source)['url'];
                $filename = md5($id . $source . $path . 'dating' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            } else {
                //小程序码
                $path = 'pages/mainPages/index/diypage?type=16&head_id='.$_W['mid'];
                $filename = md5($id . $source . $path . 'dating' . $bgimg);
                $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
                if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
            }
        }else {
            //H5&公众号  1=关注二维码，0=小程序码
            if($diyPoster['h5_poster'] == 1) {
                //关注二维码
                $path = Diyposter::getgzqrcode($id , $_W['mid'] . ":dating:" . $source)['url'];
                $filename = md5($id . $source . $path . 'dating' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            } else {
                //普通二维码
                $path = h5_url('pages/mainPages/index/diypage?type=16',['head_id' => $_W['mid']]);
                $filename = md5($id . $source . $path . 'dating' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            }
        }
        //生成默认信息
        $defaultBg = URL_H5_RESOURCE . 'poster/dating.png';
        $poster    = [
            'bg'       => $defaultBg ,
            'qrimg'    => tomedia($qrCodeLink) ,
            'nickname' => $_W['wlmember']['nickname'] ? : '' ,
            'avatar'   => $_W['wlmember']['avatar'] ? : '' ,
        ];
        //判断是使用自定义海报  还是使用默认海报
        if (p('diyposter') && !empty($_W['wlsetting']['diyposter']['dating_id'])) {
            //使用自定义海报信息
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $_W['wlsetting']['diyposter']['dating_id']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5($id . $source . $_W['wlsetting']['diyposter']['dating_id'] . $bgimg);
        } else {
            //使用默认海报
            $poster['data'] = '[{"left":"115px","top":"93px","type":"head","width":"86px","height":"86px"},
            {"left":"115px","top":"186px","type":"nickname","width":"86px","height":"29px","size":"10px","color":"#999999","words":"昵称","align":"center"},
            {"left":"30px","top":"260px","type":"text","width":"280px","height":"26px","line":"2","size":"15px","color":"#000000","words":"'.$tipStr.'","align":"left"},
            {"left":"115px","top":"406px","type":"qr","width":"90px","height":"90px","size":""}]';
        }
        //生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }

    /**
     * Comment: 家政服务项目海报【16】
     * Author: wlf
     * Date: 2021/05/11 11:57
     * @param        $id
     * @param        $source
     * @param string $bgimg
     * @return string
     */
    public static function createHousekeepPoster($id , $source , $bgimg = ''){
        global $_W;
        //基本信息获取
        $diyPoster = Setting::wlsetting_read('diyposter');
        //生成路径信息   1=公众号（默认）；2=h5；3=小程序
        if($_W['source'] == 3){
            //小程序  1=关注二维码，0=小程序码
            if($diyPoster['wxapp_poster'] == 1) {
                //关注二维码
                $path = Diyposter::getgzqrcode($id , $_W['mid'] . ":housekeep:" . $source)['url'];
                $filename = md5($id . $source . $path . 'housekeep' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            } else {
                //小程序码
                $path = 'pages/subPages2/homemaking/homemakingDetails/homemakingDetails?id='.$id;
                $filename = md5($id . $source . $path . 'housekeep' . $bgimg);
                $qrCodeLink = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
                if (is_array($qrCodeLink)) $qrCodeLink = self::qrcodeimg($path , $filename);
            }
        }else {
            //H5&公众号  1=关注二维码，0=小程序码
            if($diyPoster['h5_poster'] == 1) {
                //关注二维码
                $path = Diyposter::getgzqrcode($id , $_W['mid'] . ":housekeep:" . $source)['url'];
                $filename = md5($id . $source . $path . 'housekeep' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            } else {
                //普通二维码
                $path = h5_url('pages/subPages2/homemaking/homemakingDetails/homemakingDetails?id='.$id);
                $filename = md5($id . $source . $path . 'dating' . $bgimg);
                $qrCodeLink = self::qrcodeimg($path , $filename);
            }
        }
        //生成默认信息
        $defaultBg = URL_H5_RESOURCE . 'poster/housekeepBG.png';
        $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('type','objid','title','pricetype','price','adv','thumb','unit'));
        $adv = Housekeep::beautifyImgInfo($service['adv']);
        if(empty($service['pricetype'])){
            $price = '价格面议';
        }else if($service['pricetype'] == 1){
            $price = '预约金：￥'.$service['price'].'/'.$service['unit'];
        }else if($service['pricetype'] == 2){
            $price = '￥'.$service['price'].'/'.$service['unit'];
        }
        if($service['type'] == 1){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $service['objid']),array('storename','logo','address','mobile'));
        }else {
            $merchant = pdo_get('wlmerchant_housekeep_artificer',array('id' => $service['objid']),array('name','thumb','address','mobile'));
            $merchant['storename'] = $merchant['name'];
            $merchant['logo'] = $merchant['thumb'];
        }
        $poster    = [
            'bg'          => $defaultBg ,
            'qrimg'       => tomedia($qrCodeLink) ,
            'nickname'    => $_W['wlmember']['nickname'] ? : '' ,
            'avatar'      => $_W['wlmember']['avatar'] ? : '' ,
            'shopTitle'   => $merchant['storename'],
            'shopThumb'   => tomedia($merchant['logo']),
            'title'       => $service['title'],
            'marketprice' => $price,
            'logo'        => tomedia($service['thumb']),
            'thumb'       => $adv[0],
            'shopAddress' => $merchant['address'],
            'shopPhone'   => $merchant['mobile']
        ];
        //判断是使用自定义海报  还是使用默认海报
        if (p('diyposter') && !empty($_W['wlsetting']['diyposter']['housekeepid'])) {
            //使用自定义海报信息
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $_W['wlsetting']['diyposter']['housekeepid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
            $filename       = md5($id . $source . $_W['wlsetting']['diyposter']['housekeepid'] . $bgimg);
        } else {
            //使用默认海报
            $poster['data'] =
                '[{"left":"30px","top":"30px","type":"text","width":"216px","height":"25px","line":"1","size":"16px","color":"#FFFFFF","words":"'.$poster['shopTitle'].'","align":"left"},
              {"left":"92px","top":"68px","type":"text","width":"188px","height":"16px","line":"1","size":"11px","color":"#FFFFFF","words":"'.$poster['shopAddress'].'","align":"left"},
              {"left":"92px","top":"90px","type":"text","width":"130px","height":"16px","line":"1","size":"11px","color":"#FFFFFF","words":"'.$poster['shopPhone'].'","align":"left"},
              {"left":"30px","top":"65px","type":"img","width":"40px","height":"40px","border":"","src":"' . $poster['shopThumb'] . '"},
              {"left":"30px","top":"150px","type":"img","width":"260px","height":"145px","border":"","src":"' . $poster['thumb'] . '"},
              {"left":"30px","top":"311px","type":"text","width":"260px","height":"25px","line":"1","size":"17px","color":"#000000","words":"'.$poster['title'].'","align":"left"},
              {"left":"30px","top":"346px","type":"text","width":"260px","height":"25px","line":"1","size":"17px","color":"#FF4444","words":"'.$poster['marketprice'].'","align":"left"},
              {"left":"185px","top":"423px","type":"qr","width":"120px","height":"120px","size":""}]';
        }
        //生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }


    /**
     * Comment: 业务员海报生成
     * Author: hexin
     * @param        $id
     * @param        $disflag
     * @param string $agent
     * @param string $bgimg
     * @return array|string
     */
    public static function createSalesmanPoster($id , $disflag , $agent = '' , $bgimg = '')
    {
        global $_W , $_GPC;
        $member   = $_W['wlmember'];
        $url      = h5_url('pages/subPages/dealer/index/index');
        $filename = md5($id . 'salesman' . $disflag . $bgimg);
        $data = '[{"left":"50px","top":"434px","type":"qr","width":"80px","height":"80px","size":""}]';
        //生成二维码
        $fliename = 'salesman' . $id . 'mid' . $member['id'] . 'flag' . $disflag;
        self::qrcodeimg($url , $fliename);
        $qrimg  = 'addons/'.MODULE_NAME.'/data/poster/' . $_W['uniacid'] . '/qrcode_' . $fliename . '.png';
        $poster = [
            'bg'       => URL_H5_RESOURCE . '/image/poster/settledbg.png' ,
            'data'     => $data ,
            'qrimg'    => tomedia($qrimg) ,
            'nickname' => $_W['wlmember']['nickname'] ,
            'avatar'   => $_W['wlmember']['avatar'] ,
        ];
        if (p('diyposter') && !empty($_W['wlsetting']['diyposter']['salesmanid']) && empty($disflag)) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $_W['wlsetting']['diyposter']['salesmanid']
            ] , ['data' , 'bg']);
            $poster['bg']   = $bgimg ? $bgimg : $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
        }
        if ((is_wxapp() && p('wxapp')) || $agent == 'wxapp') {
            $src = Wxapp::get_wxapp_qrcode('salesman#invitid=' . $id , $filename);
            if (!is_error($src)) {
                $poster['qrimg'] = tomedia('../addons/' . MODULE_NAME . '/data/wxapp/' . $_W['uniacid'] . '/' . $filename . '.png');
                $filename        = md5($id . 'wxapp_salesman');
            }
        }
        $poster = Tools::createPoster($poster , $filename , $member);
        return $poster;
    }
    public static function createSubPoster($numbers)
    {
        global $_W;
        #1、基本信息获取
        $posterSet = is_array($_W['wlsetting']['diyposter']) ? $_W['wlsetting']['diyposter'] : Setting::wlsetting_read("diyposter");

        $disqrcode = Subposter::getqrcode($_W['mid']);
        $path      = $disqrcode['url'];

        $filename = md5('SubPostermid' . $_W['mid'] . 'path' . $path.time());
        $qrCodeLink = self::qrcodeimg($path , $filename);

        #4、生成默认信息
        $defaultBg = URL_H5_RESOURCE . '/poster/subposterbg.jpg';
        $poster    = [
            'bg'          => $_W['wlsetting']['subposter']['bg'] ? tomedia($_W['wlsetting']['subposter']['bg']) : $defaultBg ,
            'qrimg'       => tomedia($qrCodeLink) ,
            'nickname'    => $_W['wlmember']['nickname'] ,
            'avatar'      => $_W['wlmember']['avatar'] ,
            'numbers'     => $numbers
        ];
        #5、判断是使用自定义海报  还是使用默认海报
        if (p('diyposter') && !empty($posterSet['subposter_id'])) {
            $postertpl      = pdo_get(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $posterSet['subposter_id']
            ] , ['data' , 'bg']);
            $poster['bg']   = $postertpl['bg'];
            $poster['data'] = $postertpl['data'];
        } else {
            $name_color = $_W['wlsetting']['subposter']['name_color'] ?: '#ffffff';
            $num_color = $_W['wlsetting']['subposter']['num_color'] ?: '#ffffff';
            $poster['data'] = '[{"left":"16px","top":"14px","type":"head","width":"42px","height":"42px","border":"circle"},{"left":"65px","top":"26px","type":"nickname","width":"216px","height":"23px","line":"1","size":"16px","color":"'.$name_color.'","words":"昵称","align":"left"},{"left":"84px","top":"381px","type":"numbers","width":"127px","height":"40px","line":"1","size":"28px","color":"'.$num_color.'","words":"人数","align":"center"},{"left":"208px","top":"455px","type":"qr","width":"96px","height":"96px","size":""}]';
        }
        #5、生成海报信息
        return Tools::createPoster($poster , $filename , $_W['wlmember']);
    }
    /**
     * Comment: 商品价格的处理
     * @param $goods
     * @return mixed
     */
    public function checkprice($goods)
    {
        if ($goods['price'] > 999.99) {
            $goods['price'] = sprintf("%.1f" , $goods['price']);
        }
        if ($goods['price'] > 9999.99) {
            $goods['price'] = sprintf("%.0f" , $goods['price']);
        }
        if ($goods['oldprice'] > 999.99) {
            $goods['oldprice'] = sprintf("%.1f" , $goods['oldprice']);
        }
        if ($goods['oldprice'] > 9999.99) {
            $goods['oldprice'] = sprintf("%.0f" , $goods['oldprice']);
        }
        if(!empty($goods['viparray'])){
            $viparray = unserialize($goods['viparray']);
            $viparray = array_column($viparray,'vipprice');
            $vipdiscount = max($viparray);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;
            if($goods['vipprice'] < 0 ){
                $goods['vipprice'] = 0;
            }
        }else{
            $goods['vipprice'] = $goods['price'];
        }


        if ($goods['vipprice'] > 999.99) {
            $goods['vipprice'] = sprintf("%.1f" , $goods['vipprice']);
        }
        if ($goods['vipprice'] > 9999.99) {
            $goods['vipprice'] = sprintf("%.0f" , $goods['vipprice']);
        }
        return $goods;
    }
    /**
     * Comment: 普通二维码生成
     * @param string $url
     * @param string $filename
     * @return string
     */
    public static function qrcodeimg($url , $filename)
    {
        global $_W , $_GPC;
        load()->library('qrcode');
        if (empty($_W['wlsetting']['base']['qrstatus'])) {
            ob_clean();
        }
//        $result = Util::long2short($url);
//        if (!is_error($result)) {
//            $url = $result['short_url'];
//        }
        $path = IA_ROOT . '/addons/' . MODULE_NAME . '/data/poster/' . $_W['uniacid'];
        if (!(is_dir($path))) {
            load()->func('file');
            mkdirs($path);
        }
        $file        = 'qrcode_' . $filename . '.png';
        $qrcode_file = $path . '/' . $file;
        if (!(is_file($qrcode_file))) {
            QRcode::png($url , $qrcode_file , QR_ECLEVEL_L , 32 , 2);
        }
        return 'addons/'.MODULE_NAME.'/data/poster/' . $_W['uniacid'] . '/qrcode_' . $filename . '.png';
    }
    /**
     * Comment: 地址处理，在详细地址前面添加区
     * Author: zzw
     * Date: 2020/1/7 15:04
     * @param $id       int    地区id
     * @param $address  string 详细地址
     * @return string
     */
    public static function getAddress($id , $address)
    {
        if ($id > 0) {
            $city_name = pdo_getcolumn(PDO_NAME . "area" , ['id' => pdo_getcolumn(PDO_NAME . "area" , ['id' => $id] , 'pid')] , 'name');
            $area_name = pdo_getcolumn(PDO_NAME . "area" , ['id' => $id] , 'name');
            $domain    = strstr($address , $city_name . $area_name);
            if (empty($domain)) {
                $address = $city_name . $area_name . $address;
            }
        }
        return $address;
    }

    protected function codeImg($logo ,$QR ,$pathimg) {//$logo要更换的图片路径,$QR二维码图片路径,$pathimg完成后图片存储路径
        if (!(is_dir($pathimg))) {
            load()->func('file');
            mkdirs($pathimg);
        }
        $dir = $QR;
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 2.3;
        $scale = $logo_width/$logo_qr_width;
        $logo_qr_height = $logo_height/$scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
        imagecopyresized($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

        $img = imagepng($QR,$pathimg);
        imagedestroy($img);
        return $pathimg;
    }


}

