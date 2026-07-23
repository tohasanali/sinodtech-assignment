import apiClient from './client'

export async function setActiveBranch(branchId) {
  const response = await apiClient.post('/api/v1/session/branch', { branch_id: branchId })
  return response.data.data.active_branch_id
}
