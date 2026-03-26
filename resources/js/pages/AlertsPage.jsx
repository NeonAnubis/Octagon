import React from 'react';
import { useApi, api } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';

const severityConfig = {
    critical: { color: 'bg-red-400', badge: 'bg-red-400/10 text-red-400', label: 'Critical' },
    warning: { color: 'bg-amber-400', badge: 'bg-amber-400/10 text-amber-400', label: 'Warning' },
    info: { color: 'bg-sky-400', badge: 'bg-sky-400/10 text-sky-400', label: 'Info' },
};

const typeLabels = {
    soft_section: 'Soft Section',
    spend_efficiency: 'Spend Efficiency',
    velocity_drop: 'Velocity Drop',
    forecast_miss: 'Forecast Miss',
};

export default function AlertsPage() {
    const { data: alerts, loading, refetch } = useApi('/alerts');

    const resolveAlert = async (id) => {
        await api.post(`/alerts/${id}/resolve`);
        refetch();
    };

    if (loading) return <LoadingSpinner text="Loading alerts..." />;

    const critical = (alerts || []).filter(a => a.severity === 'critical');
    const warning = (alerts || []).filter(a => a.severity === 'warning');
    const info = (alerts || []).filter(a => a.severity === 'info');

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Alerts</h1>
                <p className="text-sm text-zinc-500 mt-1">Active alerts across all events and campaigns</p>
            </div>

            {/* Summary */}
            <div className="flex gap-4">
                {[
                    { count: critical.length, label: 'Critical', color: 'border-red-400/30 bg-red-400/5', text: 'text-red-400' },
                    { count: warning.length, label: 'Warning', color: 'border-amber-400/30 bg-amber-400/5', text: 'text-amber-400' },
                    { count: info.length, label: 'Info', color: 'border-sky-400/30 bg-sky-400/5', text: 'text-sky-400' },
                ].map(item => (
                    <div key={item.label} className={`px-4 py-3 rounded-xl border ${item.color}`}>
                        <div className={`text-2xl font-bold ${item.text}`}>{item.count}</div>
                        <div className="text-xs text-zinc-500">{item.label}</div>
                    </div>
                ))}
            </div>

            {/* Alerts List */}
            {(alerts || []).length === 0 ? (
                <div className="text-center py-20">
                    <div className="w-12 h-12 rounded-full bg-emerald-400/10 flex items-center justify-center mx-auto mb-3">
                        <svg className="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p className="text-zinc-400 font-medium">All clear</p>
                    <p className="text-xs text-zinc-600 mt-1">No active alerts</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {(alerts || []).map(alert => {
                        const config = severityConfig[alert.severity] || severityConfig.info;
                        return (
                            <div key={alert.id} className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden hover:border-zinc-700 transition-colors">
                                <div className="p-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex items-start gap-3">
                                            <div className={`w-2.5 h-2.5 rounded-full mt-1 shrink-0 ${config.color}`}></div>
                                            <div>
                                                <div className="flex items-center gap-2 mb-1">
                                                    <h3 className="text-sm font-semibold">{alert.title}</h3>
                                                    <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${config.badge}`}>{config.label}</span>
                                                    <span className="text-[10px] px-2 py-0.5 rounded bg-zinc-800 text-zinc-500">{typeLabels[alert.type] || alert.type}</span>
                                                </div>
                                                <p className="text-xs text-zinc-400 leading-relaxed">{alert.message}</p>
                                                {alert.event && (
                                                    <p className="text-[10px] text-zinc-600 mt-2">{alert.event.name} — {alert.section?.name || 'Event-level'}</p>
                                                )}
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => resolveAlert(alert.id)}
                                            className="shrink-0 text-xs px-3 py-1.5 rounded-lg border border-zinc-700 text-zinc-400 hover:text-white hover:border-zinc-500 transition-all"
                                        >
                                            Resolve
                                        </button>
                                    </div>

                                    {alert.recommendation && (
                                        <div className="mt-3 ml-5 p-3 bg-sky-400/5 border border-sky-400/10 rounded-lg">
                                            <div className="flex items-center gap-1.5 mb-1">
                                                <svg className="w-3 h-3 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                </svg>
                                                <span className="text-[10px] font-semibold text-sky-400 uppercase tracking-wider">AI Recommendation</span>
                                            </div>
                                            <p className="text-xs text-zinc-300 leading-relaxed">{alert.recommendation}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
