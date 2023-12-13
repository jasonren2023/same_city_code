<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');
function tpl_form_field_fans($name, $value, $scene = 'notify', $required = false) {
    global $_W;
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    $s = '';
    if (!defined('TPL_INIT_TINY_FANS')) {
        $option = array(
            'scene' => $scene,
        );
        $option = json_encode($option);
        $s = '
		<script type="text/javascript">
			function showFansDialog(elm) {
				var btn = $(elm);
				var openid_wxapp = btn.parent().prev();
				var openid = btn.parent().prev().prev();
				var avatar = btn.parent().prev().prev().prev();
				var nickname = btn.parent().prev().prev().prev().prev();
				var img = btn.parent().parent().next().find("img");
				irequire(["web/tiny"], function(tiny){
					tiny.selectfan(function(fans){
						console.log(fans);
						if(img.length > 0){
							img.get(0).src = fans.avatar;
						}
						openid_wxapp.val(fans.openid_wxapp);
						openid.val(fans.openid);
						avatar.val(fans.avatar);
						nickname.val(fans.nickname);
					}, ' . $option . ');
				});
			}
		</script>';
        define('TPL_INIT_TINY_FANS', true);
    }

    $s .= '
		<div class="input-group">
			<input type="text" name="' . $name . '[nickname]" value="' . $value['nickname'] . '" class="form-control" readonly ' . ($required ? 'required' : '') . '>
			<input type="hidden" name="' . $name . '[avatar]" value="' . $value['avatar'] . '">
			<input type="hidden" name="' . $name . '[openid]" value="' . $value['openid'] . '">
			<input type="hidden" name="' . $name . '[openid_wxapp]" value="' . $value['openid_wxapp'] . '">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="showFansDialog(this);">选择粉丝</button>
			</span>
		</div>
		<div class="input-group" style="margin-top:.5em;">
			<img src="' . $value['avatar'] . '" onerror="this.src=\'' . $default . '\'; this.title=\'头像未找到.\'" class="img-responsive img-thumbnail" width="150" />
		</div>';
    return $s;
}

function itpl_form_field_daterange($name, $value = array(), $time = false) {
    global $_GPC;
    $placeholder = ((isset($value['placeholder']) ? $value['placeholder'] : ''));
    $s = '';

    if (empty($time) && !defined('TPL_INIT_TINY_DATERANGE_DATE')) {
        $s = '
<script type="text/javascript">
	require(["daterangepicker"], function() {
		$(".daterange.daterange-date").each(function(){
			var elm = this;
			var container =$(elm).parent().prev();
			$(this).daterangepicker({
				format: "YYYY-MM-DD"
			}, function(start, end){
				$(elm).find(".date-title").html(start.toDateStr() + " 至 " + end.toDateStr());
				container.find(":input:first").val(start.toDateTimeStr());
				container.find(":input:last").val(end.toDateTimeStr());
			});
		});
	});

	function clearTime(obj){
		$(obj).prev().html("<span class=date-title>" + $(obj).attr("placeholder") + "</span>");
		$(obj).parent().prev().find("input").val("");
	 }
</script>';
        define('TPL_INIT_TINY_DATERANGE_DATE', true);
    }

    if (!empty($time) && !defined('TPL_INIT_TINY_DATERANGE_TIME')) {
        $s = '
<script type="text/javascript">
	require(["daterangepicker"], function($){
		$(function(){
			$(".daterange.daterange-time").each(function() {
				var elm = this;
				var container =$(elm).parent().prev();
				$(this).daterangepicker({
					format: "YYYY-MM-DD HH:mm",
					timePicker: true,
					timePicker12Hour : false,
					timePickerIncrement: 1,
					minuteStep: 1
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateTimeStr() + " 至 " + end.toDateTimeStr());
					container.find(":input:first").val(start.toDateTimeStr());
					container.find(":input:last").val(end.toDateTimeStr());
				});
			});
		});
	});

	function clearTime(obj){
		$(obj).prev().html("<span class=date-title>" + $(obj).attr("placeholder") + "</span>");
		$(obj).parent().prev().find("input").val("");
	 }
</script>';
        define('TPL_INIT_TINY_DATERANGE_TIME', true);
    }

    $str = $placeholder;
    $value['starttime'] = ((isset($value['starttime']) ? $value['starttime'] : (($_GPC[$name]['start'] ? $_GPC[$name]['start'] : ''))));
    $value['endtime'] = ((isset($value['endtime']) ? $value['endtime'] : (($_GPC[$name]['end'] ? $_GPC[$name]['end'] : ''))));
    if ($value['starttime'] && $value['endtime']) {
        if (empty($time)) {
            $str = date('Y-m-d', strtotime($value['starttime'])) . '至 ' . date('Y-m-d', strtotime($value['endtime']));
        } else {
            $str = date('Y-m-d H:i', strtotime($value['starttime'])) . ' 至 ' . date('Y-m-d  H:i', strtotime($value['endtime']));
        }
    }

    $s .= '
		<div style="float:left">
			<input name="' . $name . '[start]' . '" type="hidden" value="' . $value['starttime'] . '" />
			<input name="' . $name . '[end]' . '" type="hidden" value="' . $value['endtime'] . '" />
		</div>
		<div class="btn-group" style="padding-right:0;">
			<button style="width:240px" class="btn btn-default daterange ' . ((!empty($time) ? 'daterange-time' : 'daterange-date')) . '"  type="button"><span class="date-title">' . $str . '</span></button>
			<button class="btn btn-default" type="button" onclick="clearTime(this)" placeholder="' . $placeholder . '"><i class="fa fa-remove"></i></button>
		</div>';
    return $s;
}

