<script setup lang="ts">
import type {
  FieldErrors,
  FeatureFlagStrategyFormItem,
  StrategyField,
  StrategyFieldInputMode,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import '../assets/styles/buttons.css'
import '../assets/styles/form.css'
import '../assets/styles/strategies.css'

defineProps<{
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
  fieldInput: [event: Event, strategy: FeatureFlagStrategyFormItem, field: StrategyField]
}>()

function handleTypeChange(event: Event, strategy: FeatureFlagStrategyFormItem): void {
  const target = event.target as HTMLSelectElement | null
  if (target === null) {
    return
  }

  emit('changeType', strategy, target.value)
}

function getFieldInputMode(field: StrategyField): StrategyFieldInputMode | undefined {
  if (field.mask?.type === 'regex') {
    return field.mask.inputMode
  }

  return undefined
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
      <div v-for="(strategy, strategyIndex) in strategies" :key="strategy.uid" class="ff-strategy-row">
        <div class="ff-strategy-row__top">
          <select
            :value="strategy.type"
            class="ff-select"
            :disabled="disabled"
            @change="handleTypeChange($event, strategy)"
          >
            <option v-for="type in strategyTypes" :key="type.code" :value="type.code">
              {{ type.name }}
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

        <div class="ff-strategy-row__fields">
          <label
            v-for="field in getStrategyFields(strategy.type)"
            :key="`${strategy.uid}-${field.code}`"
            :class="['ff-field', { 'ff-strategy-row__field--wide': field.type === 'textarea' }]"
          >
            <span class="ff-field__label">{{ field.label }}</span>
            <textarea
              v-if="field.type === 'textarea'"
              :value="strategy.config[field.code]"
              class="ff-textarea ff-textarea--main ff-textarea--compact"
              rows="3"
              :placeholder="field.placeholder ?? ''"
              :disabled="disabled"
              :inputmode="getFieldInputMode(field)"
              @input="emit('fieldInput', $event, strategy, field)"
            ></textarea>
            <input
              v-else
              :value="strategy.config[field.code]"
              type="text"
              class="ff-input ff-input--main"
              :placeholder="field.placeholder ?? ''"
              :disabled="disabled"
              :inputmode="getFieldInputMode(field)"
              @input="emit('fieldInput', $event, strategy, field)"
            />
          </label>
        </div>
      </div>
    </div>
  </div>
</template>
