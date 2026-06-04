<script setup lang="ts">
import { computed } from 'vue'
import type { FeatureFlagItem, RemovalState, StrategyTypeItem } from '@/types/featureFlag'
import { getFlagRemovalState } from '@/utils/featureFlagDates'
import { Loc } from '@/utils/localization'
import FeatureFlagStatusBadge from './FeatureFlagStatusBadge.vue'
import ToggleSwitch from './ToggleSwitch.vue'
import '../assets/styles/featureFlagsCards.css'
import '../assets/styles/textUtilities.css'

const props = defineProps<{
  canWrite: boolean
  flag: FeatureFlagItem
  processingCodes: string[]
  strategyTypes: StrategyTypeItem[]
}>()

const emit = defineEmits<{
  edit: [code: string]
  toggle: [flag: FeatureFlagItem, value: boolean]
}>()

const removalState = computed(() => getFlagRemovalState(props.flag.removePlannedAt))
const strategyLabels = computed(() => (props.flag.strategies ?? []).map((strategy) => getStrategyLabel(strategy.type)))

function getStrategyLabel(code: string): string {
  return props.strategyTypes.find((item) => item.code === code)?.name ?? code
}

function getCardState(): RemovalState | 'disabled' | 'enabled' {
  return removalState.value ?? (props.flag.enabled ? 'enabled' : 'disabled')
}

function isProcessing(): boolean {
  return props.processingCodes.includes(props.flag.code)
}
</script>

<template>
  <article :class="['ff-flag-card', `ff-flag-card--${getCardState()}`]">
    <div class="ff-flag-card__body">
      <div class="ff-flag-card__top">
        <div class="ff-flag-card__title-group">
          <button
            type="button"
            class="ff-flag-card__title"
            :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_OPEN_DETAIL')"
            @click="emit('edit', flag.code)"
          >
            {{ flag.name }}
          </button>
          <div class="ff-flag-card__code">
            {{ flag.code }}
          </div>
        </div>

        <ToggleSwitch
          v-if="canWrite"
          :checked="flag.enabled"
          :disabled="isProcessing()"
          :label-on="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON')"
          :label-off="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')"
          @change="emit('toggle', flag, $event)"
        />
        <FeatureFlagStatusBadge v-else :enabled="flag.enabled" />
      </div>

      <p v-if="flag.description" class="ff-flag-card__description">
        {{ flag.description }}
      </p>

      <div class="ff-flag-card__details">
        <div class="ff-flag-card__detail">
          <span class="ff-flag-card__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_TAG') }}</span>
          <span v-if="flag.tag?.name" class="ff-flag-card__tag">{{ flag.tag.name }}</span>
          <span v-else class="ff-muted">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAG_WITHOUT') }}</span>
        </div>

        <div class="ff-flag-card__detail">
          <span class="ff-flag-card__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_TITLE') }}</span>
          <div v-if="strategyLabels.length > 0" class="ff-card-strategy-tags">
            <span
              v-for="(label, index) in strategyLabels"
              :key="`${flag.code}-${index}-${label}`"
              class="ff-card-strategy-tag"
            >
              {{ label }}
            </span>
          </div>
          <span v-else class="ff-muted">{{ Loc('SHOLOKHOV_FEATUREFLAG_STRATEGIES_ALL') }}</span>
        </div>
      </div>
    </div>

    <div class="ff-flag-card__footer">
      <div class="ff-flag-card__meta">
        <span class="ff-flag-card__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT') }}</span>
        <span class="ff-flag-card__value">{{ flag.createdAt }}</span>
        <a v-if="flag.createdBy.url" class="ff-user-link" :href="flag.createdBy.url">
          {{ flag.createdBy.title }}
        </a>
      </div>

      <div class="ff-flag-card__meta">
        <span class="ff-flag-card__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT') }}</span>
        <span class="ff-flag-card__value">{{ flag.updatedAt }}</span>
      </div>

      <div class="ff-flag-card__meta">
        <span class="ff-flag-card__label">Плановая дата удаления</span>
        <span
          :class="[
            'ff-flag-card__value',
            {
              'ff-flag-card__value--expired': removalState === 'expired',
              'ff-flag-card__value--today': removalState === 'today',
            },
          ]"
        >
          {{ flag.removePlannedAt || 'Не указана' }}
        </span>
      </div>

      <button
        type="button"
        class="ff-flag-card__menu"
        :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_OPEN_DETAIL')"
        @click="emit('edit', flag.code)"
      >
        ...
      </button>
    </div>
  </article>
</template>
