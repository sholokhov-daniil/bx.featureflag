export interface UiErrorItem {
  message: string
  code?: string | number
  customData?: unknown
}

export function extractErrorText(error: unknown, fallback: string): string {
  return extractErrorList(error, fallback).join(' ')
}

export function extractErrorList(error: unknown, fallback: string): string[] {
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

export function collectErrorsFromPayload(payload: unknown): UiErrorItem[] {
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
