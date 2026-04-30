<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import ToggleSwitch from './components/ToggleSwitch.vue'

interface FeatureFlagUser {
  id: number
  title: string
  url: string
}

interface FeatureFlagItem {
  code: string
  name: string
  description: string
  enabled: boolean
  createdAt: string
  updatedAt: string
  createdBy: FeatureFlagUser
}

interface FeatureFlagForm {
  code: string
  name: string
  description: string
  enabled: boolean
}

interface BootstrapConfig {
  actions: Record<string, string>
  messages: Record<string, string>
}

interface ActionConfig {
  list: string
  get: string
  create: string
  update: string
  delete: string
  toggle: string
}

type NoticeType = 'success' | 'error'
type ModalMode = 'create' | 'edit'
type FormFieldKey = 'code' | 'name' | 'description' | 'enabled'

interface FieldErrors {
  code: string[]
  name: string[]
  description: string[]
  enabled: string[]
}

interface FormErrorState {
  common: string[]
  fields: FieldErrors
}

const bootstrap: BootstrapConfig = window.SholokhovFeatureFlagAdmin ?? {
  actions: {},
  messages: {},
}

const messages = {
  subtitle: '',
  add: 'Добавить флаг',
  createTitle: 'Новый фича-флаг',
  editTitle: 'Настройки фича-флага',
  save: 'Сохранить',
  cancel: 'Отменить',
  delete: 'Удалить',
  loading: 'Загрузка...',
  empty: 'Фича-флаги не найдены',
  name: 'Название',
  code: 'Код',
  description: 'Описание',
  enabled: 'Включен',
  createdBy: 'Создал',
  createdAt: 'Дата создания',
  updatedAt: 'Обновлён',
  statusOn: 'Включен',
  statusOff: 'Выключен',
  deleteConfirm: 'Удалить флаг?',
  createdSuccess: 'Флаг успешно создан',
  updatedSuccess: 'Флаг успешно обновлён',
  deletedSuccess: 'Флаг успешно удалён',
  toggleSuccess: 'Статус флага обновлён',
  genericError: 'Не удалось выполнить операцию',
  loadError: 'Не удалось загрузить список фич',
  newFlagLabel: 'Новая фича',
  hintLabel: 'Описание фичи',
  closeLabel: 'Закрыть',
  openDetail: 'Открыть настройки',
  panelTitle: 'Фича-флаги',
  panelCaption: 'Управление состоянием и параметрами через контроллер',
  totalLabel: 'Флагов в системе',
  descriptionPlaceholder: '',
  namePlaceholder: '',
  codePlaceholder: '',
  ...bootstrap.messages,
}

const actions: ActionConfig = {
  list: bootstrap.actions.list ?? '',
  get: bootstrap.actions.get ?? '',
  create: bootstrap.actions.create ?? '',
  update: bootstrap.actions.update ?? '',
  delete: bootstrap.actions.delete ?? '',
  toggle: bootstrap.actions.toggle ?? '',
}

const flags = ref<FeatureFlagItem[]>([])
const isListLoading = ref(true)
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const modalMode = ref<ModalMode>('create')
const editingCode = ref('')
const processingCodes = ref<string[]>([])
const formErrors = ref<string[]>([])
const formNotice = ref<{ type: NoticeType; text: string } | null>(null)
const listError = ref('')
const notice = ref<{ type: NoticeType; text: string } | null>(null)
const shouldCloseOnOverlayClick = ref(false)
const fieldErrors = reactive<FieldErrors>({
  code: [],
  name: [],
  description: [],
  enabled: [],
})

const form = reactive<FeatureFlagForm>({
  code: '',
  name: '',
  description: '',
  enabled: false,
})

const detailMeta = reactive({
  createdBy: { id: 0, title: '', url: '' } as FeatureFlagUser,
  createdAt: '',
  updatedAt: '',
})

const isEditMode = computed(() => modalMode.value === 'edit')
const modalTitle = computed(() => isEditMode.value ? messages.editTitle : messages.createTitle)
const totalFlags = computed(() => `${flags.value.length}`)

