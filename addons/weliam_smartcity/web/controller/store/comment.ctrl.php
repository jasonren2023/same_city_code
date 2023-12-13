<?php
defined('IN_IA') or exit('Access Denied');

class Comment_WeliamController{
    public function setting(){
        global $_W,$_GPC;
        $data = Setting::agentsetting_read('comment');
        if($_GPC['token']){
            Setting::agentsetting_save($_GPC['data'], 'comment');
            wl_message("操作成功！",web_url('store/storeComment/index'),'success');
        }

        include wl_template('store/comment_setting');
    }
    /**
     * Comment: 代理端商户设置
     * Author: zzw
     */
	public function storeSet(){
	    global $_W,$_GPC;
        if (checksubmit('submit')) {
            //处理数据值
            $storeSet = $_GPC['store_set'];
            $order = $_GPC['order'];
            $storeSet['order'] = serialize($order);
            $res1 = Setting::wlsetting_save($storeSet,'agentsStoreSet');
            wl_message('保存设置成功！', referer(),'success');
        }
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        if(empty($storeSet['order'])){
            $order = [
                'sporder' => 8,
                'spstatus' => 1,
                'xqorder' => 7,
                'xqstatus' => 1,
                'dtorder' => 6,
                'dtstatus' => 1,
                'plorder' => 5,
                'plstatus' => 1,
                'xcorder' => 4,
                'xcstatus' => 1,
                'xcxmorder' => 3,
                'xcxmstatus' => 1,
                'sjzzorder' => 2,
                'sjzzstatus' => 1,
                'zporder' => 1,
                'zpstatus' => 1,
            ];
        }else{
            $order = unserialize($storeSet['order']);
        }

        include wl_template('store/storeSet');
    }
    
    /**
     * Comment: 红包活动列表
     * Author: wlf
     */
    public function redpackList(){
    	global $_W,$_GPC;
    	
		$page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//表单名称
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($title) $where .= " AND a.title LIKE '%{$title}%'";
		$field = "a.id,a.title,a.onemoney,a.fishmoney,a.fishnum,a.createtime,a.status,b.name";
		$sql = "SELECT {$field} FROM ".tablename(PDO_NAME."store_redpack")
            ." as a LEFT JOIN ".tablename(PDO_NAME."diypage")
            ." as b ON a.diypageid = b.id {$where}";
        $list = pdo_fetchall($sql." ORDER BY a.id DESC limit {$pageStart},{$pageIndex}");
		
    	//总数信息获取
        $countSql = str_replace($field,"count(*)",$sql);
        $total = pdo_fetchcolumn($countSql);
        //分页操作
        $pager = wl_pagination($total, $page, $pageIndex);
    	
    	include wl_template('store/redpackList');
    }
    
     /**
     * Comment: 添加/编辑红包活动
     * Author: wlf
     */
    public function redpackAdd(){
    	global $_W,$_GPC;
		$id = $_GPC['id'];
		if($id > 0){
			$redpack = pdo_get('wlmerchant_store_redpack',array('id' => $id));
		}
		$diypagelist = pdo_getall('wlmerchant_diypage',array('uniacid' => $_W['uniacid'],'aid' => 0,'type' => 21),array('name','id'));
    	$diyposterlist = pdo_getall('wlmerchant_poster',array('uniacid' => $_W['uniacid'],'type' => 17),array('title','id'));
		if ($_W['ispost']) {
			$info = $_GPC['redpack'];
			$info['onemoney'] = sprintf("%.2f",$info['onemoney']);
			$info['fishmoney'] = sprintf("%.2f",$info['fishmoney']);
			$info['fishnum'] = intval($info['fishnum']);
			if($id > 0){
				$res = pdo_update('wlmerchant_store_redpack',$info,array('id' => $id));
			}else{
				$info['uniacid'] = $_W['uniacid']; 
				$info['createtime'] = time(); 
				$res = pdo_insert('wlmerchant_store_redpack', $info);
			}
			wl_message("操作成功！",web_url('store/comment/redpackList'),'success');
		}
    	
    	
    	include wl_template('store/redpackAdd');
    }
	
	/**
     * Comment: 删除红包活动
     * Author: wlf
     */
     public function redpackDel(){
     	global $_W,$_GPC;
		$id = $_GPC['id'];
		$res = pdo_delete('wlmerchant_store_redpack',array('id'=>$id));
     	if($res > 0){
     		show_json(1,'删除成功');
     	}else{
     		show_json(0,'删除失败，请刷新重试');
     	}
     }


	/**
     * Comment: 红包领取记录
     * Author: wlf
     */
     
    public function redpackRecord(){
    	global $_W,$_GPC;
		//参数信息获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $where = ['uniacid' => $_W['uniacid']];
		if($_GPC['redpackid'] > 0){
			$where['redpackid'] = $_GPC['redpackid'];
		}
		//搜索
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            if ($_GPC['keywordtype'] == 1) {
                $params[':nickname'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                if ($merchants) {
                    $sids = "(";
                    foreach ($merchants as $key => $v) {
                        if ($key == 0) {
                            $sids .= $v['id'];
                        } else {
                            $sids .= "," . $v['id'];
                        }
                    }
                    $sids .= ")";
                    $where['mid#'] = $sids;
                } else {
                    $where['mid#'] = "(0)";
                }
            } else if ($_GPC['keywordtype'] == 2) {
                $where['mid@'] = intval($keyword);
            } else if ($_GPC['keywordtype'] == 3){
                $params[':mobile'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :mobile", $params);
                if ($merchants) {
                    $sids = "(";
                    foreach ($merchants as $key => $v) {
                        if ($key == 0) {
                            $sids .= $v['id'];
                        } else {
                            $sids .= "," . $v['id'];
                        }
                    }
                    $sids .= ")";
                    $where['mid#'] = $sids;
                } else {
                    $where['mid#'] = "(0)";
                }
            }
        }

		//时间
        if ($_GPC['time_limit'] && $_GPC['timetype']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
			if($_GPC['timetype'] == 1){
				$where['onegettime>'] = $starttime;
           		$where['onegettime<'] = $endtime + 86399;
			}else if($_GPC['timetype'] == 2){
				$where['fishgettime>'] = $starttime;
            	$where['fishgettime<'] = $endtime + 86399;
			}
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
		$list = Util::getNumData('*', PDO_NAME.'store_redpack_record', $where, 'id DESC', $page, $pageIndex, 1);

        $pager = $list[1];
        $list = $list[0];
		
		foreach($list as &$li){
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('nickname','encodename','avatar','mobile'));
            $li['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $li['mobile'] = $member['mobile'];
            $li['avatar'] = tomedia($member['avatar']);
			
			$li['sharenum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_store_redpack_share')." WHERE invitmid = {$li['mid']} AND redpackid = {$li['redpackid']}");
        }
		
    	
    	include wl_template('store/redpackRecord');
    }
	
	
}
