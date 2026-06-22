<template>
  <AppLayout>
    <div class="card">
      <h1>Extrato</h1>
      <p v-if="message" class="success">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>

      <table v-if="transactions.length">
        <thead>
          <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Detalhes</th>
            <th>Status</th>
            <th>Valor</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in transactions" :key="item.id">
            <td>{{ formatDate(item.created_at) }}</td>
            <td>{{ translateType(item.type) }}</td>
            <td>{{ item.description || '—' }}</td>
            <td>{{ translateStatus(item.status) }}</td>
            <td>R$ {{ formatCurrency(resolveAmount(item)) }}</td>
            <td>
              <button
                v-if="item.can_reverse"
                class="danger"
                :disabled="reversingId === item.id"
                @click="reverse(item.id)"
              >
                {{ item.reverse_action_label }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else>Nenhuma transação encontrada.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import api, { ensureCsrfCookie } from '../services/api'
import { formatCurrency } from '../utils/currency'

const transactions = ref([])
const message = ref('')
const error = ref('')
const reversingId = ref(null)

function formatDate(value) {
  return new Date(value).toLocaleString('pt-BR')
}

function translateType(type) {
  return {
    deposit: 'Depósito',
    transfer: 'Transferência',
    reversal: 'Devolução/Estorno',
  }[type] || type
}

function translateStatus(status) {
  return {
    completed: 'Concluída',
    reversed: 'Estornada',
  }[status] || status
}

function resolveAmount(item) {
  return item.metadata?.amount || item.ledger_entries?.[0]?.amount || '0.00'
}

async function loadTransactions() {
  const { data } = await api.get('/api/wallet/transactions')
  transactions.value = data.data ?? []
}

async function reverse(id) {
  reversingId.value = id
  message.value = ''
  error.value = ''

  try {
    await ensureCsrfCookie()
    const { data } = await api.post(`/api/wallet/transactions/${id}/reverse`)
    message.value = data.message
    await loadTransactions()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao processar a solicitação.'
  } finally {
    reversingId.value = null
  }
}

onMounted(loadTransactions)
</script>
