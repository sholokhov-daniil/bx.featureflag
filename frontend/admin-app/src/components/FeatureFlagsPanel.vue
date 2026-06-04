<script setup lang="ts">
import type {
  FeatureFlagItem,
  FeatureFlagsDisplayMode,
  FeatureFlagsDisplayOption,
  FeatureFlagsFilterCode,
  FeatureFlagsFilterItem,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import FeatureFlagsDisplay from './FeatureFlagsDisplay.vue'
import FeatureFlagsDisplaySwitcher from './FeatureFlagsDisplaySwitcher.vue'
import FeatureFlagsFilters from './FeatureFlagsFilters.vue'
import FeatureFlagsPagination from './FeatureFlagsPagination.vue'
import '../assets/styles/buttons.css'
import '../assets/styles/featureFlagsDisplay.css'
import '../assets/styles/panel.css'
import '../assets/styles/state.css'

defineProps<{
  canWrite: boolean
  currentPage: number
  displayMode: FeatureFlagsDisplayMode
  displayOptions: FeatureFlagsDisplayOption[]
  activeFilter: FeatureFlagsFilterCode
  filterItems: FeatureFlagsFilterItem[]
  flags: FeatureFlagItem[]
  isLoading: boolean
  listError: string
  pageSize: number
  processingCodes: string[]
  searchQuery: string
  sourceItems: number
  strategyTypes: StrategyTypeItem[]
  totalItems: number
}>()

const emit = defineEmits<{
  create: []
  displayModeChange: [mode: FeatureFlagsDisplayMode]
  edit: [code: string]
  filterChange: [filter: FeatureFlagsFilterCode]
  pageChange: [page: number]
  searchChange: [query: string]
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

    <div v-else-if="sourceItems === 0" class="ff-empty">
      <div class="ff-empty__title">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST') }}
      </div>
      <button v-if="canWrite" type="button" class="ff-button ff-button--primary" @click="emit('create')">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_BTN_ADD') }}
      </button>
    </div>

    <template v-else>
      <FeatureFlagsFilters
        :active-filter="activeFilter"
        :filters="filterItems"
        :search-query="searchQuery"
        @filter-change="emit('filterChange', $event)"
        @search-change="emit('searchChange', $event)"
      />

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
        v-if="totalItems > 0"
        :mode="displayMode"
        :can-write="canWrite"
        :flags="flags"
        :processing-codes="processingCodes"
        :strategy-types="strategyTypes"
        @edit="emit('edit', $event)"
        @toggle="(flag, value) => emit('toggle', flag, value)"
      />

      <div v-if="totalItems === 0" class="ff-state">
        Ничего не найдено
      </div>

      <FeatureFlagsPagination
        v-else
        :current-page="currentPage"
        :page-size="pageSize"
        :total-items="totalItems"
        @change="emit('pageChange', $event)"
      />
    </template>
  </section>
</template>
