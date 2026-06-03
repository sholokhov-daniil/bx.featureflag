<script setup lang="ts">
import { ref } from 'vue'
import type { AdminView } from '@/types/featureFlag'
import TagsApp from './TagsApp.vue'
import FeatureFlagsView from './views/FeatureFlagsView.vue'

const currentView = ref<AdminView>(window.SholokhovFeatureFlagAdmin?.view ?? 'flags')

function openTagsPage(): void {
  currentView.value = 'tags'
}

function openFlagsPage(): void {
  currentView.value = 'flags'
}
</script>

<template>
  <TagsApp v-if="currentView === 'tags'" embedded @back="openFlagsPage" />
  <FeatureFlagsView v-else @manage-tags="openTagsPage" />
</template>
