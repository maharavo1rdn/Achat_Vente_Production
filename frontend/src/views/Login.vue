<template>
  <div class="login-container">
    <!-- Background Animation -->
    <div class="background-animation">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>

    <!-- Login Card -->
    <div class="login-card">
      <!-- Logo Section -->
      <div class="logo-section">
        <div class="logo-circle">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
        </div>
        <h1 class="app-title">ERP Achat-Vente</h1>
        <p class="app-subtitle">Gestion Commerciale</p>
      </div>

      <!-- Form Section -->
      <form @submit.prevent="submit" class="login-form">
        <h2 class="form-title">Connexion</h2>
        <p class="form-subtitle">Accédez à votre espace de gestion</p>

        <!-- Email Input -->
        <div class="input-group">
          <label class="input-label">Adresse email</label>
          <div class="input-wrapper">
            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
            </svg>
            <input
              v-model="email"
              type="email"
              required
              placeholder="exemple@email.com"
              class="input-field"
              :class="{ 'input-error': error }"
            />
          </div>
        </div>

        <!-- Password Input -->
        <div class="input-group">
          <label class="input-label">Mot de passe</label>
          <div class="input-wrapper">
            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="••••••••"
              class="input-field"
              :class="{ 'input-error': error }"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="password-toggle"
            >
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="error-message">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>{{ error }}</span>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="loading"
          class="submit-btn"
          :class="{ 'loading': loading }"
        >
          <span v-if="!loading" class="btn-content">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            Se connecter
          </span>
          <span v-else class="btn-content">
            <svg class="spinner" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Connexion en cours...
          </span>
        </button>

        <!-- Footer Links -->
        <div class="form-footer">
          <a href="#" class="footer-link">Mot de passe oublié ?</a>
        </div>
      </form>

      <!-- Card Footer -->
      <div class="card-footer">
        <p class="footer-text">© 2024 ERP Achat-Vente. Tous droits réservés.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('admin@gmail.mg')
const password = ref('hash123')
const error = ref(null)
const loading = ref(false)
const showPassword = ref(false)

// If already authenticated, redirect to dashboard
if (localStorage.getItem('user')) {
  router.push({ name: 'dashboard' })
}

async function submit() {
  error.value = null
  loading.value = true

  try {
    const res = await fetch('/api/personnel/authenticate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value, password: password.value })
    })

    if (!res.ok) {
      const body = await res.json()
      error.value = body.error || 'Identifiants incorrects'
      loading.value = false
      return
    }

    const data = await res.json()
    localStorage.setItem('user', JSON.stringify(data))
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = 'Erreur de connexion. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  @apply min-h-screen flex items-center justify-center p-4 relative overflow-hidden;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* Background Animation */
.background-animation {
  @apply absolute inset-0 overflow-hidden;
}

.shape {
  @apply absolute rounded-full opacity-10;
  animation: float 20s infinite ease-in-out;
}

.shape-1 {
  @apply w-96 h-96 bg-blue-500;
  top: -10%;
  left: -10%;
  animation-delay: 0s;
}

.shape-2 {
  @apply w-80 h-80 bg-purple-500;
  top: 60%;
  right: -10%;
  animation-delay: 7s;
}

.shape-3 {
  @apply w-64 h-64 bg-pink-500;
  bottom: -5%;
  left: 50%;
  animation-delay: 14s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) rotate(0deg);
  }
  33% {
    transform: translate(30px, -50px) rotate(120deg);
  }
  66% {
    transform: translate(-20px, 20px) rotate(240deg);
  }
}

/* Login Card */
.login-card {
  @apply relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10;
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Logo Section */
.logo-section {
  @apply text-center p-8 pb-6 border-b border-gray-100;
}

.logo-circle {
  @apply w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-gray-900 to-gray-700 rounded-2xl flex items-center justify-center shadow-lg;
  animation: pulse 2s infinite;
}

.logo-circle svg {
  @apply text-white;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.app-title {
  @apply text-2xl font-bold text-gray-900 mb-1;
}

.app-subtitle {
  @apply text-sm text-gray-500;
}

/* Form Section */
.login-form {
  @apply p-8 space-y-6;
}

.form-title {
  @apply text-xl font-bold text-gray-900;
}

.form-subtitle {
  @apply text-sm text-gray-500 mb-6;
}

/* Input Group */
.input-group {
  @apply space-y-2;
}

.input-label {
  @apply block text-sm font-medium text-gray-700;
}

.input-wrapper {
  @apply relative;
}

.input-icon {
  @apply absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none;
}

.input-field {
  @apply w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg text-sm bg-white;
  @apply focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none;
  @apply transition-all duration-200;
}

.input-field:focus {
  @apply shadow-md;
}

.input-error {
  @apply border-red-500 focus:ring-red-500 focus:border-red-500;
}

.password-toggle {
  @apply absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors;
}

/* Error Message */
.error-message {
  @apply flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600;
  animation: shake 0.4s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

/* Submit Button */
.submit-btn {
  @apply w-full py-3 px-6 bg-gray-900 text-white rounded-lg font-medium;
  @apply hover:bg-gray-800 active:scale-95;
  @apply transition-all duration-200 shadow-lg hover:shadow-xl;
  @apply disabled:opacity-70 disabled:cursor-not-allowed;
}

.submit-btn.loading {
  @apply cursor-wait;
}

.btn-content {
  @apply flex items-center justify-center gap-2;
}

.spinner {
  @apply w-5 h-5 animate-spin;
}

/* Form Footer */
.form-footer {
  @apply text-center;
}

.footer-link {
  @apply text-sm text-gray-600 hover:text-gray-900 transition-colors;
}

/* Card Footer */
.card-footer {
  @apply p-6 pt-4 border-t border-gray-100 text-center;
}

.footer-text {
  @apply text-xs text-gray-500;
}

/* Responsive */
@media (max-width: 640px) {
  .login-card {
    @apply rounded-xl;
  }

  .logo-section,
  .login-form {
    @apply p-6;
  }

  .app-title {
    @apply text-xl;
  }
}
</style>