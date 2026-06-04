<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import AdminFooter from './components/AdminFooter.vue'
import './assets/styles/adminHero.css'
import './assets/styles/adminShell.css'
import './assets/styles/buttons.css'
import './assets/styles/featureFlagsTable.css'
import './assets/styles/form.css'
import './assets/styles/modal.css'
import './assets/styles/notice.css'
import './assets/styles/panel.css'
import './assets/styles/state.css'
import './assets/styles/table.css'
import './assets/styles/textUtilities.css'

interface TagItem {
  id: number
  name: string
}

interface BootstrapConfig {
  actions: Record<string, string>
  canWrite?: boolean
  messages: Record<string, string>
  urls?: Record<string, string>
}

interface ActionConfig {
  tagList: string
  tagCreate: string
  tagUpdate: string
  tagDelete: string
}

type NoticeType = 'success' | 'error'
type ModalMode = 'create' | 'edit'
type TagFieldKey = 'id' | 'name'

interface FieldErrors {
  id: string[]
  name: string[]
}

interface FormErrorState {
  common: string[]
  fields: FieldErrors
}

interface UiErrorItem {
  message: string
  code?: string | number
  customData?: unknown
}

const props = defineProps<{
  canWrite: boolean
  embedded?: boolean
}>()

const emit = defineEmits<{
  back: []
}>()

const bootstrap: BootstrapConfig = window.SholokhovFeatureFlagAdmin ?? {
  actions: {},
  messages: {},
}

const messages = {
  subtitle: '',
  add: 'Добавить тег',
  createTitle: 'Новый тег',
  editTitle: 'Настройки тега',
  save: 'Сохранить',
  cancel: 'Отменить',
  delete: 'Удалить',
  loading: 'Загрузка...',
  empty: 'Теги не найдены',
  name: 'Название тега',
  createdSuccess: 'Тег успешно создан',
  updatedSuccess: 'Тег успешно обновлён',
  deletedSuccess: 'Тег успешно удалён',
  genericError: 'Не удалось выполнить операцию',
  loadError: 'Не удалось загрузить теги',
  deleteConfirm: 'Удалить тег?',
  closeLabel: 'Закрыть',
  openDetail: 'Открыть настройки тега',
  panelTitle: 'Теги',
  panelCaption: 'Предустановленные теги для фича-флагов',
  totalLabel: 'Тегов в системе',
  namePlaceholder: 'Например, Checkout',
  goToFlags: 'К фича-флагам',
  ...bootstrap.messages,
}

const urls = {
  flagsPage: bootstrap.urls?.flagsPage ?? '',
}

const canGoToFlags = computed(() => props.embedded || Boolean(urls.flagsPage))

const actions: ActionConfig = {
  tagList: bootstrap.actions.tagList ?? '',
  tagCreate: bootstrap.actions.tagCreate ?? '',
  tagUpdate: bootstrap.actions.tagUpdate ?? '',
  tagDelete: bootstrap.actions.tagDelete ?? '',
}

const tags = ref<TagItem[]>([])
const isListLoading = ref(true)
const isModalOpen = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const modalMode = ref<ModalMode>('create')
const editingId = ref<number>(0)
const listError = ref('')
const formErrors = ref<string[]>([])
const fieldErrors = reactive<FieldErrors>({ id: [], name: [] })
const formNotice = ref<{ type: NoticeType; text: string } | null>(null)
const notice = ref<{ type: NoticeType; text: string } | null>(null)

const form = reactive({
  name: '',
})

const totalTags = computed(() => `${tags.value.length}`)
const isEditMode = computed(() => modalMode.value === 'edit')
const modalTitle = computed(() => (isEditMode.value ? messages.editTitle : messages.createTitle))

void loadTags()

async function loadTags(): Promise<void> {
  isListLoading.value = true
  listError.value = ''

  try {
    const response = await runAction<{ items: TagItem[] }>(actions.tagList)
    tags.value = response.items ?? []
  } catch (error) {
    listError.value = extractErrorText(error, messages.loadError)
    showNotice('error', listError.value)
  } finally {
    isListLoading.value = false
  }
}

function openCreateModal(): void {
  if (!props.canWrite) {
    return
  }

  modalMode.value = 'create'
  editingId.value = 0
  resetForm()
  resetFieldErrors()
  formErrors.value = []
  formNotice.value = null
  isModalOpen.value = true
}

