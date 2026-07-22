export default function Card({ className = '', noPadding = false, ...props }) {
  return (
    <div
      className={`rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 ${noPadding ? '' : 'p-6'} ${className}`}
      {...props}
    />
  )
}