void loadFlags()

async function loadFlags(): Promise<void> {
  isListLoading.value = true
  listError.value = ''

  try {
    const response = await runAction<{ items: FeatureFlagItem[] }>(actions.list)
    flags.value = response.items ?? []
  } catch (error) {
    listError.value = extractErrorText(error, messages.loadError)
    showNotice('error', listError.value)
  } finally {
    isListLoading.value = false
  }
}

function openCreateModal(): void {
  modalMode.value = 'create'
  editingCode.value = ''
  resetForm()
  clearMeta()
  formErrors.value = []
  resetFieldErrors()
  formNotice.value = null
  isModalLoading.value = false
  isModalOpen.value = true
}

async function openEditModal(code: string): Promise<void> {
  modalMode.value = 'edit'
  editingCode.value = code
  resetForm()
  clearMeta()
  formErrors.value = []
  resetFieldErrors()
  formNotice.value = null
  isModalLoading.value = true
  isModalOpen.value = true

  try {
    const response = await runAction<{ flag: FeatureFlagItem }>(actions.get, { code })
    hydrateForm(response.flag)
  } catch (error) {
    isModalOpen.value = false
    showNotice('error', extractErrorText(error, messages.genericError))
  } finally {
    isModalLoading.value = false
  }
}

function closeModal(force = false): void {
  if (!force && (isSaving.value || isDeleting.value)) {
    return
  }

  isModalOpen.value = false
  isModalLoading.value = false
  formErrors.value = []
  resetFieldErrors()
  formNotice.value = null
}

function dismissModal(): void {
  closeModal()
}

function armOverlayClose(): void {
  shouldCloseOnOverlayClick.value = true
}

function disarmOverlayClose(): void {
  shouldCloseOnOverlayClick.value = false
}

function handleOverlayClick(): void {
  if (!shouldCloseOnOverlayClick.value) {
    return
  }

  shouldCloseOnOverlayClick.value = false
  dismissModal()
}

async function submitForm(): Promise<void> {
  if (isSaving.value) {
    return
  }

  isSaving.value = true
  formErrors.value = []
  resetFieldErrors()
  formNotice.value = null

  const payload = {
    code: isEditMode.value ? editingCode.value : form.code.trim(),
    name: form.name.trim(),
    description: form.description.trim(),
    enabled: form.enabled,
  }

  const action = isEditMode.value ? actions.update : actions.create

  try {
    const response = await runAction<{ flag: FeatureFlagItem }>(action, payload)

    if (isEditMode.value) {
      replaceFlag(response.flag)
      hydrateForm(response.flag)
      formNotice.value = { type: 'success', text: messages.updatedSuccess }
    } else {
      flags.value = [response.flag, ...flags.value]
      modalMode.value = 'edit'
      hydrateForm(response.flag)
      formNotice.value = { type: 'success', text: messages.createdSuccess }
    }

    formErrors.value = []
    resetFieldErrors()
  } catch (error) {
    const errorState = extractFormErrorState(error, messages.genericError)
    formErrors.value = errorState.common
    applyFieldErrors(errorState.fields)
    formNotice.value = null
  } finally {
    isSaving.value = false
  }
}

async function deleteCurrentFlag(): Promise<void> {
  if (!isEditMode.value || isDeleting.value || !confirm(messages.deleteConfirm)) {
    return
  }

  isDeleting.value = true
  formErrors.value = []

  try {
    await runAction(actions.delete, { code: editingCode.value })
    flags.value = flags.value.filter((item) => item.code !== editingCode.value)
    closeModal(true)
    showNotice('success', messages.deletedSuccess)
  } catch (error) {
    formErrors.value = extractErrorList(error, messages.genericError)
  } finally {
    isDeleting.value = false
  }
}

