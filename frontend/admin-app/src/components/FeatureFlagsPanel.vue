<script setup lang="ts">
import type { FeatureFlagItem, StrategyTypeItem } from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import FeatureFlagsTable from './FeatureFlagsTable.vue'
import '../assets/styles/buttons.css'
import '../assets/styles/panel.css'
import '../assets/styles/state.css'

defineProps<{
  flags: FeatureFlagItem[]
  isLoading: boolean
  listError: string
  processingCodes: string[]
  strategyTypes: StrategyTypeItem[]
}>()

const emit = defineEmits<{
  create: []
  edit: [code: string]
  toggle: [flag: FeatureFlagItem, value: boolean]
}>()
</script>

<template>
  <section class="ff-panel">
    <div v-if="isLoading" class="ff-state">
      {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOADING') }}
    </div>

    <div v-else-if="listError" class="ff-state ff-state--error">
      {{ listError }}
    </div>

    <div v-else-if="flags.length === 0" class="ff-empty">
      <div class="ff-empty__title">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST') }}
      </div>
      <button type="button" class="ff-button ff-button--primary" @click="emit('create')">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_BTN_ADD') }}
      </button>
    </div>

    <FeatureFlagsTable
      v-else
      :flags="flags"
      :processing-codes="processingCodes"
      :strategy-types="strategyTypes"
      @edit="emit('edit', $event)"
      @toggle="(flag, value) => emit('toggle', flag, value)"
    />
  </section>
</template>
