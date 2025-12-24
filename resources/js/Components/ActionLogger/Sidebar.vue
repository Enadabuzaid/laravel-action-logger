<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

interface Module {
  key: string
  label: string
  description: string
  icon: string
}

interface Props {
  modules?: Record<string, Module>
}

const props = withDefaults(defineProps<Props>(), {
  modules: () => ({})
})

const modulesList = computed(() => {
  return Object.entries(props.modules).map(([key, module]) => ({
    ...module,
    key,
  }))
})

const isActive = (routeName: string, params?: any) => {
  return route().current(routeName, params)
}
</script>

<template>
  <aside class="w-64 bg-white border-r border-gray-200 min-h-screen">
    <div class="p-6">
      <h2 class="text-lg font-semibold text-gray-900">Action Logger</h2>
      <p class="text-sm text-gray-500 mt-1">Audit trail & activity logs</p>
    </div>

    <nav class="px-3 space-y-1">
      <!-- Dashboard -->
      <Link
        :href="route('action-logger.dashboard')"
        :class="[
          'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
          isActive('action-logger.dashboard')
            ? 'bg-indigo-50 text-indigo-700'
            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
        ]"
      >
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Dashboard
      </Link>

      <!-- All Logs -->
      <Link
        :href="route('action-logger.all')"
        :class="[
          'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
          isActive('action-logger.all')
            ? 'bg-indigo-50 text-indigo-700'
            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
        ]"
      >
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        All Logs
      </Link>

      <!-- Modules -->
      <div v-if="modulesList.length > 0" class="pt-4">
        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Modules
        </h3>
        <div class="mt-2 space-y-1">
          <Link
            v-for="module in modulesList"
            :key="module.key"
            :href="route('action-logger.modules.show', module.key)"
            :class="[
              'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
              isActive('action-logger.modules.show', module.key)
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
            ]"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            {{ module.label }}
          </Link>
        </div>
      </div>
    </nav>
  </aside>
</template>
