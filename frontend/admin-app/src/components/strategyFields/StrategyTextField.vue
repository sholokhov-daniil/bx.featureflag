<script setup lang="ts">
import type { StrategyField, StrategyFieldInputMode } from '@/types/featureFlag'
import { applyStrategyFieldMask } from '@/utils/strategyFieldMask'

const props = defineProps<{
  disabled: boolean
  field: StrategyField
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function getFieldInputMode(field: StrategyField): StrategyFieldInputMode | undefined {
  if (field.mask?.type === 'regex') {
    return field.mask.inputMode
  }

  return undefined
}

function handleInput(event: Event): void {
  const target = event.target as HTMLInputElement | null
  if (target === null) {
    return
  }

  const value = applyStrategyFieldMask(target.value, props.field.mask)
  if (target.value !== value) {
    target.value = value
  }

  emit('update:modelValue', value)
}
</script>

<template>
  <input
    :value="modelValue"
    type="text"
    class="ff-input ff-input--main"
    :placeholder="field.placeholder ?? field.options?.placeholder ?? ''"
    :disabled="disabled"
    :inputmode="getFieldInputMode(field)"
    @input="handleInput"
  />
</template>
