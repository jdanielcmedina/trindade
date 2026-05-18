import { useState } from 'react'
import { api } from '../lib/api'

function Panel({ title, children }) {
  return (
    <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 hover:border-[var(--border-hover)] transition-colors">
      <h3 className="text-sm font-semibold mb-4">{title}</h3>
      {children}
    </div>
  )
}

export default function Security() {
  const [totp, setTotp] = useState(null)
  const [encInput, setEncInput] = useState('')
  const [encResult, setEncResult] = useState('')
  const [pwd, setPwd] = useState('')
  const [pwdResult, setPwdResult] = useState(null)
  const [backupType, setBackupType] = useState('full')
  const [backupResult, setBackupResult] = useState('')
  const [alertLevel, setAlertLevel] = useState('info')
  const [alertMsg, setAlertMsg] = useState('')
  const [alertResult, setAlertResult] = useState('')

  return (
    <div>
      <div className="mb-8"><h1 className="text-lg font-semibold tracking-tight">Seguranca</h1></div>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <Panel title="Autenticacao 2FA (TOTP)">
          <button onClick={async () => setTotp(await api.totp())} className="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors mb-3">Gerar Segredo</button>
          {totp && (
            <div className="space-y-2 text-xs">
              <div className="flex justify-between py-1.5 border-b border-[var(--border)]"><span className="text-[var(--text3)]">Segredo</span><code className="text-[var(--amber)] font-mono">{totp.secret}</code></div>
              <div className="flex justify-between py-1.5"><span className="text-[var(--text3)]">Codigo</span><strong className="text-lg font-mono tracking-widest">{totp.code}</strong></div>
            </div>
          )}
        </Panel>

        <Panel title="Encriptacao AES-256">
          <textarea value={encInput} onChange={e => setEncInput(e.target.value)} rows={3} placeholder="Texto para encriptar/desencriptar..."
            className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-[var(--accent)] resize-y mb-3 placeholder:text-[var(--text3)]" />
          <div className="flex gap-2 mb-3">
            <button onClick={async () => setEncResult((await api.encrypt(encInput)).result)} className="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] transition-colors">Encriptar</button>
            <button onClick={async () => setEncResult((await api.decrypt(encInput)).result)} className="px-3 py-1.5 text-[11px] rounded-lg bg-[var(--surface2)] border border-[var(--border)] hover:bg-[var(--bg)] transition-colors">Desencriptar</button>
          </div>
          {encResult && <code className="text-[11px] break-all text-[var(--text2)] font-mono">{encResult}</code>}
        </Panel>

        <Panel title="Politica de Passwords">
          <input value={pwd} onChange={async (e) => { setPwd(e.target.value); setPwdResult(await api.policy(e.target.value)) }} placeholder="Testar password..."
            className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)] mb-3 placeholder:text-[var(--text3)]" />
          {pwdResult && (
            <div className="text-xs space-y-1">
              {pwdResult.valid ? <span className="text-[var(--green)] font-medium">Password cumpre a politica.</span> : pwdResult.errors.map((e, i) => <div key={i} className="text-[var(--red)]">{e}</div>)}
            </div>
          )}
        </Panel>

        <Panel title="Backups">
          <div className="flex gap-2 mb-3">
            <select value={backupType} onChange={e => setBackupType(e.target.value)} className="bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)]">
              <option value="full">Completo</option><option value="db">Base de dados</option><option value="files">Ficheiros</option>
            </select>
            <button onClick={async () => { const r = await api.backup(backupType); setBackupResult(r.ok ? `Criado: ${r.file}` : 'Falhou') }} className="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] transition-colors">Criar</button>
          </div>
          {backupResult && <p className="text-xs text-[var(--text2)]">{backupResult}</p>}
        </Panel>

        <Panel title="Alerta de Incidente">
          <div className="flex gap-2 mb-3">
            <select value={alertLevel} onChange={e => setAlertLevel(e.target.value)} className="bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)]">
              <option>info</option><option>warning</option><option>critical</option>
            </select>
            <input value={alertMsg} onChange={e => setAlertMsg(e.target.value)} placeholder="Mensagem..." className="flex-1 bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)] placeholder:text-[var(--text3)]" />
          </div>
          <button onClick={async () => { await api.alert(alertLevel, alertMsg); setAlertResult('Enviado.') }} className="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-[var(--red)] text-white hover:opacity-90 transition-opacity">Enviar Alerta</button>
          {alertResult && <p className="text-xs text-[var(--green)] mt-2">{alertResult}</p>}
        </Panel>

      </div>
    </div>
  )
}
