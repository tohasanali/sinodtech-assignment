import apiClient from './client'

export async function listBranches() {
  const response = await apiClient.get('/api/v1/admin/branches')
  return response.data.data
}
