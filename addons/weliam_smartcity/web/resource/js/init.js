define(['jquery', 'bootstrap'], function ($, bs) {
    window.redirect = function (url) {
        location.href = url
    }, $(document).on('click', '[data-toggle=refresh]', function (e) {
        e && e.preventDefault();
        var url = $(e.target).data("href");
        url ? window.location = url : window.location.reload()
    }), $(document).on('click', '[data-toggle=back]', function (e) {
        e && e.preventDefault();
        var url = $(e.target).data("href");
        url ? window.location = url : window.history.back()
    });

    function _bindCssEvent(events, callback) {
        var dom = this;

        function fireCallBack(e) {
            if (e.target !== this) {
                return
            }
            callback.call(this, e);
            for (var i = 0; i < events.length; i++) {
                dom.off(events[i], fireCallBack)
            }
        }

        if (callback) {
            for (var i = 0; i < events.length; i++) {
                dom.on(events[i], fireCallBack)
            }
        }
    }

    $.fn.animationEnd = function (callback) {
        _bindCssEvent.call(this, ['webkitAnimationEnd', 'animationend'], callback);
        return this
    };
    $.fn.transitionEnd = function (callback) {
        _bindCssEvent.call(this, ['webkitTransitionEnd', 'transitionend'], callback);
        return this
    };
    $.fn.transition = function (duration) {
        if (typeof duration !== 'string') {
            duration = duration + 'ms'
        }
        for (var i = 0; i < this.length; i++) {
            var elStyle = this[i].style;
            elStyle.webkitTransitionDuration = elStyle.MozTransitionDuration = elStyle.transitionDuration = duration
        }
        return this
    };
    $.fn.transform = function (transform) {
        for (var i = 0; i < this.length; i++) {
            var elStyle = this[i].style;
            elStyle.webkitTransform = elStyle.MozTransform = elStyle.transform = transform
        }
        return this
    };
    $.toQueryPair = function (key, value) {
        if (typeof value == 'undefined') {
            return key
        }
        return key + '=' + encodeURIComponent(value === null ? '' : String(value))
    };
    $.toQueryString = function (obj) {
        var ret = [];
        for (var key in obj) {
            key = encodeURIComponent(key);
            var values = obj[key];
            if (values && values.constructor == Array) {
                var queryValues = [];
                for (var i = 0, len = values.length, value; i < len; i++) {
                    value = values[i];
                    queryValues.push($.toQueryPair(key, value))
                }
                ret = concat(queryValues)
            } else {
                ret.push($.toQueryPair(key, values))
            }
        }
        return ret.join('&')
    };
    $.fn.append2 = function (html, callback) {
        var len = $("body").html().length;
        this.append(html);
        var e = 1,
            interval = setInterval(function () {
                e++;
                var clear = function () {
                    clearInterval(interval);
                    callback && callback()
                };
                if (len != $("body").html().length || e > 1000) {
                    clear()
                }
            }, 1)
    };
    myrequire(['js/tip']);
    myrequire(['js/table']);
    myrequire(['js/biz']);
    if ($('form.form-validate').length > 0 || $('form.form-modal').length > 0) {
        myrequire(['js/form'], function (form) {
            form.init()
        })
    }
    if ($('.multi-img-details').length > 0) {
        myrequire(['jquery.ui'], function () {
            $('.multi-img-details').sortable({scroll: 'false'});
            $('.multi-img-details').sortable('option', 'scroll', false);
        })
    }
    if ($('#myTab').length > 0) {
        window.optionchanged = false;
        $('#myTab a').click(function (e) {
            e.preventDefault(); //阻止a链接的跳转行为
            $(this).tab('show'); //显示当前选中的链接及关联的content
        })
    }
    if ($('.scrollLoading').length > 0) {
        myrequire(['scrollLoading'], function () {
            $(".scrollLoading").scrollLoading();
            var $pop = null;
            $('.scrollLoading').hover(function () {
                var img = $(this).attr('src');
                var obj = this;
                var $pop = util.popover(obj, function ($popover, obj) {
                    obj.$popover = $popover;
                }, '<div><img src="' + img + '" style="max-width:200px; max-height:200px;"></div>');
            }, function () {
                this.$popover.remove();
            });
        })
    }
    if ($('.select2').length > 0) {
        myrequire(['select2'], function () {
            $(".select2").each(function () {
                $(this).select2({}), $(this).hasClass("js-select2") && $(this).change(function () {
                    $(this).parents("form").submit()
                })
            })
        })
    }
    if ($('.js-switch').length > 0) {
        myrequire(['switchery'], function () {
            $('.js-switch').switchery()
        })
    }
    if ($('.js-clip').length > 0) {
        common.copyLink();
    }
    if ($(".form-editor-group").length > 0) {
        $(".form-editor-group .form-editor-btn").click(function () {
            var editor = $(this).closest(".form-editor-group");
            editor.find(".form-editor-show").hide();
            editor.find(".form-editor-edit").css('display', 'table')
        });
        $(".form-editor-group .form-editor-finish").click(function () {
            if ($(this).closest(".form-group").hasClass("has-error")) {
                return
            }
            var editor = $(this).closest(".form-editor-group");
            editor.find(".form-editor-show").show();
            editor.find(".form-editor-edit").hide();
            var input = editor.find(".form-editor-input");
            var value = $.trim(input.val());
            editor.find(".form-editor-text").text(value)
        })
    }
    $(".js-daterange").length > 0 && ($(document).on("click", ".js-daterange .js-btn-custom", function () {
        $(this).siblings().removeClass("btn-primary").addClass("btn-default"), $(this).addClass("btn-primary"), $(this).parent().next(".js-btn-daterange").removeClass("hide"), $(this).parents("form").find(':hidden[name="days"]').val(-1)
    }), require(["daterangepicker"], function () {
        $(".js-daterange").each(function () {
            var a = $(this).data("form");
            $(this).find(".daterange").on("apply.daterangepicker", function (b, c) {
                $(a).submit()
            })
        })
    }));
    $('[data-toggle="popover"]').popover();
    $(document).on("click", '[data-toggle="ajaxModal"]', function (e) {
        e.preventDefault();
        var obj = $(this),
            confirm = obj.data("confirm");
        var handler = function () {
                $("#ajaxModal").remove(), e.preventDefault();
                var url = obj.data("href") || obj.attr("href"),
                    data = obj.data("set"),
                    modal;
                $.ajax(url, {
                    type: "get",
                    dataType: "html",
                    cache: false,
                    data: data
                }).done(function (html) {
                    if (html.substr(0, 8) == '{"status') {
                        json = eval("(" + html + ')');
                        if (json.status == 0) {
                            msg = typeof (json.result) == 'object' ? json.result.message : json.result;
                            tip.msgbox.err(msg || tip.lang.err);
                            return
                        }
                    }
                    modal = $('<div class="modal fade" id="ajaxModal"><div class="modal-body "></div></div>');
                    $(document.body).append(modal), modal.modal('show');
                    myrequire(['js/jquery.gcjs'], function () {
                        modal.append2(html, function () {
                            var form_validate = $('form.form-validate', modal);
                            if (form_validate.length > 0) {
                                $("button[type='submit']", modal).length && $("button[type='submit']", modal).attr("disabled", true);
                                myrequire(['js/form'], function (form) {
                                    form.init();
                                    $("button[type='submit']", modal).length && $("button[type='submit']", modal).removeAttr("disabled")
                                })
                            }
                        })
                    })
                })
            },
            a;
        if (confirm) {
            tip.confirm(confirm, handler)
        } else {
            handler()
        }
    }),
        $(document).on("click", '[data-toggle="ajaxPost"]', function (e) {
            e.preventDefault();
            var obj = $(this),
                confirm = obj.data("confirm"),
                url = obj.data('href') || obj.attr('href'),
                data = obj.data('set') || {},
                html = obj.html();
            handler = function () {
                e.preventDefault();
                if (obj.attr('submitting') == '1') {
                    return
                }
                obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1);
                $.post(url, {
                    data: data
                }, function (ret) {
                    ret = eval("(" + ret + ")");
                    if (ret.status == 1) {
                        tip.msgbox.suc(ret.result.message || tip.lang.success, ret.result.url)
                    } else {
                        tip.msgbox.err(ret.result.message || tip.lang.error, ret.result.url), obj.removeAttr('submitting').html(html)
                    }
                }).fail(function () {
                    obj.removeAttr('submitting').html(html), tip.msgbox.err(tip.lang.exception)
                })
            };
            confirm && tip.confirm(confirm, handler);
            !confirm && handler()
        }),
        $(document).on("click", '[data-toggle="ajaxEdit"]', function (e) {
            var obj = $(this),
                url = obj.data('href') || obj.attr('href'),
                data = obj.data('set') || {},
                html = $.trim(obj.text()),
                required = obj.data('required') || true,
                edit = obj.data('edit') || 'input',
                executionMethod = obj.data('function');
            var oldval = $.trim($(this).text());
            e.preventDefault();
            submit = function () {
                e.preventDefault();
                var val = $.trim(input.val());
                if (required) {
                    if (val == '') {
                        tip.msgbox.err(tip.lang.empty);
                        return
                    }
                }
                if (val == html) {
                    input.remove(), obj.html(val).show();
                    return
                }
                if (url) {
                    $.post(url, {
                        value: val
                    }, function (ret) {
                        ret = eval("(" + ret + ")");
                        if (ret.status == 1) {
                            if (ret.result.data) {
                                obj.html(ret.result.data).show();
                            } else {
                                obj.html(val).show();
                            }
                            tip.msgbox.suc(ret.result.message);
                        } else {
                            tip.msgbox.err(ret.result.message, ret.result.url)
                            if (ret.result.data) {
                                obj.html(ret.result.data).show();
                            }
                        }
                        input.remove()
                    }).fail(function () {
                        input.remove(), tip.msgbox.err(tip.lang.exception)
                    })
                } else {
                    input.remove();
                    obj.html(val).show()
                }
                obj.trigger('valueChange', [val, oldval])
            }, obj.hide().html('<i class="fa fa-spinner fa-spin"></i>');
            var input = $('<input type="text" class="form-control input-sm" style="width: 80%;display: inline;" />');
            if (edit == 'textarea') {
                input = $('<textarea type="text" class="form-control" style="resize:none" rows=3 ></textarea>')
            }
            obj.after(input);
            input.val(html).select().blur(function () {
                submit(input);
            }).keypress(function (e) {
                if (e.which == 13) {
                    submit(input)
                }
            })
        }),
        $(document).on("click", '[data-toggle="ajaxSwitch"]', function (e) {
            e.preventDefault();
            var obj = $(this),
                confirm = obj.data('msg') || obj.data('confirm'),
                othercss = obj.data('switch-css'),
                other = obj.data('switch-other'),
                refresh = obj.data('switch-refresh') || false;
            if (obj.attr('submitting') == '1') {
                return
            }
            var value = obj.data('switch-value'),
                value0 = obj.data('switch-value0'),
                value1 = obj.data('switch-value1');
            if (value === undefined || value0 === undefined || value1 === undefined) {
                return
            }
            var url, css, text, newvalue, newurl, newcss, newtext;
            value0 = value0.split('|');
            value1 = value1.split('|');
            if (value == value0[0]) {
                url = value0[3], css = value0[2], text = value0[1], newvalue = value1[0], newtext = value1[1], newcss = value1[2]
            } else {
                url = value1[3], css = value1[2], text = value1[1], newvalue = value0[0], newtext = value0[1], newcss = value0[2]
            }
            var html = obj.html();
            var submit = function () {
                    $.post(url).done(function (data) {
                        data = eval("(" + data + ")");
                        if (data.status == 1) {
                            if (other && othercss) {
                                if (newvalue == '1') {
                                    $(othercss).each(function () {
                                        if ($(this).data('switch-value') == newvalue) {
                                            this.className = css;
                                            $(this).data('switch-value', value).html(text || html)
                                        }
                                    })
                                }
                            }
                            obj.data('switch-value', newvalue);
                            obj.html(newtext || html);
                            obj[0].className = newcss;
                            refresh && location.reload()
                        } else {
                            obj.html(html), tip.msgbox.err(data.result.message || tip.lang.error, data.result.url)
                        }
                        obj.removeAttr('submitting')
                    }).fail(function () {
                        obj.removeAttr('submitting');
                        obj.button('reset');
                        tip.msgbox.err(tip.lang.exception)
                    })
                },
                a;
            if (confirm) {
                tip.confirm(confirm, function () {
                    obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1), submit()
                })
            } else {
                obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1), submit()
            }
        }),
        //链接选择器
        $(document).on('click', '[data-toggle="selectUrl"]', function () {
            $("#selectUrl").remove();
            var _input = $(this).data('input');
            var _full = $(this).data('full');
            var _platform = $(this).data('platform');
            var _callback = $(this).data('callback') || false;
            var _cbfunction = !_callback ? false : eval("(" + _callback + ")");
            if (!_input && !_callback) {
                return
            }
            var merch = $(".diy-phone").data("merch");
            var url = biz.url('utility/select/comurl', null, merch);
            var store = $(".diy-phone").data("store");
            if (store) {
                url = biz.url('store/diypage/selecturl')
            }
            if (_full) {
                url = url + "&full=1"
            }
            if (_platform) {
                url = url + "&platform=" + _platform
            }
            //在装修页面要判断是添加小程序页面 还是添加公众号页面 获取对应链接地址 (默认获取公众号地址)
            var pageClass = $(".app-content").attr("pageClass") ? $(".app-content").attr("pageClass") : 1;//默认为公众号地址
            if (pageClass == 2) {
                //获取小程序地址
                url = biz.url('utility/select/getWeChatUrl')
            }
            $.ajax(url, {
                type: "get",
                data: {pageClass: pageClass},
                dataType: "html",
                cache: false
            }).done(function (html) {
                modal = $('<div class="modal fade" id="selectUrl"></div>');
                $(document.body).append(modal), modal.modal('show');
                modal.append2(html, function () {
                    $(document).off("click", '#selectUrl nav').on("click", '#selectUrl nav', function () {
                        var _href = $.trim($(this).data("href"));
                        var _type = $.trim($(this).data("type"));
                        var _page_path = $.trim($(this).data("page_path"));
                        if (_input) {
                            $(_input).attr("data-types", _type);
                            $(_input).attr("data-page_path", _page_path);
                            $(_input).val(_href).trigger('change');
                        } else if (_cbfunction) {
                            _cbfunction(_href)
                        }
                        modal.find(".close").click()
                    })
                })
            })
        }),
        //图片选择器(微擎  已弃用)
        $(document).on('click', '[data-toggle="selectImg"]', function () {
            var _input = $(this).data('input');
            var _img = $(this).data('img');
            var _full = $(this).data('full');
            var dest_dir = $('.diy-phone').length > 0 ? $('.diy-phone').data('merch') : '';
            var options = {};
            if (dest_dir) {
                options.dest_dir = 'merch/' + dest_dir
            }
            require(['jquery', 'util'], function ($, util) {
                util.image('', function (data) {
                    var imgurl = data.attachment;
                    if (_full) {
                        imgurl = data.url
                    }
                    if (_input) {
                        $(_input).val(imgurl).trigger('change')
                    }
                    if (_img) {
                        $(_img).attr('src', data.url)
                    }
                }, options)
            })
        }),
        //图标选择器
        $(document).on('click', '[data-toggle="selectIcon"]', function () {
            var _input = $(this).data('input');
            var _element = $(this).data('element');
            if (!_input && !_element) {
                return
            }
            var merch = $(".diy-phone").data("merch");
            var url = biz.url('utility/select/comicon', null, merch);
            $.ajax(url, {
                type: "get",
                dataType: "html",
                cache: false
            }).done(function (html) {
                modal = $('<div class="modal fade" id="selectIcon"></div>');
                $(document.body).append(modal), modal.modal('show');
                modal.append2(html, function () {
                    $(document).off("click", '#selectIcon nav').on("click", '#selectIcon nav', function () {
                        var _class = $.trim($(this).data("class"));
                        if (_input) {
                            $(_input).val(_class).trigger('change')
                        }
                        if (_element) {
                            $(_element).removeAttr("class").addClass("icon " + _class)
                        }
                        modal.find(".close").click()
                    })
                })
            })
        }),
        //商品选择器
        $(document).on('click', '[data-toggle="selectGoods"]', function () {
            $('#selectGoods').remove();
            var the = $(this);
            var page = $(this).attr("page");
            var sid = $(this).attr("sid");
            var where = $(this).parents(".selectGoodsParams").attr('params');
            var params = {};
            if(where){
                params =  JSON.parse(where);
            }
            page = page ? page : 1;
            sid = sid ? sid : 0;
            params['page'] = page;
            params['sid'] = sid;
            var url = biz.url('utility/select/getWholeGoods', params);
            $.ajax(url, {
                type: "get",
                dataType: "html",
                cache: false
            }).done(function (html) {
                modal = $('<div class="modal fade" id="selectGoods">' + html + '</div>');
                modal.modal('show');
                //选择商品
                $(document).on('click', '.determineSelectGoods', function () {
                    var id = $(this).attr("id"),
                        name = $(this).attr("name"),
                        plugin = $(this).attr("plugin"),
                        sid = $(this).attr("sid"),
                        glogo = $(this).attr("logo");
                    //  record = localStorage.getItem("selectGoodsId");
                    //if (record != id) {
                    //将商品的id，name，类型赋值到表单中
                    the.closest(".input-group").find(".selectGoods_name").val(name);
                    the.closest(".input-group").find(".selectGoods_id").val(id);
                    the.closest(".input-group").find(".selectGoods_plugin").val(plugin);
                    the.closest(".input-group").find(".selectGoods_sid").val(sid);
                    the.closest(".input-group").next().find(".selectGoods_logo").attr("src", glogo);
                    //localStorage.setItem('selectGoodsId', id);
                    //}
                    modal.find(".close").click();
                });
            })
        }),
        //红包选择器
        $(document).on('click', '[data-toggle="selectRedPack"]', function () {
            //删除已存在的内容
            $('#selectRedPack').remove();
            let _this = $(this);
            let url = biz.url('utility/select/selectRedPack');
            $.ajax(url, {
                type: "get",
                dataType: "html",
                cache: false
            }).done(function (html) {
                modal = $('<div class="modal fade" id="selectRedPack">' + html + '</div>');
                modal.modal('show');
                //选择红包
                $(document).off("click", '.selectedRedPack').on('click', '.selectedRedPack', function () {
                    //获取基本信息
                    let id = $(this).data('id'),
                        name = $(this).data('name');
                    //将获取的基本信息赋值给指定input 达到选中的操作
                    $(".selectRedPack_name").val(name);
                    $(".selectRedPack_id").val(id);
                    modal.find(".close").click();
                });
            })
        }),
        //用户选择器
        $(document).on('click', '[data-toggle="selectUser"]', function () {
            //参数获取
            let _this = $(this);
            var url = biz.url('utility/select/selectUserInfo');
            //条件信息获取
            var params = _this.data('params');
            //请求获取内容
            $.ajax(url, {
                type: "get",
                dataType: "html",
                data: {params: params},
                cache: false
            }).done(function (html) {
                //判断是否已经存在用户选择器
                if ($("#selectUserInfo").length) {
                    $("#selectUserInfo").html(html);
                    $("#selectUserInfo").css('z-index','1001');
                    $("#selectUserInfo").modal('show');
                } else {
                    modal = $('<div class="modal fade" id="selectUserInfo">' + html + '</div>');
                    modal.modal('show');
                }
                //选择用户
                $(document).off("click", '.determineSelectUser').on('click', '.determineSelectUser', function () {
                    //获取基本信息
                    let user_id = $(this).data('user_id'),
                        avatar = $(this).data('avatar'),
                        nickname = $(this).data('nickname');
                    //将获取的基本信息赋值给指定input 达到选中用户的操作
                    _this.prevAll(".user_nickname").val(nickname);
                    _this.prevAll(".user_mid").val(user_id).change();
                    _this.parent().next().find("img").get(0).src = avatar;
                    $("#selectUserInfo").find(".close").click()
                    //modal.find(".close").click();
                });
            });
        }),
        //商户选择器
        $(document).on('click', '[data-toggle="selectShop"]', function () {
            //参数获取
            let _this = $(this);
            var url = biz.url('utility/select/selectShopInfo');
            //条件信息获取
            var params = _this.data('params');
            //请求获取内容
            $.ajax(url, {
                type: "get",
                dataType: "html",
                data: {params: params},
                cache: false
            }).done(function (html) {
                //判断是否已经存在用户选择器
                if ($("#selectShopInfo").length) {
                    $("#selectShopInfo").html(html);
                    $("#selectShopInfo").modal('show');
                } else {
                    modal = $('<div class="modal fade" id="selectShopInfo">' + html + '</div>');
                    modal.modal('show');
                }
                //选择用户
                $(document).off("click", '.determineSelectShop').on('click', '.determineSelectShop', function () {
                    //获取基本信息
                    let shop_id = $(this).data('shop_id'),
                        storename = $(this).data('storename'),
                        logo = $(this).data('logo');
                    //将获取的基本信息赋值给指定input 达到选中用户的操作
                    _this.prevAll(".shop_name").val(storename);
                    _this.prevAll(".shop_id").val(shop_id);
                    _this.parent().next().find("img").get(0).src = logo;
                    $(document).find(".close").click()
                    //modal.find(".close").click();
                });
            });
        }),
        //状态改变触发器
        $(document).on('change', '.js-switch.tpl_change_status', function () {
            //状态改变控制器 —— 参数获取
            let status, the = $(this);
            url = the.data('url'),
                open = the.data('open'),
                close = the.data('close'),
                checked = the.attr("checked");
            //状态改变控制器 —— 判断当前的操作（开启/关闭；已开启则进行关闭，已关闭则开启
            if (checked) {
                //开启中，执行关闭操作
                the.attr("checked", false);
                status = close;
            } else {
                //关闭中，执行开启操
                the.attr("checked", true);
                status = open;
            }
            //请求进行修改操作
            $.ajax(url, {
                type: "post",
                data: {status: status},
                dataType: "json",
                cache: false
            }).done(function (res) {
                if (res.errno == 0) {
                    tip.msgbox.suc(res.message);
                } else {
                    tip.msgbox.err(res.message);
                    if (checked) {
                        //开启中，执行关闭操作 - 操作失败，开启
                        the.attr("checked", true);
                    } else {
                        //关闭中，执行开启操作 - 操作失败，关闭
                        the.attr("checked", false);
                    }
                }
            })
        }),
        //图文信息抓取
        $(document).on('click', '[data-toggle="importTextInfo"]', function () {
            //参数获取
            let _this = $(this),
                url = biz.url('utility/select/importTextModel'),
                name = _this.prevAll(".diy-ueditor-setting").find('textarea').attr("name"),
                ue = UE.getEditor(name);
            //请求获取内容
            $.ajax(url, {
                type: "get",
                dataType: "html",
                cache: false
            }).done(function (html) {
                //模板弹出
                modal = $('<div class="modal fade" id="selectUserInfo">' + html + '</div>');
                modal.modal('show');
                //抓取信息
                $(document).on('click', '#importDetail', function () {
                    //参数获取
                    let link = $("#importLink").val();
                    //判断链接是否存在
                    if (link.length <= 0) {
                        tip.alert("请输入地址");
                        return false;
                    }
                    //请求后台 获取内容
                    let res = common.ajaxPost('utility/select/importTextInfo', {url: link}, true, false);
                    if (res.errno == 0) {
                        let data = res.data;
                        ue.setContent(data['contents']);
                        modal.modal('hide');
                    } else {
                        tip.alert(res.message);
                    }
                });
            });
        }),
        //附件信息管理
        $(document).on('click', '[data-toggle="selectAttachment"]', function () {
            $('[data-toggle="selectAttachment"]').off("click");
            $("#selectVideoModal").remove();
            $("#selectAudioModal").remove();
            //参数获取
            let _selectButtonThis = $(this),
                _input = _selectButtonThis.data('input'),
                _img = _selectButtonThis.data('img'),
                http_url = '',
                url = '',
                _multi = _selectButtonThis.data('multi'),
                link = biz.url('utility/attachment/index');
            _multi = _multi ? _multi : '';
            //请求获取内容
            $.ajax(link, {
                type: "get",
                data:{multi:_multi},
                dataType: "html",
                cache: false,
            }).done(function (html) {
                //判断是否已经存在弹框  存在则删除
                if($("#attachmentModal").length){
                    $("#attachmentModal").html(html);
                    $("#attachmentModal").modal('show');
                }else{
                    //模板弹出
                    let modal = $('<div class="modal fade" id="attachmentModal">' + html + '</div>');
                    modal.modal('show');
                }
                //选中附件后的操作 —— 单选
                $(document).off("click", '.selectAttachmentButton').on('click', '.selectAttachmentButton', function () {
                    if(_input && _img){
                        //基本信息获取
                        url = $(this).data("url");
                        http_url = $(this).data("http_url");
                        //赋值
                        if(_multi == 'multi'){
                            //多选图片
                            //$(_input).val($(_input).val() + ',' + url);
                            let input_name = $(_input).data('name'),
                                html = '<div class="multi-item">\n' +
                                    '   <img src="'+http_url+'" class="img-responsive img-thumbnail">\n' +
                                    '   <input type="hidden" name="'+input_name+'[]" value="'+url+'">' +
                                    '   <em class="close" title="删除这张图片" onclick="$(this).closest(\'.multi-item\').remove();">×</em>\n' +
                                    '</div>';
                            $(_img).append(html);
                            $(_input).trigger('change');
                        }else{
                            //单选图片
                            if (_img) $(_img).attr('src', http_url);
                            if (_input) $(_input).val(url).trigger('change');
                        }
                    }
                    //完成  关闭弹框
                    _input = _img = http_url = url = '';
                    $("#attachmentModal").modal('hide');
                });
                //选中附件后的操作 —— 多选
                $(document).off("click", '.selectedAttachmentMultiButton').on('click', '.selectedAttachmentMultiButton', function () {
                    if(_input && _img){
                        //循环所有选中内容 进行循环操作
                        $("[name='attachment_check']:checked").each(function(){
                            //基本信息获取
                            url = $(this).siblings('.selectAttachmentButton').data("url");
                            http_url = $(this).siblings('.selectAttachmentButton').data("http_url");
                            //赋值
                            let input_name = $(_input).data('name'),
                                html = '<div class="multi-item">\n' +
                                    '   <img src="'+http_url+'" class="img-responsive img-thumbnail">\n' +
                                    '   <input type="hidden" name="'+input_name+'[]" value="'+url+'">' +
                                    '   <em class="close" title="删除这张图片" onclick="$(this).closest(\'.multi-item\').remove();">×</em>\n' +
                                    '</div>';
                            $(_img).append(html);
                        });
                        $(_input).trigger('change');
                    }
                    //完成  关闭弹框
                    _input = _img = http_url = url = '';
                    $("#attachmentModal").modal('hide');
                });
            });
        }),
        //视频附件管理
        $(document).on('click', '[data-toggle="selectVideo"]', function () {
            $('[data-toggle="selectVideo"]').off("click");
            $("#attachmentModal").remove();
            $("#selectAudioModal").remove();
            //参数获取
            let _selectButtonThis = $(this),
                _input = _selectButtonThis.data('input'),
                _img = _selectButtonThis.data('img'),
                http_url = '',
                url = '',
                link = biz.url('utility/attachment/index');
            //请求获取内容
            $.ajax(link, {
                type: "get",
                data:{mime_type:'video'},
                dataType: "html",
                cache: false,
            }).done(function (html) {
                //判断是否已经存在弹框  存在则删除
                if($("#selectVideoModal").length){
                    $("#selectVideoModal").html(html);
                    $("#selectVideoModal").modal('show');
                }else{
                    //模板弹出
                    let modal = $('<div class="modal fade" id="selectVideoModal">' + html + '</div>');
                    modal.modal('show');
                }
                //选中附件后的操作 —— 单选
                $(document).off("click", '.selectAttachmentButton').on('click', '.selectAttachmentButton', function () {
                    if(_input && _img){
                        //基本信息获取
                        url = $(this).data("url");
                        http_url = $(this).data("http_url");
                        //单选图片
                        if (_img) $(_img).attr('src', http_url);
                        if (_input) $(_input).val(url).trigger('change');
                    }
                    //完成  关闭弹框
                    $("#selectVideoModal").modal('hide');
                });
            });
        }),
        //音频附件管理
        $(document).on('click', '[data-toggle="selectAudio"]', function () {
            $('[data-toggle="selectAudio"]').off("click");
            $("#attachmentModal").remove();
            $("#selectVideoModal").remove();
            //参数获取
            let _selectButtonThis = $(this),
                _input = _selectButtonThis.data('input'),
                _img = _selectButtonThis.data('img'),
                http_url = '',
                url = '',
                link = biz.url('utility/attachment/index');
            //请求获取内容
            $.ajax(link, {
                type: "get",
                data:{mime_type:'audio'},
                dataType: "html",
                cache: false,
            }).done(function (html) {
                //判断是否已经存在弹框  存在则删除
                if($("#selectAudioModal").length){
                    $("#selectAudioModal").html(html);
                    $("#selectAudioModal").modal('show');
                }else{
                    //模板弹出
                    let modal = $('<div class="modal fade" id="selectAudioModal">' + html + '</div>');
                    modal.modal('show');
                }
                //选中附件后的操作 —— 单选
                $(document).off("click", '.selectAttachmentButton').on('click', '.selectAttachmentButton', function () {
                    if(_input && _img){
                        //基本信息获取
                        url = $(this).data("url");
                        http_url = $(this).data("http_url");
                        //单选图片
                        if (_img) $(_img).attr('src', http_url);
                        if (_input) $(_input).val(url).trigger('change');
                    }
                    //完成  关闭弹框
                    $("#selectAudioModal").modal('hide');
                });
            });
        }),
        //抽奖奖品选择器
        $(document).on('click', '[data-toggle="selectDrawPrize"]', function () {
            //参数获取
            let _this = $(this),
                _id = _this.data('id'),
                _name = _this.data('name'),
                _image = _this.data('image'),
                _imageInput = _this.data('image-input'),
                _probability = _this.data('probability'),
                link = biz.url('utility/select/selectDrawPrize');
            //请求获取内容
            $.ajax(link, {
                type: "get",
                dataType: "html",
                cache: false,
            }).done(function (html) {
                //判断是否已经存在弹框  存在则删除
                if($("#drawPrizeModal").length){
                    $("#drawPrizeModal").html(html);
                    $("#drawPrizeModal").modal('show');
                }else{
                    //模板弹出
                    let modal = $('<div class="modal fade" id="drawPrizeModal">' + html + '</div>');
                    modal.modal('show');
                }
                //点击选中内容
                $(document).off("click", '.selectedDrawPrize').on('click', '.selectedDrawPrize', function () {
                    //基本参数信息获取
                    let idContent = $(this).data("id"),
                        nameContent = $(this).data("name"),
                        probabilityContent = $(this).data("probability"),
                        imageContent = $(this).data("image");
                    //赋值
                    $(_id).val(idContent).trigger('change');
                    $(_name).val(nameContent).trigger('change');
                    $(_probability).val(probabilityContent).trigger('change');
                    $(_imageInput).val(imageContent).trigger('change');
                    $(_image).attr("src",imageContent);

                    $("#drawPrizeModal").modal('hide');
                });
            });
        }),
        //生成百度富文本编辑器
        $(".authload-editor").each(function () {
            //基本参数信息获取
            let _this = $(this),
                name = _this.attr("name");
            //判断是否已经存在 存在则删除
            if(typeof(UE) != 'undefined') UE.delEditor(name);
            //生成富文本编辑器
            var ueditoroption = {
                'autoClearinitialContent': false,
                'toolbars': [
                    [
                        //'anchor', //锚点
                        'undo', //撤销
                        'redo', //重做
                        'bold', //加粗
                        'indent', //首行缩进
                        //'snapscreen', //截图
                        'italic', //斜体
                        'underline', //下划线
                        'strikethrough', //删除线
                        'subscript', //下标
                        'fontborder', //字符边框
                        'superscript', //上标
                        'formatmatch', //格式刷
                        'source', //源代码
                        'blockquote', //引用
                        //'pasteplain', //纯文本粘贴模式
                        'selectall', //全选
                        'print', //打印
                        'preview', //预览
                        'horizontal', //分隔线
                        'removeformat', //清除格式
                        'time', //时间
                        'date', //日期
                        //'unlink', //取消链接
                        'insertrow', //前插入行
                        'insertcol', //前插入列
                        'mergeright', //右合并单元格
                        'mergedown', //下合并单元格
                        'deleterow', //删除行
                        'deletecol', //删除列
                        'splittorows', //拆分成行
                        'splittocols', //拆分成列
                        'splittocells', //完全拆分单元格
                        'deletecaption', //删除表格标题
                        'inserttitle', //插入标题
                        'mergecells', //合并多个单元格
                        'deletetable', //删除表格
                        //'cleardoc', //清空文档
                        'insertparagraphbeforetable', //"表格前插入行"
                        //'insertcode', //代码语言
                        'fontfamily', //字体
                        'fontsize', //字号
                        'paragraph', //段落格式
                        //'simpleupload', //单图上传
                        //'insertimage', //多图上传
                        'edittable', //表格属性
                        'edittd', //单元格属性
                        'link', //超链接
                        'emotion', //表情
                        'spechars', //特殊字符
                        'searchreplace', //查询替换
                        'map', //Baidu地图
                        //'gmap', //Google地图
                        //'insertvideo', //视频
                        //'help', //帮助
                        'justifyleft', //居左对齐
                        'justifyright', //居右对齐
                        'justifycenter', //居中对齐
                        'justifyjustify', //两端对齐
                        'forecolor', //字体颜色
                        'backcolor', //背景色
                        'insertorderedlist', //有序列表
                        'insertunorderedlist', //无序列表
                        'fullscreen', //全屏
                        'directionalityltr', //从左向右输入
                        'directionalityrtl', //从右向左输入
                        'rowspacingtop', //段前距
                        'rowspacingbottom', //段后距
                        'pagebreak', //分页
                        //'insertframe', //插入Iframe
                        'imagenone', //默认
                        'imageleft', //左浮动
                        'imageright', //右浮动
                        //'attachment', //附件
                        'imagecenter', //居中
                        //'wordimage', //图片转存
                        'lineheight', //行间距
                        'edittip ', //编辑提示
                        'customstyle', //自定义标题
                        'autotypeset', //自动排版
                        //'webapp', //百度应用
                        'touppercase', //字母大写
                        'tolowercase', //字母小写
                        //'background', //背景
                        //'template', //模板
                        //'scrawl', //涂鸦
                        //'music', //音乐
                        'inserttable', //插入表格
                        //'drafts', // 从草稿箱加载
                        //'charts', // 图表
                    ]
                ],
                'elementPathEnabled': false,
                'initialFrameHeight': 300,
                'focus': false,
                'maximumWords': 99999
            };
            UE.registerUI('myinsertimage', function(editor, uiName) {
                editor.registerCommand(uiName, {
                    execCommand: function() {
                        require(['fileUploader'], function(uploader) {
                            uploader.show(function(imgs) {
                                if(imgs.length == 0) {
                                    return
                                } else if(imgs.length == 1) {
                                    editor.execCommand('insertimage', {
                                        'src': imgs[0]['url'],
                                        '_src': imgs[0]['url'],
                                        'width': '100%',
                                        'alt': imgs[0].filename
                                    })
                                } else {
                                    var imglist = [];
                                    for(i in imgs) {
                                        imglist.push({
                                            'src': imgs[i]['url'],
                                            '_src': imgs[i]['url'],
                                            'width': '100%',
                                            'alt': imgs[i].filename
                                        })
                                    }
                                    editor.execCommand('insertimage', imglist)
                                }
                            }, opts)
                        })
                    }
                });
                var btn = new UE.ui.Button({
                    name: '插入图片',
                    title: '插入图片',
                    cssRules: 'background-position: -726px -77px',
                    onclick: function() {
                        let input_id = "#cimg-"+uiName,
                            src_id = "#pimg-"+uiName,
                            html = '<input  id="cimg-'+uiName+'" style="display: none">' +
                                '<img src="" id="pimg-'+uiName+'" data-toggle="selectAttachment" data-input="#cimg-'+uiName+'" data-img="#pimg-'+uiName+'" style="display: none" data-multi="multi">';
                        $(input_id).remove();
                        $(src_id).remove();
                        //模拟图片上传操作
                        $("body").append(html);
                        $(src_id).click();
                        //图片选取成功
                        $(document).off("change",input_id).on('change',input_id,function () {
                            console.log("成功");
                            //参数获取
                            $(src_id + ' img').each(function () {
                                //console.log($(this).attr("src"));

                                let link = $(this).attr("src");
                                let imgHtml = '<img src="'+link+'">';
                                editor.execCommand('insertimage', {
                                    'src': link,
                                    '_src': link,
                                    'width': '100%',
                                    'alt': link
                                });
                            });
                            //删除内容
                            $(input_id).remove();
                            $(src_id).remove();
                        });


                        //editor.execCommand(uiName)
                    }
                });
                editor.addListener('selectionchange', function() {
                    var state = editor.queryCommandState(uiName);
                    if(state == -1) {
                        btn.setDisabled(true);
                        btn.setChecked(false)
                    } else {
                        btn.setDisabled(false);
                        btn.setChecked(state)
                    }
                });
                return btn
            }, 101);
            UE.registerUI('myinsertvideo', function(editor, uiName) {
                editor.registerCommand(uiName, {
                    execCommand: function() {
                        require(['fileUploader'], function(uploader) {
                            uploader.show(function(video) {
                                if(!video) {
                                    return
                                } else {
                                    var videoType = video.isRemote ? 'iframe' : 'video';
                                    editor.execCommand('insertvideo', {
                                        'url': video.url,
                                        'width': 300,
                                        'height': 200
                                    }, videoType)
                                }
                            }, {
                                fileSizeLimit: 5120000,
                                type: 'video',
                                allowUploadVideo: true,
                                netWorkVideo: true
                            })
                        })
                    }
                });
                var btn = new UE.ui.Button({
                    name: '插入视频',
                    title: '插入视频',
                    cssRules: 'background-position: -320px -20px',
                    onclick: function() {
                        let input_id = "#cimg-"+uiName,
                            src_id = "#pimg-"+uiName,
                            html = '<input  id="cimg-'+uiName+'" style="display: none">' +
                                '<video src="" id="pimg-'+uiName+'" data-toggle="selectVideo" data-input="#cimg-'+uiName+'" data-img="#pimg-'+uiName+'" style="display: none"></video>';
                        $(input_id).remove();
                        $(src_id).remove();
                        //模拟视频上传操作
                        $("body").append(html);
                        $(src_id).click();
                        //视频选取成功
                        $(document).off("change",input_id).on('change',input_id,function () {
                            console.log("成功");
                            //参数获取
                            link = $(src_id).attr("src");
                            editor.execCommand('insertvideo', {
                                'url': link,
                                'width': 300,
                                'height': 200,
                            }, 'video');
                            //删除内容
                            $(input_id).remove();
                            $(src_id).remove();
                        });
                    }
                });
                editor.addListener('selectionchange', function() {
                    var state = editor.queryCommandState(uiName);
                    if(state == -1) {
                        btn.setDisabled(true);
                        btn.setChecked(false)
                    } else {
                        btn.setDisabled(false);
                        btn.setChecked(state)
                    }
                });
                return btn
            }, 20);
            UE.registerUI('myinsertmusic', function(editor, uiName) {
                editor.registerCommand(uiName, {
                    execCommand: function() {
                        console.log('execCommand:' + uiName);
                    }
                });
                var btn = new UE.ui.Button({
                    name: '插入音频',
                    title: '插入音频',
                    cssRules: 'background-position: -20px -40px',
                    onclick: function() {
                        let input_id = "#cimg-"+uiName,
                            src_id = "#pimg-"+uiName,
                            html = '<input  id="cimg-'+uiName+'" style="display: none">' +
                                '<embed src="" id="pimg-'+uiName+'" data-toggle="selectAudio" data-input="#cimg-'+uiName+'" data-img="#pimg-'+uiName+'" style="display: none"></music>';
                        $(input_id).remove();
                        $(src_id).remove();
                        //模拟视频上传操作
                        $("body").append(html);
                        $(src_id).click();
                        //视频选取成功
                        $(document).off("change",input_id).on('change',input_id,function () {
                            //参数获取
                            link = $(src_id).attr("src");
                            console.log(link);
                            let html = "<audio controls='controls' autoplay='autoplay'>\n" +
                                "    <source src='"+link+"'>\n" +
                                "</audio>";

                            editor.execCommand('inserthtml', html);
                            //删除内容
                            $(input_id).remove();
                            $(src_id).remove();
                        });
                    }
                });
                editor.addListener('selectionchange', function() {
                    var state = editor.queryCommandState(uiName);
                    if(state == -1) {
                        btn.setDisabled(true);
                        btn.setChecked(false)
                    } else {
                        btn.setDisabled(false);
                        btn.setChecked(state)
                    }
                });
                return btn
            }, 100);
            UE.getEditor(name, ueditoroption);
        }),
        //腾讯地图定位操作
        $(document).on('click', '[data-toggle="addresspicker"]', function () {
            //参数获取
            let _this = $(this),
                _address = $('#'+_this.data('address-id')),
                _lng = $('#'+_this.data('lng-id')),
                _lat = $('#'+_this.data('lat-id')),
                defaultAddress = _address.val(),
                defaultLng = _lng.val(),
                defaultLat = _lat.val(),
                params = {address:defaultAddress,lat:defaultLat,lng:defaultLng},
                link = biz.url('utility/select/mapPositioning',params);
            //显示地图弹框页面
            layer.open({
                title:'地图定位',
                type: 2,
                content: link,
                shadeClose: true,
                shade: [0.5, '#000000'],
                maxmin: true,
                area:["90%", '90%'],
                end:function () {
                    //信息改变 获取缓存中的信息数据
                    let data = localStorage.getItem("select_address_info");
                    if(data){
                        data = JSON.parse( data );
                        _lng.val(data['lng']);
                        _lat.val(data['lat']);
                        _address.val(data['address']).trigger('change').blur();
                    }
                    //清除缓存信息
                    localStorage.removeItem("select_address_info");
                }
            });
        }),
        //返回上一页触发器
        $(document).on('click', '[data-toggle="returnPreviousPage"]', function () {
            history.back();
        });
    $('#page-loading').hide();
});