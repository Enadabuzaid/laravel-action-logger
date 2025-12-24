<script setup lang="ts">
import Layout from './Layout.vue'
import PageHeader from '@/Components/ActionLogger/PageHeader.vue'
import StatsCard from '@/Components/ActionLogger/StatsCard.vue'
import ModuleCard from '@/Components/ActionLogger/ModuleCard.vue'
import LogsTable from '@/Components/ActionLogger/LogsTable.vue'

interface Props {
  stats: {
    total_logs: number
    logs_last_24h: number
    logs_last_7_days: number
    logs_last_30_days: number
    counts_per_event: Record<string, number>
    top_modules: Array<{
      subject_type: string
      module_key: string | null
      label: string
      count: number
    }>
    top_actors: Array<{
      actor_type: string
      actor_id: number
      count: number
    }>
  }
  modules: Record<string, any>
  recentLogs: {
    data: any[]
  }
}

const props = defineProps<Props>()

const modulesList = Object.entries(props.modules).map(([key, module]) => ({
  ...module,
  key,
  count: props.stats.top_modules.find(m => m.module_key === key)?.count || 0
}))
</script>

<template>
  <Layout :modules="modules">
    <PageHeader
      title="Action Logger Dashboard"
      subtitle="Overview of all action logs and activity"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        label="Total Logs"
        :value="stats.total_logs.toLocaleString()"
      />
      <StatsCard
        label="Last 24 Hours"
        :value="stats.logs_last_24h.toLocaleString()"
      />
      <StatsCard
        label="Last 7 Days"
        :value="stats.logs_last_7_days.toLocaleString()"
      />
      <StatsCard
        label="Last 30 Days"
        :value="stats.logs_last_30_days.toLocaleString()"
      />
    </div>

    <!-- Event Distribution -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Event Distribution</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="(count, event) in stats.counts_per_event" :key="event" class="text-center">
          <div class="text-2xl font-semibold text-gray-900">{{ count }}</div>
          <div class="text-sm text-gray-500 capitalize">{{ event }}</div>
        </div>
      </div>
    </div>

    <!-- Modules -->
    <div v-if="modulesList.length > 0" class="mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Modules</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ModuleCard
          v-for="module in modulesList"
          :key="module.key"
          :module="module"
        />
      </div>
    </div>

    <!-- Recent Logs -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
      <LogsTable :logs="recentLogs" />
    </div>
  </Layout>
</template>
