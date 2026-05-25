<script setup lang="ts">
import type { FeatureFlagsFilterCode, FeatureFlagsFilterItem } from '@/types/featureFlag'
import '../assets/styles/featureFlagsFilters.css'

defineProps<{
  activeFilter: FeatureFlagsFilterCode
  filters: FeatureFlagsFilterItem[]
  searchQuery: string
}>()

const emit = defineEmits<{
  filterChange: [filter: FeatureFlagsFilterCode]
  searchChange: [query: string]
}>()

function handleSearchInput(event: Event): void {
  const target = event.target as HTMLInputElement | null
  if (target !== null) {
    emit('searchChange', target.value)
  }
}
</script>

<template>
  <div class="ff-filter-bar">
    <div class="ff-filter-tabs" role="tablist" aria-label="Фильтр фича-флагов">
      <button
        v-for="filter in filters"
        :key="filter.code"
        type="button"
        :class="[
          'ff-filter-tab',
          `ff-filter-tab--${filter.tone}`,
          { 'is-active': activeFilter === filter.code },
        ]"
        :aria-selected="activeFilter === filter.code"
        role="tab"
        @click="emit('filterChange', filter.code)"
      >
        <span v-if="filter.code !== 'all'" class="ff-filter-tab__dot"></span>
        <span class="ff-filter-tab__label">{{ filter.label }}</span>
        <span class="ff-filter-tab__count">{{ filter.count }}</span>
      </button>
    </div>

    <label class="ff-filter-search">
      <span class="ff-filter-search__icon" aria-hidden="true"></span>
      <input
        class="ff-filter-search__input"
        type="search"
        :value="searchQuery"
        placeholder="Поиск по названию или тегу..."
        @input="handleSearchInput"
      />
    </label>
  </div>
</template>
