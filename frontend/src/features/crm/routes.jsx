import LostCustomersPage from './LostCustomersPage'
import KpiLeaderboard from './KpiLeaderboard'

const crmRoutes = [
  { path: '/customers/lost', element: <LostCustomersPage /> },
  { path: '/employees/kpi', element: <KpiLeaderboard /> },
]

export default crmRoutes
