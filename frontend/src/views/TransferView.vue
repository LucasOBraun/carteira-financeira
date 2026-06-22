<template>
  <AppLayout>
    <div class="card">
      <h1>Transferir</h1>
      <p v-if="message" class="success">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <form @submit.prevent="submit">
        <label for="recipient_email">E-mail do destinatário</label>
        <input id="recipient_email" v-model="recipientEmail" type="email" required />

        <label for="amount">Valor (R$)</label>
        <CurrencyInput id="amount" v-model="amount" required />

        <button type="submit" :disabled="loading">Enviar transferência</button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import CurrencyInput from '../components/CurrencyInput.vue'
import api, { ensureCsrfCookie } from '../services/api'

const recipientEmail = ref('')
const amount = ref('')
const loading = ref(false)
const message = ref('')
const error = ref('')

function idempotencyKey() {
  return crypto.randomUUID()
}

async function submit() {
  loading.value = true
  message.value = ''
  error.value = ''

  if (!amount.value || Number(amount.value) <= 0) {
    error.value = 'Informe um valor maior que zero.'
    loading.value = false
    return
  }

  try {
    await ensureCsrfCookie()
    const { data } = await api.post('/api/wallet/transfer', {
      recipient_email: recipientEmail.value,
      amount: Number(amount.value).toFixed(2),
      idempotency_key: idempotencyKey(),
    })
    message.value = data.message
    recipientEmail.value = ''
    amount.value = ''
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao transferir.'
  } finally {
    loading.value = false
  }
}
</script>
