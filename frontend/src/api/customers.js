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

export async function getCustomer(id) {
  const response = await apiClient.get(`/api/v1/admin/customers/${id}`)
  return response.data.data
}

export async function listLostCustomers({ assigned } = {}) {
  const response = await apiClient.get('/api/v1/admin/customers/lost', {
    params: assigned === undefined ? undefined : { assigned },
  })
  return response.data.data
}

export async function assignCustomer(id, employeeId) {
  const response = await apiClient.patch(`/api/v1/admin/customers/${id}/assign`, {
    employee_id: employeeId,
  })
  return response.data.data
}

export async function reengageCustomer(id) {
  const response = await apiClient.post(`/api/v1/admin/customers/${id}/reengage`)
  return response.data.data
}

export async function reengageBulk({ customerIds } = {}) {
  const response = await apiClient.post('/api/v1/admin/customers/reengage/bulk', {
    customer_ids: customerIds,
  })
  return response.data.data
}
