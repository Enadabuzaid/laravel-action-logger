<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  events?: string[]
  modules?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  events: () => [],
  modules: () => ({})
})

const emit = defineEmits<{
  filter: [filters: any]
}>()

const selectedEvent = ref('')
const selectedModule = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const applyFilters = () => {
  emit('filter', {
    event: selectedEvent.value || undefined,
    module_key: selectedModule.value || undefined,
    from: dateFrom.value || undefined,
    to: dateTo.value || undefined,
  })
}

const clearFilters = () => {
  selectedEvent.value = ''
  selectedModule.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  applyFilters()
}
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Event Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
        <select
          v-model="selectedEvent"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="applyFilters"
        >
          <option value="">All Events</option>
          <option v-for="event in events" :key="event" :value="event">
            {{ event }}
          </option>
        </select>
      </div>

      <!-- Module Filter -->
      <div v-if="Object.keys(modules).length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
        <select
          v-model="selectedModule"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="applyFilters"
        >
          <option value="">All Modules</option>
          <option v-for="(module, key) in modules" :key="key" :value="key">
            {{ module.label }}
          </option>
        </select>
      </div>

      <!-- Date From -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
        <input
          v-model="dateFrom"
          type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="applyFilters"
        />
      </div>

      <!-- Date To -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
        <input
          v-model="dateTo"
          type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="applyFilters"
        />
      </div>
    </div>

    <!-- Clear Filters -->
    <div class="mt-4 flex justify-end">
      <button
        type="button"
        class="text-sm text-gray-600 hover:text-gray-900"
        @click="clearFilters"
      >
        Clear Filters
      </button>
    </div>
  </div>
</template>
