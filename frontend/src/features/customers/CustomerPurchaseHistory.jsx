import { useEffect, useState } from 'react'
import { getCustomer } from '../../api/customers'

export default function CustomerPurchaseHistory({ customerId }) {
  const [state, setState] = useState({ status: 'loading' })

  useEffect(() => {
    getCustomer(customerId)
      .then((customer) => setState({ status: 'ok', history: customer.purchase_history }))
      .catch((error) => setState({ status: 'error', message: error.message }))
  }, [customerId])

  if (state.status === 'loading') {
    return <p className="text-sm text-slate-500 dark:text-slate-400">Loading purchase history...</p>
  }

  if (state.status === 'error') {
    return (
      <p className="text-sm text-red-600 dark:text-red-400">
        Failed to load purchase history ({state.message})
      </p>
    )
  }

  const { count, last_purchase_at: lastPurchaseAt, records } = state.history
  const sortedRecords = [...records].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <p className="mb-3 text-sm text-slate-600 dark:text-slate-400">
        {count} purchase{count === 1 ? '' : 's'} · Last purchase:{' '}
        {lastPurchaseAt ? new Date(lastPurchaseAt).toLocaleDateString() : 'Never'}
      </p>

      {sortedRecords.length === 0 ? (
        <p className="text-sm text-slate-500 dark:text-slate-400">No purchase records.</p>
      ) : (
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 text-slate-500 dark:border-slate-800 dark:text-slate-400">
            <tr>
              <th className="py-2 font-medium">Date</th>
              <th className="py-2 font-medium">Branch</th>
              <th className="py-2 font-medium">Total</th>
            </tr>
          </thead>
          <tbody>
            {sortedRecords.map((sale) => (
              <tr key={sale.id} className="border-t border-slate-100 dark:border-slate-800">
                <td className="py-2 text-slate-900 dark:text-slate-100">
                  {new Date(sale.created_at).toLocaleString()}
                </td>
                <td className="py-2 text-slate-600 dark:text-slate-400">{sale.branch.name}</td>
                <td className="py-2 text-slate-600 dark:text-slate-400">${sale.total.toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
