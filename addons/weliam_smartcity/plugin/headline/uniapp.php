<?php
defined('IN_IA') or exit('Access Denied');

class HeadlineModuleUniapp extends Uniapp {
    /**
     * Comment: 头条列表信息
     * Author: zzw
     * Date: 2019/7/8 15:18
     */
    public function HeadlineList() {
        global $_W, $_GPC;
        #1、参数获取
        $page     = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_num = $_GPC['page_num'] ? $_GPC['page_num'] : 10;
        $one_id = $_GPC['one_id'] ? : 0;
        $two_id = $_GPC['two_id'] ? : 0;;
        $sort = $_GPC['sort'] ? : 0;;

        #2、列表信息
        $list = WeliamWeChat::getHeadline('', $page, $page_num,$one_id,$two_id,$sort);
        #3、获取总页数
        $total = pdo_fetchcolumn("SELECT COUNT(*) as total FROM ".tablename(PDO_NAME.'headline_content')
                                 ." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ");
        if($total > 0) $total_page = ceil($total / $page_num);
        #4、头条列表顶部轮播图信息
        $banner = pdo_getall(PDO_NAME."adv",['type'=>10,'uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'enabled' => 1],['link','thumb']);
        foreach($banner as $key => &$val){
            $val['thumb'] = tomedia($val['thumb']);
        }
        #5、获取头条列表自定义菜单
        $settings = Setting::agentsetting_read('diypageset');
        if($settings['menu_headline'] > 0){
            $menudata = Diy::getMenu($settings['menu_headline'], TRUE);
        }else{
            $menudata = DiyMenu::defaultHeadlineMenu();
        }
        //基本设置
        $set = Setting::wlsetting_read('base');
        #6、数据拼装
        $data['menu']  = $menudata;
        $data['head']  = is_array($banner) ? $banner : [];
        $data['list']  = is_array($list) ? $list : [];
        $data['total'] = $total_page ? $total_page : 0;
        $data['banner_set'] = [
            'img_width' => $set['listwidth'] ? : 640,
            'img_height' => $set['listheight'] ? : 300
        ];

        $this->renderSuccess('头条列表',$data);
    }
    /**
     * Comment: 获取某条头条的详细信息
     * Author: zzw
     */
    public function HeadlineInfo() {
        global $_W, $_GPC;
        #1、参数获取
        $id = $_GPC['id'];//头条id
        $mid = $_W['mid'] ? : '';//用户id
        if(!$id) wl_json(1,'缺少参数：id');
        #2、头条详细信息获取
        $info = pdo_fetch("SELECT 
            a.id,
            a.author,
            a.author_img,
            a.title,
            a.summary,
            a.browse,
            a.display_img,
            a.content,
            a.call_id,
            a.labels,
            a.goods_id,
            a.goods_plugin,
            a.advs,
            a.bannerid,
            a.banposition,
            b.name as one_name,
            d.name as two_name
            FROM " . tablename(PDO_NAME . "headline_content")
                          . " a LEFT JOIN "
                          . tablename(PDO_NAME . "headline_class")
                          . " b ON a.one_id = b.id "
                          . " LEFT JOIN "
                          . tablename(PDO_NAME . "headline_class")
                          . " d ON a.two_id = d.id "
                          . " WHERE a.id = {$id}");
        #3、获取集call信息
        if(p('call') && $info['call_id']){
            $call = pdo_fetch("SELECT a.id, b.title FROM " . tablename(PDO_NAME . "call")
                              . " a LEFT JOIN "
                              . tablename(PDO_NAME . "couponlist")
                              . " b ON a.prize_id = b.id "
                              . " WHERE a.uniacid = {$_W['uniacid']}  AND a.id = {$info['call_id']} AND a.state = 1 ORDER BY id DESC ");
        }
        $info['call'] = $call ? $call : [];
        #4、改变头条的阅读量
        $number = intval($info['browse']) + intval(1);
        pdo_update(PDO_NAME . "headline_content", array("browse" => intval($number)), array('id' => $id));
        //处理幻灯片
        $info['advs'] = unserialize($info['advs']);
        if(!empty($info['advs'])){
            foreach($info['advs'] as &$ad){
                $ad['img'] = tomedia($ad['img']);
            }
        }
        #6、信息拼装
        $info['author_img'] = tomedia($info['author_img']);
        $info['display_img'] = tomedia($info['display_img']);
        if (is_base64($info['content'])) $info['content'] = base64_decode($info['content']);
        $info['id'] = $id;
        $info['labels'] = explode("#",$info['labels']);
        //获取商品详情链接 rush-抢购商品；groupon-团购商品；wlfightgroup-拼团商品；coupon-卡券商品；bargain-砍价商品
        switch ($info['goods_plugin']) {
            case 'rush':
                $info['goods_url'] = h5_url('pages/subPages/goods/index',['id'=>$info['goods_id'],'type'=>1]);
                if($info['goods_id'] > 0){
                    $info['goods'] = pdo_get(PDO_NAME.'rush_activity',
                                             ['id'=>$info['goods_id'],'uniacid'=>$_W['uniacid']],
                                             ['id','thumb','name','price','oldprice']);
                }
                break;//抢购商品
            case 'groupon':
                $info['goods_url'] = h5_url('pages/subPages/goods/index',['type'=>2,'id'=>$info['goods_id']]);
                $info['goods'] = pdo_get(PDO_NAME.'groupon_activity',
                                         ['id'=>$info['goods_id'],'uniacid'=>$_W['uniacid']],
                                         ['id','thumb','name','price','oldprice']);
                break;//团购商品
            case 'wlfightgroup':
                $info['goods_url'] = h5_url('pages/subPages/goods/index',['type'=>3,'id'=>$info['goods_id']]);
                if($info['goods_id'] > 0){
                    $info['goods'] = pdo_fetch("SELECT id,logo as thumb,name,price,oldprice FROM "
                                               .tablename(PDO_NAME.'fightgroup_goods')
                                               ." WHERE id = {$info['goods_id']} AND uniacid = {$_W['uniacid']}  ");
                }
                break;//拼团商品
            case 'coupon':
                $info['goods_url'] = h5_url('pages/subPages/goods/index',['type'=>5,'id'=>$info['goods_id']]);
                if($info['goods_id'] > 0){
                    $info['goods'] = pdo_fetch("SELECT id,logo as thumb,title as name,price FROM "
                                               .tablename(PDO_NAME.'couponlist')
                                               ." WHERE id = {$info['goods_id']} AND uniacid = {$_W['uniacid']}  ");
                }
                break;//优惠券
            case 'bargain':
                $info['goods_url'] = h5_url('pages/subPages/goods/index',['type'=>7,'id'=>$info['goods_id']]);
                if($info['goods_id'] > 0){
                    $info['goods'] = pdo_fetch("SELECT id,thumb,name,price,oldprice FROM "
                                               .tablename(PDO_NAME.'bargain_activity')
                                               ." WHERE id = {$info['goods_id']} AND uniacid = {$_W['uniacid']}  ");
                }
                break;//砍价商品
        }
        //转义商品图片
        if($info['goods'] &&is_array($info['goods'])){
            $info['goods']['thumb'] = tomedia($info['goods']['thumb']);
        }
        //内容转码
        $info['content'] = htmlspecialchars_decode($info['content']);
        $info['content'] = str_replace("section","div",$info['content']);

        $this->renderSuccess('头条的详细信息',$info);
    }
    /**
     * Comment: 头条留言功能
     * Author: zzw
     * Date: 2019/7/8 16:58
     */
    public function HeadlineComment (){
        global $_W , $_GPC;
        $mid  = $_W['mid'];//用户id
        $hid  = $_GPC['hid'];//头条id
        $text = $_GPC['text'];//留言内容
        if(!$hid) wl_json(1,'缺少参数：头条id');
        if(!$mid) wl_json(1,'请先登录');
        if(!$text) wl_json(1,'缺少参数：留言内容');
        //判断文本内容是否非法
        $textRes = Filter::init($text,$_W['source'],1);
        if($textRes['errno'] == 0) $this->renderError($textRes['message']);
        //储存留言信息
        $data['mid']   = $mid;
        $data['hid']   = $hid;
        $data['times'] = time();
        $data['text']  = json_encode($text);
        $result        = pdo_insert(PDO_NAME . 'headline_comment' , $data);
        if ($result) {
            $id = pdo_insertid();
            $this->renderSuccess('留言成功',$id);
        } else {
            $this->renderError('留言失败');
        }
    }
    /**
     * Comment: 好评&头条留言点赞
     * Author: zzw
     */
    public function Fabulous (){
        global $_W , $_GPC;
        $mid                  = $_W['mid'];//用户id
        $class                = $_GPC['class'];//点赞类别(1=好评点赞,2=头条留言点赞)
        $relation_id          = $_GPC['id'];//关联好评表||头条留言表的id
        $table                = PDO_NAME . "fabulous";
        $where['mid']         = $mid;//用户id
        $where['class']       = $class;
        $where['relation_id'] = $relation_id;
        $existence            = pdo_get($table , $where);
        if ($existence) {
            //点赞存在 取消点赞
            $result = pdo_delete($table , $where);
        } else {
            //点赞不存在 添加一条点赞信息
            $data   = [
                'mid'         => $mid ,
                'relation_id' => $relation_id ,
                'class'       => $class ,
                'times'       => time() ,
            ];
            $result = pdo_insert($table , $data);
        }
        if ($result) {
            $this->renderSuccess('点赞成功');
        } else {
            $this->renderError('点赞失败');
        }
    }
    /**
     * Comment: 获取某个头条留言信息
     * Author: zzw
     * Date: 2021/2/4 9:09
     */
    public function getComment(){
        global $_W, $_GPC;
        //参数获取
        $id = $_GPC['id'];//头条id
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $mid = $_W['mid'] ? : '';//用户id
        //条件生成  头条留言信息  目前是只显示精选的留言  根据登录情况使用查询条件
        if($mid){
            $where = " WHERE (a.selected = 1 OR a.mid = {$mid}) AND a.hid = {$id}";
        }else{
            $where = " WHERE a.selected = 1 AND a.hid = {$id}";
        }
        $order = " ORDER BY a.set_top DESC, a.times DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex}";
        $field = "  a.id,a.times,a.text,a.reply,a.reply_time,a.set_top,b.nickname,b.avatar";
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . "headline_comment")
            . " a LEFT JOIN "
            . tablename(PDO_NAME . "member")
            . " b ON a.mid = b.id ";
        //信息列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $k => &$v) {
            $v['times'] = date("Y-m-d H:i:s", $v['times']);
            $v['text'] = json_decode($v['text']);
            $v['reply'] = json_decode($v['reply']);
            $v['reply_time'] = date("Y-m-d H:i:s", $v['reply_time']);
            //获取当前留言的点赞数量
            $v['fabulousNum'] = intval(implode(pdo_fetch("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "fabulous")
                . " WHERE `relation_id` = {$v['id']} AND `class` = 2")));
            //判断用户是否对当前留言点赞
            if ($mid) {
                $v['fabulousState'] = (pdo_get(PDO_NAME . "fabulous"
                    , array('relation_id' => $v['id'], 'class' => 2, 'mid' => $mid))) ? true : false;
            } else {
                $v['fabulousState'] = false;
            }
        }
        //分页总数获取
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list'] = $list;

        $this->renderSuccess('头条留言信息',$data);
    }

    /**
     * Comment: 首页菜单栏
     * Author: wlf
     * Date: 2021/10/22 11:37
     */
    public function headlineSelectInfo(){
        global $_W , $_GPC;
        #1、参数获取
            $whole = [
                [
                    'id'   => '0' ,
                    'name' => '全部' ,
                    'list' => []
                ]
            ];
            //获取掌上信息分类列表
            $list = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . "headline_class") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND head_id = 0 AND state = 1 ORDER BY sort DESC,id DESC ");
            if (is_array($list) && count($list) > 0) {
                foreach ($list as $key => &$val) {
                    $val['list'] = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . "headline_class") . " WHERE state = 1 AND head_id = {$val['id']} ORDER BY sort DESC,id DESC ");
                }
            }
            $list = array_merge($whole , $list);
            //信息拼装
            $data = [
                'top'    => [
                    ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                    ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
                ] ,
                'class'  => $list ,
                'orders' => [
                    ['title' => '最新' , 'val' => 0] ,
                    ['title' => '热度' , 'val' => 1] ,
                ] ,
            ];
        $this->renderSuccess('选择信息列表' , $data);
    }



}




































