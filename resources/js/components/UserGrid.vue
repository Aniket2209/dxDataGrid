<template>
  <DxDataGrid
    :data-source="dataSource"
    :remote-operations="true"
    :paging="{ pageSize: 20 }"
    show-borders
  >
    <DxColumn data-field="id" caption="ID" />
    <DxColumn data-field="name" caption="Name" />
    <DxColumn data-field="email" caption="Email" />
    <DxColumn data-field="email_verified_at" caption="Email Verified At" data-type="date" />
    <DxColumn data-field="created_at" caption="Created At" data-type="date" />
  </DxDataGrid>
</template>

<script setup>
import { DxDataGrid, DxColumn } from 'devextreme-vue/data-grid'

const dataSource = {
  load: () => {
    return fetch('http://localhost:8000/users-json')
      .then(res => res.json())
      .then(data => ({
        data: data.data,
        totalCount: data.totalCount
      }));
  }
}
</script>
