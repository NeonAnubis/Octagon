import React, { useState } from 'react';
import { useApi } from '../hooks/useApi';
import StatCard from '../components/StatCard';
import SectionHeatmap from '../components/SectionHeatmap';
import LoadingSpinner from '../components/LoadingSpinner';
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, AreaChart, Area, BarChart, Bar } from 'recharts';

const formatCurrency = (v) => {
    if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + Number(v).toFixed(0);
};

export default function EventDetailPage({ eventId, onNavigate }) {
    const { data, loading } = useApi(eventId ? `/events/${eventId}` : '/events/1', [eventId]);
    const [selectedSection, setSelectedSection] = useState(null);

    if (loading) return <LoadingSpinner text="Loading event analytics..." />;
    if (!data) return <div className="text-center py-20 text-zinc-500">Select an event from the overview</div>;

    const { event, velocity_trend, section_performance, forecasts, alerts } = data;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-start justify-between">
                <div>
                    <button onClick={() => onNavigate('overview')} className="text-xs text-zinc-500 hover:text-sky-400 transition-colors mb-2 flex items-center gap-1">
                        <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                        All Events
                    </button>
                    <h1 className="text-2xl font-bold tracking-tight">{event.name}</h1>
                    <p className="text-sm text-zinc-500 mt-1">{event.venue} — {event.city}, {event.state || event.country}</p>
                </div>
                <span className={`text-xs px-3 py-1.5 rounded-full font-medium ${
                    event.status === 'completed' ? 'bg-zinc-800 text-zinc-400' :
                    event.status === 'sold_out' ? 'bg-sky-400/20 text-sky-400' :
                    'bg-emerald-400/20 text-emerald-400'
                }`}>
                    {event.status === 'on_sale' ? `${event.days_to_event || Math.max(0, Math.ceil((new Date(event.event_date) - new Date()) / 86400000))} days to event` : event.status}
                </span>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <StatCard title="Total Sold" value={event.total_sold?.toLocaleString()} subtitle={`of ${event.total_capacity?.toLocaleString()}`} />
                <StatCard title="Sellthrough" value={`${event.total_capacity > 0 ? Math.round(event.total_sold / event.total_capacity * 100) : 0}%`} />
                <StatCard title="Revenue" value={formatCurrency(event.total_revenue)} />
                <StatCard title="Sections" value={event.sections?.length || 0} subtitle={`${alerts?.length || 0} alerts`} />
                <StatCard title="Forecast" value={forecasts?.[0] ? `${forecasts[0].predicted_sellthrough}%` : 'N/A'} subtitle={forecasts?.[0] ? `${forecasts[0].confidence}% conf.` : ''} />
            </div>

            <div className="grid lg:grid-cols-3 gap-6">
                {/* Velocity Chart */}
                <div className="lg:col-span-2 p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <h3 className="text-sm font-semibold text-zinc-400 mb-4">Sellthrough Velocity</h3>
                    <ResponsiveContainer width="100%" height={280}>
                        <AreaChart data={velocity_trend || []}>
                            <defs>
                                <linearGradient id="velGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#38bdf8" stopOpacity={0.3} />
                                    <stop offset="95%" stopColor="#38bdf8" stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <XAxis dataKey="date" tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                            <YAxis tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                            <Tooltip
                                contentStyle={{ background: '#27272a', border: '1px solid #3f3f46', borderRadius: 8, fontSize: 12 }}
                                labelStyle={{ color: '#a1a1aa' }}
                            />
                            <Area type="monotone" dataKey="avg_sellthrough" name="Sellthrough %" stroke="#38bdf8" fill="url(#velGrad)" strokeWidth={2} />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>

                {/* Section Heatmap */}
                <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <SectionHeatmap sections={section_performance} onSelect={setSelectedSection} />
                </div>
            </div>

            {/* Section Performance Table */}
            <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                <div className="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                    <h3 className="text-sm font-semibold">Section Performance</h3>
                    {selectedSection && (
                        <button onClick={() => setSelectedSection(null)} className="text-xs text-sky-400 hover:underline">
                            Clear filter
                        </button>
                    )}
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-xs text-zinc-500 uppercase tracking-wider border-b border-zinc-800">
                                <th className="text-left px-5 py-3 font-medium">Section</th>
                                <th className="text-left px-5 py-3 font-medium">Tier</th>
                                <th className="text-right px-5 py-3 font-medium">Price</th>
                                <th className="text-right px-5 py-3 font-medium">Sold</th>
                                <th className="text-right px-5 py-3 font-medium">Sellthrough</th>
                                <th className="text-right px-5 py-3 font-medium">Revenue</th>
                                <th className="text-right px-5 py-3 font-medium">Velocity</th>
                                <th className="text-center px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800/50">
                            {(section_performance || [])
                                .filter(s => !selectedSection || s.id === selectedSection.id)
                                .map(s => (
                                <tr key={s.id} className="hover:bg-zinc-800/30 transition-colors">
                                    <td className="px-5 py-3 font-medium">{s.name}</td>
                                    <td className="px-5 py-3">
                                        <span className="text-xs px-2 py-0.5 rounded bg-zinc-800 text-zinc-400 uppercase">{s.price_tier}</span>
                                    </td>
                                    <td className="px-5 py-3 text-right">${s.current_price}</td>
                                    <td className="px-5 py-3 text-right">{s.sold} / {s.capacity}</td>
                                    <td className="px-5 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <div className="w-14 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                                <div className={`h-full rounded-full ${
                                                    s.sellthrough >= 80 ? 'bg-sky-400' :
                                                    s.sellthrough >= 60 ? 'bg-emerald-400' :
                                                    s.sellthrough >= 40 ? 'bg-amber-400' : 'bg-red-400'
                                                }`} style={{ width: `${s.sellthrough}%` }} />
                                            </div>
                                            <span className="text-xs">{s.sellthrough}%</span>
                                        </div>
                                    </td>
                                    <td className="px-5 py-3 text-right">{formatCurrency(s.revenue)}</td>
                                    <td className="px-5 py-3 text-right text-zinc-400">{s.velocity}/day</td>
                                    <td className="px-5 py-3 text-center">
                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                                            s.status === 'sold_out' ? 'bg-sky-400/20 text-sky-400' :
                                            s.status === 'available' ? 'bg-emerald-400/20 text-emerald-400' :
                                            s.status === 'soft' ? 'bg-amber-400/20 text-amber-400' :
                                            s.status === 'warning' ? 'bg-orange-400/20 text-orange-400' :
                                            'bg-red-400/20 text-red-400'
                                        }`}>{s.status}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Alerts for this event */}
            {alerts?.length > 0 && (
                <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                    <div className="px-5 py-4 border-b border-zinc-800">
                        <h3 className="text-sm font-semibold">Active Alerts</h3>
                    </div>
                    <div className="divide-y divide-zinc-800/50">
                        {alerts.map(alert => (
                            <div key={alert.id} className="px-5 py-4 flex items-start gap-3">
                                <div className={`w-2 h-2 rounded-full mt-1.5 shrink-0 ${
                                    alert.severity === 'critical' ? 'bg-red-400' :
                                    alert.severity === 'warning' ? 'bg-amber-400' : 'bg-sky-400'
                                }`} />
                                <div>
                                    <div className="text-sm font-medium">{alert.title}</div>
                                    <div className="text-xs text-zinc-400 mt-1">{alert.message}</div>
                                    {alert.recommendation && (
                                        <div className="text-xs text-sky-400/80 mt-2 p-2 bg-sky-400/5 rounded-lg border border-sky-400/10">
                                            {alert.recommendation}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
