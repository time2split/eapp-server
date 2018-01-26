<template>
    <section class="container-fluid">
        <div v-if="word" class="panel panel-default">
            <div class="panel-heading">
                <h1>{{ word }}</h1>
            </div>

            <div class="panel-body">
                <show-word-menu-relations :showRelations="showRelations" :relationsInfos="relationsInfos" @changeRelations="changeRelations"></show-word-menu-relations>

                <div class="col-sm-9 col-sm-pull-3">
                    <ul id="show-relation-type" class="nav nav-tabs">
                        <li class="INFOS"><a href="#" @click="changeRelationType(null)" @click.prevent="onEventPrevent">Infos</a></li>
                        <li class="OUT"><a href="#" @click="changeRelationType('OUT')" @click.prevent="onEventPrevent">Sortant</a></li>
                        <li class="IN"><a href="#" @click="changeRelationType('IN')" @click.prevent="onEventPrevent">Entrant</a></li>
                        <li class="INOUT"><a href="#" @click="changeRelationType(['IN','OUT'])" @click.prevent="onEventPrevent">Tous</a></li>
                    </ul>

                    <div v-if="selectedRelationTypes && selectedRelations">
                        <div class="row">
                            <show-word-config class="col-md-4"></show-word-config>
                        </div>
                        <div v-for="relation in selectedRelations">
                            <show-word-relation :word="word" :words="words" :relation="relationsInfos[relation._id]" :showType="relationsInfos.selectedTypes" @changeWord="changeWord"></show-word-relation>
                        </div>
                    </div>
                    <div v-else>
                        <div class="row">
                            <div v-if="wdata" class="col-md-3">
                                <dl class="dl-horizontal">
                                    <dt>Poids</dt>
                                    <dd><span class="badge">{{ wdata.w }}</span></dd>
                                    <dt>Identifiant</dt>
                                    <dd><span class="badge badge-info">{{ wdata._id }}</span></dd>
                                </dl>
                            </div>
                            <div v-else="" class="alert alert-info">Chargement ...</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['showRelations', 'config', 'words',
            'wordsData', 'userConfig', 'relationsData'],
        components: {
            'show-word-menu-relations': require('./show_word_menu_relations.vue'),
            'show-word-relation': require('./show_word_relation.vue'),
            'show-word-config': require('./show_word_config.vue')
        },
        data()
        {
            return {
                fillCounts_options: {},
                wdata: null,
                relations: null,
                cancelToken: {
                    word: null,
                    words: []
                },
                myapp: null,
                word: null,
                selectedRelations: null,
                selectedRelationTypes: null
            };
        },
        computed: {
            relationsInfos()
            {
                var ret = {selectedTypes: this.selectedRelationTypes};
                var rels = this.relationsData;

                for (var relid in this.relations)
                {
                    var rel = this.relations[relid];
                    var reldata = rels[relid];
                    var cnt;

                    if (rel.IN.count === null || rel.OUT.count === null)
                        cnt = null;
                    else
                        cnt = rel.IN.count + rel.OUT.count;

                    ret[relid] = {
                        IN: rel.IN,
                        OUT: rel.OUT,
                        ALL: {
                            nb: rel.IN.nb + rel.OUT.nb,
                            count: cnt
                        },
                        infos: rels[relid],
                        data: reldata
                    };
                }
                return ret;
            }
        },
        created()
        {
            this.changeTheWord();
            this.$watch('userConfig.sort_type', this.resort)
            this.$watch('wdata', this.setHtmlTitle);
            HUB.$watch('wordComputed', this.resort);
            HUB.$watch('shared.app', this.changeTheWord);
        },
        destroyed()
        {
            this.cancelTokensWords();
            this.cancelTokenWord();
            $('title').text(HUB.$data.shared.htmlTitle);
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            setHtmlTitle()
            {
                var title = HUB.$data.shared.htmlTitle;

                if (this.wdata === null)
                    return;

//                if (this.relation === null)
                {
                    title = title + ' - ' + this.wdata.nf;
                }
//                else {
//                    title = title + ' - ' + this.wdata.nf + ' (' + this.rdata.name + ')';
//                }
                $('title').text(title);
            },
            changeWord(word)
            {
                this.$emit('changeWord', word);
            },
            changeRelationType(type)
            {
                var menu = $("#show-relation-type");
                var types;

                if (type === 'INFOS')
                    types = null;
                else if (typeof type === 'string') {
                    types = [type];
                }
                else
                    types = type;

                this.selectedRelationTypes = types;

                menu.find('li').removeClass('active');
                var cls;

                if (types === null)
                    cls = "INFOS";
                else
                    cls = types.join('');

                $('.' + cls).addClass('active');
            },
            changeRelations(relations)
            {
                this.selectedRelations = null;
                this.selectedRelations = relations;

                if (this.selectedRelationTypes === null) {
                    this.changeRelationType('OUT');
                }

                this.fillCounts_options = {relations: relations};
                var tmprels = [];

                for (var relid in relations) {
                    tmprels.push(relations[relid]._id);
                }

                let optionso = {
                    url: "/@word/" + this.word + "/childs?rtid=",
                    type_rel: 'OUT',
                    max_page: true,
                    rel_ids: tmprels
                };
                let optionsi = {
                    url: "/@word/" + this.word + "/parents?rtid=",
                    type_rel: 'IN',
                    max_page: true,
                    rel_ids: tmprels
                };
                this.fillRelations(optionsi);
                this.fillRelations(optionso);
            },
            resort()
            {
                this.sortRelations();
            },
            sortRelations(rtypes = ['IN', 'OUT'])
            {
                var type = HUB.shared.userConfig.sort_type;
                var fsort = null;

                switch (type)
                {
                    case 'weight':
                        fsort = () => function (a, b)
                            {
                                return b.w - a.w;
                            }
                        ;
                        break;

                    case 'alpha':
                        fsort = (what) => function (a, b)
                            {
                                var k = what == 'IN' ? 'n1' : 'n2';
                                var wa = HUB.getWord(a[k]);
                                var wb = HUB.getWord(b[k]);

                                if (wa === null || wb === null)
                                    return 0;

                                return wa.n.localeCompare(wb.n);
                            }
                        ;
                        break;
                    default:
                        return;
                }

                for (var rid in this.relations) {
                    var rel = this.relations[rid];

                    for (var rtype of rtypes)
                        rel[rtype].data.sort(fsort(rtype));
            }
            },
            cancelTokenWord()
            {
                if (this.cancelToken.word === null)
                    return;

                this.cancelToken.word.cancel();
                this.cancelToken.word = null;
            },
            cancelTokensWords()
            {
                for (var tok of this.cancelToken.words) {
                    tok.cancel();
                }
                this.cancelToken.words = [];
            },
            initRelations()
            {
                var ret = {};
                var rel;
                this.cancelTokensWords();

                for (rel of this.showRelations)
                {
                    ret[rel._id] = {
                        IN: {count: null, computed: false, nb: 0, page: 1, data: [
                            ]},
                        OUT: {count: null, computed: false, nb: 0, page: 1, data: [
                            ]}
                    };
                }
                this.relations = ret;
            },
            canComputeCountRelations()
            {
                this.fillCountRelations(this.fillCounts_options);
            },
            fillRelations(options =
            {}) {
                var per_page = options.per_page ? options.per_page : this.config.show_word.per_page;
                var max_page = options.max_page ? options.max_page : this.config.show_word.max_page;
                var type_rel = options.type_rel ? options.type_rel : 'OUT';
                var rel_ids = options.rel_ids;
                var url = options.url ? options.url : '/@word/' + this.word + '/childs?';

                for (var rel_id of rel_ids) {
                    var ret = this.relations[rel_id][type_rel];
                    //On ne lance qu'une fois la procédure de chargement
                    //Fonction appelé comme pour la première fois (racine)
                    if (ret.computed === false) {
                        ret.computed = null; // calcul en cours
                    }
                    else if (ret.computed) {
                        continue;
                    }
                    var tmpurl = url + rel_id + '&per_page=' + per_page + '&page=' + ret.page;

                    var closure = function (ret, self)
                    {
                        var token = HUB.addHttpRequest(tmpurl, (response) => {
                            var isEnd = true;
                            var rels = response.data.data;

                            if (rels.length === 0) {
                                rels = null;
                            }
                            else {
                                ret.page++;

                                if (max_page === true || ret.page < max_page) {

                                    if (rels.length === per_page) {
                                        isEnd = false;
                                        self.fillRelations(options);
                                    }
                                }
                                ret.nb += rels.length;
                                ret.data = ret.data.concat(rels);
                                self.sortRelations([type_rel]);
                            }

                            if (isEnd) {
                                ret.computed = true;

                                if (ret.count === null) {
                                    ret.count = ret.nb;
                                }
                            }
                        });
                        self.cancelToken.words.push(token);
                    };
                    closure(ret, this);
            }
            },
            fillCountRelations(options =
            {}){
//                console.log("Fill count relations");
                var relations = options.relations ? options.relations : this.relationTypes;
                var urlargs = [];

                for (let rel of relations) {
                    urlargs.push(rel._id);
                }
                urlargs = urlargs.join(',');

                var tok1 = HUB.addHttpRequest('/@word/' + this.word + '/childs?count=true&rtid=' + urlargs, (response) => {
                    for (var relid in response.data)
                        this.relations[relid].OUT.count = response.data[relid];
                });
                var tok2 = HUB.addHttpRequest('/@word/' + this.word + '/parents?count=true&rtid=' + urlargs, (response) => {
                    for (var relid in response.data)
                        this.relations[relid].IN.count = response.data[relid];
                });
                this.cancelToken.words.push(tok1);
                this.cancelToken.words.push(tok2);
            },
            changeTheWord()
            {
                this.initRelations();
                this.changeRelationType('INFOS');

                var myapp = this.myapp;
                var curapp = HUB.$data.shared.app;

                if (myapp === curapp)
                    return;

                this.word = curapp.data.word;

                if (this.word === undefined)
                    this.word = null;

                if (this.word === null)
                    return;

                this.wdata = null;

                this.cancelTokenWord();

                var tok = HUB.addHttpRequest('/@word/' + this.word, (response) => {
                    this.wdata = response.data.word;
                });
                this.cancelToken.word = tok;

                this.fillCounts_options = {relations: this.showRelations};
                this.canComputeCountRelations();
            }
        }
    };
</script>