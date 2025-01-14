<script>
export default ({
    props: {
        title: String,
        instructions: String,
        action: String,
        blueprint: Array,
        meta: Array,
        redirectTo: String,
        values: Array,
        data: Array,
        items: Array,
        type: String,
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
            selectedPage: '',
        }
    },
    watch: {
        search: {
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
                    this.updateItems(res)
                }).catch(err => {
                    console.log(err)
                })
            }
        },
        deleteQueryString(query_string) {
            if (confirm('Are you sure you want to delete this query string?')) {
                Statamic.$axios.post(cp_url('/alt-design/alt-redirect/query-strings/delete'), {
                    query_string: query_string,
                }).then(res => {
                    this.updateItems(res)
                }).catch(err => {
                    console.log(err)
                })
            }
        },
        importFromCSV() {
            if (!this.selectedFile) {
                alert("You haven't attached a CSV file!");
                return;
            }

            var formData = new FormData();
            formData.append('file', this.selectedFile)
            formData.append('data', JSON.stringify(this.itemsReady))
            Statamic.$axios.post(cp_url('alt-design/alt-redirect/import'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(res => {
                location.reload();
                return;
            }).catch(err => {
                console.log(err)
            })
        },
        handleFileUpload(event) {
            this.selectedFile = event.target.files[0];
            this.fileName = this.selectedFile ? this.selectedFile.name : 'Choose a file...';
        },
        dropdownPageChange() {
            this.setPage(this.selectedPage)
        },
        toggleKey(index, toggleKey) {
            Statamic.$axios.post(cp_url('/alt-design/alt-redirect/query-strings/toggle'), {
                index: index,
                toggleKey: toggleKey,
            }).then(res => {
                this.updateItems(res)
            }).catch(err => {
                console.log(err)
            })
        },
    }
})
</script>

