import Vue from 'vue';
import VueResource from 'vue-resource';
import App from './components/App.vue';

Vue.use(VueResource);

require('babel-polyfill');

window.initBynderInterface = function (ref, props) {
    /* eslint-disable no-new */
    new Vue({
        el: ref,
        render(createElement) {
            return createElement(App, {
                props,
            });
        },
    });
};
