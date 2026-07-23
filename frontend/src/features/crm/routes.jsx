import LostCustomersPage from './LostCustomersPage'
import KpiLeaderboard from './KpiLeaderboard'
import MyCustomersPage from './MyCustomersPage'

const crmRoutes = [
  { path: '/customers/lost', element: <LostCustomersPage /> },
  { path: '/employees/kpi', element: <KpiLeaderboard /> },
  { path: '/customers/mine', element: <MyCustomersPage /> },
]

export default crmRoutes
