<template>
    <section class="show-relation">
        <h1>
            <span v-html="relationName"></span>
            (<show-count :nb="relation.ALL.nb" :count="relation.ALL.count"></show-count>)
        </h1>
        <div v-for="type in showType">
            <h2>{{ infoType[type].label }} : <show-count v-bind:nb="relation[type].nb" v-bind:count="relation[type].count"></show-count></h2>
            <ul class="list-inline">
                <li class="list-inline-item" v-for="rel in relation[type].data">
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
                var rel = this.relation.infos;
                
                if (rel.nom_etendu)
                    return rel.nom_etendu + ' <small>' + rel.name + '</small>';

                return rel.name;
            },
        },
        components: {
            'a-word': require('./a_word.vue'),
            'show-count': require('./show_count.vue'),
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changeWord(word)
            {
                this.$emit('changeWord', word);
            }
        }
    }
</script>