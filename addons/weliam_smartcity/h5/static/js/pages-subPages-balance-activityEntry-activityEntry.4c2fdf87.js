(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-balance-activityEntry-activityEntry"],{"0251":function(t,e,i){"use strict";var a;i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return r})),i.d(e,"a",(function(){return a}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",{staticClass:"container-image"},[i("v-uni-view",{staticClass:"iamges-box"},[i("v-uni-image",{attrs:{src:t.propsImagesSrc?t.propsImagesSrc:t.imageSrc,mode:"widthFix"}}),"Data"===t.propsdiyTitleType?[i("v-uni-view",{staticClass:"title f-24 col-9"},[t._v(t._s(t.propsdiyTitle?t.propsdiyTitle:1!=t.languageStatus?"暂无数据，快去逛逛吧~":"쇼핑하러 가기"))])]:t._e(),"packet"===t.propsdiyTitleType?[i("v-uni-view",{staticClass:"title f-24 col-9 m-btm20"},[t._v("您还没有红包，去红包广场领取吧！")]),i("v-uni-view",{staticClass:"navPacket f-24",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.navgateTo()}}},[t._v("立即去领取")])]:t._e()],2)],1)},r=[]},"39c8":function(t,e,i){"use strict";i.r(e);var a=i("0251"),n=i("3e8b");for(var r in n)"default"!==r&&function(t){i.d(e,t,(function(){return n[t]}))}(r);i("ab89");var c,s=i("f0c5"),o=Object(s["a"])(n["default"],a["b"],a["c"],!1,null,"293e65cc",null,!1,a["a"],c);e["default"]=o.exports},"3e8b":function(t,e,i){"use strict";i.r(e);var a=i("f433"),n=i.n(a);for(var r in a)"default"!==r&&function(t){i.d(e,t,(function(){return a[t]}))}(r);e["default"]=n.a},"48d0":function(t,e,i){"use strict";i.r(e);var a=i("e433"),n=i("8acb");for(var r in n)"default"!==r&&function(t){i.d(e,t,(function(){return n[t]}))}(r);i("e177c");var c,s=i("f0c5"),o=Object(s["a"])(n["default"],a["b"],a["c"],!1,null,"7e53cd3e",null,!1,a["a"],c);e["default"]=o.exports},"8acb":function(t,e,i){"use strict";i.r(e);var a=i("924e"),n=i.n(a);for(var r in a)"default"!==r&&function(t){i.d(e,t,(function(){return a[t]}))}(r);e["default"]=n.a},"8d43":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.activityEntry[data-v-7e53cd3e]{width:100vw;height:100vh;background-size:100% 100%}',""]),t.exports=e},"924e":function(t,e,i){"use strict";var a=i("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=a(i("5530")),r=a(i("77ab")),c=(a(i("39c8")),a(i("8c82")),{data:function(){return{particulars:{},id:0}},onLoad:function(t){this.id=t.id,this.getActivityDetails(t.id)},methods:{saveImg:function(t){console.log(t),uni.downloadFile({url:t,success:function(t){console.log(t.tempFilePath),uni.saveImageToPhotosAlbum({filePath:t.tempFilePath,success:function(t){console.log(t),console.log("长按保存图片")}})}})},getActivityDetails:function(t){var e=this;r.default._post_form("&p=member&do=transferDetail&id=".concat(t),{},(function(t){e.particulars=(0,n.default)((0,n.default)({},e.particulars),t.data),console.log(e.particulars)}))},goshouqian:function(){r.default.clipboard(this.particulars.pageurl),uni.setClipboardData({data:this.particulars.pageurl}),uni.showToast({title:"链接已复制到粘贴板"})}}});e.default=c},"97c7":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,".container-image[data-v-293e65cc]{position:relative;display:block;width:100%;height:0;padding-bottom:100%;overflow:hidden}.iamges-box[data-v-293e65cc]{position:absolute;display:flex;top:0;bottom:0;left:0;right:0;flex-direction:column;justify-content:center;align-items:center}.iamges-box uni-image[data-v-293e65cc]{width:%?580?%;height:%?270?%;display:block;background:transparent no-repeat;background-size:cover}.navPacket[data-v-293e65cc]{color:#17d117}",""]),t.exports=e},ab89:function(t,e,i){"use strict";var a=i("d91d"),n=i.n(a);n.a},d91d:function(t,e,i){var a=i("97c7");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("7af83296",a,!0,{sourceMap:!1,shadowMode:!1})},e177c:function(t,e,i){"use strict";var a=i("f7ee"),n=i.n(a);n.a},e433:function(t,e,i){"use strict";var a;i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return r})),i.d(e,"a",(function(){return a}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",{staticClass:"activityEntry",staticStyle:{background:"url(../../../../static/chooseCade/reddogbg.png)"}},[i("far-bottom"),i("v-uni-view",[i("v-uni-view",{staticClass:"dis-flex",staticStyle:{"justify-content":"center","align-items":"center","padding-top":"180upx"}},[i("v-uni-image",{staticStyle:{width:"120upx",height:"120upx","border-radius":"50%"},attrs:{src:t.particulars.avatar,mode:"aspectFill"}})],1),i("v-uni-view",{staticClass:"t-c f-36 ",staticStyle:{padding:"30upx 0 0",color:"#FFFFFF"}},[t._v(t._s(t.particulars.title))]),i("v-uni-view",{staticClass:"t-c f-30 ",staticStyle:{padding:"30upx 0",color:"#FFFFFF"}},[t._v(t._s(t.particulars.nickname)+"转赠"+t._s(t.particulars.money)+"元")]),i("v-uni-view",{staticClass:"dis-flex",staticStyle:{"justify-content":"center","align-items":"center"},on:{longpress:function(e){arguments[0]=e=t.$handleEvent(e),t.saveImg(t.particulars.qrlink)}}},[i("v-uni-image",{staticStyle:{width:"45vw",height:"45vw",padding:"40upx","background-color":"#FFFFFF","border-radius":"15upx"},attrs:{src:t.particulars.qrlink,mode:""}})],1),i("v-uni-view",{staticClass:"t-c f-30",staticStyle:{padding:"30upx 30upx",color:"#FDC15E",width:"88vw",overflow:"hidden"}},[t._v(t._s(t.particulars.pageurl))]),i("v-uni-view",{staticClass:"dis-flex",staticStyle:{"justify-content":"center","align-items":"center"}},[i("v-uni-view",{staticClass:"t-c f-30",staticStyle:{width:"55vw",height:"80upx","border-radius":"15upx","background-color":"#FEEC62","line-height":"80upx",color:"#333333"},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.goshouqian.apply(void 0,arguments)}}},[t._v("复制链接")])],1)],1)],1)},r=[]},f433:function(t,e,i){"use strict";var a=i("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=a(i("77ab")),r={data:function(){return{}},methods:{navgateTo:function(){n.default.navigationTo({url:"pages/subPages/redpacket/redsquare"})}},props:{diyImagesSrc:{type:String,default:function(){return""}},diyTitle:{type:String,default:function(){return""}},diyTitleType:{type:String,default:function(){return"Data"}}},computed:{imageSrc:function(){return this.imageRoot+"noneMores.png"},propsImagesSrc:function(){return this.diyImagesSrc},propsdiyTitle:function(){return this.diyTitle},propsdiyTitleType:function(){return this.diyTitleType}}};e.default=r},f7ee:function(t,e,i){var a=i("8d43");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("53736678",a,!0,{sourceMap:!1,shadowMode:!1})}}]);