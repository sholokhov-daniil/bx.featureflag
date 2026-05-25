import { collectErrorsFromPayload } from '@/utils/apiErrors'

export async function runAction<T>(
  action: string,
  data: Record<string, unknown> = {},
  fallbackMessage: string,
): Promise<T> {
  if (!action || typeof BX?.ajax?.runAction !== 'function') {
    throw new Error(fallbackMessage)
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
