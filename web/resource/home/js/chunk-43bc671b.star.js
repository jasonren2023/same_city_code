(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["chunk-43bc671b"], {
    "287d": function (t, e, a) {
    }, "2ef9": function (t, e, a) {
        "use strict";
        var i = a("287d"), s = a.n(i);
        s.a
    }, "4a06": function (t, e, a) {
    }, "7f7f": function (t, e, a) {
        var i = a("86cc").f, s = Function.prototype, n = /^\s*function ([^ (]*)/, o = "name";
        o in s || a("9e1e") && i(s, o, {
            configurable: !0, get: function () {
                try {
                    return ("" + this).match(n)[1]
                } catch (t) {
                    return ""
                }
            }
        })
    }, "86b2": function (t, e, a) {
        "use strict";
        var i = function () {
            var t = this, e = t.$createElement, a = t._self._c || e;
            return "account" == t.item.list_type ? a("div", {staticClass: "star-item star-item--account"}, [a("div", {staticClass: "star-item__box"}, [a("div", {staticClass: "star-item__info"}, [a("img", {
                directives: [{
                    name: "lazy",
                    rawName: "v-lazy",
                    value: t.item.logo,
                    expression: "item.logo"
                }], staticClass: "star-item__logo account-logo", attrs: {alt: ""}, on: {
                    click: function (e) {
                        return t.goUrl(t.item.switchurl)
                    }
                }
            }), a("div", {
                staticClass: "star-item__content", on: {
                    click: function (e) {
                        return t.goUrl(t.item.switchurl)
                    }
                }
            }, [a("div", {staticClass: "star-item__name text-over"}, [t._v(t._s(t.item.name))]), a("div", {staticClass: "star-item__desc text-over"}, [t._v(t._s(t.item.type_name) + " " + t._s(1 == t.item.support_version && t.item.current_version ? "/ 版本" + t.item.current_version.version : ""))])]), a("div", {staticClass: "star-item__right"}, [a("div", {
                staticClass: "star-item__star",
                class: {active: 1 == t.item.is_star},
                on: {
                    click: function (e) {
                        return e.stopPropagation(), t.changeStar(t.item)
                    }
                }
            }, [a("el-popover", {
                attrs: {
                    placement: "right",
                    "popper-class": "star-popover",
                    "open-delay": 500,
                    "close-delay": "100",
                    trigger: "hover",
                    content: 1 == t.item.is_star ? "取消星标" : "设为星标"
                }
            }, [a("i", {
                staticClass: "wi wi-star item",
                attrs: {slot: "reference"},
                slot: "reference"
            })])], 1), t.item.manageurl ? a("el-popover", {
                attrs: {
                    placement: "right-start",
                    "open-delay": 500,
                    "close-delay": 100,
                    "popper-class": "star-popover",
                    trigger: "hover",
                    content: "进入管理"
                }
            }, [a("i", {
                staticClass: "wi wi-setting-o item", attrs: {slot: "reference"}, on: {
                    click: function (e) {
                        return t.goUrl(t.item.manageurl)
                    }
                }, slot: "reference"
            })]) : t._e(), t.showMove ? a("el-popover", {
                attrs: {
                    placement: "right-start",
                    "open-delay": 500,
                    "close-delay": 100,
                    "popper-class": "star-popover",
                    trigger: "hover",
                    content: "拖动排序"
                }
            }, [a("i", {
                staticClass: "wi wi-sortable star-item__move item",
                attrs: {slot: "reference"},
                on: {
                    mouseenter: function (e) {
                        return t.changeMove(!1)
                    }, mouseup: function (e) {
                        return t.changeMove(!0)
                    }, mouseleave: function (e) {
                        e.stopPropagation(), !t.isMove && t.changeMove(!0)
                    }
                },
                slot: "reference"
            })]) : t._e()], 1)]), a("div", {staticClass: "star-item__footer"}, [a("div", {
                staticClass: "star-item__go",
                on: {
                    click: function (e) {
                        return t.goChild(t.item.uniacid)
                    }
                }
            }, [a("i", {staticClass: "wi wi-apply-o icon"}), t._v("平台应用模块\n      ")])])])]) : "module" == t.item.list_type ? a("div", {staticClass: "star-item star-item--module"}, [a("div", {staticClass: "star-item__box"}, [a("div", {staticClass: "star-item__info"}, [a("img", {
                directives: [{
                    name: "lazy",
                    rawName: "v-lazy",
                    value: t.item.logo,
                    expression: "item.logo"
                }], staticClass: "star-item__logo module-logo", attrs: {alt: ""}, on: {
                    click: function (e) {
                        return t.goUrl(t.item.switchurl)
                    }
                }
            }), a("div", {
                staticClass: "star-item__content", on: {
                    click: function (e) {
                        return t.goUrl(t.item.switchurl)
                    }
                }
            }, [a("div", {staticClass: "star-item__name text-over"}, [t._v(t._s(t.item.title))])]), a("div", {staticClass: "star-item__right"}, [a("div", {
                staticClass: "star-item__star",
                class: {active: 1 == t.item.is_star},
                on: {
                    click: function (e) {
                        return e.stopPropagation(), t.changeStar(t.item)
                    }
                }
            }, [a("el-popover", {
                attrs: {
                    placement: "right",
                    "open-delay": 500,
                    "close-delay": 100,
                    "popper-class": "star-popover",
                    width: "60",
                    trigger: "hover",
                    content: 1 == t.item.is_star ? "取消星标" : "设为星标"
                }
            }, [a("i", {
                staticClass: "wi wi-star item",
                attrs: {slot: "reference"},
                slot: "reference"
            })])], 1), t.showMove ? a("el-popover", {
                attrs: {
                    placement: "right",
                    "open-delay": 500,
                    "close-delay": 100,
                    "popper-class": "star-popover",
                    trigger: "hover",
                    content: "拖动排序"
                }
            }, [a("i", {
                staticClass: "wi wi-sortable star-item__move item",
                attrs: {slot: "reference"},
                on: {
                    mouseenter: function (e) {
                        return t.changeMove(!1)
                    }, mouseup: function (e) {
                        return t.changeMove(!0)
                    }, mouseleave: function (e) {
                        e.stopPropagation(), !t.isMove && t.changeMove(!0)
                    }
                },
                slot: "reference"
            })]) : t._e()], 1)]), a("div", {staticClass: "star-item__footer"}, [t.item.default_account ? [a("div", {staticClass: "star-item__account text-over"}, [t._v("\n          所属平台： " + t._s(t.item.default_account.name) + "\n        ")]), a("div", {staticClass: "star-item__accountblock"}), a("img", {
                directives: [{
                    name: "lazy",
                    rawName: "v-lazy",
                    value: t.item.default_account.logo,
                    expression: "item.default_account.logo"
                }], staticClass: "star-item__accountlogo account-logo", attrs: {alt: ""}
            })] : t._e()], 2)])]) : "system_welcome_module" == t.item.list_type ? a("div", {staticClass: "star-item star-item--welcome"}, [a("div", {staticClass: "star-item__box"}, [a("div", {staticClass: "star-item__info"}, [a("img", {
                directives: [{
                    name: "lazy",
                    rawName: "v-lazy",
                    value: t.item.logo,
                    expression: "item.logo"
                }], staticClass: "star-item__logo module-logo", attrs: {alt: ""}, on: {
                    click: function (e) {
                        return t.goUrl(t.item.manageurl, "_blank")
                    }
                }
            }), a("div", {
                staticClass: "star-item__content", on: {
                    click: function (e) {
                        return t.goUrl(t.item.manageurl, "_blank")
                    }
                }
            }, [a("div", {staticClass: "star-item__name text-over"}, [t._v(t._s(t.item.title))]), a("div", {staticClass: "star-item__desc text-over"}, [t._v(t._s(t.item.bind_domain || "未设置绑定域名"))])]), a("div", {staticClass: "star-item__right"})]), a("div", {staticClass: "star-item__footer text-align"}, [a("el-button", {
                attrs: {type: "text"},
                on: {
                    click: function (e) {
                        return t.showBindDomain(t.item)
                    }
                }
            }, [t._v("设置域名")])], 1)]), a("el-dialog", {
                staticClass: "we7-dialog dialog--domain",
                attrs: {title: "设置域名", visible: t.showBindDomainStatus},
                on: {
                    "update:visible": function (e) {
                        t.showBindDomainStatus = e
                    }
                }
            }, [a("el-alert", {
                attrs: {
                    closable: !1,
                    type: "warning"
                }
            }, [a("i", {staticClass: "wi wi-info"}), t._v("绑定域名后，直接访问域名即可访问该应用；\n      ")]), a("el-alert", {
                attrs: {
                    closable: !1,
                    type: "warning"
                }
            }, [a("i", {staticClass: "wi wi-info"}), t._v("绑定域名，只支持一级域名和二级域名；\n      ")]), a("el-alert", {
                attrs: {
                    closable: !1,
                    type: "warning"
                }
            }, [a("i", {staticClass: "wi wi-info"}), t._v("请注意一定要将绑定的域名解析到本服务器IP并绑定到系统网站目录。\n      ")]), a("el-alert", {
                attrs: {
                    closable: !1,
                    type: "warning"
                }
            }, [a("i", {staticClass: "wi wi-info"}), t._v("多个域名以逗号隔开。\n      ")]), a("el-input", {
                attrs: {placeholder: "请输入访问域名，以http://或者htpps://开头"},
                model: {
                    value: t.editItem.bind_domain_edit, callback: function (e) {
                        t.$set(t.editItem, "bind_domain_edit", e)
                    }, expression: "editItem.bind_domain_edit"
                }
            }), a("div", {
                staticClass: "dialog-footer",
                attrs: {slot: "footer"},
                slot: "footer"
            }, [a("el-button", {
                staticClass: "el-button--base",
                attrs: {type: "primary"},
                on: {click: t.bindDomain}
            }, [t._v("确 定")]), a("el-button", {
                staticClass: "el-button--base", on: {
                    click: function (e) {
                        e.stopPropagation(), t.showBindDomainStatus = !1
                    }
                }
            }, [t._v("取 消")])], 1)], 1)], 1) : t._e()
        }, s = [], n = (a("7f7f"), {
            name: "we7StarItem",
            props: {item: Object, showMove: {type: Boolean, default: !1}, isMove: {type: Boolean, default: !1}},
            data: function () {
                return {editItem: {}, showBindDomainStatus: !1}
            },
            methods: {
                goChild: function (t) {
                    this.$router.push("/appList/" + t)
                }, goUrl: function (t, e) {
                    window.open(t, e || "_self")
                }, changeMove: function (t) {
                    this.$emit("changeMove", t)
                }, changeStar: function (t) {
                    var e = this, a = {
                        type: "account" == t.list_type ? 1 : 2,
                        uniacid: "account" == t.list_type ? t.uniacid : t.default_account && t.default_account.uniacid
                    };
                    "module" == t.list_type && (a.module_name = t.name), this.$http.post("/index.php?c=account&a=display&do=setting_star", a).then(function (a) {
                        e.$message({
                            message: a || "修改成功",
                            type: "success"
                        }), t.is_star = 1 == t.is_star ? 2 : 1, e.$emit("changeStar", t)
                    })
                }, showBindDomain: function (t) {
                    this.editItem = t, this.$set(this.editItem, "bind_domain_edit", this.editItem.bind_domain), this.showBindDomainStatus = !0
                }, bindDomain: function () {
                    var t = this;
                    this.$http.post("/index.php?c=module&a=display&do=set_system_welcome_domain", {
                        domain: this.editItem.bind_domain_edit,
                        module_name: this.editItem.name
                    }).then(function () {
                        t.$message({
                            message: "设置成功",
                            type: "success"
                        }), t.editItem.bind_domain = t.editItem.bind_domain_edit, t.showBindDomainStatus = !1
                    })
                }
            }
        }), o = n, c = (a("b7a7"), a("2877")), r = Object(c["a"])(o, i, s, !1, null, null, null);
        e["a"] = r.exports
    }, a787: function (t, e, a) {
    }, b7a7: function (t, e, a) {
        "use strict";
        var i = a("4a06"), s = a.n(i);
        s.a
    }, bf60: function (t, e, a) {
        "use strict";
        var i = a("a787"), s = a.n(i);
        s.a
    }, ef9b: function (t, e, a) {
        "use strict";
        a.r(e);
        var i = function () {
                var t = this, e = t.$createElement, a = t._self._c || e;
                return a("div", {staticClass: "we7-page"}, [a("we7-account-header-info", {
                    attrs: {
                        account: t.accountInfo,
                        showDelete: !1
                    }
                }), a("div", {staticClass: "star-list"}, [t._l(t.list, function (t, e) {
                    return [a("we7-star-item", {key: e, attrs: {item: t}})]
                }), 0 == t.list.length ? a("div", {staticClass: "bottom"}, [t._v("\n        暂无应用\n    ")]) : t._e()], 2), a("el-pagination", {
                    attrs: {
                        background: "",
                        "hide-on-single-page": "",
                        "page-size": t.page_size,
                        total: t.total,
                        "current-page": t.page,
                        layout: "prev, pager, next"
                    }, on: {
                        "update:pageSize": function (e) {
                            t.page_size = e
                        }, "update:page-size": function (e) {
                            t.page_size = e
                        }, "update:currentPage": function (e) {
                            t.page = e
                        }, "update:current-page": function (e) {
                            t.page = e
                        }, "current-change": t.getList
                    }
                })], 1)
            }, s = [], n = (a("7f7f"), function () {
                var t = this, e = t.$createElement, a = t._self._c || e;
                return a("div", {staticClass: "account-header-info"}, [a("img", {
                    directives: [{
                        name: "lazy",
                        rawName: "v-lazy",
                        value: t.account.logo,
                        expression: "account.logo"
                    }], key: t.account.logo, staticClass: "account-logo", attrs: {alt: ""}
                }), a("div", {staticClass: "info"}, [a("div", {staticClass: "title"}, [t._v(t._s(t.account.name))]), a("div", {staticClass: "type"}, [a("i", {class: t.account.type_class}), t._v(t._s(t.account.type_name))])]), t.account.current_user_role != t.CONSTANT.ACCOUNT_MANAGE_NAME_FOUNDER && t.account.current_user_role != t.CONSTANT.ACCOUNT_MANAGE_NAME_OWNER && t.showDelete ? t._e() : a("div", {staticClass: "action"}, [a("a", {
                    staticClass: "el-button el-button--primary",
                    attrs: {href: t.account.switchurl_full, type: "primary"}
                }, [t._v("进入" + t._s(t.account.type_name))]), t.showDelete ? a("el-button", {
                    attrs: {type: "primary"},
                    on: {
                        click: function (e) {
                            return t.deleteAccount(t.account.uniacid)
                        }
                    }
                }, [t._v("停用")]) : t._e()], 1)])
            }), o = [], c = {
                name: "we7AccountHeaderInfo",
                props: {account: Object, showDelete: {type: Boolean, default: !0}},
                computed: {
                    typeList: function () {
                        return this.$store.state.uni_account_type_sign
                    }, CONSTANT: function () {
                        return this.$store.state.CONSTANT
                    }
                }
            }, r = c, l = (a("2ef9"), a("2877")), u = Object(l["a"])(r, n, o, !1, null, null, null), m = u.exports,
            d = a("86b2"), _ = {
                name: "starChild", components: {we7AccountHeaderInfo: m, we7StarItem: d["a"]}, data: function () {
                    return {list: [], accountInfo: {}, page_size: 1, total: 1, page: 1}
                }, methods: {
                    getInfo: function () {
                        var t = this;
                        this.$http.get("/index.php?c=account&a=post", {params: {uniacid: this.$route.params.uniacid}}).then(function (e) {
                            t.accountInfo = e, t.addDom(e.name)
                        })
                    }, getList: function () {
                        var t = this;
                        this.$http.get("/index.php?c=account&a=display&do=account_modules", {params: {uniacid: this.$route.params.uniacid}}).then(function (e) {
                            t.list = e.list, t.page_size = e.page_size, t.total = e.total
                        })
                    }, addDom: function (t) {
                        var e = document.querySelector(".header-info-common"), a = document.createElement("div");
                        a.className = "header-info-common__breadcrumb";
                        var i = '<a href="./home.php" class="home"><i class="wi wi-home"></i></a><span class="separator"> <i class="wi wi-angle-right"></i> </span><div class="item">' + t + "</div>";
                        a.innerHTML = i, e && e.appendChild(a)
                    }
                }, created: function () {
                    this.getInfo(), this.getList()
                }, beforeDestroy: function () {
                    var t = document.querySelector(".header-info-common"),
                        e = document.querySelector(".header-info-common .header-info-common__breadcrumb");
                    t && e && t.removeChild(e)
                }
            }, p = _, v = (a("bf60"), Object(l["a"])(p, i, s, !1, null, null, null));
        e["default"] = v.exports
    }
}]);