<template>
    <div>
        <span v-if="word">
            <a href="#" @click="changeWord(word.n)" @click.prevent="onEventPrevent">{{word.nf}}</a>
        </span>
        <span v-else>
            {{relation._id}}
        </span>
        <span v-if="userConfig.show.weight" class="badge">{{ relation.w }}</span>
        <span v-else> - </span>
    </div>
</template>
<script>
    import { HUB } from '../vue/data.js';

    export default{
        props: ['relation', 'words', 'show'],
        data()
        {
            return {
                userConfig: HUB.shared.userConfig
            }
        },
        computed: {
            word()
            {
                if (this.words[this.relation[this.show]]) {
                    return this.words[this.relation[this.show]];
                }
                return null;
            }
        },
        created()
        {
            HUB.askForWord(this.relation[this.show]);
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changeWord(word)
            {
                this.$emit('changeWord',word);
//                HUB.$data.shared.app = Object.assign({}, HUB.$data.shared.app, {data: {word: word}})
            },
        }
    };
</script>