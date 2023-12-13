<?php
define('IN_UNIAPP', true);
header('Access-Control-Allow-Origin:*');
if(is_file('../../../../wlversion.txt')){
    $version = file_get_contents('../../../../wlversion.txt');
    define("MODULE_NAME",$version);
}else{
    define("MODULE_NAME",'weliam_smartcity');
}
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require PATH_MODULE . "/vendor/autoload.php";
require PATH_CORE . "common/autoload.php";
Func_loader::core('global');
global $_W, $_GPC;
load()->model('attachment');

//判断公众号相关
$_W['siteroot'] = str_replace(array('/addons/'.MODULE_NAME.'/core/common','/addons/weliam_smartcity/core/common','/addons/weliam_smartcity','/addons/'.MODULE_NAME), '', $_W['siteroot']);
$_W['method'] = $method = !empty($_GPC['do']) ? $_GPC['do'] : 'index';
$_W['uniacid'] = intval($_GPC['i']);
if (empty($_W['uniacid'])) {
    $_W['uniacid'] = intval($_GPC['weid']);
}
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
if (empty($_W['uniaccount'])) {
    header('HTTP/1.1 404 Not Found');
    header("status: 404 Not Found");
    exit;
}
$_W['acid'] = $_W['uniaccount']['acid'];
$_W['attachurl'] = attachment_set_attach_url();
$_W['wlsetting'] = Setting::wlsetting_load();
$_W['source'] = $_GPC['source'] ? intval($_GPC['source']) : 1;//1=公众号（默认）；2=h5；3=小程序
$_W['aid'] = intval($_GPC['aid'])?intval($_GPC['aid']):0;//代理id

//寻找对应方法
$plugin = trim($_GPC['p']);
if (empty($plugin)) {
    require IA_ROOT . "/addons/".MODULE_NAME."/uniapp.php";
    $instance = new Weliam_smartcityModuleUniapp();
    if (!method_exists($instance, $method)) {
        $instance->securityVerification($method);//调用方法进行安全验证
    }
} else {
    $uniapp = new Uniapp();
    //判断文件是否存在
    $filePath = IA_ROOT . "/addons/".MODULE_NAME."/plugin/" . $plugin . "/uniapp.php";
    if (!file_exists($filePath)) $filePath = IA_ROOT . "/addons/".MODULE_NAME."/api/" . ucfirst($plugin) . ".php";
    if (!file_exists($filePath)) $uniapp->renderError('错误的请求 - file does not exist! ');
    require $filePath;
    //判断类是否存在
    $className = ucfirst($plugin) . 'ModuleUniapp';
    if (class_exists($className)) {
        $instance = new $className();
        if (method_exists($instance, $method)) $instance->$method();
        else $uniapp->renderError('错误的请求 - '.$instance.':'.$method.'Method does not exist! ');
    } else {
        $uniapp->renderError('错误的请求 - '.$className.'Non-existent classes! ');
    }
}


class Category {
    public $disSetting;//分销设置信息

