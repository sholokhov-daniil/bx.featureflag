<script setup lang="ts">
import type { FeatureFlagItem, StrategyTypeItem } from '@/types/featureFlag'
import { getFlagRemovalState } from '@/utils/featureFlagDates'
import { Loc } from '@/utils/localization'
import ToggleSwitch from './ToggleSwitch.vue'
import '../assets/styles/featureFlagsTable.css'
import '../assets/styles/table.css'
import '../assets/styles/textUtilities.css'

const props = defineProps<{
  flags: FeatureFlagItem[]
  processingCodes: string[]
  strategyTypes: StrategyTypeItem[]
}>()

const emit = defineEmits<{
  edit: [code: string]
  toggle: [flag: FeatureFlagItem, value: boolean]
}>()

function getStrategyLabel(code: string): string {
  return props.strategyTypes.find((item) => item.code === code)?.name ?? code
}

function getFlagStrategyLabels(flag: FeatureFlagItem): string[] {
  return (flag.strategies ?? []).map((strategy) => getStrategyLabel(strategy.type))
}

function isProcessing(code: string): boolean {
  return props.processingCodes.includes(code)
}
</script>

<template>
  <div class="ff-table-wrap">
    <table class="ff-table">
      <thead>
        <tr>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_FIELD_NAME') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_NAME') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_TITLE') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT') }}</th>
          <th>{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT') }}</th>
          <th>Плановая дата удаления</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="flag in flags"
          :key="flag.code"
          :class="{
            'ff-row--expired': getFlagRemovalState(flag.removePlannedAt) === 'expired',
            'ff-row--today': getFlagRemovalState(flag.removePlannedAt) === 'today',
          }"
        >
          <td>
            <div class="ff-flag-cell">
              <button
                type="button"
                class="ff-flag-cell__title"
                :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_OPEN_DETAIL')"
                @click="emit('edit', flag.code)"
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
          <td>{{ flag.tag?.name }}</td>
          <td>
            <div v-if="getFlagStrategyLabels(flag).length > 0" class="ff-strategy-tags">
              <span
                v-for="(label, index) in getFlagStrategyLabels(flag)"
                :key="`${flag.code}-${index}-${label}`"
                class="ff-strategy-tag"
              >
                {{ label }}
              </span>
            </div>
            <span v-else class="ff-muted">{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_ALL') }}</span>
          </td>
          <td>
            <ToggleSwitch
              :checked="flag.enabled"
              :disabled="isProcessing(flag.code)"
              :label-on="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON')"
              :label-off="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')"
              @change="emit('toggle', flag, $event)"
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
          <td class="ff-nowrap">{{ flag.removePlannedAt }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
