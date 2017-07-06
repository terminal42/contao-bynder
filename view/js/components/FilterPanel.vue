<template>
    <div v-if="hasFilters()">
        <div class="tl_submit_panel tl_subpanel">
            <button name="filter" id="filter" class="tl_img_submit filter_apply" title="">Apply</button>
            <button name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="">Reset</button>
        </div>
        <div class="tl_filter tl_subpanel">
            <strong>Filter:</strong>
            <select v-for="(options, property) in filters" :name="property" class="tl_select">
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
                           value: property
                       });
                       filters[property].push({
                           label: '---',
                           value: property
                       });

                       filterDef.options.forEach((option) => {
                           filters[property].push({
                               label: option.displayLabel, // TODO maybe use "labels"?
                               value: option.id
                           })
                       });
                   }
                });

                return filters;
            }
        },

        methods: {
            hasFilters() {
                return Object.keys(this.filters).length !== 0;
            },

            changed(values) {
                this.$emit('filtersChanged', values);
            },
        },
    };
</script>
