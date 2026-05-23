import { Search } from 'lucide-react'
import * as ScrollArea from '@radix-ui/react-scroll-area'

export default function LogViewer({ logs, filter, setFilter }) {
  const filtered = filter ? logs.filter(l => l.toLowerCase().includes(filter.toLowerCase())) : logs.slice(-100)

  return (
    <div className="bg-zinc-900/30 border border-zinc-800/40 rounded-xl p-5 flex flex-col">
      <div className="flex items-center gap-3 mb-4">
        <div className="relative flex-1 max-w-[260px]">
          <Search className="w-3 h-3 absolute left-2.5 top-2 text-zinc-600" />
          <input value={filter} onChange={e => setFilter(e.target.value)}
            placeholder="Filter logs..."
            className="w-full bg-black/40 border border-zinc-800/60 rounded-md pl-8 pr-3 py-1.5 text-[11.5px] text-zinc-300 outline-none focus:border-zinc-700/60 transition-colors placeholder:text-zinc-700" />
        </div>
        <span className="text-[10px] text-zinc-600">{filtered.length} entries</span>
      </div>

      <ScrollArea.Root className="flex-1 overflow-hidden max-h-[400px]">
        <ScrollArea.Viewport className="h-full">
          <div className="space-y-px">
            {filtered.map((l, i) => {
              let color = 'text-zinc-500', bg = '', level = ''
              if (l.includes('[error]')) { color = 'text-red-400/80'; bg = 'hover:bg-red-500/[0.03]'; level = 'ERR' }
              else if (l.includes('[warning]')) { color = 'text-amber-400/80'; bg = 'hover:bg-amber-500/[0.03]'; level = 'WRN' }
              else if (l.includes('[info]')) { color = 'text-zinc-400'; bg = 'hover:bg-white/[0.02]'; level = 'INF' }
              else { level = 'DBG' }
              return (
                <div key={i} className={`flex items-start gap-2.5 py-0.5 px-2 rounded text-[11.5px] font-mono ${bg} transition-colors`}>
                  <span className="text-[10px] font-semibold text-zinc-700 w-7 shrink-0 text-right mt-px tracking-wider">{level}</span>
                  <span className={`${color} truncate`}>{l}</span>
                </div>
              )
            })}
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar className="flex select-none touch-none p-0.5 w-2" orientation="vertical">
          <ScrollArea.Thumb className="flex-1 bg-zinc-700/50 rounded-full relative hover:bg-zinc-600/50 transition-colors" />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  )
}
