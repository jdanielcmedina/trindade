import { motion } from 'framer-motion'

export default function MetricCard({ icon: Icon, label, value, sub, color, loading }) {
  return (
    <motion.div initial={{ opacity: 0, y: 4 }} animate={{ opacity: 1, y: 0 }}
      className="bg-zinc-900/30 border border-zinc-800/40 rounded-lg p-3.5 hover:border-zinc-700/50 transition-colors">
      <div className="flex items-center justify-between mb-2">
        <span className="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">{label}</span>
        <Icon className="w-3.5 h-3.5 text-zinc-600" strokeWidth={1.5} />
      </div>
      <div className="text-lg font-bold tracking-tight text-zinc-100 tabular-nums">
        {loading ? <span className="text-zinc-800">—</span> : value}
      </div>
      {sub && <div className="text-[10px] text-zinc-600 mt-0.5">{sub}</div>}
    </motion.div>
  )
}
