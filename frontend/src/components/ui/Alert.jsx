const VARIANTS = {
  error:
    'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400',
  success:
    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-400',
}

export default function Alert({ variant = 'error', children, className = '' }) {
  return (
    <div
      role={variant === 'error' ? 'alert' : 'status'}
      className={`rounded-md border px-4 py-3 text-sm ${VARIANTS[variant]} ${className}`}
    >
      {children}
    </div>
  )
}
