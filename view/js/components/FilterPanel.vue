<template>
    <div v-if="hasFilters()">
        <div class="tl_submit_panel tl_subpanel">
            <button name="filter" id="filter" class="tl_img_submit filter_apply" title="" @click="applyFilters">{{ labels.apply }}</button>
            <button name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="" @click="resetFilters">{{ labels.reset }}</button>
        </div>
        <div class="tl_filter tl_subpanel">
            <strong>{{ labels.filter }}:</strong>
            <select v-model="filterData[property]" v-for="(options, property) in filters" :name="property" :class="{ tl_select: true, active: filterData[property] !== '' }">
                <option v-for="option in options" :value="option.value">{{ option.label }}</option>
            </select>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            mediaproperties: {
                type: Object,
                required: true,
            },
            labels: {
                type: Object,
                required: true,
            }
        },

        data() {
            return {
                filterData: {},
            }
        },

        computed: {
            filters() {
                let filters = {};

                Object.keys(this.mediaproperties).forEach((property) => {
                   let filterDef = this.mediaproperties[property];
                   // Currently, only single selects are supported.
                   if ('select' === filterDef.type && 0 === filterDef.isMultiselect) {
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
                           filters[property].push({
                               label: option.displayLabel, // TODO maybe use "labels"?
                               value: option.id
                           })
                       });

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

            applyFilters() {
                this.$forceUpdate();
                this.$emit('apply', this.filterData);
            },

            resetFilters() {
                Object.keys(this.filters).forEach((property) => {
                    // Set default selected option
                    this.filterData[property] = '';
                });
                this.$forceUpdate();
                this.$emit('reset');
            }
        },
    };
</script>
