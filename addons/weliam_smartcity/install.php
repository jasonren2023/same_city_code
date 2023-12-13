<?php
global $_W;
pdo_query("CREATE TABLE IF NOT EXISTS " . tablename('wlmerchant_setting') . " (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `plugin` varchar(200) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
?>