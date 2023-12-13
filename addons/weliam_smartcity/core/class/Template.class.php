<?php
defined('IN_IA') or exit('Access Denied');

class Template {
    /**
     * html转化tpl
     *
     * @access static public
     * @name wl_template_compile
     */
    static function wl_template_compile($from, $to, $inmodule = false) {
        global $_W;
        $path = dirname($to);
        if (!is_dir($path)) {
            load()->func('file');
            Util::mkDirs($path);
        }
        $content = self::wl_template_parse(file_get_contents($from), $inmodule);
        if (defined('IN_APP')) {
            if (!empty($_W['wlsetting']['halfcard']['text']['halfcardtext'])) {
                $content = str_replace('一卡通', $_W['wlsetting']['halfcard']['text']['halfcardtext'], $content);
            }
            if (!empty($_W['wlsetting']['halfcard']['text']['privilege'])) {
                $content = str_replace('特权', $_W['wlsetting']['halfcard']['text']['privilege'], $content);
            }
            if (!empty($_W['wlsetting']['trade']['credittext'])) {
                $content = str_replace('积分', $_W['wlsetting']['trade']['credittext'], $content);
            }
            if (!empty($_W['wlsetting']['trade']['moneytext'])) {
                $content = str_replace('余额', $_W['wlsetting']['trade']['moneytext'], $content);
            }
        }
        file_put_contents($to, $content);
    }

    /**
     * 转译模板
     *
     * @access static public
     * @name wl_template_parse
     */
    static function wl_template_parse($str, $inmodule = false) {
        $str = preg_replace('/<!--{(.+?)}-->/s', '{$1}', $str);
        $str = preg_replace('/{template\s+(.+?)}/', '<?php include wl_template($1, TEMPLATE_INCLUDEPATH);?>', $str);
        $str = preg_replace('/{php\s+(.+?)}/', '<?php $1?>', $str);
        $str = preg_replace('/{if\s+(.+?)}/', '<?php if($1) { ?>', $str);
        $str = preg_replace('/{else}/', '<?php } else { ?>', $str);
        $str = preg_replace('/{else ?if\s+(.+?)}/', '<?php } else if($1) { ?>', $str);
        $str = preg_replace('/{\/if}/', '<?php } ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2) { ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>', $str);
        $str = preg_replace('/{\/loop}/', '<?php } } ?>', $str);
        $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}/', '<?php echo $1;?>', $str);
        $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);
        $str = preg_replace('/{url\s+(\S+)}/', '<?php echo url($1);?>', $str);
        $str = preg_replace('/{url\s+(\S+)\s+(array\(.+?\))}/', '<?php echo url($1, $2);?>', $str);
        $str = @preg_replace_callback('/<\?php([^\?]+)\?>/s', "template_addquote", $str);
        $str = preg_replace('/{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}/s', '<?php echo $1;?>', $str);
        $str = str_replace('{##', '{', $str);
        $str = str_replace('##}', '}', $str);
        $str = "<?php defined('IN_IA') or exit('Access Denied');?>" . $str;
        return $str;
    }
}
