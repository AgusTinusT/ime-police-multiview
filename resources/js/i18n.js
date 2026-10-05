import { createI18n } from 'vue-i18n';
import idMain from './locales/id.json';
import updatesIdLocale from './locales/updates.json';
import enMain from './locales/en.json';
import updatesEnLocale from './locales/updates.en.json';

const i18n = createI18n({
  legacy: false, // Wajib false agar kompatibel dengan Vue 3 Composition API / <script setup>
  locale: 'id',  // Bahasa default saat pertama kali dibuka
  fallbackLocale: 'id', // Bahasa cadangan jika kunci tidak ada
  messages: {
    id: {
      ...idMain,
      updates: updatesIdLocale
    },
    en: {
      ...enMain,
      updates: updatesEnLocale
    }
  }
});

export default i18n;
