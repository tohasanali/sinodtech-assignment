import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8002'

const apiClient = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  withXSRFToken: true,
})

export default apiClient
