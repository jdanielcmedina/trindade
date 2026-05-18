import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Files() {
  const [files, setFiles] = useState({})
  const [editing, setEditing] = useState(null)
  const [content, setContent] = useState('')

  useEffect(() => { api.files().then(setFiles) }, [])

  const open = async (dir, name) => {
    const r = await api.fileGet(dir, name)
    setEditing({ dir, name })
    setContent(r.content || '')
  }

  const save = async () => {
    await api.fileSave(editing.dir, editing.name, content)
    setEditing(null)
    api.files().then(setFiles)
  }

  return (
    <div>
      <div className="mb-8"><h1 className="text-lg font-semibold tracking-tight">Ficheiros</h1></div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {Object.entries(files).map(([dir, items]) => (
          <div key={dir} className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-4">
            <h3 className="text-xs font-semibold uppercase tracking-wider text-[var(--accent)] mb-3">{dir}/</h3>
            <div className="space-y-0.5">
              {items.map(f => (
                <button key={f} onClick={() => open(dir, f)} className="w-full text-left px-3 py-1.5 text-[13px] text-[var(--text2)] hover:text-[var(--text)] hover:bg-[var(--surface2)] rounded-lg transition-colors font-mono">
                  {f}
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>

      {editing && (
        <div className="mt-6 bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5">
          <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-semibold font-mono">{editing.dir}/{editing.name}</h3>
            <span className="text-[11px] text-[var(--text3)]">{content.split('\n').length} lines</span>
          </div>
          <textarea value={content} onChange={e => setContent(e.target.value)} rows={22}
            className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg p-4 text-xs font-mono leading-relaxed outline-none focus:border-[var(--accent)] resize-y" />
          <button onClick={save} className="mt-3 px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors">Guardar</button>
        </div>
      )}
    </div>
  )
}
