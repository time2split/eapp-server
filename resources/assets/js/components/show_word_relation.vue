<template>
    <section v-if="showSection" class="show-relation">
        <h1><a href="#" @click="changeRelation(rdata.name)" @click.prevent="onEventPrevent">{{ relationName }}</a></h1>

        <div v-for="type in showType">
            <div v-if="relations[type].data.length">
                <h2>{{ infoType[type].label }} : <show-count :nb="relations[type].nb" :count="relations[type].count"></show-count></h2>
                <ul class="list-inline">
                    <li class="list-inline-item" v-for="rel in relations[type].data">
                    <a-word @changeWord="changeWord" :relation="rel" :words="words" :show="infoType[type].a_word_show" ></a-word>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['rdata', 'relations', 'words', 'word', 'showType'],
        data()
        {
            return {
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
        computed: {
            relationName()
            {
                return this.rdata.nom_etendu === '' ? this.rdata.name : this.rdata.nom_etendu;
            },
            isEmpty()
            {
                for (var type of this.showType) {

                    if (this.relations[type].data.length != 0)
                        return false;
                }
                return true;
            },
            showSection()
            {
                return (this.isEmpty && this.userConfig.show.empty === true)
                        || (!this.isEmpty && this.userConfig.show.noempty === true);
            }
        },
        components: {
            'a-word': require('./a_word.vue'),
            'show-count': require('./show_count.vue'),
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changeRelation(relation)
            {
                this.$emit('changeRelation', relation);
//                HUB.$data.shared.app = Object.assign({}, HUB.$data.shared.app, {data: {word: HUB.$data.shared.app.data.word, relation: relation}})
            },
            changeWord(word)
            {
                this.$emit('changeWord', word);
            }
        }
    }
</script>