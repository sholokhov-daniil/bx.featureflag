<script setup lang="ts">
import { computed, ref } from 'vue'
import type {
  FieldErrors,
  FeatureFlagDetailMeta,
  FeatureFlagEditableField,
  FeatureFlagForm,
  FeatureFlagStrategyFormItem,
  FeatureTagItem,
  Notice,
  StrategyField,
  StrategyTypeItem,
} from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import { dateToServerFormat } from '@/utils/featureFlagDates'
import FeatureFlagStatusBadge from './FeatureFlagStatusBadge.vue'
import ToggleSwitch from './ToggleSwitch.vue'
import FeatureFlagMeta from './FeatureFlagMeta.vue'
import FeatureStrategiesReadonly from './FeatureStrategiesReadonly.vue'
import FeatureStrategiesEditor from './FeatureStrategiesEditor.vue'
import '../assets/styles/buttons.css'
import '../assets/styles/form.css'
import '../assets/styles/modal.css'
import '../assets/styles/state.css'

const props = defineProps<{
  canWrite: boolean
  detailMeta: FeatureFlagDetailMeta
  editingCode: string
  fieldErrors: FieldErrors
  form: FeatureFlagForm
  formErrors: string[]
  formNotice: Notice | null
  getStrategyFields: (code: string) => StrategyField[]
  hasStrategyTypes: boolean
  isDeleting: boolean
  isEditMode: boolean
  isLoading: boolean
  isOpen: boolean
  isSaving: boolean
  modalTitle: string
  strategyTypes: StrategyTypeItem[]
  tags: FeatureTagItem[]
}>()

const emit = defineEmits<{
  addStrategy: []
  changeStrategyType: [strategy: FeatureFlagStrategyFormItem, type: string]
  delete: []
  dismiss: []
  removeStrategy: [index: number]
  strategyFieldChange: [value: string, strategy: FeatureFlagStrategyFormItem, field: StrategyField]
  submit: []
  updateFormField: [field: FeatureFlagEditableField, value: string | boolean]
}>()

const shouldCloseOnOverlayClick = ref(false)
const selectedTagName = computed(() => props.tags.find((tag) => String(tag.id) === props.form.tagId)?.name
  ?? Loc('SHOLOKHOV_FEATUREFLAG_TAG_WITHOUT'))
const removePlannedAtText = computed(() => dateToServerFormat(props.form.removePlannedAt) ?? 'Не указана')

function armOverlayClose(): void {
  shouldCloseOnOverlayClick.value = true
}

function disarmOverlayClose(): void {
  shouldCloseOnOverlayClick.value = false
}

function handleOverlayClick(): void {
  if (!shouldCloseOnOverlayClick.value) {
    return
  }

  shouldCloseOnOverlayClick.value = false
  emit('dismiss')
}

function updateTextField(field: FeatureFlagEditableField, event: Event): void {
  const target = event.target as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null
  if (target === null) {
    return
  }

  emit('updateFormField', field, target.value)
}

function submit(): void {
  if (props.canWrite) {
    emit('submit')
  }
}
</script>

