<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Loc } from '@/utils/localization.ts';
import ToggleSwitch from './components/ToggleSwitch.vue'

interface FeatureFlagUser {
  id: number
  title: string
  url: string
}

interface FeatureTagItem {
  id: number
  name: string
}

interface FeatureFlagItem {
  code: string
  name: string
  description: string
  enabled: boolean
  tagId: number | null
  tag: FeatureTagItem | null
  createdAt: string
  updatedAt: string
  createdBy: FeatureFlagUser
}

interface FeatureFlagForm {
  code: string
  name: string
  description: string
  enabled: boolean
  tagId: string
}

interface BootstrapConfig {
  actions: Record<string, string>
  urls?: Record<string, string>
}

interface ActionConfig {
  list: string
  get: string
  create: string
  update: string
  delete: string
  toggle: string
  tagList: string
}

type NoticeType = 'success' | 'error'
type ModalMode = 'create' | 'edit'
type FormFieldKey = 'code' | 'name' | 'description' | 'enabled' | 'tagId'

interface FieldErrors {
  code: string[]
  name: string[]
  description: string[]
  enabled: string[]
  tagId: string[]
}

interface FormErrorState {
  common: string[]
  fields: FieldErrors
}

const bootstrap: BootstrapConfig = window.SholokhovFeatureFlagAdmin ?? {
  actions: {},
}


const urls = {
  tagsPage: bootstrap.urls?.tagsPage ?? '',
}

const actions: ActionConfig = {
  list: bootstrap.actions.list ?? '',
  get: bootstrap.actions.get ?? '',
  create: bootstrap.actions.create ?? '',
  update: bootstrap.actions.update ?? '',
  delete: bootstrap.actions.delete ?? '',
  toggle: bootstrap.actions.toggle ?? '',
  tagList: bootstrap.actions.tagList ?? '',
}

const flags = ref<FeatureFlagItem[]>([])
const tags = ref<FeatureTagItem[]>([])
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
  tagId: [],
})

const form = reactive<FeatureFlagForm>({
  code: '',
  name: '',
  description: '',
  enabled: false,
  tagId: '',
})

const detailMeta = reactive({
  createdBy: { id: 0, title: '', url: '' } as FeatureFlagUser,
  createdAt: '',
  updatedAt: '',
})

const isEditMode = computed(() => modalMode.value === 'edit')
const modalTitle = computed(() => isEditMode.value ? Loc('SHOLOKHOV_FEATUREFLAG_TAGS_POPUP_EDIT_TITLE') : Loc('SHOLOKHOV_FEATUREFLAG_TAGS_POPUP_CREATE_TITLE'))
const totalFlags = computed(() => `${flags.value.length}`)

void loadFlags()
void loadTags()

