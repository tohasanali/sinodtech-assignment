import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import { fetchUser, login as loginRequest, logout as logoutRequest } from '../api/auth'
import { setActiveBranch as setActiveBranchRequest } from '../api/session'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [activeBranchId, setActiveBranchId] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchUser()
      .then(({ user, active_branch_id: activeBranchId }) => {
        setUser(user)
        setActiveBranchId(activeBranchId)
      })
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  const switchBranch = useCallback(async (branchId) => {
    const confirmedId = await setActiveBranchRequest(branchId)
    setActiveBranchId(confirmedId)
  }, [])

  // Employees with exactly one assigned branch never need to be prompted —
  // silently activate it as soon as we know who they are.
  useEffect(() => {
    if (!user || user.role !== 'employee' || activeBranchId) {
      return
    }
    const branches = user.branches ?? []
    if (branches.length === 1) {
      switchBranch(branches[0].id)
    }
  }, [user, activeBranchId, switchBranch])

  const login = useCallback(async (credentials) => {
    const { user: loggedInUser, active_branch_id: loggedInActiveBranchId } = await loginRequest(credentials)
    setUser(loggedInUser)
    setActiveBranchId(loggedInActiveBranchId)
    return loggedInUser
  }, [])

  const logout = useCallback(async () => {
    await logoutRequest()
    setUser(null)
    setActiveBranchId(null)
  }, [])

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, activeBranchId, switchBranch }}>
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
