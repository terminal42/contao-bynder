<template>
    <div>
        <div class="tl_panel cf"></div>
        <div class="tl_listing_container tree_view" id="tl_listing">
            <ul class="tl_listing picker unselectable" id="tl_select">
                <li class="tl_folder_top cf"><div class="tl_left"><img src="bundles/contaobynder/bynder-logo.svg" width="18" height="18" alt=""> Bynder Asset Management</div></li>
                <li class="tl_file click2edit toggle_select hover-div" v-for="image in images">
                    <thumbnail :name="image.name" :meta="image.meta" :thumb="image.thumb"></thumbnail>
                    <div class="tl_right">
                        <radio v-if="mode == 'radio'" :name="name" :value="image.uuid" :checked="image.selected"></radio>
                        <checkbox v-else :name="name" :value="image.uuid" :checked="image.selected"></checkbox>
                    </div>
                    <div style="clear:both"></div>
                </li>
            </ul>
        </div>
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
            name: {
                type: String,
                required: true,
            },
        },
        components: { Thumbnail, Radio, Checkbox },
        data () {
            return {
                images: [
                    {
                        'uuid': 'bynder-asset:i-am-such-a-safe-uuid',
                        'selected': false,
                        'name': 'Special image',
                        'meta': '200x300 foobar',
                        'thumb': {
                            'src': 'foobar.com',
                            'width': 400,
                            'height': 200,
                            'alt': 'Foobar',
                        }
                    },
                    {
                        'uuid': 'bynder-asset:i-am-such-a-safe-uuid-2',
                        'selected': false,
                        'name': 'Special image',
                        'meta': '200x300 foobar',
                        'thumb': {
                            'src': 'foobar.com',
                            'width': 200,
                            'height': 150,
                            'alt': 'Foobar',
                        }
                    },
                ]
            }
        },

        created() {
            this.$http.get('/_bynder_api/images', function() {
                console.log(arguments);
            })
        }
    }
</script>
