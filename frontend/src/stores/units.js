import { defineStore } from 'pinia'
import { ref } from 'vue'
import { units as unitsApi } from '@/api'

export const useUnitsStore = defineStore('units', () => {
  const list    = ref([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    list.value    = await unitsApi.list()
    loading.value = false
  }

  async function create(data) {
    const unit = await unitsApi.create(data)
    list.value.push(unit)
    return unit
  }

  async function update(id, data) {
    const unit = await unitsApi.update(id, data)
    const idx  = list.value.findIndex((u) => u.id === id)
    if (idx !== -1) list.value[idx] = unit
    return unit
  }

  async function remove(id) {
    await unitsApi.remove(id)
    list.value = list.value.filter((u) => u.id !== id)
  }

  return { list, loading, fetchAll, create, update, remove }
})
