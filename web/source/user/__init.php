<?php

defined('IN_IA') or exit('Access Denied');
if (in_array($action, array('registerset', 'agreement'))) {
	define('FRAME', 'site');
}
if (in_array($action, array('display', 'expire', 'fields', 'edit', 'create', 'save', 'get_user_group_detail_info'))) {
	define('FRAME', 'user_manage');
}
if (in_array($action, array('group', 'create-group'))) {
	define('FRAME', 'permission');
}
if (in_array($action, array('profile'))) {
	define('FRAME', 'myself');
}