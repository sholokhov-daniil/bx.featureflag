import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },
  build: {
    outDir: fileURLToPath(new URL('../../install/js/sholokhov/featureflag/admin/dist', import.meta.url)),
    emptyOutDir: true,
    cssCodeSplit: false,
    lib: {
      entry: fileURLToPath(new URL('./src/main.ts', import.meta.url)),
      formats: ['iife'],
      name: 'SholokhovFeatureFlagAdmin',
      fileName: () => 'app.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: 'app[extname]',
      },
    },
  },
  plugins: [vue()],
  resolve: {
    alias: {
      'vue': fileURLToPath(new URL('./node_modules/vue/dist/vue.runtime.esm-browser.prod.js', import.meta.url)),
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
})
