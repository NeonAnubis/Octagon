import React from 'react';

export default function StatCard({ title, value, subtitle, trend, icon }) {
    const trendColor = trend?.startsWith('+') ? 'text-emerald-400' : trend?.startsWith('-') ? 'text-red-400' : 'text-sky-400';

    return (
        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800 hover:border-zinc-700 transition-colors">
            <div className="flex items-start justify-between mb-3">
                <span className="text-xs font-medium text-zinc-500 uppercase tracking-wider">{title}</span>
                {icon && (
                    <div className="w-8 h-8 rounded-lg bg-sky-400/10 flex items-center justify-center">
                        <svg className="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d={icon} />
                        </svg>
                    </div>
                )}
            </div>
            <div className="text-2xl font-bold tracking-tight">{value}</div>
            {(subtitle || trend) && (
                <div className="flex items-center gap-2 mt-1">
                    {trend && <span className={`text-xs font-medium ${trendColor}`}>{trend}</span>}
                    {subtitle && <span className="text-xs text-zinc-500">{subtitle}</span>}
                </div>
            )}
        </div>
    );
}
