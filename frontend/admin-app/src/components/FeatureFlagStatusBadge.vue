<script setup lang="ts">
import { Loc } from '@/utils/localization'
import '../assets/styles/statusBadge.css'

defineProps<{
  enabled: boolean
}>()
</script>

<template>
  <span :class="['ff-status-badge', enabled ? 'is-enabled' : 'is-disabled']">
    {{ enabled ? Loc('SHOLOKHOV_FEATUREFLAG_STATUS_ON') : Loc('SHOLOKHOV_FEATUREFLAG_STATUS_OFF') }}
  </span>
</template>
