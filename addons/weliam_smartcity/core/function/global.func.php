<?php

function getAllPluginsName() {
    return array('Rush', 'Merchant', 'wlCoupon', 'halfcard', 'Wlfightgroup', 'Pocket');
}

function createUniontid() {
    global $_W;
    $moduleid = pdo_getcolumn("modules", array('name' => MODULE_NAME), 'mid');
    $moduleid = empty($moduleid) ? '000000' : sprintf("%06d", $moduleid);
    $uniontid = date('YmdHis') . $moduleid . random(8, 1);
    //生成商户订单号
    return $uniontid;
}

/**
 * model类实例函数
 *
 * @access public
 * @name m
 * @param $filename  文件夹/文件名
 * @return 对象
 */
function m($filename = '') {
    static $_modules = array();
    if (strpos($filename, '/') > -1) {
        [$file, $name] = explode('/', $filename);
    } else {
        die('文件结构不正确，正确结构（文件夹名/文件名）');
    }
    if (isset($_modules[$file][$name]))
        return $_modules[$file][$name];
    $model = PATH_CORE . "library/" . $file . "/" . $name . '.lib.php';
    if (!is_file($model)) {
        die('Library Class ' . $filename . ' Not Found!');
    }
    require $model;
    $class_name = ucfirst($name);
    //调用该类
    $_modules[$file][$name] = new $class_name();
    return $_modules[$file][$name];
}
/**
 * Comment: 判断 当前模块权限
 * Author: zzw
 * Date: 2020/11/16 11:24
 * @param string $name
 * @return bool
 */
function p($name = '') {
    global $_W;
    //默认拥有当前模块权限
    $status = true;
    //判断文件是否存在 不存在则无权限
    $model = PATH_PLUGIN . strtolower($name) . '/config.xml';
    if (!is_file($model)) $status = false;


    return $status;
}

/**
 * Comment: 判断 当前公众号是否有此权限
 * Author: wlf
 * Date: 2021/09/26 14:36
 * @param string $name
 * @return bool
 */
function uniacid_p($name = '') {
    global $_W;
    $status = 1;
    if($name == 'disgroup'){
        if(empty(Customized::init('groupon138'))){
            $status = 0;
        }
    }else{
        $pp = p($name);
        if($pp){
            $plugin = pdo_getcolumn(PDO_NAME.'perm_account',array('uniacid'=>$_W['uniacid']),'plugins');
            $plugin = unserialize($plugin);
            if(!empty($plugin)){
                if(!in_array($name,$plugin)){
                    $status = 0;
                }
            }
        }else{
            $status = 0;
        }
    }
    return $status;
}

/**
 * Comment: 判断 当前代理是否有此权限
 * Author: wlf
 * Date: 2021/09/26 15:06
 * @param string $name
 * @return bool
 */
function agent_p($name = ''){
    global $_W;
    $status = 1;
    $up = uniacid_p($name);
    if($up > 0){
        //获取代理分组权限信息
        if($_W['aid'] > 0){
            $groupid = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$_W['aid']),'groupid');
            if($groupid > 0){
                $plugin = pdo_getcolumn(PDO_NAME.'agentusers_group',array('id'=>$groupid),'package');
                $plugin = unserialize($plugin);
                if(!empty($plugin)){
                    if(!in_array($name,$plugin)){
                        $status = 0;
                    }
                }
            }
        }
    }else{
        $status = 0;
    }
    return $status;
}

/**
 * Comment: 判断 当前商户是否有此权限
 * Author: wlf
 * Date: 2021/09/26 15:19
 * @param string $name
 * @return bool
 */
function store_p($name = '',$sid){
    global $_W;
     if($sid > 0){
         $status = 1;
         $ap = agent_p($name);
         if($ap > 0){
             $groupid = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid),'groupid');
             if($groupid > 0){
                 $plugin = pdo_getcolumn(PDO_NAME.'chargelist',array('id'=>$groupid),'authority');
                 $plugin = unserialize($plugin);
                 if(!empty($plugin)){
                     if(!in_array($name,$plugin)){
                         $status = 0;
                     }
                 }
             }
         }else{
             $status = 0;
         }
     } else{
         $status = 0;
     }
    return $status;
}


/**
 * Comment: 判断当前模块是否存在权限
 * Author: zzw
 * Date: 2020/11/16 19:45
 * @param $name
 * @return bool
 */
function j($name){
    //TODO 当前权限判断功能需要优化  请求内容和判断内容过多 导致加载缓慢  当时为了方便直接使用的其他方法的返回结果在重新判断 可以重写判断不适用其他方法返回结果进行重新判断
    //默认拥有当前模块权限
    $status = true;
    //判断是否存在权限
    if(is_agent()) $wholePlugin = App::get_apps(0, 'agent');//全部插件
    else if(is_store()) $wholePlugin = App::get_apps(0, 'store');//全部插件
    else $wholePlugin = App::get_apps(0, 'account');//全部插件
    $wholeIdents = array_column($wholePlugin,'ident');//插件昵称
    $plugins = App::get_cate_plugins('agent');//当前代理商户拥有权限的插件
    $channel = array_column($plugins['channel']['plugins'],'ident');
    $market = array_column($plugins['market']['plugins'],'ident');
    $interact = array_column($plugins['interact']['plugins'],'ident');
    $expand = array_column($plugins['expand']['plugins'],'ident');
    $help = array_column($plugins['help']['plugins'],'ident');
    $idents = array_merge((array)$channel, (array)$market, (array)$interact, (array)$expand, (array)$help);
    //当前插件存在权限处理  当前代理无当前权限
    if(is_agent() || is_store()) {
        if(!in_array($name,$wholeIdents) || !in_array($name,$idents)) $status = false;
    }else{
        if(!in_array($name,$wholeIdents)) $status = false;
    }

    return $status;
}
/**
 * 权限检测函数
 *
 * @access public
 * @name checkLimit
 * @param $roleid  角色id
 * @param $arr  检测位置
 * @return false|true
 */
