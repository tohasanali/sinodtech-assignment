import { useEffect, useMemo, useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { listSales } from '../../api/sales'
import { Card, EmptyState } from '../../components/ui'

export default function SalesHistory() {
  const { user, activeBranchId } = useAuth()
  const isAdmin = user.role === 'admin'

  const [allSales, setAllSales] = useState([])
  const [branchFilter, setBranchFilter] = useState('')
  const [salesState, setSalesState] = useState({ status: 'loading' })

  const activeBranchName = useMemo(
    () => (user.branches ?? []).find((branch) => branch.id === activeBranchId)?.name,
    [user.branches, activeBranchId],
  )

  // One-time unfiltered fetch so the branch dropdown stays stable even once a filter narrows the list below.
  useEffect(() => {
    listSales().then(setAllSales).catch(() => {})
  }, [])

  useEffect(() => {
    setSalesState({ status: 'loading' })
    listSales({ branchId: branchFilter || undefined })
      .then((sales) => setSalesState({ status: 'ok', sales }))
      .catch((error) => setSalesState({ status: 'error', message: error.message }))
  }, [branchFilter])

  const branches = useMemo(() => {
    const byId = new Map()
    for (const sale of allSales) {
      byId.set(sale.branch.id, sale.branch.name)
    }
    return Array.from(byId, ([id, name]) => ({ id, name }))
  }, [allSales])

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Sales history</h2>

        {isAdmin ? (
          <div>
            <label htmlFor="branch-filter" className="sr-only">
              Filter by branch
            </label>
            <select
              id="branch-filter"
              value={branchFilter}
              onChange={(event) => setBranchFilter(event.target.value)}
              className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            >
              <option value="">All branches</option>
              {branches.map((branch) => (
                <option key={branch.id} value={branch.id}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>
        ) : (
          activeBranchName && (
            <span className="text-sm text-slate-500 dark:text-slate-400">Branch: {activeBranchName}</span>
          )
        )}
      </div>

      {salesState.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading sales...</p>
      )}
      {salesState.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load sales ({salesState.message})</p>
      )}

      {salesState.status === 'ok' && salesState.sales.length === 0 && (
        <EmptyState message="No sales found." />
      )}

      {salesState.status === 'ok' && salesState.sales.length > 0 && (
        <div className="space-y-4">
          {salesState.sales.map((sale) => (
            <Card key={sale.id} noPadding>
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                  <span className="font-medium text-slate-900 dark:text-slate-100">
                    {new Date(sale.created_at).toLocaleString()}
                  </span>
                  <span className="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {sale.branch.name}
                  </span>
                  <span className="text-slate-500 dark:text-slate-400">
                    {sale.customer?.name ?? 'Walk-in'}
                  </span>
                  <span className="text-slate-500 dark:text-slate-400">Sold by {sale.sold_by.name}</span>
                </div>
                <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                  ${sale.total.toFixed(2)}
                </span>
              </div>

              <table className="w-full text-left text-sm">
                <thead className="text-slate-500 dark:text-slate-400">
                  <tr>
                    <th className="px-4 py-2 font-medium">Product</th>
                    <th className="px-4 py-2 font-medium">Qty</th>
                    <th className="px-4 py-2 font-medium">Unit price</th>
                    <th className="px-4 py-2 font-medium">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  {sale.items.map((item) => (
                    <tr key={item.product_id} className="border-t border-slate-100 dark:border-slate-800">
                      <td className="px-4 py-2 text-slate-900 dark:text-slate-100">{item.product_name}</td>
                      <td className="px-4 py-2 text-slate-600 dark:text-slate-400">{item.quantity}</td>
                      <td className="px-4 py-2 text-slate-600 dark:text-slate-400">
                        ${item.unit_price.toFixed(2)}
                      </td>
                      <td className="px-4 py-2 text-slate-600 dark:text-slate-400">
                        ${item.subtotal.toFixed(2)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
