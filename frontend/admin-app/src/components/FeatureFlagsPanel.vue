<script setup lang="ts">
import type {
  FeatureFlagItem,
  FeatureFlagsDisplayMode,
  FeatureFlagsDisplayOption,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import FeatureFlagsDisplay from './FeatureFlagsDisplay.vue'
import FeatureFlagsDisplaySwitcher from './FeatureFlagsDisplaySwitcher.vue'
import FeatureFlagsPagination from './FeatureFlagsPagination.vue'
import '../assets/styles/buttons.css'
import '../assets/styles/featureFlagsDisplay.css'
import '../assets/styles/panel.css'
import '../assets/styles/state.css'

defineProps<{
  currentPage: number
  displayMode: FeatureFlagsDisplayMode
  displayOptions: FeatureFlagsDisplayOption[]
  flags: FeatureFlagItem[]
  isLoading: boolean
  listError: string
  pageSize: number
  processingCodes: string[]
  strategyTypes: StrategyTypeItem[]
  totalItems: number
}>()

const emit = defineEmits<{
  create: []
  displayModeChange: [mode: FeatureFlagsDisplayMode]
  edit: [code: string]
  pageChange: [page: number]
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

    <div v-else-if="totalItems === 0" class="ff-empty">
      <div class="ff-empty__title">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST') }}
      </div>
      <button type="button" class="ff-button ff-button--primary" @click="emit('create')">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_BTN_ADD') }}
      </button>
    </div>

    <template v-else>
      <div class="ff-list-toolbar">
        <div class="ff-list-toolbar__group">
          <span class="ff-list-toolbar__label">Вид</span>
          <FeatureFlagsDisplaySwitcher
            :mode="displayMode"
            :options="displayOptions"
            @change="emit('displayModeChange', $event)"
          />
        </div>
      </div>

      <FeatureFlagsDisplay
        :mode="displayMode"
        :flags="flags"
        :processing-codes="processingCodes"
        :strategy-types="strategyTypes"
        @edit="emit('edit', $event)"
        @toggle="(flag, value) => emit('toggle', flag, value)"
      />

      <FeatureFlagsPagination
        :current-page="currentPage"
        :page-size="pageSize"
        :total-items="totalItems"
        @change="emit('pageChange', $event)"
      />
    </template>
  </section>
</template>