function checkLimit($roleid, $arr = array()) {
    $allPerms = Perms::allParms();
    if (empty($allPerms[$arr['p']][$arr['ac']][$arr['do']]))
        return true;
    $limits = Perms::getRolePerms($roleid);
    if (empty($limits) || empty($arr))
        return false;
    if (empty($limits[$arr['p']][$arr['ac']][$arr['do']]))
        return false;
    return true;
}

if ((!function_exists('url') && defined('IN_UNIAPP')) || !function_exists('url') && defined('IS_INDEPENDENT')) {
    function url($segment, $params = array(), $noredirect = false) {
        return murl($segment, $params, $noredirect);
    }
}


/**
 * web地址生成
 *
 * @access public
 * @name getUrlByWeb
 * @param $segment do/ac/op
 * @param $params 参数
 * @return string
 */
function web_url($segment, $params = array()) {
    global $_W;
    [$p, $ac, $do] = explode('/', $segment);
    if (is_store()) {
        $url = $_W['siteroot'] . 'web/citystore.php?';
    } else if (is_agent()) {
        $url = $_W['siteroot'] . 'web/cityagent.php?';
    } else if (is_staff()) {
        $url = $_W['siteroot'] . 'web/citysys.php?';
    } else {
        $url = $_W['siteroot'] . 'web/index.php?c=site&a=entry&m=' . MODULE_NAME . '&';
    }
    if (!empty($p)) {
        $url .= "p={$p}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    $do = !empty($do) ? $do : 'index';
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    return $url;
}

/**
 * APP地址生成
 *
 * @access public
 * @name app_url
 * @param $segment do/ac/op
 * @param $params 参数
 * @return string
 */
function app_url($segment, $params = array(), $addhost = TRUE) {
    global $_W;
    [$p, $ac, $do] = explode('/', $segment);

    if ($addhost == TRUE) {
        $_W['siteroot'] = str_replace(array('/addons/' . MODULE_NAME,'/addons/weliam_smartcity','/core/common', '/addons/bm_payms'), '', $_W['siteroot']);
        $url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=' . MODULE_NAME . '&';
    } else {
        $url = './index.php?i=' . $_W['uniacid'] . '&c=entry&m=' . MODULE_NAME . '&';
    }

    if (!empty($p)) {
        $url .= "p={$p}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    $do = !empty($do) ? $do : 'index';
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    if (substr($url, -1) == '&') {
        $url = substr($url, 0, strlen($url) - 1);
    }

    return $url;
}

/**
 * Comment: H5链接地址生成  强制生成为https链接
 * Author: zzw
 * Date: 2019/7/2 16:37
 * @param string $page
 * @param array $params
 * @param        $addhost
 * @return bool|string
 */
function h5_url($page, $params = array(), $catalog = 'h5',$aid=0) {
    global $_W;
    $_W['siteroot'] = str_replace(array('/addons/' . MODULE_NAME,'/addons/weliam_smartcity','/plugin/pftapimod','/core/common', '/addons/bm_payms','/addons/weliam_smartcity/payment','/addons/'.MODULE_NAME.'/payment','/payment'), '', $_W['siteroot']);
    $_W['siteroot'] = str_replace(':19080','',$_W['siteroot']);
    $is_have = strpos($page, '?');
    if ($is_have) {
        $info = explode('?', $page);
        $page = $info[0];
        $paramStr = $info[1];
        //判断参数是否存在i 存在则删除
        foreach (explode('&', $paramStr) as $key => $value) {
            if ($value{0} == 'i' && $value{1} == '=') {
                $paramStr = str_replace($value . '&', '', $paramStr);
                break;
            }
        }
    }
    if(empty($aid)){
        $aid = $_W['aid'];
    }
    if(empty($catalog)){
        $catalog = 'h5';
    }
    $url = $_W['siteroot'] . 'addons/' . MODULE_NAME . '/' . $catalog . '/#/' . $page . "?i=" . $_W['uniacid'] . '&aid=' .$aid. '&' . $paramStr;
    $url = trim($url, "&");
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= '&' . trim($queryString, "&");
    }

    $domain = Cloud::wl_syssetting_read('jumpadmin');
    if(!empty($domain['targetDmain'])){
        foreach($domain['targetDmain'] as $tar){
            $url = str_replace($tar,$domain['endDmain'],$url);
        }
    }

    return $url;
}

function is_store() {
    if (defined('IN_STORE')) {
        return true;
    }
    return false;
}
function is_agent() {
    if (defined('IN_WEB')) {
        return true;
    }
    return false;
}
function is_staff() {
    if (defined('IN_STAFF')) {
        return true;
    }
    return false;
}
if (!function_exists('is_wxapp')) {
    function is_wxapp() {
        global $_W;
        if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram')) {
            return true;
        }

        return false;
    }

}

if (!function_exists('is_weixin')) {
    function is_weixin() {
        global $_W;

        if (empty($_SERVER['HTTP_USER_AGENT']) || ((strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === false))) {
            return false;
        }

        return true;
    }

}

if (!function_exists('is_h5app')) {
    function is_h5app() {
        if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'CK 2.0')) {
            return true;
        }

        return false;
    }

}

if (!function_exists('is_ios')) {
    function is_ios() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            return true;
        }

        return false;
    }

}

