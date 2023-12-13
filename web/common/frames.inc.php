<?php

defined('IN_IA') or exit('Access Denied');
global $_W;

$w7_system_menu = array();

$w7_system_menu['account_manage'] = array(
	'title' => '平台',
	'icon' => 'wi wi-platform-manage',
	'dimension' => 2,
	'url' => url('account/manage'),
	'section' => array(
		'account_manage' => array(
			'title' => '平台管理',
			'menu' => array(
				'account_manage_display' => array(
					'title' => '平台列表',
					'url' => url('account/manage'),
					'permission_name' => 'account_manage_display',
					'sub_permission' => array(
						array(
							'title' => '帐号停用',
							'permission_name' => 'account_manage_stop',
						),
					),
				),
			),
		),
	),
    'founder' => true,
);

$w7_system_menu['user_manage'] = array(
	'title' => '用户',
	'icon' => 'wi wi-user-group',
	'dimension' => 2,
	'url' => url('user/display'),
	'section' => array(
		'user_manage' => array(
			'title' => '用户管理',
			'menu' => array(
				'user_manage_display' => array(
					'title' => '普通用户',
					'url' => url('user/display'),
					'permission_name' => 'user_manage_display',
					'sub_permission' => array(),
				),
				'user_manage_recycle' => array(
					'title' => '回收站',
					'url' => url('user/display', array('type' => 'recycle')),
					'permission_name' => 'user_manage_recycle',
					'sub_permission' => array(),
				),
			),
		),
	),
    'founder' => true,
);

$w7_system_menu['site'] = array(
	'title' => '设置',
    'menu-title' => '系统设置',
	'icon' => 'wi wi-system-info',
	'dimension' => 3,
	'url' => url('system/site'),
	'section' => array(
		'setting' => array(
			'title' => '设置',
			'menu' => array(
				'system_setting_site' => array(
					'title' => '站点设置',
					'url' => url('system/site'),
					'permission_name' => 'system_setting_site',
				),
				'system_setting_attachment' => array(
					'title' => '附件设置',
					'url' => url('system/attachment/remote'),
					'permission_name' => 'system_setting_attachment',
				),
			),
		),
		'utility' => array(
			'title' => '常用工具',
			'menu' => array(
				'system_utility_optimize' => array(
					'title' => '性能优化',
					'url' => url('system/optimize'),
					'permission_name' => 'system_utility_optimize',
				),
				'system_utility_check' => array(
					'title' => '系统检测',
					'url' => url('system/check'),
					'permission_name' => 'system_utility_check',
				),
			),
		),
	),
	'founder' => true,
);

$w7_system_menu['cloud'] = array(
    'title' => '云服务',
    'menu-title' => '云服务中心',
    'icon' => 'wi wi-system-site',
    'dimension' => 3,
    'url' => url('cloud/auth/info'),
    'section' => array(
        'cloud_auth' => array(
            'title' => '云服务',
            'menu' => array(
                'cloud_auth_info' => array(
                    'title' => '系统授权',
                    'url' => url('cloud/auth/auth_info'),
                    'permission_name' => 'cloud_auth_info',
                ),
                'cloud_auth_upgrade' => array(
                    'title' => '系统升级',
                    'url' => url('cloud/auth/auth_upgrade'),
                    'permission_name' => 'cloud_auth_upgrade',
                ),
                'cloud_auth_upgrade_log' => array(
                    'title' => '更新日志',
                    'url' => url('cloud/auth/auth_upgrade_log'),
                    'permission_name' => 'cloud_auth_upgrade_log',
                ),
            ),
        ),
        'cloud_app' => array(
            'title' => '应用管理',
            'menu' => array(
                'cloud_app_info' => array(
                    'title' => '应用信息',
                    'url' => url('cloud/auth/app_info'),
                    'permission_name' => 'cloud_app_info',
                ),
                'cloud_app_pem' => array(
                    'title' => '应用权限',
                    'url' => url('cloud/auth/app_pem'),
                    'permission_name' => 'cloud_app_pem',
                ),
            ),
        ),
        'cloud_db' => array(
            'title' => '数据管理',
            'menu' => array(
                'cloud_dbm' => array(
                    'title' => '数据管理',
                    'url' => url('cloud/auth/db_m'),
                    'permission_name' => 'cloud_dbm',
                ),
                'cloud_db_sql' => array(
                    'title' => '运行SQL',
                    'url' => url('cloud/auth/db_sql'),
                    'permission_name' => 'cloud_db_sql',
                ),
            ),
        ),
        'cloud_set' => array(
            'title' => '系统设置',
            'menu' => array(
                'cloud_set_info' => array(
                    'title' => '系统信息',
                    'url' => url('cloud/auth/set_info'),
                    'permission_name' => 'cloud_set_info',
                ),
                'cloud_set_que' => array(
                    'title' => '计划任务',
                    'url' => url('cloud/auth/set_que'),
                    'permission_name' => 'cloud_set_que',
                ),
                'cloud_set_url' => array(
                    'title' => '跳转域名',
                    'url' => url('cloud/auth/set_url'),
                    'permission_name' => 'cloud_set_url',
                ),
            ),
        ),
    ),
    'founder' => true,
);

return $w7_system_menu;
