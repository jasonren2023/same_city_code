<?php
/**
 * Comment: 代理端头条信息管理
 * Author: ZZW
 * Date: 2018/8/30
 * Time: 14:32
 */
defined('IN_IA') or exit('Access Denied');

class Headline_WeliamController {

    public function __construct () {
        global $_W, $_GPC;
        $_W['aid'] = $_W['aid'] ? $_W['aid'] : 0;

    }
    /**
     * Comment: 进入头条分类列表首页
     * Author: zzw
     * Date: 2019/7/8 11:41
     */
    public function index() {
        global $_W, $_GPC;
        #1、条件生成
        $name = $_GPC['name'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if ($name) {
            $where .= " AND name LIKE '%{$name}%' ";
        }
        #2、查询一级菜单
        $sql = "SELECT id,head_id,`name`,sort,state FROM " . tablename(PDO_NAME . "headline_class")
            . " WHERE {$where} AND head_id = 0 ORDER BY sort ASC";
        $total = count(pdo_fetchall($sql));
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);
        #2、查询一级菜单下面的二级菜单
        foreach ($list as $key => &$val) {
            $val['footer'] = pdo_fetchall("SELECT id,head_id,`name`,sort,state FROM " . tablename(PDO_NAME . "headline_class")
                . " WHERE head_id = {$val['id']} ORDER BY sort ASC");
        }
        $pager = wl_pagination($total, $pindex, $psize);
        #4、获取顶级分类列表
        $headClass = pdo_getall(PDO_NAME . "headline_class", array('head_id' => 0, 'state' => 1, 'uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));

        include wl_template('headline/list');
    }
    /**
     * Comment: 添加/修改分类
     * Author: zzw
     * Date: 2019/7/8 11:41
     */
    public function editClass() {
        global $_W, $_GPC;
        #1、参数获取
        $id = $_GPC['id'];
        !empty($_GPC['data']) && $data = $_GPC['data'];
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'] ? $_W['aid'] : 0;
        #2、判断信息是否已经存在 或者未进行修改
        if (pdo_get(PDO_NAME . 'headline_class', $data)) wl_json(0, '请修改内容后提交!');//判断是否修改内容
        if (!$id) if (pdo_get(PDO_NAME . 'headline_class', ['name' => $data['name'],'aid'=>$data['aid'],'uniacid'=>$data['uniacid']])) wl_json(0, '当前分类已经存在!'); //判断当前分类是否存在
        #4、判断当前分类下是否存在子分类
        if ($data['head_id'] > 0) {
            $is_have = pdo_getcolumn(PDO_NAME . "headline_class" , ['id' => $data['head_id']],'head_id');
            if ($is_have > 0) wl_json(0 , '当前分类为子分类，不能成为上级分类!');
        }
        #3、添加/修改内容的操作
        if ($id) {
            $result = pdo_update(PDO_NAME . 'headline_class', $data, array('id' => $id));
        } else {
            $result = pdo_insert(PDO_NAME . 'headline_class', $data);
        }
        if ($result) {
            wl_json(1, '操作成功');
        } else {
            wl_json(0, '操作失败');
        }
    }
    /**
     * Comment: 修改分类单项数据信息
     * Author: zzw
     * Date: 2019/7/8 11:41
     */
    public function editField() {
        global $_W, $_GPC;
        #1、参数获取
        $data['id'] = $id = $_GPC['id'];
        $data[$_GPC['field']] = $_GPC['value'];
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'] ? $_W['aid'] : 0;
        #2、判断信息是否修改
        if (pdo_get(PDO_NAME . 'headline_class', $data)) wl_json(0, '请修改内容后提交!');
        #3、修改内容的操作
        unset($data['id']);
        $result = pdo_update(PDO_NAME . 'headline_class', $data, array('id' => $id));
        if ($result) {
            show_json(1, '修改成功');
        } else {
            show_json(1, '操作失败');
        }
    }
    /**
     * Comment: 删除分类
     * Author: zzw
     */
    public function delClass() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        //将以当前分类下的所有的分类设为无上级菜单
        pdo_update(PDO_NAME . "headline_class", array('head_id' => 0), array('head_id' => $id));
        $result = pdo_delete(PDO_NAME . "headline_class", array('id' => $id));
        if ($result) {
            show_json(1);
        } else {
            show_json(0, '删除失败');
        }
    }

    public function getCategory(){
        $table = tablename(PDO_NAME."headline_class");
        $info = pdo_fetchall("SELECT id,name FROM".$table);
        wl_json(1, '成功', $info);
    }
    /**
     * Comment: 进入头条信息列表
     * Author: zzw
     */
    public function infoList() {
        global $_W, $_GPC;
        $search = $_GPC['search'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if ($search) {
            $search = trim($search);
            $where .= " AND (a.title LIKE '%{$search}%' || a.author LIKE '%{$search}%' || b.name LIKE '%{$search}%' || d.name LIKE '%{$search}%') ";
        }
        $sql = "SELECT 
            a.id,
            a.title,
            a.author,
            a.summary,
            a.release_time,
            a.browse,
            a.call_id,
            a.goods_name,
            b.name as one_name,
            d.name as two_name
            FROM "
            . tablename(PDO_NAME . "headline_content")
            . " a LEFT JOIN "
            . tablename(PDO_NAME . "headline_class") . " b ON a.one_id = b.id"
            . " LEFT JOIN "
            . tablename(PDO_NAME . "headline_class") . " d ON a.two_id = d.id"
            . " WHERE {$where} ORDER BY release_time DESC ";
        $total = count(pdo_fetchall($sql));
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);
        foreach ($list as $k => $v) {
            $list[$k]['call'] = '未开启集call活动';
            if ($v['call_id']) {
                $list[$k]['call'] = implode(pdo_get(PDO_NAME . "call", array('id' => $v['call_id']), array('title')));
            }
            unset($list[$k]['call_id']);
        }

        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('headline/infoList');
    }
    public function import() {
        global $_W, $_GPC;
        if (!empty($_GPC['wechat_url'])) {
            header('location: ' . web_url('headline/headline/getIntoEdit', ['wechat_url' => urlencode(trim($_GPC['wechat_url']))]));
            die;
        }
        include wl_template('headline/import');
    }
    /**
     * Comment: 进入编辑页面
     * Author: zzw
     */
    public function getIntoEdit() {
        global $_W, $_GPC;
        #1、参数接收
        $id = $_GPC['id'];//头条id
        $weChatUrl = urldecode($_GPC['wechat_url']);//微信图文的链接
        $time = time();
        $publicWhere = " AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}  ";
        #2、获取集call活动信息列表
        if (p('call')) {
            $callList = pdo_fetchall("SELECT id,title FROM "
                . tablename(PDO_NAME . "call") .
                " WHERE state = 1 AND receive_time > {$time} {$publicWhere}");
        }
        #3、获取所有分类信息
        $classList = pdo_fetchall("SELECT head_id,id,`name` FROM "
            . tablename(PDO_NAME . "headline_class")
            . " WHERE `state` = 1 {$publicWhere} AND head_id = 0 ORDER BY sort ASC");
        #4、修改操作 获取修改数据
        if ($id) {
            //获取当前头条详细信息
            $info = pdo_get(PDO_NAME . "headline_content", array("id" => $id));
            if (is_base64($info['content'])) $info['content'] = base64_decode($info['content']);
            $advs = unserialize($info['advs']);
            //获取当前一级分类的所有下级分类
            $subClass = pdo_fetchall("SELECT id,`name` FROM "
                . tablename(PDO_NAME . "headline_class")
                . " WHERE `state` = 1 AND head_id = {$info['one_id']} {$publicWhere} ORDER BY sort ASC");
        }
        #5、判断wechat_url是否存在内容  存在则是使用微信图文
        if (!empty($weChatUrl)) {
            $result = (new GatherArticle())->get_caiji($weChatUrl);
            //信息重组
            $info['author'] = $result['nickname'];//作者昵称
            $info['title'] = $result['title'];//头条标题
            $info['summary'] = $result['desc'];//头条简介
            $info['display_img'] = $result['thumb'];//封面图片
            $info['content'] = $result['contents'];//具体内容
        }

        include wl_template('headline/edit');
    }
    /**
     * Comment: 获取某个分类的所有下级分类
     * Author: zzw
     * Date: 2019/7/8 14:27
     */
    public function getSubClass() {
        global $_W, $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'];
        if (!$id) wl_json(0, '缺少参数：id不存在');
        $publicWhere = " AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}  ";
        #2、获取当前分类的所有下级分类
        $subClass = pdo_fetchall("SELECT id,`name` FROM "
            . tablename(PDO_NAME . "headline_class")
            . " WHERE `state` = 1 AND head_id = {$id} {$publicWhere} ORDER BY sort ASC");
        wl_json(0, '下级分类列表', $subClass);
    }
    /**
     * Comment: 添加/修改 头条信息
     * Author: zzw
     */
    public function edit() {
        global $_W, $_GPC;
        $data = $_GPC['data'];
        $adv = $_GPC['adv'];
        $link = $_GPC['link'];
        if(!empty($adv)){
            foreach($adv as $key => $vle){
                $vipa['img'] = $vle;
                $vipa['url'] = $link[$key];
                $advs[] = $vipa;
            }
            $data['advs'] = serialize($advs);
        }else{
            $data['advs'] = '';
        }
        $data['content'] = base64_encode($data['content']);
        if ($_GPC['id']) {
            //修改操作  查看是否修改
            $data['id'] = $_GPC['id'];
            $data['uniacid'] = $_W['uniacid'];
            $data['aid'] = $_W['aid'];
            $update = pdo_get(PDO_NAME . "headline_content", $data);
            if ($update) wl_message('请修改后提交', referer(), 'error');
            unset($data['id']);
            //进行修改操作
            $result = pdo_update(PDO_NAME . "headline_content", $data, array("id" => $_GPC['id']));
        } else {
            //添加操作
            $data['uniacid'] = $_W['uniacid'];
            $data['aid'] = $_W['aid'];
            $data['release_time'] = time();
            $result = pdo_insert(PDO_NAME . "headline_content", $data);
        }
        if ($result) {
            wl_message('操作成功！', web_url('headline/headline/infoList'), 'success');
        } else {
            wl_message('操作失败,请重试', referer(), 'error');
        }
    }
    /**
     * Comment: 删除头条信息
     * Author: zzw
     */
    public function delHeadline() {
        global $_W, $_GPC;
        //参数获取
        $id = $_GPC['id'];
        //删除头条信息
        $result = pdo_delete(PDO_NAME . "headline_content", array('id' => $id));
        //删除头条信息相关的评论信息
        pdo_delete(PDO_NAME . "headline_comment", array('hid' => $id));
        if ($result) {
            show_json(1);
        } else {
            show_json(0, '删除失败');
        }
    }


    /**
     * Comment: 留言列表
     * Author: zzw
     */
    public function commentList() {
        global $_W, $_GPC;
        $id = $_GPC['id'];//头条id
        //获取头条列表
        $list = pdo_fetchall("SELECT id,title,release_time FROM " . tablename(PDO_NAME . "headline_content")
            . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ORDER BY release_time DESC");
        //获取所有头条的未读信息总数
        foreach ($list as $k => &$v) {
            $v['total'] = pdo_fetchcolumn("SELECT count(*) FROM "
                                          . tablename(PDO_NAME . "headline_comment")
                                          . " WHERE hid = {$v['id']} ");
            $v['num'] = pdo_fetchcolumn("SELECT count(*) FROM "
                                        . tablename(PDO_NAME . "headline_comment")
                                        . " WHERE hid = {$v['id']} AND state = 0");
            $v['release_time'] = date("Y-m-d H:i:s", $v['release_time']);
        }
        //获取某一条头条的留言信息
        if (!$id) {
            $id = $list[0]['id'];
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $sql = "SELECT * FROM " . tablename(PDO_NAME . "headline_comment")
            . " WHERE hid = {$id} ORDER BY set_top DESC, times DESC";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $commentList = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename(PDO_NAME . 'headline_comment') . " WHERE hid = {$id}");
        $pager = wl_pagination($total, $pindex, $psize);
        //将查询出来的未读信息修改为已读
        $idList = implode(',', array_column($commentList, 'id'));
        pdo_query("UPDATE " . tablename(PDO_NAME . 'headline_comment') . " SET state = 1 WHERE id IN ({$idList})");
        //获取所有留言信息的留言用户的头像昵称
        foreach ($commentList as $key => &$val) {
            $userInfo = pdo_fetch('SELECT nickname,avatar FROM ' . tablename(PDO_NAME . 'member') . " WHERE id = {$val['mid']}");
            $val['nickname'] = is_array($userInfo) ? $userInfo['nickname'] ? $userInfo['nickname'] : '' : '';
            $val['avatar'] = is_array($userInfo) ? $userInfo['avatar'] ? $userInfo['avatar'] : '' : '';
            $val['times'] = date("Y-m-d H:i:s", $val['times']);
        }

        include wl_template('headline/commentList');
    }
    /**
     * Comment: 留言精选
     * Author: zzw
     */
    public function selected() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $selected = $_GPC['selected'];
        if ($selected == 0) {
            $update['selected'] = 1;
        } else {
            $update['selected'] = 0;
        }
        $result = pdo_update(PDO_NAME . "headline_comment", $update, array("id" => $id));
        if ($result) {
            wl_json(1, '成功', $update['selected']);
        } else {
            wl_json(0, '失败', $update['selected']);
        }
    }
    /**
     * Comment: 设置留言置顶
     * Author: zzw
     */
    public function setTop() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $topState = $_GPC['topState'];
        $hid = $_GPC['hid'];
        //取消所有置顶信息
        $result = pdo_update(PDO_NAME . "headline_comment", array('set_top' => 0), array('hid' => $hid));
        if ($topState == 0) {
            //设置置顶信息
            $result = pdo_update(PDO_NAME . "headline_comment", array('set_top' => 1), array('id' => $id, 'hid' => $hid));
        }
        if ($result) {
            wl_json(1, '成功');
        } else {
            wl_json(0, '失败');
        }
    }
    /**
     * Comment: 回复留言
     * Author: zzw
     */
    public function reply() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $data['reply'] = json_encode($_GPC['text']);
        $data['reply_time'] = time();
        $result = pdo_update(PDO_NAME . "headline_comment", $data, array('id' => $id));
        if ($result) {
            wl_json(1, '成功');
        } else {
            wl_json(0, '失败');
        }
    }

    /**
     * Comment: 换灯片方法
     * Author: wlf
     */
    public function advurl(){
        global $_W, $_GPC;
        $key = $_GPC['kw'];
        include wl_template('headline/advurl');
    }

    /**
     * Comment: 删除留言
     * Author: wlf
     */
    public function delatecom(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ? : $_GPC['ids'];
        if(is_array($id)){
            foreach ($id as $item) {
                $res = pdo_delete(PDO_NAME.'headline_comment', array('id' => $item));
            }
        }else{
            $res = pdo_delete(PDO_NAME.'headline_comment',array('id'=>$id));
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败,请刷新重试');
        }
    }

}








