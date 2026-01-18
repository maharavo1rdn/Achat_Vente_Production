<!-- components/UniversalModal.vue -->
<template>
  <Modal
    :show="show"
    :title="modalConfig.title"
    :size="modalConfig.size"
    :confirm-text="modalConfig.confirmText"
    :cancel-text="modalConfig.cancelText"
    :danger="modalConfig.danger"
    @update:show="$emit('update:show', $event)"
    @confirm="handleConfirm"
    @cancel="handleCancel"
  >
    <template #body>
      <div v-if="type === 'suggestion'" class="suggestion-content">
        <div class="modal-icon">💡</div>
        <h4 class="modal-subtitle">{{ modalConfig.subtitle }}</h4>
        <div class="suggestions-list">
          <div v-for="(item, index) in content" :key="index" class="suggestion-item">
            {{ item }}
          </div>
        </div>
      </div>

      <div v-else-if="type === 'notification'" class="notification-content">
        <div class="modal-icon">🔔</div>
        <h4 class="modal-subtitle">{{ modalConfig.subtitle }}</h4>
        <div class="notifications-list">
          <div v-for="(notif, index) in content" :key="index" class="notification-item">
            <div class="notification-title">{{ notif.title }}</div>
            <div class="notification-message">{{ notif.message }}</div>
            <div class="notification-time">{{ notif.time }}</div>
          </div>
        </div>
      </div>

      <div v-else-if="type === 'warning'" class="warning-content">
        <div class="modal-icon">⚠️</div>
        <h4 class="modal-subtitle">{{ modalConfig.subtitle }}</h4>
        <div class="warning-message">
          {{ content }}
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'warning',
    validator: (value) => ['suggestion', 'notification', 'warning'].includes(value)
  },
  content: {
    type: [String, Array],
    default: ''
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  confirmText: {
    type: String,
    default: 'OK'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  }
})

const emit = defineEmits(['update:show', 'confirm', 'cancel'])

const modalConfig = computed(() => {
  const configs = {
    suggestion: {
      title: props.title || '💡 Suggestions',
      subtitle: props.subtitle || 'Suggestions pour améliorer votre gestion',
      size: 'md',
      confirmText: 'Appliquer',
      cancelText: 'Ignorer',
      danger: false
    },
    notification: {
      title: props.title || '🔔 Notifications',
      subtitle: props.subtitle || 'Vos dernières notifications',
      size: 'lg',
      confirmText: 'Marquer comme lu',
      cancelText: 'Fermer',
      danger: false
    },
    warning: {
      title: props.title || '⚠️ Attention',
      subtitle: props.subtitle || 'Action requise',
      size: 'sm',
      confirmText: props.confirmText,
      cancelText: props.cancelText,
      danger: true
    }
  }
  return configs[props.type] || configs.warning
})

const handleConfirm = () => {
  emit('confirm', props.content)
}

const handleCancel = () => {
  emit('cancel')
}
</script>

<style scoped>
.modal-icon {
  @apply text-2xl text-center mb-3;
}

.modal-subtitle {
  @apply text-lg font-semibold text-gray-700 mb-4 text-center;
}

.suggestions-list {
  @apply space-y-2;
}

.suggestion-item {
  @apply p-3 bg-blue-50 rounded-lg border border-blue-200 text-sm text-gray-700;
}

.notifications-list {
  @apply space-y-3 max-h-60 overflow-y-auto;
}

.notification-item {
  @apply p-3 bg-gray-50 rounded-lg border border-gray-200;
}

.notification-title {
  @apply font-semibold text-gray-800 text-sm;
}

.notification-message {
  @apply text-gray-600 text-sm mt-1;
}

.notification-time {
  @apply text-xs text-gray-500 mt-2;
}

.warning-message {
  @apply p-4 bg-orange-50 rounded-lg border border-orange-200 text-orange-800 text-center;
}
</style>
