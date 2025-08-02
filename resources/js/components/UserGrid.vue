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
    return fetch('http://localhost:8000/users-json?' + params.toString())
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
</script>
