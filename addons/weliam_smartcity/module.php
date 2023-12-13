<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/core/common/defines.php";
require_once PATH_CORE . "common/autoload.php";
Func_loader::core('global');

class Weliam_smartcityModule extends WeModule {
    public function welcomeDisplay() {
        global $_W, $_GPC;
        header('location: ' . web_url('dashboard/dashboard/index'));
        exit();
    }
}
class Weliam_smartcity1Module extends WeModule {
    public function welcomeDisplay() {
        global $_W, $_GPC;
        header('location: ' . web_url('dashboard/dashboard/index'));
        exit();
    }
}