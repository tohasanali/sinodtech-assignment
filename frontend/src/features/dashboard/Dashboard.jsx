import { useEffect, useState } from 'react'
import apiClient from '../../api/client'
import { Card } from '../../components/ui'

const STATUS_DOT = {
  loading: 'bg-slate-400',
  ok: 'bg-emerald-500',
  error: 'bg-red-500',
}

export default function Dashboard() {
  const [health, setHealth] = useState({ state: 'loading' })

  useEffect(() => {
    apiClient
      .get('/api/health')
      .then((res) => setHealth({ state: 'ok', data: res.data }))
      .catch((error) => setHealth({ state: 'error', error: error.message }))
  }, [])

  return (
    <Card>
      <h2 className="mb-3 text-sm font-medium text-slate-500 dark:text-slate-400">API status</h2>
      <div className="flex items-center gap-2">
        <span className={`h-2.5 w-2.5 rounded-full ${STATUS_DOT[health.state]}`} />
        <p className="text-sm text-slate-700 dark:text-slate-300">
          {health.state === 'loading' && 'Checking...'}
          {health.state === 'ok' && `${health.data.status} @ ${health.data.timestamp}`}
          {health.state === 'error' && `Failed (${health.error})`}
        </p>
      </div>
    </Card>
  )
}
