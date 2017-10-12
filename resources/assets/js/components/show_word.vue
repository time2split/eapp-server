<template>
    <section>
        <div v-if="word" class="panel panel-default">
            <div class="panel-heading">
                <h1>{{ word }}</h1>
            </div>
            <div class="panel-body">
                <dl v-if="wdata" class="dl-horizontal">
                    <dt>Poids</dt>
                    <dd><span class="badge">{{ wdata.w }}</span></dd>
                    <dt>Identifiant</dt>
                    <dd><span class="badge badge-info">{{ wdata._id }}</span></dd>
                </dl>
                <div v-else="" class="alert alert-info">Le mot n'est pas présent dans la base de données</div>
                <div v-if="relations">
                    <div v-for="relation in relationTypes">
                        <show-word-relation :words="words" :relation="relation" :relations="relations[relation._id]"></show-word-relation>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['word', 'relationTypes', 'config', 'words', 'wordsData'],
        components: {
            'show-word-relation': require('./show_word_relation.vue')
        },
        data: function () {
            return {
                wdata: null,
                relations: null
            };
        },
        created: function () {
            this.changeTheWord();
            this.$watch('word', this.changeTheWord);
        },
        methods: {
            initRelations() {
                var ret = {};
                var rel;

                for (rel of this.relationTypes)
                {
                    ret[rel._id] = null;
                }
                this.relations = ret;
            },
            fillRelations: function (page = 1) {
                var url = '/word/' + this.word + '/childs?per_page=100&page=' + page;

                HUB.addHttpRequest(url, (response) => {
                    this.cancelToken = null;
                    var rels = response.data.data;

                    if (rels.length == 0)
                        rels = null;
                    else {

                        if (page < this.config.show_word.max_page)
                        {
                            if(page > -1)
                                page++;
                            
                            this.fillRelations(page);
                        }

                        var ret = this.relations;

                        for (let rel of rels)
                        {
                            var key = rel.t;
                            if (ret[key] == null)
                                ret[key] = [];

                            ret[key].push(rel);
                        }
                    }
                });
            },
            changeTheWord: function () {

//                if (this.wordsData[this.word])
//                {
//                    var data = JSON.parse(this.wordsData[this.word]);
//                    this.relations = data.relations;
//                    this.wdata = data.wdata;
//                    console.log(data);
//                } else
                {
                    HUB.addHttpRequest('/word/' + this.word, (response) => {
                        this.wdata = response.data.word;
                        HUB.$set(this.wordsData, this.word, JSON.stringify(this.$data));
                        console.log(this.wordsData);
                    });
                    this.wdata = null;
                    this.initRelations();
                    this.fillRelations();
                }
            }
        }
    };
</script>