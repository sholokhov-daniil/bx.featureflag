<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { runAction } from '@/api/bitrixActions'
import AdminFooter from '@/components/AdminFooter.vue'
import FeatureFlagModal from '@/components/FeatureFlagModal.vue'
import FeatureFlagsHero from '@/components/FeatureFlagsHero.vue'
import FeatureFlagsPanel from '@/components/FeatureFlagsPanel.vue'
import NoticeMessage from '@/components/NoticeMessage.vue'
import type {
  ActionConfig,
  BootstrapConfig,
  FeatureFlagDetailMeta,
  FeatureFlagEditableField,
  FeatureFlagForm,
  FeatureFlagItem,
  FeatureFlagStrategyFormItem,
  FeatureFlagStrategyItem,
  FeatureFlagUser,
  FeatureFlagsDisplayMode,
  FeatureFlagsDisplayOption,
  FeatureFlagsFilterCode,
  FeatureFlagsFilterItem,
  FeatureTagItem,
  FieldErrors,
  ModalMode,
  Notice,
  NoticeType,
  StrategyField,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { extractErrorList, extractErrorText } from '@/utils/apiErrors'
import { dateToInputFormat, dateToServerFormat, getFlagRemovalState } from '@/utils/featureFlagDates'
import { createFieldErrors, extractFeatureFlagFormErrorState } from '@/utils/featureFlagErrors'
import { Loc } from '@/utils/localization'
import '../assets/styles/adminShell.css'

const FLAGS_PAGE_SIZE = 10

const props = defineProps<{
  canWrite: boolean
}>()

const emit = defineEmits<{
  manageTags: []
}>()

const bootstrap: BootstrapConfig = window.SholokhovFeatureFlagAdmin ?? {
  actions: {},
}

const actions: ActionConfig = {
  list: bootstrap.actions.list ?? '',
  get: bootstrap.actions.get ?? '',
  create: bootstrap.actions.create ?? '',
  update: bootstrap.actions.update ?? '',
  delete: bootstrap.actions.delete ?? '',
  toggle: bootstrap.actions.toggle ?? '',
  tagList: bootstrap.actions.tagList ?? '',
  strategyList: bootstrap.actions.strategyList ?? '',
  saveViewOptions: bootstrap.actions.saveViewOptions ?? '',
}

const flags = ref<FeatureFlagItem[]>([])
const tags = ref<FeatureTagItem[]>([])
const strategyTypes = ref<StrategyTypeItem[]>([])
const isListLoading = ref(true)
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const modalMode = ref<ModalMode>('create')
const editingCode = ref('')
const processingCodes = ref<string[]>([])
const formErrors = ref<string[]>([])
const formNotice = ref<Notice | null>(null)
const listError = ref('')
const notice = ref<Notice | null>(null)
const displayMode = ref<FeatureFlagsDisplayMode>(normalizeDisplayMode(bootstrap.viewOptions?.displayMode))
const activeFilter = ref<FeatureFlagsFilterCode>('all')
const searchQuery = ref('')
const currentPage = ref(1)
const fieldErrors = reactive<FieldErrors>(createFieldErrors())

const displayOptions: FeatureFlagsDisplayOption[] = [
  { code: 'cards', label: 'Карточки' },
  { code: 'table', label: 'Таблица' },
]

const form = reactive<FeatureFlagForm>({
  code: '',
  name: '',
  description: '',
  enabled: false,
  availableInJs: false,
  tagId: '',
  removePlannedAt: '',
  strategies: [],
})

const detailMeta = reactive<FeatureFlagDetailMeta>({
  createdBy: createEmptyUser(),
  createdAt: '',
  updatedAt: '',
})

const isEditMode = computed(() => modalMode.value === 'edit')
const modalTitle = computed(() => isEditMode.value ? Loc('SHOLOKHOV_FEATUREFLAG_POPUP_EDIT_TITLE') : Loc('SHOLOKHOV_FEATUREFLAG_POPUP_CREATE_TITLE'))
const totalFlags = computed(() => `${flags.value.length}`)
const hasStrategyTypes = computed(() => strategyTypes.value.some(isStrategyTypeAvailable))
const filteredFlags = computed(() => flags.value
  .filter((flag) => isFlagMatchedByFilter(flag, activeFilter.value))
  .filter((flag) => isFlagMatchedBySearch(flag, searchQuery.value)))
const filterItems = computed<FeatureFlagsFilterItem[]>(() => [
  { code: 'all', label: 'Все', count: flags.value.length, tone: 'blue' },
  { code: 'enabled', label: 'Включенные', count: countFlagsByFilter('enabled'), tone: 'green' },
  { code: 'disabled', label: 'Выключенные', count: countFlagsByFilter('disabled'), tone: 'gray' },
  { code: 'deleting', label: 'Удаляющиеся', count: countFlagsByFilter('deleting'), tone: 'yellow' },
  { code: 'expired', label: 'Просроченные', count: countFlagsByFilter('expired'), tone: 'red' },
])
const totalPages = computed(() => Math.max(1, Math.ceil(filteredFlags.value.length / FLAGS_PAGE_SIZE)))
const paginatedFlags = computed(() => {
  const start = (currentPage.value - 1) * FLAGS_PAGE_SIZE
  return filteredFlags.value.slice(start, start + FLAGS_PAGE_SIZE)
})

let strategyUid = 0

watch(() => flags.value.length, () => {
  setCurrentPage(currentPage.value)
})

watch(() => filteredFlags.value.length, () => {
  setCurrentPage(currentPage.value)
})

watch([activeFilter, searchQuery], () => {
  setCurrentPage(1)
})

void loadFlags()
void loadTags()
void loadStrategyTypes()

async function loadFlags(): Promise<void> {
  isListLoading.value = true
  listError.value = ''

  try {
    const response = await runAdminAction<{ items: FeatureFlagItem[] }>(actions.list)
    flags.value = response.items ?? []
  } catch (error) {
    listError.value = extractErrorText(error, genericErrorMessage())
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
    const response = await runAdminAction<{ items: FeatureTagItem[] }>(actions.tagList)
    tags.value = response.items ?? []
  } catch {
    tags.value = []
  }
}

async function loadStrategyTypes(): Promise<void> {
  if (!actions.strategyList) {
    strategyTypes.value = []
    return
  }

  try {
    const response = await runAdminAction<{ items: StrategyTypeItem[] }>(actions.strategyList)
    strategyTypes.value = response.items ?? []
  } catch {
    strategyTypes.value = []
  }
}

function openCreateModal(): void {
  if (!props.canWrite) {
    return
  }

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
    const response = await runAdminAction<{ flag: FeatureFlagItem }>(actions.get, { code })
    hydrateForm(response.flag)
  } catch (error) {
    isModalOpen.value = false
    showNotice('error', extractErrorText(error, genericErrorMessage()))
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

async function submitForm(): Promise<void> {
  if (!props.canWrite || isSaving.value) {
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
    availableInJs: form.availableInJs,
    tagId: form.tagId,
    removePlannedAt: dateToServerFormat(form.removePlannedAt),
    strategies: serializeStrategies(),
  }

  const action = isEditMode.value ? actions.update : actions.create

  try {
    const response = await runAdminAction<{ flag: FeatureFlagItem }>(action, payload)

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
    const errorState = extractFeatureFlagFormErrorState(error, genericErrorMessage())
    formErrors.value = errorState.common
    applyFieldErrors(errorState.fields)
    formNotice.value = null
  } finally {
    isSaving.value = false
  }
}

async function deleteCurrentFlag(): Promise<void> {
  if (!props.canWrite || !isEditMode.value || isDeleting.value || !confirm(Loc('SHOLOKHOV_FEATUREFLAG_TAGS_CONFIRM_DELETE'))) {
    return
  }

  isDeleting.value = true
  formErrors.value = []

  try {
    await runAdminAction(actions.delete, { code: editingCode.value })
    flags.value = flags.value.filter((item) => item.code !== editingCode.value)
    closeModal(true)
    showNotice('success', Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_DELETED'))
  } catch (error) {
    formErrors.value = extractErrorList(error, genericErrorMessage())
  } finally {
    isDeleting.value = false
  }
}

async function toggleFlag(flag: FeatureFlagItem, value: boolean): Promise<void> {
  if (!props.canWrite || isProcessing(flag.code)) {
    return
  }

  processingCodes.value = [...processingCodes.value, flag.code]

  try {
    const response = await runAdminAction<{ flag: FeatureFlagItem }>(actions.toggle, {
      code: flag.code,
      enabled: value,
    })

    replaceFlag(response.flag)
    showNotice('success', Loc('SHOLOKHOV_FEATUREFLAG_MSG_STATUS_UPDATED'))
  } catch (error) {
    showNotice('error', extractErrorText(error, genericErrorMessage()))
  } finally {
    processingCodes.value = processingCodes.value.filter((item) => item !== flag.code)
  }
}

function updateFormField(field: FeatureFlagEditableField, value: string | boolean): void {
  if (!props.canWrite) {
    return
  }

  switch (field) {
    case 'enabled':
      form.enabled = Boolean(value)
      break
    case 'availableInJs':
      form.availableInJs = Boolean(value)
      break
    case 'code':
      form.code = String(value)
      break
    case 'name':
      form.name = String(value)
      break
    case 'description':
      form.description = String(value)
      break
    case 'tagId':
      form.tagId = String(value)
      break
    case 'removePlannedAt':
      form.removePlannedAt = String(value)
      break
  }
}

function hydrateForm(flag: FeatureFlagItem): void {
  editingCode.value = flag.code
  form.code = flag.code
  form.name = flag.name
  form.description = flag.description
  form.enabled = flag.enabled
  form.availableInJs = flag.availableInJs
  form.removePlannedAt = dateToInputFormat(flag.removePlannedAt)
  form.tagId = flag.tagId ? String(flag.tagId) : ''
  form.strategies = (flag.strategies ?? []).map((strategy) => createStrategyFormItem(strategy.type, strategy.config))
  detailMeta.createdBy = flag.createdBy
  detailMeta.createdAt = flag.createdAt
  detailMeta.updatedAt = flag.updatedAt
}

function resetForm(): void {
  form.code = ''
  form.name = ''
  form.description = ''
  form.enabled = false
  form.availableInJs = false
  form.tagId = ''
  form.removePlannedAt = ''
  form.strategies = []
}

function clearMeta(): void {
  detailMeta.createdBy = createEmptyUser()
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
  applyFieldErrors(createFieldErrors())
}

function applyFieldErrors(errors: FieldErrors): void {
  fieldErrors.code = [...errors.code]
  fieldErrors.name = [...errors.name]
  fieldErrors.description = [...errors.description]
  fieldErrors.enabled = [...errors.enabled]
  fieldErrors.availableInJs = [...errors.availableInJs]
  fieldErrors.tagId = [...errors.tagId]
  fieldErrors.strategies = [...errors.strategies]
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
  emit('manageTags')
}

function changeDisplayMode(mode: FeatureFlagsDisplayMode): void {
  if (displayMode.value === mode) {
    return
  }

  displayMode.value = mode
  void saveDisplayMode(mode)
}

async function saveDisplayMode(mode: FeatureFlagsDisplayMode): Promise<void> {
  if (!actions.saveViewOptions) {
    return
  }

  try {
    await runAdminAction(actions.saveViewOptions, {
      displayMode: mode,
    })
  } catch (error) {
    showNotice('error', extractErrorText(error, 'Не удалось сохранить способ отображения'))
  }
}

function normalizeDisplayMode(mode: unknown): FeatureFlagsDisplayMode {
  return mode === 'table' ? 'table' : 'cards'
}

function changeFilter(filter: FeatureFlagsFilterCode): void {
  activeFilter.value = filter
}

function changeSearchQuery(query: string): void {
  searchQuery.value = query
}

function setCurrentPage(page: number): void {
  currentPage.value = Math.min(Math.max(1, page), totalPages.value)
}

function countFlagsByFilter(filter: FeatureFlagsFilterCode): number {
  return flags.value.filter((flag) => isFlagMatchedByFilter(flag, filter)).length
}

function isFlagMatchedByFilter(flag: FeatureFlagItem, filter: FeatureFlagsFilterCode): boolean {
  switch (filter) {
    case 'enabled':
      return flag.enabled
    case 'disabled':
      return !flag.enabled
    case 'deleting':
      return isFlagDeleting(flag)
    case 'expired':
      return getFlagRemovalState(flag.removePlannedAt) === 'expired'
    case 'all':
    default:
      return true
  }
}

function isFlagDeleting(flag: FeatureFlagItem): boolean {
  return flag.removePlannedAt !== '' && getFlagRemovalState(flag.removePlannedAt) !== 'expired'
}

function isFlagMatchedBySearch(flag: FeatureFlagItem, query: string): boolean {
  const normalizedQuery = query.trim().toLowerCase()
  if (normalizedQuery === '') {
    return true
  }

  return [
    flag.name,
    flag.tag?.name ?? '',
  ].some((value) => value.toLowerCase().includes(normalizedQuery))
}

function addStrategy(): void {
  if (!props.canWrite) {
    return
  }

  const type = strategyTypes.value.find(isStrategyTypeAvailable)?.code ?? ''
  if (!type) {
    return
  }

  form.strategies = [...form.strategies, createStrategyFormItem(type)]
}

function removeStrategy(index: number): void {
  if (!props.canWrite) {
    return
  }

  form.strategies = form.strategies.filter((_, itemIndex) => itemIndex !== index)
}

function changeStrategyType(strategy: FeatureFlagStrategyFormItem, type: string): void {
  if (!props.canWrite) {
    return
  }

  const strategyType = getStrategyType(type)
  if (!isStrategyTypeAvailable(strategyType)) {
    return
  }

  strategy.type = type
  strategy.config = createDefaultStrategyConfig(type)
}

function getStrategyType(code: string): StrategyTypeItem | null {
  return strategyTypes.value.find((item) => item.code === code) ?? null
}

function isStrategyTypeAvailable(strategyType: StrategyTypeItem | null | undefined): boolean {
  return strategyType?.available !== false
}

function getStrategyFields(code: string): StrategyField[] {
  const strategyType = getStrategyType(code)

  return isStrategyTypeAvailable(strategyType) ? strategyType?.fields ?? [] : []
}

function createStrategyFormItem(type: string, config: Record<string, unknown> = {}): FeatureFlagStrategyFormItem {
  const normalizedConfig = createDefaultStrategyConfig(type)
  const fields = getStrategyFields(type)

  if (fields.length === 0) {
    for (const [key, value] of Object.entries(config)) {
      normalizedConfig[key] = formatStrategyConfigValue(value)
    }
  } else {
    for (const field of fields) {
      if (config[field.code] !== undefined) {
        normalizedConfig[field.code] = formatStrategyConfigValue(config[field.code])
      }
    }
  }

  return {
    uid: `strategy-${++strategyUid}`,
    type,
    config: normalizedConfig,
  }
}

function createDefaultStrategyConfig(type: string): Record<string, string> {
  const config: Record<string, string> = {}

  for (const field of getStrategyFields(type)) {
    config[field.code] = ''
  }

  return config
}

function formatStrategyConfigValue(value: unknown): string {
  if (Array.isArray(value)) {
    return value.map((item) => String(item)).join('\n')
  }

  if (value === null || value === undefined) {
    return ''
  }

  return String(value)
}

function serializeStrategies(): FeatureFlagStrategyItem[] {
  return form.strategies
    .filter((strategy) => strategy.type !== '')
    .map((strategy) => ({
      type: strategy.type,
      config: { ...strategy.config },
    }))
}

function handleStrategyFieldChange(
  value: string,
  strategy: FeatureFlagStrategyFormItem,
  field: StrategyField,
): void {
  if (!props.canWrite) {
    return
  }

  strategy.config[field.code] = value
}

function isProcessing(code: string): boolean {
  return processingCodes.value.includes(code)
}

function runAdminAction<T>(action: string, data: Record<string, unknown> = {}): Promise<T> {
  return runAction<T>(action, data, genericErrorMessage())
}

function genericErrorMessage(): string {
  return Loc('SHOLOKHOV_FEATUREFLAG_MSG_ERROR') || Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MSG_ERROR') || 'Не удалось выполнить операцию'
}

function createEmptyUser(): FeatureFlagUser {
  return {
    id: 0,
    title: '',
    url: '',
  }
}
</script>

<template>
  <section class="ff-app">
    <FeatureFlagsHero
      :can-write="canWrite"
      :total-flags="totalFlags"
      @create="openCreateModal"
      @manage-tags="openTagsPage"
    />

    <NoticeMessage :notice="notice" />

    <FeatureFlagsPanel
      :active-filter="activeFilter"
      :can-write="canWrite"
      :current-page="currentPage"
      :display-mode="displayMode"
      :display-options="displayOptions"
      :filter-items="filterItems"
      :flags="paginatedFlags"
      :is-loading="isListLoading"
      :list-error="listError"
      :page-size="FLAGS_PAGE_SIZE"
      :processing-codes="processingCodes"
      :search-query="searchQuery"
      :source-items="flags.length"
      :strategy-types="strategyTypes"
      :total-items="filteredFlags.length"
      @create="openCreateModal"
      @display-mode-change="changeDisplayMode"
      @edit="openEditModal"
      @filter-change="changeFilter"
      @page-change="setCurrentPage"
      @search-change="changeSearchQuery"
      @toggle="toggleFlag"
    />

    <AdminFooter />

    <FeatureFlagModal
      :detail-meta="detailMeta"
      :can-write="canWrite"
      :editing-code="editingCode"
      :field-errors="fieldErrors"
      :form="form"
      :form-errors="formErrors"
      :form-notice="formNotice"
      :get-strategy-fields="getStrategyFields"
      :has-strategy-types="hasStrategyTypes"
      :is-deleting="isDeleting"
      :is-edit-mode="isEditMode"
      :is-loading="isModalLoading"
      :is-open="isModalOpen"
      :is-saving="isSaving"
      :modal-title="modalTitle"
      :strategy-types="strategyTypes"
      :tags="tags"
      @add-strategy="addStrategy"
      @change-strategy-type="changeStrategyType"
      @delete="deleteCurrentFlag"
      @dismiss="dismissModal"
      @remove-strategy="removeStrategy"
      @strategy-field-change="handleStrategyFieldChange"
      @submit="submitForm"
      @update-form-field="updateFormField"
    />
  </section>
</template>
