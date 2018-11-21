<template>
    <li class="tl_file click2edit toggle_select hover-div">
        <thumbnail :image="image"></thumbnail>
        <div class="tl_right download-button">
            <div v-if="!image.downloaded" :class="{ 'button': true, 'ld-over-inverse': true, running: this.isDownloading }" @click="downloadImage()">
                <img src="../../img/download.svg" width="20">
                <div class="ld ld-ring ld-cycle"></div>
            </div>
            <radio v-if="mode == 'radio'" name="picker" :value="image.uuid" :checked="image.selected" :disabled="!image.downloaded"></radio>
            <checkbox v-else name="picker[]" :value="image.uuid" :checked="image.selected" :disabled="!image.downloaded"></checkbox>
        </div>
    </li>
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

        components: { Thumbnail, Radio, Checkbox },

        data() {
            return {
                isDownloading: false,
            }
        },

        methods: {
            downloadImage() {
                if (this.image.downloaded || this.isDownloading) {
                    return;
                }

                this.isDownloading = true;

                let uri = '/_bynder_api/download?mediaId=' + this.image.bynder_id;

                this.$http.get(uri).then(
                    (data) => {
                        if ('OK' === data.body.status) {
                            this.image.uuid = data.body.uuid;
                            this.image.downloaded = true;
                        }
                    }
                ).catch(() => {
                    // TODO
                });
            }
        }
    }
</script>
