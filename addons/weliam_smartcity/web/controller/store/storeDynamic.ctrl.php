<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 商户动态
 * Author: zzw
 * Date: 2021/1/7 16:25
 * Class StoreDynamic_WeliamController
 */
class StoreDynamic_WeliamController{
    //批量审核
	public function dyncheck(){
		global $_W,$_GPC;
		if($_GPC['ids']){
			$ids = explode(",", $_GPC['ids']);;
			foreach($ids as$k=>$v){
				pdo_update(PDO_NAME.'store_dynamic',array('status'=>$_GPC['check']),array('id'=>$v));
			}
			wl_message("操作成功！",web_url('store/storeDynamic/dynamic'),'success');
		}
	}
    //动态详情
	public function checkdyn(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $data = pdo_get(PDO_NAME.'store_dynamic',array('id' => $id));
        $data['pic'] = unserialize($data['imgs']);
        $data['sName'] = Util::idSwitch('sid', 'sName', $data['sid']);
        if($_GPC['token']){
            $update = array(
                'content' => $_GPC['content'],
                'status' => $_GPC['checkone'],
                'imgs' => serialize($_GPC['pic']),
            );
            pdo_update(PDO_NAME.'store_dynamic',$update,array('id'=>$id));
            wl_message("操作成功！",web_url('store/storeDynamic/dynamic'),'success');
        }
        include wl_template('store/dynamic_check');
    }
    //批量删除动态
	public function dyndelete(){
		global $_W,$_GPC;
		if($_GPC['ids']){
			$ids = explode(",", $_GPC['ids']);;
			foreach($ids as$k=>$v){
				pdo_delete(PDO_NAME.'store_dynamic',array('id'=>$v));
			}
		}
		wl_message("操作成功！",web_url('store/storeDynamic/dynamic'),'success');
	}
    //动态列表
	public function dynamic(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where=array();
		$where['uniacid'] = $_W['uniacid'];
		if($_W['aid'] > 0){
            $where['aid'] = $_W['aid'];
        }
        if(is_store()){
            $where['sid'] = $_W['storeid'];
        }
		if (empty($starttime) || empty($endtime)) {//初始化时间
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!empty($_GPC['time'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			switch($_GPC['timetype']){
				case 1:$where['createtime>'] = $starttime;
					   $where['createtime<'] = $endtime;break;
				case 2:$where['passtime>'] = $starttime;
					   $where['passtime<'] = $endtime;break;
				case 3:$where['sendtime>'] = $starttime;
					   $where['sendtime<'] = $endtime;break;
			}
		}
		if (!empty($_GPC['type'])){
			if($_GPC['type'] == 4){
				$where['status'] = 0;
			}else {
				$where['status'] = $_GPC['type'];
			}
		} 
		if (!empty($_GPC['keyword'])) $where['sid'] = $_GPC['keyword'];
		$data = Util::getNumData("*",PDO_NAME.'store_dynamic',$where,'createtime desc',$pindex,$psize,1);
		$lists = $data[0];
		$pager = $data[1];
		foreach($lists as $key=>&$dyn){
			$dyn['sName'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$dyn['sid']),'storename');
			$member = pdo_get(PDO_NAME.'member',array('id'=>$dyn['mid']),array('avatar','nickname'));
			$dyn['headimg'] = tomedia($member['avatar']);
			$dyn['nickname'] = $member['nickname'];
		}
		include wl_template('store/dynamic');
	}
    //删除动态
	public function deletedyn() {
		global $_W,$_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			if (empty($id)) {
				show_json(0, '参数错误，请刷新重试！');
			}else {
				$res = pdo_delete('wlmerchant_store_dynamic', array('id' => $id,'aid' => intval($_W['aid'])));
			}
			if($res){
				show_json(1);
			}else {
				show_json(0, '删除失败,请刷新页面重试！');
			}
		}
	}
	//动态审核
	public function passdyn() {
		global $_W,$_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			if (empty($id)) {
				show_json(0, '参数错误，请刷新重试！');
			}else {
				$type = $_GPC['type'];
				if($type == 'pass'){
					$res = pdo_update('wlmerchant_store_dynamic',array('status' => 1,'passtime'=>time()),array('id' => $id));
				}else if($type == 'reject'){
					$res = pdo_update('wlmerchant_store_dynamic',array('status' => 3,'passtime'=>time()),array('id' => $id));
				}
			}
			if($res){
				show_json(1,'审核成功');
			}else {
				show_json(0, '删除失败,请刷新页面重试！');
			}
		}
	}
	//推送动态
	public function senddyn(){
		global $_W,$_GPC;
		$id = intval($_GPC['id']);
        $dynamic = pdo_get('wlmerchant_store_dynamic',array('id' => $id,'status'=>1));
        if(empty($dynamic)){
            wl_message('此动态已删除或已推送');
        }
        $fans = pdo_getall('wlmerchant_storefans',array('uniacid'=>$_W['uniacid'],'sid' => $dynamic['sid']),array('mid'));
        $fannum = count($fans);
        if(checksubmit('submit')){
            $firsttext = $_GPC['firsttext'];
            $remark =  $_GPC['remark'];
            $content = $_GPC['content'];
            $source = $_GPC['source'] ? : 1;

            include wl_template('store/dyn-process');
            exit;
        }

		include  wl_template('store/dynamicmodel');
	}
	//批量推送
    public function senddyning(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $firsttext = $_GPC['firsttext'];
        $remark =  $_GPC['remark'];
        $content = $_GPC['content'];
        $source = $_GPC['source'] ? : 1;
        $psize = 50;
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);
        $pindex = $data['pindex'];
        $success = $data['success'];

        $dynamic = pdo_get('wlmerchant_store_dynamic',array('id' => $id,'status'=>1));
        $fans = pdo_fetchall("SELECT mid FROM ".tablename('wlmerchant_storefans')."WHERE sid = {$dynamic['sid']} AND uniacid = {$_W['uniacid']} ORDER BY id DESC  LIMIT ".$pindex * $psize . ',' . $psize);
        if($fans){
            $url = h5_url('pages/mainPages/store/index',['sid'=>$dynamic['sid']]);
            foreach($fans as $key => $fan) {
                //模板消息
                News::jobNotice($fan['mid'],$firsttext,'商家动态推送',$content,'已完成',$remark,time(),$url,$source);
                //站内私信
                pdo_insert(PDO_NAME.'im', array('uniacid' => $_W['uniacid'],'send_id'=> $dynamic['sid'],'send_type'=> 2,'receive_id'=>$fan['mid'],'receive_type'=>1,'create_time'=>time(),'content'=>$content));
                $success++;
            }
            $return = array('result' => 1,'success' => $success);
        }else{
            pdo_update('wlmerchant_store_dynamic',array('status' => 2,'sendtime'=>time(),'successnum'=>$success),array('id' => $id));
            $return = array('result' => 3,'success' => $success);
        }
        die(json_encode($return));
    }
}
