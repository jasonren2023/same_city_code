<?php
defined('IN_IA') or exit('Access Denied');
set_time_limit(0);

class Wxappset_WeliamController
{

    protected $setName = 'wxapp_config';

    /**
     * Comment: 微信小程序配置信息页
     * Author: zzw
     * Date: 2019/9/4 15:28
     */
    public function wxapp_info()
    {
        global $_W, $_GPC;
        $info = Setting::wlsetting_read($this->setName);
        $info['logo'] = tomedia($info['logo']);
        $info['qrcode'] = tomedia($info['qrcode']);
        $auth = Cloud::auth_info();
        #消息推送 配置信息
        $infoUrl = $_W['siteroot'] . 'addons/'.MODULE_NAME.'/core/common/uniapp.php?i=' . $_W['uniacid'] . '&do=WxAppCustomerService';

        include wl_template('wxapp/wxapp_info');
    }

    /**
     * Comment: 编辑微信小程序配置信息
     * Author: zzw
     * Date: 2019/9/4 15:27
     */
    public function wxapp_info_edit()
    {
        global $_W, $_GPC;
        #1、判断是否为POST请求，即是否为保存设置信息
        if ($_W['ispost']) {
            $data = $_GPC['data'];
            Setting::wlsetting_save($data, $this->setName);
            wl_message('设置成功!', web_url('wxapp/wxappset/wxapp_info'), 'success');
        }
        #1、编辑操作，获取已保存的设置信息
        $info = Setting::wlsetting_read($this->setName);


        include wl_template('wxapp/wxapp_info_edit');
    }

    /**
     * 微信小程序设置
     */
    public function wxapp_setting()
    {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('wxappset');
        if (checksubmit('submit')) {
            $base = $_GPC['set'];
            Setting::wlsetting_save($base, 'wxappset');
            wl_message('保存设置成功！', referer(), 'success');
        }
        include wl_template('wxapp/wxapp_setting');
    }

    /**
     * 跳转小程序
     */
    public function wxapp_skips()
    {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('wxapp_appids');

        if (checksubmit('submit')) {
            $data_temp = $_GPC['sapp_name'];
            $data_shop = $_GPC['sapp_appid'];
            $len = count($data_temp);
            $paramids = array();
            for ($k = 0; $k < $len; $k++) {
                $paramids[$k]['sapp_name'] = $data_temp[$k];
                $paramids[$k]['sapp_appid'] = $data_shop[$k];
            }

            Setting::wlsetting_save($paramids, 'wxapp_appids');
            wl_message('保存设置成功！', referer(), 'success');
        }
        include wl_template('wxapp/wxapp_skips');
    }

    public function wxapp_skips_tpl()
    {
        include wl_template('wxapp/wxapp_skips_tpl');
    }


