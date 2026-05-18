import { useState, useCallback, useRef } from 'react'
import { ReactFlow, MiniMap, Controls, Background, useNodesState, useEdgesState, addEdge, MarkerType, Handle, Position } from '@xyflow/react'
import '@xyflow/react/dist/style.css'

const nodeTypes = { trigger: TriggerNode, database: DatabaseNode, http: HttpNode, response: ResponseNode }

const initialNodes = [
  { id: '1', type: 'trigger', position: { x: 150, y: 50 }, data: { label: 'List Users', method: 'GET', path: '/users' } },
  { id: '2', type: 'database', position: { x: 150, y: 200 }, data: { label: 'Fetch rows', table: 'users', query: 'select' } },
  { id: '3', type: 'response', position: { x: 150, y: 350 }, data: { label: 'JSON 200', status: 200 } },
]
const initialEdges = [
  { id: 'e1-2', source: '1', target: '2', animated: true, style: { stroke: '#3b82f6', strokeWidth: 1.5 }, markerEnd: { type: MarkerType.ArrowClosed, color: '#3b82f6' } },
  { id: 'e2-3', source: '2', target: '3', animated: true, style: { stroke: '#22c55e', strokeWidth: 1.5 }, markerEnd: { type: MarkerType.ArrowClosed, color: '#22c55e' } },
]

const nodeBase = "bg-[#111113] border rounded-xl px-4 py-3 min-w-[180px] text-xs shadow-lg shadow-black/20 transition-all duration-150"
const handleStyle = { width: 8, height: 8, border: '2px solid #333' }

function TriggerNode({ data, selected }) {
  const colors = { GET: 'border-emerald-500/50', POST: 'border-blue-500/50', PUT: 'border-amber-500/50', DELETE: 'border-red-500/50' }
  return (
    <div className={`${nodeBase} ${colors[data.method] || 'border-gray-700'} ${selected ? 'ring-1 ring-indigo-500' : ''}`}>
      <Handle type="source" position={Position.Bottom} style={handleStyle} />
      <div className="flex items-center gap-2 mb-2">
        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-400">{data.method}</span>
        <span className="font-mono font-medium text-white/90">{data.path}</span>
      </div>
      <p className="text-[var(--text3)] leading-relaxed">{data.label}</p>
      {data.auth && <span className="inline-block mt-2 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-500/10 text-amber-500">{data.auth}</span>}
    </div>
  )
}

function DatabaseNode({ data, selected }) {
  return (
    <div className={`${nodeBase} border-amber-500/30 ${selected ? 'ring-1 ring-amber-500' : ''}`}>
      <Handle type="target" position={Position.Top} style={handleStyle} />
      <div className="flex items-center gap-2 mb-2">
        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-400">{data.query}</span>
        <span className="font-mono font-medium text-white/90">{data.table}</span>
      </div>
      <p className="text-[var(--text3)]">{data.label}</p>
      <Handle type="source" position={Position.Bottom} style={handleStyle} />
    </div>
  )
}

function HttpNode({ data, selected }) {
  return (
    <div className={`${nodeBase} border-sky-500/30 ${selected ? 'ring-1 ring-sky-500' : ''}`}>
      <Handle type="target" position={Position.Top} style={handleStyle} />
      <div className="flex items-center gap-2 mb-1">
        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sky-500/20 text-sky-400">{data.method || 'GET'}</span>
        <span className="font-mono text-[11px] text-white/70 truncate max-w-[140px]">{data.url}</span>
      </div>
      <Handle type="source" position={Position.Bottom} style={handleStyle} />
    </div>
  )
}

