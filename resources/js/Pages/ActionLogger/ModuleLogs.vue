<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Layout from './Layout.vue'
import PageHeader from '@/Components/ActionLogger/PageHeader.vue'
import StatsCard from '@/Components/ActionLogger/StatsCard.vue'
import FiltersBar from '@/Components/ActionLogger/FiltersBar.vue'
import LogsTable from '@/Components/ActionLogger/LogsTable.vue'

interface Props {
  module: {
    key: string
    label: string
    description: string
    icon: string
    subject_type: string
  }
  moduleStats: {
    total: number
    last_24h: number
    last_7_days: number
  }
  logs: {
    data: any[]
    links?: any
    meta?: any
  }
  filters: Record<string, any>
  events: string[]
}

const props = defineProps<Props>()

const handleFilter = (newFilters: any) => {
  router.get(route('action-logger.modules.show', props.module.key), newFilters, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <Layout>
    <PageHeader
      :title="module.label"
      :subtitle="module.description"
    />

    <!-- Module Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <StatsCard
        label="Total Logs"
        :value="moduleStats.total.toLocaleString()"
      />
      <StatsCard
        label="Last 24 Hours"
        :value="moduleStats.last_24h.toLocaleString()"
      />
      <StatsCard
        label="Last 7 Days"
        :value="moduleStats.last_7_days.toLocaleString()"
      />
    </div>

    <FiltersBar
      :events="events"
      @filter="handleFilter"
    />

    <LogsTable :logs="logs" />
  </Layout>
</template>
