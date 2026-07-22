import { useState } from 'react'
import { adjustStock } from '../../api/products'
import { Alert, Button } from '../../components/ui'

export default function StockRow({ productId, branchId, branchName, quantity, onAdjusted }) {
  const [delta, setDelta] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  async function handleApply() {
    const numericDelta = Number(delta)
    if (!numericDelta) {
      return
    }

    setSubmitting(true)
    setError(null)
    try {
      await adjustStock(productId, branchId, numericDelta)
      setDelta('')
      await onAdjusted()
    } catch (err) {
      const apiError = err.response?.data?.error
      setError(apiError?.message ?? 'Could not adjust stock.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div>
      <div className="flex items-center gap-3">
        <div className="flex-1">
          <p className="text-sm text-slate-900 dark:text-slate-100">{branchName}</p>
          <p className="text-xs text-slate-500 dark:text-slate-400">Current: {quantity}</p>
        </div>
        <input
          type="number"
          placeholder="e.g. 10 or -5"
          value={delta}
          onChange={(event) => setDelta(event.target.value)}
          disabled={submitting}
          className="w-32 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        />
        <Button variant="secondary" disabled={!delta || submitting} onClick={handleApply}>
          {submitting ? 'Applying...' : 'Apply'}
        </Button>
      </div>

      {error && (
        <Alert variant="error" className="mt-2">
          {error}
        </Alert>
      )}
    </div>
  )
}
