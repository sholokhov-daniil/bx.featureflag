import type { StrategyFieldMask, StrategyRegexMaskRule } from '@/types/featureFlag'

export function applyStrategyFieldMask(value: string, mask?: StrategyFieldMask): string {
  if (mask?.type !== 'regex') {
    return value
  }

  let result = value
  for (const rule of getRegexMaskRules(mask)) {
    const pattern = rule.pattern.trim()
    if (pattern === '') {
      continue
    }

    try {
      result = result.replace(new RegExp(pattern, normalizeRegexFlags(rule.flags)), rule.replacement ?? '')
    } catch {
      continue
    }
  }

  return result
}

function getRegexMaskRules(mask: StrategyFieldMask): StrategyRegexMaskRule[] {
  if (Array.isArray(mask.rules) && mask.rules.length > 0) {
    return mask.rules
  }

  if (mask.pattern) {
    return [{
      pattern: mask.pattern,
      flags: mask.flags,
      replacement: mask.replacement,
    }]
  }

  return []
}

function normalizeRegexFlags(flags?: string): string {
  const value = flags ?? 'g'
  let result = ''

  for (const flag of value) {
    if ('gimsuy'.includes(flag) && !result.includes(flag)) {
      result += flag
    }
  }

  return result
}
