import apiClient from './client'

export async function login(credentials) {
  await apiClient.get('/sanctum/csrf-cookie')
  const response = await apiClient.post('/api/v1/login', credentials)
  return response.data.user
}

export async function logout() {
  await apiClient.post('/api/v1/logout')
}

export async function fetchUser() {
  const response = await apiClient.get('/api/v1/user')
  return response.data.user
}