if (!function_exists('is_mobile')) {
    function is_mobile() {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(android|bb\\d+|meego).+mobile|avantgo|bada\\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\\-(n|u)|c55\\/|capi|ccwa|cdm\\-|cell|chtm|cldc|cmd\\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\\-s|devi|dica|dmob|do(c|p)o|ds(12|\\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\\-|_)|g1 u|g560|gene|gf\\-5|g\\-mo|go(\\.w|od)|gr(ad|un)|haie|hcit|hd\\-(m|p|t)|hei\\-|hi(pt|ta)|hp( i|ip)|hs\\-c|ht(c(\\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\\-(20|go|ma)|i230|iac( |\\-|\\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\\/)|klon|kpt |kwc\\-|kyo(c|k)|le(no|xi)|lg( g|\\/(k|l|u)|50|54|\\-[a-w])|libw|lynx|m1\\-w|m3ga|m50\\/|ma(te|ui|xo)|mc(01|21|ca)|m\\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\\-2|po(ck|rt|se)|prox|psio|pt\\-g|qa\\-a|qc(07|12|21|32|60|\\-[2-7]|i\\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\\-|oo|p\\-)|sdk\\/|se(c(\\-|0|1)|47|mc|nd|ri)|sgh\\-|shar|sie(\\-|m)|sk\\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\\-|v\\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\\-|tdg\\-|tel(i|m)|tim\\-|t\\-mo|to(pl|sh)|ts(70|m\\-|m3|m5)|tx\\-9|up(\\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\\-|your|zeto|zte\\-/i', substr($useragent, 0, 4))) {
            return true;
        }

        return false;
    }

}

function ifilter_url($params) {
    return wl_filter_url($params);
}

function wl_setcookie($key, $value, $expire = 0, $httponly = false) {
    global $_W;
    $expire = $expire != 0 ? (TIMESTAMP + $expire) : 0;
    $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
    $value = is_array($value) ? base64_encode(json_encode($value)) : $value;
    return setcookie('weliam_' . $key, $value, $expire, $_W['config']['cookie']['path'], $_W['config']['cookie']['domain'], $secure, $httponly);
}

function wl_getcookie($key) {
    $sting = $_COOKIE['weliam_' . $key];
    $data = json_decode(base64_decode($sting), true);
    return is_array($data) ? $data : $sting;
}

//2019.9.4  默认：1=失败，0=成功
function wl_json($errno = 0, $message = '', $data = '') {
    exit(json_encode(array('errno' => $errno, 'message' => $message, 'data' => $data)));
}

function wl_new_method($plugin, $controller, $method, $catalog = 'app') {
    global $_W;
    $dir = IA_ROOT . '/addons/' . MODULE_NAME . '/';
    $file = $dir . $catalog . '/controller/' . $plugin . '/' . $controller . '.ctrl.php';
    if (!is_file($file)) {
        $file = $dir . 'plugin/' . $plugin . '/' . $catalog . '/controller/' . $controller . '.ctrl.php';
    }
    if (($catalog == 'web' || $catalog == 'sys') && !is_file($file)) {
        $_W['catalog'] = $catalog = ($catalog == 'web') ? 'sys' : 'web';
        $file = $dir . $catalog . '/controller/' . $plugin . '/' . $controller . '.ctrl.php';
        if (!is_file($file)) {
            $file = $dir . 'plugin/' . $plugin . '/' . $catalog . '/controller/' . $controller . '.ctrl.php';
        }
    }
    if (is_file($file)) {
        require_once $file;
    } else {
        trigger_error("访问的模块 {$plugin} 不存在.", E_USER_WARNING);
    }
    $class = ucfirst($controller) . '_WeliamController';
    if (class_exists($class, false)) {
        $instance = new $class();
    } else {
        $instance = new $controller();
    }
    if ($catalog == 'app') {
        $instance->inMobile = TRUE;
    }
    if (!method_exists($instance, $method)) {
        trigger_error('控制器 ' . $controller . ' 方法 ' . $method . ' 未找到!');
    }

    $instance->$method();
    exit();
}

/**
 * html文件引入，生成缓存
 *
 * @access public
 * @name wl_template
 * @param $filename  文件
 * @return string
 */
function wl_template($filename, $flag = '') {
    global $_W;
    $name = MODULE_NAME;
    $catalog = strstr($filename, 'common/') ? 'web' : $_W['catalog'] ? : 'web';
    $source = IA_ROOT . "/addons/{$name}/{$catalog}/view/default/{$filename}.html";
    $compile = IA_ROOT . "/data/tpl/{$catalog}/{$name}/{$_W['plugin']}/{$catalog}/view/default/{$filename}.tpl.php";
    if (!is_file($source)) $source = IA_ROOT . "/addons/{$name}/plugin/{$_W['plugin']}/{$catalog}/view/default/{$filename}.html";
    if (!is_file($source)) exit("Error: template source '{$filename}' is not exist!!!");
    if (!is_file($compile) || filemtime($source) > filemtime($compile)) Template::wl_template_compile($source, $compile, true);
    if ($flag == TEMPLATE_FETCH) {
        extract($GLOBALS, EXTR_SKIP);
        ob_end_flush();
        ob_clean();
        ob_start();
        include $compile;
        $contents = ob_get_contents();
        ob_clean();
        return $contents;
    } else if ($flag == 'template') {
        extract($GLOBALS, EXTR_SKIP);
        return $compile;
    } else {
        return $compile;
    }

/*    $name = MODULE_NAME;
    if (defined('IN_SYS') || defined('IN_WEB')) {
        $catalog = strstr($filename, 'common/') ? 'web' : $_W['catalog'];
        $source = IA_ROOT . "/addons/{$name}/{$catalog}/view/default/{$filename}.html";
        $compile = IA_ROOT . "/data/tpl/{$catalog}/{$name}/{$catalog}/view/default/{$filename}.tpl.php";
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/plugin/{$_W['plugin']}/{$catalog}/view/default/{$filename}.html";
        }
    }

    if (defined('IN_APP')) {
        $template = "default";
        if (!empty($_W['wlsetting']['templat']['appview'])) {
            $template = $_W['wlsetting']['templat']['appview'];
        }
        $source = IA_ROOT . "/addons/{$name}/app/view/{$template}/{$filename}.html";
        $compile = IA_ROOT . "/data/tpl/app/{$name}/app/view/{$template}/{$filename}.tpl.php";
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/app/view/default/{$filename}.html";
        }
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/plugin/{$_W['plugin']}/app/view/{$template}/{$filename}.html";
        }
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/plugin/{$_W['plugin']}/app/view/default/{$filename}.html";
        }
        if (!is_file($source)) {
            $filenames = explode("/", $filename);
            $source = IA_ROOT . "/addons/{$name}/plugin/{$filenames[0]}/app/view/default/{$filename}.html";
        }
        if (!empty($_W['wlsetting']['trade']['credittext']) || !empty($_W['wlsetting']['trade']['moneytext'])) {
            $compile = IA_ROOT . "/data/tpl/app/{$name}/{$_W['uniacid']}/app/view/{$template}/{$filename}.tpl.php";
        }
        if (!empty($_W['wlsetting']['halfcard']['text']['halfcardtext']) || !empty($_W['wlsetting']['halfcard']['text']['privilege'])) {
            $compile = IA_ROOT . "/data/tpl/app/{$name}/{$_W['uniacid']}/app/view/{$template}/{$filename}.tpl.php";
        }
    }
    if (!is_file($source)) {
        exit("Error: template source '{$filename}' is not exist!!!");
    }
    if (!is_file($compile) || filemtime($source) > filemtime($compile)) {
        Template::wl_template_compile($source, $compile, true);
    }
    if ($flag == TEMPLATE_FETCH) {
        extract($GLOBALS, EXTR_SKIP);
        ob_end_flush();
        ob_clean();
        ob_start();
        include $compile;
        $contents = ob_get_contents();
        ob_clean();
        return $contents;
    } else if ($flag == 'template') {
        extract($GLOBALS, EXTR_SKIP);
        return $compile;
    } else {
        return $compile;
    }*/
}

function wl_message($msg, $redirect = '', $type = '') {
    global $_W, $_GPC;
    if ($redirect == 'refresh') {
        $redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
    }
    if ($redirect == 'referer') {
        $redirect = referer();
    }
    if ($redirect == 'close') {
        $redirect = 'wx.closeWindow()';
        $close = 1;
    }
    if ($redirect == '') {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'fixed')) ? $type : 'info';
    } else {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'fixed')) ? $type : 'success';
    }
    if (IN_WXAPP == 'wxapp') {
        die(json_encode(array('errno' => $type == 'success' ? 0 : 1, 'message' => $msg, 'data' => $redirect)));
    }
    if ($_W['isajax'] || !empty($_GET['isajax']) || $type == 'ajax') {
        if ($type != 'ajax' && !empty($_GPC['target'])) {
            exit("
<script type=\"text/javascript\">
parent.require(['jquery', 'util'], function($, util){
	var url = " . (!empty($redirect) ? 'parent.location.href' : "''") . ";
	var modalobj = util.message('" . $msg . "', '', '" . $type . "');
	if (url) {
		modalobj.on('hide.bs.modal', function(){\$('.modal').each(function(){if(\$(this).attr('id') != 'modal-message') {\$(this).modal('hide');}});top.location.reload()});
	}
});
</script>");
        } else {
            //ajaxsubmit兼容方案
            if ($_W['isajax'] && !empty($_GPC['token'])) {
                $ret = array('status' => $type == 'success' ? 1 : 0, 'result' => $type == 'success' ? array('url' => $redirect ? $redirect : referer()) : array());
                $ret['result']['message'] = $msg;
                exit(json_encode($ret));
            } else {
                $vars = array();
                if (is_array($msg)) {
                    $vars['errno'] = $msg['errno'];
                    $vars['message'] = $msg['message'];
                    die(json_encode($vars));
                } else {
                    $vars['message'] = $msg;
                }
                $vars['redirect'] = $redirect;
                $vars['type'] = $type;
                die(json_encode($vars));
            }
        }
    } elseif (is_array($msg)) {
        $msg = $msg['message'];
    }
    if (empty($msg) && !empty($redirect)) {
        header('location: ' . $redirect);
    }
    $label = $type;
    if ($type == 'error') {
        $label = 'danger';
    }
    if ($type == 'ajax' || $type == 'sql') {
        $label = 'warning';
    }

    include wl_template('common/message', TEMPLATE_INCLUDEPATH);
    exit();
}

