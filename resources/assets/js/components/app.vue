<template>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">Déplier</span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
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
                </ul>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <nav-search @submit="changeWord" url="/@word/$/autocomplete"></nav-search>
                </div>
            </div>
        </nav>
        <div  v-if="relationTypes">
            <component v-bind:is="component" :config="config" :userConfig="userConfig" :showRelations="showRelations" :relationTypes="relationTypes" :wordsData="wordsData" :word="word" :relation="relation" :words="words"></component>
        </div>
    </div>

</div>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default {
        components: {
            'nav-search': require('./search.vue'),
            'show-welcome': require('./show_welcome.vue'),
            'show-word': require('./show_word.vue'),
            'show-relations': require('./show_relations.vue')
        },
        props: {
            urlword: null,
            urlwordrelation: null
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
            }
            , changeWord: HUB.changeWord
        }
    };
</script>