<template>
    <!-- @todo: Use a paginated selector eg. https://terryz.github.io/vue/#/selectpage -->

    <select class="form-control" @change="onSelected" data-field="-- Please select a user">
        <option value="">-- Please select a user</option>
        <option v-for="user in users" :value="user.id" v-html="user.email"/>
    </select>
</template>
<script>
export default {
    data() {
        return {
            users: []
        }
    },
    methods: {
        fetchFromBackend() {
            let that = this

            this.$authBackend().findUsers().then(function (response) {
                that.users = response.users
            })
        },

        onSelected(value) {
            this.$emit('selected', value.target.value)
        }
    },
    mounted() {
        this.fetchFromBackend()
    }
}
</script>