/**
 * debug打印
 *
 * @access public
 * @name wl_debug
 * @param $value  需要打印的参数
 */
function wl_debug($value) {
    echo "<br><pre>";
    print_r($value);
    echo "</pre>";
    exit;
}

/**
 * Comment: 自有的debug模式  只有指定人员访问才会debug，其他人员访问不debug。
 * Author: zzw
 * Date: 2019/11/12 9:27
 * @param array | string | int $value debug的内容
 * @param int  $mid     用户id
 * @param bool $status  是否强制打印
 */
function private_debug($value,$mid = 0,$status = false){
    global $_W;
    if($status || ($_W['mid'] == $mid || $_REQUEST['debug'])){
        echo "<br><pre>";
        print_r($value);
        echo "</pre>";
        exit;
    }
}


/**
 * 日志文件打印
 *
 * @access public
 * @name wl_log
 * @param $filename  文件名
 * @param $title  打印标题
 * @param $data  需要打印内容
 * @return array
 */
function wl_log($filename, $title, $data) {
    global $_W;
    if ($uniacid != '') {
        $_W['uniacid'] = $uniacid;
    }
    $url_log = PATH_DATA . "log/" . date('Y-m-d', time()) . "/" . $filename . ".log";
    $url_dir = PATH_DATA . "log/" . date('Y-m-d', time());
    Util::mkDirs($url_dir);
    file_put_contents($url_log, var_export('/=======' . date('Y-m-d H:i:s', time()) . '【' . $title . '】=======/', true) . PHP_EOL, FILE_APPEND);
    file_put_contents($url_log, var_export($data, true) . PHP_EOL, FILE_APPEND);
}

