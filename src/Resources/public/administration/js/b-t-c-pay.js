!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/bundles/btcpay/",n(n.s="W/6q")}({"2msh":function(e,t,n){},"9HF9":function(e){e.exports=JSON.parse('{"coincharge-btcpay-generate-credentials":{"button":"Berechtigungsnachweise generieren"},"coincharge-btcpay-test-connection":{"button":"Verbindung testen","success":"Das Plugin ist mit dem Server verbunden"}}')},SZ7m:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var i=t[o],a=i[0],c={id:e+":"+o,css:i[1],media:i[2],sourceMap:i[3]};r[a]?r[a].parts.push(c):n.push(r[a]={id:a,parts:[c]})}return n}n.r(t),n.d(t,"default",(function(){return y}));var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=o&&(document.head||document.getElementsByTagName("head")[0]),c=null,s=0,u=!1,f=function(){},l=null,p="data-vue-ssr-id",d="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function y(e,t,n,o){u=n,l=o||{};var a=r(e,t);return g(a),function(t){for(var n=[],o=0;o<a.length;o++){var c=a[o];(s=i[c.id]).refs--,n.push(s)}t?g(a=r(e,t)):a=[];for(o=0;o<n.length;o++){var s;if(0===(s=n[o]).refs){for(var u=0;u<s.parts.length;u++)s.parts[u]();delete i[s.id]}}}}function g(e){for(var t=0;t<e.length;t++){var n=e[t],r=i[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(v(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(o=0;o<n.parts.length;o++)a.push(v(n.parts[o]));i[n.id]={id:n.id,refs:1,parts:a}}}}function h(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function v(e){var t,n,r=document.querySelector("style["+p+'~="'+e.id+'"]');if(r){if(u)return f;r.parentNode.removeChild(r)}if(d){var o=s++;r=c||(c=h()),t=S.bind(null,r,o,!1),n=S.bind(null,r,o,!0)}else r=h(),t=w.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var b,m=(b=[],function(e,t){return b[e]=t,b.filter(Boolean).join("\n")});function S(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=m(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}function w(e,t){var n=t.css,r=t.media,o=t.sourceMap;if(r&&e.setAttribute("media",r),l.ssrId&&e.setAttribute(p,t.id),o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}},"W/6q":function(e,t,n){"use strict";n.r(t);var r=n("kOof"),o=n.n(r),i=(n("tgke"),Shopware),a=i.Component,c=i.Mixin,s=i.ApiService;function u(e){return(u="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function f(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function l(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function p(e,t){return(p=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function d(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,r=g(e);if(t){var o=g(this).constructor;n=Reflect.construct(r,arguments,o)}else n=r.apply(this,arguments);return y(this,n)}}function y(e,t){if(t&&("object"===u(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}function g(e){return(g=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}a.register("coincharge-btcpay-buttons",{template:o.a,inject:[["coinchargeBtcpayApiService"]],mixins:[c.getByName("notification")],data:function(){return{isLoading:!1,config:{"BTCPay.config.btcpayServerUrl":"","BTCPay.config.integrationStatus":!1},integrationStatus:{"BTCPay.config.integrationStatus":!1},serverUrl:{"BTCPay.config.btcpayServerUrl":""}}},methods:{generateAPIKey:function(){var e=s.getByName("systemConfigApiService"),t=document.getElementById("BTCPay.config.btcpayServerUrl").value,n=this.removeTrailingSlash(t);this.config["BTCPay.config.btcpayServerUrl"]=n;var r=window.location.origin+"/api/_action/coincharge/credentials";return e.saveValues(this.config["BTCPay.config.btcpayServerUrl"]),window.location.replace(n+"/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&selectiveStores=true&redirect="+r)},removeTrailingSlash:function(e){return e.replace(/\/$/,"")},testConnection:function(){var e=this;this.isLoading=!0;var t=s.getByName("systemConfigApiService");this.coinchargeBtcpayApiService.verifyApiKey().then((function(n){if(e.config["BTCPay.config.integrationStatus"]=!!n.success,t.saveValues(e.config["BTCPay.config.integrationStatus"]),!1===n.success)return e.createNotificationWarning({title:"BTCPay Server",message:n.message}),void(e.isLoading=!1);e.createNotificationSuccess({title:"BTCPay Server",message:e.$tc("coincharge-btcpay-test-connection.success")}),e.isLoading=!1}))}}});var h=Shopware.Classes.ApiService,v=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&p(e,t)}(i,e);var t,n,r,o=d(i);function i(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"coincharge";return f(this,i),o.call(this,e,t,n)}return t=i,(n=[{key:"verifyApiKey",value:function(){var e="/_action/".concat(this.getApiBasePath(),"/verify"),t=this.getBasicHeaders();return this.httpClient.get(e,{headers:t}).then((function(e){return h.handleResponse(e)}))}},{key:"generateWebhook",value:function(){var e="/_action/".concat(this.getApiBasePath(),"/webhook");return this.httpClient.post(e,{},{headers:this.getBasicHeaders()}).then((function(e){return h.handleResponse(e)})).catch((function(e){throw console.error("Webhook couldn't be created: "+e.message),e}))}}])&&l(t.prototype,n),r&&l(t,r),Object.defineProperty(t,"prototype",{writable:!1}),i}(h),b=n("9HF9"),m=n("z6Wy"),S=Shopware.Application;S.addServiceProvider("coinchargeBtcpayApiService",(function(e){var t=S.getContainer("init");return new v(t.httpClient,e.loginService)})),Shopware.Locale.extend("de-DE",b),Shopware.Locale.extend("en-GB",m)},kOof:function(e,t){e.exports='<sw-container columns="repeat(2, auto)" gap="0px 30px">\n\t<div>\n\t\t<sw-button class="sw-button-coincharge" :isloading="isLoading" @click="generateAPIKey">{{ $tc(\'coincharge-btcpay-generate-credentials.button\') }}</sw-button>\n\t</div>\n\t<div>\n\t\t<sw-button-process class="sw-button-coincharge" :isloading="isLoading" @click="testConnection">{{ $tc(\'coincharge-btcpay-test-connection.button\') }}</sw-button>\n\t</div>\n</sw-container>\n'},tgke:function(e,t,n){var r=n("2msh");r.__esModule&&(r=r.default),"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n("SZ7m").default)("a1a79c50",r,!0,{})},z6Wy:function(e){e.exports=JSON.parse('{"coincharge-btcpay-generate-credentials":{"button":"Generate credentials"},"coincharge-btcpay-test-connection":{"button":"Test connection","success":"The plugin is connected to the server"}}')}});