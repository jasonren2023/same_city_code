<?php
defined('IN_IA') or exit('Access Denied');

class Foot_WeliamController {

    public function index() {
        global $_W, $_GPC;
        //接收并且储存设置信息
        if (checksubmit('submit')) {
            $info = $_GPC['info'];
            $res1 = Setting::agentsetting_save($info, 'foot');
            if ($res1) {
                wl_message('保存设置成功！', referer(), 'success');
            } else {
                wl_message('保存设置失败！', referer(), 'error');
            }
        }
        //获取设置信息
        $set = self::getMenuInfo();


        include wl_template('dashboard/footIndex');
    }

    /**
     * Comment: 获取菜单设置信息
     * Author: zzw
     * Date: 2019/11/2 10:06
     * @return array|bool|mixed
     */
    protected static function getMenuInfo(){
        $set = Setting::agentsetting_read('foot');
        if(!is_array($set['list']) || !is_array($set['list']['one']) || count($set['list']) != 5){
            return [
                'status' => $set['status'] ? $set['status'] : 0 ,//0=使用默认；1=使用当前自定义菜单
                'list'   => [
                    'one'   => [
                        'default_name' => '首页' ,//默认名称
                        'diy_name'     => '' ,//自定义名称
                        'default_img'  => '' ,//默认图片
                        'selected_img' => '' ,//选中图片
                        'link'         => '' ,//跳转链接
                        'switch'       => 0 ,//是否开启：0=关，1=开启
                    ] ,
                    'two'   => [
                        'default_name' => '附近' ,//默认名称
                        'diy_name'     => '' ,//自定义名称
                        'default_img'  => '' ,//默认图片
                        'selected_img' => '' ,//选中图片
                        'link'         => '' ,//跳转链接
                        'switch'       => 0 ,//是否开启：0=关，1=开启
                    ] ,
                    'three' => [
                        'default_name' => '一卡通' ,//默认名称
                        'diy_name'     => '' ,//自定义名称
                        'default_img'  => '' ,//默认图片
                        'selected_img' => '' ,//选中图片
                        'link'         => '' ,//跳转链接
                        'switch'       => 0 ,//是否开启：0=关，1=开启
                    ] ,
                    'four'  => [
                        'default_name' => '入驻' ,//默认名称
                        'diy_name'     => '' ,//自定义名称
                        'default_img'  => '' ,//默认图片
                        'selected_img' => '' ,//选中图片
                        'link'         => '' ,//跳转链接
                        'switch'       => 0 ,//是否开启：0=关，1=开启
                    ] ,
                    'five'  => [
                        'default_name' => '我的' ,//默认名称
                        'diy_name'     => '' ,//自定义名称
                        'default_img'  => '' ,//默认图片
                        'selected_img' => '' ,//选中图片
                        'link'         => '' ,//跳转链接
                        'switch'       => 0 ,//是否开启：0=关，1=开启
                    ] ,
                ] ,
            ];
        }

        return $set;
    }


    public function searchSet() {
        global $_W, $_GPC;
        //接收并且储存设置信息
        if (checksubmit('submit')) {
            $info = $_GPC['plugin'];
            $res1 = Setting::agentsetting_save($info, 'searchset');
            if ($res1) {
                wl_message('保存设置成功！', referer(), 'success');
            } else {
                wl_message('保存设置失败！', referer(), 'error');
            }
        }
        $plugin = Setting::agentsetting_read('searchset');
        if(empty($plugin)){
            $plugin = [
                'spstatus' => 1,
                'ttstatus' => 1,
                'dpstatus' => 1,
                'tzstatus' => 1,
                'sporder' => 4,
                'ttorder' => 3,
                'dporder' => 2,
                'tzorder' => 1,
            ];
        }



        include wl_template('dashboard/searchSet');
    }


}