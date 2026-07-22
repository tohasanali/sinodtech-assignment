import { useEffect, useState } from 'react'
import apiClient from '../../api/client'
import { useAuth } from '../../context/AuthContext'

const ROLE_STYLES = {
  admin: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
  employee: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  api_consumer: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
}

const STATUS_DOT = {
  loading: 'bg-slate-400',
  ok: 'bg-emerald-500',
  error: 'bg-red-500',
}

export default function Dashboard() {
  const { user, logout } = useAuth()
  const [health, setHealth] = useState({ state: 'loading' })

  useEffect(() => {
    apiClient
      .get('/api/health')
      .then((res) => setHealth({ state: 'ok', data: res.data }))
      .catch((error) => setHealth({ state: 'error', error: error.message }))
  }, [])

  return (
    <div>
      <header className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
          <div>
            <h1 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
              SinodTech
            </h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Sales, Inventory &amp; CRM System
            </p>
          </div>
          <div className="flex items-center gap-3">
            <div className="text-right">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-100">
                {user.name}
              </p>
              <span
                className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${ROLE_STYLES[user.role] ?? ROLE_STYLES.employee}`}
              >
                {user.role.replace('_', ' ')}
              </span>
            </div>
            <button
              type="button"
              onClick={logout}
              className="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            >
              Log out
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-4 py-8">
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <h2 className="mb-3 text-sm font-medium text-slate-500 dark:text-slate-400">
            API status
          </h2>
          <div className="flex items-center gap-2">
            <span className={`h-2.5 w-2.5 rounded-full ${STATUS_DOT[health.state]}`} />
            <p className="text-sm text-slate-700 dark:text-slate-300">
              {health.state === 'loading' && 'Checking...'}
              {health.state === 'ok' && `${health.data.status} @ ${health.data.timestamp}`}
              {health.state === 'error' && `Failed (${health.error})`}
            </p>
          </div>
        </div>
      </main>
    </div>
  )
}
