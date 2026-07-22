import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import { fetchUser, login as loginRequest, logout as logoutRequest } from '../api/auth'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchUser()
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  const login = useCallback(async (credentials) => {
    const loggedInUser = await loginRequest(credentials)
    setUser(loggedInUser)
    return loggedInUser
  }, [])

  const logout = useCallback(async () => {
    await logoutRequest()
    setUser(null)
  }, [])

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }

  return context
}
