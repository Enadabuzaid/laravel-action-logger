<script setup lang="ts">
import Layout from './Layout.vue'
import PageHeader from '@/Components/ActionLogger/PageHeader.vue'
import EventBadge from '@/Components/ActionLogger/EventBadge.vue'
import LogDiffViewer from '@/Components/ActionLogger/LogDiffViewer.vue'

interface Props {
  subject: {
    id: number | string
    class: string
    class_basename: string
    display: string
    exists: boolean
  }
  logs: {
    data: Array<{
      id: number
      event: string
      event_label: string
      event_color: string
      description: string
      actor: {
        type: string
        id: number
        name: string
      } | null
      changes: Record<string, { old: any; new: any }>
      old_values: Record<string, any>
      new_values: Record<string, any>
      meta: Record<string, any> | null
      created_at: string
      created_at_human: string
    }>
    links?: any
    meta?: any
  }
}

defineProps<Props>()
</script>

<template>
  <Layout>
    <PageHeader
      :title="`Logs for ${subject.class_basename}: ${subject.display}`"
      :subtitle="`Complete activity history for this ${subject.class_basename.toLowerCase()}`"
    />

    <!-- Subject Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <div class="text-sm text-gray-500">Type</div>
          <div class="text-sm font-medium text-gray-900">{{ subject.class_basename }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-500">ID</div>
          <div class="text-sm font-medium text-gray-900">{{ subject.id }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Display Name</div>
          <div class="text-sm font-medium text-gray-900">{{ subject.display }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Status</div>
          <div>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                subject.exists
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ subject.exists ? 'Active' : 'Deleted' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="space-y-4">
      <div
        v-for="log in logs.data"
        :key="log.id"
        class="bg-white rounded-lg border border-gray-200 p-6"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center space-x-3">
            <EventBadge :event="log.event_label" :color="log.event_color" />
            <div>
              <div class="text-sm font-medium text-gray-900">{{ log.description }}</div>
              <div class="text-sm text-gray-500">
                {{ log.created_at }} ({{ log.created_at_human }})
              </div>
            </div>
          </div>
          <div v-if="log.actor" class="text-sm text-gray-500">
            by {{ log.actor.name }}
          </div>
          <div v-else class="text-sm text-gray-400">
            System
          </div>
        </div>

        <!-- Changes -->
        <LogDiffViewer
          :changes="log.changes"
          :old-values="log.old_values"
          :new-values="log.new_values"
        />

        <!-- Metadata -->
        <div v-if="log.meta && Object.keys(log.meta).length > 0" class="mt-4 pt-4 border-t border-gray-200">
          <div class="text-sm font-medium text-gray-700 mb-2">Metadata</div>
          <div class="bg-gray-50 rounded p-3">
            <pre class="text-xs text-gray-600">{{ JSON.stringify(log.meta, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="logs.links" class="mt-6">
      <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ logs.meta?.from || 0 }} to {{ logs.meta?.to || 0 }} of {{ logs.meta?.total || 0 }} logs
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>
