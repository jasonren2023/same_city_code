<?php
defined('IN_IA') or exit('Access Denied');

class Pagelinks_WeliamController{
    /**
     * Comment: 获取页面链接基本信息
     * Author: zzw
     * Date: 2019/9/6 11:31
     */
    public function index() {
        global $_W,$_GPC;
        #1、获取链接信息
        $system = Links::getLinks('system');
        unset($system['shop_cate']);
        unset($system['pock_cate']);
        
        include wl_template('dashboard/pagelinks');
    }
    /**
     * Comment: 获取小程序太阳码信息
     * Author: zzw
     * Date: 2019/12/2 16:49
     */
    public function getWxAppQrCode(){
        global $_W,$_GPC;
        #1、参数获取
        $path = $_GPC['path'] OR Commons::sRenderError('path不存在');
        #1、二维码生成
        if(p('wxapp')){
            $imageUrl = tomedia(WeApp::getQrCode($path,'system_url'.md5($path).'.png'));
            Commons::sRenderSuccess('太阳码图片地址',$imageUrl);
        }else{
            Commons::sRenderError('无小程序信息');
        }
    }

    public function getWxWechatQrCode(){
        global $_W,$_GPC;
        #1、参数获取
        $path = $_GPC['link'] OR Commons::sRenderError('连接不存在');
        $filename     = md5('system_url'. md5($path));
        $qrCodeLink   = Poster::qrcodeimg($path , $filename);
        $imageUrl = tomedia($qrCodeLink);
        Commons::sRenderSuccess('二维码图片地址',$imageUrl);
    }



}