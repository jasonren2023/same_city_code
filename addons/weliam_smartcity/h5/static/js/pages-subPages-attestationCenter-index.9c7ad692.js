(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-attestationCenter-index"],{"257d":function(t,i,a){var e=a("a27f");"string"===typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var n=a("4f06").default;n("08dd94b8",e,!0,{sourceMap:!1,shadowMode:!1})},"687e":function(t,i,a){"use strict";var e=a("257d"),n=a.n(e);n.a},a27f:function(t,i,a){var e=a("24fb");i=e(!1),i.push([t.i,".attestationCenter[data-v-4c46029e]{border:%?1?% solid transparent;overflow-x:hidden;font-size:0}.marginTop30[data-v-4c46029e]{margin:%?30?% auto 0 auto!important}.margin20[data-v-4c46029e]{margin:%?20?% 0}.p30[data-v-4c46029e]{padding:%?30?%}.mainView[data-v-4c46029e]{width:%?690?%;\r\n\t/* height: 160upx; */margin:auto;margin-bottom:%?30?%;border-radius:%?20?%}.back12CC53[data-v-4c46029e]{background:linear-gradient(90deg,#11cc53,#2be174);box-shadow:0 %?2?% %?12?% 0 rgba(18,83,204,.35)}.back297FF3[data-v-4c46029e]{background:linear-gradient(90deg,#297ff3,#48abff);box-shadow:0 %?2?% %?12?% 0 rgba(41,243,127,.35)}.backFD8834[data-v-4c46029e]{background:linear-gradient(90deg,#fe8834,#eaa72c);box-shadow:0 %?2?% %?12?% 0 rgba(253,52,136,.35)}.rzImage[data-v-4c46029e]{width:%?70?%;height:%?70?%}.rzimage[data-v-4c46029e]{width:%?60?%;height:%?60?%}.font24[data-v-4c46029e]{font-size:%?24?%}.font28[data-v-4c46029e]{font-size:%?28?%}.inlineBlock[data-v-4c46029e]{display:inline-block}.flex[data-v-4c46029e]{display:flex;justify-content:space-between}.lineHeight70[data-v-4c46029e]{line-height:%?40?%}.colorW[data-v-4c46029e]{color:#fff}.verticalM[data-v-4c46029e]{vertical-align:middle}.marginLeft20[data-v-4c46029e]{margin-left:%?20?%}.marginLeft10[data-v-4c46029e]{margin-left:%?10?%}.p43p35[data-v-4c46029e]{padding:%?43?% %?35?%}",""]),t.exports=i},a83d:function(t,i,a){"use strict";var e=a("4ea4");Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var n=e(a("77ab")),s=(e(a("8c82")),{data:function(){return{type:2,yrz:"merchant/rzyrz.png",dsh:"merchant/rzdsh.png",wrz:"merchant/rzwrz.png",rzimage:null,rzType:null,id:null,infoList:{},bzjimage:null}},onLoad:function(t){var i=this;i.rzType=t.rzType,1==t.rzType?uni.getStorage({key:"userinfo",success:function(t){i.id=t.data.mid,i.init(t.data.mid)}}):uni.getStorage({key:"checkStoreid",success:function(t){i.id=t.data,i.init(t.data)}})},methods:{naviGo:function(){0==this.infoList.is_money&&n.default.navigationTo({url:"pages/subPages/attestationCenter/bond/bond?rzType="+this.rzType})},init:function(t){var i=this;i.getInfo(t)},go:function(t){var i=this;n.default.navigationTo({url:t+"?rzType="+i.rzType})},getInfo:function(t){var i=this,a={type:i.rzType,sid:t};n.default._post_form("&p=attestation&do=getInfo",a,(function(t){i.infoList=t.data,console.info(t.data.is_attestation),0==t.data.is_attestation?i.rzimage=i.wrz+i.imgfixUrl:1==i.type?i.rzimage=i.dsh+i.imgfixUrl:2==i.type?i.rzimage=i.yrz+i.imgfixUrl:i.rzimage=i.wrz+i.imgfixUrl,0==t.data.is_money?i.bzjimage=i.wrz+i.imgfixUrl:1==t.data.is_money&&(i.bzjimage=i.yrz+i.imgfixUrl)}))}}});i.default=s},b615:function(t,i,a){"use strict";var e;a.d(i,"b",(function(){return n})),a.d(i,"c",(function(){return s})),a.d(i,"a",(function(){return e}));var n=function(){var t=this,i=t.$createElement,a=t._self._c||i;return a("v-uni-view",{staticClass:"attestationCenter"},[a("far-bottom"),a("v-uni-view",{staticClass:"mainPackage margin20"},[1==t.rzType&&1==t.infoList.switch?a("v-uni-view",{staticClass:"mainView  back12CC53",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.go("pages/subPages/attestationCenter/personAttestation/personAttestation")}}},[a("v-uni-view",{staticClass:"p43p35 flex"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-image",{staticClass:"rzImage verticalM",attrs:{src:t.imgfixUrls+"merchant/rzgr.png"}}),a("v-uni-view",{staticClass:"inlineBlock verticalM marginLeft20"},[a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font28"},[t._v("个人认证")])],1),a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font24"},[t._v("Personal authentication")])],1)],1)],1),a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-text",{staticClass:"font24 colorW lineHeight70"},[t._v(t._s(0==t.infoList.is_attestation?"未认证":1==t.infoList.is_attestation?"待审核":2==t.infoList.is_attestation?"已审核":"被驳回"))])],1),a("v-uni-image",{staticClass:"rzimage verticalM marginLeft10",attrs:{src:t.rzimage}})],1)],1),3==t.infoList.is_attestation?a("v-uni-view",{staticClass:"f-26 back12CC53 col-f dis-flex",staticStyle:{padding:"30upx","border-radius":"0upx 0upx 20upx 20upx"}},[a("v-uni-view",{staticStyle:{flex:"0.7"}},[t._v(t._s(t.infoList.reason))]),a("v-uni-view",{staticStyle:{flex:"0.3"}},[a("v-uni-view",{staticClass:"dis-il-block",staticStyle:{padding:"5upx 20upx",color:"#ffffff",border:"2upx solid #ffffff",float:"right","border-radius":"8upx"}},[t._v("认证")])],1)],1):t._e()],1):t._e(),2==t.rzType&&1==t.infoList.switch?a("v-uni-view",{staticClass:"mainView  backFD8834",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.go("pages/subPages/attestationCenter/enterpriseAttestation/enterpriseAttestation")}}},[a("v-uni-view",{staticClass:"p43p35 flex"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-image",{staticClass:"rzImage verticalM",attrs:{src:t.imgfixUrls+"merchant/rzqy.png"}}),a("v-uni-view",{staticClass:"inlineBlock verticalM marginLeft20"},[a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font28"},[t._v("企业认证")])],1),a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font24"},[t._v("Enterprise certification")])],1)],1)],1),a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-text",{staticClass:"font24 colorW lineHeight70"},[t._v(t._s(0==t.infoList.is_attestation?"未认证":1==t.infoList.is_attestation?"待审核":2==t.infoList.is_attestation?"已审核":"被驳回"))])],1),a("v-uni-image",{staticClass:"rzimage verticalM marginLeft10",attrs:{src:t.rzimage}})],1)],1),3==t.infoList.is_attestation?a("v-uni-view",{staticClass:"f-26 backFD8834 col-f dis-flex",staticStyle:{padding:"30upx","border-radius":"0upx 0upx 20upx 20upx"}},[a("v-uni-view",{staticStyle:{flex:"0.7"}},[t._v(t._s(t.infoList.reason))]),a("v-uni-view",{staticStyle:{flex:"0.3"}},[a("v-uni-view",{staticClass:"dis-il-block",staticStyle:{padding:"5upx 20upx",color:"#ffffff",border:"2upx solid #ffffff",float:"right","border-radius":"8upx"}},[t._v("认证")])],1)],1):t._e()],1):t._e(),1==t.infoList.money_switch?a("v-uni-view",{staticClass:"mainView  back297FF3 marginTop30",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.naviGo()}}},[a("v-uni-view",{staticClass:"p43p35 flex"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-image",{staticClass:"rzImage verticalM",attrs:{src:t.imgfixUrls+"merchant/rzbzj.png"}}),a("v-uni-view",{staticClass:"inlineBlock verticalM marginLeft20"},[a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font28"},[t._v("诚信保证金")])],1),a("v-uni-view",[a("v-uni-text",{staticClass:"colorW font24"},[t._v("Good faith deposit")])],1)],1)],1),a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-view",{staticClass:"inlineBlock verticalM"},[a("v-uni-text",{staticClass:"font24 colorW lineHeight70",staticStyle:{"padding-left":"20upx"}},[t._v(t._s(0==t.infoList.is_money?"未缴费":3==t.infoList.is_money?"被驳回":"已缴费"))]),t.infoList.money?a("v-uni-view",{staticClass:" f-w",staticStyle:{"font-size":"24upx",color:"#FFFFFF"}},[t._v("￥"+t._s(t.infoList.money))]):t._e()],1),a("v-uni-image",{staticClass:"rzimage verticalM marginLeft10",attrs:{src:t.bzjimage}})],1)],1)],1):t._e()],1)],1)},s=[]},bf06:function(t,i,a){"use strict";a.r(i);var e=a("a83d"),n=a.n(e);for(var s in e)"default"!==s&&function(t){a.d(i,t,(function(){return e[t]}))}(s);i["default"]=n.a},c4f1:function(t,i,a){"use strict";a.r(i);var e=a("b615"),n=a("bf06");for(var s in n)"default"!==s&&function(t){a.d(i,t,(function(){return n[t]}))}(s);a("687e");var r,o=a("f0c5"),c=Object(o["a"])(n["default"],e["b"],e["c"],!1,null,"4c46029e",null,!1,e["a"],r);i["default"]=c.exports}}]);