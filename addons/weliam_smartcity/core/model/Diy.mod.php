<?php
defined('IN_IA') or exit('Access Denied');

class Diy {
    /**
     * Comment: 获取页面列表
     * Author: zzw
     * @param $pageName 页面名称，搜索页面时使用
     * @param $pindex   页码，查询第几页的内容
     * @return mixed
     */
    public static function pageList($pageName, $pindex) {
        global $_W, $_GPC;
        $psize = 10;
        $where = " (aid = {$_W['aid']} OR is_public = 1) AND uniacid = {$_W['uniacid']} ";
        if ($_GPC['page_type']) {
            $where .= " AND type = {$_GPC['page_type']}";
        }
        if ($pageName) {
            $where .= " AND `name` LIKE '%{$pageName}%'";
        }
        $sql = "SELECT * FROM " . tablename(PDO_NAME . "diypage") . " WHERE {$where} ORDER BY lastedittime DESC";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename(PDO_NAME . 'diypage') . " WHERE {$where}");
        $pager = wl_pagination($total, $pindex, $psize);

        $data['list'] = $list;
        $data['pager'] = $pager;

        return $data;
    }
    /**
     * Comment: 获取菜单列表的信息
     * Author: zzw
     * @param $name    菜单名称，搜索菜单时使用
     * @param $pindex  页码，查询第几页的内容
     * @return mixed
     */
    public static function menuList($name, $pindex, $menuClass) {
        global $_W;
        $psize = 10;
        $where = " (aid = {$_W['aid']} OR is_public = 1) AND uniacid = {$_W['uniacid']} AND menu_class = {$menuClass} ";
        if ($name) {
            $where .= " AND name LIKE '%{$name}%'";
        }
        $list = pdo_fetchall('SELECT * FROM '
            . tablename(PDO_NAME . 'diypage_menu')
            . " WHERE " . $where . ' order by id desc limit ' . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM '
            . tablename(PDO_NAME . 'diypage_menu')
            . ' WHERE ' . $where);
        $pager = wl_pagination($total, $pindex, $psize);

        $data['list'] = $list;
        $data['pager'] = $pager;

        return $data;
    }
    /**
     * Comment: 获取广告列表的信息
     * Author: zzw
     */
    public static function advList($name, $pindex, $advClass) {
        global $_W;
        $where = " (aid = {$_W['aid']} OR is_public = 1) AND uniacid = {$_W['uniacid']} AND adv_class = {$advClass} ";
        if ($name) {
            $where .= " AND name LIKE '%{$name}%' ";
        }
        $psize = 10;
        $list = pdo_fetchall('SELECT * FROM ' . tablename(PDO_NAME . 'diypage_adv')
            . " WHERE " . $where . ' ORDER BY id DESC limit ' . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . 'diypage_adv')
            . " WHERE " . $where);
        $pager = wl_pagination($total, $pindex, $psize);


        $data['list'] = $list;
        $data['pager'] = $pager;

        return $data;

    }
    /**
     * Comment: 获取模板列表
     * Author: zzw
     * @param $cate int 分类id
     * @param $tempName string 模板名称
     * @param $pindex int 页码
     * @return bool
     */
    public static function tempList($cate, $tempName, $pindex) {
        global $_W;
        #1、获取参数信息
        $psize = 12;
        #2、判断是否存在默认模板  不存在则添加
        self::isHaveDefaultTemp();
        #3、获取查询条件
        $where = " ( uniacid = {$_W['uniacid']} OR uniacid = 0 ) AND (aid = {$_W['aid']} OR aid = 0)";
        if ($tempName) {
            $where .= " AND `name` LIKE '%{$tempName}%'";
        }
        if (intval($cate) >= 0 && strlen($cate) > 0) {
            $where .= " AND cate = {$cate} ";
        }
        $sql = "SELECT id,uniacid,aid,`name`,`type`,preview,page_class,cate FROM "
            . tablename(PDO_NAME . "diypage_temp")
            . " WHERE {$where} ORDER BY id DESC";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);
        foreach($list as $key => &$val){
            $val['cate_name'] = pdo_getcolumn(PDO_NAME."diypage_temp_cate"
                ,['id'=>$val['cate']],'name');
        }

        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename(PDO_NAME . 'diypage_temp') . " WHERE {$where}");
        $pager = wl_pagination($total, $pindex, $psize);

        $allpagetype = self::getPageType();
        $category = pdo_fetchall('SELECT id,name FROM '
            . tablename(PDO_NAME . 'diypage_temp_cate')
            . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ORDER BY id ASC ");

        $data['list'] = $list;
        $data['pager'] = $pager;
        $data['allpagetype'] = $allpagetype;
        $data['category'] = $category;

        return $data;
    }
    /**
     * Comment: 判断是否存在默认模板信息，不存在则添加模板
     * Author: zzw
     * Date: 2019/11/11 10:05
     */
    protected static function isHaveDefaultTemp(){
        global $_W;
        //TODO  由于添加了一些新的功能  默认模板需要优化一下  添加以前未添加的功能  并且多添加几个对应插件的主页
        #1、建立默认数据数组
        $data = [ [
            'id'         => 1 ,
            'uniacid'    => 0 ,
            'aid'        => 0 ,
            'type'       => 2 ,
            'name'       => '圣诞主题首页' ,
            'data'       => 'eyJwYWdlIjp7InR5cGUiOiIyIiwidGl0bGUiOiLlnKPor57kuLvpopjpppbpobUiLCJuYW1lIjoi5Zyj6K+e5Li76aKY6aaW6aG1IiwiYmFja2dyb3VuZCI6IiNGRkZGRkYiLCJkaXltZW51IjoiIiwiZGl5YWR2IjoiIiwicGFnZUNsYXNzIjoiMSJ9LCJpdGVtcyI6eyJNMTU0MjM1ODIyMDkwNCI6eyJwYXJhbXMiOnsicGxhY2Vob2xkZXIiOiLor7fovpPlhaXlhbPplK7lrZfov5vooYzmkJzntKIifSwic3R5bGUiOnsiaW5wdXRiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsImJhY2tncm91bmQiOiIjZGYzMzQxIiwiaWNvbmNvbG9yIjoiI2QxZDFkMSIsImNvbG9yIjoiI2QxZDFkMSIsInRleHRhbGlnbiI6ImxlZnQiLCJzZWFyY2hzdHlsZSI6InJvdW5kIiwibWFyZ2luQm90dG9tIjoiMCIsImFyZWFDb2xvciI6IiNmZmZmZmYifSwiZ3JvdXBfbmFtZSI6InNlYXJjaCIsImdyb3VwX2tleSI6IjEiLCJpZCI6InNlYXJjaDIiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyMzU4NDU0MTI3Ijp7Im5hdl9jbGFzcyI6IjEiLCJuYW1lIjoi5Zu+54mH6L2u5pKtIiwic3R5bGUiOnsiZG90c3R5bGUiOiJyb3VuZCIsImRvdGFsaWduIjoiY2VudGVyIiwiYm90dG9tIjoiMCJ9LCJkYXRhIjp7IkMxNTQyMzU4NDU0MTI4Ijp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9QWHh4ZTdYZ1oyMkUyYWEzalpaUDc3WERrZ0pQRXoucG5nIiwibGlua3VybCI6IiJ9LCJNMTU0MjYxNTczOTYwNCI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvenc4NjhpNmdQcDg2UWRCNnJSa284OE5nV3JKTktkLnBuZyIsImxpbmt1cmwiOiIifX0sImlkIjoiYmFubmVyIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0MjM1OTA3OTQ3MSI6eyJuYXZfY2xhc3MiOiIxIiwibmFtZSI6IuaMiemSrue7hCIsInN0eWxlIjp7Im5hdnN0eWxlIjoiIiwiYmFja2dyb3VuZCI6IiNmZmZmZmYiLCJyb3dudW0iOiI1Iiwic2hvd3R5cGUiOiIwIiwicGFnZW51bSI6IjUiLCJzaG93ZG90IjoiMSJ9LCJkYXRhIjp7IkMxNTQyMzU5MDc5NDcyIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9qUWdzdEcyU1AxZGt1ZlBKVkRQcUUyNEFKNGRvSkQucG5nIiwibGlua3VybCI6IiIsInRleHQiOiLnrb7liLAiLCJjb2xvciI6IiM2NjY2NjYifSwiQzE1NDIzNTkwNzk0NzMiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL1lCcUhUQTRxWUZCeXk4UXlMTFV1dEJINDRZNEw0NC5wbmciLCJsaW5rdXJsIjoiIiwidGV4dCI6IuS4gOWNoemAmiIsImNvbG9yIjoiIzY2NjY2NiJ9LCJDMTU0MjM1OTA3OTQ3NCI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wveUhRNVRoOGsxRXYxM3R0eFR4MzhaNTUyMU9LVlR2LnBuZyIsImxpbmt1cmwiOiIiLCJ0ZXh0Ijoi6LaF57qn5Y23IiwiY29sb3IiOiIjNjY2NjY2In0sIkMxNTQyMzU5MDc5NDc1Ijp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9xRUdiYzVMSmpqQ2xiRUxnTDN0czVqZTZzNk9iNWoucG5nIiwibGlua3VybCI6IiIsInRleHQiOiLlm6LotK3mtLvliqgiLCJjb2xvciI6IiM2NjY2NjYifSwiTTE1NDIzNTkxMjAzNTIiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL0MxbzRvYmNHMTF3WlhQb2QyTkxDUjJuejExWHd4eC5wbmciLCJsaW5rdXJsIjoiIiwidGV4dCI6IuaLvOWbouWVhuWTgSIsImNvbG9yIjoiIzY2NjY2NiJ9LCJNMTU0MjM1OTEzMTY4MCI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvVFZ2MFdoZmhXdDNFRlA1R2UwTTM1R0c0RTI5MDFqLnBuZyIsImxpbmt1cmwiOiIiLCJ0ZXh0Ijoi5oqi6LSt5rS75YqoIiwiY29sb3IiOiIjNjY2NjY2In0sIk0xNTQyMzU5MTM3NzExIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9tTjYwMjA2WlJMTmhITE9aWnpLS2FvRUtPSzRhWnIucG5nIiwibGlua3VybCI6IiIsInRleHQiOiLmjozkuIrkv6Hmga8iLCJjb2xvciI6IiM2NjY2NjYifSwiTTE1NDIzNTkxNDUzMzYiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL204aEV0ZFllRTFpaDhlNXJScWZJcTF6eTVENWVyNS5wbmciLCJsaW5rdXJsIjoiIiwidGV4dCI6IuegjeS7t+a0u+WKqCIsImNvbG9yIjoiIzY2NjY2NiJ9LCJNMTU0MjM1OTE1MDcyNyI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvQUpLNncwY2oxNmZtSzFJdzVrMW5XYTVOZ29pTXpGLnBuZyIsImxpbmt1cmwiOiIiLCJ0ZXh0Ijoi5LiA5Y2h6YCaIiwiY29sb3IiOiIjNjY2NjY2In0sIk0xNTQyMzU5MTU1OTI3Ijp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9Xb1M4SGJCWnI0bzYxNkQ5MXc5OWRzYTM5OGQzaG8ucG5nIiwibGlua3VybCI6IiIsInRleHQiOiLmtLvliqjlhaXlj6MiLCJjb2xvciI6IiM2NjY2NjYifX0sImlkIjoibWVudSIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI3OTE3OTQ3MDAiOnsibmF2X2NsYXNzIjoiMiIsIm5hbWUiOiLovoXliqnnur8iLCJzdHlsZSI6eyJiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsImJvcmRlciI6IiMwMDAwMDAiLCJwYWRkaW5nIjoiMCIsImxpbmVzdHlsZSI6InNvbGlkIiwibGVmdHBhZGRpbmciOiIwIiwiYm9yZGVyY29sb3IiOiIjZjNmM2YzIn0sImlkIjoibGluZSIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDIzNjAwNjUyMTYiOnsicGFyYW1zIjp7InRpdGxlIjoi5ZCM5Z+O5aS05p2hIn0sInN0eWxlIjp7Im1hcmdpbmJvdHRvbSI6IjAifSwiZ3JvdXBfbmFtZSI6Im5vdGljZSIsImdyb3VwX2tleSI6IjIiLCJpZCI6Im5vdGljZTMiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyNjIxMjc1OTIyIjp7Im5hdl9jbGFzcyI6IjEiLCJuYW1lIjoi5Y2V5Zu+57uEIiwiZGF0YSI6eyJDMTU0MjYyMTI3NTkyMiI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvWVdha0p0VFBScnJUVGF3NjJqOUhIVHE5OUp0V2g0LnBuZyIsImxpbmt1cmwiOiIifX0sImlkIjoicGljdHVyZSIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI3OTcyNjM1NzkiOnsibmF2X2NsYXNzIjoiMiIsIm5hbWUiOiLovoXliqnnqbrnmb0iLCJzdHlsZSI6eyJoZWlnaHQiOiIxNSIsImJhY2tncm91bmQiOiIjZjRmNGY0In0sImlkIjoiYmxhbmsiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyNjgxODkwNzE0Ijp7InBhcmFtcyI6eyJtYWluVGV4dCI6IueJueiJsue+jumjnyIsInZpY2VUZXh0Ijoi5LiN5Y+v5LiN5ZCD55qE5b2T5Zyw54m56Imy5bCP5ZCDIn0sInN0eWxlIjp7Im1hcmdpbkJvdHRvbSI6IjAiLCJiZ0NvbG9yIjoiI0ZGRkZGRiIsIm1haW5Db2xvciI6IiMwMDAwMDAiLCJtYWluQWxpZ24iOiJsZWZ0IiwidmljZUNvbG9yIjoiIzk5OTk5OSIsInZpY2VBbGlnbiI6ImxlZnQifSwiZ3JvdXBfbmFtZSI6InRpdGxlIiwiZ3JvdXBfa2V5IjoiMSIsImlkIjoidGl0bGUyIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0MjYyMTQ0OTg5MiI6eyJzdHlsZSI6eyJtYXJnaW5Cb3R0b20iOiIwIiwibWFyZ2luTGVmdFJpZ2h0IjoiNSIsIm1hcmdpblRvcEJvdHRvbSI6IjUiLCJiZ0NvbG9yIjoiI2ZmZmZmZiJ9LCJkYXRhIjp7IkMwMTIzNDU2Nzg5MTAxIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9waXpURjhyeWNFNmREbGxxQ0NPRWl0UXR6cWZxMXYucG5nIiwibGlua3VybCI6IiJ9LCJDMDEyMzQ1Njc4OTEwMiI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wveDZPdzRTMHdqT0gzRlV1ekhVZnd3WnUzSzR1V1dULnBuZyIsImxpbmt1cmwiOiIifSwiQzAxMjM0NTY3ODkxMDMiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL0FQcGI5VTRSVXVTUFBWSnVXcXE1Ync3UDZXcTUzSi5wbmciLCJsaW5rdXJsIjoiIn0sIk0xNTQyODc1MzgxMDUxIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9ERlk1MDA2R0x1WmNLQ0c1WXk5Zno5SDBpNnI2RmsuanBnIiwibGlua3VybCI6IiJ9LCJNMTU0Mjg3NTM4MTc3MSI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvQzExRGRvQU5vQTJvMER6OWVkeDZOejNvTjREODA0LmpwZyIsImxpbmt1cmwiOiIifSwiTTE1NDI4NzUzODI2NDMiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL0hOaEc0M1ROb09MNXl5bjBvNEg1dnVVY255NW5vdi5qcGciLCJsaW5rdXJsIjoiIn19LCJncm91cF9uYW1lIjoicGljdHVyZXciLCJncm91cF9rZXkiOiI1IiwiaWQiOiJwaWN0dXJldzYiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyNzkzNTI2NzMxIjp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp56m655m9Iiwic3R5bGUiOnsiaGVpZ2h0IjoiNCIsImJhY2tncm91bmQiOiIjZmZmZmZmIn0sImlkIjoiYmxhbmsiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyNzk2NzczOTMzIjp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp56m655m9Iiwic3R5bGUiOnsiaGVpZ2h0IjoiMTUiLCJiYWNrZ3JvdW5kIjoiI2Y0ZjRmNCJ9LCJpZCI6ImJsYW5rIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjc5MzU4OTY5OSI6eyJwYXJhbXMiOnsibWFpblRleHQiOiLpgJvlkIPpgJvlkIMiLCJ2aWNlVGV4dCI6IuWBmuS4quWLpOS/reaMgeWutueahOi0reeJqeeLgiJ9LCJzdHlsZSI6eyJtYXJnaW5Cb3R0b20iOiI0IiwiYmdDb2xvciI6IiNGRkZGRkYiLCJtYWluQ29sb3IiOiIjMDAwMDAwIiwibWFpbkFsaWduIjoibGVmdCIsInZpY2VDb2xvciI6IiM5OTk5OTkiLCJ2aWNlQWxpZ24iOiJsZWZ0In0sImdyb3VwX25hbWUiOiJ0aXRsZSIsImdyb3VwX2tleSI6IjEiLCJpZCI6InRpdGxlMiIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI2ODUxMjcwNDkiOnsic3R5bGUiOnsibWFyZ2luQm90dG9tIjoiMCIsIm1hcmdpbkxlZnRSaWdodCI6IjEwIiwibWFyZ2luVG9wQm90dG9tIjoiMTAiLCJiZ0NvbG9yIjoiI2ZmZmZmZiJ9LCJkYXRhIjp7IkMwMTIzNDU2Nzg5MTAxIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQzXC9BY2M0Y2M4U3NNNFdQSTJyTzV3ODNXVzJvTmNvOGMuanBnIiwibGlua3VybCI6IiJ9LCJDMDEyMzQ1Njc4OTEwMiI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0M1wvTXQ5VHR6Yjc5ZWo5ajY0WnZtVGVzalJSNjcyU3FULmpwZyIsImxpbmt1cmwiOiIifSwiQzAxMjM0NTY3ODkxMDMiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL29oVGJITGhxbWFBbGVoWkw1T2JVV0dHbU1ZNWdZUy5qcGciLCJsaW5rdXJsIjoiIn19LCJncm91cF9uYW1lIjoicGljdHVyZXciLCJncm91cF9rZXkiOiIzIiwiaWQiOiJwaWN0dXJldzQiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyOTAwMDEwMjQxIjp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp56m655m9Iiwic3R5bGUiOnsiaGVpZ2h0IjoiMTUiLCJiYWNrZ3JvdW5kIjoiI2Y0ZjRmNCJ9LCJpZCI6ImJsYW5rIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg1MDgzMTk2MCI6eyJuYXZfY2xhc3MiOiIyIiwibmFtZSI6Iui+heWKqeepuueZvSIsInN0eWxlIjp7ImhlaWdodCI6IjQiLCJiYWNrZ3JvdW5kIjoiI2ZmZmZmZiJ9LCJpZCI6ImJsYW5rIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg1MDgzNzYwMiI6eyJwYXJhbXMiOnsibWFpblRleHQiOiLnlITpgInmjqjojZAiLCJ2aWNlVGV4dCI6IuaIkeaDs+ivt+S9oOWQg+mhv+mlrSJ9LCJzdHlsZSI6eyJtYXJnaW5Cb3R0b20iOiIwIiwiYmdDb2xvciI6IiNGRkZGRkYiLCJtYWluQ29sb3IiOiIjMDAwMDAwIiwibWFpbkFsaWduIjoibGVmdCIsInZpY2VDb2xvciI6IiM5OTk5OTkiLCJ2aWNlQWxpZ24iOiJsZWZ0In0sImdyb3VwX25hbWUiOiJ0aXRsZSIsImdyb3VwX2tleSI6IjEiLCJpZCI6InRpdGxlMiIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI4NTA4Nzc1MDQiOnsicGx1Z2luIjoiMiIsInBhcmFtcyI6eyJ0eXBlIjoiMSIsIm9yZGVycyI6IjEiLCJjbGFzc3MiOiIwIiwic2hvd19udW0iOiIxIn0sInN0eWxlIjp7Im1hcmdpbkJvdHRvbSI6IjAifSwiZGF0YSI6eyJDMDEyMzQ1Njc4OTEwMSI6eyJnb29kc19uYW1lIjoi6K+36YCJ5oup5ZWG5ZOBLi4uIiwib2xkcHJpY2UiOiIwMC4wMCIsInByaWNlIjoiMDAuMDAiLCJzdG9yZW5hbWUiOiLlupfpk7rlkI3np7AuLi4iLCJsb2dvIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvaW1hZ2VzXC9kZWZhdWx0LnBuZyJ9LCJNMTU0MjkwMDAzNDM3OCI6eyJnb29kc19uYW1lIjoi6K+36YCJ5oup5ZWG5ZOBLi4uIiwib2xkcHJpY2UiOiIwMC4wMCIsInByaWNlIjoiMDAuMDAiLCJzdG9yZW5hbWUiOiLlupfpk7rlkI3np7AuLi4iLCJsb2dvIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvaW1hZ2VzXC9kZWZhdWx0LnBuZyJ9LCJNMTU0MjkwMDAzNjYxMSI6eyJnb29kc19uYW1lIjoi6K+36YCJ5oup5ZWG5ZOBLi4uIiwib2xkcHJpY2UiOiIwMC4wMCIsInByaWNlIjoiMDAuMDAiLCJzdG9yZW5hbWUiOiLlupfpk7rlkI3np7AuLi4iLCJsb2dvIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvaW1hZ2VzXC9kZWZhdWx0LnBuZyJ9fSwiZ3JvdXBfbmFtZSI6Imdyb3Vwb25fZ29vZHMiLCJncm91cF9rZXkiOiIwIiwiaWQiOiJncm91cG9uX2dvb2RzIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg1MTY2ODk2NCI6eyJzdHlsZSI6eyJtYXJnaW5MZWZ0UmlnaHQiOiIwIiwibWFyZ2luVG9wQm90dG9tIjoiNCIsImJnQ29sb3IiOiIjRkZGRkZGIn0sImRhdGEiOnsiQzAxMjM0NTY3ODkxMDEiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDNcL3ZldlRiOUVtbjY2dlV2bWJXUm11Qk5idk45TWtYVi5qcGciLCJsaW5rdXJsIjoiIn19LCJncm91cF9uYW1lIjoicGljdHVyZXciLCJncm91cF9rZXkiOiIxIiwiaWQiOiJwaWN0dXJldzIiLCJwYWdlQ2xhc3MiOiIxIn19fQ==' ,
            'preview'    => '../addons/weliam_smartcity/web/resource/diy/template/default3/page_image15429009731941.png' ,
            'cate'       => 0 ,
            'page_class' => 0
        ] , [
            'id'         => 2 ,
            'uniacid'    => 0 ,
            'aid'        => 0 ,
            'type'       => 1 ,
            'name'       => '同城吃喝玩乐' ,
            'data'       => 'eyJwYWdlIjp7InR5cGUiOiIxIiwidGl0bGUiOiLlkIzln47lkIPllp3njqnkuZAiLCJuYW1lIjoi5ZCM5Z+O5ZCD5Zad546p5LmQIiwiYmFja2dyb3VuZCI6IiNGRkZGRkYiLCJkaXltZW51IjoiIiwiZGl5YWR2IjoiIiwicGFnZUNsYXNzIjoiMSJ9LCJpdGVtcyI6eyJNMTU0MjYxNzUxOTk2OCI6eyJwYXJhbXMiOnsidGV4dGNvbnRlbnQiOiLlkIzln47lkIPllp3njqnkuZDvvIzpnaDosLHov5jlrp7mg6AifSwic3R5bGUiOnsiaW5wdXRiYWNrZ3JvdW5kIjoiI2ZmZmZjOCIsImJhY2tncm91bmQiOiIjZmZkOTQwIiwidGl0bGVjb2xvciI6IiMwMDAwMDAiLCJpY29uY29sb3IiOiIjOTk5OTk5IiwibWFyZ2luQm90dG9tIjoiMCJ9LCJncm91cF9uYW1lIjoic2VhcmNoIiwiZ3JvdXBfa2V5IjoiMiIsImlkIjoic2VhcmNoMyIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI4Nzc3Mjc5NTIiOnsibmF2X2NsYXNzIjoiMSIsImdyb3VwIjoicGljdHVyZXciLCJuYW1lIjoi5Zu+54mH5qmx56qXIiwic3R5bGUiOnsibWFyZ2luTGVmdFJpZ2h0IjoiMCIsIm1hcmdpblRvcEJvdHRvbSI6IjAiLCJiZ0NvbG9yIjoiI0ZGRkZGRiJ9LCJkYXRhIjp7IkMxNTQyODc3NzI3OTUyIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQyXC9CckdhUlNobWJHZ1NINjhtTWNjQ2Ezc1M2QzJDY00uanBnIiwibGlua3VybCI6IiJ9LCJDMTU0Mjg3NzcyNzk1MyI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0MlwvcE1GODRXOTlNb01HbU00WkZabVRGOVllRm15R0x4LmpwZyIsImxpbmt1cmwiOiIifSwiQzE1NDI4Nzc3Mjc5NTQiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDJcL0YyWkUyODg4QzYyTjQxN1dOMjNnMDc2M05WODIzRS5qcGciLCJsaW5rdXJsIjoiIn0sIkMxNTQyODc3NzI3OTU1Ijp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQyXC91NDBadVpWNkp6OTZWWmY1b082aE8yS2EwbzQ5b08uanBnIiwibGlua3VybCI6IiJ9fSwiaWQiOiJwaWN0dXJldyIsInBhZ2VDbGFzcyI6IjEiLCJncm91cF9uYW1lIjoicGljdHVyZXciLCJncm91cF9rZXkiOiIwIn0sIk0xNTQyODk5MDAxODc4Ijp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp57q/Iiwic3R5bGUiOnsiYmFja2dyb3VuZCI6IiNmZmZmZmYiLCJib3JkZXIiOiIjMDAwMDAwIiwicGFkZGluZyI6IjAiLCJsaW5lc3R5bGUiOiJzb2xpZCIsImxlZnRwYWRkaW5nIjoiMTUiLCJib3JkZXJjb2xvciI6IiNmM2YzZjMifSwiaWQiOiJsaW5lIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg5OTA4MTM3OSI6eyJuYXZfY2xhc3MiOiIyIiwibmFtZSI6Iui+heWKqeepuueZvSIsInN0eWxlIjp7ImhlaWdodCI6IjE1IiwiYmFja2dyb3VuZCI6IiNmZmZmZmYifSwiaWQiOiJibGFuayIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI4Nzk0MTQ0MjUiOnsicGFyYW1zIjp7Im1haW5UZXh0Ijoi5LuK5pel54Ot5Y2WIiwidmljZVRleHQiOiLlpb3ngrnniIbmrL7vvIzkurrmiYvkuIDku70ifSwic3R5bGUiOnsibWFyZ2luQm90dG9tIjoiMCIsImJnQ29sb3IiOiIjRkZGRkZGIiwibWFpbkNvbG9yIjoiIzAwMDAwMCIsIm1haW5BbGlnbiI6ImxlZnQiLCJ2aWNlQ29sb3IiOiIjOTk5OTk5IiwidmljZUFsaWduIjoibGVmdCJ9LCJncm91cF9uYW1lIjoidGl0bGUiLCJncm91cF9rZXkiOiIxIiwiaWQiOiJ0aXRsZTIiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyODc5NDAzODI0Ijp7InBsdWdpbiI6IjEiLCJwYXJhbXMiOnsidHlwZSI6IjEiLCJvcmRlcnMiOiIxIiwiY2xhc3NzIjoiMCIsInN0YXR1cyI6IjEiLCJzaG93X251bSI6IjEifSwic3R5bGUiOnsibWFyZ2luQm90dG9tIjoiMCJ9LCJkYXRhIjp7IkMwMTIzNDU2Nzg5MTAxIjp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImJ1eV9udW0iOiIwIiwibG9nbyI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2ltYWdlc1wvZGVmYXVsdC5wbmcifSwiQzAxMjM0NTY3ODkxMDIiOnsiZ29vZHNfbmFtZSI6Iuivt+mAieaLqeWVhuWTgS4uLiIsIm9sZHByaWNlIjoiMDAuMDAiLCJwcmljZSI6IjAwLjAwIiwic3RvcmVuYW1lIjoi5bqX6ZO65ZCN56ewLi4uIiwiYnV5X251bSI6IjAiLCJsb2dvIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvaW1hZ2VzXC9kZWZhdWx0LnBuZyJ9LCJNMTU0Mjg5OTE2MTEwMSI6eyJnb29kc19uYW1lIjoi6K+36YCJ5oup5ZWG5ZOBLi4uIiwib2xkcHJpY2UiOiIwMC4wMCIsInByaWNlIjoiMDAuMDAiLCJzdG9yZW5hbWUiOiLlupfpk7rlkI3np7AuLi4iLCJidXlfbnVtIjoiMCIsImxvZ28iOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9pbWFnZXNcL2RlZmF1bHQucG5nIn0sIk0xNTQyODk5MTYyMjA1Ijp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImJ1eV9udW0iOiIwIiwibG9nbyI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2ltYWdlc1wvZGVmYXVsdC5wbmcifX0sImdyb3VwX25hbWUiOiJydXNoX2dvb2RzIiwiZ3JvdXBfa2V5IjoiMSIsImlkIjoicnVzaF9nb29kczIiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyODc5NzY3NTIxIjp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp57q/Iiwic3R5bGUiOnsiYmFja2dyb3VuZCI6IiNmZmZmZmYiLCJib3JkZXIiOiIjMDAwMDAwIiwicGFkZGluZyI6IjAiLCJsaW5lc3R5bGUiOiJzb2xpZCIsImxlZnRwYWRkaW5nIjoiMTUiLCJib3JkZXJjb2xvciI6IiNmM2YzZjMifSwiaWQiOiJsaW5lIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg3OTgwMDUyOSI6eyJuYXZfY2xhc3MiOiIyIiwibmFtZSI6Iui+heWKqeepuueZvSIsInN0eWxlIjp7ImhlaWdodCI6IjE1IiwiYmFja2dyb3VuZCI6IiNmZmZmZmYifSwiaWQiOiJibGFuayIsInBhZ2VDbGFzcyI6IjEifSwiTTE1NDI4Nzk4MTQ4NzAiOnsicGFyYW1zIjp7Im1haW5UZXh0Ijoi5paw5ZOB5o6o6I2QIiwidmljZVRleHQiOiLmlrDlk4Hlpb3nianvvIzlhYjliLDlhYjlvpcifSwic3R5bGUiOnsibWFyZ2luQm90dG9tIjoiMCIsImJnQ29sb3IiOiIjRkZGRkZGIiwibWFpbkNvbG9yIjoiIzAwMDAwMCIsIm1haW5BbGlnbiI6ImxlZnQiLCJ2aWNlQ29sb3IiOiIjOTk5OTk5IiwidmljZUFsaWduIjoibGVmdCJ9LCJncm91cF9uYW1lIjoidGl0bGUiLCJncm91cF9rZXkiOiIxIiwiaWQiOiJ0aXRsZTIiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyODc5OTAxNTc3Ijp7InBsdWdpbiI6IjIiLCJwYXJhbXMiOnsidHlwZSI6IjEiLCJvcmRlcnMiOiIxIiwiY2xhc3NzIjoiMCIsInNob3dfbnVtIjoiMSJ9LCJzdHlsZSI6eyJtYXJnaW5Cb3R0b20iOiIwIn0sImRhdGEiOnsiQzAxMjM0NTY3ODkxMDEiOnsiZ29vZHNfbmFtZSI6Iuivt+mAieaLqeWVhuWTgS4uLiIsIm9sZHByaWNlIjoiMDAuMDAiLCJwcmljZSI6IjAwLjAwIiwic3RvcmVuYW1lIjoi5bqX6ZO65ZCN56ewLi4uIiwibG9nbyI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2ltYWdlc1wvZGVmYXVsdC5wbmcifSwiTTE1NDI4OTkxNjg5MTMiOnsiZ29vZHNfbmFtZSI6Iuivt+mAieaLqeWVhuWTgS4uLiIsIm9sZHByaWNlIjoiMDAuMDAiLCJwcmljZSI6IjAwLjAwIiwic3RvcmVuYW1lIjoi5bqX6ZO65ZCN56ewLi4uIiwibG9nbyI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2ltYWdlc1wvZGVmYXVsdC5wbmcifX0sImdyb3VwX25hbWUiOiJncm91cG9uX2dvb2RzIiwiZ3JvdXBfa2V5IjoiMiIsImlkIjoiZ3JvdXBvbl9nb29kczMiLCJwYWdlQ2xhc3MiOiIxIn19fQ==' ,
            'preview'    => '../addons/weliam_smartcity/web/resource/diy/template/default2/page_image15428995956449.png' ,
            'cate'       => 0 ,
            'page_class' => 0
        ] , [
            'id'         => 3 ,
            'uniacid'    => 0 ,
            'aid'        => 0 ,
            'type'       => 1 ,
            'name'       => '商品活动页' ,
            'data'       => 'eyJwYWdlIjp7InR5cGUiOiIxIiwidGl0bGUiOiLllYblk4HmtLvliqjpobUiLCJuYW1lIjoi5ZWG5ZOB5rS75Yqo6aG1IiwiYmFja2dyb3VuZCI6IiNmM2YzZjMiLCJkaXltZW51IjoiLTEiLCJkaXlhZHYiOiItMSIsInBhZ2VDbGFzcyI6IjEifSwiaXRlbXMiOnsiTTE1NDI4NTQ5NDc4MzUiOnsibmF2X2NsYXNzIjoiMSIsImdyb3VwIjoic2VhcmNoIiwibmFtZSI6IuaQnOe0ouahhiIsInBhcmFtcyI6eyJwbGFjZWhvbGRlciI6Iuivt+i+k+WFpeWFs+mUruWtl+i/m+ihjOaQnOe0oiJ9LCJzdHlsZSI6eyJpbnB1dGJhY2tncm91bmQiOiIjZjNmM2YzIiwiYmFja2dyb3VuZCI6IiNmZmZmZmYiLCJpY29uY29sb3IiOiIjYjRiNGI0IiwiY29sb3IiOiIjOTk5OTk5IiwidGV4dGFsaWduIjoibGVmdCIsInNlYXJjaHN0eWxlIjoicm91bmQiLCJtYXJnaW5Cb3R0b20iOiIwIn0sImlkIjoic2VhcmNoIiwicGFnZUNsYXNzIjoiMSIsImdyb3VwX25hbWUiOiJzZWFyY2giLCJncm91cF9rZXkiOiIwIn0sIk0xNTQyODU1MDA4MTQ3Ijp7Im5hdl9jbGFzcyI6IjEiLCJuYW1lIjoi5Zu+54mH6L2u5pKtIiwic3R5bGUiOnsiZG90c3R5bGUiOiJyb3VuZCIsImRvdGFsaWduIjoiY2VudGVyIiwiYm90dG9tIjoiMCJ9LCJkYXRhIjp7IkMxNTQyODU1MDA4MTQ3Ijp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQxXC9IdXJqUlhSTndYeFBJWDMzNndJdVdwWHg2NXdvUkouanBnIiwibGlua3VybCI6IiJ9LCJDMTU0Mjg1NTAwODE0OCI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0MVwvUVYyaHZWMDhhbENIMmNIdTBjaFd2dXYwaGd2d0hWLmpwZyIsImxpbmt1cmwiOiIifX0sImlkIjoiYmFubmVyIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg3MzQwOTI4OSI6eyJuYXZfY2xhc3MiOiIxIiwiZ3JvdXAiOiJwaWN0dXJldyIsIm5hbWUiOiLlm77niYfmqbHnqpciLCJzdHlsZSI6eyJtYXJnaW5MZWZ0UmlnaHQiOiI1IiwibWFyZ2luVG9wQm90dG9tIjoiNSIsImJnQ29sb3IiOiIjRkZGRkZGIn0sImRhdGEiOnsiQzE1NDI4NzM0MDkyOTAiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDFcL1NyNjR4Z3p0WFpNbTdWcEdSSm03VFg1SVpKOEc3ai5qcGciLCJsaW5rdXJsIjoiIn0sIkMxNTQyODczNDA5MjkxIjp7ImltZ3VybCI6Ii4uXC9hZGRvbnNcL3dlbGlhbV9zbWFydGNpdHlcL3dlYlwvcmVzb3VyY2VcL2RpeVwvdGVtcGxhdGVcL2RlZmF1bHQxXC9DNzZkTlA3eHNkWHgwY1RDbFBnVGRabjNsMFMzMzcuanBnIiwibGlua3VybCI6IiJ9LCJDMTU0Mjg3MzQwOTI5MiI6eyJpbWd1cmwiOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9kaXlcL3RlbXBsYXRlXC9kZWZhdWx0MVwvWUJhN05ya3JBS2FabmpSNUg2ajZBNjJhSGwyS2prLmpwZyIsImxpbmt1cmwiOiIifSwiQzE1NDI4NzM0MDkyOTMiOnsiaW1ndXJsIjoiLi5cL2FkZG9uc1wvd2VsaWFtX3NtYXJ0Y2l0eVwvd2ViXC9yZXNvdXJjZVwvZGl5XC90ZW1wbGF0ZVwvZGVmYXVsdDFcL3ZvWklqaTY2RzdMT1Z2NUpQdVg3eXg3YnZKWUxpNi5qcGciLCJsaW5rdXJsIjoiIn19LCJpZCI6InBpY3R1cmV3IiwicGFnZUNsYXNzIjoiMSIsImdyb3VwX25hbWUiOiJwaWN0dXJldyIsImdyb3VwX2tleSI6IjAifSwiTTE1NDI4NzM0ODMzNzAiOnsibmF2X2NsYXNzIjoiMiIsIm5hbWUiOiLovoXliqnnur8iLCJzdHlsZSI6eyJiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsImJvcmRlciI6IiMwMDAwMDAiLCJwYWRkaW5nIjoiMCIsImxpbmVzdHlsZSI6InNvbGlkIiwibGVmdHBhZGRpbmciOiIyMCIsImJvcmRlcmNvbG9yIjoiI2YzZjNmMyJ9LCJpZCI6ImxpbmUiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyODczNTI2MTc0Ijp7Im5hdl9jbGFzcyI6IjIiLCJuYW1lIjoi6L6F5Yqp56m655m9Iiwic3R5bGUiOnsiaGVpZ2h0IjoiOCIsImJhY2tncm91bmQiOiIjZmZmZmZmIn0sImlkIjoiYmxhbmsiLCJwYWdlQ2xhc3MiOiIxIn0sIk0xNTQyODczNTM4MjE0Ijp7InBhcmFtcyI6eyJtYWluVGV4dCI6IuS6uuawlOaOqOiNkCIsInZpY2VUZXh0Ijoi5Lq65rCU54iG5ZOB5o6o6I2Q77yM5q+P5aSp5LiA5Liq5aW95b+D5oOF77yBIn0sInN0eWxlIjp7Im1hcmdpbkJvdHRvbSI6IjAiLCJiZ0NvbG9yIjoiI0ZGRkZGRiIsIm1haW5Db2xvciI6IiMwMDAwMDAiLCJtYWluQWxpZ24iOiJsZWZ0IiwidmljZUNvbG9yIjoiIzk5OTk5OSIsInZpY2VBbGlnbiI6ImxlZnQifSwiZ3JvdXBfbmFtZSI6InRpdGxlIiwiZ3JvdXBfa2V5IjoiMSIsImlkIjoidGl0bGUyIiwicGFnZUNsYXNzIjoiMSJ9LCJNMTU0Mjg2NzMzNDU5NyI6eyJwbHVnaW4iOiIyIiwicGFyYW1zIjp7InR5cGUiOiIxIiwib3JkZXJzIjoiMSIsImNsYXNzcyI6IjAiLCJzaG93X251bSI6IjEifSwic3R5bGUiOnsibWFyZ2luQm90dG9tIjoiMCJ9LCJkYXRhIjp7IkMwMTIzNDU2Nzg5MTAxIjp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImxvZ28iOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9pbWFnZXNcL2RlZmF1bHQucG5nIn0sIkMwMTIzNDU2Nzg5MTAyIjp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImxvZ28iOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9pbWFnZXNcL2RlZmF1bHQucG5nIn0sIk0xNTQyODk4NTQ2NTA4Ijp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImxvZ28iOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9pbWFnZXNcL2RlZmF1bHQucG5nIn0sIk0xNTQyODk4NTQ3MTg2Ijp7Imdvb2RzX25hbWUiOiLor7fpgInmi6nllYblk4EuLi4iLCJvbGRwcmljZSI6IjAwLjAwIiwicHJpY2UiOiIwMC4wMCIsInN0b3JlbmFtZSI6IuW6l+mTuuWQjeensC4uLiIsImxvZ28iOiIuLlwvYWRkb25zXC93ZWxpYW1fc21hcnRjaXR5XC93ZWJcL3Jlc291cmNlXC9pbWFnZXNcL2RlZmF1bHQucG5nIn19LCJncm91cF9uYW1lIjoiZ3JvdXBvbl9nb29kcyIsImdyb3VwX2tleSI6IjEiLCJpZCI6Imdyb3Vwb25fZ29vZHMyIiwicGFnZUNsYXNzIjoiMSJ9fX0=' ,
            'preview'    => '../addons/weliam_smartcity/web/resource/diy/template/default1/page_image15428995706201.png' ,
            'cate'       => 0 ,
            'page_class' => 0
        ] ];
        #1、判断是否存在 不存在则添加，存在则不进行任何操作
        foreach($data as $key => $val){
            $isHave = pdo_get(PDO_NAME."diypage_temp",['id'=>$val['id'],'data'=>$val['data']]);
            if(!$isHave){
                pdo_delete(PDO_NAME."diypage_temp",['id'=>$val['id']]);
                pdo_insert(PDO_NAME."diypage_temp",$val);
            }
        }
    }
    /**
     * Comment: 获取模板分类列表
     * Author: zzw
     */
    public static function cateList($pindex, $keyword) {
        global $_W;
        $psize = 10;
        $where = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        if (!empty($keyword)) {
            $where .= " AND name LIKE '%" . $keyword . "%' ";
        }
        $list = pdo_fetchall('SELECT * FROM '
            . tablename(PDO_NAME . 'diypage_temp_cate')
            . " WHERE {$where} ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename(PDO_NAME . 'diypage_temp_cate') . " WHERE {$where}");
        $pager = wl_pagination($total, $pindex, $psize);

        $data['list'] = $list;
        $data['pager'] = $pager;

        return $data;
    }
    /**
     * Comment: 信息验证
     * Author: zzw
     */
    public static function verify($id, $type, $tid) {
        global $_W, $_GPC;
        $result = array(
            'id'   => $id,
            'type' => $type,
        );
        //进行数据判断 获取对应的数据信息
        if ($id < 0 && $tid < 0) {
            //添加基本页面进行的操作
            if (!empty($type)) {
                //类型存在 获取页面类型信息
                $getpagetype = self::getPageType($type);
                $result['pagetype'] = $getpagetype['pagetype'];
            }
        } else if ($id >= 0 || $tid >= 0) {
            if ($tid >= 0) {
                //tid存在 获取模板数据
                $info = pdo_get(PDO_NAME . "diypage_temp", array('id' => $tid), array('id', 'data'));
            } else {
                //tid不存在 获取页面数据
                $info = pdo_get(PDO_NAME . "diypage", array('id' => $id), array('id', 'data'));
            }
            $info['data'] = base64_decode($info['data']);
            $result['data'] = json_decode($info['data'], TRUE);
            $getpagetype = self::getPageType($type);
            $result['pagetype'] = $getpagetype['pagetype'];
            if ($result['data']['page']['title'] == '圣诞主题首页') {
                unset($result['data']['items']['M1542621275922']);
            }
        }


        return $result;
    }
    /**
     * 获取页面类型
     * @param null $type
     * @return array|mixed
     */
    public static function getPageType($type = NULL) {
        //1=自定义页面;2=平台首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页
        $pagetype = array(
            1  => array('name' => '自定义', 'pagetype' => 'diy', 'class' => ''),
            2  => array('name' => '平台首页', 'pagetype' => 'sys', 'class' => 'success'),
            3  => array('name' => '抢购首页', 'pagetype' => 'sys', 'class' => 'primary'),
            4  => array('name' => '团购首页', 'pagetype' => 'plu', 'class' => 'warning'),
            5  => array('name' => '卡券首页', 'pagetype' => 'sys', 'class' => 'danger'),
            6  => array('name' => '拼团首页', 'pagetype' => 'plu', 'class' => 'info'),
            7  => array('name' => '砍价首页', 'pagetype' => 'plu', 'class' => 'danger'),
            8  => array('name' => '好店首页', 'pagetype' => 'plu', 'class' => 'success'),
        );
        if (!empty($type)) {
            return $pagetype[$type];
        }
        return $pagetype;
    }
    /**
     * Comment: 获取菜单
     * @param $id
     * @return bool
     */
    public static function getMenu($id, $datatype = false) {
        global $_W;
        $menu = pdo_get(PDO_NAME . 'diypage_menu', array('id' => $id));
        if (!empty($menu)) {
            $menu['data'] = json_decode(base64_decode($menu['data']), true);
            foreach ($menu['data']['data'] as $key => &$val) {
                $val['imgurl'] = tomedia($val['imgurl']);
                $val['default_img'] = tomedia($val['default_img']);
                $val['select_img'] = tomedia($val['select_img']);
            }
            if ($datatype) {

            }
        }
        return $menu;
    }
    /**
     * Comment: 获取广告
     * @param $id
     * @return bool
     */
    public static function getAdv($id, $datatype = false) {
        global $_W;
        $adv = pdo_get(PDO_NAME . 'diypage_adv', array('id' => $id));
        if (!empty($adv)) {
            $adv['data'] = json_decode(base64_decode($adv['data']), true);
            if ($datatype) {
                foreach ($adv['data']['data'] as $k => &$v) {
                    $v['imgurl'] = tomedia($v['imgurl']);
                }
            }
        }
        return $adv;
    }
    /**
     * Comment: 获取页面配置
     * @param $id
     * @return bool
     */
    public static function getPage($id, $datatype = false) {
        global $_W;
        $page = pdo_get(PDO_NAME . 'diypage', array('id' => $id));
        if (!empty($page)) {
            $page['data'] = json_decode(base64_decode($page['data']), true);
            if ($datatype) {
                $page['data']['items'] = self::getPageInfo($page['data']['items'], $id, $page['type']);
            }
        }
        return $page;
    }
    /**
     * Comment: 完善页面的配置信息
     * Author: zzw
     * Date: 2019/9/11 10:59
     * @param $info array 原始的页面配置信息
     * @param $id
     * @param $type
     * @return mixed
     */
    public static function getPageInfo($info, $id, $type) {
        global $_W, $_GPC;
        //通过循环进行每一个组件的单独处理
        foreach ($info as $k => &$v) {
            $v = self::handlePageItem($v);
        }
        //兼容处理1、将默认图片的相对路径 修改为绝对路径
        $defaultImg = '../addons/weliam_smartcity/web/resource/images/default.png';//默认图片路径
        $jsonImg = trim(json_encode(tomedia($defaultImg)),'"');//绝对路径地址
        $jsonDefault = trim(json_encode($defaultImg),'"');//相对路径地址
        $jsonInfo = json_encode($info);//当前信息数组json转码后的信息数组
        //匹配替换内容
        $info = json_decode(str_replace($jsonDefault,$jsonImg,$jsonInfo),true);

        return $info;
    }
    /**
     * Comment: 判断广告是否过期  过期返回空
     * Author: zzw
     */
    public static function BeOverdue($id, $state = true) {
        $advdata = self::getAdv($id, TRUE);
        $name = 'advstate_' . $advdata['id'];
        $advstate = Util::getCookie($name);
        //判断当前广告是否已被查看
        if ($advdata['data']['params']['showtype'] == 1 && $state) {
            if ($advstate['endtime']) {
                return;
            } else {
                $time = ($advdata['data']['params']['showtime']) * 60;
                $endtime = time() + $time;
                Util::setCookie($name, array('endtime' => $endtime), $time);
            }
        } else if ($advstate) {
            Util::setCookie($name, array('endtime' => time()), 0);
        }
        $advdata['data']['id'] = $id;
        return $advdata;
    }
    /**
     * Comment: 进入模板，页面编辑页面的公共操作
     * Author: zzw
     */
    public static function getCommon() {
        global $_W;
        //公众号插件判断
        $isAuth = Customized::init('diy_userInfo');
        $plugin = [
            'groupon'      => agent_p('groupon') ? 1 : 0 ,
            'fightgroup'   => agent_p('wlfightgroup') ? 1 : 0 ,
            'coupon'       => agent_p('wlcoupon') ? 1 : 0 ,
            'rush'         => agent_p('rush') ? 1 : 0 ,
            'bargain'      => agent_p('bargain') ? 1 : 0 ,
            'activity'     => agent_p('activity') ? 1 : 0 ,
            'recruit'      => agent_p('recruit') ? 1 : 0 ,
            'dating'       => agent_p('dating') ? 1 : 0 ,
            'citydelivery' => agent_p('citydelivery') ? 1 : 0 ,
            'housekeep'    => agent_p('housekeep') ? 1 : 0 ,
            'pocket'       => agent_p('pocket') ? 1 : 0 ,
            'house'        => agent_p('house') ? 1 : 0 ,
            'user_info'    => $isAuth ? 1 : 0,
        ];
        $hasplugins = json_encode($plugin);
        //获取商品的分类信息
        $goodsWhere = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        $goodsListW = ['aid' => $_W['aid'] , 'uniacid' => $_W['uniacid']];
        $goodCate['rush']['list'] = pdo_getall(PDO_NAME . "rush_category" , $goodsListW , ['id' , 'name']);
        $goodCate['rush']['count'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE {$goodsWhere} AND status IN (1,2)");
        $goodCate['groupon']['list'] = pdo_getall(PDO_NAME . "groupon_category", $goodsListW, array('id', 'name'));
        $goodCate['groupon']['count'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE {$goodsWhere} AND status IN (1,2) ");
        $goodCate['fightgroup']['list'] = pdo_getall(PDO_NAME . "fightgroup_category", $goodsListW, array('id', 'name'));
        $goodCate['fightgroup']['count'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE {$goodsWhere} AND status = 1");
        $goodCate['coupon']['list'] = [];
        $goodCate['coupon']['count'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "couponlist") . " WHERE {$goodsWhere} AND status = 1");
        $goodCate['bargain']['list'] = pdo_getall(PDO_NAME . "bargain_category", $goodsListW, array('id', 'name'));
        $goodCate['bargain']['count'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE {$goodsWhere} AND status IN (1,2)");
        $goodCate['activity']['list']  = pdo_getall(PDO_NAME . "activity_category" , array_merge($goodsListW,['status'=>1]) , ['id' , 'name']);
        $goodCate['activity']['count'] = pdo_count(PDO_NAME . "activity_category",array_merge($goodsListW,['status'=>1]));
        $goodCate['integral']['list']  = pdo_getall(PDO_NAME . "consumption_category" , ['uniacid'=>$_W['uniacid'],'status'=>1], ['id' , 'name']);
        $goodCate['integral']['count'] = pdo_count(PDO_NAME . "consumption_category", ['uniacid'=>$_W['uniacid'],'status'=>1]);
        //配送商品分类
        $sql = "SELECT a.id,concat(b.storename,'-',a.name) as name FROM ".tablename(PDO_NAME."delivery_category")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.sid = b.id WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 1 ORDER BY a.sid DESC ";
        $goodCate['citydelivery']['list']  = pdo_fetchall($sql);
        $goodCate['citydelivery']['count'] = pdo_count(PDO_NAME . "delivery_category", ['uniacid'=>$_W['uniacid'],'status'=>1]);
        //获取行业一级分类
        $recruitWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>0];
        $goodCate['recruit']['list']  = pdo_getall(PDO_NAME."recruit_industry",$recruitWhere,['id','title']);
        $goodCate['recruit']['count']  = pdo_count(PDO_NAME."recruit_industry",$recruitWhere);
        //获取商户分类
        $goodCate['store']['list'] = pdo_getall(PDO_NAME . 'category_store' , ['uniacid' => $_W['uniacid'] , 'aid' => $_W['aid'] , 'enabled' => 1, 'state' => '0'],['id','name'],'','parentid ASC,displayorder DESC');
        $goodCate['store']['count'] = pdo_count(PDO_NAME . 'category_store' , ['uniacid' => $_W['uniacid'] , 'aid' => $_W['aid'] , 'enabled' => 1, 'state' => '0']);
		//获取掌上信息分类
		$goodCate['pocket']['list']  = pdo_getall(PDO_NAME . "pocket_type" , ['uniacid'=>$_W['uniacid'],'status'=>1,'aid' => $_W['aid'],'type'=> 0], ['id' , 'title']);
        $goodCate['pocket']['count'] = pdo_count(PDO_NAME . "pocket_type", ['uniacid'=>$_W['uniacid'],'status'=>1,'aid' => $_W['aid'],'type'=> 0]);
        

        //获取社群信息
        $communityInfo        = pdo_getall(PDO_NAME . "community",['uniacid'=>$_W['uniacid']],['id','communname']);

        $data['hasplugins'] = $hasplugins;
        $data['goodCate'] = $goodCate;
        $data['community_list'] = $communityInfo;

        return $data;
    }
    /**
     * Comment: 根据条件获取装修功能中的商品信息
     * Author: zzw
     * @param $params  商品类型
     * @param $data    当前的商品数据
     * @param $plugin  商品类型  1=抢购  2=团购  3=拼团  5=优惠券
     * @return array
     */
    public static function getDiyGoods($params, $plugin) {
        global $_W;
        /** params 属性详情
         * type:1,//1=手动选择 2=选择分类 3=选择状态
         * orders:1,//1=综合  2=按销量  3=价格降序  4=价格升序
         * classs:0,//按商品分类选择时,商品分类id
         * status:1,//按状态选择时，商品状态
         * show_num:1,//显示的商品数量
         * plugin: 商品信息  0=全部 1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品
         */
        //获取基本查询条件
        $where = " aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        //查询数量
        $limit = " LIMIT 0,{$params['show_num']} ";
        if ($params['type'] == 2) {
            //按分类查询商品
            switch ($plugin) {
                case 1:
                    $where .= " AND status IN (1,2)  ";
                    if($params['classs'] > 0 ) $where .= " AND cateid = {$params['classs']}  ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE `activityid` = goods_id  AND status IN (0,1,2,3,4,6,8,9)),0) + allsalenum) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE {$where}{$order}{$limit}");
                    break;//抢购商品
                case 2:
                    $where .= " AND status IN (1,2) ";
                    if($params['classs'] > 0 ) $where .= " AND cateid = {$params['classs']} ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE `fkid` = goods_id AND plugin = 'groupon' AND status IN (0,1,2,3,4,6,8,9)),0) + falsesalenum) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }

                    $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "groupon_activity") . " WHERE {$where}{$order}{$limit}");
                    break;//团购商品
                case 3:
                    $where .= " AND status IN (1,2) ";
                    if($params['classs'] > 0 ) $where .= " AND categoryid = {$params['classs']} ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY listorder DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL(realsalenum,0) + falsesalenum) DESC";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT id FROM " . tablename(PDO_NAME . "fightgroup_goods") . " WHERE {$where}{$order}{$limit}");
                    break;//拼团商品
                case 4:
                    $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";
                    $where .= " AND a.status = 1 AND b.id > 0 AND b.enabled = 1 ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY a.sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (SELECT COUNT(*) FROM " . tablename(PDO_NAME . "timecardrecord") . " WHERE `activeid` = a.id AND type = 2) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY a.price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY a.price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY a.id DESC ";
                            break;//创建时间
                    }
                    $sql = "SELECT a.id FROM " . tablename(PDO_NAME . "package")
                        . " as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
                        ." as b ON a.merchantid = b.id WHERE {$where}{$order}{$limit}";
                    $info = pdo_fetchall($sql);
                    break;//大礼包
                case 5:
                    $where .= "  AND status = 2 AND is_show = 0 ";
                    if($params['classs'] > 0 ) $where .= " AND type = {$params['classs']} ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY indexorder DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY surplus DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }

                    $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "couponlist") . " WHERE {$where}{$order}{$limit}");
                    break;//优惠券
                case 6:
                    $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";
                    $where .= " AND a.status = 1 AND b.id > 0 AND b.enabled = 1 ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY a.sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (SELECT COUNT(*) FROM " . tablename(PDO_NAME . "timecardrecord") . " WHERE `activeid` = a.id AND type = 1) DESC ";
                            break;//销量排序
                        case 3:
                            //$order = " ORDER BY activediscount DESC ";
                            break;//价格降序
                        case 4:
                            //$order = " ORDER BY activediscount ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY a.id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT a.id FROM " . tablename(PDO_NAME . "halfcardlist") . " as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")." as b ON a.merchantid = b.id WHERE {$where}{$order}{$limit}");
                    break;//折扣卡
                case 7:
                    $where .= " AND status IN (1,2) ";
                    if($params['classs'] > 0 ) $where .= " AND cateid = {$params['classs']}  ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE `fkid` = goods_id AND plugin = 'bargain' AND status IN (0,1,2,3,4,6,8,9)),0)) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY oldprice DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY oldprice ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "bargain_activity") . " WHERE {$where}{$order}{$limit}");
                    break;//砍价商品
                case 8:
                    $where = " status = 1 AND uniacid = {$_W['uniacid']} ";
                    if($params['classs'] > 0 ) $where .= " AND category_id = {$params['classs']}  ";
                    //根据orders获取商品的排序规则  3=推荐  4=人气
                    switch ($params['orders']) {
                        case 3:
                            $order = " ORDER BY id DESC ";
                            break;//推荐
                        case 4:
                            $order = " ORDER BY pv DESC ";
                            break;//人气
                    }
                    $info = pdo_fetchall("SELECT id,id as goods_id FROM "
                        . tablename(PDO_NAME . "consumption_goods")
                        . " WHERE {$where}{$order}{$limit}");
                    break;//积分商品
                case 9:
                    $where .= " AND status IN (1,2) ";
                    if($params['classs'] > 0 ) $where .= " AND cateid = {$params['classs']}  ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE `fkid` = goods_id AND plugin = 'activity' AND status IN (0,1,2,3,4,6,8,9)),0)) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "activitylist") . " WHERE {$where}{$order}{$limit}");
                    break;//同城活动
                case 10:
                    $where .= " AND status = 2 ";
                    if($params['classs'] > 0 ) $where .= " AND cateid = {$params['classs']}  ";
                    //根据orders获取商品的排序规则
                    switch ($params['orders']) {
                        case 1:
                            $order = " ORDER BY sort DESC ";
                            break;//默认排序
                        case 2:
                            $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE `fkid` = goods_id AND plugin = 'citydelivery' AND status IN (0,1,2,3,4,6,8,9)),0)) DESC ";
                            break;//销量排序
                        case 3:
                            $order = " ORDER BY price DESC ";
                            break;//价格降序
                        case 4:
                            $order = " ORDER BY price ASC ";
                            break;//价格升序
                        case 5:
                            $order = " ORDER BY id DESC ";
                            break;//创建时间
                    }
                    $info = pdo_fetchall("SELECT id,id as goods_id FROM "
                        . tablename(PDO_NAME . "delivery_activity")
                        . " WHERE {$where}{$order}{$limit}");
                    break;//配送商品
            }
        } else {
            //根据状态获取商品信息  仅抢购商品
            $where .= " AND status = {$params['status']} ";
            //根据orders获取抢购商品的排序规则
            switch ($params['orders']) {
                case 1:
                    $order = " ORDER BY sort DESC ";
                    break;//默认排序
                case 2:
                    $order = " ORDER BY (IFNULL((SELECT SUM(num) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE `activityid` = goods_id  AND status IN (0,1,2,3,4,6,8,9)),0) + allsalenum) DESC ";
                    break;//销量排序
                case 3:
                    $order = " ORDER BY price DESC ";
                    break;//价格降序
                case 4:
                    $order = " ORDER BY price ASC ";
                    break;//价格升序
                case 5:
                    $order = " ORDER BY id DESC ";
                    break;//创建时间
            }
            $info = pdo_fetchall("SELECT id,id as goods_id FROM " . tablename(PDO_NAME . "rush_activity") . " WHERE {$where}{$order}{$limit}");
        }
        return $info;
    }
    //用平台设置覆盖代理设置
    public static function checkuniac($agentset, $wlset) {
        if (empty($agentset)) {
            $agentset = $wlset;
        } else {
            foreach ($agentset as $key => &$v) {
                if (empty($v)) {
                    $v = $wlset[$key];
                }
            }
        }
        return $agentset;
    }
    /**
     * Comment: 获取选项卡分类设置信息
     * Author: zzw
     * Date: 2019/12/20 16:41
     * @param $params
     * @param $plugin
     * @return mixed
     */
    public static function getGoodsCateInfo($params,$plugin){
        global $_W;
        #1、根据类型获取分类列表
        $where = " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        switch ($plugin) {
            case 'rush':
                $classList = pdo_fetchall("SELECT id,`name`,thumb FROM ".tablename(PDO_NAME."rush_category")
                    .$where." ORDER BY sort DESC ");
                $set = Setting::agentsetting_read("rush");
                break;//抢购
            case 'groupon':
                $classList = pdo_fetchall("SELECT id,`name`,thumb FROM ".tablename(PDO_NAME."groupon_category")
                    .$where." ORDER BY sort DESC ");
                $set = Setting::agentsetting_read("groupon");
                break;//团购
            case 'wlfightgroup':
                $classList = pdo_fetchall("SELECT id,`name`,logo as thumb FROM ".tablename(PDO_NAME."fightgroup_category")
                    .$where." ORDER BY listorder DESC ");
                $set = Setting::agentsetting_read("fightgroup");
                break;//拼团
            case 'bargain':
                $classList = pdo_fetchall("SELECT id,`name`,thumb FROM ".tablename(PDO_NAME."bargain_category")
                    .$where." ORDER BY sort DESC ");
                $set = Setting::agentsetting_read("bargainset");
                break;//砍价
            case 'activity':
                $classList = pdo_fetchall("SELECT id,`name`,logo as thumb FROM "
                    .tablename(PDO_NAME."activity_category")
                    .$where." AND status = 1 ORDER BY sort DESC ");
                $set = Setting::agentsetting_read("activity");
                break;//砍价
            case 'pocket':
                $where .= " AND status = 1 AND type = 0 AND isnav = 0 ";
                $classList = pdo_fetchall("SELECT id,title FROM ".tablename(PDO_NAME."pocket_type")
                    .$where." ORDER BY sort DESC ");
                break;//掌上信息
        }
        #2、循环处理分类信息
        foreach($classList as $classKey => &$classVal){
            $classVal['thumb'] = !empty($classVal['thumb']) ? tomedia($classVal['thumb']) : '';
        }
        #3、数组拼装
        $params['class_list'] = is_array($classList) ? $classList : [];
        $params['class_img_switch'] = $set['img_switch'] ? : 0;

        return $params;
    }

    /**
     * Comment: 根据type获取对应页面的参数配置信息
     * Author: zzw
     * @param $type
     * @param $page_id
     * @return array
     */
    public static function getPageParams($type,$page_id){
        global $_W,$_GPC;
        //获取页面配置信息
        $settings = Setting::agentsetting_read('diypageset');//装修设置信息
        switch ($type) {
            case 1:
                #2、页面配置信息获取
                $pageset = Diy::newGetPage($page_id , true);
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                $advId    = $pageInfo['diyadv'];//广告id
                break;//自定义页面
            case 2:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_index'];
                if ($id > 0) $pageset = Diy::newGetPage($id);
                else $pageset = DiyPage::getHomePageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_index'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//商城首页
            case 3:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_rush'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getRushPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_rush'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//抢购首页
            case 4:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_groupon'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getGroupPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_groupon'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//团购首页
            case 5:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_wlcoupon'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getCouponPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_wlcoupon'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//卡券首页
            case 6:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_wlfightgroup'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getFightPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_wlfightgroup'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//拼团首页
            case 7:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_bargain'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getBargainPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_bargain'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//砍价首页
            case 8:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_shop'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getShopPageDefaultInfo();
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//好店首页
            case 13:
                //判断是否设置首页信息 未设置获取默认信息
                $id = $settings['page_card'];
                if ($id > 0) $pageset = Diy::newGetPage($id , true);
                if (!$pageset) $pageset = DiyPage::getCardPageDefaultInfo();
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//名片首页
            case 14:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_activity'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getActivityPageDefaultInfo();
                //其他信息获取
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//活动首页
            case 15:
                //判断是否设置信息 未设置获取默认信息
                $id = $settings['page_recruit'];
                if ($id > 0) $pageset = Diy::getPage($id , true);
                if (!$pageset) $pageset = DiyPage::getRecruitPageDefaultInfo();
                //其他信息获取
                $advId    = $settings['adv_recruit'];//广告id
                $pageInfo = $pageset['data']['page'];//本页面标题信息
                break;//招聘首页
        }

        return [$advId , $pageInfo , $pageset];
    }
    /**
     * Comment: 获取当前页面基本信息
     * Author: zzw
     * @param     $id
     * @param int $type
     * @return array|bool|mixed
     */
    public static function newGetPage($id,$type = 1){
        global $_W;
        $page = pdo_get(PDO_NAME . 'diypage' , ['id' => $id]);
        if (!empty($page)) $page['data'] = json_decode(base64_decode($page['data']), true);

        return is_array($page) ? $page : [];
    }
    /**
     * Comment: 处理某个组件的基本信息
     * Author: zzw
     * @param array $v
     * @return array
     */
    public static function handlePageItem(array $v){
        global $_W, $_GPC;
        //TODO：这里需要重构  修改为switch判断  并且删除已经没有使用的判断操作  同时为了保证数据的一致，手动选择的内容需要重新获取一下对应的数据
        //参数获取
        $lng = $_GPC['lng'] ? : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? : 0;//用户当前所在纬度
        //循环data 进行链接的处理
        if ($v['data']) {
            foreach ($v['data'] as $img_key => &$img_val) {
                $img_val['imgurl'] = tomedia($img_val['imgurl']);
            }
        }
        //获取当前时间戳信息
        if($v) $v['currentTime'] = time();
        //根据组件进行其单独的操作
        $goodsArr = ['rush_goods','groupon_goods','coupon_goods','fightgroup_goods','packages','discount_card',
            'bargain_goods','activity_goods','integral_goods','citydelivery_goods'];
        if (($v['group_name'] && in_array($v['group_name'], $goodsArr)) || $v['group_name'] == 'public_goods') {
            //根据条件获取商品信息&& $v['plugin'] != 4 && $v['plugin'] != 6
            if ($v['params']['type'] != 1 && $v['plugin'] != 0 ) $v['data'] = self::getDiyGoods($v['params'], $v['plugin']);
            $publicPlugin = $v['plugin'];
            //商品信息  0=全部 1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品 9=同城活动 10=配送商品
            foreach ($v['data'] as $goods_key => $goods_val) {
                //删除无商品id的商品信息
                if (!$goods_val['id']) {
                    unset($v['data'][$goods_key]);
                    continue;
                }
                //商品组件为公共组件时 重置plugin的值
                if ($publicPlugin == 0) {
                    switch ($goods_val['plugin']) {
                        case 'rush':
                            $v['plugin'] = 1;
                            break;//抢购商品
                        case 'groupon':
                            $v['plugin'] = 2;
                            break;//团购商品
                        case 'wlfightgroup':
                            $v['plugin'] = 3;
                            break;//拼团商品
                        /*case 'pocket':
                             $v['plugin'] = 4;
                             break;//大礼包*/
                        case 'coupon':
                            $v['plugin'] = 5;
                            break;//优惠券
                        /*case 'halfcard':
                            $v['plugin'] = 5;
                            break;//折扣卡*/
                        case 'bargain':
                            $v['plugin'] = 7;
                            break;//砍价
                        case 'integral':
                            $v['plugin'] = 8;
                            break;//积分商品
                        case 'activity':
                            $v['plugin'] = 9;
                            break;//同城活动
                        case 'citydelivery':
                            $v['plugin'] = 10;
                            break;//同城配送
                    }
                }
                //获取最新的商品信息
                if($v['plugin'] == 1 || $v['plugin'] == 2 || $v['plugin'] == 3){
                    $v['data'][$goods_key] = WeliamWeChat::getNewHomeGoods($v['plugin'], $goods_val['id']);
                }else{
                    $v['data'][$goods_key] = WeliamWeChat::getHomeGoods($v['plugin'], $goods_val['id']);
                }
                if($v['id'] == 'rush_goods3' || $v['id'] == 'groupon_goods3' || $v['id'] == 'bargain_goods3' || $v['id'] == 'fightgroup_goods4' || $v['id'] == 'public_goods2'){
                    foreach($v['data'] as &$wlfg){
                        $wlfg['logo'] = $wlfg['long_logo'];
                    }
                }else if($v['id'] == 'integral_goods3'){
                    foreach($v['data'] as &$wlfg){
                        $wlfg['thumb'] = $wlfg['long_logo'];
                    }
                }
                //判断当前商品是否允许显示  目前仅显示  未开始=1；进行中=2；已结束=3；已抢完=7;
                $notStatus = [4,6,8];//忽略状态判断
                if(!in_array($v['data'][$goods_key]['status'],[1,2,3,7]) && !in_array($v['plugin'],$notStatus)){
                    unset($v['data'][$goods_key]);
                    continue;
                }
                //获取商品详情链接   1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品
                switch ($v['plugin']) {
                    case 1:
                        $url = h5_url('pages/subPages/goods/index',['id'=>$goods_val['id'],'type'=>1]);
                        break;//抢购商品
                    case 2:
                        $url = h5_url('pages/subPages/goods/index',['id'=>$goods_val['id'],'type'=>2]);
                        break;//团购商品
                    case 3:
                        $url = h5_url('pages/subPages/goods/index',['id'=>$goods_val['id'],'type'=>3]);
                        break;//拼团商品
                    case 4:
                        $url = h5_url('pages/mainPages/memberCard/memberCard',['id'=>$goods_val['id'],'type'=>4]);
                        break;//大礼包
                    case 5:
                        $url = h5_url('pages/subPages/goods/index',['id'=>$goods_val['id'],'type'=>5]);
                        break;//优惠券
                    case 6:
                        $url = h5_url('pages/mainPages/memberCard/memberCard',['id'=>$goods_val['id'],'type'=>6]);
                        unset($v['data'][$goods_key]['card']);
                        break;//折扣卡
                    case 7:
                        $url = h5_url('pages/subPages/goods/index',['id'=>$goods_val['id'],'type'=>7]);
                        break;//砍价商品
                    case 8:
                        $url = h5_url('pages/subPages/goods/index',['goods_id'=>$goods_val['id'],'goodsType'=>'integral']);
                        break;//积分商品
                    case 9:
                        $url = $v['data'][$goods_key]['threeurl'] ? : h5_url('pages/subPages2/coursegoods/coursegoods',['id'=>$goods_val['id'],'type'=>9]);
                        break;//同城活动
                    case 10:
                        $url = $v['data'][$goods_key]['threeurl'] ? : h5_url('pages/subPages2/businessCenter/foodIntroduced/foodIntroduced',['id'=>$goods_val['id'],'type'=>10]);
                        break;//配送商品
                }
                $v['data'][$goods_key]['url'] = $url;
                //商品类型为抢购/团购商品时 获取抢购结束倒计时
                if ($v['plugin'] == 1 || $v['plugin'] == 2) {
                    $time = time();
                    $v['data'][$goods_key]['goods_state'] = 0;//抢购/团购 活动进行中
                    if ($v['data'][$goods_key]['starttime'] > $time) {
                        $v['data'][$goods_key]['goods_state'] = 1;//抢购/团购 活动未开始
                    } else if ($v['data'][$goods_key]['endtime'] < $time) {
                        $v['data'][$goods_key]['goods_state'] = 2;//抢购/团购 活动已结束
                    }
                }
                //当商品信息中带有sid时添加店铺链接
                if ($v['data'][$goods_key]['sid']) {
                    $v['data'][$goods_key]['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$v['data'][$goods_key]['sid']]);
                    $v['data'][$goods_key]['distance'] = Store::shopLocation($v['data'][$goods_key]['sid'], $lng, $lat);
                }
            }
            //没有商品信息是删除当前信息
            if (count($v['data']) == 0) $v = [];
            //处理配送商品的top图片
            if($v['id']=='citydelivery_goods2' ||$v['id']=='citydelivery_goods3' || $v['id']=='citydelivery_goods4'){
                $v['style']['top_image'] = tomedia($v['style']['top_image']);
            }
            //格式化下标信息
            $v['data'] = array_values($v['data']);
        } else if ($v['group_name'] && $v['group_name'] == 'headline') {
            //头条信息
            if($v['style']['type'] == 1){
                foreach ($v['data'] as $line_key => $line_val) {
                    $v['data'][$line_key] = WeliamWeChat::getHomeLine($line_val['id']);
                    if(!$v['data'][$line_key]) unset($v['data'][$line_key]);
                }
            }else if($v['style']['type'] == 2){
                $v['data'] = [];
                $headlinelist = pdo_fetchall("SELECT id,title,summary,display_img,author,author_img,browse,one_id,two_id FROM "
                    . tablename(PDO_NAME . "headline_content")
                    . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ORDER BY release_time DESC limit {$v['style']['show_num']}  ");
                foreach ($headlinelist as $line_key => $headinfo){
                    $headinfo['display_img'] = tomedia($headinfo['display_img']);
                    $headinfo['author_img'] = tomedia($headinfo['author_img']);
                    $headinfo['one_name'] = pdo_getcolumn(PDO_NAME . 'headline_class', ['id' => $headinfo['one_id']], 'name') ? : '';
                    $headinfo['two_name'] = pdo_getcolumn(PDO_NAME . 'headline_class',['id' => $headinfo['two_id']], 'name') ? : '';
                    unset($headinfo['one_id']);
                    unset($headinfo['two_id']);
                    $v['data'][$line_key] = $headinfo;
                }
            }
        } else {
            //其他信息
            //获取图片魔方的高
            if ($v['id'] == 'magic_cube') {
                $min_w = get_object_vars($v['style'])['min_w'];
                $height = 0;
                foreach ($v['data'] as $cube_key => &$cube_val) {
                    if ($height < ($cube_val['top'] + $cube_val['height'])) {
                        $height = $cube_val['top'] + $cube_val['height'];
                    }
                }
                $v['height'] = $height;
            }
            //组件为公告组件时获取公告信息
            if ($v['group_name'] == 'notice' || $v['id'] == 'notice') {
                $notice = pdo_getall(PDO_NAME . "notice", array('aid' => $_W['aid'], 'uniacid' => $_W['uniacid'], 'enabled' => 1), array('id', 'title', 'link'));
                foreach ($notice as $notice_k => &$notice_v) {
                    if (!$notice_v['link']) {
                        $notice_v['link'] = h5_url('pages/mainPages/noticeDetail/noticeDetail', ['id' => $notice_v['id']]);
                    }
                }
                $v['data'] = $notice;
            }
            //组件为社群时 获取社群信息  如果社群id不存在 删除社群信息
            if ($v['id'] == 'community') {
                if ($v['params']['community_id']) $v['params'] = Commons::getCommunity($v['params']['community_id'],$v['params']['title']);
                else $v = [];
            }
            //组件为商户组件时 获取商户的信息
            if (($v['group_name'] == 'shop' || $v['id'] == 'shop')) {
                if ($v['params']['type'] == 2) {
                    //商户为自动选择规则  从新定义商户信息数组  1 = 创建时间，2 = 店铺距离，3 = 默认设置，4 = 浏览人气
                    $getShopWhere = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2 AND enabled = 1 ";
                    if($v['params']['classs'] > 0){
                        $parentid = pdo_getcolumn(PDO_NAME.'category_store',array('id'=>$v['params']['classs']),'parentid');
                        if($parentid > 0){
                            $storeids = pdo_getall('wlmerchant_merchant_cate', ['twolevel' => $v['params']['classs']], array('sid'), 'sid');
                        }else{
                            $storeids = pdo_getall('wlmerchant_merchant_cate', ['onelevel' => $v['params']['classs']], array('sid'), 'sid');
                        }
                        $getShopWhere .= " AND id in (" . implode(',', array_keys($storeids)) . ") ";
                    }
                    switch ($v['params']['rule']) {
                        case 1:
                            $getShopWhere .= " ORDER BY createtime DESC LIMIT {$v['params']['show_num']} ";
                            break;
                        case 3:
                            $getShopWhere .= " ORDER BY listorder DESC,id DESC LIMIT {$v['params']['show_num']} ";
                            break;
                        case 4:
                            $getShopWhere .= " ORDER BY pv DESC  LIMIT {$v['params']['show_num']} ";
                            break;
                    }
                    $v['data'] = pdo_fetchall("SELECT id FROM " . tablename(PDO_NAME . "merchantdata") . $getShopWhere);
                }
                //获取最新的商户信息
                foreach ($v['data'] as $shop_key => &$shop_val) {
                    $v['data'][$shop_key] = pdo_get(PDO_NAME . "merchantdata", array('id' => $shop_val['id']),
                        array('id', 'storename', 'logo', 'listorder','address', 'location', 'storehours', 'pv', 'score', 'panorama','videourl', 'tag'));
                    //获取店铺分类信息
                    $v['data'][$shop_key]['panorama'] = !empty($v['data'][$shop_key]['panorama']) ? 1 : 0;
                    $v['data'][$shop_key]['videourl'] = !empty($v['data'][$shop_key]['videourl']) ? 1 : 0;
                    //商家标签
                    $v['data'][$shop_key]['tags'] = [];
                    $tagids = unserialize($v['data'][$shop_key]['tag']);
                    if (!empty($tagids)) {
                        $tags = pdo_getall('wlmerchant_tags', array('id' => $tagids), array('title'));
                        $v['data'][$shop_key]['tags'] = $tags ? array_column($tags, 'title') : [];
                    }
                    unset($v['data'][$shop_key]['tag']);
                    //获取店铺当月销量  评分
                    $v['data'][$shop_key]['salesVolume'] = Store::getShopSales($v['data'][$shop_key]['id'], 1, 0);
                    $v['data'][$shop_key]['score'] = sprintf("%.1f", $v['data'][$shop_key]['score']);
                    unset($v['data'][$shop_key]['onelevel']);
                    unset($v['data'][$shop_key]['twolevel']);
                    //商户信息不存在 该商户已被删除
                    if (!$shop_val || !$v['data'][$shop_key]) {
                        unset($v['data'][$shop_key]);
                        continue;
                    }
                    $v['data'][$shop_key]['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$shop_val['id']]);
                }
                //对商户进行排序 按人气排序
                if ($v['params']['type'] == 1) {
                    $v['data'] = Store::getstores($v['data'], $lng, $lat, 5);
                } else {
                    $shopSortRule = $v['params']['rule'] ? $v['params']['rule'] : 3;
                    $v['data'] = Store::getstores($v['data'], $lng, $lat, $shopSortRule);
                    if($v['params']['rule'] == 2 && $v['params']['type'] == 2){
                        //店铺距离排序
                        $v['data'] = array_slice($v['data'],0,$v['params']['show_num']);
                    }
                }
                foreach ($v['data'] as $delLocationK => &$delLocationV) {
                    unset($delLocationV['location']);
                }
                //店铺风格一 获取店铺商品活动信息
                if ($v['id'] == 'shop') {
                    $v['data'] = WeliamWeChat::getStoreList($v['data']);
                }
                //店铺风格五 顶部图片处理
                if($v['id'] == 'shop5') $v['style']['top_image'] = tomedia($v['style']['top_image']);

                //判断没有内容的商户 删除
                foreach($v['data'] as $dataKey => &$dataItem){
                    if(!$dataItem['id']) unset($v['data'][$dataKey]);
                }
            }
            //选项卡组件
            if (($v['group_name'] == 'options' || $v['id'] == 'options')) {
                //排序操作
                $keys = array_keys($v['data']);
                $optionsData = $v['data'][$keys[0]];
                if(!array_key_exists('order',$optionsData)){
                    $option_sort = array_column($v['data'], 'sort');
                    array_multisort($option_sort, SORT_ASC, $v['data']);
                }
                if ($v['id'] == 'options') {
                    //风格一
                    $v['params'] = [];
                    foreach($v['data'] as $selectKey => &$selectVal){
                        //添加请求接口信息
                        switch ($selectVal['type']) {
                            case 'store':$selectVal['request_link'] = 'p=store&do=homeList';break;//商家
                            case 'rush':$selectVal['request_link'] = 'p=rush&do=homeList';break;//抢购
                            case 'coupon':$selectVal['request_link'] = 'p=wlcoupon&do=homeList';break;//卡券
                            case 'halfcard':$selectVal['request_link'] = 'p=halfcard&do=homeList';break;//特权
                            case 'fight':$selectVal['request_link'] = 'p=wlfightgroup&do=homeList';break;//拼团
                            case 'pocket':$selectVal['request_link'] = 'p=pocket&do=infoList';break;//同城
                            case 'groupon':$selectVal['request_link'] = 'p=groupon&do=homeList';break;//团购
                            case 'goupon':$selectVal['request_link'] = 'p=groupon&do=homeList';break;//团购  兼容没有带r的参数
                            case 'bargain':$selectVal['request_link'] = 'p=bargain&do=homeList';break;//砍价
                            case 'consumption':$selectVal['request_link'] = 'p=consumption&do=homeList';break;//积分
                            case 'package':$selectVal['request_link'] = 'p=halfcard&do=packageList';break;//礼包
                            case 'activity':$selectVal['request_link'] = 'p=activity&do=homeList';break;//活动
                            case 'recruit':$selectVal['request_link'] = 'p=recruit&do=homeList';break;//招聘
                        }
                        //判断是否显示当前选项  不显示则删除
                        if($selectVal['status'] == 0) unset($v['data'][$selectKey]);
                    }
                } else if ($v['id'] == 'options2') {
                    //风格二
                    //获取商品分类信息
                    $v['params'] = self::getGoodsCateInfo($v['params'],$v['params']['goods_type']);
                    foreach($v['data'] as $selectKey => &$selectVal){
                        switch ($v['params']['goods_type']) {
                            case 'rush':
                                $selectVal['request_link'] = 'p=rush&do=homeList';
                                break;//抢购
                            case 'groupon':
                                $selectVal['request_link'] = 'p=groupon&do=homeList';
                                break;//团购
                            case 'wlfightgroup':
                                $selectVal['request_link'] = 'p=wlfightgroup&do=homeList';
                                break;//拼团
                            case 'bargain':
                                $selectVal['request_link'] = 'p=bargain&do=homeList';
                                break;//砍价
                            case 'coupon':
                                $selectVal['request_link'] = 'p=wlcoupon&do=homeList';
                                break;//砍价
                            case 'activity':
                                $selectVal['request_link'] = 'p=activity&do=homeList';
                                break;//活动
                            case 'recruit':
                                $selectVal['request_link'] = 'p=recruit&do=homeList';
                                break;//求职招聘
                            case 'dating':
                                $selectVal['request_link'] = 'p=dating&do=homeList';
                                break;//相亲交友
                            case 'housekeep':
                                //服务类型：1=商户服务商,2=个人服务商,3=客户需求,4=服务项目
                                if($selectVal['service_type'] == 1) $selectVal['request_link'] = 'p=housekeep&do=getStorelist';
                                else if($selectVal['service_type'] == 2) $selectVal['request_link'] = 'p=housekeep&do=getArtificerlist';
                                else if($selectVal['service_type'] == 3) $selectVal['request_link'] = 'p=housekeep&do=getDemandlist';
                                else if($selectVal['service_type'] == 4) $selectVal['request_link'] = 'p=housekeep&do=getServicelist';
                                break;//家政服务
                            case 'pocket':
                                $selectVal['request_link'] = 'p=pocket&do=infoList';
                                break;//同城
                            case 'house':
                                if($selectVal['house_type'] == 1) $selectVal['request_link'] = 'p=house&do=newHouseList';
                                else if($selectVal['house_type'] == 2) $selectVal['request_link'] = 'p=house&do=oldHouseList';
                                else if($selectVal['house_type'] == 3) $selectVal['request_link'] = 'p=house&do=rentingList';
                                break;//同城
                        }
                    }
                    $v['params']['total'] = count($v['data']);
                }
            }
            //富文本组件
            if($v['id'] == 'richtext'){
                $v['params']['content'] = base64_decode($v['params']['content']);
                //信息替换  链接路径替换为超链接
                $https = explode('pages/mainPages/',h5_url('pages/mainPages/index/index'))[0];
                $str = 'href="pages/';
                $v['params']['content']  = htmlspecialchars_decode($v['params']['content']);
                $v['params']['content'] = str_replace($str,'href="'.$https."pages/",$v['params']['content']);
            }
            //商户入驻组件
            if(($v['group_name'] == 'shop_join' || $v['id'] == 'shop_join')){
                $shopList = pdo_fetchall("SELECT id,storename,FROM_UNIXTIME(createtime,'%Y-%m-%d ') as createdate FROM "
                    .tablename(PDO_NAME."merchantdata")
                    ." WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 ORDER BY createtime DESC LIMIT 10 ");
                $v['data'] = $shopList;
            }
            //轮播图组件
            if(($v['group_name'] == 'banner' || $v['id'] == 'banner' || $v['id'] == 'pictures') && is_array($v['data']) && count($v['data']) > 0){
                $keyName = key($v['data']);
                $v['data'] = array_values($v['data']);
                if(!$v['params']['img_width']) $v['params']['img_width'] = !empty(trim($_W['wlsetting']['base']['listwidth'])) ? trim($_W['wlsetting']['base']['listwidth']) : 640;
                if(!$v['params']['img_height']) $v['params']['img_height'] = !empty(trim($_W['wlsetting']['base']['listheight'])) ? trim($_W['wlsetting']['base']['listheight']) : 300;
            }
            //区域选择组件
            if($v['id'] == 'area_select'){
                $v['params']['avatar']   = !empty($_W['wlmember']['avatar']) ? $_W['wlmember']['avatar'] : $v['params']['avatar'];
                $v['params']['nickname'] = !empty($_W['wlmember']['nickname']) ? $_W['wlmember']['nickname'] : $v['params']['nickname'];
            }
            //处理用户信息组件
            if($v['id'] == 'user_info'){
                if(intval($_W['mid']) > 0){
                    $v['data'] = [
                        'nickname' => $_W['wlmember']['nickname'] ,
                        'credit2'  => $_W['wlmember']['credit2'] ,
                        'avatar'   => $_W['wlmember']['avatar'] ,
                        'mobile'   => $_W['wlmember']['mobile'] ,
                    ];
                }
            }
            //处理公众号跳转小程序组件的基本信息
            if($v['id'] == 'jump_wxapp'){
                $v['params']['img'] = tomedia($v['params']['img']);
                //未填写小程序原始ID  获取小程序原始id
                if(!$v['params']['original_id']){
                    $wxAppConfig = Setting::wlsetting_read('wxapp_config');
                    $v['params']['original_id']       = $wxAppConfig['original_id'] ? $wxAppConfig['original_id'] : '';
                }
            }
            //处理招聘 —— 统计组件
            if($v['id'] == 'recruit_statistics'){
                $params = $v['params'];
                //处理图片信息
                $params['enterprise']['icon'] = tomedia($params['enterprise']['icon']);//入驻企业
                $params['recruit']['icon']    = tomedia($params['recruit']['icon']);//职业
                $params['resume']['icon']     = tomedia($params['resume']['icon']);//简历
                $params['pv']['icon']         = tomedia($params['pv']['icon']);//访问
                //统计信息
                $params['enterprise']['statistics'] = pdo_count(PDO_NAME."merchantdata",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'recruit_switch'=>1]) ? : 0;//入驻企业
                $params['recruit']['statistics']    = pdo_count(PDO_NAME."recruit_position",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']]) ? : 0;//职业
                $params['resume']['statistics']     = pdo_count(PDO_NAME."recruit_resume",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']]) ? : 0;//简历
                $params['pv']['statistics']         = pdo_fetchcolumn("SELECT SUM(pv) FROM".tablename(PDO_NAME."recruit_recruit")." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ") ? : 0;//访问
                //获取虚拟数据
                $set = Setting::agentsetting_read('recruit_set');
                $params['pv']['statistics']  = intval($params['pv']['statistics'] ) + intval($set['look']);

                $v['params'] = $params;
            }
            //处理招聘 —— 企业组件
            if(($v['id'] == 'recruit_enterprise' || $v['group_name'] == 'recruit_enterprise')){
                //获取自动加载的企业
                if($v['params']['type'] == 2){
                    //条件生成
                    $where = ' AND status = 2 AND enabled = 1 ';
                    if($v['params']['industry_id'] > 0) $where .= " AND recruit_industry_id = {$v['params']['industry_id']} ";
                    //排序信息   排序：1 = 创建时间，2 = 店铺距离，3 = 默认设置，4 = 浏览人气
                    switch ($v['params']['orders']) {
                        case 1:$order = " ORDER BY createtime DESC,id DESC ";break;//创建时间
                        case 2:$order = " ORDER BY distances DESC,id DESC ";break;//店铺距离
                        case 3:$order = " ORDER BY listorder DESC,id DESC ";break;//默认设置
                        case 4:$order = " ORDER BY pv ASC,id DESC ";break;//浏览人气
                    }
                    //查询数量
                    $limit = " LIMIT 0,{$v['params']['show_num']} ";
                    $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
                    $field = "{$distances} as distances,id,logo,storename,recruit_nature_id,recruit_scale_id,recruit_industry_id,provinceid,areaid,distid";
                    [$nterprise,$total] = Recruit::getEnterpriseList($where,$field,$order,$limit);
                    foreach($nterprise as $nterpriseIndex => &$nterpriseItem){
                        unset($nterpriseItem['distances'],$nterpriseItem['recruit_nature_id'],$nterpriseItem['recruit_scale_id'],$nterpriseItem['recruit_industry_id'],$nterpriseItem['provinceid'],$nterpriseItem['areaid'],$nterpriseItem['distid']);
                    }
                    //信息赋值
                    $v['data'] = $nterprise ? : [];
                }
                //判断没有内容的企业 删除
                foreach($v['data'] as $dataKey => &$dataItem){
                    if(!$dataItem['id']) unset($v['data'][$dataKey]);
                }

            }
            //处理招聘 —— 招聘组件
            if($v['id'] == 'recruit_recruit'){
                //获取自动加载的企业
                if($v['params']['type'] == 2){
                    //条件生成
                    $where = ' AND status = 4';
                    if ($v['params']['industry_id'] > 0) $where = " AND industry_pid = {$v['params']['industry_id']} ";
                    //生成排序条件 1=推荐排序  2=浏览量  3=发布时间  4=距离排序
                    $order = " ORDER BY is_top DESC";
                    switch ($v['params']['orders']) {
                        case 1:$order .= ",sort DESC,id DESC ";break;//推荐排序
                        case 2:$order .= ",pv DESC,id DESC ";break;//浏览量
                        case 3:$order .= ",create_time DESC,id DESC ";break;//发布时间
                        case 4:$order .= ",distances ASC,id DESC ";break;//距离排序
                    }
                    //查询数量
                    $limit = " LIMIT 0,{$v['params']['show_num']} ";
                    $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - work_lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(work_lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - work_lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
                    $field = "{$distances} as distances,id,title,recruitment_type,release_mid,release_sid,job_type,full_type,full_salary_min,full_salary_max,welfare,part_type,part_salary,work_province,work_city,work_area,create_time,is_top";
                    [$recruit,$total] = Recruit::getRecruitList($where,$field,$order,$limit);
                    foreach($recruit as &$item){
                        unset($item['position_id'] , $item['release_mid'] , $item['release_sid'] , $item['full_type'] , $item['full_salary_min'] ,
                            $item['full_salary_max'] , $item['welfare'] , $item['part_type'] , $item['part_salary'] , $item['part_settlement'] ,
                            $item['work_province'] , $item['work_city'] , $item['work_area'] , $item['create_time'], $item['province'], $item['city'],
                            $item['area'],$item['job_type'],$item['distances']);
                    }
                    //信息赋值
                    $v['data'] = $recruit ? : [];
                }
                //判断没有内容的招聘 删除
                foreach($v['data'] as $dataKey => &$dataItem){
                    if(!$dataItem['id']) unset($v['data'][$dataKey]);
                }

            }
            //处理招聘 —— 简历组件
            if($v['id'] == 'recruit_resume'){
                //获取自动加载的简历
                if($v['params']['type'] == 2){
                    //条件生成
                    $where = [];
                    if ($v['params']['industry_id'] > 0) $where['industry_pid'] = $v['params']['industry_id'];
                    //生成排序条件 1=推荐排序  2=浏览量  3=发布时间
                    switch ($v['params']['orders']) {
                        case 1:
                            $order = "  ";
                            break;//推荐排序
                        case 2:
                            $order = "pv DESC,id DESC ";
                            break;//浏览量
                        case 3:
                            $order = "create_time DESC,id DESC ";
                            break;//发布时间
                    }
                    //查询数量
                    $limit = [1,$v['params']['show_num']];
                    $field = [
                        'id',
                        'name',
                        'phone',
                        'avatar',
                        'gender',
                        'work_status',
                        'experience_label_id',
                        'education_label_id',
                        'birth_time',
                        'expect_position',
                        'job_type',
                        'expect_salary_min',
                        'expect_salary_max',
                        'expect_work_province',
                        'expect_work_city',
                        'expect_work_area'
                    ];
                    [$resume,$total] = Recruit::getResumeList($where,$field,$order,$limit);
                    foreach ($resume as &$item) {
                        unset($item['work_status'],$item['experience_label_id'],$item['education_label_id'],$item['birth_time'],$item['expect_salary_min'],$item['expect_salary_max'],$item['expect_work_province'],$item['expect_work_city'],$item['expect_work_area'],$item['expect_position'],$item['province'],$item['city'],$item['area']);
                    }
                    //信息赋值
                    $v['data'] = $resume ? : [];
                }
                //判断没有内容的简历 删除
                foreach($v['data'] as $dataKey => &$dataItem){
                    if(!$dataItem['id']) unset($v['data'][$dataKey]);
                }
            }
            //处理相亲 —— 统计组件
            if($v['id'] == 'dating_statistics'){
                //设置信息获取
                $params = $v['params'];
                //处理图片信息
                $params['user']['icon'] = tomedia($params['user']['icon']);//会员数量
                $params['pv']['icon']    = tomedia($params['pv']['icon']);//访问量
                //统计信息
                $params['user']['statistics'] = pdo_count(PDO_NAME."dating_member",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'examine'=>3]) ? : 0;//会员数量
                $params['pv']['statistics']   = pdo_fetchcolumn("SELECT SUM(pv) FROM".tablename(PDO_NAME."dating_member")." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ") ? : 0;//访问
                //获取虚拟数据
                $set = Setting::wlsetting_read('dating_set');
                $params['pv']['statistics']  = intval($params['pv']['statistics'] ) + intval($set['fictitious_pv']);

                $v['params'] = $params;
            }
            //处理相亲 —— 会员组件
            if($v['id'] == 'dating_user'){
                if($v['params']['type'] == 2) {
                    //特殊内容查询
                    $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - a.lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(a.lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - a.lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
                    $area      = "CASE WHEN current_area > 0 THEN current_area
                    WHEN current_city > 0 THEN current_city
                    ELSE current_province
                 END";
                    $age       = "CASE WHEN (FROM_UNIXTIME(unix_timestamp(now()) ,'%m') - FROM_UNIXTIME(a.birth,'%m')) < 0 AND (FROM_UNIXTIME(unix_timestamp(now()) ,'%d') - FROM_UNIXTIME(a.birth,'%d')) < 0 
                        THEN (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(a.birth,'%Y-%m-%d'), CURDATE()) - 1)
                     ELSE TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(a.birth,'%Y-%m-%d'), CURDATE())
                END";
                    //生成条件  加载类型：1=手动选择，2=自动加载
                    $where .= " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.examine = 3 ";
                    if($_W['mid']) $where .= " AND a.mid <> {$_W['mid']} ";
                    //生成排序条件 1=推荐排序  2=浏览量  3=发布时间  4=距离排序
                    $order = " ORDER BY a.is_top DESC";
                    switch ($v['params']['orders']) {
                        case 1:
                            $order .= ",a.sort DESC,a.id DESC ";
                            break;//推荐排序
                        case 2:
                            $order .= ",pv DESC,a.id DESC ";
                            break;//浏览量
                        case 3:
                            $order .= ",a.create_time DESC,a.id DESC ";
                            break;//发布时间
                        case 4:
                            $order .= ",distances ASC,a.id DESC ";
                            break;//距离排序
                    }
                    //查询数量
                    $limit = " LIMIT 0,{$v['params']['show_num']} ";
                    $field = "{$age} as age,{$distances} as distances,{$area} AS area_id,a.id,a.is_top,a.mid,a.cover,a.gneder,a.birth,a.live,a.travel,a.pv,a.falsename,a.falseavatar,a.falsereal";
                    $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member")." as a LEFT JOIN ".tablename(PDO_NAME."member")." as b ON a.mid = b.id ";
                    //列表信息获取
                    $list = pdo_fetchall($sql.$where.$order.$limit);
                    foreach ($list as &$item) {
                        //综合信息处理
                        $item = Dating::handleMemberInfo($item);
                        //最小一级区域信息查询
                        $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['area_id']],'name');
                        //判断是否为vip
                        [$item['is_vip'],$numTime] = Dating::isVip($item['mid']);
                        //封面图
                        $item['cover'] = tomedia($item['cover']);
                        //删除多余的信息
                        unset($item['birth'],$item['area_id'],$item['distances'],$item['mid'],$item['live_text'],$item['travel_text']);
                    }

                    $v['data'] = $list ? :[];
                }
            }
            //处理家政 —— 家政服务组件
            if(($v['id'] == 'house_keep' || $v['group_name'] == 'house_keep')&& $v['params']['type'] == 2){
                //基本条件
                $shopWhere = " AND enabled = 1 AND housekeepstatus = 1  ";
                $artificerWhere = $demandWhere = $serviceWhere = " AND status = 1 ";
                //请求获取列表
                $houseKeep = Housekeep::getList($v['params']['service_type'],1,$v['params']['show_num'],$shopWhere,
                    $artificerWhere,$demandWhere,$serviceWhere,$v['params']['orders'],$lng,$lat);
                $v['data'] = is_array($houseKeep['list']) && count($houseKeep['list']) ? $houseKeep['list'] : [];
                if(Customized::init('citycard1503') > 0 ){
                    $v['hidepersontag'] = 1;
                }else{
                    $v['hidepersontag'] = 0;
                }
            }
            //搜索框
            if($v['group_name'] == 'search' &&  empty($v['params']['search_type'])){
                $v['params']['search_type'] = 1;
            }
            //视频组件
            if($v['group_name'] == 'vido' || $v['id'] == 'vido'){
                $v['params']['linkurl'] = tomedia($v['params']['linkurl']);
            }
            //帖子组件
            if($v['group_name'] == 'Suprapalmar_posts'){
                if($v['params']['type'] == 1){ //手动选择
                	foreach($v['data'] as &$pocketdata){
                		$pocketdata = Pocket::getsingleInfo($pocketdata['id']);
                	}
                }else{ //自动加载
                	$v['data'] = Pocket::getNumberInfo($v['params']['show_num'],$v['params']['orders'],$v['params']['classs']);
                }
            }
            //掌上信息统计
            if($v['group_name'] == 'suprapalmar_statistics'){
               	$set = Setting::agentsetting_read('pocket');
               	$look  = $set['look'] > 0 ? intval($set['look']) : 0;//浏览量
		        $total = $set['fabu'] > 0 ? intval($set['fabu']) : 0;//发布数
		        $share = $set['share'] > 0 ? intval($set['share']) : 0;//分享数
		        $info = pdo_fetch("SELECT (IFNULL(SUM(look),0) + {$look}) AS look,(IFNULL(COUNT(*),0) + {$total}) as total,(IFNULL(SUM(share),0) + {$share})AS share FROM "
		            .tablename(PDO_NAME."pocket_informations")
		            ." WHERE status = 0 AND aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ");
		            $info = Pocket::befutInfo($info);
		            
		        $v['data'] = $info;
            }
			//定制商户组件
			if($v['id'] == 'merchant_information'){
				$sid = $_GPC['sid'];
				$info = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('storename','logo','tel','storehours','score','address','lng','lat'));
				$info['logo'] = tomedia($info['logo']);
				//营业时间的处理
		        $storehours          = unserialize($info['storehours']);
		        $info['is_business'] = Store::getShopBusinessStatus($storehours,$sid);//判断商户当前状态：0=休息中，1=营业中
		        if(!empty($storehours['startTime'])){
		            $info['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
		        }else{
		            $info['storehours'] = '';
		            foreach($storehours as $hk => $hour){
		                if($hk > 0){
		                    $info['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
		                }else{
		                    $info['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
		                }
		            }
		        }
				#3、对店铺进行定位
				$location['lng'] = $info['lng'];
				$location['lat'] = $info['lat'];
        		$info['distance'] = Store::shopLocation(0 , $lng , $lat , $location);
				$storefans = pdo_getcolumn(PDO_NAME . "storefans" , [
		            'sid'    => $sid ,
		            'mid'    => $_W['mid'] ,
		            'source' => 1
		        ] , 'id');
		        $info['is_collection'] = $storefans > 0 ? 1 : 0;
		        $info['collectionnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_storefans')." WHERE source = 1 AND sid = {$sid}");
		        $info['collectionnum'] = sprintf("%.0f",$info['pv'] / 10 ) + $info['collectionnum'];
				$v['data'] = $info;
			}
			if($v['id'] == 'custom_form'){
				$redid = $_GPC['redid'];
				$diylistinfoid = pdo_getcolumn(PDO_NAME.'store_redpack_record',array('redpackid'=>$redid,'mid'=>$_W['mid']),'formlistid');
				if($diylistinfoid > 0){
					$diyinfo = pdo_get(PDO_NAME.'diyform_list',array('id'=>$diylistinfoid));
					$forminfo = pdo_get('wlmerchant_diyform',array('id' => $diyinfo['formid']));
			        $moinfo = json_decode(base64_decode($forminfo['info']) , true);
			        $list = $moinfo['list'];
			        $list = array_values($list);
					$v['data']['formid'] = $diylistinfoid;
					$v['data']['diyform']['list'] = Im::diyFormInfo($list,$diyinfo);
				}
			}

        }
        //删除不需要的信息
        unset($v['nav_class']);
        unset($v['name']);
        unset($v['plugin']);
        return $v;
    }

}