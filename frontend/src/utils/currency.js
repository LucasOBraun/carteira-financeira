export function parseAmount(value) {
  if (value === null || value === undefined || value === '') {
    return 0
  }

  if (typeof value === 'number') {
    return value
  }

  const str = String(value).trim()

  if (str.includes(',')) {
    return Number(str.replace(/\./g, '').replace(',', '.'))
  }

  return Number(str)
}

export function formatCurrency(value) {
  const amount = parseAmount(value)

  if (Number.isNaN(amount)) {
    return '0,00'
  }

  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}
