<template>
    <div>
        <thumbnail :name="image.name" :meta="image.meta" :thumb="image.thumb"></thumbnail>
        <div class="tl_right download-button">
            <div class="button ld ld-over-inverse">
                <div class="ld ld-ring ld-spin"></div>
                <div></div>
                <img src="bundles/terminal42contaobynder/download.svg" width="20">
            </div>
            <radio v-if="mode == 'radio'" name="picker" :value="image.value" :checked="image.selected" :disabled="disabled"></radio>
            <checkbox v-else name="picker[]" :value="image.uuid" :checked="image.selected" :disabled="disabled"></checkbox>
        </div>
        <div style="clear:both"></div>
    </div>
</template>

<script>
    import Thumbnail from './Thumbnail.vue';
    import Radio from './Radio.vue';
    import Checkbox from './Checkbox.vue';
    export default {
        props: {
            mode: {
                type: String,
                required: true,
            },
            image: {
                type: Object,
                required: true,
            },
        },
        computed: {
            disabled() {
                return !this.image.downloaded;
            }
        },

        components: { Thumbnail, Radio, Checkbox },
    }
</script>
