import { useContext } from 'react'
import { AppContext } from '../App'

const items = [
  { id: 'dashboard', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { id: 'workflow', label: 'Workflows', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  { id: 'routes', label: 'Rotas', icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01' },
  { id: 'database', label: 'Base de Dados', icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4' },
  { id: 'files', label: 'Ficheiros', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'console', label: 'Consola', icon: 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { id: 'security', label: 'Seguranca', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
  { id: 'audit', label: 'Auditoria', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
  { id: 'logs', label: 'Logs', icon: 'M4 6h16M4 10h16M4 14h16M4 18h16' },
]

export default function Sidebar({ onLogout }) {
  const { page, setPage } = useContext(AppContext)

  return (
    <nav className="w-56 bg-[#161b22] border-r border-[#30363d] flex flex-col p-3 gap-0.5">
      <div className="text-sm font-semibold text-[#3b82f6] px-3 py-2 mb-1">
        Trindade Studio
      </div>
      {items.map(i => (
        <button
          key={i.id}
          onClick={() => setPage(i.id)}
          className={`flex items-center gap-2.5 px-3 py-1.5 rounded-md text-[13px] transition-colors text-left
            ${page === i.id ? 'bg-[#1f2a45] text-[#3b82f6] font-medium' : 'text-[#8b949e] hover:bg-[#1c2333] hover:text-[#e6edf3]'}`}
        >
          <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={i.icon} />
          </svg>
          {i.label}
        </button>
      ))}
      <div className="mt-auto pt-3 border-t border-[#30363d]">
        <button
          onClick={onLogout}
          className="text-[11px] text-[#8b949e] hover:text-[#da3633] px-3 py-1"
        >
          Terminar sessao
        </button>
      </div>
    </nav>
  )
}
