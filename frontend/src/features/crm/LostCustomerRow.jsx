import { useState } from 'react'
import { assignCustomer, reengageCustomer } from '../../api/customers'
import { Alert, Button } from '../../components/ui'

export default function LostCustomerRow({ customer, employees, onAssigned, selected, onToggleSelect }) {
  const [selectedEmployeeId, setSelectedEmployeeId] = useState(customer.employee?.id ?? '')
  const [assigning, setAssigning] = useState(false)
  const [assignError, setAssignError] = useState(null)

  const [reengaging, setReengaging] = useState(false)
  const [reengageError, setReengageError] = useState(null)
  const [reengaged, setReengaged] = useState(false)

  async function handleAssign() {
    if (!selectedEmployeeId) {
      return
    }

    setAssigning(true)
    setAssignError(null)
    try {
      await assignCustomer(customer.id, Number(selectedEmployeeId))
      await onAssigned()
    } catch (err) {
      const apiError = err.response?.data?.error
      setAssignError(apiError?.message ?? 'Could not assign employee.')
    } finally {
      setAssigning(false)
    }
  }

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
      <td className="px-4 py-3 align-top">
        <input
          type="checkbox"
          checked={selected}
          onChange={() => onToggleSelect(customer.id)}
          aria-label={`Select ${customer.name}`}
          className="h-4 w-4 rounded border-slate-300 dark:border-slate-700"
        />
      </td>
      <td className="px-4 py-3 align-top text-slate-900 dark:text-slate-100">{customer.name}</td>
      <td className="px-4 py-3 align-top text-slate-600 dark:text-slate-400">{customer.email}</td>
      <td className="px-4 py-3 align-top text-slate-600 dark:text-slate-400">
        {customer.employee?.name ?? '—'}
      </td>
      <td className="px-4 py-3 align-top">
        <div className="flex items-center gap-2">
          <select
            value={selectedEmployeeId}
            onChange={(event) => setSelectedEmployeeId(event.target.value)}
            disabled={assigning}
            className="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">Select employee</option>
            {employees.map((employee) => (
              <option key={employee.id} value={employee.id}>
                {employee.name}
              </option>
            ))}
          </select>
          <Button variant="secondary" disabled={!selectedEmployeeId || assigning} onClick={handleAssign}>
            {assigning ? 'Assigning...' : 'Assign'}
          </Button>
        </div>
        {assignError && (
          <Alert variant="error" className="mt-2">
            {assignError}
          </Alert>
        )}
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
