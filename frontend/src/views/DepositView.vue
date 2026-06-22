<template>
  <AppLayout>
    <div class="card">
      <h1>Depositar</h1>
      <p v-if="message" class="success">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <form @submit.prevent="submit">
        <label for="amount">Valor (R$)</label>
        <CurrencyInput id="amount" v-model="amount" required />
        <button type="submit" :disabled="loading">Confirmar depósito</button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import CurrencyInput from '../components/CurrencyInput.vue'
import api, { ensureCsrfCookie } from '../services/api'

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
    const { data } = await api.post('/api/wallet/deposit', {
      amount: Number(amount.value).toFixed(2),
      idempotency_key: idempotencyKey(),
    })
    message.value = data.message
    amount.value = ''
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao depositar.'
  } finally {
    loading.value = false
  }
}
</script>
