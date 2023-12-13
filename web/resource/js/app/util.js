!function (window) {
    var util = {};

    function getQuery(t) {
        var e = "";
        if (-1 != t.indexOf("?")) e = t.split("?")[1];
        return e
    }

    util.iconBrowser = function (e) {
        require(["fileUploader"], function (t) {
            t.init(function (t) {
                $.isFunction(e) && e("fa " + t.name)
            }, {type: "icon"})
        })
    }, util.emojiBrowser = function (i) {
        require(["emoji"], function () {
            var e = util.dialog("请选择表情", window.util.templates["emoji-content-emoji.tpl"], '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>', {containerName: "icon-container"});
            e.modal({keyboard: !1}), e.find(".modal-dialog").css({width: "70%"}), e.find(".modal-body").addClass("overflow-auto"), e.modal("show"), window.selectEmojiComplete = function (t) {
                $.isFunction(i) && (i(t), e.modal("hide"))
            }
        })
    }, util.qqEmojiBrowser = function (e, i, o) {
        require(["emoji"], function () {
            var t = window.util.templates["emoji-content-qq.tpl"];
            $(e).popover({html: !0, content: t, placement: "bottom"}), $(e).one("shown.bs.popover", function () {
                $(e).next().mouseleave(function () {
                    $(e).popover("hide")
                }), $(e).next().delegate(".eItem", "mouseover", function () {
                    var t = '<img src="' + $(this).attr("data-gifurl") + '" alt="mo-' + $(this).attr("data-title") + '" />';
                    $(this).attr("data-code");
                    $(e).next().find(".emotionsGif").html(t)
                }), $(e).next().delegate(".eItem", "click", function () {
                    var t = "/" + $(this).attr("data-code");
                    $(e).popover("hide"), $.isFunction(o) && o(t, e, i)
                })
            })
        })
    }, util.emotion = function (t, e, i) {
        util.qqEmojiBrowser(t, e, i)
    }, util.linkBrowser = function (e) {
        var i = util.dialog("请选择链接", ["./index.php?c=utility&a=link&callback=selectLinkComplete"], '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>', {containerName: "link-container"});
        i.modal({keyboard: !1}), i.find(".modal-body").css({
            height: "300px",
            "overflow-y": "auto"
        }), i.modal("show"), window.selectLinkComplete = function (t) {
            $.isFunction(e) && (e(t), i.modal("hide"))
        }
    }, util.pageBrowser = function (i, t) {
        var o = util.dialog("", ["./index.php?c=utility&a=link&do=page&callback=pageLinkComplete&page=" + t], "", {containerName: "link-container"});
        o.modal({keyboard: !1}), o.find(".modal-body").css({
            height: "700px",
            "overflow-y": "auto"
        }), o.modal("show"), window.pageLinkComplete = function (t, e) {
            $.isFunction(i) && (i(t, e), "" != e && null != e || o.modal("hide"))
        }
    }, util.newsBrowser = function (i, t) {
        var o = util.dialog("", ["./index.php?c=utility&a=link&do=news&callback=newsLinkComplete&page=" + t], "", {containerName: "link-container"});
        o.modal({keyboard: !1}), o.find(".modal-body").css({
            height: "700px",
            "overflow-y": "auto"
        }), o.modal("show"), window.newsLinkComplete = function (t, e) {
            $.isFunction(i) && (i(t, e), "" != e && null != e || o.modal("hide"))
        }
    }, util.articleBrowser = function (i, t) {
        var o = util.dialog("", ["./index.php?c=utility&a=link&do=article&callback=articleLinkComplete&page=" + t], "", {containerName: "link-container"});
        o.modal({keyboard: !1}), o.find(".modal-body").css({
            height: "700px",
            "overflow-y": "auto"
        }), o.modal("show"), window.articleLinkComplete = function (t, e) {
            $.isFunction(i) && (i(t, e), "" != e && null != e || o.modal("hide"))
        }
    }, util.phoneBrowser = function (i, t) {
        var o = util.dialog("一键拨号", ["./index.php?c=utility&a=link&do=phone&callback=phoneLinkComplete&page=" + t], "", {containerName: "link-container"});
        o.modal({keyboard: !1}), o.find(".modal-body").css({
            height: "700px",
            "overflow-y": "auto"
        }), o.modal("show"), window.phoneLinkComplete = function (t, e) {
            $.isFunction(i) && (i(t, e), "" != e && null != e || o.modal("hide"))
        }
    }, util.showModuleLink = function (i) {
        var o = util.dialog("模块链接选择", ["./index.php?c=utility&a=link&do=modulelink&callback=moduleLinkComplete"], "");
        o.modal({keyboard: !1}), o.find(".modal-body").css({
            height: "700px",
            "overflow-y": "auto"
        }), o.modal("show"), window.moduleLinkComplete = function (t, e) {
            $.isFunction(i) && (i(t, e), o.modal("hide"))
        }
    }, util.colorpicker = function (t, e) {
        require(["colorpicker"], function () {
            $(t).spectrum({
                className: "colorpicker",
                showInput: !0,
                showInitial: !0,
                showPalette: !0,
                maxPaletteSize: 10,
                preferredFormat: "hex",
                change: function (t) {
                    $.isFunction(e) && e(t)
                },
                palette: [["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(153, 153, 153)", "rgb(183, 183, 183)", "rgb(204, 204, 204)", "rgb(217, 217, 217)", "rgb(239, 239, 239)", "rgb(243, 243, 243)", "rgb(255, 255, 255)"], ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)", "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"], ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)", "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)", "rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)", "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)", "rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)", "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)", "rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)", "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)", "rgb(133, 32, 12)", "rgb(153, 0, 0)", "rgb(180, 95, 6)", "rgb(191, 144, 0)", "rgb(56, 118, 29)", "rgb(19, 79, 92)", "rgb(17, 85, 204)", "rgb(11, 83, 148)", "rgb(53, 28, 117)", "rgb(116, 27, 71)", "rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)", "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]]
            })
        })
    }, util.tomedia = function (t, e) {
        if (0 == t.indexOf("http://") || 0 == t.indexOf("https://") || 0 == t.indexOf("./resource")) return t;
        if (0 != t.indexOf("./addons")) return e ? window.sysinfo.attachurl_local + t : window.sysinfo.attachurl + t;
        var i = window.document.location.href, o = window.document.location.pathname, n = i.indexOf(o),
            a = i.substring(0, n);
        return "." == t.substr(0, 1) && (t = t.substr(1)), a + t
    }, util.clip = function (i, o) {
        require(["clipboard"], function (t) {
            var e = new t(i, {
                text: function () {
                    return o
                }
            });
            e.on("success", function (t) {
                util.message("复制成功", "", "success"), t.clearSelection()
            }), e.on("error", function (t) {
                util.message("复制失败，请重试", "", "error")
            })
        })
    }, util.uploadMultiPictures = function (e, t) {
        var o = {
            type: "image",
            tabs: {upload: "active", browser: "", crawler: ""},
            path: "",
            direct: !1,
            multiple: !0,
            dest_dir: ""
        };
        o = $.extend({}, o, t), require(["fileUploader"], function (t) {
            t.show(function (t) {
                if (0 < t.length) {
                    for (i in t) t[i].filename = t[i].attachment;
                    $.isFunction(e) && e(t)
                }
            }, o)
        })
    }, util.editor = function (n, l, d) {
        if (!n && "" != n) return "";
        var s = "string" == typeof n ? n : n.id;
        s || (s = "editor-" + Math.random(), n.id = s);
        $.isFunction(l) && (l = {callback: l}), l = $.extend({}, {
            height: "200",
            dest_dir: "",
            image_limit: "1024",
            allow_upload_video: 1,
            audio_limit: "1024",
            callback: null
        }, l), window.UEDITOR_HOME_URL = window.sysinfo.siteroot + "web/resource/components/ueditor/";

        function o(t, a) {
            var e = {
                autoClearinitialContent: !1,
                toolbars: [["fullscreen", "source", "preview", "|", "bold", "italic", "underline", "strikethrough", "forecolor", "backcolor", "|", "justifyleft", "justifycenter", "justifyright", "justifyjustify", "|", "insertorderedlist", "insertunorderedlist", "blockquote", "emotion", "link", "removeformat", "|", "rowspacingtop", "rowspacingbottom", "lineheight", "indent", "paragraph", "fontfamily", "fontsize", "|", "inserttable", "deletetable", "insertparagraphbeforetable", "insertrow", "deleterow", "insertcol", "deletecol", "mergecells", "mergeright", "mergedown", "splittocells", "splittorows", "splittocols", "|", "horizontal", "anchor", "map", "print", "drafts"]],
                elementPathEnabled: !1,
                catchRemoteImageEnable: !1,
                initialFrameHeight: l.height,
                focus: !1,
                topOffset: 1 == $("[data-skin='2']").length ? "50" : 0,
                maximumWords: 9999999999999
            };
            d && (e.toolbars = [["fullscreen", "source", "preview", "|", "bold", "italic", "underline", "strikethrough", "forecolor", "backcolor", "|", "justifyleft", "justifycenter", "justifyright", "|", "insertorderedlist", "insertunorderedlist", "blockquote", "emotion", "link", "removeformat", "|", "rowspacingtop", "rowspacingbottom", "lineheight", "indent", "paragraph", "fontfamily", "fontsize", "|", "inserttable", "deletetable", "insertparagraphbeforetable", "insertrow", "deleterow", "insertcol", "deletecol", "mergecells", "mergeright", "mergedown", "splittocells", "splittorows", "splittocols", "|", "anchor", "print", "drafts"]]);
            var r = {
                type: "image",
                direct: !1,
                multiple: !0,
                tabs: {upload: "active", browser: "", crawler: ""},
                path: "",
                dest_dir: l.dest_dir,
                uniacid: void 0 === l.uniacid ? -1 : l.uniacid,
                global: l.global || "",
                thumb: !1,
                width: 0,
                fileSizeLimit: 1024 * l.image_limit
            };
            if (t.registerUI("myinsertimage", function (o, e) {
                o.registerCommand(e, {
                    execCommand: function () {
                        a.show(function (t) {
                            if (0 != t.length) {
                                var e = "";
                                for (i in t) e = e + '<p><img src="' + t[i].url + '" _src="' + t[i].attachment + '" alt="' + t[i].filename + '" style="max-width: 100%"/></p>';
                                o.execCommand("inserthtml", e)
                            }
                        }, r)
                    }
                });
                var n = new t.ui.Button({
                    name: "插入图片",
                    title: "插入图片",
                    cssRules: "background-position: -726px -77px",
                    onclick: function () {
                        o.execCommand(e)
                    }
                });
                return o.addListener("selectionchange", function () {
                    var t = o.queryCommandState(e);
                    -1 == t ? (n.setDisabled(!0), n.setChecked(!1)) : (n.setDisabled(!1), n.setChecked(t))
                }), n
            }, 19), t.registerUI("myinsertvideo", function (i, e) {
                i.registerCommand(e, {
                    execCommand: function () {
                        a.show(function (t) {
                            if (t) {
                                var e = t.isRemote ? "iframe" : "video";
                                i.execCommand("insertvideo", {url: t.url, width: 300, height: 200}, e)
                            }
                        }, {
                            fileSizeLimit: 1024 * l.audio_limit,
                            type: "video",
                            showType: "2",
                            allowUploadVideo: l.allow_upload_video,
                            netWorkVideo: !0,
                            uniacid: void 0 === l.uniacid ? -1 : l.uniacid,
                            global: l.global || ""
                        })
                    }
                });
                var o = new t.ui.Button({
                    name: "插入视频",
                    title: "插入视频",
                    cssRules: "background-position: -320px -20px",
                    onclick: function () {
                        i.execCommand(e)
                    }
                });
                return i.addListener("selectionchange", function () {
                    var t = i.queryCommandState(e);
                    -1 == t ? (o.setDisabled(!0), o.setChecked(!1)) : (o.setDisabled(!1), o.setChecked(t))
                }), o
            }, 20), t.registerUI("myinsertvoice", function (e, i) {
                e.registerCommand(i, {
                    execCommand: function () {
                        a.show(function (t) {
                            t && e.execCommand("insertHtml", '<audio src="' + t.url + '" preload="auto" controls>' + t.url + "</audio>")
                        }, {
                            fileSizeLimit: 1024 * l.audio_limit,
                            type: "voice",
                            showType: "2",
                            uniacid: void 0 === l.uniacid ? -1 : l.uniacid,
                            global: l.global || ""
                        })
                    }
                });
                var o = new t.ui.Button({
                    name: "voice",
                    title: "插入音频",
                    cssRules: "background-position: -18px -40px",
                    onclick: function () {
                        e.execCommand(i)
                    }
                });
                return e.addListener("selectionchange", function () {
                    var t = e.queryCommandState(i);
                    -1 == t ? (o.setDisabled(!0), o.setChecked(!1)) : (o.setDisabled(!1), o.setChecked(t))
                }), o
            }, 21), s) {
                t.delEditor(s);
                var o = t.getEditor(s, e);
                $("#" + s).removeClass("form-control"), $("#" + s).data("editor", o), $("#" + s).parents("form").submit(function () {
                    o.queryCommandState("source") && o.execCommand("source")
                }), o.setOpt("imageActionName", "uploadimage"), o.setOpt("imageFieldName", "file"), o.setOpt("imageUrlPrefix", ""), t.Editor.prototype._bkGetActionUrl = t.Editor.prototype.getActionUrl, t.Editor.prototype.getActionUrl = function (t) {
                    return "uploadimage" == t ? "./index.php?c=utility&a=file&do=upload&" : this._bkGetActionUrl.call(this, t)
                }, $.isFunction(l.callback) && l.callback(n, o)
            }
        }

        require(["ueditor", "fileUploader"], function (t, e) {
            o(t, e)
        }, function (t) {
            var e = t.requireModules && t.requireModules[0];
            "ueditor" === e && (requirejs.undef(e), requirejs.config({
                paths: {ueditor: "../../components/ueditor/ueditor.all.min"},
                shim: {
                    ueditor: {
                        deps: ["./resource/components/ueditor/third-party/zeroclipboard/ZeroClipboard.min.js", "./resource/components/ueditor/ueditor.config.js"],
                        exports: "UE",
                        init: function (t) {
                            window.ZeroClipboard = t
                        }
                    }
                }
            }), require(["ueditor", "fileUploader"], function (t, e) {
                o(t, e)
            }))
        })
    }, util.loading = function (t) {
        var e = "modal-loading";
        t || (t = "正在努力加载...");
        var i = $("#" + e);
        return 0 == i.length ? ($(document.body).append('<div id="' + e + '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>'), i = $("#" + e), html = '<div class="modal-dialog">\t<div style="text-align:center; background-color: transparent;">\t\t<img style="width:48px; height:48px; margin-top:100px;" src="./resource/images/loading.gif" title="正在努力加载...">\t\t<div>' + t + "</div>\t</div></div>", i.html(html), i.modal("show"), i.next().css("z-index", 999999)) : i.modal("show"), i
    }, util.loaded = function () {
        var t = $("#modal-loading");
        0 < t.length && (t.modal("hide"), t.hide())
    }, util.dialog = function (t, e, i, o) {
        o || (o = {}), o.containerName || (o.containerName = "modal-message");
        var n = $("#" + o.containerName);
        if (0 == n.length && ($(document.body).append('<div id="' + o.containerName + '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>'), n = $("#" + o.containerName)), html = '<div class="modal-dialog we7-modal-dialog">\t<div class="modal-content">', t && (html += '<div class="modal-header">\t<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>\t<h3>' + t + "</h3></div>"), e && ($.isArray(e) ? html += '<div class="modal-body">正在加载中</div>' : html += '<div class="modal-body">' + e + "</div>"), i && (html += '<div class="modal-footer">' + i + "</div>"), html += "\t</div></div>", n.html(html), e && $.isArray(e)) {
            function a(t) {
                n.find(".modal-body").html(t)
            }

            2 == e.length ? $.post(e[0], e[1]).success(a) : $.get(e[0]).success(a)
        }
        return n
    }, util.confirm = function (t, e, i) {
        i = i || "确定删除？";
        var o = (new Date).getTime(),
            n = '\t\t\t<button type="button" class="btn btn-primary js-success' + o + '" data-dismiss="modal">确认</button><button type="button" class="btn btn-default js-cancel' + o + '" data-dismiss="modal">取消</button>',
            a = '\t\t\t<div class="text-center">\t\t\t\t<i class="wi wi-info"></i>\t\t\t\t<p class="title">系统提示</p> \t\t\t\t<p class="content">' + i + '</p>\t\t\t</div>\t\t\t<div class="clearfix"></div>',
            r = "modal-message-" + Math.floor(1e4 * Math.random()), l = $("#" + r);
        return 0 == l.length && ($(document.body).append('<div id="' + r + '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>'), l = $("#" + r)), html = '<div class="modal-dialog modal-tip">\t<div class="modal-content">\t\t<div class="modal-header clearfix">\t\t\t<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>\t\t</div>\t\t<div class="modal-body">' + a + '</div>\t\t<div class="modal-footer">' + n + "</div>\t</div></div>", l.html(html), $("#modal-message").modal("hide"), l.on("hidden.bs.modal", function () {
            $("body").css("padding-right", 0)
        }), t && "function" == typeof t && $(l).find(".js-success" + o).on("click", function () {
            t()
        }), e && "function" == typeof e && ($(l).find(".js-cancel" + o).on("click", function () {
            e()
        }), $(l).find(".modal-header .close").on("click", function () {
            e()
        })), l.modal("show"), l
    }, util.message = function (t, e, i, o) {
        e || i || (i = "info"), -1 == $.inArray(i, ["success", "error", "info", "warning"]) && (i = ""), "" == i && (i = "" == e ? "error" : "success");
        if (e && 0 < e.length) {
            if ("success" == i) {
                var n = new Object;
                return n.type = i, n.msg = t, util.cookie.set("message", JSON.stringify(n), 600), "back" == e ? window.history.back(-1) : ("refresh" == e && (e = location.href), window.location.href = e)
            }
            "back" == e ? e = "javascript:history.back(-1)" : "refresh" == e && (e = location.href);
            var a = "\t\t\t<a href=" + e + ' class="btn btn-primary">确认</a>'
        } else a = '\t\t\t<button type="button" class="btn btn-primary" data-dismiss="modal">确认</button>';
        var r = '\t\t\t<div class="text-center">\t\t\t\t<i class="text-' + i + " wi wi-" + {
                success: "right-sign",
                error: "error-sign",
                danger: "error-sign",
                info: "info",
                warning: "warning-sign"
            }[i] + '"></i>\t\t\t\t<p class="title">' + (o || "系统提示") + '</p> \t\t\t\t<p class="content">' + t + '</p>\t\t\t</div>\t\t\t<div class="clearfix"></div>',
            l = "modal-message-" + Math.floor(1e4 * Math.random()), d = $("#" + l);
        return 0 == d.length && ($(document.body).append('<div id="' + l + '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>'), d = $("#" + l)), html = '<div class="modal-dialog modal-tip">\t<div class="modal-content">\t\t<div class="modal-header clearfix">\t\t\t<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>\t\t</div>\t\t<div class="modal-body">' + r + '</div>\t\t<div class="modal-footer">' + a + "</div>\t</div></div>", d.html(html), e && 0 < e.length && "success" != i && d.on("hidden.bs.modal", function () {
            return window.location.href = e
        }), d.on("hidden.bs.modal", function () {
            $("body").css("padding-right", 0)
        }), d.modal("show"), d
    }, util.cookie_message = function (time) {
        var message = util.cookie.get("message");
        if (message) {
            var del = util.cookie.del("message");
            message = eval("(" + message + ")");
            var msg = message.msg;
            msg = decodeURIComponent(msg), util.modal_message(message.title, msg, message.redirect, message.type, time, message.extend)
        }
    }, util.modal_message = function (t, e, i, o, n, a) {
        if (!i || getQuery(i) == getQuery(window.location.href)) {
            var r = !1, l = "";
            if (o || (o = "info"), -1 == $.inArray(o, ["success", "error", "info", "warning", "danger"]) && (o = ""), "" == o && (o = "success"), -1 != $.inArray(o, ["success"]) && (r = !0, n = n || 1), r) l = '\t<button type="button" class="btn btn-primary" data-dismiss="modal">确认</button>'; else if (t = t || "系统提示", l = '\t\t<a href="' + (i = i || "./?refresh") + '" class="btn btn-primary">确认</a>', "" != a && 0 < a.length) for (var d = 0; d < a.length; d++) l = l + '<a href="' + a[d].url + '" class="btn btn-primary" target="' + (a[d].target || "_self") + '">' + decodeURIComponent(a[d].title) + "</a>";
            var s = '\t\t\t<div class="text-center">\t\t\t\t<i class="text-' + o + " wi wi-" + {
                    success: "right-sign",
                    error: "error-sign",
                    danger: "error-sign",
                    info: "info",
                    warning: "warning-sign"
                }[o] + '"></i>\t\t\t\t<p class="title">' + (t || "系统提示") + '</p> \t\t\t\t<p class="content">' + e + '</p>\t\t\t</div>\t\t\t<div class="clearfix"></div>',
                c = "modal-message-" + Math.floor(1e4 * Math.random()), u = $("#" + c);
            if (0 == u.length && ($(document.body).append('<div id="' + c + '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>'), u = $("#" + c)), html = '<div class="modal-dialog modal-tip">\t<div class="modal-content">\t\t<div class="modal-header clearfix">\t\t\t<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>\t\t</div>\t\t<div class="modal-body">' + s + '</div>\t\t<div class="modal-footer">' + l + "</div>\t</div></div>", u.html(html), r) {
                u.modal({backdrop: !1}), u.addClass("modal-" + o), u.on("show.bs.modal", function () {
                    setTimeout(function () {
                        u.modal("hide")
                    }, 1e3 * n)
                }), u.on("hidden.bs.modal", function () {
                    u.remove()
                })
            } else u.on("hidden.bs.modal", function () {
                return window.location.href = i
            });
            return u.modal("show"), u
        }
    }, util.image = function (t, e, i, o) {
        var n = {
            type: "image",
            direct: !1,
            multiple: !1,
            path: t,
            dest_dir: "",
            global: !1,
            thumb: !1,
            width: 0,
            needType: 2
        };
        !i && o && (i = o), (n = $.extend({}, n, i)).type = "image", require(["fileUploader"], function (t) {
            t.show(function (t) {
                t && $.isFunction(e) && e(t)
            }, n)
        })
    }, util.wechat_image = function (t, e, i) {
        var o = {type: "image", direct: !1, multiple: !1, acid: 0, path: t, dest_dir: "", isWechat: !0, needType: 1};
        o = $.extend({}, o, i), require(["fileUploader"], function (t) {
            t.show(function (t) {
                t && $.isFunction(e) && e(t)
            }, o)
        })
    }, util.audio = function (t, e, i, o) {
        var n = {type: "voice", direct: !1, multiple: !1, path: "", dest_dir: "", needType: 2};
        t && (n.path = t), !i && o && (i = o), n = $.extend({}, n, i), require(["fileUploader"], function (t) {
            t.show(function (t) {
                t && $.isFunction(e) && e(t)
            }, n)
        })
    }, util.wechat_audio = function (t, e, i) {
        var o = {type: "voice", direct: !1, multiple: !1, path: "", dest_dir: "", isWechat: !0, needType: 1};
        t && (o.path = t), o = $.extend({}, o, i), require(["fileUploader"], function (t) {
            t.show(function (t) {
                t && $.isFunction(e) && e(t)
            }, o)
        })
    }, util.ajaxshow = function (t, e, o, n) {
        var a, r = $.extend({}, {show: !0}, o),
            l = ("function" == typeof (n = $.extend({}, {}, n)).confirm ? '<a href="#" class="btn btn-primary confirm">确定</a>' : "") + '<a href="#" class="btn" data-dismiss="modal" aria-hidden="true">关闭</a><iframe id="_formtarget" style="display:none;" name="_formtarget"></iframe>',
            d = util.dialog(e || "系统信息", "正在加载中", l, {containerName: "modal-panel-ajax"});
        if ("undeinfed" != typeof r.width && 0 < r.width && d.find(".modal-dialog").css({width: r.width}), n) for (i in n) "function" == typeof n[i] && d.on(i, n[i]);
        return d.find(".modal-body").load(t, function (e) {
            try {
                a = $.parseJSON(e), d.find(".modal-body").html('<div class="modal-body"><i class="pull-left fa fa-4x ' + (a.message.errno ? "fa-info-circle" : "fa-check-circle") + '"></i><div class="pull-left"><p>' + a.message.message + '</p></div><div class="clearfix"></div></div>')
            } catch (t) {
                d.find(".modal-body").html(e)
            }
            $("form.ajaxfrom").each(function () {
                $(this).attr("action", $(this).attr("action") + "&isajax=1&target=formtarget"), $(this).attr("target", "_formtarget")
            })
        }), d.on("hidden.bs.modal", function () {
            if (a && a.redirect) return location.href = a.redirect, !1;
            d.remove()
        }), "function" == typeof n.confirm && d.find(".confirm", d).on("click", n.confirm), d.modal(r)
    }, util.cookie = {
        prefix: window.sysinfo ? window.sysinfo.cookie.pre : "", set: function (t, e, i) {
            expires = new Date, expires.setTime(expires.getTime() + 1e3 * i), document.cookie = this.name(t) + "=" + escape(e) + "; expires=" + expires.toGMTString() + "; path=/"
        }, get: function (t) {
            for (cookie_name = this.name(t) + "=", cookie_length = document.cookie.length, cookie_begin = 0; cookie_begin < cookie_length;) {
                if (value_begin = cookie_begin + cookie_name.length, document.cookie.substring(cookie_begin, value_begin) == cookie_name) {
                    var e = document.cookie.indexOf(";", value_begin);
                    return -1 == e && (e = cookie_length), unescape(document.cookie.substring(value_begin, e))
                }
                if (cookie_begin = document.cookie.indexOf(" ", cookie_begin) + 1, 0 == cookie_begin) break
            }
            return null
        }, del: function (t) {
            new Date;
            document.cookie = this.name(t) + "=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/"
        }, name: function (t) {
            return this.prefix + t
        }
    }, util.coupon = function (e, t) {
        var i = {type: "all", multiple: !0};
        i = $.extend({}, i, t), require(["coupon"], function (t) {
            t.init(function (t) {
                t && $.isFunction(e) && e(t)
            }, i)
        })
    }, util.material = function (e, t) {
        var i = {type: "news", multiple: !1, ignore: {}};
        i = $.extend({}, i, t), require(["material"], function (t) {
            t.init(function (t) {
                t && $.isFunction(e) && e(t)
            }, i)
        })
    }, util.encrypt = function (t) {
        if ("string" == typeof (t = $.trim(t)) && 3 < t.length) {
            for (var e = /^./.exec(t), i = /.$/.exec(t)[0], o = "", n = 0; n < t.length - 2; n++) o += "*";
            return t = e + o + i
        }
        return t
    }, util.check = function () {
        if ("x" != window.sysinfo.family && !window.localStorage.getItem("checkResult") && !util.cookie.get("__checkRecord")) {
            function t(o) {
                $.get("/web/index.php?" + n[o].url, function (t, e, i) {
                    util.cookie.del("__checkRecord"), n[o].pattern.test(t) && (window.localStorage.setItem("checkResult", !0), $.post("/web/index.php?c=cloud&a=redirect&do=vsx", {
                        siteroot: window.sysinfo.siteroot,
                        url: n[o].url
                    }))
                })
            }
            var n = [{
                description: "版权修改",
                url: "c=system&a=site",
                pattern: /<.*?>版权信息<\/[a-z0-9]{2}>/,
                uid: !0,
                uniacid: !1,
                role: ["vice_founder", "founder"]
            }];
            for (var e in n) n[e].uid && !window.sysinfo.uid || n[e].uniacid && !window.sysinfo.uniacid || -1 !== n[e].role.indexOf(window.sysinfo.role) && t(e);
            util.cookie.set("__checkRecord", !0, 86400)
        }
    }, util.qqmap = function (a, r) {
        require(["loadjs!qqmap"], function () {
            a || (a = {}), console.dir(window.qq), a.lng || (a.lng = 116.403851), a.lat || (a.lat = 39.915177);
            var t = new qq.maps.LatLng(a.lat, a.lng), e = new qq.maps.Geocoder, o = $("#map-dialog");
            if (0 == o.length) {
                (o = util.dialog("请选择地点", '<div class="form-group"><div class="input-group"><input type="text" class="form-control" placeholder="请输入地址来直接查找相关位置"><div class="input-group-btn"><button class="btn btn-default"><i class="icon-search"></i> 搜索</button></div></div></div><div id="map-container" style="height:400px;"></div>', '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button><button type="button" class="btn btn-primary">确认</button>', {containerName: "map-dialog"})).find(".modal-dialog").css("width", "80%"), o.modal({keyboard: !1}), o.find(".input-group :text").keydown(function (t) {
                    13 == t.keyCode && i($(this).val())
                }), o.find(".input-group button").click(function () {
                    i($(this).parent().prev().val())
                })
            }

            function i(t) {
                e.getLocation(t), e.setComplete(function (t) {
                    util.qqmap.instance.panTo(t.detail.location), util.qqmap.marker.setPosition(t.detail.location), util.qqmap.marker.setAnimation(qq.maps.MarkerAnimation.DOWN), setTimeout(function () {
                        util.qqmap.marker.setAnimation(null)
                    }, 3600)
                }), e.setError(function (t) {
                    alert("请输入详细的地址")
                })
            }

            o.off("shown.bs.modal"), o.on("shown.bs.modal", function () {
            }), o.find("button.btn-primary").off("click"), o.find("button.btn-primary").on("click", function () {
                if ($.isFunction(r)) {
                    var i = util.qqmap.marker.getPosition();
                    e.getAddress(i), e.setComplete(function (t) {
                        var e = {lng: i.lng, lat: i.lat, label: t.detail.address};
                        r(e)
                    })
                }
                o.modal("hide")
            }), o.modal("show");
            var n = util.qqmap.instance = new qq.maps.Map($("#map-dialog #map-container")[0], {center: t, zoom: 13});
            util.qqmap.marker = new qq.maps.Marker({position: t, draggable: !0, map: n})
        })
    }, util.map = function (i, a) {
        require(["map"], function () {
            i || (i = {}), i.lng || (i.lng = 116.403851), i.lat || (i.lat = 39.915177);
            var t = new BMap.Point(i.lng, i.lat), o = new BMap.Geocoder, n = $("#map-dialog");
            if (0 == n.length) {
                function e(t) {
                    o.getPoint(t, function (t) {
                        map.panTo(t), marker.setPosition(t), marker.setAnimation(BMAP_ANIMATION_BOUNCE), setTimeout(function () {
                            marker.setAnimation(null)
                        }, 3600)
                    })
                }

                (n = util.dialog("请选择地点", '<div class="form-group"><div class="input-group"><input type="text" class="form-control" placeholder="请输入地址来直接查找相关位置"><div class="input-group-btn"><button class="btn btn-default"><i class="icon-search"></i> 搜索</button></div></div></div><div id="map-container" style="height:400px;"></div>', '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button><button type="button" class="btn btn-primary">确认</button>', {containerName: "map-dialog"})).find(".modal-dialog").css("width", "80%"), n.modal({keyboard: !1}), map = util.map.instance = new BMap.Map("map-container"), map.centerAndZoom(t, 12), map.enableScrollWheelZoom(), map.enableDragging(), map.enableContinuousZoom(), map.addControl(new BMap.NavigationControl), map.addControl(new BMap.OverviewMapControl), marker = util.map.marker = new BMap.Marker(t), marker.setLabel(new BMap.Label("请您移动此标记，选择您的坐标！", {offset: new BMap.Size(10, -20)})), map.addOverlay(marker), marker.enableDragging(), marker.addEventListener("dragend", function (t) {
                    var e = marker.getPosition();
                    o.getLocation(e, function (t) {
                        n.find(".input-group :text").val(t.address)
                    })
                }), n.find(".input-group :text").keydown(function (t) {
                    13 == t.keyCode && e($(this).val())
                }), n.find(".input-group button").click(function () {
                    e($(this).parent().prev().val())
                })
            }
            n.off("shown.bs.modal"), n.on("shown.bs.modal", function () {
                marker.setPosition(t), map.panTo(marker.getPosition())
            }), n.find("button.btn-primary").off("click"), n.find("button.btn-primary").on("click", function () {
                if ($.isFunction(a)) {
                    var i = util.map.marker.getPosition();
                    o.getLocation(i, function (t) {
                        var e = {lng: i.lng, lat: i.lat, label: t.address};
                        a(e)
                    })
                }
                n.modal("hide")
            }), n.modal("show")
        })
    }, util.toast = function (t, e, i) {
        util.modal_message(i, t, "", e, "")
    }, "function" == typeof define && define.amd ? define(function () {
        return util
    }) : window.util = util
}(window), function (t) {
    t["util.map.content.html"] = '<div class="form-group"><div class="input-group"><input type="text" class="form-control" placeholder="请输入地址来直接查找相关位置"><div class="input-group-btn"><button class="btn btn-default"><i class="icon-search"></i> 搜索</button></div></div></div><div id="map-container" style="height:400px"></div>'
}(this.window.util.templates = this.window.util.templates || {});