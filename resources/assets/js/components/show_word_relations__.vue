<template>
    <div>
        <div v-for="relation in relationTypes">
            <show-word-relation :relation="relation" :word="word"></show-word-relation>
        </div>
    </div>
</template>
<script>
    export default{
        props: ['word', 'relationTypes'],
        components: {
            'show-word-relation': require('./show_word_relation.vue')
        }
    }
</script>