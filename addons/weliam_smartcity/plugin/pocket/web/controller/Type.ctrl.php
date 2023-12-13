<?php
defined('IN_IA') or exit('Access Denied');

class Type_WeliamController {
    /**
     * Comment: 进入帖子分类列表
     */
	function lists() {
		global $_W, $_GPC;
		$list = Pocket::gettypes();
		include   wl_template('pocket/typelist');
	}
    /**
     * Comment: 进入编辑分类
     */
	function operating() {
		global $_W, $_GPC;
		//参数信息获取
        $parentId = trim($_GPC['parentid']);

		if ($_GPC['id']) {
			$data = Util::getSingelData("*", PDO_NAME . 'pocket_type', array('id' => $_GPC['id']));
			if($data['type'] > 0) $parentId = $data['type'];
            $data['adv'] = unserialize($data['adv']);
		}
		if ($_GPC['did']) {
			pdo_delete(PDO_NAME . 'pocket_type', array('id' => $_GPC['did']));
			wl_message('删除数据成功', web_url('pocket/Type/lists'), 'success');
		}
		if ($_GPC['data']) {
			$temp = $_GPC['data'];
			$temp['uniacid'] = $_W['uniacid'];
			$temp['aid'] = $_W['aid'];
            $temp['adv'] = serialize($temp['adv']);

			if ($temp['id'] > 0) {
				$status = Util::getSingelData("status", PDO_NAME . 'pocket_type', array('id' => $temp['type']));
				
				if ($status['status'] === 0) {
					$temp['status'] = 0;
				}
				pdo_update(PDO_NAME . 'pocket_type', $temp, array('id' => $temp['id']));
				$temp = Util::getSingelData("*", PDO_NAME.'pocket_type', array('id'=>$temp['id']));
				
				if (!$temp['type']) {
					echo $temp['status'];
					if (!$temp['status']) {
						$temp1 = Util::getNumData("*", PDO_NAME . 'pocket_type', array('type' => $temp['id']));
						$temp1 = $temp1[0];
						foreach ($temp1 as $value) {
							$value['status'] = 0;
							pdo_update(PDO_NAME . 'pocket_type', $value, array('id' => $value['id']));
						}
					}
					if ($temp['status'] == 1) {
					$temp1 = Util::getNumData("*", PDO_NAME . 'pocket_type', array('type' => $temp['id']));
					$temp1 = $temp1[0];
					foreach ($temp1 as $value) {
						$value['status'] = 1;
						pdo_update(PDO_NAME . 'pocket_type', $value, array('id' => $value['id']));
					}
				}
				}

				wl_message('修改成功', web_url('pocket/Type/lists'), 'success');
			}else if ($parentId > 0) {
                $temp['type'] = $parentId;

                $status = Util::getSingelData("status", PDO_NAME . 'pocket_type', array('id' => $temp['type']));
                if ($status['status'] === 0) {
                    $temp['status'] = 0;
                }
                pdo_insert(PDO_NAME . 'pocket_type', $temp);
                wl_message('插入子类型成功', web_url('pocket/Type/lists'), 'success');
            } else {
				pdo_insert(PDO_NAME . 'pocket_type', $temp);
				wl_message('添加类型成功', web_url('pocket/Type/lists'), 'success');
			}
		}
		//进入添加子分类页面  获取上级分类信息
        if($parentId > 0){
            $parentTitle = pdo_getcolumn(PDO_NAME . 'pocket_type',['id'=>$parentId],'title');
        }
        //获取表单信息列表
        $diyFormList = pdo_getall(PDO_NAME."diyform",['uniacid'=> $_W['uniacid'],'aid' => $_W['aid'],'sid' => 0],['id','title'],'','create_time DESC,id DESC');

		include  wl_template('pocket/typeadd');
	}
    /**
     * Comment: 设置/修改 帖子发帖需要支付的费用
     * Author: zzw
     */
	function setPrice(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $price = trim($_GPC['value']);
        $res = pdo_update(PDO_NAME."pocket_type",array('price'=>$price),array('id'=>$id));
        if($res){
            show_json(1, "修改成功");
        }else{
            show_json(0, "修改失败");
        }
    }
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 17:37
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：导航id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."pocket_type",['status'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }



}