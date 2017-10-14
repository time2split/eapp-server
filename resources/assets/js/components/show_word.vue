<template>
    <section>
        <div v-if="word" class="panel panel-default">
            <div class="panel-heading">
                <h1>{{ word }}</h1>
            </div>
            <show-word-config></show-word-config>
            <div class="panel-body">
                <dl v-if="wdata" class="dl-horizontal">
                    <dt>Poids</dt>
                    <dd><span class="badge">{{ wdata.w }}</span></dd>
                    <dt>Identifiant</dt>
                    <dd><span class="badge badge-info">{{ wdata._id }}</span></dd>
                </dl>
                <div v-else="" class="alert alert-info">Le mot n'est pas présent dans la base de données</div>
                <div v-if="relations">
                    <div v-if="relation">
                        <show-word-relation :word="word" :words="words" :rdata="rdata" :relations="relations[rdata._id]" :showType="['OUT','IN']"></show-word-relation>
                    </div>
                    <div v-else v-for="relation in showRelations">
                        <show-word-relation :word="word" :words="words" :rdata="relation" :relations="relations[relation._id]" :showType="['OUT','IN']"></show-word-relation>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['word', 'relation', 'showRelations', 'config', 'words', 'wordsData', 'userConfig'],
        components: {
            'show-word-relation': require('./show_word_relation.vue'),
            'show-word-config': require('./show_word_config.vue')
        },
        data() {
            return {
                pages: 0, //nombres de pages parcourues
                doonce_counts: false,
                fillCounts_options: {},
                wdata: null,
                rdata: null,
                relations: null,
                cancelToken: []
            };
        },
