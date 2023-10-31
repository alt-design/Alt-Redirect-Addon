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
            content: JSON.parse(document.getElementById('alt-redirect-app').getAttribute('data-content')),
        };
    },
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
                <th class="actions-column"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in items" :key="item.id">
                <td>
                {{ item.from }}
                </td>
                <td>
                    {{ item.to }}
                </td>
                <td><button class="btn" style="color: #bc2626;">Remove</button></td>
            </tr>
            </tbody>
        </table>
    </div>
    </div>
</template>

<style scoped>

</style>
