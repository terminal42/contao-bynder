<template>
    <div id="bynder-asset-mgmt">
        <filter-panel
                :metaproperties="metaproperties"
                :labels="labels"
                :pagination="pagination"
                @apply="applyFilter"
                @reset="resetFilter"
                @paginationUpdated="paginationUpdated">
        </filter-panel>
        <div class="tl_listing_container tree_view" id="tl_listing">
            <div v-if="loading" class="loader">{{ labels.loadingData }}</div>
            <div v-else-if="!hasImages()">{{ labels.noResult }}</div>
            <ul v-else class="tl_listing picker unselectable" id="tl_select">
                <li class="tl_folder_top cf"><div class="tl_left"><img src="bundles/terminal42contaobynder/bynder-logo.svg" width="18" height="18" alt=""> Bynder Asset Management</div></li>
                <li class="tl_file click2edit toggle_select hover-div" v-for="image in images">
                    <image-row :image="image" :mode="mode"></image-row>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    import FilterPanel from './FilterPanel.vue';
    import ImageRow from './ImageRow.vue';

    export default {
        props: {
            mode: {
                type: String,
                required: true,
            },
            labels: {
                type: Object,
                required: true,
            },
        },

        components: { FilterPanel, ImageRow },

        data() {
            return {
                metaproperties: {},
                pagination: {},
                currentPage: 1,
                images: [],
                loading: false,
            }
        },

        created() {

            this.$http.get('/_bynder_api/metaproperties').then(
                (data) => {
                    this.metaproperties = data.body;
                }
            );

            this.updateImages();
        },

        methods: {

            hasImages() {
                return this.images.length !== 0;
            },

            applyFilter(filters, keywords) {

                let optionIds = [];
                let queryString = {};

                if ('' !== keywords) {
                    queryString['keyword'] = keywords;
                }

                Object.keys(filters).forEach((property) => {
                    let optionId = filters[property];

                    if ('' !== optionId) {
                        optionIds.push(optionId);
                    }
                });

                if (optionIds.length) {
                    queryString['propertyOptionId'] = optionIds.join(',');
                }

                this.updateImages(queryString);
            },

            resetFilter() {
                this.updateImages();
            },

            updateImages(queryString) {

                queryString = queryString || {};

                Object.assign(queryString, {page: this.currentPage});

                queryString = this.buildQueryString(queryString);
                this.loading = true;

                let uri = '/_bynder_api/images' + (('' !== queryString) ? ('?' + queryString) : '');

                this.$http.get(uri).then(
                    (data) => {
                        this.images = data.body.images;
                        this.pagination = data.body.pagination;
                        this.loading = false;
                    }
                ).catch(() => {
                    this.loading = false;
                });
            },

            paginationUpdated(page) {
                this.currentPage = page;
                this.updateImages();
            },

            buildQueryString(data) {
                return Object.keys(data).map(function(key) {
                    return [key, data[key]].map(encodeURIComponent).join('=');
                }).join('&');
            }
        }
    }
</script>
