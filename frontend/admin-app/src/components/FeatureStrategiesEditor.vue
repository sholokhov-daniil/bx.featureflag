<script setup lang="ts">
import type { Component } from 'vue'
import type {
  FieldErrors,
  FeatureFlagStrategyFormItem,
  StrategyField,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import StrategyEntitySelectorField from './strategyFields/StrategyEntitySelectorField.vue'
import StrategyTextField from './strategyFields/StrategyTextField.vue'
import StrategyTextareaField from './strategyFields/StrategyTextareaField.vue'
import '../assets/styles/buttons.css'
import '../assets/styles/form.css'
import '../assets/styles/strategies.css'

const props = defineProps<{
  disabled: boolean
  fieldErrors: FieldErrors
  hasStrategyTypes: boolean
  strategies: FeatureFlagStrategyFormItem[]
  strategyTypes: StrategyTypeItem[]
  getStrategyFields: (code: string) => StrategyField[]
}>()

const emit = defineEmits<{
  add: []
  remove: [index: number]
  changeType: [strategy: FeatureFlagStrategyFormItem, type: string]
  fieldChange: [value: string, strategy: FeatureFlagStrategyFormItem, field: StrategyField]
}>()

const strategyFieldComponents = {
  text: StrategyTextField,
  textarea: StrategyTextareaField,
  'entity-selector': StrategyEntitySelectorField,
} satisfies Record<StrategyField['type'], Component>

function handleTypeChange(event: Event, strategy: FeatureFlagStrategyFormItem): void {
  const target = event.target as HTMLSelectElement | null
  if (target === null) {
    return
  }

  emit('changeType', strategy, target.value)
}

function getStrategyFieldComponent(field: StrategyField): Component {
  return strategyFieldComponents[field.type] ?? StrategyTextField
}

function getFieldLabel(field: StrategyField): string {
  return field.label ?? field.name ?? field.code
}

function isWideField(field: StrategyField): boolean {
  return field.type !== 'text'
}

function getStrategyType(code: string): StrategyTypeItem | null {
  return props.strategyTypes.find((item) => item.code === code) ?? null
}

function isStrategyTypeAvailable(type: StrategyTypeItem | null | undefined): boolean {
  return type?.available !== false
}

function isStrategyAvailable(code: string): boolean {
  return isStrategyTypeAvailable(getStrategyType(code))
}

function getStrategyOptionLabel(type: StrategyTypeItem): string {
  return isStrategyTypeAvailable(type)
    ? type.name
    : `${type.name} (${Loc('SHOLOKHOV_FEATUREFLAG_STRATEGY_UNAVAILABLE_SHORT')})`
}

function getStrategyUnavailableReason(code: string): string {
  const type = getStrategyType(code)
  if (isStrategyTypeAvailable(type)) {
    return ''
  }

  return type?.unavailableReason || Loc('SHOLOKHOV_FEATUREFLAG_STRATEGY_UNAVAILABLE')
}
</script>

<template>
  <div class="ff-field ff-field--full">
    <div class="ff-strategies-head">
      <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_TITLE') }}</span>
      <button
        type="button"
        class="ff-button ff-button--ghost ff-button--compact"
        :disabled="disabled || !hasStrategyTypes"
        @click="emit('add')"
      >
        {{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGY_ADD') }}
      </button>
    </div>

    <div v-if="fieldErrors.strategies.length" class="ff-field-errors">
      <div v-for="(error, index) in fieldErrors.strategies" :key="`strategies-${index}-${error}`">
        {{ error }}
      </div>
    </div>

    <div v-if="strategies.length === 0" class="ff-strategies-empty">
      {{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_ALL') }}
    </div>

    <div v-else class="ff-strategies-list">
      <div
        v-for="(strategy, strategyIndex) in strategies"
        :key="strategy.uid"
        :class="['ff-strategy-row', { 'is-unavailable': !isStrategyAvailable(strategy.type) }]"
      >
        <div class="ff-strategy-row__top">
          <select
            :value="strategy.type"
            class="ff-select"
            :disabled="disabled"
            @change="handleTypeChange($event, strategy)"
          >
            <option
              v-for="type in strategyTypes"
              :key="type.code"
              :value="type.code"
              :disabled="!isStrategyTypeAvailable(type)"
            >
              {{ getStrategyOptionLabel(type) }}
            </option>
          </select>

          <button
            type="button"
            class="ff-icon-button"
            :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_STRATEGY_REMOVE')"
            :disabled="disabled"
            @click="emit('remove', strategyIndex)"
          >
            ×
          </button>
        </div>

        <div v-if="getStrategyUnavailableReason(strategy.type)" class="ff-strategy-row__notice">
          {{ getStrategyUnavailableReason(strategy.type) }}
        </div>

        <div v-else class="ff-strategy-row__fields">
          <div
            v-for="field in getStrategyFields(strategy.type)"
            :key="`${strategy.uid}-${field.code}`"
            :class="['ff-field', { 'ff-strategy-row__field--wide': isWideField(field) }]"
          >
            <span class="ff-field__label">{{ getFieldLabel(field) }}</span>
            <component
              :is="getStrategyFieldComponent(field)"
              :field="field"
              :model-value="strategy.config[field.code] ?? ''"
              :disabled="disabled"
              @update:model-value="emit('fieldChange', $event, strategy, field)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
