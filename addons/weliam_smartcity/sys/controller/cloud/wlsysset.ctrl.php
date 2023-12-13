<?php
defined('IN_IA') or exit('Access Denied');

class Wlsysset_WeliamController{
	function taskcover(){
		global $_W,$_GPC;
        $sets = Cloud::wl_syssetting_read('taskcover');
        if($sets['passiveid']){
            $passiveid = unserialize($sets['passiveid']);
        }else{
            $passiveid = [];
        }
		$settings['url'] = $_W['siteroot']."addons/".MODULE_NAME."/core/common/task.php";
		$settings['name'] = '计划任务入口';
        $lock = cache_read(MODULE_NAME.':task:status');
        if(empty($lock)){
            $lock = unserialize(pdo_getcolumn('core_cache',array('key' => MODULE_NAME.':task:status'),'value'));
        }
		if(empty($lock)){
			$status = 1;
		}else if($lock['expire'] < (time() - 600 )){
			$status = 3;
		}else{
            $status = 2;
        }
        $all_wechats = pdo_fetchall("select a.uniacid, b.name from " . tablename("account") . " as a left join " . tablename("account_wechats") . " as b on a.uniacid = b.uniacid WHERE a.isdeleted = 0 AND a.type = 1 ");
        if (checksubmit('submit')) {
            $passiveid = $_GPC['passiveid'];
            if(!empty($passiveid)){
                $passiveid = serialize($passiveid);
            }
            $base['passiveid'] = $passiveid;
            Cloud::wl_syssetting_save($base,'taskcover');
            wl_message('操作完成', 'referer', 'success');
        }
		include wl_template('cloud/taskcover');
	}
	
	function base(){
		global $_W,$_GPC;
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
		$settings = Cloud::wl_syssetting_read('base');
		if (checksubmit('submit')) {
			$base = array(
				'name'=>$_GPC['name'],
				'logo'=>$_GPC['logo'],
				'copyright'=>$_GPC['copyright']
			);
			Cloud::wl_syssetting_save($base, 'base');
			wl_message('更新设置成功！', web_url('cloud/wlsysset/base',['lct'=> $lct]));
		}
		include wl_template('cloud/base');
	}

	function jumpadmin(){
        global $_W,$_GPC;
        if(IMS_FAMILY == 'wl'){
            $lct = 'wl';
        }else{
            $lct = 0;
        }
        $settings = Cloud::wl_syssetting_read('jumpadmin');
        if (checksubmit('submit')) {
            $base = array(
                'targetDmain'=>$_GPC['targetDmain'],
                'endDmain'=>$_GPC['endDmain']
            );
            Cloud::wl_syssetting_save($base, 'jumpadmin');
            wl_message('更新设置成功！', web_url('cloud/wlsysset/jumpadmin',['lct'=> $lct]));
        }
        include wl_template('cloud/jumpadmin');
    }

    function restartqueen(){
        global $_W,$_GPC;
        $queue = new Queue;
        $queue -> deleteLock();
        show_json(1);
    }

}
