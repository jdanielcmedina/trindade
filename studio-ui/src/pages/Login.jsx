import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Login({ onLogin }) {
  const [pwd, setPwd] = useState('')
  const [err, setErr] = useState('')

  const submit = async (e) => {
    e.preventDefault()
    const ok = await api.auth(pwd)
    if (ok) onLogin()
    else setErr('Password invalida')
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#0d1117]">
      <form onSubmit={submit} className="w-80 bg-[#161b22] border border-[#30363d] rounded-xl p-6 text-center">
        <h1 className="text-lg font-semibold text-[#3b82f6] mb-4">Trindade Studio</h1>
        <input
          type="password" value={pwd} onChange={e => setPwd(e.target.value)}
          placeholder="Password" autoFocus
          className="w-full bg-[#0d1117] border border-[#30363d] rounded-md px-3 py-2 text-sm text-center mb-3 outline-none focus:border-[#3b82f6]"
        />
        {err && <p className="text-xs text-[#da3633] mb-2">{err}</p>}
        <button className="w-full bg-[#3b82f6] hover:bg-[#2563eb] rounded-md py-2 text-sm font-medium transition-colors">
          Entrar
        </button>
      </form>
    </div>
  )
}
