<template>
    <input
        ref="input"
        type="radio"
        class="tl_tree_radio"
        onfocus="Backend.getScrollOffset()"
        :name="name"
        :value="value"
        :checked="checked"
        @change="toggle($event.target.value)"
    >
</template>

<script>
    export default {
        props: {
            name: {
                type: String,
                required: true,
            },
            value: {
                type: String,
            },
            checked: {
                type: Boolean,
            },
        },
        methods: {
            toggle(value) {
                this.$emit('checked', value);
            },
        },
    };
</script>
