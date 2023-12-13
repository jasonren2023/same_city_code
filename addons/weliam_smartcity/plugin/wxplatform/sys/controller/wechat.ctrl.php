<?php
defined('IN_IA') or exit('Access Denied');

class Wechat_WeliamController {

    public function info(){
        global $_W;
        $info = Util::object_array($_W['account']);
        $info['serviceUrl'] = $_W['siteroot'] . 'api.php?id=' . $info['acid'];
        include  wl_template('wxplatform/info');
    }

    public function diymenu(){
        global $_W,$_GPC;
        load()->model('menu');
        $do = 'post';
        $type = intval($_GPC['type']);
        $id = intval($_GPC['id']);
        $copy = intval($_GPC['copy']);
        if (empty($type)) {
            if (!$_W['isajax']) {
                $update_self_menu = menu_update_currentself();
                if (is_error($update_self_menu)) {
                    wl_message($update_self_menu['message']);
                }
            }
            $type = MENU_CURRENTSELF;
            $default_menu = menu_default();
            $id = intval($default_menu['id']);
        }
        $params = array();
        if ($id > 0) {
            $menu = menu_get($id);
            if (empty($menu)) {
                wl_message('菜单不存在或已经删除');
            }
            if (!empty($menu['data'])) {
                $menu['data'] = iunserializer(base64_decode($menu['data']));
                if (!empty($menu['data']['button'])) {
                    foreach ($menu['data']['button'] as &$button) {
                        if (!empty($button['url'])) {
                            $button['url'] = preg_replace('/(.*)redirect_uri=(.*)&response_type(.*)wechat_redirect/', '$2', $button['url']);
                        }
                        if (empty($button['sub_button'])) {
                            if ('media_id' == $button['type']) {
                                $button['type'] = 'click';
                            }
                            $button['sub_button'] = array();
                        } else {
                            $button['sub_button'] = !empty($button['sub_button']['list']) ? $button['sub_button']['list'] : $button['sub_button'];
                            foreach ($button['sub_button'] as &$subbutton) {
                                if (!empty($subbutton['url'])) {
                                    $subbutton['url'] = preg_replace('/(.*)redirect_uri=(.*)&response_type(.*)wechat_redirect/', '$2', $subbutton['url']);
                                }
                                if ('media_id' == $subbutton['type']) {
                                    $subbutton['type'] = 'click';
                                }
                            }
                            unset($subbutton);
                        }
                    }
                    unset($button);
                }
                if (!empty($menu['data']['matchrule']['province'])) {
                    $menu['data']['matchrule']['province'] .= '省';
                }
                if (!empty($menu['data']['matchrule']['city'])) {
                    $menu['data']['matchrule']['city'] .= '市';
                }
                if (empty($menu['data']['matchrule']['sex'])) {
                    $menu['data']['matchrule']['sex'] = 0;
                }
                if (empty($menu['data']['matchrule']['group_id'])) {
                    $menu['data']['matchrule']['group_id'] = -1;
                }
                if (empty($menu['data']['matchrule']['client_platform_type'])) {
                    $menu['data']['matchrule']['client_platform_type'] = 0;
                }
                if (empty($menu['data']['matchrule']['language'])) {
                    $menu['data']['matchrule']['language'] = '';
                }
                $params = $menu['data'];
                $params['title'] = $menu['title'];
                $params['type'] = $menu['type'];
                $params['id'] = $menu['id'];
                $params['status'] = $menu['status'];
            }
            $type = $menu['type'];
        }
        $status = $params['status'];
        //$groups = mc_fans_groups();
        $languages = menu_languages();
        if ($_W['isajax'] && $_W['ispost']) {
            set_time_limit(0);
            $_GPC['group']['title'] = trim($_GPC['group']['title']);
            $_GPC['group']['type'] = 0 == intval($_GPC['group']['type']) ? 1 : intval($_GPC['group']['type']);
            $post = $_GPC['group'];
            if (empty($post['title'])) {
                iajax(-1, '请填写菜单组名称！', '');
            }
            $check_title_exist_condition = array(
                'title' => $post['title'],
                'type' => $type,
                'uniacid' => $_W['uniacid']
            );
            if (!empty($id)) {
                $check_title_exist_condition['id <>'] = $id;
            }
            $check_title_exist = pdo_getcolumn('uni_account_menus', $check_title_exist_condition, 'id');
            if (!empty($check_title_exist)) {
                iajax(-1, '菜单组名称已存在，请重新命名！', '');
            }
            if (MENU_CONDITIONAL == $post['type'] && empty($post['matchrule'])) {
                iajax(-1, '请选择菜单显示对象', '');
            }
            if (!empty($post['button'])) {
                foreach ($post['button'] as $key => &$button) {
                    $keyword_exist = strexists($button['key'], 'keyword:');
                    if ($keyword_exist) {
                        $button['key'] = substr($button['key'], 8);
                    }
                    if (!empty($button['sub_button'])) {
                        foreach ($button['sub_button'] as &$subbutton) {
                            $sub_keyword_exist = strexists($subbutton['key'], 'keyword:');
                            if ($sub_keyword_exist) {
                                $subbutton['key'] = substr($subbutton['key'], 8);
                            }
                        }
                        unset($subbutton);
                    }
                }
                unset($button);
            }

            $is_conditional = MENU_CONDITIONAL == $post['type'] ? true : false;
            $account_api = WeAccount::createByUniacid();
            $menu = $account_api->menuBuild($post, $is_conditional);
            if ('publish' == $_GPC['submit_type'] || $is_conditional) {
                $result = $account_api->menuCreate($menu);
            } else {
                $result = true;
            }
            if (is_error($result)) {
                iajax($result['errno'], $result['message']);
            } else {
                if ($post['matchrule']['group_id'] != -1) {
                    $menu['matchrule']['groupid'] = $menu['matchrule']['tag_id'];
                    unset($menu['matchrule']['tag_id']);
                }
                $menu = json_decode(urldecode(json_encode($menu)), true);

                $insert = array(
                    'uniacid' => $_W['uniacid'],
                    'menuid' => $result,
                    'title' => $post['title'],
                    'type' => $post['type'],
                    'sex' => intval($menu['matchrule']['sex']),
                    'group_id' => isset($menu['matchrule']['group_id']) ? $menu['matchrule']['group_id'] : -1,
                    'client_platform_type' => intval($menu['matchrule']['client_platform_type']),
                    'area' => trim($menus['matchrule']['country']) . trim($menu['matchrule']['province']) . trim($menu['matchrule']['city']),
                    'data' => base64_encode(iserializer($menu)),
                    'status' => STATUS_ON,
                    'createtime' => TIMESTAMP,
                );

                if (MENU_CURRENTSELF == $post['type']) {
                    if (!empty($id)) {
                        pdo_update('uni_account_menus', $insert, array('uniacid' => $_W['uniacid'], 'type' => MENU_CURRENTSELF, 'id' => $id));
                    } else {
                        pdo_insert('uni_account_menus', $insert);
                    }
                    iajax(0, '创建菜单成功', web_url('wxplatform/wechat/diymenulist'));
                } elseif (MENU_CONDITIONAL == $post['type']) {
                    if (STATUS_OFF == $post['status'] && $post['id'] > 0) {
                        pdo_update('uni_account_menus', $insert, array('uniacid' => $_W['uniacid'], 'type' => MENU_CONDITIONAL, 'id' => $post['id']));
                    } else {
                        pdo_insert('uni_account_menus', $insert);
                    }
                    iajax(0, '创建菜单成功', web_url('wxplatform/wechat/diymenulist', array('type' => MENU_CONDITIONAL)));
                }
            }
        }
        include  wl_template('wxplatform/menu');
    }

