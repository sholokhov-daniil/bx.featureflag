<script setup lang="ts">
import type { FeatureFlagItem, StrategyTypeItem } from '@/types/featureFlag'
import FeatureFlagCard from './FeatureFlagCard.vue'
import '../assets/styles/featureFlagsCards.css'

defineProps<{
  canWrite: boolean
  flags: FeatureFlagItem[]
  processingCodes: string[]
  strategyTypes: StrategyTypeItem[]
}>()

const emit = defineEmits<{
  edit: [code: string]
  toggle: [flag: FeatureFlagItem, value: boolean]
}>()
</script>

<template>
  <div class="ff-flag-cards">
    <FeatureFlagCard
      v-for="flag in flags"
      :key="flag.code"
      :can-write="canWrite"
      :flag="flag"
      :processing-codes="processingCodes"
      :strategy-types="strategyTypes"
      @edit="emit('edit', $event)"
      @toggle="(item, value) => emit('toggle', item, value)"
    />
  </div>
</template>
