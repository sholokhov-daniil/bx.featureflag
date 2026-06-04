export interface FeatureFlagUser {
  id: number
  title: string
  url: string
}

export interface FeatureTagItem {
  id: number
  name: string
}

export interface FeatureFlagItem {
  code: string
  name: string
  description: string
  enabled: boolean
  tagId: number | null
  tag: FeatureTagItem | null
  strategies: FeatureFlagStrategyItem[]
  createdAt: string
  updatedAt: string
  removePlannedAt: string
  createdBy: FeatureFlagUser
}

export interface FeatureFlagStrategyItem {
  type: string
  config: Record<string, unknown>
}

export interface FeatureFlagForm {
  code: string
  name: string
  description: string
  enabled: boolean
  tagId: string
  removePlannedAt: string
  strategies: FeatureFlagStrategyFormItem[]
}

export interface FeatureFlagStrategyFormItem {
  uid: string
  type: string
  config: Record<string, string>
}

export type StrategyFieldType = 'text' | 'textarea' | 'entity-selector'

export interface StrategyField {
  code: string
  type: StrategyFieldType
  label: string
  name?: string
  placeholder?: string
  required?: boolean
  mask?: StrategyFieldMask
  options?: StrategyEntitySelectorOptions
}

export interface StrategyEntitySelectorOptions {
  id?: string
  items?: StrategyEntitySelectorTagItem[]
  dialogOptions?: StrategyEntitySelectorDialogOptions
  multiple?: boolean
  readonly?: boolean
  locked?: boolean
  deselectable?: boolean
  showAddButton?: boolean
  showCreateButton?: boolean
  showTextBox?: boolean
  addButtonCaption?: string
  addButtonCaptionMore?: string
  createButtonCaption?: string
  placeholder?: string
  maxHeight?: number
  textBoxAutoHide?: boolean
  textBoxWidth?: string | number
  tagAvatar?: string
  tagMaxWidth?: number
  tagTextColor?: string
  tagBgColor?: string
  tagFontWeight?: string
}

export interface StrategyEntitySelectorDialogOptions {
  id?: string
  context?: string
  items?: StrategyEntitySelectorDialogItem[]
  selectedItems?: StrategyEntitySelectorDialogItem[]
  preselectedItems?: StrategyEntitySelectorItemId[]
  undeselectedItems?: StrategyEntitySelectorItemId[]
  entities?: StrategyEntitySelectorEntity[]
  popupOptions?: StrategyEntitySelectorPopupOptions
  multiple?: boolean
  preload?: boolean
  dropdownMode?: boolean
  enableSearch?: boolean
  hideOnSelect?: boolean
  hideOnDeselect?: boolean
  clearSearchOnSelect?: boolean
  width?: number
  height?: number
  autoHide?: boolean
  hideByEsc?: boolean
  showAvatars?: boolean
  compactView?: boolean
}

export interface StrategyEntitySelectorPopupOptions {
  overlay?: boolean | Record<string, unknown>
  bindOptions?: Record<string, unknown>
  targetContainer?: HTMLElement
  zIndexOptions?: StrategyEntitySelectorZIndexOptions
  events?: Record<string, (...args: unknown[]) => void>
  animation?: string | boolean | Record<string, unknown>
  className?: string
}

export interface StrategyEntitySelectorZIndexOptions {
  alwaysOnTop?: boolean | number
  overlay?: HTMLElement
  overlayGap?: number
  events?: Record<string, (...args: unknown[]) => void>
}

export interface StrategyEntitySelectorEntity {
  id: string
  options?: Record<string, unknown>
  dynamicLoad?: boolean
  dynamicSearch?: boolean
  searchFields?: Record<string, unknown>[]
}

export interface StrategyEntitySelectorDialogItem extends StrategyEntitySelectorTagItem {
  tabs?: string | string[]
  subtitle?: string
}

export interface StrategyEntitySelectorTagItem {
  id: string | number
  entityId: string
  entityType?: string
  title?: string
  avatar?: string
  textColor?: string
  bgColor?: string
  fontWeight?: string
  link?: string
  maxWidth?: number
  deselectable?: boolean
  animate?: boolean
  customData?: Record<string, unknown>
}

export type StrategyEntitySelectorItemId = [string, string | number]

export interface StrategyRegexMaskRule {
  pattern: string
  flags?: string
  replacement?: string
}

export interface StrategyRegexMask {
  type: 'regex'
  pattern?: string
  flags?: string
  replacement?: string
  rules?: StrategyRegexMaskRule[]
  inputMode?: StrategyFieldInputMode
}

export type StrategyFieldInputMode = 'none' | 'text' | 'tel' | 'url' | 'email' | 'numeric' | 'decimal' | 'search'
export type StrategyFieldMask = StrategyRegexMask

export interface StrategyTypeItem {
  code: string
  name: string
  description: string
  available: boolean
  unavailableReason: string
  fields: StrategyField[]
}

export interface BootstrapConfig {
  actions: Record<string, string>
  canWrite?: boolean
  viewOptions?: FeatureFlagsViewOptions
  urls?: Record<string, string>
}

export interface ActionConfig {
  list: string
  get: string
  create: string
  update: string
  delete: string
  toggle: string
  tagList: string
  strategyList: string
  saveViewOptions: string
}

export type NoticeType = 'success' | 'error'
export type ModalMode = 'create' | 'edit'
export type FormFieldKey = 'code' | 'name' | 'description' | 'enabled' | 'tagId' | 'strategies'
export type AdminView = 'flags' | 'tags'
export type FeatureFlagsDisplayMode = 'cards' | 'table'
export type FeatureFlagsFilterCode = 'all' | 'enabled' | 'disabled' | 'deleting' | 'expired'
export type FeatureFlagEditableField = 'code' | 'name' | 'description' | 'enabled' | 'tagId' | 'removePlannedAt'
export type RemovalState = 'expired' | 'today' | null

export interface FeatureFlagsDisplayOption {
  code: FeatureFlagsDisplayMode
  label: string
}

export interface FeatureFlagsViewOptions {
  displayMode?: FeatureFlagsDisplayMode
}

export interface FeatureFlagsFilterItem {
  code: FeatureFlagsFilterCode
  label: string
  count: number
  tone: 'blue' | 'green' | 'gray' | 'yellow' | 'red'
}

export interface Notice {
  type: NoticeType
  text: string
}

export interface FieldErrors {
  code: string[]
  name: string[]
  description: string[]
  enabled: string[]
  tagId: string[]
  strategies: string[]
}

export interface FormErrorState {
  common: string[]
  fields: FieldErrors
}

export interface FeatureFlagDetailMeta {
  createdBy: FeatureFlagUser
  createdAt: string
  updatedAt: string
}
