import { useState, createContext } from 'react'
import Login from './pages/Login'
import Sidebar from './components/Sidebar'
import Dashboard from './pages/Dashboard'
import Routes from './pages/Routes'
import Database from './pages/Database'
import Files from './pages/Files'
import Console from './pages/Console'
import Security from './pages/Security'
import Audit from './pages/Audit'
import Logs from './pages/Logs'
import WorkflowEditor from './pages/WorkflowEditor'

export const AppContext = createContext({})

export default function App() {
  const [auth, setAuth] = useState(false)
  const [page, setPage] = useState('dashboard')

  if (!auth) return <Login onLogin={() => setAuth(true)} />

  const pages = {
    dashboard: <Dashboard />,
    routes: <Routes />,
    workflow: <WorkflowEditor />,
    database: <Database />,
    files: <Files />,
    console: <Console />,
    security: <Security />,
    audit: <Audit />,
    logs: <Logs />,
  }

  return (
    <div className="flex h-screen bg-[var(--bg)] overflow-hidden">
      <Sidebar onLogout={() => setAuth(false)} />
      <main className="flex-1 overflow-y-auto">
        <div className="max-w-5xl mx-auto px-8 py-8">
          {pages[page] || <Dashboard />}
        </div>
      </main>
    </div>
  )
}
