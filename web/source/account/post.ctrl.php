<?php

defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('cloud');
load()->model('cache');
load()->classs('weixin.platform');
load()->model('utility');
load()->func('file');
$uniacid = intval($_GPC['uniacid']);
if (empty($uniacid)) {
	$url = url('account/manage', array('account_type' => ACCOUNT_TYPE));
	if ($_W['isajax']) {
		iajax(-1, '请选择要编辑的' . ACCOUNT_TYPE_NAME);
	}
	itoast('请选择要编辑的' . ACCOUNT_TYPE_NAME, $url, 'error');
}
$account = uni_fetch($uniacid);
if (!$account) {
	if ($_W['isajax']) {
		iajax(-1, '无效的uniacid');
	}
	itoast('无效的uniacid');
}
$acid = $account['acid']; 
$state = permission_account_user_role($_W['uid'], $uniacid);
$dos = array('base', 'sms', 'modules_tpl', 'edit_modules_tpl', 'operators');
$role_permission = in_array($state, array(ACCOUNT_MANAGE_NAME_FOUNDER, ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_VICE_FOUNDER));
if ($role_permission || $_W['isajax']) {
	$do = in_array($do, $dos) ? $do : 'base';
} elseif (ACCOUNT_MANAGE_NAME_MANAGER == $state) {
	if (ACCOUNT_TYPE == ACCOUNT_TYPE_APP_NORMAL || ACCOUNT_TYPE == ACCOUNT_TYPE_APP_AUTH) {
		header('Location: ' . url('wxapp/manage/display', array('uniacid' => $uniacid)));
		exit;
	} else {
		$do = in_array($do, $dos) ? $do : 'modules_tpl';
	}
} else {
	itoast('您是该公众号的操作员，无权限操作！', url('account/manage'), 'error');
}

