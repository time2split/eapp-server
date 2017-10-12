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
                        <show-word-relation @askForWord="askForWord" :words="words" :relation="relation" :relations="relations[relation._id]"></show-word-relation>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
    export default{
        props: ['word', 'relationTypes', 'config'],
        components: {
            'show-word-relation': require('./show_word_relation.vue')
        },
        data: function () {
            return {
                wdata: null,
                relations: null,
                words: {},
                wordQ: [],
                wordComputed: false
            };
        },
        created: function () {
            this.changeTheWord();
            this.$watch('word', this.changeTheWord);
            this.$watch('wordQ', this.getAWord);
        },
        beforeDestroy: function () {
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
            askForWord: function (wid)
            {
                if (!this.words[wid] && this.wordQ.indexOf(wid) == -1)
                    this.wordQ.push(wid);
            },
            fillWords: function ()
            {
                var nb = this.config.get_words.nb;
                var wids = [];

                for (var i = 0; i < nb && this.wordQ.length; i++)
                {
                    wids.push(this.wordQ.pop());
                }
                var url = '/@get/words?words=' + wids.join(',');

                axios.get(url).then((response) => {
                    var words = response.data;

                    for (var word of words)
                    {
                        var wid = word._id;
                        this.$set(this.words, wid, word);
                    }

                    if (this.wordQ.length == 0)
                        this.wordComputed = false;
                    else
                        this.fillWords();
                });
            },
            getAWord: function ()
            {
                if (this.wordComputed)
                    return;

                this.wordComputed = true;
                this.fillWords();
            },
            fillRelations: function (page = 1) {
                var url = '/word/' + this.word + '/childs?per_page=100&page=' + page;

                axios.get(url).then((response) => {
                    this.cancelToken = null;
                    var rels = response.data.data;

                    if (rels.length == 0)
                        rels = null;
                    else {

                        if (page < this.config.show_word.max_page)
                            this.fillRelations(page + 1);

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

                axios.get('/word/' + this.word).then((response) => {
                    this.wdata = response.data.word;
//                    data = {
//                        word : this.word,
//                        wdata : wdata,
//                        relations : {},
//                        
//                    };
//                    this.$set(this.wordData,data);
//                    this.cancelToken = null;
                });
                this.wdata = null;
                this.initRelations();
                this.fillRelations();
            }
        }
    };
</script>