function tpl_form_field_tiny_link($name, $value = '', $options = array()) {
    global $_GPC;
    $s = '';
    if (!defined('TPL_INIT_TINY_LINK')) {
        $s = '
		<script type="text/javascript">
			function showTinyLinkDialog(elm) {
				irequire(["web/tiny"], function(tiny){
					var ipt = $(elm).parent().prev();
					tiny.selectLink(function(href){
						ipt.val(href);
					});
				});
			}
		</script>';
        define('TPL_INIT_TINY_LINK', true);
    }
    $s .= '
	<div class="input-group">
		<input type="text" value="' . $value . '" name="' . $name . '" class="form-control ' . $options['css']['input'] . '" autocomplete="off">
		<span class="input-group-btn">
			<button class="btn btn-default ' . $options['css']['btn'] . '" type="button" onclick="showTinyLinkDialog(this);">选择链接</button>
		</span>
	</div>
	';
    return $s;
}

function tpl_form_field_tiny_wxapp_link($name, $value = '', $options = array()) {
    global $_GPC;
    $s = '';
    if (!defined('TPL_INIT_TINY_WXAPP_LINK')) {
        $s = '
		<script type="text/javascript">
			function showTinyWxappLinkDialog(elm) {
				irequire(["web/tiny"], function(tiny){
					var ipt = $(elm).parent().prev();
					tiny.selectWxappLink(function(href){
						ipt.val(href);
					});
				});
			}
		</script>';
        define('TPL_INIT_TINY_WXAPP_LINK', true);
    }
    $s .= '
	<div class="input-group">
		<input type="text" value="' . $value . '" name="' . $name . '" class="form-control ' . $options['css']['input'] . '" autocomplete="off">
		<span class="input-group-btn">
			<button class="btn btn-default ' . $options['css']['btn'] . '" type="button" onclick="showTinyWxappLinkDialog(this);">选择链接</button>
		</span>
	</div>
	';
    return $s;
}

function tpl_form_field_tiny_coordinate($field, $value = array(), $required = false) {
    global $_W;
    $s = '';
    if (!defined('TPL_INIT_TINY_COORDINATE')) {
        $s .= '<script type="text/javascript">
				function showCoordinate(elm) {
					irequire(["web/tiny"], function(tiny){
						var val = {};
						val.lng = parseFloat($(elm).parent().prev().prev().find(":text").val());
						val.lat = parseFloat($(elm).parent().prev().find(":text").val());
						tiny.map(val, function(r){
							$(elm).parent().prev().prev().find(":text").val(r.lng);
							$(elm).parent().prev().find(":text").val(r.lat);
						});
					});
				}
			</script>';
        define('TPL_INIT_TINY_COORDINATE', true);
    }
    $s .= '
		<div class="row row-fix">
			<div class="col-xs-4 col-sm-4">
				<input type="text" name="' . $field . '[lng]" value="' . $value['lng'] . '" placeholder="地理经度"  class="form-control" ' . ($required ? 'required' : '') . '/>
			</div>
			<div class="col-xs-4 col-sm-4">
				<input type="text" name="' . $field . '[lat]" value="' . $value['lat'] . '" placeholder="地理纬度"  class="form-control" ' . ($required ? 'required' : '') . '/>
			</div>
			<div class="col-xs-4 col-sm-4">
				<button onclick="showCoordinate(this);" class="btn btn-default" type="button">选择坐标</button>
			</div>
		</div>';
    return $s;
}

function tpl_select2($name, $data, $value = 0, $filter = array('id', 'title'), $default = '请选择') {
    $element_id = "select2-{$name}";
    $json_data = array();
    foreach ($data as $da) {
        $json_data[] = array(
            'id'   => $da[$filter[0]],
            'text' => $da[$filter[1]],
        );
    }
    $json_data = json_encode($json_data);
    $html = '<select name="' . $name . '" class="form-control" id="' . $element_id . '"></select>';
    $html .= '<script type="text/javascript">
					require(["jquery", "select2"], function($) {
						$("#' . $element_id . '").select2({
							placeholder: "' . $default . '",
							data: ' . $json_data . ',
							val: ' . $value . '
						});
					});
			  </script>';
    return $html;
}

