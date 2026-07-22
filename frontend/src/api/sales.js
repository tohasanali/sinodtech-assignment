import apiClient from './client'

export async function listSales({ branchId } = {}) {
  const response = await apiClient.get('/api/v1/admin/sales', {
    params: branchId ? { branch_id: branchId } : undefined,
  })
  return response.data.data
}

export async function createSale(payload) {
  const response = await apiClient.post('/api/v1/admin/sales', payload)
  return response.data.data
}
