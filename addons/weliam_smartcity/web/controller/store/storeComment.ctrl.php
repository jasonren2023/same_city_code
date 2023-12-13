<?php
defined('IN_IA') or exit('Access Denied');

class StoreComment_WeliamController{
	//商户评论列表
	public function index(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where=array();
		$where['uniacid'] = $_W['uniacid'];
		if($_W['aid'] > 0){
            $where['aid'] = $_W['aid'];
        }
		$stores = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid'=>$_W['aid']),array('id'));
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
			}
		}
		if (!empty($_GPC['type'])) $where['true'] = $_GPC['type'];
		if (!empty($_GPC['keyword'])) $where['sid'] = $_GPC['keyword'];
		if (!empty($_GPC['checkone'])) $where['checkone'] = $_GPC['checkone'];
		$data = Util::getNumData("*", PDO_NAME.'comment', $where, 'createtime desc', $pindex, $psize, 1);
		$lists = $data[0];
		$pager = $data[1];
		foreach($lists as $key=>&$value){
			$starNum = array();
			for($i=0;$i<$value['star'];$i++){
				$starNum[$i] = $i;
			}
			$value['star'] = $starNum;
			//获取用户信息
            $commember =  pdo_get('wlmerchant_member',array('id' => $value['mid']),array('nickname','encodename','avatar'));
            $value['nickname'] = empty($commember['encodename']) ? $commember['nickname'] : base64_decode($commember['encodename']);
            $value['headimg'] = tomedia($commember['avatar']);

            if($value['housekeepflag'] == 1){
                $member = pdo_get('wlmerchant_member',array('id' => $value['sid']),array('nickname','encodename'));
                $value['sName'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            }else{
                $value['sName'] = Util::idSwitch('sid', 'sName', $value['sid']);
            }

            //订单链接
            switch ($value['plugin']){
                case 'rush':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 1]);
                    break;
                case 'groupon':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 10]);
                    break;
                case 'wlfightgroup':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 2]);
                    break;
                case 'coupon':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 3]);
                    break;
                case 'bargain':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 12]);
                    break;
                case 'hotel':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 17]);
                    break;
                case 'activity':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 9]);
                    break;
                case 'citydelivery':
                    $value['orderurl'] = web_url('order/wlOrder/orderdetail',['orderid' => $value['idoforder'],'type'=> 14]);
                    break;
                case 'halfcard':
                    $value['orderurl'] = web_url('order/orderPayOnline/payonlinelist',['orderid' => $value['idoforder']]);
                    break;
                case 'housekeep':
                    $value['orderurl'] = web_url('housekeep/KeepType/orderlists',['orderid' => $value['idoforder']]);
                    break;
            }


		}
		include wl_template('store/comment');
	}
	//商户评论审核
	public function check(){
		global $_W,$_GPC;
		$id = $_GPC['id'];
		$pindex = $_GPC['pindex'];
		$page = $_GPC['page'];
		$data = Util::getSingelData("*", PDO_NAME.'comment', array('id'=>$id));
		$starNum = array();
		for($i=0;$i<$data['star'];$i++){
			$starNum[$i] = $i;
		}
		$data['star'] = $starNum;
		$data['pic'] = unserialize($data['pic']);
		$data['sName'] = Util::idSwitch('sid', 'sName', $data['sid']);
		if($_GPC['checkone']){
			$update = array(
				'checkone'=>$_GPC['checkone'],
				'pic'=>serialize($_GPC['pic']),
			);
			//送积分
			if($_W['wlsetting']['creditset']['commentcredit'] && $update['checkone'] == 2){
				Member::credit_update_credit1($data['mid'], $_W['wlsetting']['creditset']['commentcredit'], '评价赠送积分');
			}
			pdo_update(PDO_NAME.'comment',$update,array('id'=>$id));
			wl_message("操作成功！",web_url('store/storeComment/index',array('page'=>$page)),'success');
		}
		if($_GPC['ids']){
			$ids = explode(",", $_GPC['ids']);;
			foreach($ids as$k=>$v){
				pdo_update(PDO_NAME.'comment',array('checkone'=>$_GPC['check']),array('id'=>$v));
				if($_W['wlsetting']['creditset']['commentcredit'] && $_GPC['check'] == 2){
					$mid = pdo_getcolumn(PDO_NAME.'comment',array('id'=>$v),'mid');
					Member::credit_update_credit1($mid, $_W['wlsetting']['creditset']['commentcredit'], '评价赠送积分');
				}
			}
			wl_message("操作成功！",web_url('store/storeComment/index'),'success');
		}


		include wl_template('store/comment_check');
	}
	//商户评论回复
    public function reply(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $pindex = $_GPC['pindex'];
        $page = $_GPC['page'];
        $data = Util::getSingelData("*", PDO_NAME.'comment', array('id'=>$id));
        $starNum = array();
        for($i=0;$i<$data['star'];$i++){
            $starNum[$i] = $i;
        }
        $data['star'] = $starNum;
        $data['pic'] = unserialize($data['pic']);
        $data['sName'] = Util::idSwitch('sid', 'sName', $data['sid']);
        $data['replypicone'] = unserialize($data['replypicone']);


        if($_GPC['replytextone']){
            $replyone = $_GPC['replytextone']?2:1;
            $update = array(
                'replytextone'=>$_GPC['replytextone'],
                'replypicone'=>serialize($_GPC['replypicone']),
                'replyone'=>$replyone,
            );
            pdo_update(PDO_NAME.'comment',$update,array('id'=>$id));
            //发送模板消息
            $comment = pdo_get('wlmerchant_comment',array('id' => $id),array('mid','replytextone','sid'));
            $storename = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$comment['sid']),'storename');
            $openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$comment['mid']),'openid');
            $first = '商家回复了您的评论';
            $type = '商家评论回复';
            $status = '已回复';
            $remark = '回复内容:'.$comment['replytextone'];
            $content = '商家名:['.$storename.']';
            News::jobNotice($comment['mid'],$first,$type,$content,$status,$remark,time());
            wl_message("操作成功！",web_url('store/storeComment/index',array('page'=>$page)),'success');
        }
        include wl_template('store/comment_reply');

    }
    //添加商户评论
    public function add(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $pindex = $_GPC['pindex'];
        $page = $_GPC['page'];
        if(empty($id) && !is_store()){
            $storeList = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 2,'enabled' => 1),array('id','storename'));
        }else{
            if($id) $data = Util::getSingelData("*", PDO_NAME.'comment', array('id'=>$id));
        }
        if($_GPC['data']){
            unset($data['id']);
            $update = $_GPC['data'];
            if(empty($id)){
                $data['sid'] = $update['sid'];
                $data['gid'] = $update['gid'];
                $data['plugin'] = $update['plugin'];
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['status'] = 1;
            }
            $data['star'] = $update['star'];
            if($data['star'] > 3){
                $data['level'] = 1;
            }else if($data['star'] == 3){
                $data['level'] = 2;
            }else{
                $data['level'] = 3;
            }
            $data['headimg'] = tomedia($update['headimg']);
            $data['nickname'] = $update['nickname'];
            $data['text'] = $update['text'];
            $data['createtime'] = strtotime($update['time']);
            $data['replytextone'] = $update['replytextone'];
            if(!empty($data['replytextone'])){
                $data['replyone'] = 2;
            }
            $data['true'] = 2;
            $data['checkone'] = 2;
            $data['pic']=serialize($_GPC['pic']);
            $data['replypicone']=serialize($_GPC['replypicone']);
            pdo_insert(PDO_NAME.'comment',$data);
            wl_message("操作成功！",web_url('store/storeComment/index',array('page'=>$page)),'success');
        }
        include wl_template('store/comment_add');
    }
    //删除商户评论
    public function delete(){
        global $_W,$_GPC;
        $pindex = $_GPC['pindex'];
        if($_GPC['id']){
            $ids = explode(",", $_GPC['id']);;
            foreach($ids as$k=>$v){
                pdo_delete(PDO_NAME.'comment',array('id'=>$v));
            }
        }
        show_json(1);
    }
}
