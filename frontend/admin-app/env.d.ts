/// <reference types="vite/client" />

declare global {
  const BX: any

  interface Window {
    SholokhovFeatureFlagAdmin?: {
      view?: 'flags' | 'tags'
      langId: string
      canWrite?: boolean
      actions: Record<string, string>
      messages: Record<string, string>
      viewOptions?: {
        displayMode?: 'cards' | 'table'
      }
      urls?: Record<string, string>
    }
  }
}

export {}
