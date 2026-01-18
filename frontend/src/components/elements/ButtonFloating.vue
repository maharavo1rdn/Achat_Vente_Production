<!-- src/components/elements/ButtonFloating.vue -->
<template>
  <button
    class="floating-btn"
    :class="btnClasses"
    @click="handleClick"
    :style="btnStyle"
    :title="tooltip"
  >
    <component :is="getIcon" class="floating-icon" />
    
    <span v-if="notificationCount > 0" class="notification-badge">
      {{ formattedCount }}
    </span>
    
    <span v-if="hasNotification && notificationCount === 0" class="notification-dot"></span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { 
  MessageCircle, 
  Bell, 
  AlertTriangle, 
  HelpCircle, 
  Settings 
} from 'lucide-vue-next'

const props = defineProps({
  type: {
    type: String,
    default: 'chat',
    validator: (value) => ['chat', 'notification', 'warning', 'help', 'settings'].includes(value)
  },
  position: {
    type: String,
    default: 'bottom-right',
    validator: (value) => [
      'top-left', 'top-right', 'bottom-left', 'bottom-right',
      'center-left', 'center-right'
    ].includes(value)
  },
  notificationCount: {
    type: Number,
    default: 0
  },
  hasNotification: {
    type: Boolean,
    default: false
  },
  tooltip: {
    type: String,
    default: ''
  },
  offsetX: {
    type: String,
    default: '20px'
  },
  offsetY: {
    type: String,
    default: '20px'
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  }
})

const emit = defineEmits(['click'])

// Classes CSS calculées
const btnClasses = computed(() => [
  `floating-${props.type}`,
  `floating-${props.size}`,
  { 'has-notification': props.hasNotification }
])

// Style de position calculé
const btnStyle = computed(() => {
  const positions = {
    'top-left': { top: props.offsetY, left: props.offsetX },
    'top-right': { top: props.offsetY, right: props.offsetX },
    'bottom-left': { bottom: props.offsetY, left: props.offsetX },
    'bottom-right': { bottom: props.offsetY, right: props.offsetX },
    'center-left': { top: '50%', left: props.offsetX, transform: 'translateY(-50%)' },
    'center-right': { top: '50%', right: props.offsetX, transform: 'translateY(-50%)' }
  }
  return positions[props.position]
})

// Icône selon le type
const getIcon = computed(() => {
  const icons = {
    chat: MessageCircle,
    notification: Bell,
    warning: AlertTriangle,
    help: HelpCircle,
    settings: Settings
  }
  return icons[props.type] || MessageCircle
})

// Format du compteur de notifications
const formattedCount = computed(() => {
  return props.notificationCount > 99 ? '99+' : props.notificationCount
})

const handleClick = () => {
  emit('click')
}
</script>

<style scoped>
.floating-btn {
  position: fixed;
  z-index: 40;
  border-radius: 50%;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease-in-out;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
  outline: none;
}

.floating-btn:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Tailles */
.floating-small {
  width: 40px;
  height: 40px;
}

.floating-medium {
  width: 48px;
  height: 48px;
}

.floating-large {
  width: 56px;
  height: 56px;
}

.floating-icon {
  width: 20px;
  height: 20px;
}

.floating-small .floating-icon {
  width: 16px;
  height: 16px;
}

.floating-large .floating-icon {
  width: 24px;
  height: 24px;
}

/* Types */
.floating-chat {
  background-color: #2563eb;
  color: white;
}

.floating-chat:hover {
  background-color: #1d4ed8;
}

.floating-notification {
  background-color: #7c3aed;
  color: white;
}

.floating-notification:hover {
  background-color: #6d28d9;
}

.floating-warning {
  background-color: #f59e0b;
  color: white;
}

.floating-warning:hover {
  background-color: #d97706;
}

.floating-help {
  background-color: #059669;
  color: white;
}

.floating-help:hover {
  background-color: #047857;
}

.floating-settings {
  background-color: #4b5563;
  color: white;
}

.floating-settings:hover {
  background-color: #374151;
}

/* Badge de notification */
.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background-color: #ef4444;
  color: white;
  font-size: 12px;
  line-height: 1;
  border-radius: 10px;
  min-width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  font-weight: 600;
  animation: pulse 2s infinite;
}

/* Dot de notification simple */
.notification-dot {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 12px;
  height: 12px;
  background-color: #ef4444;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

/* Animation au survol */
.floating-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.floating-btn:active {
  transform: scale(0.95);
}

/* Effet de pulse pour attirer l'attention */
.has-notification {
  animation: gentlePulse 2s infinite;
}

@keyframes gentlePulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>