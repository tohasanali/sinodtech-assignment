import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { Alert, BrandMark, Button, Card, Field } from '../../components/ui'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setError(null)

    try {
      await login({ email, password })
      navigate('/')
    } catch (err) {
      const apiError = err.response?.data?.error
      setError(
        apiError?.errors?.email?.[0] ?? apiError?.message ?? 'Login failed. Please check your credentials.',
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <div className="mb-6 flex flex-col items-center text-center">
          <BrandMark className="mb-3 h-11 w-11 text-base" />
          <h1 className="text-xl font-semibold text-slate-900 dark:text-slate-100">SinodTech</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Sign in to your account
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Field
            id="email"
            label="Email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
            autoComplete="email"
          />

          <Field
            id="password"
            label="Password"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
            autoComplete="current-password"
          />

          {error && <Alert variant="error">{error}</Alert>}

          <Button type="submit" disabled={submitting} className="w-full">
            {submitting ? 'Logging in...' : 'Log in'}
          </Button>
        </form>
      </Card>
    </div>
  )
}
