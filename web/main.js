import Vue from 'vue'
import App from './App'
import router from './router'
import VueMaterial from 'vue-material'
import axios from 'axios'
import VueAxios from 'vue-axios'
import 'vue-material/dist/vue-material.css'
import 'vue-material/dist/theme/default.css'
import Raven from 'raven-js'
import RavenVue from 'raven-js/plugins/vue'
import VueI18n from 'vue-i18n'
import Datetime from 'vue-datetime'
import infiniteScroll from 'vue-infinite-scroll'
import Cookies from 'js-cookie'
import moment from 'moment-timezone'
import mavonEditor from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'
import VueHcaptcha from '@hcaptcha/vue-hcaptcha';

Vue.use(mavonEditor)
Vue.use(infiniteScroll)
Vue.use(Datetime)
Vue.use(VueI18n)
Vue.use(VueAxios, axios)
Vue.use(VueMaterial)
Vue.use(require('vue-moment'))
Vue.use(moment)
Vue.use(vuehcaptcha)
var VueCookie = require('vue-cookie');
Vue.use(VueCookie);

var locale = ""
if(Cookies.get("lang") === "zh"){
    locale = "zh-cn"
}
else if(Cookies.get("lang") === "en"){
    locale = "en"
}else{
    let userLang = window.navigator.language || window.navigator.userLanguage;
    if(userLang && userLang.includes("zh")){
        locale = "zh-cn"
    }else{
        locale = "en"
    }
}

var i18n = new VueI18n({
    locale: "zh-cn",
    fallbackLocale: "zh-cn",
    messages: {},
    silentTranslationWarn: true
})

Raven
    .config('https://a9dbb410043f46369cd2f27763a1be82@sentry.io/282834', {
        ignoreErrors: ["*Request failed*"],
    })
    .addPlugin(RavenVue, Vue)
    .install();

Vue.config.productionTip = false


new Vue({
    i18n,
    el: '#app',
    router,
    components: {
        App
    },
    directives: {infiniteScroll},
    template: '<App/>'
})