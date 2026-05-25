import type { RemovalState } from '@/types/featureFlag'

export function dateToInputFormat(value: string): string {
  const match = value.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)

  if (match === null) {
    return ''
  }

  return `${match[3]}-${match[2]}-${match[1]}`
}

export function dateToServerFormat(value: string): string | null {
  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/)

  if (match === null) {
    return null
  }

  return `${match[3]}.${match[2]}.${match[1]}`
}

export function getFlagRemovalState(removePlannedAt: string): RemovalState {
  if (!removePlannedAt) {
    return null
  }

  const match = removePlannedAt.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)

  if (match === null) {
    return null
  }

  const removeDate = new Date(
    Number(match[3]),
    Number(match[2]) - 1,
    Number(match[1]),
  )

  const now = new Date()

  removeDate.setHours(0, 0, 0, 0)
  now.setHours(0, 0, 0, 0)

  const removeTime = removeDate.getTime()
  const currentTime = now.getTime()

  if (removeTime < currentTime) {
    return 'expired'
  }

  if (removeTime === currentTime) {
    return 'today'
  }

  return null
}
