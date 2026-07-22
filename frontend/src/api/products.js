import apiClient from './client'

export async function listProducts() {
  const response = await apiClient.get('/api/v1/admin/products')
  return response.data.data
}

export async function createProduct(payload) {
  const response = await apiClient.post('/api/v1/admin/products', payload)
  return response.data.data
}

export async function updateProduct(id, payload) {
  const response = await apiClient.put(`/api/v1/admin/products/${id}`, payload)
  return response.data.data
}

export async function deleteProduct(id) {
  await apiClient.delete(`/api/v1/admin/products/${id}`)
}

export async function adjustStock(productId, branchId, delta) {
  const response = await apiClient.patch(
    `/api/v1/admin/products/${productId}/branches/${branchId}/stock`,
    { delta },
  )
  return response.data.data
}
