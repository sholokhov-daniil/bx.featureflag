<script setup lang="ts">
import type { Notice } from '@/types/featureFlag'
import '../assets/styles/notice.css'

defineProps<{
  notice: Notice | null
}>()
</script>

<template>
  <div v-if="notice" class="ff-notice" :class="`is-${notice.type}`">
    {{ notice.text }}
  </div>
</template>
