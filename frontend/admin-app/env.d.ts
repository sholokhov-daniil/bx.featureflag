/// <reference types="vite/client" />

declare global {
  const BX: any

  interface Window {
    SholokhovFeatureFlagAdmin?: {
      view?: 'flags' | 'tags'
      langId: string
      actions: Record<string, string>
      messages: Record<string, string>
      urls?: Record<string, string>
    }
  }
}

export {}
