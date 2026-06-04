<script setup lang="ts">
import type {
  FeatureFlagStrategyFormItem,
  StrategyField,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import '../assets/styles/form.css'
import '../assets/styles/strategies.css'

const props = defineProps<{
  getStrategyFields: (code: string) => StrategyField[]
  strategies: FeatureFlagStrategyFormItem[]
  strategyTypes: StrategyTypeItem[]
}>()

function getStrategyName(code: string): string {
  return props.strategyTypes.find((item) => item.code === code)?.name ?? code
}

function getStrategyValues(strategy: FeatureFlagStrategyFormItem): Array<{ code: string; label: string; value: string }> {
  const fields = props.getStrategyFields(strategy.type)

  if (fields.length > 0) {
    return fields.map((field) => ({
      code: field.code,
      label: field.label ?? field.name ?? field.code,
      value: formatValue(strategy.config[field.code]),
    }))
  }

  return Object.entries(strategy.config).map(([code, value]) => ({
    code,
    label: code,
    value: formatValue(value),
  }))
}

function formatValue(value: unknown): string {
  if (Array.isArray(value)) {
    return value.map((item) => String(item)).join('\n')
  }

  if (value === null || value === undefined || value === '') {
    return '—'
  }

  if (typeof value === 'object') {
    return JSON.stringify(value)
  }

  return String(value)
}
</script>

<template>
  <div class="ff-field ff-field--full">
    <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_TITLE') }}</span>

    <div v-if="strategies.length === 0" class="ff-field__value">
      {{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_ALL') }}
    </div>

    <div v-else class="ff-strategies-list">
      <div v-for="strategy in strategies" :key="strategy.uid" class="ff-strategy-row">
        <div class="ff-strategy-row__title">{{ getStrategyName(strategy.type) }}</div>

        <div v-if="getStrategyValues(strategy).length > 0" class="ff-strategy-row__fields">
          <div
            v-for="field in getStrategyValues(strategy)"
            :key="`${strategy.uid}-${field.code}`"
            class="ff-field"
          >
            <span class="ff-field__label">{{ field.label }}</span>
            <span class="ff-field__value ff-field__value--multiline">{{ field.value }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
