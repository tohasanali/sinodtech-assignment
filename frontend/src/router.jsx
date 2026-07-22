import { createBrowserRouter } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute'
import authRoutes from './features/auth/routes'
import dashboardRoutes from './features/dashboard/routes'

const router = createBrowserRouter([
  ...authRoutes,
  { element: <ProtectedRoute />, children: dashboardRoutes },
])

export default router