function ResponseNode({ data, selected }) {
  const isError = data.status >= 400
  return (
    <div className={`${nodeBase} ${isError ? 'border-red-500/30' : 'border-emerald-500/30'} ${selected ? 'ring-1 ring-white/20' : ''}`}>
      <Handle type="target" position={Position.Top} style={handleStyle} />
      <div className="flex items-center gap-2">
        <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${isError ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400'}`}>{data.status}</span>
        <span className="text-[var(--text2)]">{data.label}</span>
      </div>
    </div>
  )
}

export default function WorkflowEditor() {
  const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes)
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges)
  const [selected, setSelected] = useState(null)
  const [reactFlowInstance, setReactFlowInstance] = useState(null)

  const onConnect = useCallback((params) => setEdges((eds) => addEdge({ ...params, animated: true, style: { stroke: '#6366f1', strokeWidth: 1.5 }, markerEnd: { type: MarkerType.ArrowClosed, color: '#6366f1' } }, eds)), [setEdges])
  const onDragOver = useCallback((e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move' }, [])
  const onDrop = useCallback((e) => {
    e.preventDefault()
    const type = e.dataTransfer.getData('application/reactflow')
    if (!type || !reactFlowInstance) return
    const pos = reactFlowInstance.screenToFlowPosition({ x: e.clientX, y: e.clientY })
    setNodes((nds) => nds.concat({ id: String(Date.now()), type, position: pos, data: { label: type, method: 'GET', path: '/', query: 'select', table: 'table', status: 200 } }))
  }, [reactFlowInstance, setNodes])
  const onNodeClick = useCallback((_, node) => setSelected(node), [])

  const updateNode = (id, data) => setNodes((nds) => nds.map(n => n.id === id ? { ...n, data: { ...n.data, ...data } } : n))

  const addNode = (type) => {
    setNodes((nds) => nds.concat({ id: String(Date.now()), type, position: { x: 250, y: nds.length * 120 + 50 }, data: { label: type, method: 'GET', path: '/', query: 'select', table: 'table', status: 200 } }))
  }

  const exportCode = () => {
    const triggers = nodes.filter(n => n.type === 'trigger')
    let code = "<?php\n\n"
    triggers.forEach(t => {
      const next = edges.filter(e => e.source === t.id).map(e => nodes.find(n => n.id === e.target))
      const auth = t.data.auth ? `\n    if (!$app->csrf_check() && !$app->token()) return $app->error('Unauthorized', 401);` : ''
      const dbCalls = next.filter(n => n.type === 'database').map(n => `    $rows = $app->db->${n.data.query}('${n.data.table}', '${n.data.columns || '*'}');`)
      code += `$app->on('${t.data.method} ${t.data.path}', function () use ($app) {${auth}\n${dbCalls.join('\n')}\n    return $app->success($rows ?? []);\n});\n\n`
    })
    return code
  }

  return (
    <div className="h-full flex flex-col" style={{ height: 'calc(100vh - 4rem)' }}>
      <div className="flex items-center justify-between mb-6 shrink-0">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Workflows</h1>
          <p className="text-sm text-[var(--text3)] mt-1">Visual route builder. Drag to connect nodes.</p>
        </div>
        <div className="flex gap-1.5">
          {['trigger', 'database', 'http', 'response'].map(t => (
            <button key={t} onClick={() => addNode(t)} className="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-[var(--surface2)] border border-[var(--border)] hover:border-[var(--border-hover)] hover:bg-[var(--surface)] transition-all capitalize">{t}</button>
          ))}
          <button onClick={() => navigator.clipboard.writeText(exportCode())} className="px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-white text-black hover:bg-gray-200 transition-colors">Export Code</button>
        </div>
      </div>

      <div className="flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-xl overflow-hidden">
        <ReactFlow
          nodes={nodes} edges={edges}
          onNodesChange={onNodesChange} onEdgesChange={onEdgesChange}
          onConnect={onConnect} onInit={setReactFlowInstance}
          onDrop={onDrop} onDragOver={onDragOver} onNodeClick={onNodeClick}
          nodeTypes={nodeTypes} fitView deleteKeyCode={['Backspace', 'Delete']}
        >
          <Controls className="!rounded-lg !overflow-hidden !border !border-[var(--border)]" />
          <MiniMap nodeColor={(n) => n.type === 'trigger' ? '#818cf8' : n.type === 'database' ? '#f59e0b' : '#22c55e'} maskColor="rgba(10,10,11,0.85)" className="!rounded-lg !overflow-hidden !border !border-[var(--border)]" />
          <Background gap={24} color="#1c1c1f" />
        </ReactFlow>
      </div>

      {selected && (
        <div className="mt-4 bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 shrink-0 animate-in">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <span className="text-[11px] font-bold uppercase tracking-wider text-[var(--text3)]">{selected.type}</span>
              <span className="text-[var(--text3)]">—</span>
              <span className="text-xs text-[var(--text2)]">Properties</span>
            </div>
            <button onClick={() => setSelected(null)} className="text-xs text-[var(--text3)] hover:text-[var(--text)] transition-colors">Close</button>
          </div>
          <div className="grid grid-cols-4 gap-4">
            {selected.type === 'trigger' && (
              <>
                <Field label="Method">
                  <Select value={selected.data.method || 'GET'} onChange={e => updateNode(selected.id, { method: e.target.value })} options={['GET', 'POST', 'PUT', 'DELETE', 'PATCH']} />
                </Field>
                <Field label="Path"><Input value={selected.data.path || ''} onChange={e => updateNode(selected.id, { path: e.target.value })} placeholder="/users/:id" /></Field>
                <Field label="Auth">
                  <Select value={selected.data.auth || ''} onChange={e => updateNode(selected.id, { auth: e.target.value })} options={['', 'JWT', 'CSRF', 'Session']} placeholder="None" />
                </Field>
                <Field label="Rate Limit">
                  <label className="flex items-center gap-2 pt-6 text-xs cursor-pointer">
                    <input type="checkbox" checked={selected.data.rate || false} onChange={e => updateNode(selected.id, { rate: e.target.checked })} className="w-3.5 h-3.5 rounded accent-indigo-500" />
                    Enable
                  </label>
                </Field>
              </>
            )}
            {selected.type === 'database' && (
              <>
                <Field label="Operation">
                  <Select value={selected.data.query || 'select'} onChange={e => updateNode(selected.id, { query: e.target.value })} options={['select', 'get', 'insert', 'update', 'delete', 'count', 'pages']} />
                </Field>
                <Field label="Table"><Input value={selected.data.table || ''} onChange={e => updateNode(selected.id, { table: e.target.value })} placeholder="users" /></Field>
                <Field label="Columns"><Input value={selected.data.columns || '*'} onChange={e => updateNode(selected.id, { columns: e.target.value })} /></Field>
              </>
            )}
            {selected.type === 'response' && (
              <Field label="Status Code">
                <Input type="number" value={selected.data.status || 200} onChange={e => updateNode(selected.id, { status: parseInt(e.target.value) })} />
              </Field>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

function Field({ label, children }) {
  return (
    <div>
      <label className="block text-[11px] font-medium text-[var(--text3)] uppercase tracking-wider mb-1.5">{label}</label>
      {children}
    </div>
  )
}

function Input({ className = '', ...props }) {
  return <input {...props} className={`w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)] transition-colors ${className}`} />
}

function Select({ options = [], placeholder, ...props }) {
  return (
    <select {...props} className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)] transition-colors cursor-pointer">
      {placeholder && <option value="">{placeholder}</option>}
      {options.map(o => <option key={o} value={o}>{o || 'None'}</option>)}
    </select>
  )
}
