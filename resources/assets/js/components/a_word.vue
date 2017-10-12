<template>
    <div>
        <span v-if="word">
            <a href="#" @click="changeWord(word.n,$event)">
                {{word.n}}
            </a>
        </span>
        <span v-else>
            {{relation._id}}
        </span>
        <span class="badge">{{ relation.w }}</span>
    </div>
</template>
<script>
    import { HUB } from '../vue/data.js';

    export default{
        props: ['relation', 'words'],
        computed: {
            word: function () {

                if (this.words[this.relation.n2]) {
                    return this.words[this.relation.n2];
                }
                return null;
            }
        },
        created: function () {
            HUB.askForWord(this.relation.n2);
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