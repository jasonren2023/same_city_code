(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-search-search~pages-subPages2-businessCenter-foodIntroduced-foodIntroduced~pages-subP~ed2634ea"],{"002c":function(t,e,a){"use strict";a.r(e);var i=a("05a7"),n=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},"05a7":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={data:function(){return{}},mounted:function(){},computed:{loadImage:function(){var t=this,e=t.$store.state.appInfo.loading;return e||""}}};e.default=i},"0b44":function(t,e,a){var i=a("14b2");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("4a2e5f60",i,!0,{sourceMap:!1,shadowMode:!1})},"0e15":function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,".ballBox[data-v-1c0be340]{position:fixed;left:%?-10?%;top:%?15?%;z-index:9;\n\t/*  用颜色来演示用原理 */\n\t/* background-color: #4CD964; */height:%?30?%;width:%?30?%}.ballOuter[data-v-1c0be340]{background:red;height:100%;width:100%;border-radius:50%;background-size:100% 100%;background-position:50%;z-index:999}",""]),t.exports=e},"14b2":function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,".loadlogo-container[data-v-23fdce49]{width:100%;height:100%;background-color:#fff;position:fixed;z-index:999}.loadlogo[data-v-23fdce49]{width:80px;height:80px;\n\t/* margin: -60px 0 0 -60px; */position:fixed;top:50%;left:50%;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);overflow:hidden}.loadlogo .image[data-v-23fdce49]{width:100%;height:100%;overflow:hidden}",""]),t.exports=e},"20c8":function(t,e,a){"use strict";a("a9e3"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={name:"hx-jump-ball",data:function(){return{flag:!1,ballBoxAnimation:null,ballOuterAnimation:null,ballBoxAnimationData:{},ballOuterAnimationData:{}}},props:{ballWidth:{type:Number,default:20},ballHeight:{type:Number,default:20},backgroundColor:{type:String,default:"#ff4444"},backgroundImage:{type:String,default:""},index:{type:Number,default:999},start:{type:Number,default:1},element:{type:Array,default:function(){return[]}},speed:{type:Number,default:400},bezier:{type:String,default:"cubic-bezier(.6,-0.63,.94,.71)"}},watch:{start:{handler:function(t,e){console.log(t,e);var a=this;a.element&&1!=a.flag&&(a.flag=!a.flag,console.log(a.flag),a.getElementCoordinate(a.element[0],a.element[1]))},deep:!0}},created:function(){this.ballBoxAnimation=uni.createAnimation({duration:0,timingFunction:this.bezier,delay:0}),this.ballOuterAnimation=uni.createAnimation({duration:0,timingFunction:"linear",delay:0}),this.setEnd()},mounted:function(){this.monitoring(),uni.$on("update",(function(t){this.setEnd()}))},methods:{monitoring:function(){var t=this;this.$on("childMethod",(function(e){t.element=e,t.getElementCoordinate(t.element[0],t.element[1])}))},getElementCoordinate:function(t,e){var a=this,i=t.left+((t.width+a.ballWidth)/2-a.ballWidth),n=t.bottom-((t.height-a.ballHeight)/2+a.ballHeight),o=e.left+((e.width+a.ballWidth)/2-a.ballWidth),u=e.bottom-((e.height-a.ballHeight)/2+a.ballHeight);a.setStart(i,n,o,u),a.startAnimation(i,n,o,u)},startAnimation:function(t,e,a,i){var n=this;setTimeout((function(){n.flag=!n.flag,n.$emit("msg",{code:0,status:!0})}),600),setTimeout((function(){n.ballBoxAnimation.opacity(1).translate3d(a,i,0).step({duration:n.speed}),n.ballBoxAnimationData=n.ballBoxAnimation.export(),n.ballOuterAnimation.opacity(1).translate3d(0,0,0).step({duration:n.speed}),n.ballOuterAnimationData=n.ballOuterAnimation.export()}),50)},setStart:function(t,e,a,i){this.ballBoxAnimation.translate3d(a,e,0).opacity(1).step({duration:0}),this.ballBoxAnimationData=this.ballBoxAnimation.export(),this.ballOuterAnimation.translate3d(t-a,0,0).opacity(1).step({duration:0}),this.ballOuterAnimationData=this.ballOuterAnimation.export()},setEnd:function(){this.ballBoxAnimation.opacity(0).step({duration:0}),this.ballBoxAnimationData=this.ballBoxAnimation.export(),this.ballOuterAnimation.opacity(0).step({duration:0}),this.ballOuterAnimationData=this.ballOuterAnimation.export()}}};e.default=i},"53f2":function(t,e,a){"use strict";var i=a("a017"),n=a.n(i);n.a},"5e89":function(t,e,a){var i=a("861d"),n=Math.floor;t.exports=function(t){return!i(t)&&isFinite(t)&&n(t)===t}},"77f1":function(t,e,a){"use strict";var i;a.d(e,"b",(function(){return n})),a.d(e,"c",(function(){return o})),a.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticClass:"tui-numberbox-class tui-numberbox"},[a("v-uni-view",{directives:[{name:"show",rawName:"v-show",value:t.value,expression:"value"}],staticClass:"tui-numbox-icon tui-icon-reduce ",class:[t.disabled||t.min>=t.value?"tui-disabled":""],staticStyle:{"border-radius":"5upx",border:"1upx solid #FF4444",color:"#FF4444","font-size":"20upx","box-sizing":"border-box"},on:{click:function(e){e.stopPropagation(),arguments[0]=e=t.$handleEvent(e),t.reduce.apply(void 0,arguments)}}}),a("v-uni-input",{directives:[{name:"show",rawName:"v-show",value:t.value,expression:"value"}],staticClass:"tui-num-input",staticStyle:{width:"48upx",height:"48upx"},attrs:{type:"number",disabled:t.disabled},on:{click:function(e){e.stopPropagation(),arguments[0]=e=t.$handleEvent(e)},blur:function(e){arguments[0]=e=t.$handleEvent(e),t.blur.apply(void 0,arguments)},input:function(e){arguments[0]=e=t.$handleEvent(e),t.srvalue(e)}},model:{value:t.inputValue,callback:function(e){t.inputValue=e},expression:"inputValue"}}),a("v-uni-view",{staticClass:"tui-numbox-icon tui-icon-plus",class:[t.disabled||t.value>=t.max?"tui-disabled":""],staticStyle:{"border-radius":"5upx","background-color":"#FF4444",color:"#FFFFFF","font-size":"20upx"},on:{click:function(e){e.stopPropagation(),arguments[0]=e=t.$handleEvent(e),t.plus.apply(void 0,arguments)}}})],1)},o=[]},"864b":function(t,e,a){"use strict";var i;a.d(e,"b",(function(){return n})),a.d(e,"c",(function(){return o})),a.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",[a("v-uni-view",{staticClass:"loadlogo-container"},[a("v-uni-view",{staticClass:"loadlogo"},[a("v-uni-image",{staticClass:"image",attrs:{src:t.loadImage||t.imgfixUrls+"loadlogo.svg",mode:"aspectFill"}})],1)],1)],1)},o=[]},"8ba4":function(t,e,a){var i=a("23e7"),n=a("5e89");i({target:"Number",stat:!0},{isInteger:n})},a017:function(t,e,a){var i=a("0e15");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("5b536541",i,!0,{sourceMap:!1,shadowMode:!1})},a555:function(t,e,a){"use strict";var i=a("be77"),n=a.n(i);n.a},ba99:function(t,e,a){"use strict";a.r(e);var i=a("20c8"),n=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},be77:function(t,e,a){var i=a("d6fa");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("db93fbca",i,!0,{sourceMap:!1,shadowMode:!1})},c6b2:function(t,e,a){"use strict";var i=a("0b44"),n=a.n(i);n.a},cb39:function(t,e,a){"use strict";a.r(e);var i=a("864b"),n=a("002c");for(var o in n)"default"!==o&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("c6b2");var u,l=a("f0c5"),r=Object(l["a"])(n["default"],i["b"],i["c"],!1,null,"23fdce49",null,!1,i["a"],u);e["default"]=r.exports},d1a1:function(t,e,a){"use strict";a("c975"),a("a9e3"),a("8ba4"),a("ac1f"),a("1276"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={name:"tuiNumberbox",props:{value:{type:[Number,String],default:0},min:{type:Number,default:1},max:{type:Number,default:99},step:{type:Number,default:1},disabled:{type:Boolean,default:!1},iconSize:{type:Number,default:26},iconColor:{type:String,default:"#666666"},height:{type:Number,default:42},width:{type:Number,default:80},size:{type:Number,default:28},bgcolor:{type:String,default:"#F5F5F5"},color:{type:String,default:"#333"},index:{type:[Number,String],default:0},custom:{type:[Number,String],default:0}},created:function(){this.inputValue=+this.value},data:function(){return{inputValue:0,inpushur:null}},watch:{value:function(t){this.inputValue=+t}},methods:{getScale:function(){var t=1;return Number.isInteger(this.step)||(t=Math.pow(10,(this.step+"").split(".")[1].length)),t},calcNum:function(t){if(!this.disabled){var e=this.getScale(),a=this.value*e,i=this.step*e;"reduce"===t?a-=i:"plus"===t&&(a+=i);var n=a/e;"plus"===t&&n<this.min?n=this.min:"reduce"===t&&n>this.max&&(n=this.max),n<this.min||n>this.max||this.handleChange(n,t)}},plus:function(t){this.calcNum("plus")},reduce:function(){this.calcNum("reduce")},srvalue:function(t){this.inpushur=Number(t.detail.value)},blur:function(t){var e=t.detail.value;e?(~e.indexOf(".")&&Number.isInteger(this.step)&&(e=e.split(".")[0]),e=Number(e),e>this.max?e=this.max:e<this.min&&(e=this.min)):e=this.min,e==this.value&&e!=this.inputValue&&(this.inputValue=e),this.value!=e&&(this.$emit("chanji",e),this.$emit("chankg",e))},handleChange:function(t,e){this.disabled||this.$emit("change",{value:t,type:e,index:this.index,custom:this.custom})}}};e.default=i},d6fa:function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,'@font-face{font-family:numberbox;src:url(data:application/font-woff;charset=utf-8;base64,d09GRgABAAAAAASQAA0AAAAABtwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABGRlRNAAAEdAAAABoAAAAciBpnRUdERUYAAARUAAAAHgAAAB4AKQALT1MvMgAAAZwAAABDAAAAVjxzSINjbWFwAAAB9AAAAEYAAAFK5zLpOGdhc3AAAARMAAAACAAAAAj//wADZ2x5ZgAAAkgAAACHAAAAnIfIEjxoZWFkAAABMAAAAC8AAAA2FZWEOWhoZWEAAAFgAAAAHAAAACQH3gOFaG10eAAAAeAAAAARAAAAEgwAAAFsb2NhAAACPAAAAAwAAAAMADAATm1heHAAAAF8AAAAHwAAACABEAAobmFtZQAAAtAAAAFJAAACiCnmEVVwb3N0AAAEHAAAAC0AAABV/+8iFXjaY2BkYGAA4gVmC5Tj+W2+MnCzMIDATWsFOQT9v5GFgbkeyOVgYAKJAgDrogf+AHjaY2BkYGBu+N/AEMPCAAJAkpEBFbAAAEcKAm142mNgZGBgYGWQYQDRDAxMQMwFhAwM/8F8BgALpAE5AHjaY2BkYWCcwMDKwMDUyXSGgYGhH0IzvmYwYuQAijKwMjNgBQFprikMDs9Yn01kbvjfwBDD3MDQABRmBMkBAOXpDHEAeNpjYYAAFghmZGAAAACdAA4AAAB42mNgYGBmgGAZBkYGEHAB8hjBfBYGDSDNBqQZGZiesT6b+P8/AwOElvwnWQxVDwSMbAxwDiMTkGBiQAWMDMMeAABRZwszAAAAAAAAAAAAAAAwAE542iWKQQrCMBBF5xNpd0pQ7EIoTEnahSCTUNqdWz2A9TrieXKeXCc1qcPn/zfzh0BYv2pVH7oQgbvqdG5Yt/DTrNlPYz+wHvuuqhFSME4sFshTgKUsKfhH5lg8BSul3i5bS3mQdd0RIh2IjnvUrkXDd8zuhuFt86tY9fonIsSYgsXpB+cCGosAeNp9kD1OAzEQhZ/zByQSQiCoXVEA2vyUKRMp9Ailo0g23pBo1155nUg5AS0VB6DlGByAGyDRcgpelkmTImvt6PObmeexAZzjGwr/3yXuhBWO8ShcwREy4Sr1F+Ea+V24jhY+hRvUf4SbuFUD4RYu1BsdVO2Eu5vSbcsKZxgIV3CKJ+Eq9ZVwjfwqXMcVPoQb1L+EmxjjV7iFa2WpDOFhMEFgnEFjig3jAjEcLJIyBtahOfRmEsxMTzd6ETubOBso71dilwMeaDnngCntPbdmvkon/mDLgdSYbh4FS7YpjS4idCgbXyyc1d2oc7D9nu22tNi/a4E1x+xRDWzU/D3bM9JIbAyvkJI18jK3pBJTj2hrrPG7ZynW814IiU68y/SIx5o0dTr3bmniwOLn8owcfbS5kj33qBw+Y1kIeb/dTsQgil2GP5PYcRkAAAB42mNgYoAALjDJyIAOWMGiTIxMjMwiWZmJQJRXVQoigTgjMd9QGIsgAFDsEBsAAAAAAAAB//8AAgABAAAADAAAABYAAAACAAEAAwAEAAEABAAAAAIAAAAAeNpjYGBgZACCq0vUOUD0TWsFORgNADPBBE4AAA==) format("woff");font-weight:400;font-style:normal}.tui-numbox-icon[data-v-aeabd94e]{font-family:numberbox!important;font-style:normal;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;\n\t/* padding: 16rpx; */width:%?50?%;height:%?50?%;text-align:center;line-height:%?50?%}.tui-padding-wx[data-v-aeabd94e]{padding:%?16?%}.tui-icon-reduce[data-v-aeabd94e]:before{content:"\\e691"}.tui-icon-plus[data-v-aeabd94e]:before{content:"\\e605"}.tui-numberbox[data-v-aeabd94e]{display:-webkit-inline-flex;display:inline-flex;align-items:center}.tui-num-input[data-v-aeabd94e]{text-align:center;\n\t/* margin: 0 12rpx; */font-weight:400;border-top:1px solid rgba(255,68,68,.5);border-bottom:1px solid rgba(255,68,68,.5)}.tui-disabled[data-v-aeabd94e]{color:#ededed!important}',""]),t.exports=e},d8a5:function(t,e,a){"use strict";a.r(e);var i=a("efcb"),n=a("ba99");for(var o in n)"default"!==o&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("53f2");var u,l=a("f0c5"),r=Object(l["a"])(n["default"],i["b"],i["c"],!1,null,"1c0be340",null,!1,i["a"],u);e["default"]=r.exports},e358:function(t,e,a){"use strict";a.r(e);var i=a("d1a1"),n=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},e708:function(t,e,a){"use strict";a.r(e);var i=a("77f1"),n=a("e358");for(var o in n)"default"!==o&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("a555");var u,l=a("f0c5"),r=Object(l["a"])(n["default"],i["b"],i["c"],!1,null,"aeabd94e",null,!1,i["a"],u);e["default"]=r.exports},efcb:function(t,e,a){"use strict";var i;a.d(e,"b",(function(){return n})),a.d(e,"c",(function(){return o})),a.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticClass:"hx-jump-ball"},[a("v-uni-view",{staticClass:"ballBox",attrs:{animation:t.ballBoxAnimationData}},[a("v-uni-view",{staticClass:"ballOuter",style:{width:2*t.ballWidth+"upx",height:2*t.ballHeight+"upx","background-color":t.backgroundColor,"background-image":t.backgroundImage?"url("+t.backgroundImage+")":"","z-index":t.index},attrs:{animation:t.ballOuterAnimationData}},[a("v-uni-text",{staticStyle:{"font-size":"24",margin:"auto",color:"#FFFFFF","font-weight":"700","line-height":"20upx","text-align":"center"}},[t._v("+1")])],1)],1)],1)},o=[]}}]);