async function loadFlags(): Promise<void> {
  isListLoading.value = true
  listError.value = ''

  try {
    const response = await runAction<{ items: FeatureFlagItem[] }>(actions.list)
    flags.value = response.items ?? []
  } catch (error) {
    listError.value = extractErrorText(error, Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOAD_ERROR'))
    showNotice('error', listError.value)
  } finally {
    isListLoading.value = false
  }
}

async function loadTags(): Promise<void> {
  if (!actions.tagList) {
    tags.value = []
    return
  }

  try {
    const response = await runAction<{ items: FeatureTagItem[] }>(actions.tagList)
    tags.value = response.items ?? []
  } catch {
    tags.value = []
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
    showNotice('error', extractErrorText(error, Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR')))
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
    tagId: form.tagId,
  }

  const action = isEditMode.value ? actions.update : actions.create

  try {
    const response = await runAction<{ flag: FeatureFlagItem }>(action, payload)

    if (isEditMode.value) {
      replaceFlag(response.flag)
      hydrateForm(response.flag)
      formNotice.value = { type: 'success', text: Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_UPDATED') }
    } else {
      flags.value = [response.flag, ...flags.value]
      modalMode.value = 'edit'
      hydrateForm(response.flag)
      formNotice.value = { type: 'success', text: Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ADDED') }
    }

    formErrors.value = []
    resetFieldErrors()
  } catch (error) {
    const errorState = extractFormErrorState(error, Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR'))
    formErrors.value = errorState.common
    applyFieldErrors(errorState.fields)
    formNotice.value = null
  } finally {
    isSaving.value = false
  }
}

async function deleteCurrentFlag(): Promise<void> {
  if (!isEditMode.value || isDeleting.value || !confirm(Loc('SHOLOKHOV_FEATUREFLAG_TAGS_CONFIRM_DELETE'))) {
    return
  }

  isDeleting.value = true
  formErrors.value = []

  try {
    await runAction(actions.delete, { code: editingCode.value })
    flags.value = flags.value.filter((item) => item.code !== editingCode.value)
    closeModal(true)
    showNotice('success', Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_DELETED'))
  } catch (error) {
    formErrors.value = extractErrorList(error, Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR'))
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
    showNotice('success', Loc('SHOLOKHOV_FEATUREFLAG_MSG_STATUS_UPDATED'))
  } catch (error) {
    showNotice('error', extractErrorText(error, Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR')))
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
  form.tagId = flag.tagId ? String(flag.tagId) : ''
  detailMeta.createdBy = flag.createdBy
  detailMeta.createdAt = flag.createdAt
  detailMeta.updatedAt = flag.updatedAt
}

function resetForm(): void {
  form.code = ''
  form.name = ''
  form.description = ''
  form.enabled = false
  form.tagId = ''
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
  fieldErrors.tagId = []
}

function applyFieldErrors(errors: FieldErrors): void {
  fieldErrors.code = [...errors.code]
  fieldErrors.name = [...errors.name]
  fieldErrors.description = [...errors.description]
  fieldErrors.enabled = [...errors.enabled]
  fieldErrors.tagId = [...errors.tagId]
}

function showNotice(type: NoticeType, text: string): void {
  notice.value = { type, text }
  window.setTimeout(() => {
    if (notice.value?.text === text) {
      notice.value = null
    }
  }, 3500)
}

function openTagsPage(): void {
  if (!urls.tagsPage) {
    return
  }

  window.location.href = urls.tagsPage
}

function isProcessing(code: string): boolean {
  return processingCodes.value.includes(code)
}

async function runAction<T>(action: string, data: Record<string, unknown> = {}): Promise<T> {
  if (!action || typeof BX?.ajax?.runAction !== 'function') {
    throw new Error(Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR'))
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
      tagId: [],
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
    && state.fields.tagId.length === 0
  ) {
    state.common = extractErrorList(error, fallback)
  }

  state.common = Array.from(new Set(state.common))
  state.fields.code = Array.from(new Set(state.fields.code))
  state.fields.name = Array.from(new Set(state.fields.name))
  state.fields.description = Array.from(new Set(state.fields.description))
  state.fields.enabled = Array.from(new Set(state.fields.enabled))
  state.fields.tagId = Array.from(new Set(state.fields.tagId))

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
  if (code.includes('TAG_ID') || code.includes('TAGID') || code.includes('TAG')) {
    return 'tagId'
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
  if (normalizedMessage.includes('тег') || normalizedMessage.includes('tag')) {
    return 'tagId'
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

  if (candidate === 'tag_id') {
    return 'tagId'
  }

  if (candidate === 'code' || candidate === 'name' || candidate === 'description' || candidate === 'enabled' || candidate === 'tagId') {
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
        <p class="ff-hero__eyebrow">
          {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_PAGE_SUBTITLE') }}
        </p>
        <div class="ff-hero__summary">
          <div>
            <div class="ff-hero__count">
              {{ totalFlags }}
            </div>
            <div class="ff-hero__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_TOTAL_LABEL') }}</div>
          </div>
          <div class="ff-actions__group">
            <button type="button" class="ff-button ff-button--ghost" :disabled="!urls.tagsPage" @click="openTagsPage">
              {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MANAGE') }}
            </button>
            <button type="button" class="ff-button ff-button--primary" @click="openCreateModal">
              {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_ADD') }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <div v-if="notice" class="ff-notice" :class="`is-${notice.type}`">
      {{ notice.text }}
    </div>

    <section class="ff-panel">
      <div v-if="isListLoading" class="ff-state">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOADING') }}
      </div>

      <div v-else-if="listError" class="ff-state ff-state--error">
        {{ listError }}
      </div>

      <div v-else-if="flags.length === 0" class="ff-empty">
        <div class="ff-empty__title">
          {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOADING') }}
        </div>
        <button type="button" class="ff-button ff-button--primary" @click="openCreateModal">
          {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_ADD') }}
        </button>
      </div>

      <div v-else class="ff-table-wrap">
        <table class="ff-table">
          <thead>
            <tr>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_FIELD_NAME') }}</th>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_NAME') }}</th>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED') }}</th>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY') }}</th>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT') }}</th>
              <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="flag in flags" :key="flag.code">
              <td>
                <div class="ff-flag-cell">
                  <button
                    type="button"
                    class="ff-flag-cell__title"
                    :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_OPEN_DETAIL')"
                    @click="openEditModal(flag.code)"
                  >
                    {{ flag.name }}
                  </button>
                  <span
                    v-if="flag.description"
                    class="ff-tooltip"
                    :data-tooltip="flag.description"
                    :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_HINT_LABEL')"
                    tabindex="0"
                  >
                    ?
                  </span>
                  <div class="ff-flag-cell__code">
                    {{ flag.code }}
                  </div>
                </div>
              </td>
              <td>{{ flag?.tag?.name }}</td>
              <td>
                <ToggleSwitch
                  :checked="flag.enabled"
                  :disabled="isProcessing(flag.code)"
                  :label-on="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON')"
                  :label-off="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')"
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
                <span v-else class="ff-muted">{{ Loc('SHOLOKHOV_FEATUREFLAG_NEW_FLAG_LABEL') }}</span>
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
            <button type="button" class="ff-icon-button" :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_CLOSE_LABEL')" @click="dismissModal">
              ×
            </button>
          </div>

          <div v-if="isModalLoading" class="ff-state">
            {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOADING') }}
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
              <div class="ff-field">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED') }}</span>
                <div class="ff-field__value">
                  <ToggleSwitch
                      :checked="form.enabled"
                      :disabled="isSaving || isDeleting"
                      :label-on="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON')"
                      :label-off="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')"
                      @change="form.enabled = $event"
                  />
                </div>
                <div v-if="fieldErrors.enabled.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.enabled" :key="`enabled-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </div>

              <div class="ff-field">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CODE') }}</span>
                <input
                    v-if="!isEditMode"
                    v-model="form.code"
                    type="text"
                    class="ff-input ff-input--code"
                    :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_CODE_PLACEHOLDER')"
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

              <label class="ff-field">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_FIELD_NAME') }}</span>
                <input
                  v-model="form.name"
                  type="text"
                  :class="['ff-input', 'ff-input--main', { 'is-invalid': fieldErrors.name.length > 0 }]"
                  :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_NAME_PLACEHOLDER')"
                  :aria-invalid="fieldErrors.name.length > 0 ? 'true' : 'false'"
                />
                <div v-if="fieldErrors.name.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.name" :key="`name-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </label>

              <div class="ff-field">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_TAG') }}</span>
                <select
                  v-model="form.tagId"
                  :class="['ff-select', { 'is-invalid': fieldErrors.tagId.length > 0 }]"
                  :disabled="isSaving || isDeleting"
                >
                  <option value="">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAG_WITHOUT') }}</option>
                  <option v-for="tagItem in tags" :key="tagItem.id" :value="String(tagItem.id)">
                    {{ tagItem.name }}
                  </option>
                </select>
                <div v-if="fieldErrors.tagId.length" class="ff-field-errors">
                  <div v-for="(error, index) in fieldErrors.tagId" :key="`tagId-${index}-${error}`">
                    {{ error }}
                  </div>
                </div>
              </div>

              <label class="ff-field ff-field--full">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION') }}</span>
                <textarea
                  v-model="form.description"
                  :class="['ff-textarea', 'ff-textarea--main', { 'is-invalid': fieldErrors.description.length > 0 }]"
                  rows="5"
                  :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_DESCRIPTION_PLACEHOLDER')"
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
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY') }}</span>
                <a
                  v-if="detailMeta.createdBy.url"
                  class="ff-user-link"
                  :href="detailMeta.createdBy.url"
                >
                  {{ detailMeta.createdBy.title }}
                </a>
                <span v-else class="ff-field__value">{{ detailMeta.createdBy.title || Loc('SHOLOKHOV_FEATUREFLAG_NEW_FLAG_LABEL') }}</span>
              </div>
              <div class="ff-meta__item">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT') }}</span>
                <span class="ff-field__value">{{ detailMeta.createdAt }}</span>
              </div>
              <div class="ff-meta__item">
                <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT') }}</span>
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
                  {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_DELETE') }}
                </button>
              </div>
              <div class="ff-actions__group">
                <button
                  type="button"
                  class="ff-button ff-button--ghost"
                  :disabled="isSaving || isDeleting"
                  @click="dismissModal"
                >
                  {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_CANCEL') }}
                </button>
                <button
                  type="submit"
                  class="ff-button ff-button--primary"
                  :disabled="isSaving || isDeleting"
                >
                  {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_SAVE') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </section>
</template>
