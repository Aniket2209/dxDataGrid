<template>
  <DxDataGrid
    :data-source="dataSource"
    :paging="{ pageSize: pageSize, enabled: true }"
    :pager="{
      showPageSizeSelector: true,
      allowedPageSizes: [5, 10, 20, 50],
      showInfo: true
    }"
    :remote-operations="true"
    :sorting="{ mode: 'multiple' }"
    :filter-row="{ visible: true }" 
    :editing="{
      mode: 'row',
      allowAdding: true,
      allowDeleting: true
    }"
    @row-inserting="onRowInserting"
    @row-removing="onRowRemoving"
    show-borders
    :no-data-text="'No Data there to show!'"
  >
    <DxColumn data-field="id" caption="ID" width="80" alignment="center" />
    <DxColumn data-field="name" caption="Name" />
    <DxColumn data-field="email" caption="Email" width="350" />
    <DxColumn data-field="email_verified_at" caption="Email Verified At" data-type="date" format="dd-MM-yyyy" width="160" />
    <DxColumn data-field="created_at" caption="Registered On..." data-type="date" format="dd-MM-yyyy" width="140" />
  </DxDataGrid>
</template>

<script setup>
import { DxDataGrid, DxColumn } from 'devextreme-vue/data-grid';
import CustomStore from 'devextreme/data/custom_store';
import { ref } from 'vue';

const pageSize = ref(20);
const BASE_URL = 'http://localhost:8000/users-json';

const dataSource = new CustomStore({
  key: 'id',
  load: (loadOptions) => {
    const params = new URLSearchParams();
    params.append('skip', loadOptions.skip || 0);
    params.append('take', loadOptions.take || pageSize.value);
    if (loadOptions.sort) {
      params.append('sort', JSON.stringify(loadOptions.sort));
    }
    if (loadOptions.filter) {
      params.append('filter', JSON.stringify(loadOptions.filter));
    }
    return fetch(`${BASE_URL}?${params.toString()}`)
      .then(res => {
        if (!res.ok) throw new Error('Failed to load data');
        return res.json();
      })
      .then(data => ({
        data: data.data,
        totalCount: data.totalCount,
      }));
  }
});

async function sendRequest(url, method, data) {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    const err = await res.json();
    throw new Error(err.message || 'Request failed');
  }
  return res.json();
}

async function onRowInserting(e) {
  try {
    await sendRequest(BASE_URL, 'POST', e.data);
    dataSource.reload();
  }
  catch (error) {
    alert(`Add failed: ${error.message}`);
  }
}

async function onRowRemoving(e) {
  try {
    const id = e.key;
    await sendRequest(`${BASE_URL}/${id}`, 'DELETE');
    dataSource.reload();
  }
  catch (error) {
    alert(`Delele failed: ${error.message}`);
  }
}

</script>
