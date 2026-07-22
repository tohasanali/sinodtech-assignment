import { useState } from 'react'
import { Alert, Button, Field } from '../../components/ui'

const EMPTY_FORM = { name: '', sku: '', price: '', description: '' }

export default function ProductForm({ initialValues, onSubmit, onCancel, submitLabel }) {
  const [form, setForm] = useState(initialValues ?? EMPTY_FORM)
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  function updateField(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setError(null)

    try {
      await onSubmit({ ...form, price: Number(form.price) })
    } catch (err) {
      const apiError = err.response?.data?.error
      setError(apiError?.message ?? 'Could not save product.')
      setSubmitting(false)
      return
    }

    setSubmitting(false)
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="mb-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field
          id="name"
          label="Name"
          type="text"
          value={form.name}
          onChange={(event) => updateField('name', event.target.value)}
          required
        />

        <Field
          id="sku"
          label="SKU"
          type="text"
          value={form.sku}
          onChange={(event) => updateField('sku', event.target.value)}
          required
        />

        <Field
          id="price"
          label="Price"
          type="number"
          step="0.01"
          min="0"
          value={form.price}
          onChange={(event) => updateField('price', event.target.value)}
          required
        />

        <Field
          id="description"
          label="Description"
          type="text"
          value={form.description ?? ''}
          onChange={(event) => updateField('description', event.target.value)}
        />
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      <div className="flex items-center gap-3">
        <Button type="submit" disabled={submitting}>
          {submitting ? 'Saving...' : submitLabel}
        </Button>
        <Button type="button" variant="secondary" onClick={onCancel}>
          Cancel
        </Button>
      </div>
    </form>
  )
}
