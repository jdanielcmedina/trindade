import { useState } from 'react'
import Login from './components/Login'
import Dashboard from './components/Dashboard'

export default function App() {
  const [auth, setAuth] = useState(false)
  if (!auth) return <Login onSuccess={() => setAuth(true)} />
  return <Dashboard />
}
