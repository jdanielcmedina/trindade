export default function ChartCard({ title, icon: Icon, children }) {
  return (
    <div className="bg-zinc-900/30 border border-zinc-800/40 rounded-xl p-5">
      <div className="flex items-center gap-2 mb-4">
        <Icon className="w-3.5 h-3.5 text-zinc-500" strokeWidth={1.5} />
        <h3 className="text-[12px] font-semibold text-zinc-300">{title}</h3>
      </div>
      {children}
    </div>
  )
}
