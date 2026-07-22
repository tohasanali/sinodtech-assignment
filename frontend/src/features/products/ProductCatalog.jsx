import { Fragment, useEffect, useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { createProduct, deleteProduct, listProducts, updateProduct } from '../../api/products'
import { listBranches } from '../../api/branches'
import { Button, EmptyState } from '../../components/ui'
import ProductForm from './ProductForm'
import StockPanel from './StockPanel'

export default function ProductCatalog() {
  const { user } = useAuth()
  const isAdmin = user.role === 'admin'

  const [state, setState] = useState({ status: 'loading' })
  const [branches, setBranches] = useState([])
  const [creating, setCreating] = useState(false)
  const [editingId, setEditingId] = useState(null)
  const [managingStockId, setManagingStockId] = useState(null)

  function loadProducts() {
    setState({ status: 'loading' })
    return listProducts()
      .then((products) => setState({ status: 'ok', products }))
      .catch((error) => setState({ status: 'error', message: error.message }))
  }

  useEffect(() => {
    loadProducts()
  }, [])

  useEffect(() => {
    if (isAdmin) {
      listBranches().then(setBranches).catch(() => {})
    }
  }, [isAdmin])

  async function handleCreate(payload) {
    await createProduct(payload)
    setCreating(false)
    loadProducts()
  }

  async function handleUpdate(id, payload) {
    await updateProduct(id, payload)
    setEditingId(null)
    loadProducts()
  }

  async function handleDelete(product) {
    if (!window.confirm(`Delete "${product.name}"? This cannot be undone.`)) {
      return
    }

    await deleteProduct(product.id)
    loadProducts()
  }

  function toggleEditing(productId) {
    setManagingStockId(null)
    setEditingId((prev) => (prev === productId ? null : productId))
  }

  function toggleStockPanel(productId) {
    setEditingId(null)
    setManagingStockId((prev) => (prev === productId ? null : productId))
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Products</h2>
        {isAdmin && !creating && (
          <Button onClick={() => setCreating(true)}>Add product</Button>
        )}
      </div>

      {creating && (
        <ProductForm submitLabel="Create product" onSubmit={handleCreate} onCancel={() => setCreating(false)} />
      )}

      {state.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading products...</p>
      )}

      {state.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load products ({state.message})</p>
      )}

      {state.status === 'ok' && state.products.length === 0 && (
        <EmptyState message="No products yet." />
      )}

      {state.status === 'ok' && state.products.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">SKU</th>
                <th className="px-4 py-3 font-medium">Price</th>
                <th className="px-4 py-3 font-medium">Stock by branch</th>
                {isAdmin && <th className="px-4 py-3 font-medium">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {state.products.map((product) => (
                <Fragment key={product.id}>
                  <tr className="border-b border-slate-100 last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
                    <td className="px-4 py-3 text-slate-900 dark:text-slate-100">{product.name}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{product.sku}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">${product.price.toFixed(2)}</td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap gap-1.5">
                        {product.stock.map((entry) => (
                          <span
                            key={entry.branch_id}
                            className="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                          >
                            {entry.branch_name}: {entry.quantity}
                          </span>
                        ))}
                      </div>
                    </td>
                    {isAdmin && (
                      <td className="space-x-3 px-4 py-3">
                        <Button variant="link" onClick={() => toggleStockPanel(product.id)}>
                          Manage stock
                        </Button>
                        <Button variant="link" onClick={() => toggleEditing(product.id)}>
                          Edit
                        </Button>
                        <Button variant="danger" onClick={() => handleDelete(product)}>
                          Delete
                        </Button>
                      </td>
                    )}
                  </tr>
                  {editingId === product.id && (
                    <tr>
                      <td colSpan={isAdmin ? 5 : 4} className="px-4 py-3">
                        <ProductForm
                          initialValues={{
                            name: product.name,
                            sku: product.sku,
                            price: product.price,
                            description: product.description ?? '',
                          }}
                          submitLabel="Save changes"
                          onSubmit={(payload) => handleUpdate(product.id, payload)}
                          onCancel={() => setEditingId(null)}
                        />
                      </td>
                    </tr>
                  )}
                  {managingStockId === product.id && (
                    <tr>
                      <td colSpan={isAdmin ? 5 : 4} className="px-4 py-3">
                        <StockPanel product={product} branches={branches} onAdjusted={loadProducts} />
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
