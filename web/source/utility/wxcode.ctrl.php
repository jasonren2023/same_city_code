<?php

defined('IN_IA') or exit('Access Denied');
$dos = array('verifycode', 'image');
$do = in_array($do, $dos) ? $do : 'verifycode';

$_W['uniacid'] = intval($_GPC['uniacid']);
if ('verifycode' == $do) {
	load()->func('communication');
		$username = trim($_GPC['username']);
	$response = ihttp_get("https://mp.weixin.qq.com/cgi-bin/verifycode?username={$username}&r=" . TIMESTAMP);
	if (!is_error($response)) {
		isetcookie('code_cookie', $response['headers']['Set-Cookie']);
		header('Content-type: image/jpg');
		echo $response['content'];
		exit();
	}
} elseif ('image' == $do) {
	load()->func('communication');
		$image = trim($_GPC['attach']);
	if (empty($image)) {
		exit();
	}
		if (!starts_with($image, array('http://mmbiz.qpic.cn/', 'https://mmbiz.qpic.cn/'))) {
		exit();
	}
	$content = ihttp_request($image, '', array('CURLOPT_REFERER' => 'http://www.qq.com'));
	header('Content-Type:image/jpg');
	echo $content['content'];
	exit();
}