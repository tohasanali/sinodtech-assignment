import { createBrowserRouter } from 'react-router-dom'
import Layout from './components/Layout'
import ProtectedRoute from './components/ProtectedRoute'
import authRoutes from './features/auth/routes'
import dashboardRoutes from './features/dashboard/routes'
import productRoutes from './features/products/routes'
import saleRoutes from './features/sales/routes'
import customerRoutes from './features/customers/routes'

const router = createBrowserRouter([
  ...authRoutes,
  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <Layout />,
        children: [...dashboardRoutes, ...productRoutes, ...saleRoutes, ...customerRoutes],
      },
    ],
  },
])

export default router
