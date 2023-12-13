<?php
defined('IN_IA') or exit('Access Denied');

class Shopset_WeliamController {
    //基础设置
    public function base() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('base');
        $settings['plugin'] = unserialize($settings['plugin']);
        $settings['copytext'] = unserialize($settings['copytext']);
        $settings['copyurl'] = unserialize($settings['copyurl']);
        $urlnum = count($settings['copyurl']) + 1;
        if(empty($settings['serbgw'])){
            $settings['serbgw'] = "#FFD93F";
        }
        if(empty($settings['serbgn'])){
            $settings['serbgn'] = "#FFF4C4";
        }

        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['shop']);
            $base['copytext'] = serialize($_GPC['copytext']);
            $urltext = $_GPC['urltext'];
            $copyurl = $_GPC['copyurl'];
            $redIds = array();
            $len = count($urltext);
            for ($k = 0; $k < $len; $k++) {
                $redIds[$k]['text'] = $urltext[$k];
                $redIds[$k]['url'] = $copyurl[$k];
            }
            $base['copyurl'] = serialize($redIds);
            Setting::wlsetting_save($base, 'base');
            wl_message('更新设置成功！', web_url('setting/shopset/base'));
        }
        include wl_template('setting/shopBase');
    }

    public function copytext(){
        include wl_template('setting/copytext');
    }

    public function copyurl(){
        global $_W, $_GPC;
        $kk = $_GPC['kw'];
        include wl_template('setting/copyurl');
    }

    //分享与关注设置
    public function share() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('share');;
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['share']);
            $base['forcefollow'] = $_GPC['forcefollow'];
            Setting::wlsetting_save($base, 'share');
            wl_message('更新设置成功！', web_url('setting/shopset/share'));
        }
        include wl_template('setting/shopShare');
    }

    //客服设置
    public function customer() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('customer');
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['customer']);
            Setting::wlsetting_save($base, 'customer');
            Setting::agentsetting_save($base, 'agentcustomer');
            wl_message('更新设置成功！', web_url('setting/shopset/customer'));
        }
        include wl_template('setting/customer');
    }

    /**
     * Comment: api接口信息设置
     * Author: zzw
     * Date: 2019/10/8 16:09
     */
    public function api() {
        global $_W, $_GPC;
        #1、参数获取
        $type = $_GPC['type'] ?: '';
        #2、根据type进行不同的操作
        if ($type == 'get') {
            //获取设置信息
            $set = Setting::wlsetting_read('api');
            Commons::sRenderSuccess('api设置信息', $set);
        } else if ($type == 'set') {
            //储存设置信息
            $info = $_GPC['info'] OR Commons::sRenderError('非法提交');
            $info['logistics']['datatime'] = intval($info['logistics']['datatime']);
            Setting::wlsetting_save($info, 'api');
            Commons::sRenderSuccess('api设置修改成功');
        }

        include wl_template('setting/shopApi');
    }
    //文字设置
    public function trade() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('trade');;
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['data']);
            Setting::wlsetting_save($base,'trade');
            wl_message('更新设置成功！', web_url('setting/shopset/trade'));
        }
        include wl_template('setting/tradeset');
    }

    //附件设置
    public function enclosure() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('enclosure');
        $alioss = $settings['alioss'];
        $qiniu = $settings['qiniu'];
        $tengxun = $settings['tengxun'];

        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['data']);
            $alioss = Util::trimWithArray($_GPC['alioss']);
            $qiniu = Util::trimWithArray($_GPC['qiniu']);
            $tengxun = Util::trimWithArray($_GPC['cos']);

            if($base['service'] == 2){
                if ('' == trim($alioss['key'])) {
                    wl_json(-1, '阿里云OSS-Access Key ID不能为空');
                }
                if ('' == trim($alioss['secret'])) {
                    wl_json(-1, '阿里云OSS-Access Key Secret不能为空');
                }
                $buckets = attachment_alioss_buctkets($alioss['key'], $alioss['secret']);
                if (is_error($buckets)) {
                    wl_json(-1, 'OSS-Access Key ID 或 OSS-Access Key Secret错误，请重新填写');
                }
                list($alioss['bucket'], $alioss['url']) = explode('@@', $_GPC['alioss']['bucket']);
                if (empty($buckets[$alioss['bucket']])) {
                    wl_json(-1, 'Bucket不存在或是已经被删除');
                }
                $alioss['url'] = 'http://' . $alioss['bucket'] . '.' . $buckets[$alioss['bucket']]['location'] . '.aliyuncs.com';
                $alioss['ossurl'] = $buckets[$alioss['bucket']]['location'] . '.aliyuncs.com';
                if (!empty($_GPC['custom']['url'])) {
                    $url = trim($_GPC['custom']['url'], '/');
                    if (!strexists($url, 'http://') && !strexists($url, 'https://')) {
                        $url = 'http://' . $url;
                    }
                    $alioss['url'] = $url;
                }
            }

            if($base['service'] == 3){
                if (empty($qiniu['accesskey'])) {
                    wl_json(-1, '请填写Accesskey.');
                }
                if (empty($qiniu['secretkey'])) {
                    wl_json(-1, '请填写secretkey.');
                }
                if (empty($qiniu['bucket'])) {
                    wl_json(-1, '请填写bucket.');
                }
                if (empty($qiniu['url'])) {
                    wl_json(-1, '请填写url.');
                } else {
                    $qiniu['url'] = strexists($qiniu['url'], 'http') ? trim($qiniu['url'], '/') : 'http://' . trim($qiniu['url'], '/');
                }
            }

            if($base['service'] == 4){
                if (empty($tengxun['appid'])) {
                    wl_json(-1, '请填写APPID');
                }
                if (empty($tengxun['secretid'])) {
                    wl_json(-1, '请填写SECRETID');
                }
                if (empty($tengxun['secretkey'])) {
                    wl_json(-1, '请填写SECRETKEY');
                }
                if (empty($tengxun['bucket'])) {
                    wl_json(-1, '请填写BUCKET');
                }
            }

            $base['alioss'] = $alioss;
            $base['qiniu'] = $qiniu;
            $base['tengxun'] = $tengxun;

            Setting::wlsetting_save($base,'enclosure');
            wl_message('更新设置成功！', web_url('setting/shopset/enclosure'));
        }
        include wl_template('setting/enclosure');
    }


}
