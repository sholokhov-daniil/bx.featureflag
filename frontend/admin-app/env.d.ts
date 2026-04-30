/// <reference types="vite/client" />

declare global {
  const BX: any

  interface Window {
    SholokhovFeatureFlagAdmin?: {
      langId: string
      actions: Record<string, string>
      messages: Record<string, string>
    }
  }
}

export {}