async function toggleFlag(flag: FeatureFlagItem, value: boolean): Promise<void> {
  if (isProcessing(flag.code)) {
    return
  }

  processingCodes.value = [...processingCodes.value, flag.code]

  try {
    const response = await runAction<{ flag: FeatureFlagItem }>(actions.toggle, {
      code: flag.code,
      enabled: value,
    })

    replaceFlag(response.flag)
    showNotice('success', messages.toggleSuccess)
  } catch (error) {
    showNotice('error', extractErrorText(error, messages.genericError))
  } finally {
    processingCodes.value = processingCodes.value.filter((item) => item !== flag.code)
  }
}

function hydrateForm(flag: FeatureFlagItem): void {
  editingCode.value = flag.code
  form.code = flag.code
  form.name = flag.name
  form.description = flag.description
  form.enabled = flag.enabled
  detailMeta.createdBy = flag.createdBy
  detailMeta.createdAt = flag.createdAt
  detailMeta.updatedAt = flag.updatedAt
}

function resetForm(): void {
  form.code = ''
  form.name = ''
  form.description = ''
  form.enabled = false
}

function clearMeta(): void {
  detailMeta.createdBy = { id: 0, title: '', url: '' }
  detailMeta.createdAt = ''
  detailMeta.updatedAt = ''
}

function replaceFlag(flag: FeatureFlagItem): void {
  const index = flags.value.findIndex((item) => item.code === flag.code)
  if (index === -1) {
    flags.value = [flag, ...flags.value]
    return
  }

  const next = [...flags.value]
  next[index] = flag
  flags.value = next
}

function resetFieldErrors(): void {
  fieldErrors.code = []
  fieldErrors.name = []
  fieldErrors.description = []
  fieldErrors.enabled = []
}

function applyFieldErrors(errors: FieldErrors): void {
  fieldErrors.code = [...errors.code]
  fieldErrors.name = [...errors.name]
  fieldErrors.description = [...errors.description]
  fieldErrors.enabled = [...errors.enabled]
}

function showNotice(type: NoticeType, text: string): void {
  notice.value = { type, text }
  window.setTimeout(() => {
    if (notice.value?.text === text) {
      notice.value = null
    }
  }, 3500)
}

