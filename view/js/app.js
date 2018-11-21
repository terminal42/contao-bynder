import Vue from 'vue';
import VueResource from 'vue-resource';
import App from './components/App.vue';

require('../css/styles.scss');

Vue.use(VueResource);

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
