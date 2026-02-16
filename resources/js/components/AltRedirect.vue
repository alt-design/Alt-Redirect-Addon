<script setup>
import { Header, Heading, Subheading, PublishContainer, Button, Switch, Card, Input, Pagination } from '@statamic/cms/ui';
import { Pipeline, BeforeSaveHooks, Request, AfterSaveHooks } from '@statamic/cms/save-pipeline'; 
import { router } from '@statamic/cms/inertia'
import { computed, ref } from 'vue'; 

const props = defineProps({
    title: String,
    instructions: String,
    action: String,
    blueprint: Array,
    initialMeta: Array,
    initialValues: Array,
    items: Array,
    type: String
});

const perPage = 10;
const currentPage = ref(1);
const selectedFile = ref(null);
const search = ref('');
const values = ref({...props.initialValues});
const meta = props.initialMeta;
const errors = ref({});
const saving = ref(false);
const container = ref('container');

const lastPage = computed(() => {
    return Math.ceil(props.items.length / perPage);
});

const itemsSliced = computed(() => {
    let temp = props.items;

    if (search.value?.length > 0) {
        temp = temp.filter(item => {
            // Convert all values to string and lower case for case-insensitive comparison
            let tempArr = Object.values(item).map(value => value?.toString().toLowerCase());
            // Check if any value includes the search string
            return tempArr.some(value => value?.includes(search.value.toLowerCase()));
        });
    }

    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;

    return temp.slice(start, end);
});

function setPage(page) {
    // If the page we were looking at has now been removed
    if(page > lastPage.value) {
        page = lastPage.value;
    }
    currentPage.value = page
}


function deleteRedirect(from, id) {
    if (confirm('Are you sure you want to delete this redirect?')) {
        router.post(cp_url('alt-design/alt-redirect/delete'), {
            from: from,
            id: id
        }, {preserveState: true, preserveScroll: true, onSuccess: () => {
            Statamic.$toast.success("Redirect deleted successfully!")
            setPage(currentPage.value);
        }});
    }
}

function deleteQueryString(query_string) {
    if (confirm('Are you sure you want to delete this query string?')) {
        router.post(cp_url('/alt-design/alt-redirect/query-strings/delete'), {
            query_string: query_string,
        }, {preserveState: true, preserveScroll: true, onSuccess: () => {
            Statamic.$toast.success("Query string deleted successfully!")
            setPage(currentPage.value);
        }});
    }
}

function importFromCSV() {
    if (!selectedFile.value) {
        alert("You haven't attached a CSV file!");
        return;
    }

    router.post(cp_url('alt-design/alt-redirect/import'), {file: selectedFile.value}, {
        preserveState: true, preserveScroll: true, onSuccess: () => {
        Statamic.$toast.success("CSV imported successfully!")
        setPage(currentPage.value);
    }});
}

function toggleKey(index, toggleKey) {
    router.post(cp_url('/alt-design/alt-redirect/query-strings/toggle'), {
        index: index,
        toggleKey: toggleKey,
    }, {preserveState: true, preserveScroll: true, onSuccess: () => {
        Statamic.$toast.success("Toggled successfully!")
        setPage(currentPage.value);
    }});
}

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new BeforeSaveHooks('alt-redirect'),
            new Request(cp_url('/alt-design/alt-redirect'), 'POST', { type: props.type }),
            new AfterSaveHooks('alt-redirect'),
        ])
        .then(() => {
            values.value = {...props.initialValues}
            Statamic.$toast.success("Redirect added successfully!")
            router.reload()
        });
}

</script>

<template>
    <div id="alt-redirect">
        <Header :title="title">
            <template #title>
                <div>
                    {{ title }}
                    <div class="text-sm">{{ instructions }}</div>
                </div>
            </template>
            <Button text="Save" variant="primary" :disabled="saving" @click="save" /> 
        </Header>

        <PublishContainer ref="container" :action="action" :blueprint="blueprint" :meta="meta" v-model="values" :errors="errors" />

        <Card class="overflow-hidden p-0">
            <div class="my-4 pb-2 px-4">
                <Input v-model="search" placeholder="Search" />
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
                                <Button icon="trash" size="sm" @click="deleteRedirect(item.from, item.id)" text="Remove" variant="danger" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table v-else-if="type == 'query-strings'" data-size="sm" tabindex="0" class="data-table" style="table-layout: fixed">
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
                            <Switch v-model="item.strip" v-on:update:model-value="toggleKey( item.query_string, 'strip' )" />
                        </td>
                        <td>
                            {{ (item.sites && item.sites.length ) ? item.sites.join(', ') : "Unknown" }}
                        </td>
                        <td>
                            <Button icon="trash" size="sm" @click="deleteQueryString(item.query_string)" text="Remove" variant="danger" />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :resource-meta="{
                current_page: currentPage,
                last_page: lastPage,
                total: items.length,
            }" :show-totals="false" :show-per-page-selector="false" @page-selected="setPage" />
        </Card>
        <div class="flex justify-between" :class="{ hidden: type == 'query-strings' }">
            <Card class="w-full xl:w-1/2 card overflow-hidden p-0 mb-4 mt-4 mr-4 px-4 py-4">
                <header>
                    <Heading>CSV Export</Heading>
                    <Subheading>Exports CSV of all redirects, use this format on import.</Subheading>
                </header>
                <a class="btn-primary" :href="cp_url('/alt-design/alt-redirect/export')" download>
                    <Button text="Export CSV"/>
                </a>
            </Card>

            <Card class="w-full xl:w-1/2 card overflow-hidden p-0 mb-4 mt-4 ml-4 px-4 py-4">
                <header>
                    <Heading>CSV Import</Heading>
                    <Subheading>Import CSV for redirects, use the export format on import.</Subheading>
                </header>

                <div class="flex justify-between items-center">
                    <Input type="file" @input="selectedFile = $event.target.files[0]" />
                    <Button @click="importFromCSV()" text="Import" />
                </div>
            </Card>
        </div>
    </div>
</template>

<style scoped></style>
