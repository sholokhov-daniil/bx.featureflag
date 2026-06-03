<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type {
  StrategyEntitySelectorDialogOptions,
  StrategyEntitySelectorItemId,
  StrategyEntitySelectorOptions,
  StrategyEntitySelectorTagItem,
  StrategyField,
} from '@/types/featureFlag'

interface BitrixWindow extends Window {
  BX?: {
    loadExt?: (extension: string) => Promise<unknown>
    UI?: {
      EntitySelector?: {
        TagSelector?: BitrixTagSelectorConstructor
      }
    }
  }
}

type BitrixTagSelectorConstructor = new (options: BitrixTagSelectorOptions) => BitrixTagSelector

interface BitrixTagSelectorOptions extends Omit<StrategyEntitySelectorOptions, 'dialogOptions' | 'items'> {
  dialogOptions?: StrategyEntitySelectorDialogOptions
  events?: Record<string, (event: BitrixBaseEvent) => void>
  items?: StrategyEntitySelectorTagItem[]
}

interface BitrixBaseEvent {
  getTarget(): BitrixTagSelector
  getData(): Record<string, unknown>
}

interface BitrixDialog {
  destroy?: () => void
}

interface BitrixTagItem {
  getEntityId(): string
  getId(): string | number
}

interface BitrixTagSelector {
  addTag(tagOptions: StrategyEntitySelectorTagItem): BitrixTagItem | null
  getDialog?: () => BitrixDialog | null
  getTags(): BitrixTagItem[]
  removeTags(): void
  renderTo(node: HTMLElement): void
  setReadonly?: (flag: boolean) => void
}

