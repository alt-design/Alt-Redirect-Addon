<script>

export default ({
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
    computed: {
        lastPage() {
            return Math.ceil(this.totalItems / this.perPage);
        }
    },
    data() {
        return {
            itemsReady: [],
            itemsSliced: [],
            perPage: 10,
            currentPage: 1,
            totalItems: 0,
            selectedFile: null,
            search: '',
            fileName: 'Choose a file...',
        }
    },
    watch: {
        search:{
            immediate: true,
            handler() {
                this.sliceItems();
            }
        }
    },
    mounted() {
        this.itemsReady = this.items
        this.totalItems = this.items.length
        this.sliceItems()
    },
    methods: {
        updateItems(res) {
            this.itemsReady = res.data.data
            this.totalItems = res.data.data.length
            this.sliceItems()
            this.$forceUpdate()
        },
        manualUpdateItems(data) {
            this.itemsReady = data
            this.totalItems = data.length
            this.sliceItems()
            this.$forceUpdate()
        },
        setPage(page) {
            this.currentPage = page
            this.sliceItems()
        },
        sliceItems() {
            let temp = this.itemsReady;

            if (this.search) {
                temp = temp.filter(item => {
                    // Convert all values to string and lower case for case-insensitive comparison
                    let tempArr = Object.values(item).map(value => value.toString().toLowerCase());
                    // Check if any value includes the search string
                    return tempArr.some(value => value.includes(this.search.toLowerCase()));
                });
            }

            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            this.totalItems = temp.length;
            this.itemsSliced = temp.slice(start, end);

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
        },
        exportToCSV(data) {
            Statamic.$axios.post(cp_url('alt-design/alt-redirect/export'), {
                data: data,
            }).then(res => {
                console.log(res.data);

                const url = window.URL.createObjectURL(new Blob([res.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'redirects - ' + this.getTimeString() + '.csv');  // Or another filename
                link.setAttribute('target', '_blank');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

            })
                .catch(err => {
                    console.log(err)
                })
        },
        importFromCSV() {
            if (!this.selectedFile) {
                alert("You haven't attached a CSV file!");
                return;
            }
            console.log(this.selectedFile);
            var formData = new FormData();
            formData.append('file', this.selectedFile)
            formData.append('data', JSON.stringify(this.itemsReady))
            Statamic.$axios.post(cp_url('alt-design/alt-redirect/import'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(res => {
                // this.itemsReady = res.data;
                // this.manualUpdateItems(this.itemsReady)
                location.reload();
                return;
            })
                .catch(err => {
                    console.log(err)
                })
        },
        handleFileUpload(event) {
            this.selectedFile = event.target.files[0];
            this.fileName = this.selectedFile  ? this.selectedFile .name : 'Choose a file...';
        },
        getTimeString() {
            let now = new Date();
            let year = now.getFullYear();
            let month = String(now.getMonth() + 1).padStart(2, '0'); // getMonth() returns 0-11
            let day = String(now.getDate()).padStart(2, '0');
            let hours = String(now.getHours()).padStart(2, '0');
            let minutes = String(now.getMinutes()).padStart(2, '0');
            let seconds = String(now.getSeconds()).padStart(2, '0');
            return `${year}/${month}/${day}-${hours}:${minutes}:${seconds}`;
        },
        dropdownPageChange() {
            this.setPage(this.selectedPage)
        },
    }
})
</script>

<template>
    <div id="alt-redirect">

        <publish-form :title="title" :action="action" :blueprint="blueprint" :meta="meta" :values="values"
            @saved="updateItems($event)"></publish-form>

        <div class="card overflow-hidden p-0 mb-4">
            <table data-size="sm" tabindex="0" class="data-table">
                <thead>
                    <tr>
                        <th class="group from-column sortable-column">
                            <span>CSV Export</span>
                        </th>
                        <th class="group to-column pr-8">
                            <span>CSV Import</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="flex items-center">
                                <button class="btn-primary" @click="exportToCSV(itemsReady)">Export CSV</button>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-between items-center">
                                <div>
                                    <input type="file" id='file-upload' @change="handleFileUpload" class="hidden">
                                    <label for="file-upload" class="btn-primary">Upload File</label>
                                    <span class="file-upload-cover px-4">{{ fileName }}</span>
                                </div>
                                <button class="btn-primary" @click="importFromCSV()">Update Redirects</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden p-0">
            <div class="py-2 px-2">
                <input type="text" class="input-text" v-model="search" placeholder="Search">
            </div>
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
                        <td><button @click="deleteRedirect(item.from, item.id)" class="btn"
                                style="color: #bc2626;">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <div class="pagination py-2 flex">
                <div class=" w-1/3"></div>
                <div class=" w-1/3 flex justify-center">
                    <!-- First Page -->
                    <span v-if="currentPage > 1" class="cursor-pointer py-1 px-2 border mx-1" @click="setPage(1)">1</span>
                    <span v-if="currentPage == 1" class="cursor-pointer py-1 px-2 border mx-1 font-bold"
                        @click="setPage(1)">1</span>

                    <!-- Ellipsis for Previous Pages -->
                    <span v-if="currentPage > 3">...</span>

                    <!-- Previous Page -->
                    <span v-if="currentPage > 2" class="cursor-pointer py-1 px-2 border mx-1"
                        @click="setPage(currentPage - 1)">{{ currentPage - 1 }}</span>

                    <!-- Current Page (not shown if it's the first or last page) -->
                    <span v-if="currentPage !== 1 && currentPage !== lastPage"
                        class="cursor-pointer py-1 px-2 border mx-1 font-bold">{{ currentPage }}</span>

                    <!-- Next Page -->
                    <span v-if="currentPage < lastPage - 1" class="cursor-pointer py-1 px-2 border mx-1"
                        @click="setPage(currentPage + 1)">{{ currentPage + 1 }}</span>

                    <!-- Ellipsis for Next Pages -->
                    <span v-if="currentPage < lastPage - 2">...</span>

                    <!-- Last Page -->
                    <span v-if="currentPage < lastPage" class="cursor-pointer py-1 px-2 border mx-1"
                        @click="setPage(lastPage)">{{ lastPage }}</span>
                    <span v-if="currentPage == lastPage && lastPage == 1" class="cursor-pointer py-1 px-2 border mx-1 font-bold"
                        @click="setPage(lastPage)">{{ lastPage }}</span>
                </div>
                <div class="w-1/3 mr-2 flex justify-end">
                    <select v-model="selectedPage" @change="dropdownPageChange" class="w-1/2">
                        <option value="1">Go to page</option>

                        <option v-for="n in lastPage" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
