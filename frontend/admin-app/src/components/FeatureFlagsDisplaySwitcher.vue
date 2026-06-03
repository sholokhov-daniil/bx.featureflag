<script setup lang="ts">
import type { FeatureFlagsDisplayMode, FeatureFlagsDisplayOption } from '@/types/featureFlag'
import '../assets/styles/featureFlagsDisplay.css'

defineProps<{
  mode: FeatureFlagsDisplayMode
  options: FeatureFlagsDisplayOption[]
}>()

const emit = defineEmits<{
  change: [mode: FeatureFlagsDisplayMode]
}>()
</script>

<template>
  <div class="ff-view-switch" role="group" aria-label="Способ отображения">
    <button
      v-for="option in options"
      :key="option.code"
      type="button"
      :class="['ff-view-switch__button', { 'is-active': option.code === mode }]"
      :aria-pressed="option.code === mode"
      @click="emit('change', option.code)"
    >
      <span
        :class="['ff-view-switch__icon', `ff-view-switch__icon--${option.code}`]"
        aria-hidden="true"
      ></span>
      <span>{{ option.label }}</span>
    </button>
  </div>
</template>
