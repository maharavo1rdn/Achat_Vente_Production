<template>
  <div class="app-container">
    <SideBar />
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
          <component :is="Component" :key="$route.path" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import SideBar from './components/layout/SideBar.vue'
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 14px;
}

body {
  @apply bg-gray-50 text-gray-900 antialiased;
}

.app-container {
  @apply flex min-h-screen bg-gray-50;
}

.main-content {
  @apply flex-1 min-h-screen;
}

/* Page Fade Transition */
.page-fade-enter-active {
  transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

.page-fade-leave-active {
  transition: opacity 0.2s ease-in;
}

.page-fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.page-fade-leave-to {
  opacity: 0;
}

.page-fade-enter-to {
  opacity: 1;
  transform: translateY(0);
}

/* Typography */
h1 {
  @apply text-2xl font-bold tracking-tight;
}

h2 {
  @apply text-xl font-bold tracking-tight;
}

h3 {
  @apply text-lg font-semibold tracking-tight;
}

h4 {
  @apply text-base font-semibold;
}

/* Focus States */
button:focus-visible {
  @apply outline-none ring-2 ring-gray-900 ring-offset-2;
}

input:focus,
select:focus,
textarea:focus {
  @apply outline-none;
}

/* Scrollbar */
::-webkit-scrollbar {
  @apply w-2 h-2;
}

::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

::-webkit-scrollbar-thumb {
  @apply bg-gray-400 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-500;
}

/* Global Animations */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slideUp 0.3s ease-out;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

/* Loading Spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Pulse Animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>