function openEditModal(tag: TagItem): void {
  modalMode.value = 'edit'
  editingId.value = tag.id
  form.name = tag.name
  resetFieldErrors()
  formErrors.value = []
  formNotice.value = null
  isModalOpen.value = true
}

function dismissModal(): void {
  if (isSaving.value || isDeleting.value) {
    return
  }

  isModalOpen.value = false
  formNotice.value = null
  formErrors.value = []
  resetFieldErrors()
}

async function submitForm(): Promise<void> {
  if (!props.canWrite || isSaving.value) {
    return
  }

  isSaving.value = true
  formErrors.value = []
  resetFieldErrors()
  formNotice.value = null

  try {
    if (isEditMode.value) {
      const response = await runAction<{ tag: TagItem }>(actions.tagUpdate, {
        id: editingId.value,
        name: form.name.trim(),
      })
      replaceTag(response.tag)
      form.name = response.tag.name
      formNotice.value = { type: 'success', text: messages.updatedSuccess }
    } else {
      const response = await runAction<{ tag: TagItem }>(actions.tagCreate, {
        name: form.name.trim(),
      })
      tags.value = [response.tag, ...tags.value]
      modalMode.value = 'edit'
      editingId.value = response.tag.id
      form.name = response.tag.name
      formNotice.value = { type: 'success', text: messages.createdSuccess }
    }
  } catch (error) {
    const errorState = extractFormErrorState(error, messages.genericError)
    formErrors.value = errorState.common
    fieldErrors.id = [...errorState.fields.id]
    fieldErrors.name = [...errorState.fields.name]
  } finally {
    isSaving.value = false
  }
}

async function deleteCurrentTag(): Promise<void> {
  if (!props.canWrite || !isEditMode.value || isDeleting.value || !confirm(messages.deleteConfirm)) {
    return
  }

  isDeleting.value = true
  formErrors.value = []

  try {
    await runAction(actions.tagDelete, { id: editingId.value })
    tags.value = tags.value.filter((item) => item.id !== editingId.value)
    isModalOpen.value = false
    showNotice('success', messages.deletedSuccess)
  } catch (error) {
    formErrors.value = extractErrorList(error, messages.genericError)
  } finally {
    isDeleting.value = false
  }
}

function goToFlags(): void {
  if (props.embedded) {
    emit('back')
    return
  }

  if (!urls.flagsPage) {
    return
  }

  window.location.href = urls.flagsPage
}

function replaceTag(tag: TagItem): void {
  const index = tags.value.findIndex((item) => item.id === tag.id)
  if (index === -1) {
    tags.value = [tag, ...tags.value]
    return
  }

  const next = [...tags.value]
  next[index] = tag
  tags.value = next
}

function resetForm(): void {
  form.name = ''
}

function resetFieldErrors(): void {
  fieldErrors.id = []
  fieldErrors.name = []
}

function showNotice(type: NoticeType, text: string): void {
  notice.value = { type, text }
  window.setTimeout(() => {
    if (notice.value?.text === text) {
      notice.value = null
    }
  }, 3500)
}

async function runAction<T>(action: string, data: Record<string, unknown> = {}): Promise<T> {
  if (!action || typeof BX?.ajax?.runAction !== 'function') {
    throw new Error(messages.genericError)
  }

  const response = await BX.ajax.runAction(action, { data })
  const actionErrors = collectErrorsFromPayload(response)

  if (response?.status && response.status !== 'success') {
    throw response
  }

  if (actionErrors.length > 0) {
    throw { errors: actionErrors }
  }

  return response.data as T
}

function extractFormErrorState(error: unknown, fallback: string): FormErrorState {
  const state: FormErrorState = {
    common: [],
    fields: { id: [], name: [] },
  }

  const items = collectErrorsFromPayload(error)
  for (const item of items) {
    const field = detectFieldFromErrorItem(item)
    if (field === null) {
      state.common.push(item.message)
      continue
    }

    state.fields[field].push(item.message)
  }

  if (
    state.common.length === 0
    && state.fields.id.length === 0
    && state.fields.name.length === 0
  ) {
    state.common = extractErrorList(error, fallback)
  }

  state.common = Array.from(new Set(state.common))
  state.fields.id = Array.from(new Set(state.fields.id))
  state.fields.name = Array.from(new Set(state.fields.name))

  return state
}