//        computed: {
//            sortedRelations() {
//                var type = HUB.shared.userConfig.sort_type;
//                var ret = Array.from(this.relations);
//
//                switch (type)
//                {
//                    case 'weight':
//                        break;
//                }
//                return ret;
//            }
//        },
        created() {
            this.changeTheWord();

//            if (this.relation)
//                this.changeTheRelation();

            this.$watch('pages', this.changePages);
            this.$watch('word', this.changeTheWord);
            this.$watch('relation', this.changeTheWord);
            this.$watch('userConfig.sort_type', this.resort)
            HUB.$watch('wordComputed', this.resort);
        },
        destroyed() {
            this.cancelTokens();
        },
        methods: {
            resort()
            {
                this.sortRelations();
            },
            sortRelations(rtypes = ['IN', 'OUT']) {
                var type = HUB.shared.userConfig.sort_type;
                var fsort = null;

                switch (type)
                {
                    case 'weight':
                        fsort = () => function (a, b) {
                                return b.w - a.w;
                            }
                        ;
                        break;

                    case 'alpha':
                        fsort = (what) => function (a, b) {
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

                for (var rid in this.relations)
                {
                    var rel = this.relations[rid];

                    for (var rtype of rtypes)
                        rel[rtype].data.sort(fsort(rtype));
            }
            },
            cancelTokens() {

                for (var tok of this.cancelToken)
                {
                    tok.cancel();
                }
            },
            initRelations() {
                var ret = {};
                var rel;

                for (rel of this.showRelations)
                {
                    ret[rel._id] = {
                        IN: {count: null, nb: 0, data: []},
                        OUT: {count: null, nb: 0, data: []}
                    };
                }
                this.relations = ret;
            },
            changePages() {

                if (this.doonce_counts)
                    return;

                var page_min = this.config.show_word.min_page_for_counts;

                if (this.pages >= page_min)
                {
                    this.doonce_counts = true;
                    this.fillCountRelations(this.fillCounts_options);
                }
            },
            fillRelations(page = 1, options = {}) {
                var per_page = options.per_page ? options.per_page : this.config.show_word.per_page;
                var max_page = options.max_page ? options.max_page : this.config.show_word.max_page;
                var type_rel = options.type_rel ? options.type_rel : 'OUT';
                var url = options.url ? options.url : '/@word/' + this.word + '/childs?';

                url += 'per_page=' + per_page + '&page=' + page;

                var token = HUB.addHttpRequest(url, (response) => {
                    var rels = response.data.data;

                    if (rels.length == 0)
                        rels = null;
                    else {
                        this.pages++;

                        if (max_page === true || page < max_page)
                        {
                            if (page > -1)
                                page++;

                            if (rels.length === per_page)
                                this.fillRelations(page, options);
                        }
                        var ret = this.relations;

                        for (let rel of rels)
                        {
                            var key = rel.t;
                            if (ret[key] === undefined)
                                continue;

                            ret[key][type_rel].nb += 1;
                            ret[key][type_rel].data.push(rel);
                        }
                        this.sortRelations([type_rel]);
                    }
                });
                this.cancelToken.push(token);
            },
            fillCountRelations(options = {}){
                var relations = options.relations ? options.relations : this.relationTypes;

                for (let rel of relations)
                {
                    var tok1 = HUB.addHttpRequest('/@word/' + this.word + '/childs?count=true&rtid=' + rel._id, (response) => {
                        this.relations[rel._id].OUT.count = response.data;
                    });
                    var tok2 = HUB.addHttpRequest('/@word/' + this.word + '/parents?count=true&rtid=' + rel._id, (response) => {
                        this.relations[rel._id].IN.count = response.data;
                    });
                    this.cancelToken.push(tok1);
                    this.cancelToken.push(tok2);
            }
            },
            changeTheWord() {
                this.doonce_counts = false;
                this.pages = 0;
                this.fillCounts_options = {};

                console.log(this.word)
                console.log(this.relation)

//                if (this.wordsData[this.word])
//                {
//                    var data = JSON.parse(this.wordsData[this.word]);
//                    this.relations = data.relations;
//                    this.wdata = data.wdata;
//                    console.log(data);
//                } else
                {
                    this.wdata = null;

                    var tok = HUB.addHttpRequest('/@word/' + this.word, (response) => {
                        this.wdata = response.data.word;
//                        Vue.set(this.wordsData, this.word, JSON.stringify(this.$data));
//                        console.log(this.wdata);
                    });
                    this.cancelToken.push(tok);

                    if (this.relation === null)
                    {
                        this.doonce_counts = true;
                        this.initRelations();
                        {
                            let options = {
                                url: "/@word/" + this.word + "/childs?",
                                type_rel: 'OUT'
                            };
                            this.initRelations();
                            this.fillRelations(1, options);
                        }
                        {
                            let options = {
                                url: "/@word/" + this.word + "/parents?",
                                type_rel: 'IN'
                            };
                            this.fillRelations(1, options);
                        }
                    }
                }
                if (this.relation !== null)
                {
                    for (let rel of this.showRelations)
                    {
                        if (rel.name === this.relation)
                        {
                            this.rdata = rel;
                            break;
                        }
                    }
                    this.fillCounts_options = {relations: [this.rdata]};
                    {
                        let options = {
                            url: "/@word/" + this.word + "/childs?rtid=" + this.rdata._id + '&',
                            type_rel: 'OUT',
                            max_page: true
                        };
                        this.initRelations();
                        this.fillRelations(1, options);
                    }
                    {
                        let options = {
                            url: "/@word/" + this.word + "/parents?rtid=" + this.rdata._id + '&',
                            type_rel: 'IN',
                            max_page: true
                        };
                        this.fillRelations(1, options);
                    }
                }
            },
//            changeTheRelation()
//            {
//                this.cancelTokens();
//                console.log('this.relation');
//                console.log(this.relation);
//
//                if (this.relation === null)
//                {
//                    this.changeTheWord();
//                    return;
//                }
//
//                for (let rel of this.relationTypes)
//                {
//                    if (rel.name === this.relation)
//                    {
//                        this.rdata = rel;
//                        break;
//                    }
//                }
//                this.fillCountRelations({relations: [this.rdata]});
//                {
//                    let options = {
//                        url: "/@word/" + this.word + "/childs?rtid=" + this.rdata._id + '&',
//                        type_rel: 'OUT',
//                        max_page: true
//                    };
//                    this.initRelations();
//                    this.fillRelations(1, options);
//                }
//                {
//                    let options = {
//                        url: "/@word/" + this.word + "/parents?rtid=" + this.rdata._id + '&',
//                        type_rel: 'IN',
//                        max_page: true
//                    };
//                    this.fillRelations(1, options);
//                }
//            }
        }
    };
</script>