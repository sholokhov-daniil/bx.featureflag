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
  const target = event.target as HTMLTextAreaElement | null
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
  <textarea
    :value="modelValue"
    class="ff-textarea ff-textarea--main ff-textarea--compact"
    rows="3"
    :placeholder="field.placeholder ?? field.options?.placeholder ?? ''"
    :disabled="disabled"
    :inputmode="getFieldInputMode(field)"
    @input="handleInput"
  ></textarea>
</template>