function detectFieldFromErrorItem(item: UiErrorItem): TagFieldKey | null {
  const fromCustomData = extractErrorField(item.customData)
  if (fromCustomData !== null) {
    return fromCustomData
  }

  const code = String(item.code ?? '').toUpperCase()
  if (code.includes('ID')) {
    return 'id'
  }
  if (code.includes('NAME')) {
    return 'name'
  }
  const normalizedMessage = item.message.toLowerCase()
  if (normalizedMessage.includes('идентификатор') || normalizedMessage.includes(' id')) {
    return 'id'
  }
  if (normalizedMessage.includes('назван') || normalizedMessage.includes('name')) {
    return 'name'
  }
  return null
}

function extractErrorField(customData: unknown): TagFieldKey | null {
  if (typeof customData === 'string') {
    try {
      return extractErrorField(JSON.parse(customData))
    } catch {
      return null
    }
  }

  if (typeof customData !== 'object' || customData === null) {
    return null
  }

  const data = customData as Record<string, unknown>
  const candidate = data.field ?? data.FIELD
  if (candidate === 'id' || candidate === 'name') {
    return candidate
  }

  return null
}

function extractErrorText(error: unknown, fallback: string): string {
  return extractErrorList(error, fallback).join(' ')
}

function extractErrorList(error: unknown, fallback: string): string[] {
  const collected = collectErrorsFromPayload(error)
  if (collected.length > 0) {
    return collected.map((item) => item.message).filter((item) => item !== '')
  }

  if (typeof error === 'object' && error !== null) {
    const errorMap = error as Record<string, unknown>
    if (typeof errorMap.message === 'string' && errorMap.message !== '') {
      return [errorMap.message]
    }
  }

  return [fallback]
}

function collectErrorsFromPayload(payload: unknown): UiErrorItem[] {
  const errors: UiErrorItem[] = []
  const visited = new Set<object>()
  const queue: unknown[] = [payload]

  while (queue.length > 0) {
    const current = queue.shift()
    if (current === undefined || current === null) {
      continue
    }

    const direct = normalizeErrorItems(current)
    if (direct.length > 0) {
      errors.push(...direct)
    }

    if (typeof current !== 'object') {
      continue
    }

    if (visited.has(current)) {
      continue
    }
    visited.add(current)

    if (Array.isArray(current)) {
      for (const item of current) {
        queue.push(item)
      }
      continue
    }

    const mapped = current as Record<string, unknown>
    const candidateLists: unknown[] = [
      mapped.errors,
      mapped.error,
      mapped.ERRORS,
      mapped.ERROR,
      mapped.data,
      mapped.response,
      mapped.answer,
      mapped.exception,
      mapped.ex,
      mapped.customData,
      mapped.CUSTOM_DATA,
      mapped.custom_data,
    ]

    for (const candidate of candidateLists) {
      if (candidate !== undefined) {
        queue.push(candidate)
      }
    }
  }

  return uniqueErrorItems(errors)
}

function normalizeErrorItems(candidate: unknown): UiErrorItem[] {
  if (Array.isArray(candidate)) {
    return candidate
      .map(normalizeErrorItem)
      .filter((item): item is UiErrorItem => item !== null)
  }

  const single = normalizeErrorItem(candidate)
  return single === null ? [] : [single]
}

function normalizeErrorItem(item: unknown): UiErrorItem | null {
  if (typeof item === 'string') {
    const message = item.trim()
    return message === '' ? null : { message }
  }

  if (typeof item !== 'object' || item === null) {
    return null
  }

  const data = item as Record<string, unknown>
  const messageRaw = data.message ?? data.MESSAGE ?? data.error_description ?? data.description
  const message = typeof messageRaw === 'string' ? messageRaw.trim() : ''
  if (message === '') {
    return null
  }

  return {
    message,
    code: (data.code ?? data.CODE) as string | number | undefined,
    customData: data.customData ?? data.CUSTOM_DATA,
  }
}

function uniqueErrorItems(items: UiErrorItem[]): UiErrorItem[] {
  const seen = new Set<string>()
  const unique: UiErrorItem[] = []

  for (const item of items) {
    const key = `${String(item.code ?? '')}:${item.message}`
    if (seen.has(key)) {
      continue
    }

    seen.add(key)
    unique.push(item)
  }

  return unique
}
</script>