    public function diymenulist(){
        global $_W,$_GPC;
        load()->model('menu');
        $do = 'display';
        set_time_limit(0);
        $type = !empty($_GPC['type']) ? intval($_GPC['type']) : MENU_CURRENTSELF;
        if (MENU_CONDITIONAL == $type) {
            $update_conditional_menu = menu_update_conditional();
            if (is_error($update_conditional_menu)) {
                wl_message($update_conditional_menu['message']);
            }
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $condition = ' WHERE uniacid = :uniacid';
        $params[':uniacid'] = $_W['uniacid'];
        if (isset($_GPC['keyword'])) {
            $condition .= ' AND title LIKE :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        if (!empty($type)) {
            $condition .= ' AND type = :type';
            $params[':type'] = $type;
        }
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('uni_account_menus') . $condition, $params);
        $data = pdo_fetchall('SELECT * FROM ' . tablename('uni_account_menus') . $condition . ' ORDER BY type ASC, status DESC,id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
        $pager = wl_pagination($total, $pindex, $psize);
//        if (MENU_CONDITIONAL == $type) {
//            $names = array(
//                'sex' => array('不限', '男', '女'),
//                'client_platform_type' => array('不限', '苹果', '安卓', '其他'),
//            );
//            $groups = mc_fans_groups(true);
//        }


        include  wl_template('wxplatform/menu');
    }

    public function autoreply(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        //筛选
        $search = trim($_GPC['search']);
        $keywordtype = $_GPC['keywordtype'];
        if(!empty($search) && !empty($keywordtype)){
            if($keywordtype == 1){//规则名
                $where['@name@'] = $search;
            }else if($keywordtype == 2){
                //关键字
                $params[':content'] = "%{$search}%";
                $kwlists = pdo_fetchall("SELECT rid FROM " . tablename('rule_keyword') . "WHERE uniacid = {$_W['uniacid']} AND content LIKE :content", $params);
                if ($kwlists) {
                    $rids = "(";
                    foreach ($kwlists as $key => $v) {
                        if ($key == 0) {
                            $rids .= $v['rid'];
                        } else {
                            $rids .= "," . $v['rid'];
                        }
                    }
                    $rids .= ")";
                    $where['id#'] = $rids;
                }else{
                    $where['id#'] = "(0)";
                }
            }
        }
        $rList = Util::getNumData('*','rule', $where, 'displayorder DESC,id DESC', $pindex, $psize, 1);
        $list = $rList[0];
        $pager = $rList[1];
        foreach ($list as $key => &$li) {
            //关键字
            $kw = pdo_getall('rule_keyword',array('rid' => $li['id']),array('content','type'),'','displayorder DESC');
            $li['keyword'] = $kw;
            //回复内容
            if(empty($li['containtype'])){
                $li['content'] = '系统设置';
            }else{
                $allnum = 0;
                $li['containtype'] = explode(',',$li['containtype']);
                foreach($li['containtype'] as $containtype){
                    if($containtype == 'basic'){
                        $basicnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('basic_reply')." WHERE rid = {$li['id']}");
                        $allnum += $basicnum;
                    }else if($containtype == 'images'){
                        $imagenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('images_reply')." WHERE rid = {$li['id']}");
                        $allnum += $imagenum;
                    }
                }
                $li['content'] = '共'.$allnum.'条(';
                if($basicnum > 0){
                    $li['content'] .= $basicnum.'条文字 ';
                }
                if($imagenum > 0){
                    $li['content'] .= $imagenum.'条图片 ';
                }
                $li['content'] .= ')';
            }
        }
        include  wl_template('wxplatform/replylist');
    }

    public function changeReplyStatus(){
        global $_W,$_GPC;
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数,请刷新重试');
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update('rule',array('status' => $status),array('id' => $id));
        if ($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

    public function deteleReply(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if(!empty($id)){
            Wxplatform::deteleOneReply($id,1);
        }
        if(!empty($ids)){
            foreach ($ids as $k => $v){
                Wxplatform::deteleOneReply($v,1);
            }
        }
        show_json(1, '操作成功');
    }

    public function creatReply(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $reply = pdo_get('rule',array('id' => $id));
        $reply['kwlist'] = pdo_getall('rule_keyword',array('rid' => $id),array('content','type'));
        $reply['baselist'] = pdo_getall('basic_reply',array('rid' => $id),array('content'));
        $reply['imglist'] = pdo_getall('images_reply',array('rid' => $id),array('imgurl'));

        if ($_W['ispost']) {
            $reply = $_GPC['reply'];
            $reply['module'] = 'reply';
            $reply['uniacid'] = $_W['uniacid'];
            $keyword = $_GPC['keyword'];
            $kwtype = $_GPC['kwtype'];
            $contenttext = $_GPC['contenttext'];
            $contentimg = $_GPC['contentimg'];
            if(empty($keyword)){
                Commons::sRenderError('请设置关键字!');
            }
            if(empty($contenttext) && empty($contentimg)){
                Commons::sRenderError('请设置回复内容!');
            }
            $reply['containtype'] = '';
            if(!empty($contenttext)){
                $reply['containtype'] .= 'basic,';
            }
            if(!empty($contentimg)){
                $reply['containtype'] .= 'images';
            }
            if($id > 0){
                pdo_update('rule',$reply,array('id' => $id));
                Wxplatform::deteleOneReply($id);
                $rid = $id;
            }else{
                pdo_insert('rule',$reply);
                $rid = pdo_insertid();
            }
            foreach($keyword as $k => $kwd){
                $kwinfo = [
                    'rid'     => $rid,
                    'uniacid' => $_W['uniacid'],
                    'module'  => 'reply',
                    'content' => $kwd,
                    'type'    => $kwtype[$k],
                    'status'  => 1
                ];
                pdo_insert('rule_keyword',$kwinfo);
            }
            if(!empty($contenttext)){
                foreach($contenttext as $text){
                    $textinfo = [
                        'rid'     => $rid,
                        'content' => $text,
                    ];
                    pdo_insert('basic_reply',$textinfo);
                }
            }
            if(!empty($contentimg)){
                foreach($contentimg as $img){
                    $imginfo = [
                        'rid'     => $rid,
                        'mediaid' => Wxplatform::uploadTemporaryMaterial($img),
                        'imgurl'  => $img,
                        'createtime' => time()
                    ];
                    pdo_insert('images_reply',$imginfo);
                }
            }
            wl_message('保存成功！' ,web_url('wxplatform/wechat/autoreply'), 'success');
        }

        include  wl_template('wxplatform/creatreply');
    }

    public function imgdiv(){
        global $_W,$_GPC;
        $kw = $_GPC['kw'];
        include  wl_template('wxplatform/imgdiv');
    }

    public function push(){
        global $_W,$_GPC;
        load()->model('menu');
        $id = intval($_GPC['id']);
        $result = menu_push($id);

        if (is_error($result)) {
            show_json(0, $result['message']);
        } else {
            show_json(1, '操作成功');
        }
    }

    public function delete(){
        global $_W,$_GPC;
        load()->model('menu');
        $id = intval($_GPC['id']);
        $result = menu_delete($id);
        if (is_error($result)) {
            wl_message('删除失败'.$result['message'] ,referer(), 'error');
        }
        wl_message('删除成功！' ,referer(), 'success');
    }



}
