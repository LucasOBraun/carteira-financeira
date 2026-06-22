<template>
  <AppLayout>
    <div class="card">
      <h1>Olá, {{ auth.userName || 'usuário' }}</h1>
      <p>Saldo disponível</p>
      <p class="balance">R$ {{ formattedBalance }}</p>
      <div class="actions">
        <router-link class="btn" to="/deposit">Depositar</router-link>
        <router-link class="btn secondary" to="/transfer">Transferir</router-link>
        <router-link class="btn secondary" to="/transactions">Ver extrato</router-link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { unwrapResource } from '../utils/api'
import { formatCurrency } from '../utils/currency'

const auth = useAuthStore()
const wallet = ref(null)

const formattedBalance = computed(() => formatCurrency(wallet.value?.balance))

async function loadDashboard() {
  if (!auth.user) {
    await auth.fetchUser()
  }

  const { data } = await api.get('/api/wallet')
  wallet.value = unwrapResource(data)
}

onMounted(loadDashboard)
</script>
