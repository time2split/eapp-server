<template>
    <span v-if="count !== null">
        <span v-if="count == nb">
            {{nb}}
        </span>
        <span v-else>
            <span v-if="nb">{{ nb }}/</span>{{ count }}
        </span>
    </span>
    <span v-else-if="nb !== null">
        {{ nb }}
    </span>
</template>
<script>
    export default {
        props: ['nb', 'count']
    }
</script>
