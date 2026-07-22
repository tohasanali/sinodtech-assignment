export default function BrandMark({ className = '' }) {
  return (
    <span
      className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-blue-600 text-sm font-semibold text-white ${className}`}
    >
      S
    </span>
  )
}
