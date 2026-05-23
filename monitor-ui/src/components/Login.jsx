import { useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Zap, Eye, EyeOff, AlertCircle } from 'lucide-react'
import { login } from '../lib/api'

export default function Login({ onSuccess }) {
  const [user, setUser] = useState('')
  const [pass, setPass] = useState('')
  const [err, setErr] = useState('')
  const [show, setShow] = useState(false)
  const [loading, setLoading] = useState(false)

  const submit = async e => {
    e.preventDefault()
    setLoading(true)
    setErr('')
    const r = await login(user, pass)
    setLoading(false)
    if (r?.ok) onSuccess()
    else setErr(r?.error || 'Invalid credentials')
  }

  return (
    <div className="min-h-screen bg-[#0a0a0b] flex items-center justify-center p-4">
      <motion.div initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} className="w-full max-w-[360px]">
        <div className="text-center mb-8">
          <div className="w-9 h-9 rounded-lg bg-white/10 inline-flex items-center justify-center mb-4 ring-1 ring-white/5">
            <Zap className="w-4.5 h-4.5 text-white" fill="currentColor" />
          </div>
          <h1 className="text-[15px] font-semibold tracking-tight text-zinc-100">Trindade Monitor</h1>
          <p className="text-[13px] text-zinc-500 mt-1">Sign in to your dashboard</p>
        </div>

        <form onSubmit={submit} className="bg-zinc-900/40 border border-zinc-800/50 rounded-xl p-6 space-y-4 backdrop-blur-sm">
          <div className="space-y-1.5">
            <label className="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">Username</label>
            <input value={user} onChange={e => setUser(e.target.value)} autoFocus
              className="w-full bg-black/50 border border-zinc-800/80 rounded-lg px-3 py-2 text-[13px] text-zinc-200 outline-none focus:border-zinc-700/80 focus:ring-1 focus:ring-zinc-700/50 transition-all placeholder:text-zinc-600" />
          </div>
          <div className="space-y-1.5">
            <label className="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">Password</label>
            <div className="relative">
              <input type={show ? 'text' : 'password'} value={pass} onChange={e => setPass(e.target.value)}
                className="w-full bg-black/50 border border-zinc-800/80 rounded-lg px-3 py-2 pr-10 text-[13px] text-zinc-200 outline-none focus:border-zinc-700/80 focus:ring-1 focus:ring-zinc-700/50 transition-all placeholder:text-zinc-600" />
              <button type="button" onClick={() => setShow(!show)} className="absolute right-2.5 top-2 text-zinc-600 hover:text-zinc-400 transition-colors">
                {show ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
              </button>
            </div>
          </div>

          <AnimatePresence>
            {err && (
              <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} exit={{ opacity: 0, height: 0 }}
                className="flex items-center gap-2 text-[12px] text-red-400/90 bg-red-500/5 border border-red-500/10 rounded-lg px-3 py-2">
                <AlertCircle className="w-3.5 h-3.5 shrink-0" /> {err}
              </motion.div>
            )}
          </AnimatePresence>

          <button type="submit" disabled={loading}
            className="w-full bg-white/90 hover:bg-white text-black rounded-lg py-2 text-[13px] font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            {loading ? 'Signing in...' : 'Sign in'}
          </button>
        </form>

        <p className="text-center text-[11px] text-zinc-600 mt-4">Trindade Monitor v1.0</p>
      </motion.div>
    </div>
  )
}