    /**
     * Comment: 进入小程序发布页面
     * Author: zzw
     * Date: 2020/10/10 10:58
     */
    public function wxapp_upload(){
        global $_W, $_GPC;
        //判断授权信息
        $auth = Cloud::auth_info();
        if (is_error($auth)) wl_message($auth['message'], referer(), 'warning');
        //获取配置信息  判断是否完善信息
        $info = Setting::wlsetting_read($this->setName);
        if(!$info['appid'] || !$info['appid'] || !$info['rsa_private_key']) wl_message('请先完善小程序信息', referer(), 'error');

        include wl_template('wxapp/wxapp_upload');
    }
    /**
     * Comment: 小程序发布操作
     * Author: zzw
     * Date: 2020/10/10 11:45
     */
    public function wxapp_upload_code(){
        global $_W, $_GPC;
        //基本设置信息获取
        $set = Setting::wlsetting_read($this->setName);
        $appidList = Setting::wlsetting_read('wxapp_appids');
        $appids = array_column($appidList,'sapp_appid');

        $http = str_replace('http://','https://',$_W['siteroot']);
        //参数生成
        $data = [
            'do'         => 'sapp_upload' ,//访问方法
            'url'        => $_W['siteroot'] ,//授权域名
            'pd'         => MODULE_NAME,//产品标识
            'appid'      => $set['appid'] ,//小程序appid
            'privatekey' => base64_encode(trim($set['rsa_private_key'])) ,//上传秘钥
            'replaceA'   => '"siteroot":"https://citydev.weliam.com.cn","uniacid":"1"' ,//需要替换的文本
            'replaceB'   => '"siteroot":"'.$http . '","uniacid":"' . $_W['uniacid'] . '"' ,//替换文本的内容
            'orPID'      => 'weliam_smartcity',
            'rePID'      => MODULE_NAME,
            'appidlist'  => base64_encode(json_encode($appids)) ,//跳转小程序数组
            'liveplay'   => !empty($_W['wlsetting']['wxappset']['liveplay']) ? 'yes' : 'no',//是否包含直播yes包含，no不包含
            //'product'    => '' ,//小程序标识，不填则使用产品标识
            'version'    => WELIAM_VERSION ,//发布的小程序版本号，不填则使用最新版本号
            //'nbarcolor'  => '' ,//小程序主色调
        ];

        //请求发布小程序
        $info = Cloud::api_post($data);
        if ($info['code'] == 0) Setting::wlsetting_save(['time' => time(), 'version' => WELIAM_VERSION], 'wxapp_upload_code');

        wl_json($info['code'], $info['message'], $info['data']);
    }
    /**
     * Comment: 下载小程序代码
     */
    public function wxapp_download_code(){
        global $_W, $_GPC;
        //基本设置信息获取
        $set = Setting::wlsetting_read($this->setName);
        $appidList = Setting::wlsetting_read('wxapp_appids');
        $appids = array_column($appidList,'sapp_appid');

        $http = str_replace('http://','https://',$_W['siteroot']);
        //参数生成
        $data = [
            'do'         => 'sapp_download' ,//访问方法
            'url'        => $_W['siteroot'] ,//授权域名
            'pd'         => MODULE_NAME,//产品标识
            'appid'      => $set['appid'] ,//小程序appid
            'privatekey' => base64_encode(trim($set['rsa_private_key'])) ,//上传秘钥
            'replaceA'   => '"siteroot":"https://citydev.weliam.com.cn","uniacid":"1"' ,//需要替换的文本
            'replaceB'   => '"siteroot":"'.$http . '","uniacid":"' . $_W['uniacid'] . '"' ,//替换文本的内容
            'orPID'      => 'weliam_smartcity',
            'rePID'      => MODULE_NAME,
            'appidlist'  => base64_encode(json_encode($appids)) ,//跳转小程序数组
            'liveplay'   => !empty($_W['wlsetting']['wxappset']['liveplay']) ? 'yes' : 'no',//是否包含直播yes包含，no不包含
            //'product'    => '' ,//小程序标识，不填则使用产品标识
            'version'    => WELIAM_VERSION ,//发布的小程序版本号，不填则使用最新版本号
            //'nbarcolor'  => '' ,//小程序主色调
        ];

        //请求发布小程序
        $info = Cloud::api_post($data);
        if (!is_error($info)) {
            if ($info['code'] == 0) {
                header('Content-Disposition:attachment; filename='.MODULE_NAME.'.zip');
                echo base64_decode($info['data']['content']);
            }
            wl_message($info['message'], web_url('wxapp/wxappset/wxapp_upload'), 'error');
        }
    }
    /**
     * Comment: 小程序预览二维码获取
     * Author: zzw
     * Date: 2020/10/14 14:15
     */
    public function wxapp_preview(){
        global $_W, $_GPC;
        //基本设置信息获取
        $set = Setting::wlsetting_read($this->setName);
        //参数生成
        $data = [
            'do'         => 'sapp_preview' ,//访问方法
            'url'        => $_W['siteroot'] ,//授权域名
            'appid'      => $set['appid'] ,//小程序appid
            'version'    => $_W['wlsetting']['wxapp_upload_code']['version'],//预览的小程序版本号，不填则使用最新版本号
        ];
        //请求发布小程序
        $info = Cloud::api_post($data);

        wl_json($info['code'], $info['message'], $info['data']);
    }











}
