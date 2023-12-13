<?php

require './framework/bootstrap.inc.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    header('Location: ' . $_W['siteroot'] . 'web/index.php?' . $_SERVER['QUERY_STRING']);
} else {
    header('Location:'. $_W['siteroot'] . 'web/home.php');
}