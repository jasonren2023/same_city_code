(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages2-merchantca-merchantca"],{"002c":function(e,t,a){"use strict";a.r(t);var r=a("05a7"),i=a.n(r);for(var o in r)"default"!==o&&function(e){a.d(t,e,(function(){return r[e]}))}(o);t["default"]=i.a},"05a7":function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r={data:function(){return{}},mounted:function(){},computed:{loadImage:function(){var e=this,t=e.$store.state.appInfo.loading;return t||""}}};t.default=r},"0b44":function(e,t,a){var r=a("14b2");"string"===typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);var i=a("4f06").default;i("4a2e5f60",r,!0,{sourceMap:!1,shadowMode:!1})},"14b2":function(e,t,a){var r=a("24fb");t=r(!1),t.push([e.i,".loadlogo-container[data-v-23fdce49]{width:100%;height:100%;background-color:#fff;position:fixed;z-index:999}.loadlogo[data-v-23fdce49]{width:80px;height:80px;\n\t/* margin: -60px 0 0 -60px; */position:fixed;top:50%;left:50%;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);overflow:hidden}.loadlogo .image[data-v-23fdce49]{width:100%;height:100%;overflow:hidden}",""]),e.exports=t},"433a":function(e,t,a){"use strict";a.r(t);var r=a("d10d"),i=a("7994");for(var o in i)"default"!==o&&function(e){a.d(t,e,(function(){return i[e]}))}(o);a("b744");var n,d=a("f0c5"),c=Object(d["a"])(i["default"],r["b"],r["c"],!1,null,"2594c3d6",null,!1,r["a"],n);t["default"]=c.exports},"44a1":function(e,t,a){"use strict";var r;a.d(t,"b",(function(){return i})),a.d(t,"c",(function(){return o})),a.d(t,"a",(function(){return r}));var i=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("v-uni-view",{staticClass:"merchantca"},[e.loadlogo?a("loadlogo"):e._e(),a("v-uni-view",{staticClass:"merchantca_head"},[e._v(e._s(e.orderSubmitInfomem.diyform.title))]),a("form-item",{ref:"formItem",attrs:{gopay:e.gopay,gopaytwo:e.gopaytwo,orderSubmitInfo:e.orderSubmitInfomem}}),a("v-uni-view",{staticClass:"merchantca_buttom"},[a("v-uni-view",{staticClass:"merchantca_buttom_bat",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.submitform.apply(void 0,arguments)}}},[e._v("提交")])],1)],1)},o=[]},"4af0":function(e,t,a){var r=a("a955");"string"===typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);var i=a("4f06").default;i("37e9c49d",r,!0,{sourceMap:!1,shadowMode:!1})},"4b78":function(e,t,a){"use strict";var r=a("4af0"),i=a.n(r);i.a},"4fbc":function(e,t,a){var r=a("5c85");"string"===typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);var i=a("4f06").default;i("04ceeec4",r,!0,{sourceMap:!1,shadowMode:!1})},"5c85":function(e,t,a){var r=a("24fb");t=r(!1),t.push([e.i,".dynamiccheck[data-v-2594c3d6]{padding:0 %?30?%;font-size:%?20?%;border-radius:%?10?%;border:%?1?% solid #f44;color:#fff;background-color:#f44;height:%?50?%;line-height:%?50?%;margin-right:%?10?%;margin-bottom:%?20?%}.dynamicnocheck[data-v-2594c3d6]{padding:0 %?30?%;font-size:%?20?%;border-radius:%?10?%;border:%?1?% solid #f44;color:#f44;height:%?50?%;line-height:%?50?%;margin-right:%?10?%;margin-bottom:%?20?%}.formItem[data-v-2594c3d6]{padding-top:%?30?%}.formItem > uni-view[data-v-2594c3d6]{display:flex;flex-direction:column}.formItem > uni-view > uni-view[data-v-2594c3d6]:nth-child(1){background-color:#fafafa;border-radius:%?10?%;margin-bottom:%?20?%}.formItem > uni-view[data-v-2594c3d6]:last-child{border:none}.formItem > uni-view > uni-view > uni-view > span[data-v-2594c3d6]{display:inline-block;vertical-align:middle;width:%?200?%;font-size:%?24?%;color:#333}.formItem > uni-view > uni-view > uni-view > uni-input[data-v-2594c3d6]{margin-left:%?40?%;display:inline-block;width:%?350?%;height:%?82?%;color:#333;font-size:%?24?%;vertical-align:middle}.m-t[data-v-2594c3d6]{margin-top:%?14?%}.describe[data-v-2594c3d6]{height:%?35?%;font-size:%?20?%;margin-left:%?220?%;color:#888}",""]),e.exports=t},7994:function(e,t,a){"use strict";a.r(t);var r=a("fa86"),i=a.n(r);for(var o in r)"default"!==o&&function(e){a.d(t,e,(function(){return r[e]}))}(o);t["default"]=i.a},"7aae":function(e,t,a){"use strict";var r=a("4ea4");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0,a("96cf");var i=r(a("1da1")),o=r(a("77ab")),n=r(a("433a")),d=r(a("cb39")),c={data:function(){return{orderSubmitInfomem:{diyform:{}},gopay:!0,gopaytwo:!0,loadlogo:!0,forid:""}},components:{formItem:n.default,loadlogo:d.default},mounted:function(){this.merchantdata()},onLoad:function(e){this.forid=e.id},onShow:function(){this.merchantdata()},methods:{merchantdata:function(){var e=this,t=this,a={formid:this.forid};o.default._post_form("&p=Im&do=getDiyformInfo",a,(function(e){t.orderSubmitInfomem.diyform=e.data,console.log(t.orderSubmitInfomem.diyform),t.gopaytwo=!1}),!1,(0,i.default)(regeneratorRuntime.mark((function t(){var a;return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,e.$refs.formItem.createOrderInfo;case 2:a=t.sent,a&&(e.loadlogo=!1);case 4:case"end":return t.stop()}}),t)}))))},submitform:function(){var e=this,t=this;t.gopay=!1,setTimeout((function(){t.gopay=!0}),2e3),setTimeout((function(){if(t.$refs.formItem){var a={formid:e.orderSubmitInfomem.diyform.listid,diyformid:e.forid};if(0==t.$refs.formItem.ifrequired)return void uni.showToast({icon:"none",title:"请完善必填信息"});var r=t.$refs.formItem.formList;a.datas=JSON.stringify(r),o.default._post_form("&p=Im&do=saveDiyformInfo",a,(function(e){uni.showToast({title:"提交成功",icon:"success"});var t=getCurrentPages();t.length>1?uni.showModal({title:"友情提示",content:"提交成功",showCancel:!1,success:function(e){e.confirm&&uni.navigateBack({delta:1})}}):uni.showModal({title:"友情提示",content:"提交成功",showCancel:!1,success:function(e){e.confirm&&uni.switchTab({url:"/pages/mainPages/index/index"})}})}),!1)}}))}}};t.default=c},"864b":function(e,t,a){"use strict";var r;a.d(t,"b",(function(){return i})),a.d(t,"c",(function(){return o})),a.d(t,"a",(function(){return r}));var i=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("v-uni-view",[a("v-uni-view",{staticClass:"loadlogo-container"},[a("v-uni-view",{staticClass:"loadlogo"},[a("v-uni-image",{staticClass:"image",attrs:{src:e.loadImage||e.imgfixUrls+"loadlogo.svg",mode:"aspectFill"}})],1)],1)],1)},o=[]},"8acd":function(e,t,a){"use strict";a.r(t);var r=a("44a1"),i=a("aad2");for(var o in i)"default"!==o&&function(e){a.d(t,e,(function(){return i[e]}))}(o);a("4b78");var n,d=a("f0c5"),c=Object(d["a"])(i["default"],r["b"],r["c"],!1,null,"05df8f09",null,!1,r["a"],n);t["default"]=c.exports},a955:function(e,t,a){var r=a("24fb");t=r(!1),t.push([e.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.merchantca[data-v-05df8f09]{padding:%?20?%}.merchantca .merchantca_head[data-v-05df8f09]{width:100%;height:%?100?%;background-color:#000;color:#fff;font-size:%?30?%;line-height:%?100?%;text-align:center;border-radius:%?10?%}.merchantca .merchantca_buttom[data-v-05df8f09]{display:flex;justify-content:center;padding-bottom:%?50?%;margin-top:%?50?%}.merchantca .merchantca_buttom .merchantca_buttom_bat[data-v-05df8f09]{width:75%;height:%?70?%;background-color:#ff4f4f;border-radius:%?30?%;text-align:center;color:#fff;line-height:%?70?%}',""]),e.exports=t},aad2:function(e,t,a){"use strict";a.r(t);var r=a("7aae"),i=a.n(r);for(var o in r)"default"!==o&&function(e){a.d(t,e,(function(){return r[e]}))}(o);t["default"]=i.a},b744:function(e,t,a){"use strict";var r=a("4fbc"),i=a.n(r);i.a},c6b2:function(e,t,a){"use strict";var r=a("0b44"),i=a.n(r);i.a},cb39:function(e,t,a){"use strict";a.r(t);var r=a("864b"),i=a("002c");for(var o in i)"default"!==o&&function(e){a.d(t,e,(function(){return i[e]}))}(o);a("c6b2");var n,d=a("f0c5"),c=Object(d["a"])(i["default"],r["b"],r["c"],!1,null,"23fdce49",null,!1,r["a"],n);t["default"]=c.exports},d10d:function(e,t,a){"use strict";var r;a.d(t,"b",(function(){return i})),a.d(t,"c",(function(){return o})),a.d(t,"a",(function(){return r}));var i=function(){var e=this,t=e.$createElement,a=e._self._c||t;return e.logocome&&e.formData?a("v-uni-view",{staticClass:"formItem"},e._l(e.formData,(function(t,r,i){return a("v-uni-view",{key:r,staticClass:"f-24 col-3"},[a("v-uni-view",{},[a("v-uni-view",{staticClass:"dis-flex",staticStyle:{"line-height":"100upx"}},[a("span",{class:"datetime"==t.id?"m-t":"",staticStyle:{display:"inline-block",width:"200upx","text-align":"right","margin-right":"20upx"}},[e._v(e._s(t.data.title)),a("v-uni-text",{staticClass:"c-ff4444 f-28 p-l-20",staticStyle:{"padding-left":"10upx","line-height":"10upx"}},[e._v(e._s(1==t.data.is_required?"*":" "))])],1),"text"==t.id?a("v-uni-input",{staticClass:"flex-box",staticStyle:{position:"relative",height:"100upx","line-height":"100upx","margin-left":"0"},attrs:{placeholder:""+t.data.placeholder,maxlength:""!=t.data.length?Number(t.data.length):9999,"adjust-position":!1},model:{value:e.createOrderInfo[r].data,callback:function(t){e.$set(e.createOrderInfo[r],"data",t)},expression:"createOrderInfo[key].data"}}):e._e(),"textarea"==t.id?a("v-uni-textarea",{staticClass:"f-26 flex-box",style:{height:t.data.height+"rpx",marginTop:"32rpx"},attrs:{placeholder:""+t.data.placeholder,maxlength:""!=t.data.length?Number(t.data.length):9999,"placeholder-style":"font-size: 26upx","adjust-position":!1},model:{value:e.createOrderInfo[r].data,callback:function(t){e.$set(e.createOrderInfo[r],"data",t)},expression:"createOrderInfo[key].data"}}):e._e(),"select"==t.id&&t.data.options?a("lb-picker",{staticClass:"flex-box",attrs:{list:t.data.options,mode:"selector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"select"+r)}},model:{value:e.createOrderInfo["select"+r],callback:function(t){e.$set(e.createOrderInfo,"select"+r,t)},expression:"createOrderInfo['select' + key]"}},[a("v-uni-button",{staticClass:"t-r",staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"100upx",width:"100%"}},[e._v(e._s(e.createOrderInfo["select"+r]||t.data.options[0])),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):e._e(),"checkbox"==t.id&&e.createOrderInfo[r+"multiple"]?a("v-uni-view",{staticClass:"dis-flex t-r",staticStyle:{"flex-wrap":"wrap",width:"100%","align-content":"flex-end",flex:"1","margin-top":"26upx"}},e._l(e.createOrderInfo[r+"multiple"],(function(o,n){return a("v-uni-view",{key:n,class:o.check?"dynamiccheck":"dynamicnocheck",on:{click:function(a){arguments[0]=a=e.$handleEvent(a),e.dycheck(r,t,n,i,o.check)}}},[e._v(e._s(o.name))])})),1):e._e(),"time"==t.id?a("v-uni-view",{staticClass:"flex-box",staticStyle:{}},[a("ruiDatePicker",{attrs:{fields:"minute",start:"1970-00-00 00:00",end:"2100-12-30 23:59",value:t.data.value||t.data.datetime_local,iftime:"time"==t.id},on:{change:function(t){arguments[0]=t=e.$handleEvent(t),e.bindChange(t,r,1)}}})],1):e._e(),"datetime"==t.id?a("v-uni-view",{staticStyle:{flex:"1","line-height":"50upx"}},[a("v-uni-view",[a("ruiDatePicker",{attrs:{fields:"minute",start:"1970-00-00 00:00",end:"2100-12-30 23:59",value:t.data.value?t.data.value[0]:t.data.start_time},on:{change:function(t){arguments[0]=t=e.$handleEvent(t),e.bindChange(t,r,2)}}})],1),a("v-uni-view",{staticStyle:{padding:"0 20upx","font-size":"20upx","line-height":"30upx","padding-left":"100upx"}},[e._v("至")]),a("v-uni-view",[a("ruiDatePicker",{attrs:{fields:"minute",start:"1970-00-00 00:00",end:"2100-12-30 23:59",value:t.data.value?t.data.value[1]:t.data.end_time},on:{change:function(t){arguments[0]=t=e.$handleEvent(t),e.bindChange(t,r,3)}}})],1)],1):e._e(),"city"==t.id?a("v-uni-view",{staticClass:"flex-box",staticStyle:{"margin-top":"20upx"}},["true"==t.data.area&&"false"==t.data.city&&"false"==t.data.province?a("lb-picker",{attrs:{list:e.area,mode:"selector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"area"+r)}},model:{value:e.createOrderInfo["area"+r],callback:function(t){e.$set(e.createOrderInfo,"area"+r,t)},expression:"createOrderInfo['area' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"62upx"}},[e._v(e._s(e.createOrderInfo["area"+r]||e.area[0])),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):"false"==t.data.area&&"true"==t.data.city&&"false"==t.data.province?a("lb-picker",{attrs:{list:e.city,mode:"selector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"area"+r)}},model:{value:e.createOrderInfo["city"+r],callback:function(t){e.$set(e.createOrderInfo,"city"+r,t)},expression:"createOrderInfo['city' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0"}},[e._v(e._s(e.createOrderInfo["city"+r]||e.city[0])),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx","line-height":"62upx"}})],1)],1):"false"==t.data.area&&"false"==t.data.city&&"true"==t.data.province?a("lb-picker",{attrs:{list:e.province,mode:"selector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"province"+r)}},model:{value:e.createOrderInfo["province"+r],callback:function(t){e.$set(e.createOrderInfo,"province"+r,t)},expression:"createOrderInfo['province' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"62upx"}},[e._v(e._s(e.createOrderInfo["province"+r]||e.province[0])),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):"false"==t.data.area&&"true"==t.data.city&&"true"==t.data.province?a("lb-picker",{attrs:{level:2,list:e.provincecity,mode:"multiSelector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"provincecity"+r)}},model:{value:e.createOrderInfo["provincecity"+r],callback:function(t){e.$set(e.createOrderInfo,"provincecity"+r,t)},expression:"createOrderInfo['provincecity' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"62upx"}},[e._v(e._s(e.createOrderInfo["provincecity"+r].length>0?e.createOrderInfo["provincecity"+r][0]+"-"+e.createOrderInfo["provincecity"+r][1]:e.provincecity[0].label+"-"+e.provincecity[0].children[0].label)),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):"true"==t.data.area&&"true"==t.data.city&&"false"==t.data.province?a("lb-picker",{attrs:{list:e.cityarea,mode:"multiSelector",level:2},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"cityarea"+r)}},model:{value:e.createOrderInfo["cityarea"+r],callback:function(t){e.$set(e.createOrderInfo,"cityarea"+r,t)},expression:"createOrderInfo['cityarea' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"62upx"}},[e._v(e._s(e.createOrderInfo["cityarea"+r].length>0?e.createOrderInfo["cityarea"+r][0]+"-"+e.createOrderInfo["cityarea"+r][1]:e.cityarea[0].label+"-"+e.cityarea[0].children[0].label)),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):"true"==t.data.area&&"true"==t.data.city&&"true"==t.data.province?a("lb-picker",{attrs:{list:e.provincecityarea,level:3,mode:"multiSelector"},on:{confirm:function(t){arguments[0]=t=e.$handleEvent(t),e.confirm(t,"provincecityarea"+r)}},model:{value:e.createOrderInfo["provincecityarea"+r],callback:function(t){e.$set(e.createOrderInfo,"provincecityarea"+r,t)},expression:"createOrderInfo['provincecityarea' + key]"}},[a("v-uni-button",{staticStyle:{"background-color":"#fafafa","font-size":"26upx","text-align":"left",padding:"0","line-height":"62upx"}},[e._v(e._s(e.createOrderInfo["provincecityarea"+r].length>0?e.createOrderInfo["provincecityarea"+r][0]+"-"+e.createOrderInfo["provincecityarea"+r][1]+"-"+e.createOrderInfo["provincecityarea"+r][2]:t.data.value?t.data.value[0]+"-"+t.data.value[1]+"-"+t.data.value[1]:e.provincecityarea[0].label+"-"+e.provincecityarea[0].children[0].label+"-"+e.provincecityarea[0].children[0].children[0].label)),a("v-uni-text",{staticClass:"iconfont icon-right col-9",staticStyle:{float:"right","font-size":"26upx","margin-right":"20upx"}})],1)],1):e._e()],1):e._e(),"img"==t.id&&1==t.data.number?a("v-uni-view",[e.createOrderInfo[r].data&&e.createOrderInfo[r].data.length>0?a("v-uni-view",{staticClass:"imgPreview",staticStyle:{position:"relative"}},[a("v-uni-image",{staticStyle:{width:"420upx",height:"330upx","margin-top":"20upx"},attrs:{src:e.createOrderInfo[r].data}}),a("v-uni-image",{staticClass:"close",staticStyle:{width:"40upx",height:"40upx",position:"absolute",right:"20upx",top:"20upx"},attrs:{src:e.imgfixUrls+"merchant/close.png"},on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.closeLogo(r)}}})],1):a("v-uni-view",{staticClass:"userImgUpdata",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.uploadFiles(1,r)}}},[a("v-uni-image",{staticStyle:{width:"420upx",height:"330upx","margin-top":"40upx"},attrs:{src:e.imgfixUrls+"tpsz.png"}})],1)],1):e._e(),"img"==t.id&&t.data.number>1?a("v-uni-view",{staticStyle:{display:"flex","justify-content":"flex-start","flex-direction":"row","align-items":"center","text-align":"left","flex-wrap":"wrap",flex:"1","margin-top":"20upx"}},[e._l(e.createOrderInfo["img"+r],(function(t,i){return a("v-uni-view",{key:i,staticClass:"imgPreview",staticStyle:{flex:"30%","max-width":"28%",position:"relative","margin-right":"20upx",height:"150upx"}},[a("v-uni-image",{staticStyle:{width:"130upx",height:"130upx"},attrs:{src:t}}),a("v-uni-image",{staticClass:"close",staticStyle:{width:"30upx",height:"30upx",position:"absolute",right:"-6upx",top:"0"},attrs:{src:e.imgfixUrls+"merchant/close.png"},on:{click:function(a){arguments[0]=a=e.$handleEvent(a),e.closePreview(t,r)}}})],1)})),e.createOrderInfo["img"+r]&&e.createOrderInfo["img"+r].length===Number(t.data.number)?e._e():a("v-uni-view",{staticClass:"userImgUpdata",staticStyle:{flex:"30%","max-width":"29%",width:"130upx",height:"130upx",border:"1upx solid #CCCCCC","border-radius":"10upx",position:"relative","margin-right":"20upx","margin-bottom":"20upx"},on:{click:function(a){arguments[0]=a=e.$handleEvent(a),e.uploadFiles(2,r,Number(t.data.number))}}},[a("v-uni-image",{staticStyle:{width:"40upx",height:"40upx",margin:"auto",padding:"40upx",display:"flex"},attrs:{src:e.imgfixUrls+"merchant/addImg.svg"}})],1)],2):e._e()],1),a("v-uni-view",{staticClass:"describe"},[a("v-uni-text",{staticClass:"f-20"},[e._v(e._s(t.data.tipdetail))])],1)],1)],1)})),1):e._e()},o=[]},fa86:function(e,t,a){"use strict";var r=a("4ea4");a("d81d"),a("a434"),a("a9e3"),a("e25e"),a("ac1f"),a("5319"),Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0,a("96cf");var i=r(a("1da1")),o=r(a("77ab")),n=r(a("8c82")),d=r(a("a701")),c=r(a("826a")),l={data:function(){return{createOrderInfo:{},province:[],city:[],area:[],provincecity:[],cityarea:[],provincecityarea:[],logocome:!0,formList:{},ifrequired:!0,type1Url:"",type1Urlon:"",type2Url:[],type2Urlon:[],localIds:"",uploadlength:0,inputs:{},imgs:{},formData:null,is_text:""}},props:{orderSubmitInfo:{type:Object,default:{}},gopay:{type:Boolean,default:!1},gopaytwo:{type:Boolean,default:!1}},mounted:function(){console.log(this.orderSubmitInfo)},components:{LbPicker:d.default,ruiDatePicker:c.default},methods:{datasValue:function(){var e=this,t=0;if(console.log(e.orderSubmitInfo,"初始化数据"),e.orderSubmitInfo.diyform){var a=function(a){if("img"==e.orderSubmitInfo.diyform.list[a].id&&Number(e.orderSubmitInfo.diyform.list[a].data.number)>1)e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:[],count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},e.createOrderInfo["img"+a]?e.createOrderInfo["img"+a]=e.createOrderInfo["img"+a]:e.createOrderInfo["img"+a]=[],e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo["img"+a]=e.orderSubmitInfo.diyform.list[a].data.value,e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value);else if("checkbox"==e.orderSubmitInfo.diyform.list[a].id)e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:[],count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},e.createOrderInfo[a+"multiple"]=[],e.orderSubmitInfo.diyform.list[a].data.options&&e.orderSubmitInfo.diyform.list[a].data.options.map((function(t){var r={name:t,check:!1};e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value,e.orderSubmitInfo.diyform.list[a].data.value.map((function(e){e==t&&(r={name:t,check:!0})}))),e.createOrderInfo[a+"multiple"].push(r),console.log(e.createOrderInfo[a+"multiple"])})),console.log(e.createOrderInfo[a+"multiple"],1e24);else if("time"==e.orderSubmitInfo.diyform.list[a].id)e.createOrderInfo["show"+a]=!1,e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:e.addtime(e.orderSubmitInfo.diyform.list[a].data.time_stamp),count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},e.orderSubmitInfo.diyform.list[a].data.datetime_local=e.orderSubmitInfo.diyform.list[a].data.datetime_local.replace(/T/g," "),console.log(e.createOrderInfo[a].data,e.orderSubmitInfo.diyform.list[a].data.datetime_local),e.orderSubmitInfo.diyform.list[a].data.datetime_local=e.orderSubmitInfo.diyform.list[a].data.datetime_local.substring(0,e.orderSubmitInfo.diyform.list[a].data.datetime_local.length-3),console.log(e.createOrderInfo[a].data,e.orderSubmitInfo.diyform.list[a].data.datetime_local),e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value);else if("text"==e.orderSubmitInfo.diyform.list[a].id||"textarea"==e.orderSubmitInfo.diyform.list[a].id||"select"==e.orderSubmitInfo.diyform.list[a].id||"img"==e.orderSubmitInfo.diyform.list[a].id&&1==Number(e.orderSubmitInfo.diyform.list[a].data.number))e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:"",count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},"img"==e.orderSubmitInfo.diyform.list[a].id?e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value[0],console.log(e.createOrderInfo[a].data,e.orderSubmitInfo.diyform.list[a].data.value[0]),e.imgs[a]=e.orderSubmitInfo.diyform.list[a].data.value):"select"==e.orderSubmitInfo.diyform.list[a].id?e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value,e.createOrderInfo["select"+a]=e.orderSubmitInfo.diyform.list[a].data.value):e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data=e.orderSubmitInfo.diyform.list[a].data.value);else if("datetime"==e.orderSubmitInfo.diyform.list[a].id)e.createOrderInfo["start"+a]=!1,e.createOrderInfo["end"+a]=!1,e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:[],count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},e.createOrderInfo[a].data[0]=e.addtime(e.orderSubmitInfo.diyform.list[a].data.start_time_stamp),e.createOrderInfo[a].data[1]=e.addtime(e.orderSubmitInfo.diyform.list[a].data.end_time_stamp),e.orderSubmitInfo.diyform.list[a].data.end_time=e.orderSubmitInfo.diyform.list[a].data.end_time.replace(/T/g," "),e.orderSubmitInfo.diyform.list[a].data.end_time=e.orderSubmitInfo.diyform.list[a].data.end_time.substring(0,e.orderSubmitInfo.diyform.list[a].data.end_time.length-3),e.orderSubmitInfo.diyform.list[a].data.start_time=e.orderSubmitInfo.diyform.list[a].data.start_time.replace(/T/g," "),e.orderSubmitInfo.diyform.list[a].data.start_time=e.orderSubmitInfo.diyform.list[a].data.start_time.substring(0,e.orderSubmitInfo.diyform.list[a].data.start_time.length-3),e.orderSubmitInfo.diyform.list[a].data.value&&(e.createOrderInfo[a].data[0]=e.orderSubmitInfo.diyform.list[a].data.value[0],e.createOrderInfo[a].data[1]=e.orderSubmitInfo.diyform.list[a].data.value[1]);else if("city"==e.orderSubmitInfo.diyform.list[a].id){e.createOrderInfo[a]={id:e.orderSubmitInfo.diyform.list[a].id,data:[],count:t,key:e.orderSubmitInfo.diyform.list[a].key,title:e.orderSubmitInfo.diyform.list[a].data.title},e.createOrderInfo["show"+a]=!1;var r=uni.getStorageSync("cityList");r=JSON.stringify(r).replace(/name/g,"label").replace(/id/g,"value").replace(/area/g,"children").replace(/dist/g,"children"),r=JSON.parse(r),e.province=[],e.city=[],e.area=[],e.provincecityarea=r,e.provincecityarea.map((function(t){t.value=t.label,e.province.push(t.label),t.children.map((function(t){t.value=t.label,e.city.push(t.label),t.children.map((function(t){t.value=t.label,e.area.push(t.label)}))}))})),e.provincecity=e.provincecityarea,"true"==e.orderSubmitInfo.diyform.list[a].data.area&&"true"==e.orderSubmitInfo.diyform.list[a].data.city&&"true"==e.orderSubmitInfo.diyform.list[a].data.province?""!==e.orderSubmitInfo.diyform.list[a].data.value[0]?(e.createOrderInfo["provincecityarea"+a]=[],e.createOrderInfo["provincecityarea"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo["provincecityarea"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[1]),e.createOrderInfo["provincecityarea"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[2]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[1]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[2])):(e.createOrderInfo["provincecityarea"+a]=[],e.createOrderInfo["provincecityarea"+a].push(e.provincecityarea[0].label),e.createOrderInfo["provincecityarea"+a].push(e.provincecityarea[0].children[0].label),e.createOrderInfo["provincecityarea"+a].push(e.provincecityarea[0].children[0].children[0].label)):"false"==e.orderSubmitInfo.diyform.list[a].data.area&&"true"==e.orderSubmitInfo.diyform.list[a].data.city&&"true"==e.orderSubmitInfo.diyform.list[a].data.province?(e.provincecity.map((function(e){e.children.map((function(e){delete e.children}))})),e.orderSubmitInfo.diyform.list[a].data.value?(e.createOrderInfo["provincecity"+a]=[],e.createOrderInfo["provincecity"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo["provincecity"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[1]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[1])):(e.createOrderInfo["provincecity"+a]=[],e.createOrderInfo["provincecity"+a].push(e.provincecity[0].label),e.createOrderInfo["provincecity"+a].push(e.provincecity[0].children[0].label))):"true"==e.orderSubmitInfo.diyform.list[a].data.area&&"true"==e.orderSubmitInfo.diyform.list[a].data.city&&"false"==e.orderSubmitInfo.diyform.list[a].data.province&&(e.provincecityarea.map((function(t){t.children.map((function(t){e.cityarea.push(t)}))})),e.orderSubmitInfo.diyform.list[a].data.value?(e.createOrderInfo["cityarea"+a]=[],e.createOrderInfo["cityarea"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo["cityarea"+a].push(e.orderSubmitInfo.diyform.list[a].data.value[1]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[0]),e.createOrderInfo[a].data.push(e.orderSubmitInfo.diyform.list[a].data.value[1])):(e.createOrderInfo["cityarea"+a]=[],e.createOrderInfo["cityarea"+a].push(e.cityarea[0].label),e.createOrderInfo["cityarea"+a].push(e.cityarea[0].children[0].label))),console.log(e.createOrderInfo,e.city,e.area)}t++};for(var r in e.orderSubmitInfo.diyform.list)a(r)}console.log(e.createOrderInfo),e.formData=e.orderSubmitInfo.diyform.list},inputValue:function(e,t){this.createOrderInfo[t].data=e.detail.value,this.logocome=!this.logocome,this.logocome=!this.logocome},confirm:function(e,t){console.log(e,t),this.$set(this.createOrderInfo,t,e.value),this.logocome=!this.logocome,this.logocome=!this.logocome},bindChange:function(e,t,a){console.log(e,t),1==a?this.createOrderInfo[t].data=e:2==a?this.createOrderInfo[t].data[0]=e:this.createOrderInfo[t].data[1]=e},dycheck:function(e,t,a,r,i){var o=this;if(console.log(o.formData),i)o.createOrderInfo[e+"multiple"][a].check=!1,o.createOrderInfo[e].data.map((function(t,r){t==o.formData[e].data.options[a]&&o.createOrderInfo[e].data.splice(r,1)}));else{o.createOrderInfo[e+"multiple"][a].check=!0;var n=o.formData[e].data.options[a];o.createOrderInfo[e].data.push(n)}o.logocome=!o.logocome,o.logocome=!o.logocome},addtime:function(e){var t=1e3*e;if("undefined"==typeof t)return"";var a=new Date(parseInt(t)),r=a.getFullYear(),i=a.getMonth()+1;i=i<10?"0"+i:i;var o=a.getDate();o=o<10?"0"+o:o;var n=a.getHours();n=n<10?"0"+n:n;var d=a.getMinutes();d=d<10?"0"+d:d;var c=a.getSeconds();return c=c<10?"0"+c:c,r+"-"+i+"-"+o+" "+n+":"+d+":"+c},closePreview:function(e,t){var a=this;a.createOrderInfo["img"+t]&&a.createOrderInfo["img"+t].map((function(r,i){r==e&&(console.log(a.createOrderInfo["img"+t],1111111111111),a.createOrderInfo["img"+t].splice(i,1),a.logocome=!a.logocome,a.logocome=!a.logocome)})),console.log(this.type2Url,a.createOrderInfo["img"+t])},uplodephone:function(e,t,a,r){var i=this;n.default.uoloadIg(t[e],(function(t){if("uploadImage:ok"===t.errMsg){uni.showLoading({});var n={upload_type:2,id:t.serverId};o.default._post_form("&do=uploadFiles",n,(function(t){if(0===t.errno){var o=e+1;if(i.createOrderInfo["img"+a].length==r)return void uni.showToast({title:"最多上传".concat(r,"张图片"),icon:"none"});i.createOrderInfo["img"+a].push(t.data.img),o<i.uploadlength?uni.setTimeout(i.uplodephone(o,i.localIds,a),500):uni.$emit("update",{msg:"页面更新"}),i.logocome=!i.logocome,i.logocome=!i.logocome}}),!1,(function(){uni.hideLoading()}))}else uni.hideLoading(),o.default.showError("上传失败")}))},uploadFiles:function(e,t){var a=arguments,r=this;return(0,i.default)(regeneratorRuntime.mark((function i(){var d,c,l,f,u,s;return regeneratorRuntime.wrap((function(i){while(1)switch(i.prev=i.next){case 0:if(d=a.length>2&&void 0!==a[2]?a[2]:1,c=r,c.$emit("ononahow"),2!=o.default.getClientType()){i.next=40;break}return i.next=6,o.default.browser_upload(d);case 6:if(l=i.sent,console.log(l),1!=e){i.next=22;break}return i.next=11,o.default._upLoad(l.tempFilePaths[0]);case 11:f=i.sent,console.log(f),c.type1Url=f.data.img,c.type1Urlon=f.data.image,c.imgs[t]=c.type1Url,c.createOrderInfo[t].data=c.type1Url,console.log(c.createOrderInfo[t]),c.logocome=!c.logocome,c.logocome=!c.logocome,i.next=39;break;case 22:if(2!=e){i.next=39;break}u=0;case 24:if(!(u<l.tempFilePaths.length)){i.next=39;break}return i.next=27,o.default._upLoad(l.tempFilePaths[u]);case 27:if(s=i.sent,c.createOrderInfo["img"+t].length!=d){i.next=31;break}return uni.showToast({title:"最多上传九张图片",icon:"none"}),i.abrupt("return");case 31:console.log(c.type2Url,c.createOrderInfo["img"+t]),c.createOrderInfo["img"+t].push(s.data.img),c.type2Urlon.push(s.data.image),c.logocome=!c.logocome,c.logocome=!c.logocome;case 36:u++,i.next=24;break;case 39:return i.abrupt("return");case 40:2==e?n.default.choseImage((function(e){c.localIds=e.localIds,c.uploadlength=e.localIds.length,c.uplodephone(0,c.localIds,t,d)}),d):n.default.choseImage((function(a){n.default.uoloadIg(a.localIds[0],(function(a){if("uploadImage:ok"===a.errMsg){uni.showLoading({});var r={upload_type:2,id:a.serverId};o.default._post_form("&do=uploadFiles",r,(function(a){1==e&&(c.type1Url=a.data.img,c.type1Urlon=a.data.image,c.imgs[t]=c.type1Url,c.createOrderInfo[t].data=c.type1Url,uni.$emit("update",{msg:"页面更新"})),c.logocome=!c.logocome,c.logocome=!c.logocome}),!1,(function(){uni.hideLoading()}))}else uni.hideLoading(),o.default.showError("上传失败")}))}));case 41:case"end":return i.stop()}}),i)})))()},closeLogo:function(e,t){var a=this;a.type1Url="",a.type1Urlon="",a.createOrderInfo[e].data="",a.imgs[e]="",a.logocome=!a.logocome,a.logocome=!a.logocome}},computed:{},watch:{gopaytwo:function(){this.datasValue()},gopay:{handler:function(e,t){var a=this,r=Object.assign({},a.createOrderInfo);console.log(r,a.formData);var i=null,o=[],n=[];if(a.orderSubmitInfo.diyform)for(var d in a.formData)for(var c in"city"==a.formData[d].id&&("true"==a.formData[d].data.province&&"false"==a.formData[d].data.city&&"false"==a.formData[d].data.area?(i="province",n=a.province[0]):"false"==a.formData[d].data.province&&"true"==a.formData[d].data.city&&"false"==a.formData[d].data.area?(i="city",n=a.city[0]):"false"==a.formData[d].data.province&&"false"==a.formData[d].data.city&&"true"==a.formData[d].data.area?(i="area",n=a.area[0]):"false"==a.formData[d].data.province&&"true"==a.formData[d].data.city&&"true"==a.formData[d].data.area?(i="cityarea",n.push(a.cityarea[0].label),n.push(a.cityarea[0].children[0].label)):"true"==a.formData[d].data.province&&"true"==a.formData[d].data.city&&"false"==a.formData[d].data.area?(i="provincecity",n.push(a.provincecity[0].label),n.push(a.provincecity[0].children[0].label)):"true"==a.formData[d].data.province&&"true"==a.formData[d].data.city&&"true"==a.formData[d].data.area&&(i="provincecityarea",n.push(a.provincecity[0].label),n.push(a.provincecity[0].children[0].label),n.push(a.provincecity[0].children[0].children[0].label))),console.log(r),r)if(r[c]instanceof Object){if("select"==r[c].id&&"select"==a.formData[d].id)r[d].data=r["select"+d]||a.formData[d].data.options[0];else if("img"==r[c].id&&"img"==a.formData[d].id&&c==d)if(1==a.formData[d].data.number){var l=a.imgs[d];r[d].data||(l=r[d].data),r[d].data=new Array,l&&r[d].data.push(l)}else r[d].data=r["img"+d]||[];else"city"==r[c].id&&"city"==a.formData[d].id&&(r[i+d]?r[d].data=r[i+d]:r[d].data=n);if(r[c].id==a.formData[d].id&&c==d&&(console.log(c,d),o.push(r[c]),1==a.formData[d].data.is_required)){if(r[c].data instanceof Array&&0==r[c].data.length)return console.log(r[c].id,"进来了",a.formData[d].id,c,d),a.ifrequired=!1,void(a.is_text=r[c].title);if(!r[c].data)return a.ifrequired=!1,void(a.is_text=r[c].title);a.ifrequired=!0}}a.formList=o},deep:!0}}};t.default=l}}]);