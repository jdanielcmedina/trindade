import { Globe } from 'lucide-react'

export default function RequestTable({ requests }) {
  return (
    <div className="bg-zinc-900/30 border border-zinc-800/40 rounded-xl overflow-hidden">
      <div className="flex items-center gap-2 px-5 py-3 border-b border-zinc-800/40">
        <Globe className="w-3.5 h-3.5 text-zinc-500" strokeWidth={1.5} />
        <span className="text-[12px] font-semibold text-zinc-300">Requests</span>
        <span className="text-[10px] text-zinc-600 ml-2">{requests.length} entries</span>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-[12px]">
          <thead>
            <tr className="bg-zinc-950/30 text-zinc-500 border-b border-zinc-800/30">
              {['Time', 'Action', 'IP', 'User'].map(h => (
                <th key={h} className="text-left font-medium px-4 py-2 text-[10px] uppercase tracking-wider">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {requests.map((r, i) => (
              <tr key={i} className="border-b border-zinc-800/20 hover:bg-white/[0.02] transition-colors">
                <td className="px-4 py-2 font-mono text-zinc-500">{(r.ts || '').substring(11, 19)}</td>
                <td className="px-4 py-2 text-zinc-300 font-medium">{r.action}</td>
                <td className="px-4 py-2 font-mono text-zinc-500">{r.ip}</td>
                <td className="px-4 py-2 text-zinc-500">{r.user || '—'}</td>
              </tr>
            ))}
            {requests.length === 0 && (
              <tr><td colSpan={4} className="px-4 py-8 text-center text-zinc-600">No requests recorded</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
