<!-- components/Modal.vue -->
<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="show" class="modal-overlay" @click="handleOverlayClick">
        <div class="modal-container" :class="size" @click.stop>
          <!-- Header -->
          <div class="modal-header">
            <h2 class="modal-title">{{ title }}</h2>
            <button @click="close" class="modal-close-btn">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="modal-body">
            <slot name="body">
              <p v-if="message" class="modal-message">{{ message }}</p>
            </slot>
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <slot name="footer">
              <button
                v-if="cancelText"
                @click="handleCancel"
                class="btn-secondary"
              >
                {{ cancelText }}
              </button>
              <button
                v-if="confirmText"
                @click="handleConfirm"
                class="btn-primary"
                :class="{ 'btn-danger': danger }"
              >
                {{ confirmText }}
              </button>
            </slot>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { X } from 'lucide-vue-next'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Modal'
  },
  message: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  confirmText: {
    type: String,
    default: 'Confirmer'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  danger: {
    type: Boolean,
    default: false
  },
  closeOnOverlay: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:show', 'confirm', 'cancel', 'close'])

const close = () => {
  emit('update:show', false)
  emit('close')
}

const handleConfirm = () => {
  emit('confirm')
  close()
}

const handleCancel = () => {
  emit('cancel')
  close()
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    close()
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 1rem;
}

.modal-container {
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-container.sm { max-width: 28rem; }
.modal-container.md { max-width: 32rem; }
.modal-container.lg { max-width: 42rem; }
.modal-container.xl { max-width: 56rem; }

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

.modal-close-btn {
  padding: 0.25rem;
  border-radius: 0.5rem;
  color: #6b7280;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
  flex: 1;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

.btn-primary {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: white;
  background-color: #2563eb;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-danger {
  background-color: #dc2626;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}
</style>
