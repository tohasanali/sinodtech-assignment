import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { BrandMark, Button } from './ui'

const ROLE_STYLES = {
  admin: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
  employee: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  api_consumer: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
}

const NAV_LINKS = [
  { to: '/', label: 'Dashboard', end: true },
  { to: '/products', label: 'Products' },
  { to: '/customers', label: 'Customers' },
  { to: '/customers/lost', label: 'Lost Customers', adminOnly: true },
  { to: '/customers/mine', label: 'My Customers', employeeOnly: true },
  { to: '/sales/new', label: 'New Sale' },
  { to: '/sales', label: 'Sales History' },
  { to: '/employees/kpi', label: 'KPI', adminOnly: true },
]

export default function Layout() {
  const { user, logout, activeBranchId, switchBranch } = useAuth()
  const navLinks = NAV_LINKS.filter((link) => {
    if (link.adminOnly) {
      return user.role === 'admin'
    }
    if (link.employeeOnly) {
      return user.role === 'employee'
    }
    return true
  })
  const branches = user.branches ?? []
  const showBranchSwitcher = user.role === 'employee' && branches.length > 1

  return (
    <div>
      <header className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <div className="flex items-center gap-3">
            <BrandMark />
            <div>
              <h1 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
                SinodTech
              </h1>
              <p className="text-sm text-slate-500 dark:text-slate-400">
                Sales, Inventory &amp; CRM System
              </p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            {showBranchSwitcher && (
              <select
                value={activeBranchId ?? ''}
                onChange={(event) => switchBranch(Number(event.target.value))}
                aria-label="Active branch"
                className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="" disabled>
                  Select a branch
                </option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </select>
            )}
            <div className="text-right">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-100">
                {user.name}
              </p>
              <span
                className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${ROLE_STYLES[user.role] ?? ROLE_STYLES.employee}`}
              >
                {user.role.replace('_', ' ')}
              </span>
            </div>
            <Button variant="secondary" onClick={logout}>
              Log out
            </Button>
          </div>
        </div>
        <div className="border-t border-slate-200 dark:border-slate-800">
          <div className="mx-auto max-w-6xl px-4 py-2">
            <nav className="flex items-center gap-1 text-sm font-medium text-slate-600 dark:text-slate-300">
              {navLinks.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  end={link.end}
                  className={({ isActive }) =>
                    `rounded-md px-3 py-1.5 transition-colors ${
                      isActive
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                        : 'hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                    }`
                  }
                >
                  {link.label}
                </NavLink>
              ))}
            </nav>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  )
}
