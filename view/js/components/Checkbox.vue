<template>
    <input
        ref="input"
        type="checkbox"
        class="tl_tree_checkbox"
        onfocus="Backend.getScrollOffset()"
        :name="name"
        :checked="checked"
        :value="value"
        @change="toggle($event.target.value, $event.target.checked)"
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
            toggle(value, checked) {
                this.$emit('checked', value, checked);
            },
        },
    };
</script>
