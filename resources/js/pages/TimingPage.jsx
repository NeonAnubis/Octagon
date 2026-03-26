import React from 'react';
import { useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';

const phaseColors = {
    awareness: 'bg-sky-400/10 text-sky-400 border-sky-400/30',
    consideration: 'bg-violet-400/10 text-violet-400 border-violet-400/30',
    urgency: 'bg-amber-400/10 text-amber-400 border-amber-400/30',
    fight_week: 'bg-red-400/10 text-red-400 border-red-400/30',
};

const priorityColors = {
    high: 'bg-red-400/10 text-red-400',
    medium: 'bg-amber-400/10 text-amber-400',
    low: 'bg-emerald-400/10 text-emerald-400',
};

export default function TimingPage() {
    const { data, loading } = useApi('/marketing/timing');

    if (loading) return <LoadingSpinner text="Analyzing campaign timing..." />;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Campaign Timing</h1>
                <p className="text-sm text-zinc-500 mt-1">AI-powered recommendations for when to push spend based on sales pace</p>
            </div>

            {(data || []).map(rec => {
                const phaseStyle = phaseColors[rec.phase?.name] || phaseColors.awareness;
                const gapColor = rec.sellthrough_gap >= 0 ? 'text-emerald-400' : rec.sellthrough_gap < -10 ? 'text-red-400' : 'text-amber-400';

                return (
                    <div key={rec.event_id} className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                        {/* Header */}
                        <div className="p-5 border-b border-zinc-800">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h2 className="text-lg font-bold">{rec.event_name}</h2>
                                    <p className="text-xs text-zinc-500 mt-1">{rec.days_to_event} days to event</p>
                                </div>
                                <span className={`text-xs px-3 py-1.5 rounded-full border font-medium ${phaseStyle}`}>
                                    {rec.phase?.label}
                                </span>
                            </div>
                            <p className="text-xs text-zinc-400 mt-2">{rec.phase?.description}</p>
                        </div>

                        {/* Metrics Row */}
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 p-5 border-b border-zinc-800">
                            <div>
                                <div className="text-xs text-zinc-500 mb-1">Current Sellthrough</div>
                                <div className="text-lg font-bold">{rec.current_sellthrough}%</div>
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500 mb-1">Historical Avg</div>
                                <div className="text-lg font-bold text-zinc-400">{rec.historical_avg_sellthrough}%</div>
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500 mb-1">Gap</div>
                                <div className={`text-lg font-bold ${gapColor}`}>
                                    {rec.sellthrough_gap > 0 ? '+' : ''}{rec.sellthrough_gap}%
                                </div>
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500 mb-1">Daily Velocity</div>
                                <div className="text-lg font-bold">{rec.velocity_daily}/day</div>
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500 mb-1">7-Day Spend</div>
                                <div className="text-lg font-bold">${rec.recent_spend?.toLocaleString()}</div>
                                {rec.spend_trend_pct !== 0 && (
                                    <div className={`text-xs ${rec.spend_trend_pct > 0 ? 'text-sky-400' : 'text-zinc-500'}`}>
                                        {rec.spend_trend_pct > 0 ? '+' : ''}{rec.spend_trend_pct}% vs prior week
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Timing Actions */}
                        <div className="p-5 border-b border-zinc-800">
                            <h3 className="text-sm font-semibold text-zinc-400 mb-3">Recommended Actions</h3>
                            <div className="space-y-3">
                                {(rec.recommendations || []).map((action, i) => (
                                    <div key={i} className="flex items-start gap-3">
                                        <span className={`shrink-0 text-[10px] px-2 py-0.5 rounded-full font-medium mt-0.5 ${priorityColors[action.priority]}`}>
                                            {action.priority}
                                        </span>
                                        <div>
                                            <div className="text-sm font-medium">{action.action}</div>
                                            <div className="text-xs text-zinc-400 mt-0.5">{action.detail}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Channel Recommendations & Spend Schedule */}
                        <div className="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-zinc-800">
                            <div className="p-5">
                                <h3 className="text-sm font-semibold text-zinc-400 mb-3">Optimal Channels</h3>
                                <div className="space-y-2">
                                    {(rec.optimal_channels || []).map((ch, i) => (
                                        <div key={i} className="flex items-center justify-between text-xs">
                                            <div className="flex items-center gap-2">
                                                <span className={`w-2 h-2 rounded-full ${
                                                    ch.recommended_action === 'increase' ? 'bg-emerald-400' :
                                                    ch.recommended_action === 'maintain' ? 'bg-sky-400' : 'bg-red-400'
                                                }`}></span>
                                                <span className="font-medium">{ch.source}</span>
                                                <span className="text-zinc-500">{ch.type}</span>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <span className="text-zinc-400">ROAS: {ch.roas}x</span>
                                                <span className={`px-1.5 py-0.5 rounded text-[10px] font-medium ${
                                                    ch.recommended_action === 'increase' ? 'bg-emerald-400/10 text-emerald-400' :
                                                    ch.recommended_action === 'maintain' ? 'bg-zinc-800 text-zinc-400' :
                                                    'bg-red-400/10 text-red-400'
                                                }`}>
                                                    {ch.recommended_action}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="p-5">
                                <h3 className="text-sm font-semibold text-zinc-400 mb-3">Spend Schedule</h3>
                                <div className="space-y-2">
                                    {(rec.spend_schedule || []).map((s, i) => (
                                        <div key={i} className="flex items-center justify-between text-xs">
                                            <span className="text-zinc-300">{s.window}</span>
                                            <div className="flex items-center gap-3">
                                                <div className="w-16 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                                    <div className="h-full rounded-full bg-sky-400" style={{ width: `${s.budget_pct}%` }} />
                                                </div>
                                                <span className="text-zinc-400 w-8 text-right">{s.budget_pct}%</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