$_W['breadcrumb'] = $account['name'];
if ('base' == $do) {
	if (!$role_permission && !$_W['isajax']) {
		itoast('无权限操作！', url('account/post/modules_tpl', array('uniacid' => $uniacid)), 'error');
	}
	if ($_W['ispost'] && $_W['isajax']) {
		if (!empty($_GPC['type'])) {
			$type = safe_gpc_string($_GPC['type']);
		} else {
			iajax(-1, '参数错误！', '');
		}
		$request_data = safe_gpc_string(trim($_GPC['request_data']));
		switch ($type) {
			case 'qrcodeimgsrc':
			case 'headimgsrc':
				$imgsrc = safe_gpc_path($request_data);
				$image_type = array(
					'qrcodeimgsrc' => 'qrcode',
					'headimgsrc' => 'logo',
				);
				if (file_is_image($imgsrc)) {
					$result = table('uni_account')->where('uniacid', $uniacid)->fill($image_type[$type], $imgsrc)->save();
				} else {
					$result = '';
				}
				break;
			case 'name':
				$uni_account = pdo_update('uni_account', array('name' => $request_data), array('uniacid' => $uniacid));
				$account_wechats = pdo_update($account->tablename, array('name' => $request_data), array('uniacid' => $uniacid));
				$result = ($uni_account && $account_wechats) ? true : false;
				break;
			case 'account':
				$data = array('account' => $request_data); break;
			case 'original':
				$data = array('original' => $request_data); break;
			case 'level':
				$data = array('level' => intval($_GPC['request_data'])); break;
			case 'appid':
				if (!empty($request_data)) {
					$hasAppid = uni_get_account_by_appid($request_data, $account['type'], $account['uniacid']);
					if (!empty($hasAppid)) {
						iajax(1, "{$hasAppid['key_title']}已被{$hasAppid['type_title']}[ {$hasAppid['name']} ]使用");
					}
				}
				$data = array('appid' => $request_data); break;
			case 'key':
				if (!empty($request_data) && !in_array($account['type_sign'], array(BAIDUAPP_TYPE_SIGN, TOUTIAOAPP_TYPE_SIGN))) {
					$hasAppid = uni_get_account_by_appid($request_data, $account['type'], $account['uniacid']);
					if (!empty($hasAppid)) {
						iajax(1, "{$hasAppid['key_title']}已被{$hasAppid['type_title']}[ {$hasAppid['name']} ]使用");
					}
				}
				if ($account['key'] == $request_data) {
					iajax(0, '修改成功！', referer());
				}
				$data = array('key' => $request_data); break;
			case 'secret':
				if ($account['secret'] == $request_data) {
					iajax(0, '修改成功！', referer());
				}
				$data = array('secret' => $request_data); break;
			case 'token':
				$oauth = (array) uni_setting_load(array('oauth'), $uniacid);
				if ($oauth['oauth'] == $acid && 4 != $account['level']) {
					$acid = pdo_fetchcolumn('SELECT acid FROM ' . tablename('account_wechats') . " WHERE uniacid = :uniacid AND level = 4 AND secret != '' AND `key` != ''", array(':uniacid' => $uniacid));
					pdo_update('uni_settings', array('oauth' => iserializer(array('account' => $acid, 'host' => $oauth['oauth']['host']))), array('uniacid' => $uniacid));
				}
				$data = array('token' => $request_data);
				break;
			case 'encodingaeskey':
				$oauth = (array) uni_setting_load(array('oauth'), $uniacid);
				if ($oauth['oauth'] == $acid && 4 != $account['level']) {
					$acid = pdo_fetchcolumn('SELECT acid FROM ' . tablename('account_wechats') . " WHERE uniacid = :uniacid AND level = 4 AND secret != '' AND `key` != ''", array(':uniacid' => $uniacid));
					pdo_update('uni_settings', array('oauth' => iserializer(array('account' => $acid, 'host' => $oauth['oauth']['host']))), array('uniacid' => $uniacid));
				}
				$data = array('encodingaeskey' => $request_data);
				break;
			case 'jointype':
				if (in_array($account['type'], array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_APP_NORMAL))) {
					$result = true;
				} else {
					$change_type = array(
						'type' => 'account' == $account->typeSign ? ACCOUNT_TYPE_OFFCIAL_NORMAL : ACCOUNT_TYPE_APP_NORMAL,
					);
					$update_type = pdo_update('account', $change_type, array('uniacid' => $uniacid));
					$result = $update_type ? true : false;
				}
				break;
			case 'highest_visit':
				if (user_is_vice_founder() || empty($_W['isfounder'])) {
					iajax(1, '只有创始人可以修改！');
				}
				$statistics_setting = (array) uni_setting_load(array('statistics'), $uniacid);
				if (!empty($statistics_setting['statistics'])) {
					$highest_visit = $statistics_setting['statistics'];
					$highest_visit['founder'] = intval($_GPC['request_data']);
				} else {
					$highest_visit = array('founder' => intval($_GPC['request_data']));
				}
				$result = pdo_update('uni_settings', array('statistics' => iserializer($highest_visit)), array('uniacid' => $uniacid));
				break;
			case 'endtime':
				$endtime = strtotime($_GPC['endtime']);
				if ($endtime <= 0) {
					iajax(1, '参数错误！');
				}
				
				if ($_W['isfounder']) {
					$endtime = 1 != $_GPC['endtype'] ? $endtime : 0;
					
				} else {
					$owner_id = pdo_getcolumn('uni_account_users', array('uniacid' => $uniacid, 'role' => 'owner'), 'uid');
					$user_endtime = pdo_getcolumn('users', array('uid' => $owner_id), 'endtime');
					
					if ($user_endtime != USER_ENDTIME_GROUP_UNLIMIT_TYPE && $user_endtime != USER_ENDTIME_GROUP_EMPTY_TYPE && $user_endtime < $endtime && !empty($user_endtime)) {
						iajax(1, '设置到期日期不能超过' . user_end_time($owner_id));
					}
				}
				$result = pdo_update('account', array('endtime' => $endtime), array('uniacid' => $uniacid));
				break;
			case 'attachment_limit':
				if (user_is_vice_founder() || empty($_W['isfounder'])) {
					iajax(1, '只有创始人可以修改！');
				}
				$has_uniacid = pdo_getcolumn('uni_settings', array('uniacid' => $uniacid), 'uniacid');
				if ($_GPC['request_data'] < 0) {
					$attachment_limit = -1;
				} else {
					$attachment_limit = intval($_GPC['request_data']);
				}
				if (empty($has_uniacid)) {
					$result = pdo_insert('uni_settings', array('attachment_limit' => $attachment_limit, 'uniacid' => $uniacid));
				} else {
					$result = pdo_update('uni_settings', array('attachment_limit' => $attachment_limit), array('uniacid' => $uniacid));
				}
				break;
		}
		if (!in_array($type, array('qrcodeimgsrc', 'headimgsrc', 'name', 'endtime', 'jointype', 'highest_visit', 'attachment_limit'))) {
			$result = pdo_update($account->tablename, $data, array('uniacid' => $uniacid));
		}
		if ($result) {
			cache_delete(cache_system_key('uniaccount', array('uniacid' => $uniacid)));
			cache_delete(cache_system_key('accesstoken', array('uniacid' => $uniacid)));
			cache_delete(cache_system_key('statistics', array('uniacid' => $uniacid)));
			iajax(0, '修改成功！', referer());
		} else {
			iajax(1, '修改失败！', '');
		}
	}

	if(!$_W['isadmin']){
		$owner_id = pdo_getcolumn('uni_account_users', array('uniacid' => $uniacid, 'role' => 'owner'), 'uid');
		$user_endtime = user_end_time($owner_id);
	}
	if ($_W['setting']['platform']['authstate']) {
		$account_platform = new WeixinPlatform();
		$preauthcode = $account_platform->getPreauthCode();
		if (is_error($preauthcode)) {
			if (40013 == $preauthcode['errno']) {
				$url = '微信开放平台 appid 链接不成功，请检查修改后再试' . "<a href='" . url('system/platform') . "' style='color:#3296fa'>去设置</a>";
			} else {
				$url = "{$preauthcode['message']}";
			}

			$authurl = array(
				'errno' => 1,
				'url' => $url,
			);
		} else {
			$authurl_type = $account['type'] == 4 ? ACCOUNT_PLATFORM_API_LOGIN_WXAPP : ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT;
			$callurl = $authurl_type == ACCOUNT_PLATFORM_API_LOGIN_WXAPP ? 'index.php?c=wxapp&a=auth&do=forward' : 'index.php?c=account&a=auth&do=forward';
			$authurl = array(
				'errno' => 0,
				'url' => sprintf(ACCOUNT_PLATFORM_API_LOGIN, $account_platform->appid, $preauthcode, urlencode($GLOBALS['_W']['siteroot'] . $callurl), $authurl_type),
			);
		}
	}
	$account['start'] = date('Y-m-d', $account['starttime']);
	$account['end'] = in_array($account['endtime'], array(USER_ENDTIME_GROUP_EMPTY_TYPE, USER_ENDTIME_GROUP_UNLIMIT_TYPE)) ? '永久' : date('Y-m-d', $account['endtime']);
	$account['endtype'] = (in_array($account['endtime'], array(USER_ENDTIME_GROUP_EMPTY_TYPE, USER_ENDTIME_GROUP_UNLIMIT_TYPE)) || 	$account['endtime'] == 0) ? 1 : 2;
	$uni_setting = (array) uni_setting_load(array('statistics', 'attachment_limit', 'attachment_size'), $uniacid);
	$account['highest_visit'] = empty($uni_setting['statistics']['founder']) ? 0 : $uni_setting['statistics']['founder'];

	$attachment_limit = intval($uni_setting['attachment_limit']);
	if (0 == $attachment_limit) {
		$upload = setting_load('upload');
		$attachment_limit = empty($upload['upload']['attachment_limit']) ? 0 : intval($upload['upload']['attachment_limit']);
	}
	if ($attachment_limit <= 0) {
		$attachment_limit = -1;
	}
	$account['attachment_limit'] = intval($attachment_limit);
	$account['switchurl_full'] = $_W['siteroot'] . 'web/' . ltrim($account['switchurl'], './');
	$account['endtime'] = strlen($account['endtime']) == 10 ? $account['endtime'] : time();
	$account['headimgsrc'] = $account['logo'];
	$account['qrcodeimgsrc'] = $account['qrcode'];
	$account['switchurl_full'] = $_W['siteroot'] . 'web/' . ltrim($account['switchurl'], './');
	$account['siteurl'] = $account['type_sign'] != WXAPP_TYPE_SIGN ? $_W['siteroot'] : str_replace('http://', 'https://', $_W['siteroot']);
	$account['service_url'] = $account['siteurl'] . 'api.php?id=' . $account['acid'];
	$account['type_class'] = $account_all_type_sign[$account['type_sign']]['icon'];
	$account['owner_endtime'] = $user_endtime;
	$account['support_version'] = $account->supportVersion;
	$uniaccount = array();
	$uniaccount = pdo_get('uni_account', array('uniacid' => $uniacid));
	
	if ($_W['isajax']) {
		iajax(0, $account);
	} else {
		template('account/manage-base');
	}
}
