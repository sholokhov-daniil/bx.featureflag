<script setup lang="ts">
import type { FeatureFlagItem, FeatureFlagsDisplayMode, StrategyTypeItem } from '@/types/featureFlag'
import FeatureFlagsCards from './FeatureFlagsCards.vue'
import FeatureFlagsTable from './FeatureFlagsTable.vue'

defineProps<{
  mode: FeatureFlagsDisplayMode
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
  <FeatureFlagsCards
    v-if="mode === 'cards'"
    :flags="flags"
    :processing-codes="processingCodes"
    :strategy-types="strategyTypes"
    @edit="emit('edit', $event)"
    @toggle="(flag, value) => emit('toggle', flag, value)"
  />

  <FeatureFlagsTable
    v-else-if="mode === 'table'"
    :flags="flags"
    :processing-codes="processingCodes"
    :strategy-types="strategyTypes"
    @edit="emit('edit', $event)"
    @toggle="(flag, value) => emit('toggle', flag, value)"
  />
</template>
