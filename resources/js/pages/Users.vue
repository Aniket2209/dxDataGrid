<template>
    <div>
        <h1>Fake User Details</h1>
        <Button @click="exportAllToExcel" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-400">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                d="M16 16l4-4m0 0l-4-4m4 4H7m6 4v1m-3-1V4" />
            </svg>
            Export All to Excel
        </Button>
        <UserGrid />
    </div>
</template>

<script setup>
    import UserGrid from '@/components/UserGrid.vue'
    function exportAllToExcel(e)
    {
        window.open('/api/export-users-excel', '_blank');
    }
</script>