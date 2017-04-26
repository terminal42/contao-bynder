<template>
    <div class="tl_left" style="padding-left:40px"> {{ name }} <span class="tl_gray">({{ meta }})</span><br>
        <img
                :src="thumb.src"
                :width="thumb.width"
                :height="thumb.height"
                :alt="thumb.alt"
                style="margin:0 0 2px -19px"
        >
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String,
                required: true,
            },
            meta: {
                type: String,
                required: true,
            },
            thumb: {
                type: Object,
                required: true,
            },
        },
    };
</script>
