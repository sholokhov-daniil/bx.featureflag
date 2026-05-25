<script setup lang="ts">
import { computed } from 'vue'
import '../assets/styles/featureFlagsDisplay.css'

const props = defineProps<{
  currentPage: number
  pageSize: number
  totalItems: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)))
const firstItem = computed(() => props.totalItems === 0 ? 0 : ((props.currentPage - 1) * props.pageSize) + 1)
const lastItem = computed(() => Math.min(props.currentPage * props.pageSize, props.totalItems))

const visiblePages = computed(() => {
  const pages: number[] = []
  const from = Math.max(1, props.currentPage - 2)
  const to = Math.min(totalPages.value, props.currentPage + 2)

  for (let page = from; page <= to; page += 1) {
    pages.push(page)
  }

  return pages
})
</script>

<template>
  <div class="ff-pagination">
    <div class="ff-pagination__summary">
      Показано {{ firstItem }}-{{ lastItem }} из {{ totalItems }}
    </div>

    <div class="ff-pagination__controls">
      <button
        type="button"
        class="ff-pagination__button"
        :disabled="currentPage <= 1"
        aria-label="Предыдущая страница"
        @click="emit('change', currentPage - 1)"
      >
        ‹
      </button>

      <button
        v-for="page in visiblePages"
        :key="page"
        type="button"
        :class="['ff-pagination__button', { 'is-active': page === currentPage }]"
        :aria-current="page === currentPage ? 'page' : undefined"
        @click="emit('change', page)"
      >
        {{ page }}
      </button>

      <button
        type="button"
        class="ff-pagination__button"
        :disabled="currentPage >= totalPages"
        aria-label="Следующая страница"
        @click="emit('change', currentPage + 1)"
      >
        ›
      </button>
    </div>
  </div>
</template>
