import { useEffect, useState } from 'react'
import './App.css'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8002'

function App() {
  const [health, setHealth] = useState({ state: 'loading' })

  useEffect(() => {
    fetch(`${API_URL}/api/health`)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        return res.json()
      })
      .then((data) => setHealth({ state: 'ok', data }))
      .catch((error) => setHealth({ state: 'error', error: error.message }))
  }, [])

  return (
    <section id="center">
      <h1>SinodTech</h1>
      <p>Sales, Inventory &amp; CRM System</p>
      <p>
        API health check ({API_URL}/api/health):{' '}
        {health.state === 'loading' && 'checking...'}
        {health.state === 'ok' && `${health.data.status} @ ${health.data.timestamp}`}
        {health.state === 'error' && `failed (${health.error})`}
      </p>
    </section>
  )
}

export default App