function wl_filter_url($params) {
    global $_W;
    if (empty($params)) {
        return '';
    }
    $query_arr = array();
    $parse = parse_url($_W['siteurl']);
    if (!empty($parse['query'])) {
        $query = $parse['query'];
        parse_str($query, $query_arr);
    }
    $params = explode(',', $params);
    foreach ($params as $val) {
        if (!empty($val)) {
            $data = explode(':', $val);
            $query_arr[$data[0]] = trim($data[1]);
        }
    }
    $query_arr['page'] = 1;
    $query = http_build_query($query_arr);
    if(defined('IN_WEB')){
        return './cityagent.php?' . $query;
    }else if(defined('IN_STORE')){
        return  './citystore.php?' . $query;
    }else if(defined('IN_STAFF')){
        return  './citysys.php?' . $query;
    }else{
        return './index.php?' . $query;
    }
}

function wl_get_module_name(){
    return MODULE_NAME;
}

function wl_tpl_form_field_member($value = array()) {
    $s = '';
    $s = '
		<script type="text/javascript">
			function search_members() {
	       	if( $.trim($("#search-kwd").val())==""){
	            Tip.focus("#search-kwd","请输入关键词");
	            return;
	        }
	
			$("#module-menus").html("正在搜索....");
			$.get("' . web_url('member/wlMember/selectMember') . '", {
				keyword: $.trim($("#search-kwd").val())
			}, function(dat){
				$("#module-menus").html(dat);
			});
		}
	    function select_member(o) {
			$("#openid").val(o.openid);
			$("#saler").val(o.nickname);
			$("#search-kwd").val(o.nickname);
			$("#module-menus").html("");
			$("#myModal").modal("hide");
			$(".modal-backdrop").remove();
		}
		</script>';
    $s .= '
		<div class="form-group">
        	<label class="col-sm-2 control-label">选择微信账号</label>
            <div class="col-sm-9">
                <input type="hidden" id="openid" name="openid" value="' . $value['openid'] . '" />
                <div class="input-group">
                    <input type="text" name="nickname" maxlength="30" value="' . $value['nickname'] . '" id="saler" class="form-control" readonly />
                    <div class="input-group-btn">
                        <button class="btn btn-default" type="button" data-toggle="modal" data-target="#myModal">选择微信账号</button>
                    </div>
                </div>
      			<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog" style="width: 660px;">
                        <div class="modal-content">
                            <div class="modal-header"><button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button><h3>选择微信账号</h3></div>
                            <div class="modal-body" >
                                <div class="row">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="keyword" value="" id="search-kwd" placeholder="可搜索微信昵称，openid，UID" />
                                        <span class="input-group-btn"><button type="button" class="btn btn-default" onclick="search_members();">搜索</button></span>
                                    </div>
                                </div>
                               	<div id="module-menus" style="padding-top:5px;"></div>
                            </div>
                           	<div class="modal-footer"><a href="#" class="btn btn-default" data-dismiss="modal" aria-hidden="true">关闭</a></div>
                        </div>
                    </div>
                </div>
        	</div>
		</div>
		';
    return $s;
}

/**
 * qr二维码生成
 *
 * @access public
 * @name qr
 * @param $url  需要生成二维码的链接
 */
function qr($url) {
    global $_W;
    if (empty($url))
        return false;
    m('qrcode/QRcode')->png($url, false, QR_ECLEVEL_H, 4);
}

/**
 * puv浏览量统计
 *
 * @access public
 * @name puv
 * @param
 */
function puv() {
    global $_W;
    if ($_W['uniacid'] <= 0 || empty($_W['areaid'])) {
        return;
    }
    $puv = pdo_getcolumn(PDO_NAME . 'puv', array('uniacid' => $_W['uniacid'], 'date' => date('Ymd'), 'areaid' => $_W['areaid']), 'id');
    if (empty($puv)) {
        pdo_insert(PDO_NAME . 'puv', array('areaid' => $_W['areaid'], 'uniacid' => $_W['uniacid'], 'pv' => 0, 'uv' => 0, 'date' => date('Ymd')));
        $puv = pdo_insertid();
    }
    pdo_query('UPDATE ' . tablename(PDO_NAME . 'puv') . " SET `pv` = `pv` + 1 WHERE id = {$puv}");
    if ($_W['mid']) {
        $myp = pdo_getcolumn(PDO_NAME . 'puvrecord', array('uniacid' => $_W['uniacid'], 'date' => date('Ymd'), 'mid' => $_W['mid'], 'areaid' => $_W['areaid']), 'id');
        if (empty($myp)) {
            pdo_query('UPDATE ' . tablename(PDO_NAME . 'puv') . " SET `uv` = `uv` + 1 WHERE id = {$puv}");
            pdo_insert(PDO_NAME . 'puvrecord', array('areaid' => $_W['areaid'], 'uniacid' => $_W['uniacid'], 'pv' => 0, 'mid' => $_W['mid'], 'date' => date('Ymd')));
            $myp = pdo_insertid();
        }
        pdo_query('UPDATE ' . tablename(PDO_NAME . 'puvrecord') . " SET `pv` = `pv` + 1 WHERE id = {$myp}");
    }
}

