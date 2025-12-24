<script setup lang="ts">
import Sidebar from '@/Components/ActionLogger/Sidebar.vue'

interface Props {
  modules?: Record<string, any>
}

withDefaults(defineProps<Props>(), {
  modules: () => ({})
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="flex">
      <!-- Sidebar -->
      <Sidebar :modules="modules" />

      <!-- Main Content -->
      <main class="flex-1 p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
