import type { FieldErrors, FormErrorState, FormFieldKey } from '@/types/featureFlag'
import { collectErrorsFromPayload, extractErrorList, type UiErrorItem } from '@/utils/apiErrors'

export function createFieldErrors(): FieldErrors {
  return {
    code: [],
    name: [],
    description: [],
    enabled: [],
    availableInJs: [],
    tagId: [],
    strategies: [],
  }
}

export function extractFeatureFlagFormErrorState(error: unknown, fallback: string): FormErrorState {
  const state: FormErrorState = {
    common: [],
    fields: createFieldErrors(),
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

  if (!hasFormErrors(state)) {
    state.common = extractErrorList(error, fallback)
  }

  state.common = Array.from(new Set(state.common))
  state.fields.code = Array.from(new Set(state.fields.code))
  state.fields.name = Array.from(new Set(state.fields.name))
  state.fields.description = Array.from(new Set(state.fields.description))
  state.fields.enabled = Array.from(new Set(state.fields.enabled))
  state.fields.availableInJs = Array.from(new Set(state.fields.availableInJs))
  state.fields.tagId = Array.from(new Set(state.fields.tagId))
  state.fields.strategies = Array.from(new Set(state.fields.strategies))

  return state
}

function hasFormErrors(state: FormErrorState): boolean {
  return (
    state.common.length > 0
    || state.fields.code.length > 0
    || state.fields.name.length > 0
    || state.fields.description.length > 0
    || state.fields.enabled.length > 0
    || state.fields.availableInJs.length > 0
    || state.fields.tagId.length > 0
    || state.fields.strategies.length > 0
  )
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
  if (code.includes('AVAILABLE_IN_JS') || code.includes('AVAILABLEINJS') || code.includes('JS')) {
    return 'availableInJs'
  }
  if (code.includes('ENABLED')) {
    return 'enabled'
  }
  if (code.includes('TAG_ID') || code.includes('TAGID') || code.includes('TAG')) {
    return 'tagId'
  }
  if (code.includes('STRATEG')) {
    return 'strategies'
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
  if (normalizedMessage.includes('availableinjs') || normalizedMessage.includes('available_in_js') || normalizedMessage.includes('js')) {
    return 'availableInJs'
  }
  if (normalizedMessage.includes('активн') || normalizedMessage.includes('enabled') || normalizedMessage.includes('статус')) {
    return 'enabled'
  }
  if (normalizedMessage.includes('тег') || normalizedMessage.includes('tag')) {
    return 'tagId'
  }
  if (normalizedMessage.includes('стратег') || normalizedMessage.includes('strategy')) {
    return 'strategies'
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

  if (candidate === 'availableInJs' || candidate === 'available_in_js' || candidate === 'AVAILABLE_IN_JS' || candidate === 'js') {
    return 'availableInJs'
  }

  if (candidate === 'tag_id') {
    return 'tagId'
  }

  if (candidate === 'strategy' || candidate === 'strategies') {
    return 'strategies'
  }

  if (candidate === 'code' || candidate === 'name' || candidate === 'description' || candidate === 'enabled' || candidate === 'availableInJs' || candidate === 'tagId') {
    return candidate
  }

  return null
}