<template>
  <transition name="ff-modal-fade">
    <div
      v-if="isOpen"
      class="ff-modal"
      @mousedown.self="armOverlayClose"
      @click.self="handleOverlayClick"
    >
      <div class="ff-modal__dialog" @mousedown="disarmOverlayClose">
        <div class="ff-modal__header">
          <div>
            <h3 class="ff-modal__title">
              {{ modalTitle }}
            </h3>
          </div>
          <button
            type="button"
            class="ff-icon-button"
            :aria-label="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_CLOSE_LABEL')"
            @click="emit('dismiss')"
          >
            ×
          </button>
        </div>

        <div v-if="isLoading" class="ff-state">
          {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_LOADING') }}
        </div>

        <form v-else class="ff-form" @submit.prevent="submit">
          <div v-if="formErrors.length" class="ff-form-errors">
            <div v-for="error in formErrors" :key="error">
              {{ error }}
            </div>
          </div>

          <div v-if="formNotice" class="ff-form-notice" :class="`is-${formNotice.type}`">
            {{ formNotice.text }}
          </div>

          <div class="ff-form__grid">
            <div class="ff-field">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED') }}</span>
              <div class="ff-field__value">
                <ToggleSwitch
                  v-if="canWrite"
                  :checked="form.enabled"
                  :disabled="isSaving || isDeleting"
                  :label-on="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON')"
                  :label-off="Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')"
                  @change="emit('updateFormField', 'enabled', $event)"
                />
                <FeatureFlagStatusBadge v-else :enabled="form.enabled" />
              </div>
              <div v-if="fieldErrors.enabled.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.enabled" :key="`enabled-${index}-${error}`">
                  {{ error }}
                </div>
              </div>
            </div>

            <div class="ff-field">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CODE') }}</span>
              <input
                v-if="canWrite && !isEditMode"
                :value="form.code"
                type="text"
                class="ff-input ff-input--code"
                :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_CODE_PLACEHOLDER')"
                autocapitalize="off"
                autocomplete="off"
                spellcheck="false"
                @input="updateTextField('code', $event)"
              />
              <div v-else class="ff-field__value ff-field__value--mono">
                {{ editingCode || form.code }}
              </div>
              <div v-if="fieldErrors.code.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.code" :key="`code-${index}-${error}`">
                  {{ error }}
                </div>
              </div>
            </div>

            <label v-if="canWrite" class="ff-field">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_FIELD_NAME') }}</span>
              <input
                :value="form.name"
                type="text"
                :class="['ff-input', 'ff-input--main', { 'is-invalid': fieldErrors.name.length > 0 }]"
                :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_TAGS_NAME_PLACEHOLDER')"
                :aria-invalid="fieldErrors.name.length > 0 ? 'true' : 'false'"
                @input="updateTextField('name', $event)"
              />
              <div v-if="fieldErrors.name.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.name" :key="`name-${index}-${error}`">
                  {{ error }}
                </div>
              </div>
            </label>
            <div v-else class="ff-field">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_FIELD_NAME') }}</span>
              <span class="ff-field__value">{{ form.name || '—' }}</span>
            </div>

            <div class="ff-field">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_TAG') }}</span>
              <select
                v-if="canWrite"
                :value="form.tagId"
                :class="['ff-select', { 'is-invalid': fieldErrors.tagId.length > 0 }]"
                :disabled="isSaving || isDeleting"
                @change="updateTextField('tagId', $event)"
              >
                <option value="">{{ Loc('SHOLOKHOV_FEATUREFLAG_TAG_WITHOUT') }}</option>
                <option v-for="tagItem in tags" :key="tagItem.id" :value="String(tagItem.id)">
                  {{ tagItem.name }}
                </option>
              </select>
              <span v-else class="ff-field__value">{{ selectedTagName }}</span>
              <div v-if="fieldErrors.tagId.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.tagId" :key="`tagId-${index}-${error}`">
                  {{ error }}
                </div>
              </div>
            </div>

            <label v-if="canWrite" class="ff-field ff-field--full">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION') }}</span>
              <textarea
                :value="form.description"
                :class="['ff-textarea', 'ff-textarea--main', { 'is-invalid': fieldErrors.description.length > 0 }]"
                rows="5"
                :placeholder="Loc('SHOLOKHOV_FEATUREFLAG_DESCRIPTION_PLACEHOLDER')"
                :aria-invalid="fieldErrors.description.length > 0 ? 'true' : 'false'"
                @input="updateTextField('description', $event)"
              ></textarea>
              <div v-if="fieldErrors.description.length" class="ff-field-errors">
                <div v-for="(error, index) in fieldErrors.description" :key="`description-${index}-${error}`">
                  {{ error }}
                </div>
              </div>
            </label>
            <div v-else class="ff-field ff-field--full">
              <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION') }}</span>
              <span class="ff-field__value ff-field__value--multiline">{{ form.description || '—' }}</span>
            </div>

            <label v-if="canWrite" class="ff-field ff-field--full">
              <span class="ff-field__label">Плановая дата удаления</span>
              <input
                :value="form.removePlannedAt"
                type="date"
                class="ff-input ff-input--main"
                :disabled="isSaving || isDeleting"
                @input="updateTextField('removePlannedAt', $event)"
              />
            </label>
            <div v-else class="ff-field ff-field--full">
              <span class="ff-field__label">Плановая дата удаления</span>
              <span class="ff-field__value">{{ removePlannedAtText }}</span>
            </div>

            <FeatureStrategiesEditor
              v-if="canWrite"
              :disabled="isSaving || isDeleting"
              :field-errors="fieldErrors"
              :get-strategy-fields="getStrategyFields"
              :has-strategy-types="hasStrategyTypes"
              :strategies="form.strategies"
              :strategy-types="strategyTypes"
              @add="emit('addStrategy')"
              @remove="emit('removeStrategy', $event)"
              @change-type="(strategy, type) => emit('changeStrategyType', strategy, type)"
              @field-change="(value, strategy, field) => emit('strategyFieldChange', value, strategy, field)"
            />
            <FeatureStrategiesReadonly
              v-else
              :get-strategy-fields="getStrategyFields"
              :strategies="form.strategies"
              :strategy-types="strategyTypes"
            />
          </div>

          <FeatureFlagMeta v-if="isEditMode" :meta="detailMeta" />

          <div v-if="canWrite" class="ff-actions">
            <div class="ff-actions__group">
              <button
                v-if="isEditMode"
                type="button"
                class="ff-button ff-button--danger"
                :disabled="isSaving || isDeleting"
                @click="emit('delete')"
              >
                {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_DELETE') }}
              </button>
            </div>
            <div class="ff-actions__group">
              <button
                type="button"
                class="ff-button ff-button--ghost"
                :disabled="isSaving || isDeleting"
                @click="emit('dismiss')"
              >
                {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_CANCEL') }}
              </button>
              <button
                type="submit"
                class="ff-button ff-button--primary"
                :disabled="isSaving || isDeleting"
              >
                {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_BTN_SAVE') }}
              </button>
            </div>
          </div>
          <div v-else class="ff-actions">
            <div></div>
            <div class="ff-actions__group">
              <button type="button" class="ff-button ff-button--ghost" @click="emit('dismiss')">
                {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_CLOSE_LABEL') }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>
