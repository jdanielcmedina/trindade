import { useState, useCallback, useRef } from 'react'
import {
  ReactFlow, MiniMap, Controls, Background, useNodesState, useEdgesState,
  addEdge, Panel, MarkerType,
} from '@xyflow/react'
import '@xyflow/react/dist/style.css'

const nodeTypes = {
  trigger: TriggerNode,
  database: DatabaseNode,
  http: HttpNode,
  response: ResponseNode,
}

const initialNodes = [
  {
    id: '1', type: 'trigger', position: { x: 250, y: 50 },
    data: { label: 'GET /users', method: 'GET', path: '/users' },
  },
  {
    id: '2', type: 'database', position: { x: 250, y: 200 },
    data: { label: 'SELECT users', table: 'users', query: 'select', columns: '*' },
  },
  {
    id: '3', type: 'response', position: { x: 250, y: 350 },
    data: { label: '200 OK', status: 200 },
  },
]

const initialEdges = [
  { id: 'e1-2', source: '1', target: '2', markerEnd: { type: MarkerType.ArrowClosed } },
  { id: 'e2-3', source: '2', target: '3', markerEnd: { type: MarkerType.ArrowClosed } },
]

function TriggerNode({ data, selected }) {
  return (
    <div className={`custom-node trigger ${selected ? 'selected' : ''}`}>
      <div className="flex items-center gap-2 mb-1">
        <span className="text-[10px] font-bold uppercase bg-[#238636]/20 text-[#238636] px-1.5 py-0.5 rounded">{data.method}</span>
        <span className="text-xs font-medium">{data.path}</span>
      </div>
      <div className="text-[11px] text-[#8b949e]">{data.label}</div>
      <div className="flex items-center gap-2 mt-1.5">
        {data.auth && <span className="text-[10px] bg-[#1f2a45] text-[#3b82f6] px-1 rounded">{data.auth}</span>}
        {data.rate && <span className="text-[10px] bg-[#3d2e00] text-[#d29922] px-1 rounded">rate</span>}
      </div>
      <Handle type="source" position="bottom" />
    </div>
  )
}

function DatabaseNode({ data, selected }) {
  return (
    <div className={`custom-node db ${selected ? 'selected' : ''}`}>
      <Handle type="target" position="top" />
      <div className="flex items-center gap-2 mb-1">
        <span className="text-[10px] font-bold uppercase bg-[#d29922]/20 text-[#d29922] px-1.5 py-0.5 rounded">{data.query}</span>
        <span className="text-xs font-medium">{data.table}</span>
      </div>
      <div className="text-[11px] text-[#8b949e]">{data.label}</div>
      <Handle type="source" position="bottom" />
    </div>
  )
}

function HttpNode({ data, selected }) {
  return (
    <div className={`custom-node http ${selected ? 'selected' : ''}`}>
      <Handle type="target" position="top" />
      <div className="flex items-center gap-2 mb-1">
        <span className="text-[10px] font-bold uppercase bg-[#3b82f6]/20 text-[#3b82f6] px-1.5 py-0.5 rounded">HTTP</span>
        <span className="text-xs font-medium">{data.method}</span>
      </div>
      <div className="text-[11px] text-[#8b949e]">{data.url || data.label}</div>
      <Handle type="source" position="bottom" />
    </div>
  )
}

function ResponseNode({ data, selected }) {
  return (
    <div className={`custom-node response ${selected ? 'selected' : ''}`}>
      <Handle type="target" position="top" />
      <div className="flex items-center gap-2">
        <span className="text-[10px] font-bold uppercase bg-[#58a6ff]/20 text-[#58a6ff] px-1.5 py-0.5 rounded">{data.status}</span>
        <span className="text-xs">{data.label}</span>
      </div>
    </div>
  )
}