<template>
  <section class="ff-app">
    <header class="ff-hero">
      <div class="ff-hero__content">
        <p v-if="messages.subtitle" class="ff-hero__eyebrow">
          {{ messages.subtitle }}
        </p>
        <div class="ff-hero__summary">
          <div>
            <div class="ff-hero__count">{{ totalTags }}</div>
            <div class="ff-hero__label">{{ messages.totalLabel }}</div>
          </div>
          <div class="ff-actions__group">
            <button type="button" class="ff-button ff-button--ghost" :disabled="!canGoToFlags" @click="goToFlags">
              {{ messages.goToFlags }}
            </button>
            <button v-if="canWrite" type="button" class="ff-button ff-button--primary" @click="openCreateModal">
              {{ messages.add }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <div v-if="notice" class="ff-notice" :class="`is-${notice.type}`">
      {{ notice.text }}
    </div>

    <section class="ff-panel">
      <div class="ff-panel__header">
        <div>
          <h2 class="ff-panel__title">{{ messages.panelTitle }}</h2>
          <p class="ff-panel__caption">{{ messages.panelCaption }}</p>
        </div>
      </div>

      <div v-if="isListLoading" class="ff-state">{{ messages.loading }}</div>
      <div v-else-if="listError" class="ff-state ff-state--error">{{ listError }}</div>
      <div v-else-if="tags.length === 0" class="ff-empty">
        <div class="ff-empty__title">{{ messages.empty }}</div>
        <button v-if="canWrite" type="button" class="ff-button ff-button--primary" @click="openCreateModal">
          {{ messages.add }}
        </button>
      </div>
      <div v-else class="ff-table-wrap">
        <table class="ff-table ff-table--tags">
          <thead>
            <tr>
              <th>{{ messages.name }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tag in tags" :key="tag.id">
              <td>
                <button
                  type="button"
                  class="ff-flag-cell__title"
                  :aria-label="messages.openDetail"
                  @click="openEditModal(tag)"
                >
                  {{ tag.name }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <AdminFooter />

    <transition name="ff-modal-fade">
      <div v-if="isModalOpen" class="ff-modal">
        <div class="ff-modal__dialog">
          <div class="ff-modal__header">
            <div>
              <h3 class="ff-modal__title">{{ modalTitle }}</h3>
            </div>
            <button type="button" class="ff-icon-button" :aria-label="messages.closeLabel" @click="dismissModal">
              ×
            </button>
          </div>

          <form class="ff-form" @submit.prevent="submitForm">
            <div v-if="formErrors.length" class="ff-form-errors">
              <div v-for="error in formErrors" :key="error">{{ error }}</div>
            </div>

            <div v-if="formNotice" class="ff-form-notice" :class="`is-${formNotice.type}`">
              {{ formNotice.text }}
            </div>

            <label v-if="canWrite" class="ff-field">
              <span class="ff-field__label">{{ messages.name }}</span>
              <input
                v-model="form.name"
                type="text"
                :class="['ff-input', 'ff-input--main', { 'is-invalid': fieldErrors.name.length > 0 }]"
                :placeholder="messages.namePlaceholder"
                :aria-invalid="fieldErrors.name.length > 0 ? 'true' : 'false'"
              />
              <div v-if="fieldErrors.name.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.name" :key="`name-${index}-${error}`">{{ error }}</div>
              </div>
            </label>
            <div v-else class="ff-field">
              <span class="ff-field__label">{{ messages.name }}</span>
              <span class="ff-field__value">{{ form.name || '—' }}</span>
            </div>

            <div v-if="canWrite" class="ff-actions">
              <div class="ff-actions__group">
                <button
                  v-if="isEditMode"
                  type="button"
                  class="ff-button ff-button--danger"
                  :disabled="isSaving || isDeleting"
                  @click="deleteCurrentTag"
                >
                  {{ messages.delete }}
                </button>
              </div>
              <div class="ff-actions__group">
                <button type="button" class="ff-button ff-button--ghost" :disabled="isSaving || isDeleting" @click="dismissModal">
                  {{ messages.cancel }}
                </button>
                <button type="submit" class="ff-button ff-button--primary" :disabled="isSaving || isDeleting">
                  {{ messages.save }}
                </button>
              </div>
            </div>
            <div v-else class="ff-actions">
              <div></div>
              <div class="ff-actions__group">
                <button type="button" class="ff-button ff-button--ghost" @click="dismissModal">
                  {{ messages.closeLabel }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </section>
</template>
