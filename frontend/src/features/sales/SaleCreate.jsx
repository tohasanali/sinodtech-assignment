import { useEffect, useMemo, useState } from 'react'
import { listProducts } from '../../api/products'
import { listCustomers } from '../../api/customers'
import { createSale } from '../../api/sales'
import { Alert, Button, Card, EmptyState, Field } from '../../components/ui'

export default function SaleCreate() {
  const [productsState, setProductsState] = useState({ status: 'loading' })
  const [customers, setCustomers] = useState([])
  const [branchId, setBranchId] = useState('')
  const [selectedProductId, setSelectedProductId] = useState('')
  const [pickQuantity, setPickQuantity] = useState(1)
  const [cart, setCart] = useState([])
  const [customerId, setCustomerId] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState(null)
  const [submitSuccess, setSubmitSuccess] = useState(null)

  useEffect(() => {
    listProducts()
      .then((products) => setProductsState({ status: 'ok', products }))
      .catch((error) => setProductsState({ status: 'error', message: error.message }))
  }, [])

  useEffect(() => {
    listCustomers().then(setCustomers).catch(() => {})
  }, [])

  const products = useMemo(
    () => (productsState.status === 'ok' ? productsState.products : []),
    [productsState],
  )

  const branches = useMemo(() => {
    const byId = new Map()
    for (const product of products) {
      for (const entry of product.stock) {
        byId.set(entry.branch_id, entry.branch_name)
      }
    }
    return Array.from(byId, ([id, name]) => ({ id, name }))
  }, [products])

  const productsAtBranch = useMemo(() => {
    if (!branchId) {
      return []
    }
    return products.map((product) => {
      const stockEntry = product.stock.find((entry) => entry.branch_id === Number(branchId))
      return { ...product, availableQuantity: stockEntry?.quantity ?? 0 }
    })
  }, [products, branchId])

  const total = cart.reduce((sum, line) => sum + line.unit_price * line.quantity, 0)

  function handleBranchChange(value) {
    setBranchId(value)
    setSelectedProductId('')
    setCart([])
    setSubmitSuccess(null)
  }

  function handleAddToCart() {
    const product = productsAtBranch.find((p) => p.id === Number(selectedProductId))
    if (!product) {
      return
    }

    setCart((prev) => {
      const existing = prev.find((line) => line.product_id === product.id)
      if (existing) {
        return prev.map((line) =>
          line.product_id === product.id
            ? { ...line, quantity: line.quantity + Number(pickQuantity) }
            : line,
        )
      }
      return [
        ...prev,
        {
          product_id: product.id,
          name: product.name,
          unit_price: product.price,
          quantity: Number(pickQuantity),
        },
      ]
    })
    setPickQuantity(1)
  }

  function updateLineQuantity(productId, quantity) {
    setCart((prev) => prev.map((line) => (line.product_id === productId ? { ...line, quantity } : line)))
  }

  function removeLine(productId) {
    setCart((prev) => prev.filter((line) => line.product_id !== productId))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setSubmitError(null)
    setSubmitSuccess(null)

    try {
      const sale = await createSale({
        branch_id: Number(branchId),
        customer_id: customerId ? Number(customerId) : null,
        items: cart.map((line) => ({ product_id: line.product_id, quantity: line.quantity })),
      })
      setSubmitSuccess(sale)
      setCart([])
      setCustomerId('')
    } catch (err) {
      const apiError = err.response?.data?.error
      setSubmitError(apiError ?? { message: 'Could not record the sale.' })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div>
      <h2 className="mb-6 text-lg font-semibold text-slate-900 dark:text-slate-100">New sale</h2>

      {productsState.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading products...</p>
      )}
      {productsState.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">
          Failed to load products ({productsState.message})
        </p>
      )}

      {productsState.status === 'ok' && (
        <div className="space-y-6">
          <Card>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="branch" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Branch
                </label>
                <select
                  id="branch"
                  value={branchId}
                  onChange={(event) => handleBranchChange(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="">Select a branch</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label htmlFor="customer" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Customer
                </label>
                <select
                  id="customer"
                  value={customerId}
                  onChange={(event) => setCustomerId(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="">Walk-in (no customer)</option>
                  {customers.map((customer) => (
                    <option key={customer.id} value={customer.id}>
                      {customer.name} — {customer.email}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {branchId && (
              <div className="mt-4 flex items-end gap-3">
                <div className="flex-1">
                  <label htmlFor="product" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Product
                  </label>
                  <select
                    id="product"
                    value={selectedProductId}
                    onChange={(event) => setSelectedProductId(event.target.value)}
                    className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                  >
                    <option value="">Select a product</option>
                    {productsAtBranch.map((product) => (
                      <option key={product.id} value={product.id}>
                        {product.name} — {product.availableQuantity} in stock (${product.price.toFixed(2)})
                      </option>
                    ))}
                  </select>
                </div>
                <Field
                  id="pick-quantity"
                  label="Qty"
                  type="number"
                  min="1"
                  value={pickQuantity}
                  onChange={(event) => setPickQuantity(event.target.value)}
                  className="w-24"
                />
                <Button onClick={handleAddToCart} disabled={!selectedProductId}>
                  Add to cart
                </Button>
              </div>
            )}
          </Card>

          <Card>
            <h3 className="mb-3 text-sm font-medium text-slate-500 dark:text-slate-400">Cart</h3>

            {cart.length === 0 && <EmptyState message="No items added yet." />}

            {cart.length > 0 && (
              <table className="w-full text-left text-sm">
                <thead className="border-b border-slate-200 text-slate-500 dark:border-slate-800 dark:text-slate-400">
                  <tr>
                    <th className="py-2 font-medium">Product</th>
                    <th className="py-2 font-medium">Unit price</th>
                    <th className="py-2 font-medium">Quantity</th>
                    <th className="py-2 font-medium">Subtotal</th>
                    <th className="py-2" />
                  </tr>
                </thead>
                <tbody>
                  {cart.map((line) => (
                    <tr key={line.product_id} className="border-b border-slate-100 last:border-0 dark:border-slate-800">
                      <td className="py-2 text-slate-900 dark:text-slate-100">{line.name}</td>
                      <td className="py-2 text-slate-600 dark:text-slate-400">${line.unit_price.toFixed(2)}</td>
                      <td className="py-2">
                        <input
                          type="number"
                          min="1"
                          value={line.quantity}
                          onChange={(event) => updateLineQuantity(line.product_id, Number(event.target.value))}
                          className="w-20 rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                      </td>
                      <td className="py-2 text-slate-600 dark:text-slate-400">
                        ${(line.unit_price * line.quantity).toFixed(2)}
                      </td>
                      <td className="py-2">
                        <Button variant="danger" onClick={() => removeLine(line.product_id)}>
                          Remove
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

            {cart.length > 0 && (
              <p className="mt-4 text-right text-sm font-semibold text-slate-900 dark:text-slate-100">
                Total: ${total.toFixed(2)}
              </p>
            )}
          </Card>

          {submitError && (
            <Alert variant="error">
              <p className="font-medium">{submitError.message}</p>
              {submitError.errors && (
                <ul className="mt-1 list-inside list-disc">
                  {Object.values(submitError.errors)
                    .flat()
                    .map((message) => (
                      <li key={message}>{message}</li>
                    ))}
                </ul>
              )}
            </Alert>
          )}

          {submitSuccess && (
            <Alert variant="success">
              Sale #{submitSuccess.id} recorded — total ${submitSuccess.total.toFixed(2)}.
            </Alert>
          )}

          <Button onClick={handleSubmit} disabled={submitting || !branchId || cart.length === 0}>
            {submitting ? 'Recording sale...' : 'Record sale'}
          </Button>
        </div>
      )}
    </div>
  )
}
