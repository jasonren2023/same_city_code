<?php
defined('IN_IA') or exit('Access Denied');

class Tools {

    static function getPosterTemp() {
        $templist = array();
        for ($i = 1; $i < 12; $i++) {
            $templist[$i] = array('bg' => URL_APP_RESOURCE . '/image/poster/' . 'bg' . $i . '.jpg', 'nail' => URL_APP_RESOURCE . '/image/poster/' . 's_bg' . $i . '.jpg');
        }
        return $templist;
    }

    static function getRealData($data) {
        $data['left'] = intval(str_replace('px', '', $data['left'])) * 2;
        $data['top'] = intval(str_replace('px', '', $data['top'])) * 2;
        $data['width'] = intval(str_replace('px', '', $data['width'])) * 2;
        $data['height'] = intval(str_replace('px', '', $data['height'])) * 2;
        $data['size'] = intval(str_replace('px', '', $data['size'])) * 2;
        $data['src'] = tomedia($data['src']);
        return $data;
    }

    /**
     * 图片圆角
     * @param bool $target
     * @param bool $circle
     * @return resource
     */
    static function imageRadius($target = false, $circle = false) {
        /*        //1、创建画布
                $im = imagecreatetruecolor(300,200);//新建一个真彩色图像，默认背景是黑色，返回图像标识符。另外还有一个函数 imagecreate 已经不推荐使用。
                //2、绘制所需要的图像
                $red = imagecolorallocate($im,255,0,0);//创建一个颜色，以供使用
                imageellipse($im,30,30,40,40,$red);//画一个圆。参数说明：30，30为圆形的中心坐标；40，40为宽和高，不一样时为椭圆；$red为圆形的颜色（框颜色）
                //3、输出图像
                header("content-type: image/png");
                imagepng($im);//输出到页面。如果有第二个参数[,$filename],则表示保存图像
                //4、销毁图像，释放内存
                imagedestroy($im);die;*/
        $w = imagesx($target);
        $h = imagesy($target);
        $w = min($w, $h);
        $h = $w;
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $radius = $circle ? $w / 2 : 20;
        $r = $radius;
        $x = 0;

        while ($x < $w) {
            $y = 0;
            while ($y < $h) {
                $rgbColor = imagecolorat($target, $x, $y);
                if ($radius <= $x && $x <= $w - $radius || $radius <= $y && $y <= $h - $radius) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    $y_x = $r;
                    $y_y = $r;
                    if (($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }

                    $y_x = $w - $r;
                    $y_y = $r;
                    if (($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }

                    $y_x = $r;
                    $y_y = $h - $r;
                    if (($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }

                    $y_x = $w - $r;
                    $y_y = $h - $r;
                    if (($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
                ++$y;
            }
            ++$x;
        }
        return $img;
    }

    static function createImage($imgurl) {
        @ini_set('memory_limit', '512M');
        load()->func('communication');
        $resp = ihttp_request($imgurl);

        if ($resp['code'] == 200 && !empty($resp['content'])) {
            return imagecreatefromstring($resp['content']);
        }
        if ($resp['errno'] == 35) {
            $imgurl = str_replace(array('https://'), 'http://', $imgurl);
        }

        $i = 0;
        while ($i < 3) {
            $resp = ihttp_request($imgurl);
            if ($resp['code'] == 200 && !empty($resp['content'])) {
                return imagecreatefromstring($resp['content']);
            }

            ++$i;
        }


        //以上方法都未获取图片资源
        $resp = file_get_contents($imgurl);
        return imagecreatefromstring($resp);
    }

    static function mergeImage($target, $data, $imgurl) {
        $img = self::createImage($imgurl);
        $w = imagesx($img);
        $h = imagesy($img);
        if ($data['appoint']) {
            $w = $data['width'];
            $h = $data['height'];
        }
        if ($data['border'] == 'radius' || $data['border'] == 'circle') {
            $img = self::imageRadius($img, $data['border'] == 'circle');
            $number = $data['width'];
            if ($w > $h) {
                $width = $number;
                $height = $width / $w * $h;
            } else if ($w < $h) {
                $height = $number;
                $width = $height / $h * $w;
            } else {
                $width = $number;
                $height = $number;
            }
            $wh = min([$w, $h]);
            $minWh = min([$width, $height]);
            imagecopyresized($target, $img, $data['left'], $data['top'], 0, 0, $minWh, $minWh, $wh, $wh);
            imagedestroy($img);
            return $target;
        }
        if ($data['position'] == 'cover') {
            $oldheight = $data['height'];
            $data['height'] = $data['width'] * $h / $w;
            if ($data['height'] > $oldheight) {
                $data['top'] = $data['top'] - ($data['height'] - $oldheight) / 2;
            }
        }

        imagecopyresized($target, $img, $data['left'], $data['top'], 0, 0, $data['width'], $data['height'], $w, $h);
        imagedestroy($img);

        return $target;
    }

    static function mergeText($target, $data, $text) {
        $font = IA_ROOT . '/addons/' . MODULE_NAME . '/web/resource/fonts/pingfang.ttf';
        if (!is_file($font)) {
            $font = IA_ROOT . '/addons/' . MODULE_NAME . '/web/resource/fonts/msyh.ttf';
        }
        $colors = self::hex2rgb($data['color']);
        // 自动换行处理
        $text = self::autowrap($data['size'], 0, $font, $text, $data['width'], $data['line']);
        //文字居中
        if ($data['align'] == 'center') {
            $textbox = imagettfbbox($data['size'], 0, $font, $text);
            $textwidth = $textbox[4] - $textbox[6];
            $data['left'] = $data['left'] + ($data['width'] / 2 - $textwidth / 2);
        }
        //$text = mb_convert_encoding($text, "html-entities", "utf-8");
        $encode = mb_detect_encoding($text, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5','LATIN1'));
        if($encode != 'UTF-8'){
            $text = mb_convert_encoding($text, 'UTF-8', $encode);
        }
        $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
        imagettftext($target, $data['size'], 0, $data['left'], $data['top'] + $data['size'], $color, $font, $text);

        return $target;
    }

    //画线
    protected static function mergeLine($target, $data) {
        $colors = self::hex2rgb($data['color']);
        $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
        imageline($target, $data['left'], $data['top'], $data['left'] + $data['width'], $data['top'] + $data['height'], $color);
        return $target;
    }

    // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
    static function autowrap($fontsize, $angle, $fontface, $string, $width, $needhang = 1) {
        $content = "";
        $hang = 1;
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i = 0; $i < mb_strlen($string, 'UTF8'); $i++) {
            $letter[] = mb_substr($string, $i, 1, 'UTF8');
        }
        foreach ($letter as $l) {
            $teststr = $content . " " . $l;
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                if ($hang < $needhang) {
                    $content .= "\n";
                    $hang++;
                } else {
                    break;
                }
            }
            $content .= $l;
        }
        return $content;
    }

    static function hex2rgb($colour) {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }

        if (strlen($colour) == 6) {
            list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
        } else if (strlen($colour) == 3) {
            list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
        } else {
            return false;
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return array('red' => $r, 'green' => $g, 'blue' => $b);
    }

    static function createPoster($poster, $filename = '', $member, $width = 640) {
        global $_W;
        $path = IA_ROOT . '/addons/' . MODULE_NAME . '/data/poster/' . $_W['uniacid'] . '/';
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $md5 = $filename ? $filename : md5(json_encode(array('openid' => $member['openid'], 'time' => time())));
        $file = $md5 . '.png';
        //if (!is_file($path . $file)) {
        if(1==1){
            set_time_limit(0);
            @ini_set('memory_limit', '512M');
            $bg = self::createImage(tomedia($poster['bg']));
            $target = imagecreatetruecolor($width, imagesy($bg));
            imagecopy($target, $bg, 0, 0, 0, 0, $width, imagesy($bg));
            imagedestroy($bg);
            $data = json_decode(str_replace('&quot;', '\'', $poster['data']), true);
            $textArr = ['vip_price', 'nickname', 'title', 'sub_title', 'text', 'shopTitle', 'shopAddress', 'shopPhone',
                'position', 'company_branch', 'address', 'mobile', 'desc', 'wechat', 'name', 'numbers'];
            foreach ($data as $d) {
                $d = self::getRealData($d);
                if ($d['type'] == 'head') {
                    $avatar = preg_replace('/\\/0$/i', '/96', $poster['avatar']);
                    $target = self::mergeImage($target, $d, $avatar);
                } else if ($d['type'] == 'img') {
                    $target = self::mergeImage($target, $d, $d['src']);
                } else if ($d['type'] == 'qr') {
                    $target = self::mergeImage($target, $d, $poster['qrimg']);
                } else if (in_array($d['type'], $textArr)) {
                    $textContent = ($d['type'] == 'text') ? $d['words'] : $poster[$d['type']];
                    $textContent = str_replace(['“','”'],['"','"'],$textContent);
                    $textContent = htmlspecialchars_decode($textContent);

                    $target = self::mergeText($target, $d, $textContent);
                } else if ($d['type'] == 'line') {
                    $target = self::mergeLine($target, $d);
                } else {
                    if ($d['type'] == 'thumb' || $d['type'] == 'shopThumb' || $d['type'] == 'logo') {
                        $thumb = tomedia($poster[$d['type']]);
                        $target = self::mergeImage($target, $d, $thumb);
                    } else if ($d['type'] == 'marketprice' || $d['type'] == 'productprice') {
                        $target = self::mergeText($target, $d, is_numeric($poster[$d['type']]) ? '￥' . $poster[$d['type']] : $poster[$d['type']]);
                    }
                }
            }
            imagepng($target, $path . $file);
//            header("content-type: image/png");
//            imagepng($target);//输出到页面。如果有第二个参数[,$filename],则表示保存图像
//            imagedestroy($target);die;
            imagedestroy($target);
        }
        $img = $_W['siteroot'] . 'addons/' . MODULE_NAME . '/data/poster/' . $_W['uniacid'] . '/' . $file;
        return $img;
    }

    static function clearposter() {
        global $_W;
        load()->func('file');
        @rmdirs(IA_ROOT . '/addons/' . MODULE_NAME . '/data/poster/' . $_W['uniacid']);
        @rmdirs(ATTACHMENT_ROOT . 'images/' . $_W['uniacid'] . '/media.upload/');
    }

    static function clearwxapp() {
        global $_W;
        load()->func('file');
        @rmdirs(IA_ROOT . '/addons/' . MODULE_NAME . '/data/wxapp/' . $_W['uniacid']);
    }

    static function get_head_img($url, $num) {
        $imgs_array = array();
        $random_array = array();
        $files = array();
        if ($handle = opendir($url)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (substr($file, -3) == 'gif' || substr($file, -3) == 'jpg') {
                        $files[count($files)] = $file;
                    }
                }
            }
        }
        closedir($handle);
        for ($i = 0; $i < $num; $i++) {
            $random = rand(0, count($files) - 1);
            while (in_array($random, $random_array)) {
                $random = rand(0, count($files) - 1);
            }
            $random_array[$i] = $random;
            $imgs_url = $url . "/" . $files[$random];
            $imgs_array[$i] = $imgs_url;

        }
        return $imgs_array;
    }
}
