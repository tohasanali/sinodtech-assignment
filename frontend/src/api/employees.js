import apiClient from './client'

export async function listEmployeeKpi() {
  const response = await apiClient.get('/api/v1/admin/employees/kpi')
  return response.data.data
}