<template>
    <div id="alt-redirect">

        <h1 class="flex-1">{{ title }}</h1>
        <h2 class="flex-1">{{ instructions }}</h2>

        <publish-form :title="''" :action="action" :blueprint="blueprint" :meta="meta" :values="values" @saved="updateItems($event)"></publish-form>

        <div class="card overflow-hidden p-0">
            <div class="mt-4 pb-2 px-4">
                <input type="text" class="input-text" v-model="search" placeholder="Search">
            </div>
            <div class="px-2">
                <table v-if="type == 'redirects'" data-size="sm" tabindex="0" class="data-table" style="table-layout: fixed">
                    <thead>
                        <tr>
                            <th class="group from-column sortable-column" style="width:33%">
                                <span>From</span>
                            </th>
                            <th class="group from-column sortable-column pr-8 w-24" style="width:33%">
                                <span>To</span>
                            </th>
                            <th class="group to-column pr-8" style="width:8%">
                                <span>Type</span>
                            </th>
                            <th class="group to-column pr-8" style="width:15%">
                                <span>Sites</span>
                            </th>
                            <th class="actions-column" style="width:13.4%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in itemsSliced" :key="item.id" style="width : 100%; overflow: clip">
                            <td>
                                {{ item.from }}
                            </td>
                            <td>
                                {{ item.to }}
                            </td>
                            <td>
                                {{ item.redirect_type }}
                            </td>
                            <td>
                                {{ (item.sites && item.sites.length ) ? item.sites.join(', ') : "Unknown" }}
                            </td>
                            <td>
                                <button @click="deleteRedirect(item.from, item.id)" class="btn"
                                        style="color: #bc2626;">Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table v-if="type == 'query-strings'" data-size="sm" tabindex="0" class="data-table" style="table-layout: fixed">
                    <thead>
                    <tr>
                        <th class="group from-column sortable-column" style="width:46%">
                            <span>Query String Key</span>
                        </th>
                        <th class="group to-column pr-8" style="width:20%">
                            <span>Strip</span>
                        </th>
                        <th class="group to-column pr-8" style="width:20.6%">
                            <span>Sites</span>
                        </th>
                        <th class="actions-column" style="width:13.4%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in itemsSliced" :key="item.id" style="width : 100%; overflow: clip">
                        <td>
                            {{ item.query_string }}
                        </td>
                        <td>
                            <button @click="toggleKey( item.query_string, 'strip' )" type="button" aria-pressed="false" aria-label="Toggle Button" class="toggle-container" :class="{ on : item.strip }" id="field_preserve">
                                <div class="toggle-slider">
                                    <div tabindex="0" class="toggle-knob">
                                    </div>
                                </div>
                            </button>
                        </td>
                        <td>
                            {{ (item.sites && item.sites.length ) ? item.sites.join(', ') : "Unknown" }}
                        </td>
                        <td>
                            <button @click="deleteQueryString(item.query_string)" class="btn"
                                    style="color: #bc2626;">Remove
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination text-sm py-4 px-4 flex items-center justify-between">
                <div class="w-1/3 flex items-center">
                    Page <span class="font-semibold mx-1" v-html="currentPage"></span> of <span class="mx-1" v-html="lastPage"></span>
                </div>
                <div class="w-1/3 flex items-center justify-center">
                    <span style="height: 15px; margin: 0 15px; width: 12px;" class="cursor-pointer" @click="setPage(currentPage - 1 > 0 ? currentPage - 1 : 1)">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="205" height="205" viewBox="0 0 205 205"><defs><clipPath id="clip-LEFT"><rect width="205" height="205"/></clipPath></defs><g id="LEFT" clip-path="url(#clip-LEFT)"><rect width="205" height="205" fill="#fff"/><path stroke="#2e9fff" fill="#2e9fff" id="Icon_awesome-arrow-left" data-name="Icon awesome-arrow-left" d="M114.961,184.524l-9.91,9.91a10.669,10.669,0,0,1-15.132,0L3.143,107.7a10.669,10.669,0,0,1,0-15.132L89.919,5.794a10.669,10.669,0,0,1,15.132,0l9.91,9.91a10.725,10.725,0,0,1-.179,15.311L60.994,82.259H189.283A10.687,10.687,0,0,1,200,92.972v14.284a10.687,10.687,0,0,1-10.713,10.713H60.994l53.789,51.244A10.648,10.648,0,0,1,114.961,184.524Z" transform="translate(2.004 2.353)"/></g></svg>
                    </span>
                    <!-- First Page -->
                    <span v-if="currentPage > 1" class="cursor-pointer py-1 mx-1"
                          @click="setPage(1)">1</span>
                    <span v-if="currentPage == 1" class="cursor-pointer py-1 mx-1 font-semibold"
                          @click="setPage(1)">1</span>

                    <!-- Ellipsis for Previous Pages -->
                    <span v-if="currentPage > 3">...</span>

                    <!-- Previous Page -->
                    <span v-if="currentPage > 2" class="cursor-pointer py-1 mx-1"
                          @click="setPage(currentPage - 1)">{{ currentPage - 1 }}</span>

                    <!-- Current Page (not shown if it's the first or last page) -->
                    <span v-if="currentPage !== 1 && currentPage !== lastPage"
                          class="cursor-pointer py-1 mx-1 font-semibold">{{ currentPage }}</span>

                    <!-- Next Page -->
                    <span v-if="currentPage < lastPage - 1" class="cursor-pointer py-1 mx-1"
                          @click="setPage(currentPage + 1)">{{ currentPage + 1 }}</span>

                    <!-- Ellipsis for Next Pages -->
                    <span v-if="currentPage < lastPage - 2">...</span>

                    <!-- Last Page -->
                    <span v-if="currentPage < lastPage" class="cursor-pointer py-1 mx-1"
                          @click="setPage(lastPage)">{{ lastPage }}</span>
                    <span v-if="currentPage == lastPage && lastPage != 1"
                          class="cursor-pointer py-1 mx-1 font-semibold"
                          @click="setPage(lastPage)">{{ lastPage }}</span>
                    <span style="height: 15px; margin: 0 15px; width: 12px;" class="cursor-pointer" @click="setPage(currentPage + 1 < lastPage ? currentPage + 1 : lastPage)">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="205" height="205" viewBox="0 0 205 205"><defs><clipPath id="clip-RIGHT"><rect width="205" height="205"/></clipPath></defs><g id="RIGHT" clip-path="url(#clip-RIGHT)"><rect width="205" height="205" fill="#fff"/><path stroke="#2e9fff" fill="#2e9fff" id="Icon_awesome-arrow-left" data-name="Icon awesome-arrow-left" d="M85.032,184.524l9.91,9.91a10.669,10.669,0,0,0,15.132,0L196.85,107.7a10.669,10.669,0,0,0,0-15.132L110.073,5.794a10.669,10.669,0,0,0-15.132,0l-9.91,9.91a10.725,10.725,0,0,0,.179,15.311L139,82.259H10.71A10.687,10.687,0,0,0,0,92.972v14.284A10.687,10.687,0,0,0,10.71,117.969H139L85.21,169.214A10.648,10.648,0,0,0,85.032,184.524Z" transform="translate(2.004 2.353)"/></g></svg>
                    </span>
                </div>
                <div class="w-1/3 flex justify-end">
                    <select v-model="selectedPage" @change="dropdownPageChange" class="w-1/2 text-sm">
                        <option value="" disabled>Select Page</option>
                        <option v-for="n in lastPage" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex justify-between" :class="{ hidden: type == 'query-strings' }">
            <div class="w-full xl:w-1/2 card overflow-hidden p-0 mb-4 mt-4 mr-4 px-4 py-4">
                <span class="font-semibold mb-2">CSV Export</span><br>
                <p class="text-sm mb-4">Exports CSV of all redirects, use this format on import.</p>

                <a class="btn-primary" :href="cp_url('/alt-design/alt-redirect/export')" download>Export CSV</a>
            </div>

            <div class="w-full xl:w-1/2 card overflow-hidden p-0 mb-4 mt-4 ml-4 px-4 py-4">
                <span class="font-semibold mb-2">CSV Import</span><br>
                <p class="text-sm mb-4">Import CSV for redirects, use the export format on import.</p>

                <div class="flex justify-between items-center">
                    <div>
                        <input type="file" id='file-upload' @change="handleFileUpload" class="hidden">
                        <label for="file-upload" class="btn-primary">Upload File</label>
                        <span class="file-upload-cover px-4">{{ fileName }}</span>
                    </div>
                    <button class="btn-primary" @click="importFromCSV()">Import</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
