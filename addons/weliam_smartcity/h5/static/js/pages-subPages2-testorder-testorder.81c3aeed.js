(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages2-testorder-testorder"],{2760:function(n,r,t){var e=t("a5d4");"string"===typeof e&&(e=[[n.i,e,""]]),e.locals&&(n.exports=e.locals);var a=t("4f06").default;a("c16821f4",e,!0,{sourceMap:!1,shadowMode:!1})},"2af0":function(n,r,t){"use strict";t.r(r);var e=t("d555"),a=t("8653");for(var o in a)"default"!==o&&function(n){t.d(r,n,(function(){return a[n]}))}(o);t("302a");var d,s=t("f0c5"),i=Object(s["a"])(a["default"],e["b"],e["c"],!1,null,"1e740e32",null,!1,e["a"],d);r["default"]=i.exports},"302a":function(n,r,t){"use strict";var e=t("2760"),a=t.n(e);a.a},"321c":function(n,r,t){"use strict";var e=t("4ea4");Object.defineProperty(r,"__esModule",{value:!0}),r.default=void 0;var a=e(t("77ab")),o={name:"testorder",data:function(){return{}},onLoad:function(){this.pime()},methods:{ordn:function(){wx.checkBeforeAddOrder({success:function(n){var r={trace_id:n.data.traceId};a.default._post_form("&p=wxchannels&do=addOrder",r,(function(n){a.default._post_form("&p=wxchannels&do=getPaymentParams",{order_id:n.data.data.order_id,out_order_id:n.data.data.out_order_id},(function(n){wx.requestOrderPayment({timeStamp:n.data.payment_params.timeStamp,nonceStr:n.data.payment_params.nonceStr,package:n.data.payment_params.package,paySign:n.data.payment_params.paySign,signType:n.data.payment_params.signType,success:function(n){console.log(n),uni.showToast({title:"支付成功",icon:"success",duration:2e3})},fail:function(n){console.log(n),"requestOrderPayment:fail cancel"==n.errMsg&&uni.showToast({title:"支付失败",icon:"error",duration:2e3})}}),console.log(n)})),console.log(n)}))}})}}};r.default=o},8653:function(n,r,t){"use strict";t.r(r);var e=t("321c"),a=t.n(e);for(var o in e)"default"!==o&&function(n){t.d(r,n,(function(){return e[n]}))}(o);r["default"]=a.a},a5d4:function(n,r,t){var e=t("24fb");r=e(!1),r.push([n.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.testorder[data-v-1e740e32]{width:100vw;height:100vh;background:#fff;overflow:hidden}.ordn[data-v-1e740e32]{margin:%?60?% auto;width:90%;height:%?100?%;background:linear-gradient(0deg,#195ce0,#4b87ff);border-radius:%?10?%;text-align:center;line-height:%?100?%;color:#fff;letter-spacing:%?10?%}',""]),n.exports=r},d555:function(n,r,t){"use strict";var e;t.d(r,"b",(function(){return a})),t.d(r,"c",(function(){return o})),t.d(r,"a",(function(){return e}));var a=function(){var n=this,r=n.$createElement,t=n._self._c||r;return t("v-uni-view",{staticClass:"testorder"})},o=[]}}]);