function tpl_form_field_tiny_image($name, $value = '') {
    global $_W;
    $default = '';
    $val = $default;
    if (!empty($value)) {
        $val = tomedia($value);
    }
    if (!empty($options['global'])) {
        $options['global'] = true;
    } else {
        $options['global'] = false;
    }
    if (empty($options['class_extra'])) {
        $options['class_extra'] = '';
    }
    if (isset($options['dest_dir']) && !empty($options['dest_dir'])) {
        if (!preg_match('/^\w+([\/]\w+)?$/i', $options['dest_dir'])) {
            exit('图片上传目录错误,只能指定最多两级目录,如: "we7_store","we7_store/d1"');
        }
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    if (isset($options['thumb'])) {
        $options['thumb'] = !empty($options['thumb']);
    }
    $s = '';
    if (!defined('TPL_INIT_TINY_IMAGE')) {
        $s = '
		<script type="text/javascript">
			function showImageDialog(elm, opts, options) {
				require(["util"], function(util){
					var btn = $(elm);
					var ipt = btn.parent().prev();
					var val = ipt.val();
					var img = ipt.parent().parent().find(".input-group-addon img");
					options = ' . str_replace('"', '\'', json_encode($options)) . ';
					util.image(val, function(url){
						if(url.url){
							if(img.length > 0){
								img.get(0).src = url.url;
							}
							ipt.val(url.attachment);
							ipt.attr("filename",url.filename);
							ipt.attr("url",url.url);
						}
						if(url.media_id){
							if(img.length > 0){
								img.get(0).src = "";
							}
							ipt.val(url.media_id);
						}
					}, null, options);
				});
			}
			function deleteImage(elm){
				require(["jquery"], function($){
					$(elm).prev().attr("src", "./resource/images/nopic.jpg");
					$(elm).parent().prev().find("input").val("");
				});
			}
		</script>';
        define('TPL_INIT_TINY_IMAGE', true);
    }

    $s .= '
		<div class="input-group ' . $options['class_extra'] . '">
			<div class="input-group-addon">
				<img src="' . $val . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" width="20" height="20" />
			</div>
			<input type="text" name="' . $name . '" value="' . $value . '" class="form-control" autocomplete="off">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="showImageDialog(this);">选择图片</button>
			</span>
		</div>';
    return $s;
}

function tpl_form_field_store($name, $value = '', $option = array('mutil' => 0)) {
    global $_W;
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    if (!is_array($value)) {
        $value = intval($value);
        $value = array($value);
    }
    $value_ids = implode(',', $value);
    $stores_temp = pdo_fetchall('select id, title, logo from ' . tablename('tiny_wmall_store') . " where uniacid = :uniacid and id in ({$value_ids})", array(':uniacid' => $_W['uniacid']));
    $stores = array();
    if (!empty($stores_temp)) {
        foreach ($stores_temp as $row) {
            $row['logo'] = tomedia($row['logo']);
            $stores[] = $row;
        }
    }

    $definevar = 'TPL_INIT_TINY_STORE';
    $function = 'showStoreDialog';
    if (!empty($option['mutil'])) {
        $definevar = 'TPL_INIT_TINY_MUTIL_STORE';
        $function = 'showMutilStoreDialog';
    }
    $s = '';
    if (!defined($definevar)) {
        $option_json = json_encode($option);
        $s = '
		<script type="text/javascript">
			function ' . $function . '(elm) {
				var btn = $(elm);
				var value_cn = btn.parent().prev();
				var logo = btn.parent().parent().next().find("img");
				irequire(["web/tiny"], function(tiny){
					tiny.selectstore(function(stores, option){
						if(option.mutil == 1) {
							$.each(stores, function(idx, store){
								$(elm).parent().parent().next().append(\'<div class="multi-item"><img onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+store.logo+\'" class="img-responsive img-thumbnail"><input type="hidden" name="\'+name+\'[]" value="\'+store.id+\'"><em class="close" title="删除该门店" onclick="deleteStore(this)">×</em><span>\'+store.title+\'</span></div>\');
							});
						} else {
							value_cn.val(stores.title);
							logo[0].src = stores.logo;
							logo.prev().val(stores.id);
							logo.next().removeClass("hide").html(stores.title);
						}
					}, ' . $option_json . ');
				});
			}

			function deleteMutilStore(elm){
				$(elm).parent().remove();
			}
		</script>';
        define($definevar, true);
    }

    $s .= '
		<div class="input-group">
			<input type="text" class="form-control store-cn" readonly value="' . $stores[0]['title'] . '">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="' . $function . '(this);">选择商家</button>
			</span>
		</div>';
    if (empty($option['mutil'])) {
        $s .= '
		<div class="input-group single-item" style="margin-top:.5em;">
			<input type="hidden" name="' . $name . '" value="' . $value[0] . '">
			<img src="' . $stores[0]['logo'] . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" width="150" />
		';
        if (empty($stores[0]['title'])) {
            $s .= '<span class="hide"></span>';
        } else {
            $s .= '<span>' . $stores[0]['title'] . '</span>';
        }
        $s .= '</div>';
    } else {
        $s .= '<div class="input-group multi-img-details">';
        foreach ($stores as $store) {
            $s .= '
			<div class="multi-item">
				<img src="' . $store['logo'] . '" title="' . $store['title'] . '" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
				<input type="hidden" name="' . $name . '[]" value="' . $store['id'] . '">
				<em class="close" title="删除该门店" onclick="deleteMutilStore()">×</em>
				<span>' . $store['title'] . '</span>
			</div>';
        }
        $s .= '</div>';
    }
    return $s;
}

function tpl_form_field_mutil_store($name, $value = '') {
    return tpl_form_field_store($name, $value, $option = array('mutil' => 1));
}

function tpl_form_field_goods($name, $value = '', $option = array('mutil' => 0, 'sid' => 0, 'ignore' => array())) {
    global $_W;
    if (!isset($option['mutil'])) {
        $option['mutil'] = 0;
    }
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    if (!is_array($value)) {
        $value = intval($value);
        $value = array($value);
    }
    $condition = ' where uniacid = :uniacid';
    $params = array(':uniacid' => $_W['uniacid']);
    $value_ids = implode(',', $value);
    $condition .= " and id in ({$value_ids})";
    $goods_temp = pdo_fetchall('select id, title, thumb from ' . tablename('tiny_wmall_goods') . "{$condition}", $params);
    $goods = array();
    if (!empty($goods_temp)) {
        foreach ($goods_temp as $row) {
            $row['thumb'] = tomedia($row['thumb']);
            $goods[] = $row;
        }
    }

    $definevar = 'TPL_INIT_TINY_GOODS';
    $function = 'showGoodsDialog';
    if (!empty($option['mutil'])) {
        $definevar = 'TPL_INIT_TINY_MUTIL_GOODS';
        $function = 'showMutilGoodsDialog';
    }
    $s = '';
    if (!defined($definevar)) {
        $option_json = json_encode($option);
        $s = '
		<script type="text/javascript">
			function ' . $function . '(elm) {
				var btn = $(elm);
				var value_cn = btn.parent().prev();
				var thumb = btn.parent().parent().next().find("img");
				tiny.selectgoods(function(goods, option){
					if(option.mutil == 1) {
						$.each(goods, function(idx, good){
							$(elm).parent().parent().next().append(\'<div class="multi-item"><img onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+store.good+\'" class="img-responsive img-thumbnail"><input type="hidden" name="\'+name+\'[]" value="\'+good.id+\'"><em class="close" title="删除该商品" onclick="deleteStore(this)">×</em><span>\'+good.title+\'</span></div>\');
						});
					} else {
						value_cn.val(goods.title);
						thumb[0].src = goods.thumb;
						thumb.prev().val(goods.id);
						thumb.next().removeClass("hide").html(goods.title);
					}
				}, ' . $option_json . ');
			}

			function deleteMutilGoods(elm){
				$(elm).parent().remove();
			}
		</script>';
        define($definevar, true);
    }

    $s .= '
		<div class="input-group">
			<input type="text" class="form-control store-cn" readonly value="' . $goods[0]['title'] . '">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="' . $function . '(this);">选择商品</button>
			</span>
		</div>';
    if (empty($option['mutil'])) {
        $s .= '
		<div class="input-group single-item" style="margin-top:.5em;">
			<input type="hidden" name="' . $name . '" value="' . $value[0] . '">
			<img src="' . $goods[0]['thumb'] . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" width="150" />
		';
        if (empty($goods[0]['title'])) {
            $s .= '<span class="hide"></span>';
        } else {
            $s .= '<span>' . $goods[0]['title'] . '</span>';
        }
        $s .= '</div>';
    } else {
        $s .= '<div class="input-group multi-img-details">';
        foreach ($goods as $good) {
            $s .= '
			<div class="multi-item">
				<img src="' . $good['thumb'] . '" title="' . $good['title'] . '" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
				<input type="hidden" name="' . $name . '[]" value="' . $good['id'] . '">
				<em class="close" title="删除该商品" onclick="deleteMutilStore()">×</em>
				<span>' . $good['title'] . '</span>
			</div>';
        }
        $s .= '</div>';
    }
    return $s;
}

function tpl_form_field_mutil_goods($name, $value = '', $option = array('sid' => 0, 'ignore' => array())) {
    if (!isset($option['mutil'])) {
        $option['mutil'] = 1;
    }
    return tpl_form_field_goods($name, $value, $option);
}

function tpl_form_filter_hidden($ctrls) {
    global $_W;
    if (is_agent()) {
        $html = '';
    } else {
        $html = '
			<input type="hidden" name="c" value="site">
			<input type="hidden" name="a" value="entry">
			<input type="hidden" name="m" value="' . MODULE_NAME . '">
		';
    }

    [$p, $ac, $do] = explode('/', $ctrls);
    if (!empty($p)) {
        $html .= '<input type="hidden" name="p" value="' . $p . '"/>';
        if (!empty($ac)) {
            $html .= '<input type="hidden" name="ac" value="' . $ac . '"/>';
            if (!empty($do)) {
                $html .= '<input type="hidden" name="do" value="' . $do . '"/>';
            }
        }
    }
    return $html;
}

function tpl_form_field_tiny_category_2level($name, $parents, $children, $parentid, $childid) {
    $html = '
		<script type="text/javascript">
			window._' . $name . ' = ' . json_encode($children) . ';
		</script>';
    if (!defined('TPL_INIT_TINY_CATEGORY')) {
        $html .= '
					<script type="text/javascript">
						function irenderCategory(obj, name){
							var index = obj.options[obj.selectedIndex].value;
							require([\'jquery\', \'util\'], function($, u){
								$selectChild = $(\'#\'+name+\'_child\');
								var html = \'<option value="0">请选择二级分类</option>\';
								console.log(index);
								console.log(_category);

								if (!window[\'_\'+name] || !window[\'_\'+name][index]) {
									$selectChild.html(html);
									return false;
								}
								for(var i in window[\'_\'+name][index]){
									html += \'<option value="\'+window[\'_\'+name][index][i][\'id\']+\'">\'+window[\'_\'+name][index][i][\'name\']+\'</option>\';
								}
								$selectChild.html(html);
							});
						}
					</script>
					';
        define('TPL_INIT_TINY_CATEGORY', true);
    }

    $html .=
        '<div class="row row-fix tpl-category-container">
	<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
		<select class="form-control tpl-category-parent" id="' . $name . '_parent" name="' . $name . '[parentid]" onchange="irenderCategory(this,\'' . $name . '\')">
					<option value="0">请选择一级分类</option>';
    $ops = '';
    foreach ($parents as $row) {
        $html .= '
					<option value="' . $row['id'] . '" ' . (($row['id'] == $parentid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
    }
    $html .= '
				</select>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<select class="form-control tpl-category-child" id="' . $name . '_child" name="' . $name . '[childid]">
					<option value="0">请选择二级分类</option>';
    if (!empty($parentid) && !empty($children[$parentid])) {
        foreach ($children[$parentid] as $row) {
            $html .= '
					<option value="' . $row['id'] . '"' . (($row['id'] == $childid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
        }
    }
    $html .= '
				</select>
			</div>
		</div>
	';
    return $html;
}

function tpl_form_field_editor($params = array(), $callback = NULL) {
    $html = '<span class="form-editor-group">';
    $html .= '<span class="form-control-static form-editor-show">';
    $html .= '<a class="form-editor-text">' . $params['value'] . '</a>';
    $html .= '<a class="text-primary form-editor-btn">修改</a>';
    $html .= '</span>';
    $html .= '<span class="input-group form-editor-edit">';
    $html .= '<input class="form-control form-editor-input" value="' . $params['value'] . '" name="' . $params['name'] . '"';

    if (!empty($params['placeholder'])) {
        $html .= 'placeholder="' . $params['placeholder'] . '"';
    }

    if (!empty($params['id'])) {
        $html .= 'id="' . $params['id'] . '"';
    }

    if (!empty($params['data-rule-required']) || !empty($params['required'])) {
        $html .= ' data-rule-required="true"';
    }

    if (!empty($params['data-msg-required'])) {
        $html .= ' data-msg-required="' . $params['data-msg-required'] . '"';
    }

    $html .= ' /><span class="input-group-btn">';
    $html .= '<span class="btn btn-default form-editor-finish"';

    if ($callback) {
        $html .= 'data-callback="' . $callback . '"';
    }

    $html .= '><i class="fa fa-check"></i></span>';
    $html .= '</span>';
    $html .= '</span>';
    return $html;
}

/**
 * Comment: 商品选择器
 * Author: zzw
 * Date: 2019/7/10 15:11
 * @param $info @商品名称、商品id、商品类型(字符串，例：rush=抢购商品)、商户id
 * @return string
 */
function tpl_select_goods($info) {
    $html = '<div class="input-group">
                <input type="text" placeholder="请选择商品!" name="data[goods_name]" readonly="readonly"  value="' . $info['goods_name'] . '" class="form-control selectGoods_name" autocomplete="off">
                <input type="text" placeholder="请选择商品!" name="data[goods_id]" readonly="readonly"  value="' . $info['goods_id'] . '" class="form-control hide selectGoods_id" autocomplete="off">
                <input type="text" placeholder="请选择商品!" name="data[goods_plugin]" readonly="readonly"  value="' . $info['goods_plugin'] . '" class="form-control hide selectGoods_plugin" autocomplete="off">
                <input type="text" placeholder="请选择商品!" name="data[sid]" readonly="readonly"  value="' . $info['sid'] . '" class="form-control hide selectGoods_sid" autocomplete="off">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectGoods">选择商品</button>
                    <button type="button" class="btn btn-default clearinput">X</button>
                </span>
            </div>';

    $html .= '<script type="text/javascript">
                  $(".clearinput").click(function(){
                        $(".selectGoods_name").val("请选择商品");
                        $(".selectGoods_id").val(0);
                        $(".selectGoods_plugin").val("");
                        $(".selectGoods_sid").val(0);
                  });
              </script>';
    return $html;

}
/**
 * Comment: 状态改变控制器
 * Author: zzw
 * Date: 2019/9/18 14:30
 * @param string $url 状态改变后提交的地址
 * @param string $val 当前的值
 * @param int $open 开启的值
 * @param int $close 关闭的值
 * @return string
 */
function tpl_change_status($url, $val, $open = 1, $close = 0) {
    $url = web_url($url);
    #1、生成基础内容
    if ($val == $open) $html = '<input type="checkbox" class="js-switch tpl_change_status" data-url="' . $url . '" data-open="' . $open . '" data-close="' . $close . '" checked="checked">';
    else $html = '<input type="checkbox" class="js-switch tpl_change_status" data-url="' . $url . '" data-open="' . $open . '" data-close="' . $close . '">';
    return $html;
}
/**
 * Comment: 用户选择器
 * Author: zzw
 * Date: 2019/11/29 18:17
 * @param string|int $mid 用户id名称
 * @param string|int $midVal 用户id值
 * @return string
 */
function tpl_select_user($mid, $midVal,$title = '选择用户') {
    $default = './resource/images/nopic.jpg';
    if(!empty($midVal)) $member = Member::wl_member_get($midVal);
    $html = '<div class="input-group">
                <input type="text" value="' . $member['nickname'] . '" readonly class="form-control user_nickname" />
                <input type="hidden" name="' . $mid . '" value="' . $midVal . '"  class="user_mid"/>
                <span class="btn input-group-addon" data-toggle="selectUser">'.$title.'</span>
            </div>
            <div class="input-group" style="margin-top:.5em;">
                <img src="' . $member['avatar'] . '" onerror="this.src=\'' . $default . '\'; this.title=\'头像未找到.\'" class="img-responsive img-thumbnail" width="132" />
            </div>';
    return $html;
}
/**
 * Comment: 时间选择器  tpl_form_field_daterange的重构版本
 * Author: zzw
 * Date: 2020/4/9 11:53
 * @param       $name
 * @param array $value
 * @param bool  $time
 * @return string
 */
function tpl_select_time_info($name, $value = array(), $time = false) {
    $s = '
            <script type="text/javascript">
                require(["daterangepicker"], function(){
                    $(function(){
                        $(".daterange.daterange-date").each(function(){
                            var elm = this;
                            $(this).daterangepicker({
                                ranges: {
                                    "近7天": [moment().subtract(7, "days").hours(0).minutes(0), moment().hours(23).minutes(59)],
                                    "本月": [moment().startOf("month").hours(0).minutes(0), moment().endOf("month").hours(23).minutes(59)],
                                    "上个月": [moment().subtract(1, "month").startOf("month").hours(0).minutes(0), moment().subtract(1, "month").endOf("month").hours(23).minutes(59)],
                                    "本周": [moment().startOf("week").add(1,"days").hours(0).minutes(0), moment().endOf("week").add(1,"days").hours(23).minutes(59)],
                                    "未来7天": [moment().hours(0).minutes(0),moment().add(7, "days").hours(23).minutes(59)],
                                    "未来15天": [moment().hours(0).minutes(0),moment().add(15, "days").hours(23).minutes(59)],
                                    "未来1个月": [moment().hours(0).minutes(0),moment().add(1, "months").hours(23).minutes(59)],
                                    "未来6个月": [moment().hours(0).minutes(0),moment().add(6, "months").hours(23).minutes(59)],
                                    "未来一年": [moment().hours(0).minutes(0),moment().add(1, "years").hours(23).minutes(59)],
                                },
                                startDate: $(elm).prev().prev().val(),
                                endDate: $(elm).prev().val(),
                                format: "YYYY-MM-DD HH:mm",
                                timePicker: true,
                                timePicker12Hour: false,
                                timePicker24Hour: true,
                                locale: {
                                    applyLabel: "确定",
                                    cancelLabel: "取消",
                                    fromLabel: "起始时间",
                                    toLabel: "结束时间",
                                    customRangeLabel: "自定义时间",
                                    firstDay: 1
                                  },
                                timePickerIncrement: 1,
                                minuteStep: 1
                            }, function(start, end){
                                console.log("开始时间"+start.toDateTimeStr());
                                console.log("结束时间"+end.toDateTimeStr()); 
                                $(elm).find(".date-title").html(start.toDateTimeStr() + " 至 " + end.toDateTimeStr());
                                $(elm).prev().prev().val(start.toDateTimeStr());
                                $(elm).prev().val(end.toDateTimeStr());
                            });
                        });
                    });
                });
            </script>
        ';
    define('TPL_INIT_DATERANGE_DATE' , true);
    if ($value['starttime'] !== false && $value['start'] !== false) {
        if($value['start']) {
            $value['starttime'] = empty($time) ? date('Y-m-d',strtotime($value['start'])) : date('Y-m-d H:i',strtotime($value['start']));
        }
        $value['starttime'] = empty($value['starttime']) ? (empty($time) ? date('Y-m-d') : date('Y-m-d H:i') ): $value['starttime'];
    } else {
        $value['starttime'] = '请选择';
    }

    if ($value['endtime'] !== false && $value['end'] !== false) {
        if($value['end']) {
            $value['endtime'] = empty($time) ? date('Y-m-d',strtotime($value['end'])) : date('Y-m-d H:i',strtotime($value['end']));
        }
        $value['endtime'] = empty($value['endtime']) ? $value['starttime'] : $value['endtime'];
    } else {
        $value['endtime'] = '请选择';
    }


    $s .= '
	<input name="'.$name . '[start]'.'" type="hidden" value="'. $value['starttime'].'" />
	<input name="'.$name . '[end]'.'" type="hidden" value="'. $value['endtime'].'" />
	<button class="btn btn-default daterange daterange-date" type="button"><span class="date-title">'.$value['starttime'].' 至 '.$value['endtime'].'</span> <i class="fa fa-calendar"></i></button>
	';
    return $s;
}
/**
 * Comment: 图片选择模型  —— 单选
 * Author: zzw
 * Date: 2020/8/31 18:50
 * @param        $name
 * @param string $default_img
 * @return string
 */
function attachment_select($name,$default_img = '',$ordernum = 0){
    //名称处理
    $idName = str_replace(['[',']'],'_',$name);
    $http_url = $default_img;
    if($http_url) $http_url = tomedia($http_url);
    //生成html信息
    $html = '<div class="input-group">
                <input type="text" name="'.$name.'" value="'.$default_img.'" class="form-control" autocomplete="off" id="cimg-'.$idName.$ordernum.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectAttachment" data-input="#cimg-'.$idName.$ordernum.'" data-img="#pimg-'.$idName.$ordernum.'">选择图片</button>
                </span>
            </div>
            <div class="input-group " style="margin-top:.5em;">
                <img src="'.$http_url.'" id="pimg-'.$idName.$ordernum.'" class="img-responsive img-thumbnail" width="150" onerror="this.src=\''.IMAGE_NOPIC_SMALL.'\'">
                <em class="close" style="position:absolute; top: 0px; right: -14px;"
                    onclick="$(\'#cimg-'.$idName.$ordernum.'\').val(\'\');$(\'#pimg-'.$idName.$ordernum.'\').attr(\'src\',\'\');">×</em>
            </div>';

    return $html;
}

function attachment_select2W($name,$default_img = '',$ordernum = 0){
    //名称处理
    $idName = str_replace(['[',']'],'_',$name);
    $http_url = $default_img;
    if($http_url) $http_url = tomedia($http_url);
    //生成html信息
    $html = '<div class="input-group">
                <input type="text" name="'.$name.'" value="'.$default_img.'" class="form-control" autocomplete="off" id="cimg-'.$idName.$ordernum.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectAttachment" data-input="#cimg-'.$idName.$ordernum.'" data-img="#pimg-'.$idName.$ordernum.'">选择图片</button>
                </span>
            </div>
            <div class="input-group " style="margin-top:.5em;">
                <img src="'.$http_url.'" id="pimg-'.$idName.$ordernum.'" class="img-responsive img-thumbnail" width="150" onerror="this.src=\''.IMAGE_NOPIC_SMALL.'\'">
                <em class="close" style="position:absolute; top: 0px; right: -14px;"
                    onclick="$(this).parent().prev().remove();$(this).parent().remove();">×</em>
            </div>';

    return $html;
}

/**
 * Comment: 图片选择模型 —— 多选
 * Author: zzw
 * Date: 2020/8/31 19:35
 * @param        $name
 * @param string $default_img
 * @return string
 */
function attachment_select_multi($name,$default_img = ''){
    //名称处理
    $idName = str_replace(['[',']'],'_',$name);
    //默认图片信息处理
    $html = '';
    if(is_array($default_img) && count($default_img) > 0){
        foreach($default_img as $attKey => $attVal){
            //图片信息处理
            $httpUrl = tomedia($attVal);
            //生成html信息
            $html .= '<div class="multi-item">
                        <img src="'.$httpUrl.'" class="img-responsive img-thumbnail">
                        <input type="hidden" name="'.$name.'[]" value="'.$attVal.'">
                        <em class="close" title="删除这张图片" onclick="$(this).closest(\'.multi-item\').remove();">×</em>
                    </div>';
        }
    }
    //生成html信息
    $html = '<div class="input-group">
                <input type="text" class="form-control valid" value="批量上传图片" readonly="readonly" autocomplete="off" data-name="'.$name.'" id="cimg-'.$idName.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectAttachment" data-input="#cimg-'.$idName.'" data-img="#pimg-'.$idName.'" data-multi="multi">选择图片</button>
                </span>
            </div>
            <div class="input-group multi-img-details ui-sortable" id="pimg-'.$idName.'">'.$html.'</div>';


    return $html;
}
/**
 * Comment: 生成百度富文本编辑器
 * Author: zzw
 * Date: 2020/9/3 14:38
 * @param        $name
 * @param string $value
 * @return string
 */
function tpl_diy_editor_create($name,$value = ''){
    $html = '<textarea class="authload-editor" id="'.$name.'" name="'.$name.'">'.$value.'</textarea>';

    return $html;
}
/**
 * Comment: 地图定位
 * Author: zzw
 * Date: 2020/9/8 16:02
 * @param $addressName
 * @param $lngName
 * @param $latName
 * @param $address
 * @param $lng
 * @param $lat
 * @return string
 */
function tpl_select_address($addressName,$lngName,$latName,$address,$lng,$lat){
    //名称处理
    $idAddressName = str_replace(['[',']'],'_',$addressName);
    $idLngName = str_replace(['[',']'],'_',$lngName);
    $idLatName = str_replace(['[',']'],'_',$latName);
    //生成html信息
    $html = '<div class="input-group">
                <input class="form-control " id="c-'.$idAddressName.'"  name="'.$addressName.'" type="text" value="'.$address.'" required>
                <input class="form-control hide" id="c-'.$idLngName.'"  name="'.$lngName.'" type="text" value="'.$lng.'">
                <input class="form-control hide" id="c-'.$idLatName.'"  name="'.$latName.'" type="text" value="'.$lat.'">
                <span class="btn input-group-addon" data-toggle="addresspicker" data-address-id="c-'.$idAddressName.'" data-lng-id="c-'.$idLngName.'" data-lat-id="c-'.$idLatName.'">地图定位</span>
            </div>';

    return $html;
}
/**
 * Comment: 红包选择器
 * Author: zzw
 * Date: 2020/9/14 15:22
 * @param string $name
 * @param string $id
 * @return string
 */
function tpl_select_redPack($name = '',$id = ''){
    $html = '<div class="input-group">
                <input type="text" placeholder="请选择红包!" name="data[red_pack_name]" readonly="readonly" value="'.$name.'" class="form-control selectRedPack_name" autocomplete="off">
                <input type="text" name="data[red_pack_id]" value="'.$id.'" class="hide selectRedPack_id" autocomplete="off">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectRedPack">选择红包</button>
                </span>
            </div>';
    return $html;
}
/**
 * Comment: 音频选择器
 * Author: zzw
 * Date: 2021/2/1 10:12
 * @param        $name
 * @param string $defaultUrl
 * @return string
 */
function attachment_select_audio($name,$defaultUrl = ''){
    //名称处理
    $idName = str_replace(['[',']'],'_',$name);
    //生成html信息
    $html = '<div class="input-group">
                <input type="text" value="'.$defaultUrl.'" name="'.$name.'" class="form-control" autocomplete="off" id="cimg-'.$idName.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectAudio" data-input="#cimg-'.$idName.'" data-img="#pimg-'.$idName.'">选择音频文件</button>
                </span>
            </div>';
    return $html;
}
/**
 * Comment: 视频选择器
 * Author: zzw
 * Date: 2021/2/26 17:39
 * @param        $name
 * @param string $defaultUrl
 * @return string
 */
function attachment_select_video($name,$defaultUrl = ''){
    //名称处理
    $idName = str_replace(['[',']'],'_',$name);
    $http_url = $defaultUrl;
    if($http_url) $http_url = tomedia($http_url);
    //生成html信息
    $html = '<div class="input-group">
                <input type="text" value="'.$defaultUrl.'" name="'.$name.'" placeholder="请选择视频!" readonly="readonly" class="form-control"  autocomplete="off" id="cimg-'.$idName.'">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-toggle="selectVideo" data-input="#cimg-'.$idName.'" data-img="#pimg-'.$idName.'" >选择视频</button>
                </span>
            </div>
            <div class="input-group " style="margin-top:.5em;">
                <video src="'.$http_url.'" id="pimg-'.$idName.'" controls="controls" loop="loop" style="height: auto!important;max-height: 300px!important;width: auto!important;max-width: 300px!important;border: 1px solid #ddd;padding: 5px;">
                    您的浏览器不支持 video 标签。请更新或者更换浏览器
                </video>
                <em class="close" style="position:absolute; top: 0px; right: -14px;"
                    onclick="$(\'#cimg-'.$idName.'\').val(\'\');$(\'#pimg-'.$idName.'\').attr(\'src\',\'\');">×</em>
            </div>';

    return $html;
}


