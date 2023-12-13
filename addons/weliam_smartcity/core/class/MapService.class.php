<?php
defined('IN_IA') or exit('Access Denied');

class MapService {

    /**
     * 获取腾讯地图KEY
     * @return string
     */
    private static function get_key() {
        global $_W;
        return empty($_W['wlsetting']['api']['txmapkey']) ? '4PUBZ-I2QCU-5OHV7-2JX2J-4JERZ-UTBQG' : $_W['wlsetting']['api']['txmapkey'];
    }

    /**
     * 统一请求函数
     * @return JSON
     */
    private static function get_content($apiurl) {
        $result = ihttp_get($apiurl);
        if ($result['code'] != 200) {
            return error(-1, $result['content']);
        }
        $content = @json_decode($result['content'], true);
        if (!is_array($content)) {
            return error(-1, $result['content']);
        }
        if ($content['status'] != 0) {
            return error($content['status'], $content['message']);
        }
        return $content;
    }

    /**
     * 逆地址解析(坐标位置描述)https://lbs.qq.com/webservice_v1/guide-gcoder.html
     * @param $location  位置坐标，格式：location=lat<纬度>,lng<经度>
     * @param $get_poi  是否返回周边POI列表：1.返回；0不返回(默认)
     * @return JSON
     */
    static function guide_gcoder($location, $get_poi = 0) {
        global $_W;
        //检查锁
        if(lockTool($_W['mid'],'location')){
            return 0;
        }
        if (empty($location)) {
            return error(1, '位置坐标不得为空');
        }
        $apiurl = 'https://apis.map.qq.com/ws/geocoder/v1/?location=' . $location . '&key=' . self::get_key() . '&get_poi=' . $get_poi;
        return self::get_content($apiurl);
    }

    /**
     * 地址搜索https://lbs.qq.com/webservice_v1/guide-search.html
     * @param $keyword  POI搜索关键字，用于全文检索字段
     * @param $boundary  搜索地理范围
     * @return JSON
     */
    static function guide_search($keyword, $boundary) {
        if (empty($keyword)) {
            return error(1, '搜索关键字不得为空');
        }
        if (empty($boundary)) {
            return error(1, '搜索地理范围不得为空');
        }
        $apiurl = 'https://apis.map.qq.com/ws/place/v1/search?keyword=' . urlencode($keyword) . '&key=' . self::get_key() . '&boundary=' . $boundary;
        return self::get_content($apiurl);
    }

    /**
     * IP定位https://lbs.qq.com/webservice_v1/guide-ip.html
     * @param $ip  IP地址，缺省时会使用请求端的IP
     * @return JSON
     */
    static function guide_ip($ip) {
        if (empty($ip)) {
            return error(1, 'IP地址不得为空');
        }
        $apiurl = 'https://apis.map.qq.com/ws/location/v1/ip?ip=' . $ip . '&key=' . self::get_key();
        return self::get_content($apiurl);
    }
}