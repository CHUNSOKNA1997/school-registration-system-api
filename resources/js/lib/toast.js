// Simple toast notification system
class ToastManager {
  constructor() {
    this.listeners = []
  }

  subscribe(listener) {
    this.listeners.push(listener)
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener)
    }
  }

  show(message, type = 'success') {
    this.listeners.forEach(listener => listener({ message, type, id: Date.now() }))
  }

  success(message) {
    this.show(message, 'success')
  }

  error(message) {
    this.show(message, 'error')
  }

  warning(message) {
    this.show(message, 'warning')
  }

  info(message) {
    this.show(message, 'info')
  }
}

export const toast = new ToastManager()
