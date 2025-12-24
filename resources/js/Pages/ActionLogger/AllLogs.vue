<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Layout from './Layout.vue'
import PageHeader from '@/Components/ActionLogger/PageHeader.vue'
import FiltersBar from '@/Components/ActionLogger/FiltersBar.vue'
import LogsTable from '@/Components/ActionLogger/LogsTable.vue'

interface Props {
  filters: Record<string, any>
  logs: {
    data: any[]
    links?: any
    meta?: any
  }
  modules: Record<string, any>
  events: string[]
}

defineProps<Props>()

const handleFilter = (newFilters: any) => {
  router.get(route('action-logger.all'), newFilters, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <Layout :modules="modules">
    <PageHeader
      title="All Logs"
      subtitle="Complete history of all action logs"
    />

    <FiltersBar
      :events="events"
      :modules="modules"
      @filter="handleFilter"
    />

    <LogsTable :logs="logs" />
  </Layout>
</template>
