<?php
defined('IN_IA') or exit('Access Denied');

class Notice_WeliamController{
    /**
     * Comment: 获取公告列表信息
     * Author: zzw
     * Date: 2019/9/18 11:38
     */
    public function index(){
        global $_W,$_GPC;
        #1、参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        #1、条件生成
        $where = " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        $table = tablename(PDO_NAME."notice");
        #1、列表信息获取
        $list = pdo_fetchall("SELECT id,title,enabled,createtime FROM ".$table.$where ." ORDER BY createtime DESC limit {$pageStart},{$pageIndex}");
        #1、分页操作
        $total = pdo_fetchcolumn("SELECT count(*) FROM ".$table.$where);
        $pager = wl_pagination(ceil($total / $pageIndex), $page, $pageIndex);

        include wl_template('dashboard/noticeIndex');
    }
    /**
     * Comment: 编辑公告
     * Author: zzw
     * Date: 2019/9/19 10:03
     */
	 public function edit(){
	 	global $_W,$_GPC;
	 	#1、参数接收
        $id = $_GPC['id'] ? : '';
		if(checksubmit('submit')){
            #1、接收数据并且做出对应的操作
            $data = $_GPC['notice'];
            $data['content'] = htmlspecialchars_decode($data['content']);
			if($id){
			    #2、修改操作 - 判断是否修改
                $data['id'] = $id;
			    $res = pdo_get(PDO_NAME."notice",$data);
			    if($res) wl_message('请修改后提交!',web_url('dashboard/notice/edit',['id'=>$id]),'success');
			    #3、修改操作
                unset($data['id']);
                $res = pdo_update(PDO_NAME."notice",$data,['id'=>$id]);
			}else{
			    #2、添加操作
                $data['aid']        = $_W[ 'aid' ];
                $data['uniacid']    = $_W[ 'uniacid' ];
                $data['createtime'] = time();
                $res = pdo_insert(PDO_NAME."notice",$data);
			}
            #3、返回结果
            if($res) wl_message('操作成功',web_url('dashboard/notice/index'),'success');
			    else wl_message('操作失败',referer(),'error');
		}
         #2、编辑信息准备
		if($id) $notice = pdo_get(PDO_NAME."notice",['id'=>$id],['id','title','content','link','enabled']);

		include wl_template('dashboard/noticeEdit');
	 }
    /**
     * Comment: 删除公告
     * Author: zzw
     * Date: 2019/9/19 10:03
     */
    public function delete (){
        global $_W , $_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR show_json(0, '删除失败,缺少id!');
        #2、删除操作
        pdo_delete(PDO_NAME."notice",['id'=>$id]) OR show_json(0, '失败');
        show_json(1, '成功');
    }
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 15:20
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：公告id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."notice",['enabled'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
            else Commons::sRenderError('修改失败，请刷新重试!');
    }

    public function clear(){
        global $_W , $_GPC;
        if($_W['aid'] > 0){
            pdo_delete('wlmerchant_diypage',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'name' => 'weliam_default_index'));
        }else{
            pdo_delete('wlmerchant_diypage',array('uniacid'=>$_W['uniacid'],'name' => 'weliam_default_index'));
        }
        show_json(1, '清理成功');
    }
}