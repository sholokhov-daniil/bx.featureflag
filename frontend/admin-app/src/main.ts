import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'

function mountApp(): void {
  const container = document.getElementById('sholokhov-featureflag-admin-app')
  if (!container) {
    return
  }

  createApp(App).mount(container)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountApp, { once: true })
} else {
  mountApp()
}
