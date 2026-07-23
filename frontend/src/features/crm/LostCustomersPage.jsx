import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { listLostCustomers, reengageBulk } from '../../api/customers'
import { listUsers } from '../../api/users'
import { Alert, Button, EmptyState } from '../../components/ui'
import LostCustomerRow from './LostCustomerRow'

export default function LostCustomersPage() {
  const { user } = useAuth()
  const isAdmin = user.role === 'admin'

  const [assignedFilter, setAssignedFilter] = useState('')
  const [state, setState] = useState({ status: 'loading' })
  const [employees, setEmployees] = useState([])
  const [bulkState, setBulkState] = useState({ status: 'idle' })
  const [selectedIds, setSelectedIds] = useState(() => new Set())

  const loadLostCustomers = useCallback(() => {
    setState({ status: 'loading' })
    return listLostCustomers({ assigned: assignedFilter || undefined })
      .then((customers) => {
        setState({ status: 'ok', customers })
        setSelectedIds(new Set())
      })
      .catch((error) => setState({ status: 'error', message: error.message }))
  }, [assignedFilter])

  useEffect(() => {
    if (!isAdmin) {
      return
    }
    loadLostCustomers()
  }, [isAdmin, loadLostCustomers])

  useEffect(() => {
    if (!isAdmin) {
      return
    }
    listUsers()
      .then((users) => setEmployees(users.filter((candidate) => candidate.role === 'employee')))
      .catch(() => {})
  }, [isAdmin])

  function toggleSelectAll() {
    if (state.status !== 'ok') {
      return
    }
    setSelectedIds((prev) =>
      prev.size === state.customers.length ? new Set() : new Set(state.customers.map((customer) => customer.id)),
    )
  }

  function toggleSelect(customerId) {
    setSelectedIds((prev) => {
      const next = new Set(prev)
      if (next.has(customerId)) {
        next.delete(customerId)
      } else {
        next.add(customerId)
      }
      return next
    })
  }

  async function handleBulkReengage() {
    if (selectedIds.size === 0) {
      return
    }
    if (!window.confirm(`Re-engage ${selectedIds.size} selected customer(s)?`)) {
      return
    }

    setBulkState({ status: 'submitting' })
    try {
      const result = await reengageBulk({ customerIds: [...selectedIds] })
      setBulkState({ status: 'done', notified: result.notified })
      setSelectedIds(new Set())
    } catch (err) {
      const apiError = err.response?.data?.error
      setBulkState({ status: 'error', message: apiError?.message ?? 'Could not re-engage customers.' })
    }
  }

  if (!isAdmin) {
    return <EmptyState message="Admins only." />
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Lost customers</h2>
        <div className="flex items-center gap-3">
          <select
            value={assignedFilter}
            onChange={(event) => setAssignedFilter(event.target.value)}
            className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">All</option>
            <option value="true">Assigned</option>
            <option value="false">Unassigned</option>
          </select>
          <Button
            variant="secondary"
            disabled={selectedIds.size === 0 || bulkState.status === 'submitting'}
            onClick={handleBulkReengage}
          >
            {bulkState.status === 'submitting' ? 'Re-engaging...' : `Re-engage selected (${selectedIds.size})`}
          </Button>
        </div>
      </div>

      {bulkState.status === 'done' && (
        <Alert variant="success" className="mb-4">
          Notified {bulkState.notified} customer(s).
        </Alert>
      )}
      {bulkState.status === 'error' && (
        <Alert variant="error" className="mb-4">
          {bulkState.message}
        </Alert>
      )}

      {state.status === 'loading' && (
        <p className="text-sm text-slate-500 dark:text-slate-400">Loading lost customers...</p>
      )}
      {state.status === 'error' && (
        <p className="text-sm text-red-600 dark:text-red-400">Failed to load lost customers ({state.message})</p>
      )}
      {state.status === 'ok' && state.customers.length === 0 && (
        <EmptyState message="No lost customers in this view." />
      )}

      {state.status === 'ok' && state.customers.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium">
                  <input
                    type="checkbox"
                    checked={selectedIds.size > 0 && selectedIds.size === state.customers.length}
                    onChange={toggleSelectAll}
                    aria-label="Select all"
                    className="h-4 w-4 rounded border-slate-300 dark:border-slate-700"
                  />
                </th>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Email</th>
                <th className="px-4 py-3 font-medium">Assigned employee</th>
                <th className="px-4 py-3 font-medium">Assign to</th>
                <th className="px-4 py-3 font-medium">Re-engage</th>
              </tr>
            </thead>
            <tbody>
              {state.customers.map((customer) => (
                <LostCustomerRow
                  key={customer.id}
                  customer={customer}
                  employees={employees}
                  onAssigned={loadLostCustomers}
                  selected={selectedIds.has(customer.id)}
                  onToggleSelect={toggleSelect}
                />
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
