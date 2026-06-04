<script setup lang="ts">
import { ref } from 'vue'
import type { AdminView } from '@/types/featureFlag'
import TagsApp from './TagsApp.vue'
import FeatureFlagsView from './views/FeatureFlagsView.vue'

const currentView = ref<AdminView>(window.SholokhovFeatureFlagAdmin?.view ?? 'flags')
const canWrite = window.SholokhovFeatureFlagAdmin?.canWrite === true

function openTagsPage(): void {
  currentView.value = 'tags'
}

function openFlagsPage(): void {
  currentView.value = 'flags'
}
</script>

<template>
  <TagsApp v-if="currentView === 'tags'" embedded :can-write="canWrite" @back="openFlagsPage" />
  <FeatureFlagsView v-else :can-write="canWrite" @manage-tags="openTagsPage" />
</template>
