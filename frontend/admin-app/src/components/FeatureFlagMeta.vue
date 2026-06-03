<script setup lang="ts">
import type { FeatureFlagDetailMeta } from '@/types/featureFlag'
import { Loc } from '@/utils/localization'
import '../assets/styles/featureFlagMeta.css'
import '../assets/styles/form.css'
import '../assets/styles/textUtilities.css'

defineProps<{
  meta: FeatureFlagDetailMeta
}>()
</script>

<template>
  <div class="ff-meta">
    <div class="ff-meta__item">
      <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY') }}</span>
      <a
        v-if="meta.createdBy.url"
        class="ff-user-link"
        :href="meta.createdBy.url"
      >
        {{ meta.createdBy.title }}
      </a>
      <span v-else class="ff-field__value">
        {{ meta.createdBy.title || Loc('SHOLOKHOV_FEATUREFLAG_NEW_FLAG_LABEL') }}
      </span>
    </div>
    <div class="ff-meta__item">
      <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT') }}</span>
      <span class="ff-field__value">{{ meta.createdAt }}</span>
    </div>
    <div class="ff-meta__item">
      <span class="ff-field__label">{{ Loc('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT') }}</span>
      <span class="ff-field__value">{{ meta.updatedAt }}</span>
    </div>
  </div>
</template>
