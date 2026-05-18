import { useContext } from 'react'
import { AppContext } from '../App'

function DashIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>) }
function BoltIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>) }
function RouteIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>) }
function DBIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125S3.75 12.403 3.75 10.125V6.375" /></svg>) }
function FileIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>) }
function TerminalIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" /></svg>) }
function LockIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>) }
function AuditIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 16.5h4.5M9.75 13.5h4.5" /></svg>) }
function LogIcon() { return (<svg className="w-[18px] h-[18px] shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>) }

const icons = {
  dashboard: <DashIcon />,
  workflow: <BoltIcon />,
  routes: <RouteIcon />,
  database: <DBIcon />,
  files: <FileIcon />,
  console: <TerminalIcon />,
  security: <LockIcon />,
  audit: <AuditIcon />,
  logs: <LogIcon />,
}

const items = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'workflow', label: 'Workflows' },
  { id: 'routes', label: 'Rotas' },
  { id: 'database', label: 'Base de Dados' },
  { id: 'files', label: 'Ficheiros' },
  { id: 'console', label: 'Consola' },
  { id: 'security', label: 'Seguranca' },
  { id: 'audit', label: 'Auditoria' },
  { id: 'logs', label: 'Logs' },
]

export default function Sidebar({ onLogout }) {
  const { page, setPage } = useContext(AppContext)

  return (
    <div className="flex flex-col w-60 border-r border-[var(--border)] bg-[var(--surface)]">
      <div className="flex items-center gap-2.5 px-5 h-14 border-b border-[var(--border)] shrink-0">
        <div className="w-6 h-6 rounded-md bg-[var(--accent)] flex items-center justify-center">
          <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <span className="text-sm font-semibold tracking-tight">Trindade</span>
        <span className="text-[11px] text-[var(--text3)] font-medium ml-auto">Studio</span>
      </div>

      <nav className="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">
        {items.map(i => (
          <button
            key={i.id}
            onClick={() => setPage(i.id)}
            className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] transition-all duration-150
              ${page === i.id
                ? 'bg-[var(--accent-bg)] text-[var(--accent)] font-medium'
                : 'text-[var(--text2)] hover:text-[var(--text)] hover:bg-[var(--surface2)]'
              }`}
          >
            {icons[i.id]}
            <span className="truncate">{i.label}</span>
          </button>
        ))}
      </nav>

      <div className="px-4 py-3 border-t border-[var(--border)]">
        <button
          onClick={onLogout}
          className="w-full text-left text-[12px] text-[var(--text3)] hover:text-[var(--red)] transition-colors py-1"
        >
          Terminar sessao
        </button>
      </div>
    </div>
  )
}
