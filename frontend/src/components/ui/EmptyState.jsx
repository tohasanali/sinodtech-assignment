export default function EmptyState({ message, action }) {
  return (
    <div className="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center dark:border-slate-700">
      <p className="text-sm text-slate-500 dark:text-slate-400">{message}</p>
      {action && <div className="mt-3">{action}</div>}
    </div>
  )
}
