<template>
  <div>
    <header class="container">
      <div class="nav">
        <router-link to="/">Dashboard</router-link>
        <router-link to="/deposit">Depositar</router-link>
        <router-link to="/transfer">Transferir</router-link>
        <router-link to="/transactions">Extrato</router-link>
        <button class="secondary" @click="logout">Sair</button>
      </div>
    </header>
    <main class="container">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
