import { useState } from 'react'
import { api } from '../lib/api'

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
      <h2 className="text-base font-semibold mb-4">Seguranca</h2>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Autenticacao 2FA (TOTP)</h3>
          <button onClick={async () => setTotp(await api.totp())} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md mb-2">Gerar Segredo</button>
          {totp && (
            <div className="text-xs space-y-1">
              <div><span className="text-[#8b949e]">Segredo:</span> <code className="text-[#d29922]">{totp.secret}</code></div>
              <div><span className="text-[#8b949e]">Codigo:</span> <strong className="text-lg">{totp.code}</strong></div>
            </div>
          )}
        </div>

        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Encriptacao AES-256</h3>
          <textarea value={encInput} onChange={e => setEncInput(e.target.value)} rows={3} placeholder="Texto para encriptar..." className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs mb-2" />
          <div className="flex gap-2 mb-2">
            <button onClick={async () => setEncResult((await api.encrypt(encInput)).result)} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md">Encriptar</button>
            <button onClick={async () => setEncResult((await api.decrypt(encInput)).result)} className="px-3 py-1 text-xs bg-[#161b22] border border-[#30363d] hover:bg-[#21262d] rounded-md">Desencriptar</button>
          </div>
          {encResult && <code className="text-xs break-all text-[#8b949e]">{encResult}</code>}
        </div>

        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Politica de Passwords</h3>
          <input value={pwd} onChange={async (e) => { setPwd(e.target.value); setPwdResult(await api.policy(e.target.value)) }} placeholder="Testar password..." className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs mb-2" />
          {pwdResult && (
            <div className="text-xs">
              {pwdResult.valid ? <span className="text-[#238636]">Password valida.</span> : pwdResult.errors.map((e, i) => <div key={i} className="text-[#da3633]">{e}</div>)}
            </div>
          )}
        </div>

        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Backups</h3>
          <div className="flex gap-2 mb-2">
            <select value={backupType} onChange={e => setBackupType(e.target.value)} className="bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
              <option value="full">Completo</option><option value="db">Base de dados</option><option value="files">Ficheiros</option>
            </select>
            <button onClick={async () => { const r = await api.backup(backupType); setBackupResult(r.ok ? `Criado: ${r.file}` : 'Falhou') }} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md">Criar Backup</button>
          </div>
          {backupResult && <p className="text-xs">{backupResult}</p>}
        </div>

        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Alerta de Incidente</h3>
          <div className="flex gap-2 mb-2">
            <select value={alertLevel} onChange={e => setAlertLevel(e.target.value)} className="bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
              <option>info</option><option>warning</option><option>critical</option>
            </select>
            <input value={alertMsg} onChange={e => setAlertMsg(e.target.value)} placeholder="Mensagem..." className="flex-1 bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs" />
          </div>
          <button onClick={async () => { await api.alert(alertLevel, alertMsg); setAlertResult('Enviado') }} className="px-3 py-1 text-xs bg-[#da3633]/80 hover:bg-[#da3633] rounded-md">Enviar Alerta</button>
          {alertResult && <p className="text-xs mt-1 text-[#238636]">{alertResult}</p>}
        </div>
      </div>
    </div>
  )
}
