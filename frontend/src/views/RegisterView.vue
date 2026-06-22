<template>
  <div class="auth-wrapper">
    <div class="card auth-card">
      <h1>Criar conta</h1>
      <p v-if="error" class="error">{{ error }}</p>
      <form @submit.prevent="submit">
        <label for="name">Nome</label>
        <input id="name" v-model="form.name" type="text" required />

        <label for="email">E-mail</label>
        <input id="email" v-model="form.email" type="email" required />

        <label for="password">Senha</label>
        <input id="password" v-model="form.password" type="password" minlength="8" required />

        <label for="password_confirmation">Confirmar senha</label>
        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required />

        <button type="submit" :disabled="loading">Cadastrar</button>
      </form>
      <p style="margin-top: 1rem">
        Já tem conta?
        <router-link to="/login">Entrar</router-link>
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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''

  try {
    await auth.register(form)
    router.push({ name: 'dashboard' })
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message || 'Erro ao cadastrar.'
  } finally {
    loading.value = false
  }
}
</script>
