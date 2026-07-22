import StockRow from './StockRow'

export default function StockPanel({ product, branches, onAdjusted }) {
  const rows = branches.map((branch) => {
    const existing = product.stock.find((entry) => entry.branch_id === branch.id)
    return { branchId: branch.id, branchName: branch.name, quantity: existing?.quantity ?? 0 }
  })

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <h3 className="mb-1 text-sm font-medium text-slate-500 dark:text-slate-400">Manage stock</h3>
      <p className="mb-4 text-xs text-slate-400 dark:text-slate-500">
        Enter a positive number to add stock, negative to remove.
      </p>

      <div className="space-y-3">
        {rows.map((row) => (
          <StockRow
            key={row.branchId}
            productId={product.id}
            branchId={row.branchId}
            branchName={row.branchName}
            quantity={row.quantity}
            onAdjusted={onAdjusted}
          />
        ))}
      </div>
    </div>
  )
}