    #0=操作成功；1=操作失败；2=需要登录；3=未开通地区;4=隐藏信息，仅开发者查看使用;5=小程序端必须登录才能访问的接口
    public function __construct() {
        $field = 'id,name';
        $table = tablename(PDO_NAME."headline_class");
        $info = pdo_fetch("SELECT {$field} FROM " . $table );
        return $info;
    }
    /**
     * Comment: 操作成功输出方法
     * Author: zzw
     * Date: 2019/7/16 9:35
     * @param string $message
     * @param array $data
     */
    public function renderSuccess($message = '操作成功', $data = [],$nodo = 0) {
        global $_W,$_GPC;
        //JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK,JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT
        $content = json_encode(['errno' => 0, 'message' => $message, 'data' => $data]);
        //if($_GPC['do'] != 'getGoodsExtensionInfo' && $_GPC['do'] != 'transferDetail'){
        if(empty($nodo)){
            //开启链接处理功能一   目前主要兼容未在#号前添加问号（?）的链接
            //$content = str_replace('weliam_smartcity\/h5\/#\/', 'weliam_smartcity\/h5\/?#\/', $content);
            $prefixPaths = explode('#/', h5_url('pages/mainPages/index/diypage'))[0] . '#/';
            //处理富文本超链接一  带有href跳转的链接转换为指定字符串
            $temporaryUrl = 'this_is_temporary_url_info';
            $hrefPath = json_encode('href="'.$prefixPaths);
            $content = str_replace(trim($hrefPath, '"'), $temporaryUrl, $content);
            //开启链接处理功能二   目前主要兼容删除?#/前面的链接 只保留page路径信息及参数信息
            $prefixPaths = explode('#/', h5_url('pages/mainPages/index/diypage'))[0] . '#/';
            $jsonPath = json_encode($prefixPaths);
            $content = str_replace(trim($jsonPath, '"'), '', $content);
            //处理富文本超链接二 将字段字符串转为超链接前缀
            $temporaryUrl = 'this_is_temporary_url_info';
            $content = str_replace($temporaryUrl, trim($hrefPath, '"'), $content);
        }

        exit($content);
    }
    /**
     * Comment: 操作失败返回内容
     * Author: zzw
     * Date: 2019/7/16 9:36
     * @param string $message
     * @param array $data
     */
    public function renderError($message = '操作失败', $data = []) {
        global $_W;
        //JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK,JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT
        $content = json_encode(['errno' => 1, 'message' => $message, 'data' => $data]);
        //是否开启链接处理功能一   目前主要兼容未在#号前添加问号（?）的链接
        $state1 = $_REQUEST['debug'] ? true : true;
//        if ($state1) {
//            $content = str_replace('weliam_smartcity\/h5\/#\/', 'weliam_smartcity\/h5\/?#\/', $content);
//        }
        //是否开启链接处理功能二   目前主要兼容删除?#/前面的链接 只保留page路径信息及参数信息
        $state2 = $_REQUEST['debug'] ? true : true;
        if ($state2) {
            $prefixPaths = explode('#/', h5_url('pages/mainPages/index/diypage'))[0] . '#/';
            $jsonPath = json_encode($prefixPaths);
            $content = str_replace(trim($jsonPath, '"'), '', $content);
        }

        exit($content);
    }
    /**
     * Comment: 登录错误，重新登录
     * Author: zzw
     * Date: 2019/7/29 16:40
     * @param string $message
     */
    public function reLogin() {
        global $_W;
        $errno = ($_W['source'] == 3) ? 5 : 2;
        exit(json_encode(array(
            'errno'   => $errno,//2
            'message' => '请先登录',
            'data'    => [
                'weChat_login' => $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=' . MODULE_NAME . '&do=wechatsign',
            ],
        )));
    }
    /**
     * Comment: 隐藏信息，仅开发者查看使用
     * Author: zzw
     * Date: 2019/11/1 10:07
     * @param string $message
     * @param array $data
     */
    public function debugging($message = '维护中....', $data = []) {
        exit(json_encode(array(
            'errno'   => 4,
            'message' => $message,
            'data'    => $data,
        )));
    }
    /**
     * Comment: 不需要登录验证的接口列表
     * Author: zzw
     * Date: 2019/7/29 16:24
     * @return array
     */
    protected function noLoginApiList($source) {
        $list = ['member/login', 'headline/HeadlineList','headline/HeadlineInfo','headline/getComment', 'distribution/getSetting', 'store/homeList', 'rush/homeList', 'rush/specialInfo', 'wlcoupon/homeList' ,
            'halfcard/homeList', 'wlfightgroup/homeList', 'pocket/homeList', 'groupon/homeList', 'bargain/homeList', 'consumption/homeList', 'halfcard/packageList',
            'member/getRegisterSet', 'member/register', 'member/userLogin', 'member/resetPassword','citycard/homeList','citycard/cardHome','citycard/cardInfo',
            'redpack/newRedPackInfo','pocket/commentList','redpack/festivalRedPackDesc','citydelivery/finishOrder','goods/getGoodsCateList','recruit/homeList',
            'dating/homeList','im/infoList','Im/getSetInfo','dating/homeList','dating/dynamicList','vehicle/homeList','member/checkCode'];
        if($source == 3 || $source == 2){
            //小程序不需要登录的接口
            $newList = [
                'store/chargeList','store/detail','store/getStoreGoods','store/getStoreComment','store/getStoreDynamic',
                'store/cateList','goods/getGoodsDetail', 'member/memberInfo','helper/helpInfo','helper/helpInfo','helper/problemList', 'helper/detail',
                'distribution/applyCondition', 'citycard/cardShare','halfcard/memberCardHome','halfcard/cardList', 'consumption/homeInfo', 'consumption/detail',
                'pocket/homeInfo','pocket/classList','pocket/receivingRecords','pocket/getPocketStore','store/userShopList', 'pocket/infoList','pocket/detail','pocket/shareNum','pocket/getDisclaimer','wxapp/liveList','bargain/detail','housekeep/getStorelist',
                'housekeep/getServicelist','housekeep/getArtificerlist','housekeep/getDemandlist','housekeep/getNewStorelist','recruit/recruitDesc','wlfightgroup/groupDetail','yellowpage/homeInfo','yellowpage/homeList',
                'yellowpage/cateList','luckydraw/drawPageInfo','luckydraw/getShareCode'
            ];
            $list = array_merge($newList,$list);
        }

        return $list;
    }

}
