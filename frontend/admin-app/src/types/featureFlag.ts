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

export interface StrategyField {
  code: string
  type: 'text' | 'textarea'
  label: string
  placeholder?: string
  required?: boolean
  mask?: StrategyFieldMask
}

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
  fields: StrategyField[]
}

export interface BootstrapConfig {
  actions: Record<string, string>
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
}

export type NoticeType = 'success' | 'error'
export type ModalMode = 'create' | 'edit'
export type FormFieldKey = 'code' | 'name' | 'description' | 'enabled' | 'tagId' | 'strategies'
export type AdminView = 'flags' | 'tags'
export type FeatureFlagsDisplayMode = 'cards' | 'table'
export type FeatureFlagEditableField = 'code' | 'name' | 'description' | 'enabled' | 'tagId' | 'removePlannedAt'
export type RemovalState = 'expired' | 'today' | null

export interface FeatureFlagsDisplayOption {
  code: FeatureFlagsDisplayMode
  label: string
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
