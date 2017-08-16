<template>
    <div class="tl_limit tl_subpanel">
        <strong>{{ labels.showOnly }}:</strong>
        <select v-model="data.currentPage" name="tl_limit" :class="{'tl_select': true, 'active': data.currentPage !== 1 }" @change="apply()">
            <option v-for="option in getOptions()" :value="option.value">{{ option.label }}</option>
        </select>
    </div>
</template>


<script>
    export default {
        props: {
            data: {
                type: Object,
                required: true,
            },
            labels: {
                type: Object,
                required: true,
            },
        },

        methods: {

            apply() {
                this.$emit('apply');
            },

            getOptions() {
                let options = [];
                for (let i = 0; i < this.data.totalPages; i++) {
                    options.push({
                        value: i + 1,
                        label: this.getFrom(i) + ' - ' + this.getTo(i)
                    });
                }

                return options;
            },

            getFrom(i) {
                if (0 === i) {
                    return 1;
                }

                return i * this.data.perPage;
            },

            getTo(i) {
                i++;
                return Math.min(i * this.data.perPage, this.data.totalImages);
            }
        }
    }
</script>
