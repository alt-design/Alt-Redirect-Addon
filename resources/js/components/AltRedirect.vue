<script>

export default({
    props: {
        title: String,
        action: String,
        blueprint: Array,
        meta: Array,
        redirectTo: String,
        values: Array,
        data: Array,
        items: Array,
    },
    data() {
        return {
            itemsReady: [],
            itemsSliced: [],
            perPage: 10,
            currentPage: 1,
            totalItems: 0,
        }
    },
    mounted() {
        this.itemsReady = this.items
        this.totalItems = this.items.length
        this.sliceItems()
        console.log(this.itemsSliced)
    },
    methods: {
        updateItems(res) {
            this.itemsReady = res.data.data
            this.totalItems = res.data.data.length
            this.sliceItems()
            this.$forceUpdate()
        },
        setPage(page) {
            this.currentPage = page
            this.sliceItems()
        },
        sliceItems() {
            const start = (this.currentPage - 1) * this.perPage
            const end = start + this.perPage
            console.log(this.currentPage)
            this.itemsSliced = this.itemsReady.slice(start, end)
        },
        deleteRedirect(from, id) {
            if (confirm('Are you sure you want to delete this redirect?')) {
                Statamic.$axios.post(cp_url('alt-design/alt-redirect/delete'), {
                        from: from,
                        id: id
                    }).then(res => {
                    console.log('done')
                        this.updateItems(res)
                    })
                    .catch(err => {
                        console.log(err)
                    })
            }
        }
    }
})
</script>

<template>
    <div id="alt-redirect">

    <publish-form
        :title="title"
        :action="action"
        :blueprint="blueprint"
        :meta="meta"
        :values="values"
        @saved="updateItems($event)"
    ></publish-form>

    <div class="card overflow-hidden p-0">
        <table data-size="sm" tabindex="0" class="data-table">
            <thead>
            <tr>
                <th class="group from-column sortable-column">
                    <span>From</span>
                </th>
                <th class="group to-column pr-8">
                    <span>To</span>
                </th>
                <th class="group to-column pr-8">
                    <span>Type</span>
                </th>
                <th class="actions-column"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in itemsSliced" :key="item.id">
                <td>
                    {{ item.from }}
                </td>
                <td>
                    {{ item.to }}
                </td>
                <td>
                    {{ item.redirect_type }}
                </td>
                <td><button @click="deleteRedirect(item.from, item.id)" class="btn" style="color: #bc2626;">Remove</button></td>
            </tr>
            </tbody>
        </table>

        <div class="pagination py-2">
            <!-- Generate numbers based on total left -->
            <span class="cursor-pointer py-1 px-2 border mx-1" :class="{'font-bold': n == currentPage}" v-for="n in Math.ceil(totalItems / perPage)" :key="n" @click="setPage(n)">{{ n }}</span>
        </div>
    </div>
    </div>
</template>

<style scoped>

</style>
