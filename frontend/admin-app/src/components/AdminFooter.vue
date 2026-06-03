<script setup lang="ts">
import '../assets/styles/adminFooter.css'

const authorEmail = 'sholokhovdaniil@yandex.ru'
const githubUrl = 'https://github.com/sholokhov-daniil'
const issuesUrl = 'https://github.com/sholokhov-daniil/bx.featureflag/issues'
const docsUrl = 'https://github.com/sholokhov-daniil/bx.featureflag/wiki'
</script>

<template>
  <footer class="ff-admin-footer">
    <div class="ff-admin-footer__content">
      <div class="ff-admin-footer__main">
        <div class="ff-admin-footer__text">
          Если нашли баг или есть идея для улучшения, напишите обращение в репозитории проекта.
        </div>
      </div>

      <nav class="ff-admin-footer__links" aria-label="Полезные ссылки">
        <a class="ff-admin-footer__link" :href="`mailto:${authorEmail}`">
          <svg class="ff-admin-footer__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16v12H4V6Zm1.8 1.7 6.2 4.4 6.2-4.4H5.8Zm12.7 8.6V9.4L12 14 5.5 9.4v6.9h13Z" />
          </svg>
          <span>{{ authorEmail }}</span>
        </a>

        <a class="ff-admin-footer__link" :href="githubUrl" target="_blank" rel="noopener noreferrer">
          <svg class="ff-admin-footer__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.8c-2.8.6-3.4-1.2-3.4-1.2-.5-1.1-1.1-1.4-1.1-1.4-.9-.6.1-.6.1-.6 1 0 1.6 1 1.6 1 .9 1.5 2.4 1.1 2.9.9.1-.6.4-1.1.7-1.3-2.2-.3-4.6-1.1-4.6-4.9 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.7 0 0 .9-.3 2.8 1a9.8 9.8 0 0 1 5 0c1.9-1.3 2.8-1 2.8-1 .5 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.8-2.3 4.6-4.6 4.9.4.3.7 1 .7 2v2.4c0 .3.2.6.7.5A10 10 0 0 0 12 2Z" />
          </svg>
          <span>GitHub</span>
        </a>

        <a class="ff-admin-footer__link" :href="issuesUrl" target="_blank" rel="noopener noreferrer">
          <svg class="ff-admin-footer__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2.8 2.8 12l9.2 9.2 9.2-9.2L12 2.8Zm0 2.4 6.8 6.8-6.8 6.8L5.2 12 12 5.2Zm-1 4h2v4.8h-2V9.2Zm0 6.2h2v1.8h-2v-1.8Z" />
          </svg>
          <span>Сообщить о баге</span>
        </a>

        <a class="ff-admin-footer__link" :href="docsUrl" target="_blank" rel="noopener noreferrer">
          <svg class="ff-admin-footer__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 3.5h10.2L20 7.3v13.2H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Zm9.4 1.8v2.8h2.8l-2.8-2.8ZM6 5.5v13h12V10h-4.6V5.5H6Zm2 6.2h8v1.6H8v-1.6Zm0 3.2h8v1.6H8v-1.6Zm0-6.4h4v1.6H8V8.5Z" />
          </svg>
          <span>Документация</span>
        </a>
      </nav>
    </div>
  </footer>
</template>
