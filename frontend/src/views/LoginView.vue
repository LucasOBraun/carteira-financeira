<template>
  <div class="auth-wrapper">
    <div class="card auth-card">
      <h1>Entrar</h1>
      <p v-if="error" class="error">{{ error }}</p>
      <form @submit.prevent="submit">
        <label for="email">E-mail</label>
        <input id="email" v-model="form.email" type="email" required />

        <label for="password">Senha</label>
        <input id="password" v-model="form.password" type="password" required />

        <button type="submit" :disabled="loading">Entrar</button>
      </form>
      <p style="margin-top: 1rem">
        Não tem conta?
        <router-link to="/register">Cadastre-se</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''

  try {
    await auth.login(form)
    router.push({ name: 'dashboard' })
  } catch (err) {
    error.value = err.response?.data?.message
      || err.response?.data?.errors?.email?.[0]
      || 'Credenciais inválidas.'
  } finally {
    loading.value = false
  }
}
</script>
