<template>
  <input
    :id="id"
    :value="display"
    type="text"
    inputmode="numeric"
    autocomplete="off"
    :placeholder="placeholder"
    :required="required"
    @input="handleInput"
  />
</template>

<script setup>
import { ref, watch } from 'vue'
import { formatCurrency } from '../utils/currency'

defineProps({
  id: { type: String, default: undefined },
  placeholder: { type: String, default: '0,00' },
  required: { type: Boolean, default: false },
})

const model = defineModel({ type: [Number, String], default: '' })

const display = ref('')

watch(
  model,
  (value) => {
    if (value === '' || value === null || value === undefined) {
      display.value = ''
      return
    }

    display.value = formatCurrency(value)
  },
  { immediate: true },
)

function handleInput(event) {
  const digits = event.target.value.replace(/\D/g, '')

  if (!digits) {
    model.value = ''
    display.value = ''
    return
  }

  const amount = Number(digits) / 100
  model.value = amount
  display.value = formatCurrency(amount)
}
</script>