if (!function_exists('show_json')) {
    function show_json($status = 1, $return = NULL) {
        $ret = array('status' => $status, 'result' => $status == 1 ? array('url' => referer()) : array());

        if (!is_array($return)) {
            if ($return) {
                $ret['result']['message'] = $return;
            }

            exit(json_encode($ret));
        } else {
            $ret['result'] = $return;
        }

        if (isset($return['url'])) {
            $ret['result']['url'] = $return['url'];
        } else {
            if ($status == 1) {
                $ret['result']['url'] = referer();
            }
        }

        exit(json_encode($ret));
    }

}

function checkshare() {//禁止分享
    global $_W;
    if ($_W['controller'] == 'supervise') {//商户管理
        return 1;
    } else if ($_W['method'] == 'orderlist') {//订单列表详情
        return 1;
    } else if ($_W['controller'] == 'home' && $_W['method'] == 'paySuccess') {//抢购订单详情
        return 1;
    } else if ($_W['controller'] == 'coupon_app' && $_W['method'] == 'couponDetail') {//卡券订单详情
        return 1;
    } else if ($_W['controller'] == 'fightapp' && $_W['method'] == 'expressorder') {//拼团订单详情
        return 1;
    } else {
        return 0;
    }
}

if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = NULL) {
        $arr = array();
        foreach ($input as $d) {
            if (!isset($d[$column_key])) {
                return NULL;
            }
            if ($index_key !== NULL) {
                return array($d[$index_key] => $d[$column_key]);
            }
            $arr[] = $d[$column_key];
        }

        if ($index_key !== NULL) {
            $tmp = array();
            foreach ($arr as $ar) {
                $tmp[key($ar)] = current($ar);
            }
            $arr = $tmp;
        }
        return $arr;
    }
}

/**
 * $time 需要格式化的时间戳
 * return 格式化时间
 */
function time_tran($time) {
    $text = '';
    if (!$time) {
        return $text;
    }
    $current = time();
    $t = $current - $time;
    $retArr = array('刚刚', '秒前', '分钟前', '小时前', '天前', '月前', '年前');
    switch ($t) {
        case $t < 0://时间大于当前时间，返回格式化时间
            $text = date('Y-m-d', $time);
            break;
        case $t == 0://刚刚
            $text = $retArr[0];
            break;
        case $t < 60:// 几秒前
            $text = $t . $retArr[1];
            break;
        case $t < 3600://几分钟前
            $text = floor($t / 60) . $retArr[2];
            break;
        case $t < 86400://几小时前
            $text = floor($t / 3600) . $retArr[3];
            break;
        case $t < 2592000: //几天前
            $text = floor($t / 86400) . $retArr[4];
            break;
        case $t < 31536000: //几个月前
            $text = floor($t / 2592000) . $retArr[5];
            break;
        default : //几年前
            $text = floor($t / 31536000) . $retArr[6];

    }
    return $text;
}

/**
 * Comment: http请求(post)
 * Author: zzw
 * Date: 2019/10/8 14:29
 * @param $url
 * @param $postData
 * @param $header
 * @return mixed
 */
function curlPostRequest($url, $postData, $header = '') {
    $curl = curl_init();//初始化
    curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
    if ($header) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);//设置header信息
    curl_setopt($curl, CURLOPT_HEADER, 1);//设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_POST, 1);//设置post方式提交
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    $data = curl_exec($curl); //执行命令
//    if (curl_errno($curl)) {
//        $error = 'Error:' . curl_error($curl);
//        wl_debug($error);
//    }
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);// 获得响应结果里的：header头大小
    $info = substr($data, $headerSize);//通过截取 获取body信息
    curl_close($curl);//关闭URL请求
    return json_decode($info, true);//返回获取的信息数据
}
/**
 * Comment: Comment: http请求(get)
 * Author: zzw
 * Date: 2020/7/22 17:15
 * @param string $url       请求地址
 * @param string $header
 * @return mixed
 */
function curlGetRequest($url, $header = '') {
    $curl = curl_init();//初始化
    curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
    if ($header) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);//设置header信息
    curl_setopt($curl, CURLOPT_HEADER, 1);//设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设置获取的信息以文件流的形式返回，而不是直接输出。
    $data = curl_exec($curl); //执行命令
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);// 获得响应结果里的：header头大小
    $info = substr($data, $headerSize);//通过截取 获取body信息
    curl_close($curl);//关闭URL请求
    return json_decode($info, true);//返回获取的信息数据
}
/**
 * Comment: 查看本地是否存在某张图片，不存在则下载到本地
 * Author: zzw
 * Date: 2019/11/18 18:37
 * @param $imgPath
 * @param $imgLink
 */
function wl_uploadImages($imgPath,$imgLink){
    #1、判断是否已经存在本地
    if(!file_exists($imgPath)){
        $imgName = explode('/',$imgPath);
        $dir = str_replace($imgName[count($imgName) - 1],'',$imgPath);
        mkdir($dir,0777,true);
        $img =  $imgLink;
        ob_clean();
        ob_start();
        readfile($img);//读取图片
        $img = ob_get_contents();//得到缓冲区中保存的图片
        ob_end_clean();//清空缓冲区
        $fp = fopen($imgPath,'w');	//写入图片
        if(fwrite($fp,$img)) {
            fclose($fp);
        }
    }
}
/**
 * Comment: 判断某个远程文件是否存在
 * Author: zzw
 * Date: 2020/1/17 11:17
 * @param $url
 * @return int
 */