function isProcessing(code: string): boolean {
  return processingCodes.value.includes(code)
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

function extractErrorList(error: unknown, fallback: string): string[] {
  const collected = collectErrorsFromPayload(error)
  if (collected.length > 0) {
    return collected
      .map((item) => item.message)
      .filter((item) => item !== '')
  }

  if (typeof error === 'object' && error !== null) {
    const errorMap = error as Record<string, unknown>

    if (typeof errorMap.message === 'string' && errorMap.message !== '') {
      return [errorMap.message]
    }
  }

  return [fallback]
}

function extractFormErrorState(error: unknown, fallback: string): FormErrorState {
  const state: FormErrorState = {
    common: [],
    fields: {
      code: [],
      name: [],
      description: [],
      enabled: [],
    },
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
    && state.fields.code.length === 0
    && state.fields.name.length === 0
    && state.fields.description.length === 0
    && state.fields.enabled.length === 0
  ) {
    state.common = extractErrorList(error, fallback)
  }

  state.common = Array.from(new Set(state.common))
  state.fields.code = Array.from(new Set(state.fields.code))
  state.fields.name = Array.from(new Set(state.fields.name))
  state.fields.description = Array.from(new Set(state.fields.description))
  state.fields.enabled = Array.from(new Set(state.fields.enabled))

  return state
}

interface UiErrorItem {
  message: string
  code?: string | number
  customData?: unknown
}

function detectFieldFromErrorItem(item: UiErrorItem): FormFieldKey | null {
  const fromCustomData = extractErrorField(item.customData)
  if (fromCustomData !== null) {
    return fromCustomData
  }

  const code = String(item.code ?? '').toUpperCase()
  if (code.includes('CODE')) {
    return 'code'
  }
  if (code.includes('NAME')) {
    return 'name'
  }
  if (code.includes('DESCRIPTION')) {
    return 'description'
  }
  if (code.includes('ENABLED')) {
    return 'enabled'
  }

  const normalizedMessage = item.message.toLowerCase()
  if (normalizedMessage.includes('код') || normalizedMessage.includes('code')) {
    return 'code'
  }
  if (normalizedMessage.includes('назван') || normalizedMessage.includes('name')) {
    return 'name'
  }
  if (normalizedMessage.includes('описан') || normalizedMessage.includes('description')) {
    return 'description'
  }
  if (normalizedMessage.includes('активн') || normalizedMessage.includes('enabled') || normalizedMessage.includes('статус')) {
    return 'enabled'
  }

  return null
}

function extractErrorField(customData: unknown): FormFieldKey | null {
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
  const candidate = data.field ?? data.FIELD ?? (typeof data.customData === 'object' && data.customData !== null
    ? (data.customData as Record<string, unknown>).field
    : null)

  if (candidate === 'code' || candidate === 'name' || candidate === 'description' || candidate === 'enabled') {
    return candidate
  }

  return null
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

function extractErrorText(error: unknown, fallback: string): string {
  return extractErrorList(error, fallback).join(' ')
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
            <div class="ff-hero__count">
              {{ totalFlags }}
            </div>
            <div class="ff-hero__label">{{ messages.totalLabel }}</div>
          </div>
          <button type="button" class="ff-button ff-button--primary" @click="openCreateModal">
            {{ messages.add }}
          </button>
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

      <div v-if="isListLoading" class="ff-state">
        {{ messages.loading }}
      </div>

      <div v-else-if="listError" class="ff-state ff-state--error">
        {{ listError }}
      </div>

      <div v-else-if="flags.length === 0" class="ff-empty">
        <div class="ff-empty__title">
          {{ messages.empty }}
        </div>
        <button type="button" class="ff-button ff-button--primary" @click="openCreateModal">
          {{ messages.add }}
        </button>
      </div>

      <div v-else class="ff-table-wrap">
        <table class="ff-table">
          <thead>
            <tr>
              <th>{{ messages.name }}</th>
              <th>{{ messages.enabled }}</th>
              <th>{{ messages.createdBy }}</th>
              <th>{{ messages.createdAt }}</th>
              <th>{{ messages.updatedAt }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="flag in flags" :key="flag.code">
              <td>
                <div class="ff-flag-cell">
                  <button
                    type="button"
                    class="ff-flag-cell__title"
                    :aria-label="messages.openDetail"
                    @click="openEditModal(flag.code)"
                  >
                    {{ flag.name }}
                  </button>
                  <span
                    v-if="flag.description"
                    class="ff-tooltip"
                    :data-tooltip="flag.description"
                    :aria-label="messages.hintLabel"
                    tabindex="0"
                  >
                    ?
                  </span>
                  <div class="ff-flag-cell__code">
                    {{ flag.code }}
                  </div>
                </div>
              </td>
              <td>
                <ToggleSwitch
                  :checked="flag.enabled"
                  :disabled="isProcessing(flag.code)"
                  :label-on="messages.statusOn"
                  :label-off="messages.statusOff"
                  @change="toggleFlag(flag, $event)"
                />
              </td>
              <td>
                <a
                  v-if="flag.createdBy.url"
                  class="ff-user-link"
                  :href="flag.createdBy.url"
                >
                  {{ flag.createdBy.title }}
                </a>
                <span v-else class="ff-muted">{{ messages.newFlagLabel }}</span>
              </td>
              <td class="ff-nowrap">{{ flag.createdAt }}</td>
              <td class="ff-nowrap">{{ flag.updatedAt }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <transition name="ff-modal-fade">
      <div
        v-if="isModalOpen"
        class="ff-modal"
        @mousedown.self="armOverlayClose"
        @click.self="handleOverlayClick"
      >
        <div class="ff-modal__dialog" @mousedown="disarmOverlayClose">
          <div class="ff-modal__header">
            <div>
              <h3 class="ff-modal__title">
                {{ modalTitle }}
              </h3>
            </div>
            <button type="button" class="ff-icon-button" :aria-label="messages.closeLabel" @click="dismissModal">
              ×
            </button>
          </div>

          <div v-if="isModalLoading" class="ff-state">
            {{ messages.loading }}
          </div>

          <form v-else class="ff-form" @submit.prevent="submitForm">
            <div v-if="formErrors.length" class="ff-form-errors">
              <div v-for="error in formErrors" :key="error">
                {{ error }}
              </div>
            </div>

            <div v-if="formNotice" class="ff-form-notice" :class="`is-${formNotice.type}`">
              {{ formNotice.text }}
            </div>

            <div class="ff-form__grid">
              <label class="ff-field">
                <span class="ff-field__label">{{ messages.name }}</span>
                <input
                  v-model="form.name"
                  type="text"
                  :class="['ff-input', 'ff-input--main', { 'is-invalid': fieldErrors.name.length > 0 }]"
                  :placeholder="messages.namePlaceholder"
                  :aria-invalid="fieldErrors.name.length > 0 ? 'true' : 'false'"
                />
                <div v-if="fieldErrors.name.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.name" :key="`name-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </label>

              <div class="ff-field">
                <span class="ff-field__label">{{ messages.code }}</span>
                <input
                  v-if="!isEditMode"
                  v-model="form.code"
                  type="text"
                  class="ff-input ff-input--code"
                  :placeholder="messages.codePlaceholder"
                  autocapitalize="off"
                  autocomplete="off"
                  spellcheck="false"
                />
                <div v-else class="ff-field__value ff-field__value--mono">
                  {{ editingCode }}
                </div>
                <div v-if="fieldErrors.code.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.code" :key="`code-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </div>

              <div class="ff-field">
                <span class="ff-field__label">{{ messages.enabled }}</span>
                <div class="ff-field__value">
                  <ToggleSwitch
                    :checked="form.enabled"
                    :disabled="isSaving || isDeleting"
                    :label-on="messages.statusOn"
                    :label-off="messages.statusOff"
                    @change="form.enabled = $event"
                  />
                </div>
                <div v-if="fieldErrors.enabled.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.enabled" :key="`enabled-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </div>

              <label class="ff-field ff-field--full">
                <span class="ff-field__label">{{ messages.description }}</span>
                <textarea
                  v-model="form.description"
                  :class="['ff-textarea', 'ff-textarea--main', { 'is-invalid': fieldErrors.description.length > 0 }]"
                  rows="5"
                  :placeholder="messages.descriptionPlaceholder"
                  :aria-invalid="fieldErrors.description.length > 0 ? 'true' : 'false'"
                ></textarea>
                <div v-if="fieldErrors.description.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.description" :key="`description-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </label>
            </div>

            <div v-if="isEditMode" class="ff-meta">
              <div class="ff-meta__item">
                <span class="ff-field__label">{{ messages.createdBy }}</span>
                <a
                  v-if="detailMeta.createdBy.url"
                  class="ff-user-link"
                  :href="detailMeta.createdBy.url"
                >
                  {{ detailMeta.createdBy.title }}
                </a>
                <span v-else class="ff-field__value">{{ detailMeta.createdBy.title || messages.newFlagLabel }}</span>
              </div>
              <div class="ff-meta__item">
                <span class="ff-field__label">{{ messages.createdAt }}</span>
                <span class="ff-field__value">{{ detailMeta.createdAt }}</span>
              </div>
              <div class="ff-meta__item">
                <span class="ff-field__label">{{ messages.updatedAt }}</span>
                <span class="ff-field__value">{{ detailMeta.updatedAt }}</span>
              </div>
            </div>

            <div class="ff-actions">
              <div class="ff-actions__group">
                <button
                  v-if="isEditMode"
                  type="button"
                  class="ff-button ff-button--danger"
                  :disabled="isSaving || isDeleting"
                  @click="deleteCurrentFlag"
                >
                  {{ messages.delete }}
                </button>
              </div>
              <div class="ff-actions__group">
                <button
                  type="button"
                  class="ff-button ff-button--ghost"
                  :disabled="isSaving || isDeleting"
                  @click="dismissModal"
                >
                  {{ messages.cancel }}
                </button>
                <button
                  type="submit"
                  class="ff-button ff-button--primary"
                  :disabled="isSaving || isDeleting"
                >
                  {{ messages.save }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </section>
</template>
