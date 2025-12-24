<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  changes: Record<string, { old: any; new: any }>
  oldValues?: Record<string, any>
  newValues?: Record<string, any>
}

const props = defineProps<Props>()

const isExpanded = ref(false)

const hasChanges = Object.keys(props.changes || {}).length > 0
</script>

<template>
  <div v-if="hasChanges" class="border border-gray-200 rounded-lg overflow-hidden">
    <button
      type="button"
      class="w-full px-4 py-3 bg-gray-50 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center justify-between"
      @click="isExpanded = !isExpanded"
    >
      <span>View Changes ({{ Object.keys(changes).length }})</span>
      <svg
        :class="['w-5 h-5 transition-transform', isExpanded ? 'transform rotate-180' : '']"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <div v-if="isExpanded" class="p-4 bg-white">
      <div class="space-y-4">
        <div v-for="(change, field) in changes" :key="field" class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
          <div class="text-sm font-medium text-gray-700 mb-2">{{ field }}</div>
          <div class="grid grid-cols-2 gap-4">
            <!-- Old Value -->
            <div>
              <div class="text-xs text-gray-500 mb-1">Old Value</div>
              <div class="bg-red-50 border border-red-200 rounded p-2 text-sm">
                <code class="text-red-900">{{ change.old ?? 'null' }}</code>
              </div>
            </div>
            <!-- New Value -->
            <div>
              <div class="text-xs text-gray-500 mb-1">New Value</div>
              <div class="bg-green-50 border border-green-200 rounded p-2 text-sm">
                <code class="text-green-900">{{ change.new ?? 'null' }}</code>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="text-sm text-gray-500 italic">
    No changes recorded
  </div>
</template>
