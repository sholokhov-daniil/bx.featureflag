<script setup lang="ts">
import { Loc } from '@/utils/localization'
import '../assets/styles/adminHero.css'
import '../assets/styles/buttons.css'

defineProps<{
  totalFlags: string
}>()

const emit = defineEmits<{
  create: []
  manageTags: []
}>()
</script>

<template>
  <header class="ff-hero">
    <div class="ff-hero__content">
      <p class="ff-hero__eyebrow">
        {{ Loc('SHOLOKHOV_FEATUREFLAG_PAGE_SUBTITLE') }}
      </p>
      <div class="ff-hero__summary">
        <div>
          <div class="ff-hero__count">
            {{ totalFlags }}
          </div>
          <div class="ff-hero__label">
            {{ Loc('SHOLOKHOV_FEATUREFLAG_TOTAL_LABEL') }}
          </div>
        </div>
        <div class="ff-actions__group">
          <button type="button" class="ff-button ff-button--ghost" @click="emit('manageTags')">
            {{ Loc('SHOLOKHOV_FEATUREFLAG_TAGS_MANAGE') }}
          </button>
          <button type="button" class="ff-button ff-button--primary" @click="emit('create')">
            {{ Loc('SHOLOKHOV_FEATUREFLAG_BTN_ADD') }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