function remoteFileExists($url){
    if(file_get_contents($url,0,null,0,1)) return 1;
    else return 0;
}
/**
 * Comment: 汉字首字母 拼音获取
 * Author: zzw
 * Date: 2020/4/26 14:26
 * @param $s0
 * @return string|null
 */
function getFirstChar($s0){
    $fchar = ord($s0{0});
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($s0{0});
    $s1 = iconv("UTF-8","gb2312", $s0);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $s0){$s = $s1;}else{$s = $s0;}
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
    //return $s0;
}
/**
 * Comment: 腾讯云远程附件 丢失方法
 * 由于自动加载路径问题，当前方法在微擎腾讯云远程附件中报错，所以在这里添加一个方法，强制成功
 * Author: zzw
 * Date: 2020/9/7 11:49
 * @param     $uri
 * @param int $options
 * @return mixed
 */
function _idn_uri_convert($uri, $options = 0)
{
    if ($uri->getHost()) {
        $idnaVariant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
        $asciiHost = $idnaVariant === 0
            ? idn_to_ascii($uri->getHost(), $options)
            : idn_to_ascii($uri->getHost(), $options, $idnaVariant, $info);
        if ($asciiHost === false) {
            $errorBitSet = isset($info['errors']) ? $info['errors'] : 0;

            $errorConstants = array_filter(array_keys(get_defined_constants()), function ($name) {
                return substr($name, 0, 11) === 'IDNA_ERROR_';
            });

            $errors = [];
            foreach ($errorConstants as $errorConstant) {
                if ($errorBitSet & constant($errorConstant)) {
                    $errors[] = $errorConstant;
                }
            }

            $errorMessage = 'IDN conversion failed';
            if ($errors) {
                $errorMessage .= ' (errors: ' . implode(', ', $errors) . ')';
            }

            throw new InvalidArgumentException($errorMessage);
        } else {
            if ($uri->getHost() !== $asciiHost) {
                // Replace URI only if the ASCII version is different
                $uri = $uri->withHost($asciiHost);
            }
        }
    }

    return $uri;
}
/**
* Comment: 分页方法
 * Author: zzw
 * Date: 2020/12/29 9:49
 * @param        $total
 * @param        $pageIndex
 * @param int    $pageSize
 * @param string $url
 * @param array  $context
 * @return string
 */