export default function WorkflowEditor() {
  const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes)
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges)
  const [selected, setSelected] = useState(null)
  const reactFlowWrapper = useRef(null)
  const [reactFlowInstance, setReactFlowInstance] = useState(null)

  const onConnect = useCallback((params) => setEdges((eds) => addEdge(params, eds)), [setEdges])
  const onDragOver = useCallback((e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move' }, [])

  const onDrop = useCallback((e) => {
    e.preventDefault()
    const type = e.dataTransfer.getData('application/reactflow')
    if (!type || !reactFlowInstance) return
    const pos = reactFlowInstance.screenToFlowPosition({ x: e.clientX, y: e.clientY })
    const id = String(Date.now())
    const newNode = {
      id,
      type,
      position: pos,
      data: { label: type, method: type === 'trigger' ? 'GET' : 'GET', path: '/', query: 'select', table: 'table', status: 200 },
    }
    setNodes((nds) => nds.concat(newNode))
  }, [reactFlowInstance, setNodes])

  const onNodeClick = useCallback((_, node) => setSelected(node), [])

  const updateNode = (id, data) => {
    setNodes((nds) => nds.map(n => n.id === id ? { ...n, data: { ...n.data, ...data } } : n))
  }

  const addNode = (type) => {
    const id = String(Date.now())
    setNodes((nds) => nds.concat({
      id, type,
      position: { x: 250, y: nds.length * 120 + 50 },
      data: { label: type, method: 'GET', path: '/', query: 'select', table: 'table', status: 200 },
    }))
  }

  const exportCode = () => {
    const triggers = nodes.filter(n => n.type === 'trigger')
    let code = "<?php\n\n"
    triggers.forEach(t => {
      const next = edges.filter(e => e.source === t.id).map(e => nodes.find(n => n.id === e.target))
      const auth = t.data.auth ? `\n    if (!\\$app->csrf_check() && !\\$app->token()) return \\$app->error('Unauthorized', 401);` : ''
      const dbCalls = next.filter(n => n.type === 'database').map(n => `    \\$rows = \\$app->db->${n.data.query}('${n.data.table}', '${n.data.columns || '*'}');`)
      code += `\\$app->on('${t.data.method} ${t.data.path}', function () use (\\$app) {${auth}\n${dbCalls.join('\n')}\n    return \\$app->success(\\$rows ?? []);\n});\n\n`
    })
    return code
  }

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between mb-3">
        <h2 className="text-base font-semibold">Workflow Editor</h2>
        <div className="flex gap-2">
          <button onClick={() => addNode('trigger')} className="px-3 py-1 text-xs bg-[#238636] hover:bg-[#2ea043] rounded-md transition-colors">+ Trigger</button>
          <button onClick={() => addNode('database')} className="px-3 py-1 text-xs bg-[#d29922]/80 hover:bg-[#d29922] rounded-md transition-colors">+ Database</button>
          <button onClick={() => addNode('http')} className="px-3 py-1 text-xs bg-[#3b82f6]/80 hover:bg-[#3b82f6] rounded-md transition-colors">+ HTTP</button>
          <button onClick={() => addNode('response')} className="px-3 py-1 text-xs bg-[#58a6ff]/80 hover:bg-[#58a6ff] rounded-md transition-colors">+ Response</button>
          <button onClick={() => alert(exportCode())} className="px-3 py-1 text-xs bg-[#161b22] border border-[#30363d] hover:bg-[#21262d] rounded-md transition-colors">Export PHP</button>
        </div>
      </div>

      <div className="flex-1 bg-[#0d1117] rounded-lg border border-[#30363d] overflow-hidden" ref={reactFlowWrapper}>
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
          onInit={setReactFlowInstance}
          onDrop={onDrop}
          onDragOver={onDragOver}
          onNodeClick={onNodeClick}
          nodeTypes={nodeTypes}
          fitView
          deleteKeyCode={['Backspace', 'Delete']}
        >
          <Controls />
          <MiniMap
            nodeColor={(n) => n.type === 'trigger' ? '#238636' : n.type === 'database' ? '#d29922' : '#3b82f6'}
            maskColor="rgba(13, 17, 23, 0.8)"
          />
          <Background gap={20} color="#21262d" />
        </ReactFlow>
      </div>

      {selected && (
        <div className="mt-3 bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <div className="flex items-center justify-between mb-2">
            <h3 className="text-sm font-semibold capitalize">{selected.type} Properties</h3>
            <button onClick={() => setSelected(null)} className="text-xs text-[#8b949e] hover:text-white">Close</button>
          </div>
          <div className="grid grid-cols-2 gap-3">
            {selected.type === 'trigger' && (
              <>
                <div><label className="text-[11px] text-[#8b949e]">Method</label>
                  <select value={selected.data.method || 'GET'} onChange={e => updateNode(selected.id, { method: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
                    <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
                  </select></div>
                <div><label className="text-[11px] text-[#8b949e]">Path</label>
                  <input value={selected.data.path || ''} onChange={e => updateNode(selected.id, { path: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" /></div>
                <div><label className="text-[11px] text-[#8b949e]">Auth</label>
                  <select value={selected.data.auth || ''} onChange={e => updateNode(selected.id, { auth: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
                    <option value="">None</option><option>JWT</option><option>CSRF</option><option>Session</option>
                  </select></div>
                <div className="flex items-end"><label className="flex items-center gap-2 text-xs"><input type="checkbox" checked={selected.data.rate || false} onChange={e => updateNode(selected.id, { rate: e.target.checked })} className="accent-[#3b82f6]" /> Rate Limit</label></div>
              </>
            )}
            {selected.type === 'database' && (
              <>
                <div><label className="text-[11px] text-[#8b949e]">Query</label>
                  <select value={selected.data.query || 'select'} onChange={e => updateNode(selected.id, { query: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
                    <option>select</option><option>get</option><option>insert</option><option>update</option><option>delete</option><option>count</option><option>pages</option>
                  </select></div>
                <div><label className="text-[11px] text-[#8b949e]">Table</label>
                  <input value={selected.data.table || ''} onChange={e => updateNode(selected.id, { table: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" /></div>
                <div><label className="text-[11px] text-[#8b949e]">Columns</label>
                  <input value={selected.data.columns || '*'} onChange={e => updateNode(selected.id, { columns: e.target.value })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" /></div>
              </>
            )}
            {selected.type === 'response' && (
              <>
                <div><label className="text-[11px] text-[#8b949e]">Status</label>
                  <input type="number" value={selected.data.status || 200} onChange={e => updateNode(selected.id, { status: parseInt(e.target.value) })} className="w-full mt-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" /></div>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
