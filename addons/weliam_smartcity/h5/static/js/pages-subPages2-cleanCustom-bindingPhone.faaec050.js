(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages2-cleanCustom-bindingPhone"],{"1a71":function(n,e,t){var i=t("24fb");e=i(!1),e.push([n.i,"uni-page-body[data-v-7236db76]{background-color:#f8f8f8}body.?%PAGE?%[data-v-7236db76]{background-color:#f8f8f8}",""]),n.exports=e},"1be2":function(n,e,t){"use strict";var i=t("bf05"),o=t.n(i);o.a},"271d":function(n,e,t){"use strict";var i=t("7de9"),o=t.n(i);o.a},"6b8d":function(n,e,t){"use strict";var i;t.d(e,"b",(function(){return o})),t.d(e,"c",(function(){return a})),t.d(e,"a",(function(){return i}));var o=function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("v-uni-view",{staticClass:"binding"},[t("v-uni-view",{staticClass:"img"},[t("v-uni-image",{style:{width:n.imgStyle.width+"px",height:n.imgStyle.height+"px"},attrs:{src:n.logo,mode:""}})],1),t("v-uni-view",{staticStyle:{padding:"50upx"}},[t("v-uni-view",{staticClass:"f-36 f-w"},[n._v("手机绑定")]),t("v-uni-view",{staticClass:"dis-flex inputbox"},[t("v-uni-input",{staticClass:"inputclass",attrs:{placeholder:"请输入手机号",maxlength:"11",type:"number",value:""},model:{value:n.phone,callback:function(e){n.phone=e},expression:"phone"}})],1),0==n.smsver?t("v-uni-view",{staticClass:"dis-flex inputbox",staticStyle:{"margin-top":"40upx"}},[t("v-uni-input",{staticClass:"inputclass",attrs:{placeholder:"请输入验证码",maxlength:"11",type:"number",value:""},model:{value:n.smsCode,callback:function(e){n.smsCode=e},expression:"smsCode"}}),n.min>0?t("v-uni-view",{staticClass:"f-28 col-f4"},[n._v("请等待"+n._s(n.min)+"s")]):t("v-uni-view",{staticClass:"f-28 col-f4",on:{click:function(e){arguments[0]=e=n.$handleEvent(e),n.PIN()}}},[n._v("发送验证码")])],1):n._e(),t("v-uni-view",{staticClass:"bindingBtn",on:{click:function(e){arguments[0]=e=n.$handleEvent(e),n.changeMobile.apply(void 0,arguments)}}},[n._v("绑 定")])],1)],1)},a=[]},"6d43":function(n,e,t){"use strict";var i=t("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var o=i(t("77ab")),a={data:function(){return{min:0,phone:"",getSmsCode:"",smsver:"",smsCode:"",imgStyle:{},logo:""}},onLoad:function(){var n=this;n.smsver=uni.getStorageSync("platformInfor").smsver,n.imgStyle=uni.getStorageSync("imgstyle"),n.logo=uni.getStorageSync("platformInfor").logo},methods:{changeMobile:function(){var n=this;if(""!=n.phone&&11===n.phone.length)if(""!=n.smsCode||0!=n.smsver){var e={mobile:n.phone,mergeflag:"",code:n.smsCode};o.default._post_form("&p=member&do=changeMobile",e,(function(e){n.phone="",n.smsCode="",uni.showToast({icon:"success",title:"绑定成功"}),setTimeout((function(){uni.reLaunch({url:"/pages/mainPages/index/index"})}),1e3)}),(function(e){e.data.data.code&&1===e.data.data.code||"手机号已存在,无法重复绑定"==e.data.message||uni.showModal({title:"友情提示",content:e.data.message,showCancel:!0,success:function(e){if(e.confirm){var t={mobile:n.phone,mergeflag:1,code:n.smsCode};o.default._post_form("&p=member&do=changeMobile",t,(function(e){n.phone="",n.smsCode="",uni.showToast({icon:"success",title:"绑定成功"}),setTimeout((function(){uni.reLaunch({url:"/pages/mainPages/index/index"})}),1e3)}))}}})}))}else uni.showModal({showCancel:!1,content:"请输入验证码"});else uni.showModal({showCancel:!1,content:"请输入正确的手机号"})},PIN:function(){var n=this;if(""!=n.phone&&11===n.phone.length){var e={phone:n.phone,type:2,code:"",time:""};o.default._post_form("&do=PIN",e,(function(e){n.getSmsCode=e.data.code,n.min=60,n.sendcode()}))}else uni.showToast({icon:"none",title:"请输入正确的手机号",duration:2e3})},sendcode:function(){var n=this;0!=n.min&&(n.min--,setTimeout((function(){n.sendcode()}),1e3))}}};e.default=a},"7de9":function(n,e,t){var i=t("1a71");"string"===typeof i&&(i=[[n.i,i,""]]),i.locals&&(n.exports=i.locals);var o=t("4f06").default;o("05a522fb",i,!0,{sourceMap:!1,shadowMode:!1})},"92a9":function(n,e,t){var i=t("24fb");e=i(!1),e.push([n.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.binding .inputbox[data-v-7236db76]{border-bottom:%?1?% solid #ccc;height:%?100?%;line-height:%?100?%}.binding .inputbox .inputclass[data-v-7236db76]{flex:1;text-align:left;height:%?100?%;line-height:%?100?%;font-size:%?28?%}.binding .bindingBtn[data-v-7236db76]{height:%?90?%;line-height:%?90?%;background-color:#f44;border-radius:%?60?%;color:#fff;text-align:center;font-size:%?30?%;margin-top:%?90?%}',""]),n.exports=e},aab3:function(n,e,t){"use strict";t.r(e);var i=t("6d43"),o=t.n(i);for(var a in i)"default"!==a&&function(n){t.d(e,n,(function(){return i[n]}))}(a);e["default"]=o.a},bd14:function(n,e,t){"use strict";t.r(e);var i=t("6b8d"),o=t("aab3");for(var a in o)"default"!==a&&function(n){t.d(e,n,(function(){return o[n]}))}(a);t("1be2"),t("271d");var s,r=t("f0c5"),c=Object(r["a"])(o["default"],i["b"],i["c"],!1,null,"7236db76",null,!1,i["a"],s);e["default"]=c.exports},bf05:function(n,e,t){var i=t("92a9");"string"===typeof i&&(i=[[n.i,i,""]]),i.locals&&(n.exports=i.locals);var o=t("4f06").default;o("17101974",i,!0,{sourceMap:!1,shadowMode:!1})}}]);