<script setup lang="ts">
defineProps<{
  checked: boolean
  disabled?: boolean
  labelOn: string
  labelOff: string
}>()

const emit = defineEmits<{
  change: [value: boolean]
}>()

function handleChange(event: Event) {
  const target = event.target as HTMLInputElement
  emit('change', target.checked)
}
</script>

<template>
  <label class="ff-toggle" :class="{ 'is-disabled': disabled }">
    <input
      class="ff-toggle__input"
      type="checkbox"
      :checked="checked"
      :disabled="disabled"
      @change="handleChange"
    />
    <span class="ff-toggle__track">
      <span class="ff-toggle__thumb"></span>
    </span>
    <span class="ff-toggle__label">{{ checked ? labelOn : labelOff }}</span>
  </label>
</template>
