<?php

defined('IN_IA') or exit('Access Denied');
load()->model('system');

if ('check_fpm' == $do) {
	$result = fastcgi_finish_request();
	if (is_error($result)) {
		$message = array(
			'status' => $result['errno'],
			'message' => $result['message']
		);
		iajax(0, $message);
	}
	exit();
}

$system_check_items = system_check_items();
if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
	unset($system_check_items['mcrypt']);
}

foreach ($system_check_items as $check_item_name => &$check_item) {
	$check_item['check_result'] = $check_item['operate']($check_item_name);
}

$check_num = count($system_check_items);
$check_wrong_num = 0;
foreach ($system_check_items as $check_key => $check_val) {
	if (false === $check_val['check_result']) {
		$check_wrong_num += 1;
	}
}

cache_write(cache_system_key('system_check'), array('check_items' => $system_check_items, 'check_num' => $check_num, 'check_wrong_num' => $check_wrong_num));
if ($_W['isw7_request']) {
	$message = array(
		'check_num' => $check_num,
		'check_wrong_num' => $check_wrong_num,
		'system_check_items' => $system_check_items
	);
	iajax(0, $message);
}
template('system/check');