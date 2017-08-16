<template>
    <div>
        <div class="tl_panel cf">
            <div class="tl_search tl_subpanel">
                <strong>{{ labels.search }}:</strong>
                <select name="tl_field" :class="{ tl_select: true, active: keywords !== '' }">
                    <option value="keywords">{{ labels.keywords }}</option>
                </select>
                <span>=</span>
                <input type="search" name="tl_value" :class="{ tl_text: true, active: keywords !== '' }" v-model="keywords" @keyup="applyFiltersDebounced()">
            </div>
        </div>
        <div v-if="hasFilters()" class="tl_panel cf">
            <div class="tl_filter tl_subpanel">
                <strong>{{ labels.filter }}:</strong>
                <select v-model="filterData[property]" v-for="(options, property) in filters" :name="property" :class="{ tl_select: true, active: isFilterActive(property)}" @change="applyFilters()">
                    <option v-for="option in options" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>
        <div class="tl_panel cf">
            <div class="tl_submit_panel tl_subpanel" style="min-width:0">
                <button name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="" @click="resetFilters">{{ labels.reset }}</button>
            </div>
            <pagination-drop-down :data="pagination" :labels="labels" @apply="updatePagination"></pagination-drop-down>
        </div>
    </div>
</template>

<script>
    import debounce from 'lodash.debounce';
    import PaginationDropDown from './PaginationDropDown.vue';

    export default {
        props: {
            metaproperties: {
                type: Object,
                required: true,
            },
            labels: {
                type: Object,
                required: true,
            },
            pagination: {
                type: Object,
                required: true,
            },
        },

        components: { PaginationDropDown },

        data() {
            return {
                filterData: {},
                keywords: '',
            }
        },

        computed: {
            filters() {
                let filters = {};

                Object.keys(this.metaproperties).forEach((property) => {
                   let filterDef = this.metaproperties[property];
                   // Currently, only single selects are supported.
                   if ('select' === filterDef.type || 'select2' === filterDef.type) {
                       filters[property] = [];

                       // Add label and reset options first
                       filters[property].push({
                           label: filterDef.label,
                           value: ''
                       });
                       filters[property].push({
                           label: '---',
                           value: ''
                       });

                       filterDef.options.forEach((option) => {

                           // No need to show empty filter options
                           if (0 === option.mediaCount) {
                               return;
                           }

                           filters[property].push({
                               label: option.displayLabel, // TODO maybe use "labels"?
                               value: option.id,
                           })
                       });

                       // If length is equal to 3 (label, reset plus 1 option only)
                       // We do not show the filter either because filtering for
                       // only one available option is not really helpful either,
                       // is it?
                       if (filters[property].length === 3) {
                           delete filters[property];
                       }

                       // Set default selected option
                       this.filterData[property] = '';
                   }
                });

                return filters;
            }
        },

        methods: {

            hasFilters() {
                return Object.keys(this.filters).length !== 0;
            },

            applyFiltersDebounced: debounce(function() {
                this.applyFilters();
            }, 500),

            applyFilters() {
                this.$forceUpdate();

                if (this.isAtLeastOneFilterOrKeywordsActive()) {
                    this.$emit('apply', this.filterData, this.keywords);
                } else {
                    this.$emit('reset');
                }
            },

            isFilterActive(property) {
                return  '' !== this.filterData[property];
            },

            isAtLeastOneFilterOrKeywordsActive() {
                let hasFilters = false;

                Object.keys(this.filters).forEach((property) => {
                    if (!hasFilters && this.isFilterActive(property)) {
                        hasFilters = true;
                    }
                });

                return '' !== this.keywords || hasFilters;
            },

            resetFilters() {
                Object.keys(this.filters).forEach((property) => {
                    // Set default selected option
                    this.filterData[property] = '';
                });
                this.keywords = '';

                this.$emit('reset');
            },

            updatePagination(page) {
                this.$emit('paginationUpdated', page);
            }
        },
    };
</script>
