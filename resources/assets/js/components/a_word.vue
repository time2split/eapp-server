<template>
    <div>
        <span v-if="word">
            <a :href="'/' + word.n" @click="changeWord(word.n,$event)">
                {{word.n}}
        </a>
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
        data() {
            return {
                userConfig: HUB.shared.userConfig
            }
        },
        computed: {
            word: function () {

                if (this.words[this.relation[this.show]]) {
                    return this.words[this.relation[this.show]];
                }
                return null;
            }
        },
        created: function () {
            HUB.askForWord(this.relation[this.show]);
        },
        methods: {
            changeWord(word, e)
            {
                e.preventDefault();
                HUB.changeWord(word);
            }
        }
    };
</script>