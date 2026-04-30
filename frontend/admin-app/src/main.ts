import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import TagsApp from './TagsApp.vue'

function mountApp(): void {
  const container = document.getElementById('sholokhov-featureflag-admin-app')
  if (!container) {
    return
  }

  const view = window.SholokhovFeatureFlagAdmin?.view
  const RootComponent = view === 'tags' ? TagsApp : App

  createApp(RootComponent).mount(container)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountApp, { once: true })
} else {
  mountApp()
}
