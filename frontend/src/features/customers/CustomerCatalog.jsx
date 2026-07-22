import { Fragment, useEffect, useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { createCustomer, deleteCustomer, listCustomers, updateCustomer } from '../../api/customers'
import { Button, EmptyState } from '../../components/ui'
import CustomerForm from './CustomerForm'

export default function CustomerCatalog() {
  const { user } = useAuth()
  const isAdmin = user.role === 'admin'

  const [state, setState] = useState({ status: 'loading' })
  const [creating, setCreating] = useState(false)
  const [editingId, setEditingId] = useState(null)

  function loadCustomers() {
    setState({ status: 'loading' })
    return listCustomers()
      .then((customers) => setState({ status: 'ok', customers }))
      .catch((error) => setState({ status: 'error', message: error.message }))
  }

  useEffect(() => {
    loadCustomers()
  }, [])

  async function handleCreate(payload) {
    await createCustomer(payload)
    setCreating(false)
    loadCustomers()
  }

  async function handleUpdate(id, payload) {
    await updateCustomer(id, payload)
    setEditingId(null)
    loadCustomers()
  }

  async function handleDelete(customer) {
    if (!window.confirm(`Delete "${customer.name}"? This cannot be undone.`)) {
      return
    }

    try {
      await deleteCustomer(customer.id)
      loadCustomers()
    } catch (err) {
      const apiError = err.response?.data?.error
      window.alert(apiError?.message ?? 'Could not delete customer.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Customers</h2>
        {isAdmin && !creating && (
          <Button onClick={() => setCreating(true)}>Add customer</Button>
        )}
      </div>

      {creating && (
        <CustomerForm submitLabel="Create customer" onSubmit={handleCreate} onCancel={() => setCreating(false)} />
      )}

      {state.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading customers...</p>
      )}

      {state.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load customers ({state.message})</p>
      )}

      {state.status === 'ok' && state.customers.length === 0 && (
        <EmptyState message="No customers yet." />
      )}

      {state.status === 'ok' && state.customers.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Email</th>
                <th className="px-4 py-3 font-medium">Phone</th>
                <th className="px-4 py-3 font-medium">Assigned employee</th>
                {isAdmin && <th className="px-4 py-3 font-medium">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {state.customers.map((customer) => (
                <Fragment key={customer.id}>
                  <tr className="border-b border-slate-100 last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
                    <td className="px-4 py-3 text-slate-900 dark:text-slate-100">{customer.name}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{customer.email}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{customer.phone ?? '—'}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                      {customer.employee?.name ?? '—'}
                    </td>
                    {isAdmin && (
                      <td className="space-x-3 px-4 py-3">
                        <Button
                          variant="link"
                          onClick={() => setEditingId(editingId === customer.id ? null : customer.id)}
                        >
                          Edit
                        </Button>
                        <Button variant="danger" onClick={() => handleDelete(customer)}>
                          Delete
                        </Button>
                      </td>
                    )}
                  </tr>
                  {editingId === customer.id && (
                    <tr>
                      <td colSpan={isAdmin ? 5 : 4} className="px-4 py-3">
                        <CustomerForm
                          initialValues={{
                            name: customer.name,
                            email: customer.email,
                            phone: customer.phone ?? '',
                          }}
                          submitLabel="Save changes"
                          onSubmit={(payload) => handleUpdate(customer.id, payload)}
                          onCancel={() => setEditingId(null)}
                        />
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
