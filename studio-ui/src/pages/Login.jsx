import { useState } from 'react'
import { api } from '../lib/api'

export default function Login({ onLogin }) {
  const [pwd, setPwd] = useState('')
  const [err, setErr] = useState('')
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setErr('')
    const ok = await api.auth(pwd)
    setLoading(false)
    if (ok) onLogin()
    else setErr('Password invalida')
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--bg)]">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="w-10 h-10 rounded-xl bg-[var(--accent)] inline-flex items-center justify-center mb-4">
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h1 className="text-xl font-semibold tracking-tight">Trindade Studio</h1>
          <p className="text-sm text-[var(--text3)] mt-1">Enter your password to continue</p>
        </div>

        <form onSubmit={submit} className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-6">
          <input
            type="password" value={pwd} onChange={e => setPwd(e.target.value)}
            placeholder="Password" autoFocus
            className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-4 py-2.5 text-sm text-center outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-all placeholder:text-[var(--text3)]"
          />
          {err && <p className="text-xs text-[var(--red)] mt-2 text-center">{err}</p>}
          <button
            disabled={loading}
            className="w-full mt-3 bg-white text-black rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-200 transition-colors disabled:opacity-50"
          >
            {loading ? 'Entrando...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  )
}
