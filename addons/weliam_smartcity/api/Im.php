<?php
/**
 * 通讯系统
 */
defined('IN_IA') or exit('Access Denied');

class ImModuleUniapp extends Uniapp {
    //TODO：由于多功能通信大部分用户配置失败  如果删除多功能通信后需要保留发送图片和视频等功能 需要重新优化这里的通讯功能  目前普通通信仅支持发送文本信息
    /**
     * Comment: 通讯信息列表(用户)
     * Author: zzw
     * Date: 2019/8/27 9:04
     */
    public function infoList(){
        global $_W,$_GPC;
        #1、参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $plugin    = $_GPC['plugin'] ? : '';
        #2、获取列表信息   （1=用户；2=商户）
        $data = Im::myList($_W['mid'],1,$page,$pageIndex,$plugin);


        $this->renderSuccess('通讯列表',$data);
    }
    /**
     * Comment: 获取通讯记录
     * Author: zzw
     * Date: 2019/8/26 17:42
     */
    public function get(){
        global $_W,$_GPC;
        #1、参数接收
        $page           = $_GPC['page'] ? : 1;
        $pageIndex      = $_GPC['page_index'] ? : 10;
        $thisId         = $_GPC['id'] ? : $_W['mid'];//当前使用者的id:用户id（默认）|商户id
        $thisType       = $_GPC['type'] ? : 1;// 当前使用者的类型；1=用户（默认）；2=商户
        $otherPartyType = $_GPC['other_party_type'] ? : 1;//通讯对象类型；1=用户；2=商户
        $otherPartyId = $_GPC['other_party_id'] or $this->renderError('缺少参数：other_party_id');//通讯对象id
        #1、类型一致，id一致 则是给自己发送消息
        if($thisId == $otherPartyId && $thisType == $otherPartyType) $this->renderError("不能给自己发送信息哦",['url'=>h5_url('pages/mainPages/userCenter/userCenter')]);
        $sendInfo['send_id']      = $thisId;//发送方id
        $sendInfo['send_type']    = $thisType;//发送方类型（1=用户；2=商户）
        $sendInfo['receive_id']   = $otherPartyId;//接收人id
        $sendInfo['receive_type'] = $otherPartyType;//接收人类型（1=用户；2=商户）

        $data = Im::imRecord($page,$pageIndex,$sendInfo);
        #3、修改信息为已读
        //if(is_array($data['list']) && count($data['list']) > 0) Im::is_read(array_column($data['list'],'id'),$thisId);
        //修改所有对方发送的通讯信息为已读
        $where = " send_id = {$otherPartyId} AND send_type = {$otherPartyType} AND receive_id = {$thisId} AND receive_type = {$thisType} AND is_read = 0 ";
        $sql = " UPDATE ".tablename(PDO_NAME."im")." SET is_read = 1 WHERE {$where}  ";
        pdo_query($sql);
        #4、信息排序
        $sortArr = array_column($data['list'], 'create_time');
        array_multisort($sortArr, SORT_ASC, $data['list']);
        #4、获取聊天对象的昵称  1=用户；2=商户
        if($otherPartyType == 1){
            $data['receive_name'] = pdo_getcolumn(PDO_NAME."member",['id'=>$otherPartyId],'nickname');
        }else{
            $data['receive_name'] = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$otherPartyId],'storename');
        }

        $this->renderSuccess('通讯记录',$data);
    }
    /**
     * Comment: 发送通讯信息
     * Author: zzw
     * Date: 2019/8/26 15:48
     */
    public function send (){
        global $_W , $_GPC;
        //判断是否绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('private',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        #1、参数接收
        $sendId   = $_GPC['send_id'] ? : $_W['mid'];//发送方id
        $sendType = $_GPC['send_type'] ? : 1;//发送方类型（1=用户；2=商户）
        $type     = $_GPC['type'] ? : 0;//内容类型（0=文本信息(默认)，1=图片地址，2=视频信息 3=名片 4=简历）
        $plugin   = $_GPC['plugin'] ? : '';//
        !empty($_GPC['receive_id']) ? $receiveId = $_GPC['receive_id'] : $this->renderError('缺少参数：receive_id');//接收人id
        !empty($_GPC['receive_type']) ? $receiveType = $_GPC['receive_type'] : $this->renderError('缺少参数：receive_type');//接收人类型（1=用户；2=商户）
        !empty($_GPC['content']) ? $content = $_GPC['content'] : $this->renderError('缺少参数：content');//发送内容
        if($sendId == $receiveId && $sendType == $receiveType) $this->renderError("不能给自己发送信息哦");
        #2、信息拼装
        $data['uniacid']      = $_W['uniacid'];//
        $data['send_id']      = $sendId;//发送方id
        $data['send_type']    = $sendType;//发送方类型（1=用户；2=商户）
        $data['receive_id']   = $receiveId;//接收人id
        $data['receive_type'] = $receiveType;//接收人类型（1=用户；2=商户）
        $data['create_time']  = time();//发送时间（建立时间）
        $data['type']         = $type;//内容类型（0=文本信息(默认)，1=图片地址，2=视频信息）
        $data['plugin']       = $plugin;//通讯插件
        if(empty($data['type'])){
            $data['content']      =  htmlspecialchars_decode($content);//发送内容
        }else{
            $data['content']      = $content;//发送内容
        }

        #2、信息记录
        $res = Im::insert($data);
        if ($res) $this->renderSuccess('发送成功');
        else $this->renderError('发送失败');
    }
    /**
     * Comment: 通讯设置
     * Author: zzw
     * Date: 2021/3/8 11:57
     */
    public function getSetInfo(){
        global $_W,$_GPC;
        //获取基本设置
        //$set =  Setting::wlsetting_read('im_set');
        //判断用户是否存在简历信息
        $isResume = pdo_get(PDO_NAME."recruit_resume",['mid' => $_W['mid']]);
        //信息拼接
        $data = [
            'type'       => 1,
            'port'       => '',
            'mid'        => $_W['mid'],
            'is_card'    => p('citycard') ? 1 : 0,//是否存在名片插件  0=不存在，1=存在
            'is_recruit' => p('recruit') ? 1 : 0,//是否存在求职招聘插件   0=不存在，1=存在
            'is_resume'  => $isResume ? 1 : 0,//是否存在简历信息  0=不存在，1=存在
            'resume_id'  => $isResume['id'],//简历id
        ];

        $this->renderSuccess('通讯设置信息',$data);
    }

    /**
     * Comment: 自定义表单信息
     * Author: wlf
     * Date: 2022/03/30 15:34
     */
    public function getDiyformInfo(){
        global $_W,$_GPC;
        $id = $_GPC['formid'];
        if(empty($id)){
            $this->renderError('无id参数');
        }
        $forminfo = pdo_get('wlmerchant_diyform',array('id' => $id));
        $moinfo = json_decode(base64_decode($forminfo['info']) , true);
        $list = $moinfo['list'];
        $list = array_values($list);

        $data['list'] = $list;
        $data['title'] = $forminfo['title'];
        //额外内容
        $nowlist = pdo_get('wlmerchant_diyform_list',array('mid' => $_W['mid'],'formid' => $id));

        $data['list'] = Im::diyFormInfo($data['list'],$nowlist);

        $this->renderSuccess('自定义表单信息',$data);
    }

    /**
     * Comment: 自定义表单提交
     * Author: wlf
     * Date: 2022/03/31 09:20
     */
    public function saveDiyformInfo(){
        global $_W,$_GPC;
        $infoid = $_GPC['formid'];
        $id = $_GPC['diyformid'];
        if(empty($id)){
            $this->renderError('无id参数');
        }
        $info = [];
        $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
        $diyForm  = pdo_get(PDO_NAME."diyform",['id'=>$id]);

        $diyFormSet  = array_values(json_decode(base64_decode($diyForm['info']), true)['list']);//页面的配置信息
        foreach($diyFormInfo as $formKey => &$formVal){
            $formVal['att_show'] = $diyFormSet[$formKey]['data']['att_show'];
        }
        $info['listinfo'] = serialize($diyFormInfo);
        $info['dotime'] = time();
        if($infoid > 0){
            $res = pdo_update('wlmerchant_diyform_list',$info,array('id' => $infoid));
        }else{
            $info['uniacid'] = $_W['uniacid'];
            $info['aid'] = $diyForm['aid'];
            $info['mid'] = $_W['mid'];
            $info['formid'] = $id;
            $res =  pdo_insert(PDO_NAME.'diyform_list',$info);
        }
        if($res){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

	/**
     * Comment: 领取红包
     * Author: wlf
     * Date: 2022/11/12 21:35
     */
     
	public function getStoreRedPack(){
	   global $_W,$_GPC;
	   $id = $_GPC['id']; //活动id
	   $infoid = $_GPC['formid'];  //记录id
       $diyformid = $_GPC['diyformid'];  //表单id
       $sid = $_GPC['sid'];
	   //活动信息
	   $redinfo = pdo_get('wlmerchant_store_redpack',array('id' => $id),array('diypageid','onemoney','fishmoney','fishnum'));
	   $record = pdo_get('wlmerchant_store_redpack_record',array('mid' => $_W['mid'],'redpackid' => $id),array('onemoney','id','onegettime','fishmoney','fishgettime'));
	    //处理表单
	  	$info = [];
	    $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
	    $diyForm  = pdo_get(PDO_NAME."diyform",['id'=>$diyformid]);
	
	    $diyFormSet  = array_values(json_decode(base64_decode($diyForm['info']), true)['list']);//页面的配置信息
	    foreach($diyFormInfo as $formKey => &$formVal){
	        $formVal['att_show'] = $diyFormSet[$formKey]['data']['att_show'];
	    }
	    $info['listinfo'] = serialize($diyFormInfo);
	    $info['dotime'] = time();
	    if($infoid > 0){
	        $res = pdo_update('wlmerchant_diyform_list',$info,array('id' => $infoid));
	    }else{
	        $info['uniacid'] = $_W['uniacid'];
	        $info['aid'] = $diyForm['aid'];
	        $info['mid'] = $_W['mid'];
	        $info['formid'] = $diyformid;
			$info['plugin'] = 'storeRed';
	        $res =  pdo_insert(PDO_NAME.'diyform_list',$info);
			$infoid = pdo_insertid();
			//通知商户
			if($res){
				$first = '用户表单已经提交';
	            $type = '商户红包表单提交';
	            $content = '用户名:['.$_W['wlmember']['nickname'].']';
	            $newStatus = '提交成功';
	            $remark = '点击查看表单详情';
	            $urls = h5_url('pagesA/merchantformlist/merchantformlist' , ['sid' => $sid]);
	            News::noticeShopAdmin($sid,$first,$type,$content,$newStatus,$remark,time(),$urls);
			}
	    } 
		if(empty($record)){
			$record = [
				'uniacid' => $_W['uniacid'],
				'redpackid' => $id,
				'onemoney' => 0,
				'fishmoney' => 0,
				'mid' => $_W['mid'],
				'formlistid' => $infoid,
				'sid' => $sid
			];
			pdo_insert('wlmerchant_store_redpack_record', $record);
			$record['id'] = pdo_insertid();
		}
	    if($res){
	   		//判断是第几次领取
	   		if($record['onemoney'] < 0.01){
	   			//第一次领取 发红包
	   			$cashinfo = [
	   				'order_no' => 'SR'.date("YmdHis").random(4,true), //订单号生成
	   				'source' => $_W['source'],
	   				'actype' => 2,
	   				'prize_number' => $redinfo['onemoney'],
	   				'draw_title' => $redinfo['title'],
	   				'title' => '填表现金红包'
	   			];
	   			Payment::cashRedPack($cashinfo);
				$getres = pdo_update('wlmerchant_store_redpack_record',array('onemoney' => $redinfo['onemoney'],'onegettime' => time()),array('id' => $record['id']));
			
	   		}else if($record['fishmoney']<0.01){
	   			//领取第二次红包
	   			//判断是否够资格领取
	   			$sharenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_store_redpack_share')." WHERE redpackid = {$id} AND invitmid = {$_W['mid']}");
	   			if($sharenum >= $redinfo['fishnum']){
	   				$cashinfo = [
		   				'order_no' => 'SR'.date("YmdHis").random(4,true), //订单号生成
		   				'source' => $_W['source'],
		   				'actype' => 2,
		   				'prize_number' => $redinfo['fishmoney'],
		   				'draw_title' => $redinfo['title'],
		   				'title' => '分享现金红包'
		   			];
		   			Payment::cashRedPack($cashinfo);
					$getres = pdo_update('wlmerchant_store_redpack_record',array('fishmoney' => $redinfo['fishmoney'],'fishgettime' => time()),array('id' => $record['id']));
	   			}else{
	   				$this->renderError('需要邀请'.$redinfo['fishnum'].'人才能领取奖励，您目前邀请'.$sharenum.'人');
	   			}
	   		}
			if($getres > 0){
				$this->renderSuccess('领取成功');
			}else{
				$this->renderError('您已全部领取');
			}
        }else{
            $this->renderError('数据储存失败，请刷新重试');
        }
	}


	/**
     * Comment: 分享进入写记录
     * Author: wlf
     * Date: 2022/11/12 22:41
     */
	public function storeRedShareRecord(){
	   global $_W,$_GPC;
	   $id = $_GPC['id'];
	   $invitmid = $_GPC['head_id'];
	   if($invitmid != $_W['mid']){
		   $flag = pdo_get('wlmerchant_store_redpack_share',array('mid' => $_W['mid'],'invitmid' => $invitmid,'redpackid' => $id),array('id'));
		   if(empty($flag)){
		   		pdo_insert('wlmerchant_store_redpack_share', ['uniacid' => $_W['uniacid'],'mid' => $_W['mid'],'invitmid' => $invitmid,'redpackid' => $id,'createtime' => time()]);
		   }
	   }
	   $this->renderSuccess('okok');
	}
	
	
	/**
     * Comment: 获取商户表单记录
     * Author: wlf
     * Date: 2022/11/14 21:15
     */
     
    public function storeRedFormList(){
	   	global $_W,$_GPC;
	   	$sid = $_GPC['storeid'];
	    $page = $_GPC['page'] ? $_GPC['page'] : 1;
	    $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
	    $pageStart = $page * $pageIndex - $pageIndex;
		$where = " WHERE sid = {$sid}";
		$limit = " LIMIT {$pageStart},{$pageIndex} ";

        $AreaTab = tablename(PDO_NAME . "store_redpack_record");
        $orderBy = " ORDER BY id DESC";
		$list = pdo_fetchall("SELECT formlistid,mid  FROM".$AreaTab . $where .  $orderBy.$limit);
        foreach ($list as &$li){
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','mobile','nickname','encodename'));
            //用户信息
            $li['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $li['mobile'] = $member['mobile'];
            $li['avatar'] = tomedia($member['avatar']);
			//自定义表单信息处理
			$forminfo = pdo_get('wlmerchant_diyform_list',array('id' => $li['formlistid']),array('listinfo'));
			
            $moinfo = unserialize($forminfo['listinfo']);
            foreach($moinfo as &$moinfoItem){
                if($moinfoItem['id'] == 'img'){
                    foreach($moinfoItem['data'] as $imgKey => $imgLink){
                        $moinfoItem['data'][$imgKey] = tomedia($imgLink);
                    }
                }
            }
            $li['moinfo'] = $moinfo;
		}
		
		$allnum = count(pdo_fetchall("SELECT id FROM " . $AreaTab . $where));
        $allpage = ceil($allnum/10);
        $data = [
            'total' => $allpage,
            'list' => $list
        ];
        $this->renderSuccess('商户表单',$data);
	}

}



