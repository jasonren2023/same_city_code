<?php
defined('IN_IA') or exit('Access Denied');
require_once PATH_CORE . "library/querylist/QueryList.class.php";
use QL\QueryList;

class GatherArticle {

    function get_caiji($url) {
        global $_W;
        load()->func('file');
        load()->func('communication');
        //采集规则
        $html = ihttp_request($url, '', array('CURLOPT_REFERER' => 'http://www.qq.com'));
        // $html = file_get_contents($url);
        $html = str_replace("<!--headTrap<body></body><head></head><html></html>-->", "", $html['content']);
        $reg = array(
            //采集文章标题
            'title'    => array('#activity-name', 'text'),
            //采集文章发布日期,这里用到了QueryList的过滤功能，过滤掉span标签和a标签
            //采集文章正文内容,利用过滤功能去掉文章中的超链接，但保留超链接的文字，并去掉版权、JS代码等无用信息
            'content'  => array('#js_content', 'html'),
            'nickname' => array('.profile_nickname', 'text'),
            'video'    => array('.video_iframe', 'data-src', '', function ($video) {
                $video = explode('vid=', $video);
                $video = explode('&', $video['1']);
                return $video['0'];
            }),
            'logo'     => array(':contains(msg_cdn_url)', 'text', '', function ($logo) {
                $logo = explode('var msg_cdn_url = "', $logo);
                $logo = explode('";', $logo['1']);
                $logo = 'web/index.php?c=utility&a=wxcode&do=image&attach=' . $logo['0'];
                return $logo;
            }),
            'desc'     => array(':contains(msg_cdn_url)', 'text', '', function ($desc) {
                $desc = explode('var msg_desc = "', $desc);
                $desc = explode('";', $desc['1']);
                return $desc['0'];
            }),
        );

        $rang = 'body';
        $ql = QueryList::Query($html, $reg, $rang, 'UTF-8');

        $con = $ql->getData();
        $contents = $con['0']['content'];
        //如果出现中文乱码使用下面代码
        //$getcontent = iconv("gb2312", "utf-8",$contents);
        preg_match_all('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $contents, $match);
        $pic1 = $match['0'];
        $img = $match['2'];

        foreach ($pic1 as $key => $value) {
            $url = $value;
            $path = $_W['siteroot'] . 'web/index.php?c=utility&a=wxcode&do=image&attach=' . $img[$key];

            // $imgarr = getimagesize($path);
            // if ($imgarr['0'] > 300 && $imgarr['1'] > 10) {
            //     $fileurl = '<img src="' . tomedia($path) . '" width="100%"/>';
            // } else {
            //     $fileurl = '<img src="' . tomedia($path) . '" width="' . $imgarr[0] . '" />';
            // }
            // if ($imgarr['0'] > 300 && $imgarr['1'] > 200) {
            //     if ($key < 4) {
            //         $pic .= tomedia($path) . ',';
            //     }
            // }
            $fileurl = '<img src="' . tomedia($path) . '" width="' . $imgarr[0] . '" />';
            $pic .= tomedia($path) . ',';
            $contents = str_replace("{$url}", $fileurl, $contents);
        }
        preg_match_all('/<\s*iframe\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $contents, $match);
        $fs = $match['0'];
        $fskey = $match['2'];
        foreach ($fs as $key => $value) {
            $fileurl = "<iframe border='0' width='100%' height='250' src='http://v.qq.com/iframe/player.html?vid={$con['0']['video']}&tiny=0&auto=0' allowfullscreen></iframe>";
            $contents = str_replace("$value", $fileurl, $contents);
        }
        $pic = rtrim($pic, ",");
        $pic = explode(",", $pic);
        if (count($pic) == 3) {
            $pic = iserializer($pic);
        } else {
            $pic = null;
        }
        $data = array(
            'title'    => $con['0']['title'],
            'contents' => $contents,
            'desc'     => $con['0']['desc'],
            'pic'      => $pic,
            'vid'      => $con['0']['video'],
            'thumb'    => $_W['siteroot'] . $con['0']['logo'],
            'nickname' => $con['0']['nickname']
        );

        return $data;
    }
}