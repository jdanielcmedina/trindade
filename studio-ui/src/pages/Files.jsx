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
  }

  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Ficheiros</h2>

      <div className="space-y-3">
        {Object.entries(files).map(([dir, items]) => (
          <div key={dir} className="bg-[#161b22] border border-[#30363d] rounded-lg p-3">
            <h3 className="text-sm font-semibold text-[#3b82f6] mb-1">{dir}/</h3>
            <div className="space-y-0.5">
              {items.map(f => (
                <button key={f} onClick={() => open(dir, f)} className="block w-full text-left px-2 py-1 text-xs text-[#8b949e] hover:bg-[#0d1117] hover:text-white rounded">
                  {f}
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>

      {editing && (
        <div className="mt-4 bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">{editing.dir}/{editing.name}</h3>
          <textarea value={content} onChange={e => setContent(e.target.value)} rows={20} className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs font-mono" />
          <button onClick={save} className="mt-2 px-3 py-1 text-xs bg-[#238636] hover:bg-[#2ea043] rounded-md">Guardar</button>
        </div>
      )}
    </div>
  )
}
