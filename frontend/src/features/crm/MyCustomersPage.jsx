import { useCallback, useEffect, useState } from 'react'
import { listMyCustomers } from '../../api/customers'
import { EmptyState } from '../../components/ui'
import MyCustomerRow from './MyCustomerRow'

export default function MyCustomersPage() {
  const [lostOnlyFilter, setLostOnlyFilter] = useState('')
  const [state, setState] = useState({ status: 'loading' })

  const loadMyCustomers = useCallback(() => {
    setState({ status: 'loading' })
    return listMyCustomers({ lostOnly: lostOnlyFilter === 'true' })
      .then((customers) => setState({ status: 'ok', customers }))
      .catch((error) => setState({ status: 'error', message: error.message }))
  }, [lostOnlyFilter])

  useEffect(() => {
    loadMyCustomers()
  }, [loadMyCustomers])

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">My customers</h2>
        <select
          value={lostOnlyFilter}
          onChange={(event) => setLostOnlyFilter(event.target.value)}
          className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All assigned</option>
          <option value="true">Lost only</option>
        </select>
      </div>

      {state.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading your customers...</p>
      )}
      {state.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load your customers ({state.message})</p>
      )}
      {state.status === 'ok' && state.customers.length === 0 && (
        <EmptyState message="No customers assigned to you in this view." />
      )}

      {state.status === 'ok' && state.customers.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Email</th>
                <th className="px-4 py-3 font-medium">Phone</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Re-engage</th>
              </tr>
            </thead>
            <tbody>
              {state.customers.map((customer) => (
                <MyCustomerRow key={customer.id} customer={customer} />
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
