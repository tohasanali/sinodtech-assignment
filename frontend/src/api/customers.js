import apiClient from './client'

export async function listCustomers() {
  const response = await apiClient.get('/api/v1/admin/customers')
  return response.data.data
}

export async function createCustomer(payload) {
  const response = await apiClient.post('/api/v1/admin/customers', payload)
  return response.data.data
}

export async function updateCustomer(id, payload) {
  const response = await apiClient.put(`/api/v1/admin/customers/${id}`, payload)
  return response.data.data
}

export async function deleteCustomer(id) {
  await apiClient.delete(`/api/v1/admin/customers/${id}`)
}
