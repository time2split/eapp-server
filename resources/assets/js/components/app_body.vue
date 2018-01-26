<template>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">

            <div class="nav navbar-left" id="navbarNav">
                <ul class="nav navbar-nav mr-auto">
                    <li>
                        <a class="nav-link" href="#" @click="loadComponent('show-welcome')" @click.prevent="onEventPrevent">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="loadComponent('show-relations')" @click.prevent="onEventPrevent">Relations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="loadComponent('pattern-engine')" @click.prevent="onEventPrevent">Inférences</a>
                    </li>
                </ul>
            </div>

            <!--<div class="container-fluid">-->
            <!--<div class="row">-->
            <form class="navbar navbar-form navbar-right inline-form"  @submit="changeWordFromForm" @submit.prevent="onEventPrevent">
                <div class="form-group">
                    <nav-search url="/@word/$/autocomplete" placeholder="Recherche"></nav-search>
                    <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>
                </div>
            </form>
            <!--</div>-->
            <!--</div>-->
        </nav>
        <div v-if="shared.relationTypes">
            <component v-bind:is="component" :config="shared.config" :userConfig="shared.userConfig" :showRelations="showRelations" :relationTypes="shared.relationTypes" :relationsData="relationsData" :wordsData="shared.wordsData" :words="shared.words" @changeWord="changeWord" @changeRelation="changeRelation"></component>
        </div>
    </div>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default {
        components: {
            'nav-search': require('./form/search.vue'),
            'show-welcome': require('./show_welcome.vue'),
            'show-word': require('./show_word.vue'),
            'show-relations': require('./show_relations.vue'),
            'pattern-engine': require('./pattern_engine/main.vue')
        },
//        props: {
//            urlword: null,
//            urlwordrelation: null,
//            papp: null,
//            args: null
//        },
        data()
        {
            return {
                shared: HUB.$data.shared,
                component: null,
                firstApp: false, //L'app du tout premier chargement
                word: null,
//                relation: null
            };
        },
        computed: {
            showRelations()
            {
                var ret = [];
                var ids = this.shared.config.relations.exclude.map((rel) => rel._id)

                for (var rel of this.shared.relationTypes) {

                    if (ids.indexOf(rel._id) == -1)
                        ret.push(rel);
                }
                return ret;
            },
            relationsData()
            {
                var ret = {};

                for (var r of this.showRelations) {
                    ret[r._id] = r;
                }
                return ret;
            }
        },
        created: function ()
        {
            HUB.addHttpRequest('/@get/relationTypes', (response) => {
                this.shared.relationTypes = response.data;
            });
            HUB.addHttpRequest('/@get/relationTypes?get=excluded', (response) => {
                this.shared.config.relations.exclude = response.data;
            });
            this.firstApp = this.shared.app = this.appFromUrl();

            if (this.shared.app.data.word)
                this.word = this.shared.app.data.word;

            this.loadAppPage();
            this.$watch('shared.app', this.loadAppPage);
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            appFromUrl()
            {
                var url = HUB.getUrl();
                var path = url.path;
                var direction;
                var action;

                if (path.length >= 3) {
                    direction = path[1];
                    action = path[2];
                }
                else {
                    direction = '@app:site';
                    action = 'show-welcome';
                }
                return {
                    direction: direction,
                    action: action,
                    data: url.args,
                    path: url.path.slice(3),
                    isFirst: true
                };
            },
            changeWordFromForm(e)
            {
                this.changeWord($(e.target).find('input').val());
            },
            changeRelation(relation)
            {
//                console.log('I want to use the relation ' + relation)

                if (this.component === 'show-word' && this.relation == relation)
                    return;

                var app = this.getApp('show-word');
                this.shared.app = Object.assign({}, app, {isFirst: true, data: {word: this.word, relation: relation}});
            },
            changeWord(word)
            {
//                this.relation = null;
//                console.log('I want to use the word ' + word)
                /*
                 * Si déjà sur la page avec le même mot on ne fait rien
                 */
                if (this.component === 'show-word' && this.word == word)
                    return;

                var app = this.getApp('show-word');
                this.shared.app = Object.assign({}, app, {isFirst: true, data: {word: word, relation: this.relation}});
            },
            getApp(component)
            {
                var assoc = {
                    'show-word': {
                        direction: '@app:site',
                        action: 'show-word',
                        isFirst: true,
                        data: {}
                    },
                    'show-relations': {
                        direction: '@app:site',
                        action: 'show-relations',
                        isFirst: true,
                        data: {}
                    },
                    'show-welcome': {
                        direction: '@app:site',
                        action: 'show-welcome',
                        isFirst: true,
                        data: {}
                    },
                    'pattern-engine': {
                        direction: '@app:site',
                        action: 'pattern-engine',
                        isFirst: true,
                        data: {}
                    }
                };
                if (assoc[component])
                    return assoc[component];

                return null;
            },
            loadComponent(component)
            {
                /*
                 * Si on est déjà sur la page on ne change rien
                 */
                if (component === this.component)
                    return;

                this.shared.app = this.getApp(component);
            },
            loadAppPage()
            {
                var error;
                var app = this.shared.app;

                /*
                 * Retour arrière hors session
                 */
                if (app === null) {
                    app = this.appFromUrl();
                    app.isFirst = false;
                    this.shared.app = app;
                }
                else if (['pattern-engine', 'show-word', 'show-welcome',
                    'show-relations'].indexOf(app.action) !== -1) {
                    this.component = app.action;
                }
                else {
                    error = true;
                }
                this.relation = app.data.relation ? app.data.relation : null;
                this.word = app.data.word ? app.data.word : null;

                if (error) {
                    this.component = 'show-welcome';
                    console.error('Unknow page ' + app.direction + ':' + app.action);
                }

                /*
                 * Empeche d'enregistre le tout premier chargement
                 * Dans l'autre cas cela ajoute à chaque rechargement une nouvelle page
                 * dans l'historique.
                 */
                if (app === this.firstApp) {
                }
                /*
                 * N'enregistre une même page qu'une seule fois
                 */
                else if (app.isFirst) {
                    var end = '';
                    app.isFirst = false;

                    if (app.path) {
                        end += '/' + app.path.join('/');
                    }

                    if (app.data) {
                        var key;
                        var tmp = [];

                        for (key in app.data) {

                            if (app.data[key])
                                tmp.push(key + '=' + app.data[key]);
                        }

                        if (tmp.length > 0)
                            end += '?' + tmp.join('&');
                    }
                    var url = ('/' + app.direction + '/' + app.action + end)
                    history.pushState({app: app}, null, url);
                }
            },
        }
    };
</script>