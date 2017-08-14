<template>
    <div class="tl_left" style="padding-left:40px"> {{ image.name }} <span class="tl_gray">{{ image.meta }}</span><br>
        <img
                :src="image.thumb.src"
                :width="image.thumb.width"
                :height="image.thumb.height"
                :alt="image.thumb.alt"
                style="margin:5px 0 2px -19px"
        >
        <div><span class="tl_gray">Media ID: {{ image.bynder_id }}</span></div>
    </div>
</template>

<script>
    export default {
        props: {
            image: {
                type: Object,
                required: true,
            },
        },
    };
</script>
