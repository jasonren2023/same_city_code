<?php
defined('IN_IA') or exit('Access Denied');

class Plugins_WeliamController {
    /*
     * 入口函数
     */
    public function index() {
        global $_W, $_GPC;
        if (is_agent()) {
            $category = App::get_cate_plugins('agent');
        }else if(is_store()){
            $category = App::get_cate_plugins('store');
        } else {
            $category = App::get_cate_plugins('sys');
        }

        include wl_template('app/plugins_list');
    }

}
