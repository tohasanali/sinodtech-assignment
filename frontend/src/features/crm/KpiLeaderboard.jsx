import { useEffect, useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { listEmployeeKpi } from '../../api/employees'
import { EmptyState } from '../../components/ui'

export default function KpiLeaderboard() {
  const { user } = useAuth()
  const isAdmin = user.role === 'admin'

  const [state, setState] = useState({ status: 'loading' })

  useEffect(() => {
    if (!isAdmin) {
      return
    }
    listEmployeeKpi()
      .then((entries) => setState({ status: 'ok', entries }))
      .catch((error) => setState({ status: 'error', message: error.message }))
  }, [isAdmin])

  if (!isAdmin) {
    return <EmptyState message="Admins only." />
  }

  return (
    <div>
      <h2 className="mb-6 text-lg font-semibold text-slate-900 dark:text-slate-100">
        Employee KPI leaderboard
      </h2>

      {state.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading leaderboard...</p>
      )}
      {state.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load leaderboard ({state.message})</p>
      )}
      {state.status === 'ok' && state.entries.length === 0 && (
        <EmptyState message="No KPI activity yet." />
      )}

      {state.status === 'ok' && state.entries.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium">#</th>
                <th className="px-4 py-3 font-medium">Employee</th>
                <th className="px-4 py-3 font-medium">Points</th>
              </tr>
            </thead>
            <tbody>
              {state.entries.map((entry, index) => (
                <tr
                  key={entry.user_id}
                  className="border-b border-slate-100 last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50"
                >
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-400">#{index + 1}</td>
                  <td className="px-4 py-3 text-slate-900 dark:text-slate-100">{entry.name}</td>
                  <td className="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{entry.points}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
