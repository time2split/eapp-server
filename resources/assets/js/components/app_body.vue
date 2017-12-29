<template>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">

            <div class="nav navbar-left" id="navbarNav">
                <ul class="nav navbar-nav mr-auto">
                    <li>
                        <a class="nav-link" href="#" @click="loadComponent('show-welcome',$event)">Accueil</a>
                    </li>
                    <li v-if="word" class="nav-item">
                        <a class="nav-link" href="#" @click="loadComponent('show-word',$event)">"{{ word }}"</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="loadComponent('show-relations',$event)">Relations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="loadComponent('pattern-engine',$event)">Inférences</a>
                    </li>
                </ul>
            </div>

            <!--<div class="container-fluid">-->
            <!--<div class="row">-->
            <form class="navbar navbar-form navbar-right inline-form"  @submit="changeWord" @submit.prevent="onSubmitPrevent">
                <div class="form-group">
                    <nav-search url="/@word/$/autocomplete" placeholder="Recherche"></nav-search>
                    <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>
                </div>
            </form>
            <!--</div>-->
            <!--</div>-->
        </nav>
        <div v-if="relationTypes">
            <component v-bind:is="component" :config="config" :userConfig="userConfig" :showRelations="showRelations" :relationTypes="relationTypes" :wordsData="wordsData" :word="word" :relation="relation" :words="words"></component>
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
        props: {
            urlword: null,
            urlwordrelation: null,
            papp: null,
            args: null
        },
        data() {
            return HUB.$data.shared;
        },
        computed: {
            showRelations() {
                var ret = [];
                var ids = this.config.relations.exclude.map((rel) => rel._id)

                for (var rel of this.relationTypes)
                {
                    if (ids.indexOf(rel._id) == -1)
                        ret.push(rel);
//                    else
//                        console.log(rel.name)
                }
                return ret;
            }
        },
        created: function ()
        {
            HUB.addHttpRequest('/@get/relationTypes', (response) => {
                this.relationTypes = response.data;
            });

            HUB.addHttpRequest('/@get/relationTypes?get=excluded', (response) => {
                this.config.relations.exclude = response.data;
            });

            var app = this.papp.split(':', 2);

            if (app.length == 2)
            {
                this.app.direction = app[0];
                this.app.action = app[1];

                if (this.args instanceof String)
                    this.app.args = this.args.split('/');

                //TODO : mettre en externe la gestion du chargement de service particulier
                this.loadComponent('pattern-engine');
            }
            if (this.urlwordrelation != '')
                this.relation = this.urlwordrelation;

            if (this.urlword != '')
                this.word = this.urlword;

            if (this.word != null)
                this.loadComponent('show-word');

            this.$watch('word', this.loadWordPage)
        },
        methods: {
            loadComponent: function (component, e)
            {
                if (e)
                    e.preventDefault();

                if (component == this.component)
                    return;

                this.component = component;
            },
            loadWordPage: function ()
            {
                this.component = 'show-word';
            },
            onSubmitPrevent(e)
            {
                e.preventDefault();
            },
            changeWord: HUB.changeWord
        }
    };
</script>