import SaleCreate from './SaleCreate'
import SalesHistory from './SalesHistory'

const saleRoutes = [
  { path: '/sales/new', element: <SaleCreate /> },
  { path: '/sales', element: <SalesHistory /> },
]

export default saleRoutes
