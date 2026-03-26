import React from 'react';
import { useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';

const formatCurrency = (v) => {
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + Number(v).toFixed(0);
};

const statusStyles = {
    healthy: { bg: 'bg-emerald-400/10', text: 'text-emerald-400', dot: 'bg-emerald-400', label: 'Healthy' },
    warning: { bg: 'bg-amber-400/10', text: 'text-amber-400', dot: 'bg-amber-400', label: 'Warning' },
    critical: { bg: 'bg-red-400/10', text: 'text-red-400', dot: 'bg-red-400', label: 'Critical' },
};

export default function SpendPage() {
    const { data, loading } = useApi('/marketing/spend-optimization');

    if (loading) return <LoadingSpinner text="Analyzing spend efficiency..." />;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Spend Optimization</h1>
                <p className="text-sm text-zinc-500 mt-1">Flag inefficient spend and recommend reallocation</p>
            </div>

            {(data || []).map(eventData => (
                <div key={eventData.event_id} className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-bold">{eventData.event_name}</h2>
                            <p className="text-xs text-zinc-500">{eventData.days_to_event} days out — ${eventData.total_recent_spend?.toLocaleString()} spent last 7 days</p>
                        </div>
                    </div>

                    {/* Channel Health Cards */}
                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {(eventData.channels || []).map((ch, i) => {
                            const style = statusStyles[ch.status] || statusStyles.healthy;
                            return (
                                <div key={i} className={`rounded-xl border border-zinc-800 overflow-hidden ${ch.status !== 'healthy' ? 'border-l-2' : ''}`}
                                    style={ch.status !== 'healthy' ? { borderLeftColor: ch.status === 'critical' ? '#f87171' : '#fbbf24' } : {}}>
                                    <div className="p-4">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <div className={`w-2 h-2 rounded-full ${style.dot}`}></div>
                                                <span className="text-sm font-semibold capitalize">{ch.source}</span>
                                                <span className="text-[10px] px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-500">{ch.type}</span>
                                            </div>
                                            <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${style.bg} ${style.text}`}>
                                                {style.label}
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-3 gap-2 mb-3">
                                            <div>
                                                <div className="text-[10px] text-zinc-500">7d Spend</div>
                                                <div className="text-sm font-bold">{formatCurrency(ch.recent_spend)}</div>
                                                <div className={`text-[10px] ${ch.spend_change_pct > 0 ? 'text-sky-400' : 'text-zinc-500'}`}>
                                                    {ch.spend_change_pct > 0 ? '+' : ''}{ch.spend_change_pct}%
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-[10px] text-zinc-500">Conversions</div>
                                                <div className="text-sm font-bold">{ch.recent_conversions}</div>
                                                <div className={`text-[10px] ${ch.conversion_change_pct > 0 ? 'text-emerald-400' : ch.conversion_change_pct < -10 ? 'text-red-400' : 'text-zinc-500'}`}>
                                                    {ch.conversion_change_pct > 0 ? '+' : ''}{ch.conversion_change_pct}%
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-[10px] text-zinc-500">ROAS</div>
                                                <div className={`text-sm font-bold ${ch.roas >= 3 ? 'text-emerald-400' : ch.roas >= 1.5 ? 'text-sky-400' : 'text-red-400'}`}>
                                                    {ch.roas}x
                                                </div>
                                                <div className="text-[10px] text-zinc-500">CPA: {formatCurrency(ch.recent_cpa)}</div>
                                            </div>
                                        </div>

                                        {ch.alerts?.length > 0 && (
                                            <div className="space-y-1 mb-2">
                                                {ch.alerts.map((alert, j) => (
                                                    <div key={j} className="text-[10px] text-amber-400/80 flex items-start gap-1.5">
                                                        <span className="mt-0.5">⚠</span>
                                                        <span>{alert}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        <div className="text-xs text-zinc-400 p-2 bg-zinc-800/50 rounded-lg">
                                            {ch.recommendation}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Optimal Reallocation */}
                    {eventData.optimal_allocation?.length > 0 && (
                        <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                            <div className="px-5 py-4 border-b border-zinc-800">
                                <h3 className="text-sm font-semibold">Recommended Budget Reallocation</h3>
                                <p className="text-xs text-zinc-500 mt-1">Based on ROAS-weighted optimization</p>
                            </div>

                            <div className="p-5">
                                <div className="grid gap-3">
                                    {eventData.optimal_allocation.map((alloc, i) => {
                                        const shiftColor = alloc.shift > 5 ? 'text-emerald-400' : alloc.shift < -5 ? 'text-red-400' : 'text-zinc-400';
                                        return (
                                            <div key={i} className="flex items-center gap-4">
                                                <span className="text-sm font-medium w-20 capitalize">{alloc.source}</span>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <div className="flex-1 h-2 bg-zinc-800 rounded-full overflow-hidden relative">
                                                            <div className="absolute inset-y-0 left-0 bg-zinc-600 rounded-full" style={{ width: `${alloc.current_share}%` }} />
                                                            <div className="absolute inset-y-0 left-0 bg-sky-400 rounded-full" style={{ width: `${alloc.optimal_share}%`, opacity: 0.5 }} />
                                                        </div>
                                                    </div>
                                                    <div className="flex justify-between text-[10px]">
                                                        <span className="text-zinc-500">Current: {alloc.current_share}%</span>
                                                        <span className="text-sky-400">Optimal: {alloc.optimal_share}%</span>
                                                    </div>
                                                </div>
                                                <div className={`text-xs font-medium w-16 text-right ${shiftColor}`}>
                                                    {alloc.shift > 0 ? '+' : ''}{alloc.shift}%
                                                </div>
                                                <div className="text-xs text-zinc-500 w-20 text-right">
                                                    {formatCurrency(alloc.recommended_budget)}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}
