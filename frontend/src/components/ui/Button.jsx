const VARIANTS = {
  primary:
    'rounded-md px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60',
  secondary:
    'rounded-md px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800',
  link: 'text-blue-600 hover:underline dark:text-blue-400',
  danger: 'text-red-600 hover:underline dark:text-red-400',
}

export default function Button({ variant = 'primary', className = '', type = 'button', ...props }) {
  return (
    <button type={type} className={`text-sm font-medium transition-colors ${VARIANTS[variant]} ${className}`} {...props} />
  )
}
