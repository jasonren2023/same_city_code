(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-merchant-merchantClerkList-merchantClerkList"],{"002c":function(t,a,e){"use strict";e.r(a);var n=e("05a7"),i=e.n(n);for(var r in n)"default"!==r&&function(t){e.d(a,t,(function(){return n[t]}))}(r);a["default"]=i.a},"05a7":function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var n={data:function(){return{}},mounted:function(){},computed:{loadImage:function(){var t=this,a=t.$store.state.appInfo.loading;return a||""}}};a.default=n},"0a44":function(t,a,e){"use strict";var n;e.d(a,"b",(function(){return i})),e.d(a,"c",(function(){return r})),e.d(a,"a",(function(){return n}));var i=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",[e("v-uni-view",{staticClass:"container"},[e("far-bottom"),e("v-uni-view",{staticClass:"clerklist-content p-r p-left-right-30"},[t._l(t.adminList,(function(a,n){return e("v-uni-view",{staticClass:"m-btm20"},[e("v-uni-view",{staticClass:"menu-style dis-flex flex-x-between flex-y-center"},[e("v-uni-view",{staticClass:"dis-flex flex-y-center"},[e("v-uni-image",{staticClass:"menu-style-bg",attrs:{src:a.avatar}}),e("v-uni-view",{staticClass:"dis-flex flex-dir-column"},[e("v-uni-view",{staticClass:"f-30 color-33 dis-flex flex-y-center",staticStyle:{"padding-bottom":"12upx"}},[e("v-uni-view",[t._v(t._s(a.name))]),1==a.enabled?e("v-uni-image",{staticClass:"iconbox",attrs:{src:t.imgfixUrls+"centerMerchant/clerksuccess.png"}}):t._e(),0==a.enabled?e("v-uni-image",{staticClass:"iconbox",attrs:{src:t.imgfixUrls+"centerMerchant/clerkjian.png"}}):t._e()],1),e("v-uni-view",{staticClass:"f-24 color-99"},[t._v("角色："+t._s(t.ismainList[a.ismain-1]))])],1)],1),e("v-uni-view",{staticClass:"clerklist-btns dis-flex"},["1"!=a.ismain&&"4"!=a.ismain?e("v-uni-view",{staticClass:"edit",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.goEdit(a.id)}}},[t._v("编辑店员")]):t._e(),"1"!=a.ismain&&"4"!=a.ismain?e("v-uni-view",{class:0==a.enabled?"switchOn":"switch",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.changeAdminEnabled(a.id)}}},[t._v(t._s(t.enabledList[Number(a.enabled)]))]):t._e()],1)],1)],1)})),e("v-uni-view",{staticClass:"addbtn",class:1==t.openButton?"":"cantButton",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.getAdminInfo.apply(void 0,arguments)}}},[t._v("添加店员")])],2)],1),e("PopManager",{attrs:{show:t.popShow,type:t.popType}},[e("v-uni-view",{staticClass:"popView"},[e("v-uni-view",{staticClass:"popImg"},[e("span",[t._v("用微信扫一扫，成为店员")]),e("v-uni-view",{staticClass:"imgView"},[e("v-uni-image",{attrs:{src:t.src}})],1)],1)],1),e("v-uni-view",{staticClass:"closeImg",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.closePopShow.apply(void 0,arguments)}}},[e("v-uni-image",{attrs:{src:t.imgfixUrls+"merchant/close.png"}})],1)],1)],1)},r=[]},"0b44":function(t,a,e){var n=e("14b2");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var i=e("4f06").default;i("4a2e5f60",n,!0,{sourceMap:!1,shadowMode:!1})},"148b":function(t,a,e){var n=e("24fb");a=n(!1),a.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.cantButton[data-v-36935a82]{pointer-events:none}.closeImg[data-v-36935a82]{margin:%?40?% auto 0 auto;width:%?42?%;height:%?42?%}.closeImg > uni-image[data-v-36935a82]{width:%?42?%;height:%?42?%}.popView[data-v-36935a82]{width:%?512?%;height:%?537?%;background:#fff;border-radius:%?10?%}.popImg[data-v-36935a82]{text-align:center;padding:%?30?%}.popImg > span[data-v-36935a82]{font-size:%?28?%;color:#333}.imgView[data-v-36935a82]{margin:%?20?% auto 0 auto;width:%?359?%;height:%?359?%}.imgView > uni-image[data-v-36935a82]{width:%?359?%;height:%?359?%}uni-page-body[data-v-36935a82]{background-color:#f7f7f7}.container .color-33[data-v-36935a82]{color:#333}.container .color-99[data-v-36935a82]{color:#999}.container .record-dialog[data-v-36935a82]{z-index:10;position:fixed;background:rgba(0,0,0,.7);top:0;right:0;bottom:0;left:0}.container .record-dialog .record-dialog-main[data-v-36935a82]{border-radius:%?10?%;padding:0 %?76?% %?56?% %?76?%;z-index:11;background:#fff;position:relative}.container .record-dialog .record-dialog-main .qrcode[data-v-36935a82]{width:%?359?%;height:%?359?%}.container .record-dialog .record-dialog-btns[data-v-36935a82]{position:absolute;bottom:%?-64?%;left:0;right:0}.container .record-dialog .record-dialog-btns .btn[data-v-36935a82]{width:%?42?%;height:%?42?%;text-align:center;line-height:%?42?%;border:%?2?% solid #fff;border-radius:50%;color:#fff}.container .record-dialog .record-picker-view[data-v-36935a82]{margin:%?40?% %?30?%;box-sizing:border-box;width:%?540?%;height:%?240?%}.container .circle[data-v-36935a82]{width:%?14?%;height:%?14?%;background:#45e7ab;border-radius:50%;margin-right:4px}.container .addbtn[data-v-36935a82]{position:fixed;left:%?30?%;right:%?30?%;bottom:%?130?%;height:%?90?%;line-height:%?90?%;text-align:center;border-radius:%?45?%;font-size:%?28?%;color:#fff;background:#38f}.container .clerklist-content[data-v-36935a82]{padding-bottom:%?120?%;margin-top:%?37?%}.container .clerklist-content .tool-tab[data-v-36935a82]{padding:%?30?% 0}.container .clerklist-content .tool-tab .tool-tab-title[data-v-36935a82]{padding:0 %?30?%}.container .clerklist-content .tool-tab .tool-item .tool-item-icon[data-v-36935a82]{margin:0 auto %?20?%;display:block;width:%?80?%;height:%?80?%}.container .clerklist-content .menu-style[data-v-36935a82]{background-color:#fff;position:relative;padding:%?30?%;border-radius:%?10?%}.container .clerklist-content .menu-style .iconbox[data-v-36935a82]{width:%?24?%;height:%?24?%;margin-left:%?16?%;border-radius:50%}.container .clerklist-content .menu-style .clerklist-btns[data-v-36935a82]{font-size:%?24?%;text-align:center}.container .clerklist-content .menu-style .clerklist-btns .edit[data-v-36935a82]{width:%?124?%;height:%?44?%;line-height:%?44?%;background:#f5faff;border-radius:6px;color:#38f;margin-right:%?20?%}.container .clerklist-content .menu-style .clerklist-btns .switch[data-v-36935a82]{width:%?76?%;height:%?44?%;line-height:%?44?%;border:%?1?% solid #f62d26;border-radius:%?6?%;color:#f44}.container .clerklist-content .menu-style .clerklist-btns .switchOn[data-v-36935a82]{line-height:%?44?%;width:%?76?%;height:%?44?%;background:#38f;border-radius:%?6?%;color:#fff;font-size:%?24?%;border:%?1?% solid transparent}.container .clerklist-content .menu-style .clerklist-btns .switch.on[data-v-36935a82]{width:%?76?%;height:%?44?%;line-height:%?44?%;color:#fff;background:#38f;border:none}.container .clerklist-content .menu-style .menu-style-bg[data-v-36935a82]{width:%?80?%;height:%?80?%;margin-right:%?20?%;border-radius:%?80?%}body.?%PAGE?%[data-v-36935a82]{background-color:#f7f7f7}',""]),t.exports=a},"14b2":function(t,a,e){var n=e("24fb");a=n(!1),a.push([t.i,".loadlogo-container[data-v-23fdce49]{width:100%;height:100%;background-color:#fff;position:fixed;z-index:999}.loadlogo[data-v-23fdce49]{width:80px;height:80px;\n\t/* margin: -60px 0 0 -60px; */position:fixed;top:50%;left:50%;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);overflow:hidden}.loadlogo .image[data-v-23fdce49]{width:100%;height:100%;overflow:hidden}",""]),t.exports=a},"2e73":function(t,a,e){"use strict";e.r(a);var n=e("e684"),i=e("c09f");for(var r in i)"default"!==r&&function(t){e.d(a,t,(function(){return i[t]}))}(r);e("7f47");var o,s=e("f0c5"),d=Object(s["a"])(i["default"],n["b"],n["c"],!1,null,"437d84fa",null,!1,n["a"],o);a["default"]=d.exports},"3c7d":function(t,a,e){var n=e("24fb");a=n(!1),a.push([t.i,".diy-tabbar[data-v-437d84fa]{border-color:rgba(0,0,0,.33);position:fixed;z-index:9999;height:%?130?%;left:0;background-color:#fff;color:#6e6d6b;bottom:0;width:100%;display:flex}.tabbar-item[data-v-437d84fa]{display:flex;justify-content:center;align-items:center;flex-direction:column;flex:1;font-size:0;color:#6e6d6b;text-align:center;z-index:5;padding-bottom:%?30?%}.tabbar-severImg[data-v-437d84fa]{width:%?84?%;height:%?84?%;position:relative;top:%?-20?%}.tabbar-item .tabbar-item-icon[data-v-437d84fa]{font-size:%?44?%}.tabbar-item.item-on[data-v-437d84fa]{\n\t/* color: #fd4a5f; */}.tabbar-item .image[data-v-437d84fa]{display:inline-block;width:%?100?%;height:%?100?%}.tabbat-item-text[data-v-437d84fa]{padding-top:0;padding-bottom:0;font-size:%?20?%;line-height:1.8;text-align:center}.navstyle-image[data-v-437d84fa]{width:%?60?%;height:%?60?%;background-repeat:no-repeat;background-size:%?60?% %?60?%;display:block;margin:0 auto}.navstyle-3-item[data-v-437d84fa]{padding:%?10?% 0}",""]),t.exports=a},"4ef6":function(t,a,e){var n=e("148b");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var i=e("4f06").default;i("b5642050",n,!0,{sourceMap:!1,shadowMode:!1})},"7f47":function(t,a,e){"use strict";var n=e("cd33"),i=e.n(n);i.a},"864b":function(t,a,e){"use strict";var n;e.d(a,"b",(function(){return i})),e.d(a,"c",(function(){return r})),e.d(a,"a",(function(){return n}));var i=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",[e("v-uni-view",{staticClass:"loadlogo-container"},[e("v-uni-view",{staticClass:"loadlogo"},[e("v-uni-image",{staticClass:"image",attrs:{src:t.loadImage||t.imgfixUrls+"loadlogo.svg",mode:"aspectFill"}})],1)],1)],1)},r=[]},b1da:function(t,a,e){"use strict";var n=e("4ef6"),i=e.n(n);i.a},c09f:function(t,a,e){"use strict";e.r(a);var n=e("da82"),i=e.n(n);for(var r in n)"default"!==r&&function(t){e.d(a,t,(function(){return n[t]}))}(r);a["default"]=i.a},c6b2:function(t,a,e){"use strict";var n=e("0b44"),i=e.n(n);i.a},cb39:function(t,a,e){"use strict";e.r(a);var n=e("864b"),i=e("002c");for(var r in i)"default"!==r&&function(t){e.d(a,t,(function(){return i[t]}))}(r);e("c6b2");var o,s=e("f0c5"),d=Object(s["a"])(i["default"],n["b"],n["c"],!1,null,"23fdce49",null,!1,n["a"],o);a["default"]=d.exports},cd33:function(t,a,e){var n=e("3c7d");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var i=e("4f06").default;i("0486725d",n,!0,{sourceMap:!1,shadowMode:!1})},d2a9:function(t,a,e){"use strict";e.r(a);var n=e("0a44"),i=e("da24");for(var r in i)"default"!==r&&function(t){e.d(a,t,(function(){return i[t]}))}(r);e("b1da");var o,s=e("f0c5"),d=Object(s["a"])(i["default"],n["b"],n["c"],!1,null,"36935a82",null,!1,n["a"],o);a["default"]=d.exports},da24:function(t,a,e){"use strict";e.r(a);var n=e("f8b5"),i=e.n(n);for(var r in n)"default"!==r&&function(t){e.d(a,t,(function(){return n[t]}))}(r);a["default"]=i.a},da82:function(t,a,e){"use strict";var n=e("4ea4");e("99af"),e("c740"),e("caad"),e("c975"),e("a9e3"),e("ac1f"),e("1276"),Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var i=n(e("77ab")),r={data:function(){return{current:0,isPadding:null,menu:null}},props:{tabBarAct:{type:Number,default:function(){return 0}},tabBarData:{default:function(){return null}},pageType:{type:String,default:function(){return""}},pageId:{type:String,default:function(){return""}},menuList:{default:function(){return""}}},mounted:function(){var t=this;t.current=uni.getStorageSync("tabbarindex"),uni.getSystemInfo({success:function(a){var e=a.model,n=["iPhone10,3","iPhone10,6","iPhone11,8","iPhone11,2","iPhone11,6"];t.isPadding=n.includes(e)||-1!==e.indexOf("iPhone X")||-1!==e.indexOf("iPhone12")}}),t.getbtmNavBar()},methods:{onTabItem:function(t,a,e){if(uni.setStorageSync("tabbarindex",e),-1!=t.indexOf("indet"))return console.log("再次刷新 tabar"),i.default.navigationToH5(!1,"".concat(i.default.base,"#/").concat(t)),void window.location.reload();i.default.navigationTo({url:t})},getbtmNavBar:function(){var t=this,a={};if(t.pageType&&(a={type:t.pageType}),t.pageId&&Object.assign(a,{id:t.pageId}),"draw"==t.pageType){t.setData({menu:t.tabBarData});var e=getCurrentPages(),n=e[e.length-1],r=n.route||n.__route__,o=[],s=!1;for(var d in o=t.menu.data,o)o[d].page_path.split("?")[0]==r&&(s=!0);s||(uni.removeStorageSync("tabbarindex"),t.current=0)}else i.default._post_form("&do=BottomMenu",a,(function(a){t.setData({menu:a.data.data});var e=getCurrentPages(),n=e[e.length-1],i=n.route||n.__route__,r=[],o=!1;for(var s in r=t.menu.data,r)r[s].page_path.split("?")[0]==i&&(o=!0);o||(uni.removeStorageSync("tabbarindex"),t.current=0)}))}},computed:{TabBarsData:function(){var t,a=getCurrentPages(),e=a[a.length-1],n=e.route||e.__route__,i={data:this.tabBarData&&this.tabBarData.length>0?this.tabBarData:this.menu},r=e.$mp.query;if(i.data){var o=[];for(var s in i.data.data)o.push(i.data.data[s]);return"pages/mainPages/index/diypage"===n?(n=n+"?i="+r.i+(r["aid"]?"&aid="+r["aid"]:"")+(r["id"]?"&id="+r["id"]:"")+"&type="+r["type"],t=o.findIndex((function(t){return t.linkurl===n})),this.current=t):(t=o.findIndex((function(t){return t.linkurl.split("?")[0]===n})),this.current=t),i.data.data=o,i.data}}}};a.default=r},e684:function(t,a,e){"use strict";var n;e.d(a,"b",(function(){return i})),e.d(a,"c",(function(){return r})),e.d(a,"a",(function(){return n}));var i=function(){var t=this,a=t.$createElement,e=t._self._c||a;return t.TabBarsData?e("v-uni-view",[e("v-uni-view",{staticClass:"diy-tabbar",style:{background:t.TabBarsData?t.TabBarsData.style.bgcolor:"#ffffff","padding-bottom":t.isPadding?"20px":""}},t._l(t.TabBarsData.data,(function(a,n){return e("v-uni-view",{key:n,staticClass:"tabbar-item",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.onTabItem(a.linkurl,a.name,n)}}},["1"===t.TabBarsData.params.navstyle?e("v-uni-view",[e("v-uni-image",{staticClass:"image",attrs:{src:a.imgurl}})],1):t._e(),"0"===t.TabBarsData.params.navstyle?e("v-uni-view",["pages/subPages2/homemaking/postDemand/postDemand"==a.page_path?e("v-uni-view",{staticClass:"tabbar-sever"},[e("v-uni-image",{staticClass:"tabbar-severImg",attrs:{src:t.imgfixUrls+"homemakingImg/enterCheck.png",mode:""}})],1):e("v-uni-view",[e("v-uni-view",{staticClass:"iconfont tabbar-item-icon",class:a.iconclass,style:t.current===n?"color:"+t.TabBarsData.style.iconcoloron:"color:"+t.TabBarsData.style.iconcolor}),e("v-uni-view",{staticClass:"f-24",style:t.current===n?"color:"+t.TabBarsData.style.textcoloron:"color:"+t.TabBarsData.style.textcolor},[t._v(t._s(a.text))])],1)],1):t._e(),"2"===t.TabBarsData.params.navstyle?e("v-uni-view",{staticClass:"navstyle-3-item"},[e("v-uni-view",{staticClass:"navstyle-image",style:{"background-image":t.current===n?"url("+a.select_img+")":"url("+a.default_img+")"}}),e("v-uni-view",{staticClass:"f-24 t-c",style:t.current===n?"color:"+t.TabBarsData.style.textcoloron:"color:"+t.TabBarsData.style.textcolor},[t._v(t._s(a.text))])],1):t._e()],1)})),1)],1):t._e()},r=[]},f8b5:function(t,a,e){"use strict";var n=e("4ea4");Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var i=n(e("77ab")),r=n(e("cb39")),o=n(e("2e73")),s=n(e("a833")),d={data:function(){return{openButton:1,qrcode:null,src:null,popShow:!1,popType:"center",loadlogo:!1,addDialog:!0,ismainList:["店长","核销员","管理员","业务员"],adminList:{},enabledList:["开启","停用"]}},components:{Loadlogo:r.default,TabBars:o.default,PopManager:s.default},computed:{},onShow:function(){},mounted:function(){},onLoad:function(t){var a=this;uni.getStorage({key:"checkStoreid",success:function(t){a.storeid=t.data,a.init(t.data)}})},methods:{closePopShow:function(){var t=this;t.popShow=!1,t.openButton=1,clearInterval(t.t1)},init:function(t){var a=this;a.getAdminList(t)},adminAjax:function(){},getAdminInfo:function(){var t=this;t.openButton=0;var a={storeid:t.storeid};i.default._post_form("&p=store&do=adminInfo",a,(function(a){function e(){var a={qrcode:t.qrcode};i.default._post_form("&p=store&do=adminAjax",a,(function(a){t.userInfo=a.data,a.data.id&&(clearInterval(t.t1),t.popShow=!1,i.default.navigationTo({url:"pages/subPages/merchant/merchantClerkEdit/merchantClerkEdit?newadminid="+a.data.id}))}),(function(a){console.info("过期结束"),clearInterval(t.t1)}))}t.popShow=!0,t.src=a.data.src,t.qrcode=a.data.qrcode,t.qrcode&&(t.t1=setInterval(e,3e3))}),(function(t){}))},getAdminList:function(t){var a=this,e={storeid:a.storeid||t};i.default._post_form("&p=store&do=adminList",e,(function(t){a.adminList=t.data}))},goEdit:function(t){i.default.navigationTo({url:"pages/subPages/merchant/merchantClerkEdit/merchantClerkEdit?adminid="+t})},changeAdminEnabled:function(t){var a=this,e={userid:t};i.default._post_form("&p=store&do=changeAdminEnabled",e,(function(e){uni.showToast({icon:"none",title:"修改店员状态成功",duration:2e3});for(var n=0;n<a.adminList.length;n++)t==a.adminList[n].id&&(0==a.adminList[n].enabled?a.adminList[n].enabled=1:a.adminList[n].enabled=0)}))},navgateTo:function(t,a){switch(a){case"contactway":i.default.navigationTo({url:"pages/subPages/dealer/contactway/contactway"});break;case"withdraw":i.default.navigationTo({url:"pages/subPages/dealer/withdraw/withdraw"});break;case"setshop":i.default.navigationTo({url:"pages/subPages/dealer/setshop/setshop"});break;case"order":i.default.navigationTo({url:"pages/subPages/merchant/merchantUserCenter/merchantUserCenter"});break;case"rank":i.default.navigationTo({url:"pages/subPages/dealer/rank/rank"});break;case"level":i.default.navigationTo({url:"pages/subPages/dealer/level/level"});break;case"client":i.default.navigationTo({url:"pages/subPages/dealer/client/client"});break;case"help":i.default.navigationTo({url:"pages/subPages/dealer/richtext/setrich"});break;case"gener":i.default.navigationTo({url:"pages/subPages/dealer/gener/gener"});break;case"poster":location.href=t.currentTarget.dataset.url;break;case"shops":location.href=t.currentTarget.dataset.url;break}}}};a.default=d}}]);