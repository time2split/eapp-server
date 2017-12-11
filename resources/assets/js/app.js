
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */


// ne pas afficher (interne), r_succ, r_prev
// r_pos affichage différent

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

//require('./vue/app_body.js');

new Vue({
    el: '#app',
    components: {
        'my-app': require('./components/app_body.vue')
    }
});