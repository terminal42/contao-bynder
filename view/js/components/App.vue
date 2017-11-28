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
                <image-row :image="image" :mode="mode" v-for="image in images"></image-row>
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
            preSelected: {
                type: Array,
                required: true,
            },
        },

        components: { FilterPanel, ImageRow },

        data() {
            return {
                metaproperties: {},
                pagination: {},
                images: [],
                loading: false,
                lastQueryString: '',
                imageQuery: {
                    filters: {},
                    keywords: '',
                }
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

                this.imageQuery.filters = filters;
                this.imageQuery.keywords = keywords;
                this.pagination.currentPage = 1;

                this.updateImages();
            },

            resetFilter() {
                this.imageQuery.filters = {};
                this.imageQuery.keywords = '';
                this.pagination.currentPage = 1;

                this.updateImages();
            },

            updateImages() {
                if (this.loading) {
                    return;
                }

                if (undefined === this.pagination.currentPage) {
                    this.pagination.currentPage = 1;
                }

                let queryString =  {
                    preSelected: this.preSelected.join(','),
                    page: this.pagination.currentPage
                };
                let optionIds = [];

                if ('' !== this.imageQuery.keywords) {
                    queryString['keyword'] = this.imageQuery.keywords;
                }

                Object.keys(this.imageQuery.filters).forEach((property) => {
                    let optionId = this.imageQuery.filters[property];

                    if ('' !== optionId) {
                        optionIds.push(optionId);
                    }
                });

                if (optionIds.length) {
                    queryString['propertyOptionId'] = optionIds.join(',');
                }

                queryString = this.buildQueryString(queryString);

                if (this.lastQueryString === queryString) {
                    return;
                }

                this.loading = true;
                this.lastQueryString = queryString;

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

            paginationUpdated() {
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
