<script setup lang="ts">
interface Props {
  label: string
  value: string | number
  trend?: string
  trendUp?: boolean
}

defineProps<Props>()
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ label }}</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ value }}</p>
      </div>
      <div v-if="trend" class="flex items-center">
        <span
          :class="[
            'text-sm font-medium',
            trendUp ? 'text-green-600' : 'text-red-600'
          ]"
        >
          {{ trend }}
        </span>
      </div>
    </div>
  </div>
</template>
