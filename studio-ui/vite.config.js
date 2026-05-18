import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  base: '/studio/',
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/studio/api': 'http://127.0.0.1:8080',
      '/studio/login': 'http://127.0.0.1:8080',
    }
  }
})