function wl_pagination($total, $pageIndex, $pageSize = 15, $url = '', $context = array('before' => 5, 'after' => 4, 'ajaxcallback' => '', 'callbackfuncname' => '')) {
    global $_W;
    $pdata = array(
        'tcount' => 0,
        'tpage' => 0,
        'cindex' => 0,
        'findex' => 0,
        'pindex' => 0,
        'nindex' => 0,
        'lindex' => 0,
        'options' => '',
    );
    if (empty($context['before'])) {
        $context['before'] = 5;
    }
    if (empty($context['after'])) {
        $context['after'] = 4;
    }

    if ($context['ajaxcallback']) {
        $context['isajax'] = true;
    }

    if ($context['callbackfuncname']) {
        $callbackfunc = $context['callbackfuncname'];
    }

    $pdata['tcount'] = $total;
    $pdata['tpage'] = (empty($pageSize) || $pageSize < 0) ? 1 : ceil($total / $pageSize);
    if ($pdata['tpage'] <= 1) {
        return '';
    }
    $cindex = $pageIndex;
    $cindex = min($cindex, $pdata['tpage']);
    $cindex = max($cindex, 1);
    $pdata['cindex'] = $cindex;
    $pdata['findex'] = 1;
    $pdata['pindex'] = $cindex > 1 ? $cindex - 1 : 1;
    $pdata['nindex'] = $cindex < $pdata['tpage'] ? $cindex + 1 : $pdata['tpage'];
    $pdata['lindex'] = $pdata['tpage'];

    if ($context['isajax']) {
        if (empty($url)) {
            $url = $_W['script_name'] . '?' . http_build_query($_GET);
        }
        $pdata['faa'] = 'href="javascript:;" page="' . $pdata['findex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['findex'] . '\', this);"' : '');
        $pdata['paa'] = 'href="javascript:;" page="' . $pdata['pindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['pindex'] . '\', this);"' : '');
        $pdata['naa'] = 'href="javascript:;" page="' . $pdata['nindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['nindex'] . '\', this);"' : '');
        $pdata['laa'] = 'href="javascript:;" page="' . $pdata['lindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['lindex'] . '\', this);"' : '');
    } else {
        if ($url) {
            $pdata['faa'] = 'href="?' . str_replace('*', $pdata['findex'], $url) . '"';
            $pdata['paa'] = 'href="?' . str_replace('*', $pdata['pindex'], $url) . '"';
            $pdata['naa'] = 'href="?' . str_replace('*', $pdata['nindex'], $url) . '"';
            $pdata['laa'] = 'href="?' . str_replace('*', $pdata['lindex'], $url) . '"';
        } else {
            $_GET['page'] = $pdata['findex'];
            $pdata['faa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['pindex'];
            $pdata['paa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['nindex'];
            $pdata['naa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['lindex'];
            $pdata['laa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
        }
    }
    $html = '<div><ul class="pagination pagination-centered">';
    $html .= "<li><a {$pdata['faa']} class=\"pager-nav\">首页</a></li>";
    empty($callbackfunc) && $html .= "<li><a {$pdata['paa']} class=\"pager-nav\">&laquo;上一页</a></li>";

    if (!$context['before'] && 0 != $context['before']) {
        $context['before'] = 5;
    }
    if (!$context['after'] && 0 != $context['after']) {
        $context['after'] = 4;
    }

    if (0 != $context['after'] && 0 != $context['before']) {
        $range = array();
        $range['start'] = max(1, $pdata['cindex'] - $context['before']);
        $range['end'] = min($pdata['tpage'], $pdata['cindex'] + $context['after']);
        if ($range['end'] - $range['start'] < $context['before'] + $context['after']) {
            $range['end'] = min($pdata['tpage'], $range['start'] + $context['before'] + $context['after']);
            $range['start'] = max(1, $range['end'] - $context['before'] - $context['after']);
        }
        for ($i = $range['start']; $i <= $range['end']; ++$i) {
            if ($context['isajax']) {
                $aa = 'href="javascript:;" page="' . $i . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $i . '\', this);"' : '');
            } else {
                if ($url) {
                    $aa = 'href="?' . str_replace('*', $i, $url) . '"';
                } else {
                    $_GET['page'] = $i;
                    $aa = 'href="?' . http_build_query($_GET) . '"';
                }
            }
            if (!empty($context['isajax'])) {
                $html .= ($i == $pdata['cindex'] ? '<li class="active">' : '<li>') . "<a {$aa}>" . $i . '</a></li>';
            } else {
                $html .= ($i == $pdata['cindex'] ? '<li class="active"><a href="javascript:;">' . $i . '</a></li>' : "<li><a {$aa}>" . $i . '</a></li>');
            }
        }
    }

    if ($pdata['cindex'] < $pdata['tpage']) {
        empty($callbackfunc) && $html .= "<li><a {$pdata['naa']} class=\"pager-nav\">下一页&raquo;</a></li>";
        $html .= "<li><a {$pdata['laa']} class=\"pager-nav\">尾页</a></li>";
    }
    //跳转页
    if($pdata['tpage'] > 1){
        $html .= "<li><input type='number' id='page_num' value='{$pageIndex}' 
style='background-color: #FFF;border: 1px solid #DDD;color: inherit;float: left;line-height: 1.42857;margin: -1px;position: relative;text-decoration: none;height: 33px;width: 80px;outline: none;text-align: center;'></li>";
        $html .= "<li><a href='javascript:;' class='pager-nav' id='page_num_jump'>跳转</a></li>";
        //跳转js操作
        $get = $_GET;
        unset($get['page']);
        $html .= "
<script>
    $(document).on('click','#page_num_jump',function (){
        let page = $('#page_num').val();
        let url  = \"{$_W['script_name']}?".http_build_query($get)."&page=\"+page;
        if(!page) tip.alert('请输入跳转页!');
        //跳转页面
        console.log(url);
        window.location.href = url;
    });
</script>";
    }
    $html .= '</ul></div>';

    return $html;
}
/**
 * Comment: 判断当前数组是否存在指定表中不存在的字段，存在则删除该字段
 * Author: zzw
 * Date: 2021/3/10 10:26
 * @param array $data
 * @param string $table
 * @return mixed
 */
function fieldJudge($data,$table){
    $sql = "SHOW COLUMNS FROM ".tablename(PDO_NAME.$table);
    $fieldList = pdo_fetchall($sql);
    $fields = array_column($fieldList,'Field');
    foreach($data as $fieldName => $fieldVal){
        //判断 数据库不存在该字段 删除信息
        if(!in_array($fieldName, $fields)) unset($data[$fieldName]);
    }

    return $data;
}
/**
 * Comment: 判断当前字符串是否包含html内容
 * Author: zzw
 * Date: 2021/4/27 9:47
 * @param $str
 * @return bool
 */
function isHtml($str){
 if($str != strip_tags($str)) return true;
 else return false;
}
/**
 * Comment: 生成距离计算sql语句
 * Author: zzw
 * Date: 2021/5/6 11:30
 * @param float|int    $lat 纬度
 * @param float|int    $lng 经度
 * @param string $latField  纬度在表中的字段
 * @param string $lngField  经度在表中的字段
 * @return string
 */
function getDistancesSql($lat,$lng,string $latField,string $lngField):string
{
    return "ROUND(CASE 
    WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
            SQRT(
                POW(SIN(({$lat} * PI() / 180 - $latField * PI() / 180) / 2),2) +
                    COS({$lat} * PI() / 180) * COS($latField * PI() / 180) *
                    POW(SIN(({$lng} * PI() / 180 - $lngField * PI() / 180) / 2),2)
                )
            ) * 1000
        ) 
    ELSE 0
END)";
}


/**
 * Comment: 增加图片前缀
 * Author: wlf
 */
function beautifyImgInfo($imgs)
{
    global $_W;
    $imgs = unserialize($imgs);
    if (empty($imgs)) {
        $imgs = [];
    } else {
        foreach ($imgs as &$th) {
            $th = tomedia($th);
        }
    }
    return $imgs;
}
/**
 * Comment: 增加链接的代理参数
 * Author: wlf
 */
function add_aid($url){
    global $_W;
    if(strstr($url,'?')){
        $url = $url.'&aid='.$_W['aid'];
    }else{
        $url = $url.'?aid='.$_W['aid'];
    }
    return $url;
}

/**
 * Comment: 锁操作
 * Author: wlf
 * Date: 2022/07/11 16:11
 */

function lockTool($mid,$operation){
    global $_W;
    $key = md5($mid.$operation);
    $lock = cache_read($key);
    if(!empty($lock)){
        if(time() - $lock < 5){
            return 1;
        }
    }
    cache_delete($key);
    cache_write($key, time());
    return 0;
}




