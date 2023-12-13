<?php
defined('IN_IA') or exit('Access Denied');

class HelperModuleUniapp extends Uniapp {
    /**
     * Comment: 帮助中心信息获取
     * Author: zzw
     * Date: 2019/8/23 10:27
     */
    public function helpInfo(){
        global $_W,$_GPC;
        #1、幻灯片信息获取
        $adv = pdo_getall(PDO_NAME."helper_slide"
            ,['uniacid'=>$_W['uniacid'],'status'=>1]
            ,['img','url'],'',' sort DESC ');
        if(is_array($adv) && count($adv) > 0){
            foreach ($adv as $key => &$val){
                $val['img'] = tomedia($val['img']);
            }
        }
        #2、分类列表信息获取
        $class = Helper::getTypeList(false);
        #3、信息拼装
        $data['set'] = $_W['wlsetting']['helper']['status'] ? : 0;
        $data['adv'] = $adv;
        $data['class'] = $class;

        $this->renderSuccess('帮助中心信息获取',$data);
    }
    /**
     * Comment: 获取问题列表
     * Author: zzw
     * Date: 2019/8/23 10:43
     */
    public function problemList(){
        global $_W,$_GPC;
        #1、参数接收
        $is_recommend = $_GPC['is_recommend'] ? : 0;//是否推荐0=获取全部；1=仅获取推荐问题
        $type_id = $_GPC['type_id'] ? : 0;//0=获取全部；大于0仅获取当前分类问题
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search = $_GPC['search'] ? : '';
        #2、条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND status = 1 ";
        if($is_recommend > 0) $where .= " AND recommend = 1 ";
        if($type_id > 0) $where .= " AND type = {$type_id} ";
        if(!empty($search)) $where .= " AND title LIKE '%{$search}%' ";
        #3、列表
        $data['list'] = pdo_fetchall("SELECT id,title FROM ".tablename(PDO_NAME."helper_question")
                             .$where." ORDER BY sort DESC,id DESC LIMIT {$pageStart},{$pageIndex}");
        #3、获取总页数
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."helper_question") .$where);
        $data['total'] = ceil($total / $pageIndex);

        $this->renderSuccess('获取问题列表',$data);
    }
    /**
     * Comment: 获取问题的详细信息
     * Author: zzw
     * Date: 2019/8/23 10:54
     */
    public function detail(){
        global $_W,$_GPC;
        #1、参数接收
        $_GPC['id'] ? $id = $_GPC['id'] : $this->renderError('缺少参数：id');
        #2、信息获取
        $info = pdo_get(PDO_NAME."helper_question",['id'=>$id] ,['title','content']);
        $info['content'] = htmlspecialchars_decode($info['content']);

        $this->renderSuccess('问题的详细信息',$info);
    }


}




