const props = defineProps<{
  disabled: boolean
  field: StrategyField
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const containerRef = ref<HTMLElement | null>(null)
const isFallbackInput = ref(false)
const selectorId = `featureflag-${Math.random().toString(36).slice(2)}`

let tagSelector: BitrixTagSelector | null = null
let lastEmittedValue = ''
let renderRequest = 0

onMounted(() => {
  void renderTagSelector()
})

watch(() => props.disabled, () => {
  applyReadonlyState()
})

watch(() => props.modelValue, (value) => {
  if (tagSelector === null || value === lastEmittedValue) {
    return
  }

  void renderTagSelector()
})

watch(() => props.field.options, () => {
  void renderTagSelector()
}, {
  deep: true,
})

onBeforeUnmount(() => {
  renderRequest += 1
  destroyTagSelector()
})

async function renderTagSelector(): Promise<void> {
  const requestId = ++renderRequest
  const TagSelector = await getTagSelectorConstructor()
  if (requestId !== renderRequest) {
    return
  }

  if (TagSelector === null || containerRef.value === null) {
    isFallbackInput.value = true
    return
  }

  destroyTagSelector()
  isFallbackInput.value = false
  tagSelector = new TagSelector(createTagSelectorOptions())
  tagSelector.renderTo(containerRef.value)
  applyReadonlyState()
}

function destroyTagSelector(): void {
  tagSelector?.getDialog?.()?.destroy?.()
  tagSelector = null
  containerRef.value?.replaceChildren()
}

async function getTagSelectorConstructor(): Promise<BitrixTagSelectorConstructor | null> {
  const bx = (window as BitrixWindow).BX
  let TagSelector = bx?.UI?.EntitySelector?.TagSelector ?? null
  if (TagSelector !== null) {
    return TagSelector
  }

  if (typeof bx?.loadExt !== 'function') {
    return null
  }

  try {
    await bx.loadExt('ui.entity-selector')
  } catch {
    return null
  }

  TagSelector = bx.UI?.EntitySelector?.TagSelector ?? null
  return TagSelector
}

function createTagSelectorOptions(): BitrixTagSelectorOptions {
  const fieldOptions = cloneOptions(props.field.options ?? {})
  const selectedItems = parseModelValue(props.modelValue)
  const dialogOptions = createDialogOptions(fieldOptions, selectedItems)
  const hasDialogOptions = Object.keys(dialogOptions).length > 0
  const items = hasDialogOptions
    ? fieldOptions.items ?? []
    : createFallbackItems(fieldOptions.items ?? [], selectedItems)

  const options: BitrixTagSelectorOptions = {
    ...fieldOptions,
    id: `${fieldOptions.id ?? props.field.code}-${selectorId}`,
    multiple: fieldOptions.multiple ?? true,
    readonly: props.disabled || fieldOptions.readonly === true,
    events: {
      onTagAdd: syncValueFromSelector,
      onTagRemove: syncValueFromSelector,
    },
  }

  if (hasDialogOptions) {
    options.dialogOptions = dialogOptions
  } else {
    delete options.dialogOptions
  }

  if (items.length > 0) {
    options.items = items
  } else {
    delete options.items
  }

  return options
}

function cloneOptions(options: StrategyEntitySelectorOptions): StrategyEntitySelectorOptions {
  return JSON.parse(JSON.stringify(options)) as StrategyEntitySelectorOptions
}

function createDialogOptions(
  fieldOptions: StrategyEntitySelectorOptions,
  selectedItems: StrategyEntitySelectorItemId[],
): StrategyEntitySelectorDialogOptions {
  const dialogOptions: StrategyEntitySelectorDialogOptions = {
    ...(fieldOptions.dialogOptions ?? {}),
  }
  const preselectedItems = mergeItemIds(
    normalizeItemIds(dialogOptions.preselectedItems ?? []),
    selectedItems,
  )

  if (preselectedItems.length > 0) {
    dialogOptions.preselectedItems = preselectedItems
  }

  if (dialogOptions.multiple === undefined && fieldOptions.multiple !== undefined) {
    dialogOptions.multiple = fieldOptions.multiple
  }

  dialogOptions.popupOptions = {
    ...(dialogOptions.popupOptions ?? {}),
    targetContainer: getDialogTargetContainer(),
    zIndexOptions: {
      ...(dialogOptions.popupOptions?.zIndexOptions ?? {}),
      alwaysOnTop: dialogOptions.popupOptions?.zIndexOptions?.alwaysOnTop ?? true,
    },
  }

  return dialogOptions
}

function getDialogTargetContainer(): HTMLElement {
  return containerRef.value?.closest('.ff-modal') as HTMLElement ?? document.body
}

function createFallbackItems(
  items: StrategyEntitySelectorTagItem[],
  selectedItems: StrategyEntitySelectorItemId[],
): StrategyEntitySelectorTagItem[] {
  const result = [...items]

  for (const [entityId, id] of selectedItems) {
    if (result.some((item) => item.entityId === entityId && String(item.id) === String(id))) {
      continue
    }

    result.push({
      id,
      entityId,
      title: String(id),
      animate: false,
    })
  }

  return result
}

function parseModelValue(value: string): StrategyEntitySelectorItemId[] {
  const entityId = getDefaultEntityId()
  const result: StrategyEntitySelectorItemId[] = []
  const usedItems = new Set<string>()

  for (const token of value.split(/[\s,;]+/)) {
    const id = token.trim()
    if (id === '') {
      continue
    }

    const itemId = normalizeItemId(id)
    const key = `${entityId}:${String(itemId)}`
    if (usedItems.has(key)) {
      continue
    }

    usedItems.add(key)
    result.push([entityId, itemId])
  }

  return result
}

function getDefaultEntityId(): string {
  const dialogOptions = props.field.options?.dialogOptions
  const entityId = dialogOptions?.entities?.[0]?.id
  if (entityId) {
    return entityId
  }

  const preselectedEntityId = dialogOptions?.preselectedItems?.[0]?.[0]
  return preselectedEntityId ?? 'user'
}

function normalizeItemId(id: string): string | number {
  return /^[1-9]\d*$/.test(id) ? Number(id) : id
}

function normalizeItemIds(items: StrategyEntitySelectorItemId[]): StrategyEntitySelectorItemId[] {
  return items.filter((item): item is StrategyEntitySelectorItemId => (
    Array.isArray(item)
    && item.length === 2
    && typeof item[0] === 'string'
    && (typeof item[1] === 'string' || typeof item[1] === 'number')
  ))
}

function mergeItemIds(
  firstItems: StrategyEntitySelectorItemId[],
  secondItems: StrategyEntitySelectorItemId[],
): StrategyEntitySelectorItemId[] {
  const result: StrategyEntitySelectorItemId[] = []
  const usedItems = new Set<string>()

  for (const item of [...firstItems, ...secondItems]) {
    const key = `${item[0]}:${String(item[1])}`
    if (usedItems.has(key)) {
      continue
    }

    usedItems.add(key)
    result.push(item)
  }

  return result
}

function applyReadonlyState(): void {
  tagSelector?.setReadonly?.(props.disabled || props.field.options?.readonly === true)
}

function syncValueFromSelector(): void {
  if (tagSelector === null) {
    return
  }

  const value = tagSelector
    .getTags()
    .map((tag) => String(tag.getId()))
    .filter((id) => id !== '')
    .join('\n')

  lastEmittedValue = value
  emit('update:modelValue', value)
}

function handleFallbackInput(event: Event): void {
  const target = event.target as HTMLInputElement | null
  if (target === null) {
    return
  }

  lastEmittedValue = target.value
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div
    v-show="!isFallbackInput"
    ref="containerRef"
    :class="['ff-entity-selector-field', { 'is-disabled': disabled }]"
  ></div>
  <input
    v-if="isFallbackInput"
    :value="modelValue"
    type="text"
    class="ff-input ff-input--main"
    :placeholder="field.options?.placeholder ?? field.placeholder ?? ''"
    :disabled="disabled"
    @input="handleFallbackInput"
  />
</template>
