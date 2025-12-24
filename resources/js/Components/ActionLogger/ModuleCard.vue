<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface Module {
  key: string
  label: string
  description: string
  icon: string
  count?: number
}

interface Props {
  module: Module
}

defineProps<Props>()
</script>

<template>
  <Link
    :href="route('action-logger.modules.show', module.key)"
    class="block bg-white rounded-lg border border-gray-200 p-6 hover:border-indigo-300 hover:shadow-md transition-all"
  >
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
        </div>
      </div>
      <div class="ml-4 flex-1">
        <h3 class="text-lg font-medium text-gray-900">{{ module.label }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ module.description }}</p>
        <div v-if="module.count !== undefined" class="mt-2">
          <span class="text-2xl font-semibold text-indigo-600">{{ module.count }}</span>
          <span class="text-sm text-gray-500 ml-1">logs</span>
        </div>
      </div>
      <div class="ml-4">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </div>
    </div>
  </Link>
</template>
