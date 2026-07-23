import apiClient from './client'

export async function listUsers() {
  const response = await apiClient.get('/api/v1/admin/users')
  return response.data.users
}
