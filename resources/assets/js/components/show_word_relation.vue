<template>
    <section class="show-relation">
        <h1>
            <span v-html="relationName"></span>
            (<show-count :nb="relation.ALL.nb" :count="relation.ALL.count"></show-count>)
        </h1>
        <div v-for="type in showType" v-bind:key="type._id">
            <h2>{{ infoType[type].label }} : <show-count v-bind:nb="relation[type].nb" v-bind:count="relation[type].count"></show-count></h2>
            <pagination v-bind:total="relation[type].count" v-bind:current-page.sync="currentPage[type]" v-bind:per-page="wordPerPage" @changePage="changePage(type,$event)"></pagination>
            <ul class="list-inline">
                <li class="list-inline-item" v-for="rel in relationsPaginate[type]">
                <a-word @changeWord="changeWord" v-bind:relation="rel" v-bind:words="words" v-bind:show="infoType[type].a_word_show" ></a-word>
                </li>
            </ul>
        </div>
    </section>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['relation', 'words', 'word', 'showType'],
        data()
        {
            return {
                currentPage: {},
                userConfig: HUB.shared.userConfig,
                infoType: {
                    IN: {
                        label: 'Entrant',
                        a_word_show: 'n1'
                    },
                    OUT: {
                        label: 'Sortant',
                        a_word_show: 'n2'
                    }
                }
            }
        },
        created()
        {
            this.initCurrentPage();
            this.$watch('relation',function(){ this.initCurrentPage() })
        },
        computed: {
            wordPerPage()
            {
                return HUB.shared.userConfig.show.wordPerPage;
            },
            relationName()
            {
                var rel = this.relation.infos;

                if (rel.nom_etendu)
                    return rel.nom_etendu + ' <small>' + rel.name + '</small>';

                return rel.name;
            },
            relationsPaginate()
            {
                var ret = {};

                for (var type of this.showType) {
                    var cpage = this.currentPage[type];
                    var perPage = this.wordPerPage;

                    if (cpage === null) {
                        ret[type] = null;
                        continue;
                    }
                    var relation = this.relation[type];
                    var rlen = relation.data.length;
                    var offset = (cpage - 1) * perPage;
                    var end = offset + perPage;

                    if (offset < 0 || offset >= rlen) {
                        ret[type] = null;
                        continue;
                    }

                    if (end >= rlen)
                        end = rlen;

                    ret[type] = relation.data.slice(offset, end);
                }
                return ret;
            }
        },
        components: {
            'a-word': require('./a_word.vue'),
            'show-count': require('./show_count.vue'),
            'pagination': require('./pagination/pagination.vue'),
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changeWord(word)
            {
                this.$emit('changeWord', word);
            },
            initCurrentPage()
            {
                var ret = {};

                for (var type of this.showType) {
                    ret[type] = 1;
                }
                this.currentPage = ret;
            },
        }
    }
</script>