import { useState } from 'react'
import { reengageCustomer } from '../../api/customers'
import { Alert, Button } from '../../components/ui'

export default function MyCustomerRow({ customer }) {
  const [reengaging, setReengaging] = useState(false)
  const [reengageError, setReengageError] = useState(null)
  const [reengaged, setReengaged] = useState(false)

  async function handleReengage() {
    setReengaging(true)
    setReengageError(null)
    try {
      await reengageCustomer(customer.id)
      setReengaged(true)
    } catch (err) {
      const apiError = err.response?.data?.error
      setReengageError(apiError?.message ?? 'Could not re-engage customer.')
    } finally {
      setReengaging(false)
    }
  }

  const alreadyContacted = reengaged || customer.recently_contacted

  return (
    <tr className="border-b border-slate-100 last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
      <td className="px-4 py-3 align-top text-slate-900 dark:text-slate-100">{customer.name}</td>
      <td className="px-4 py-3 align-top text-slate-600 dark:text-slate-400">{customer.email}</td>
      <td className="px-4 py-3 align-top text-slate-600 dark:text-slate-400">{customer.phone}</td>
      <td className="px-4 py-3 align-top">
        <span
          className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${
            customer.is_lost
              ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
              : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
          }`}
        >
          {customer.is_lost ? 'Lost' : 'Active'}
        </span>
      </td>
      <td className="px-4 py-3 align-top">
        <Button variant="secondary" disabled={reengaging || alreadyContacted} onClick={handleReengage}>
          {reengaging ? 'Sending...' : alreadyContacted ? 'Recently contacted' : 'Re-engage'}
        </Button>
        {reengageError && (
          <Alert variant="error" className="mt-2">
            {reengageError}
          </Alert>
        )}
      </td>
    </tr>
  )
}
