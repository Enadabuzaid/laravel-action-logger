<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import EventBadge from './EventBadge.vue'

interface Log {
  id: number
  event: string
  event_label: string
  event_color: string
  description: string
  subject: {
    type: string
    type_full: string
    id: number
    display: string
  }
  actor: {
    type: string
    id: number
    name: string
  } | null
  created_at: string
  created_at_human: string
}

interface Props {
  logs: {
    data: Log[]
    links?: any
    meta?: any
  }
}

defineProps<Props>()
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date/Time
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Event
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Subject
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actor
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Description
            </th>
            <th scope="col" class="relative px-6 py-3">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <div>{{ log.created_at }}</div>
              <div class="text-xs text-gray-400">{{ log.created_at_human }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <EventBadge :event="log.event_label" :color="log.event_color" />
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ log.subject.type }}</div>
              <div class="text-sm text-gray-500">{{ log.subject.display }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <div v-if="log.actor">
                {{ log.actor.name }}
              </div>
              <div v-else class="text-gray-400">
                System
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ log.description }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <Link
                :href="route('action-logger.subject.show', [log.subject.type_full, log.subject.id])"
                class="text-indigo-600 hover:text-indigo-900"
              >
                View
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="logs.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
      <div class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link
            v-if="logs.links.prev"
            :href="logs.links.prev"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Previous
          </Link>
          <Link
            v-if="logs.links.next"
            :href="logs.links.next"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ logs.meta?.from || 0 }}</span>
              to
              <span class="font-medium">{{ logs.meta?.to || 0 }}</span>
              of
              <span class="font-medium">{{ logs.meta?.total || 0 }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <Link
                v-for="(link, index) in logs.meta?.links"
                :key="index"
                :href="link.url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  link.active
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  index === 0 ? 'rounded-l-md' : '',
                  index === logs.meta.links.length - 1 ? 'rounded-r-md' : ''
                ]"
                v-html="link.label"
              />
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
