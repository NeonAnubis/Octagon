import React from 'react';
import { useApi } from '../hooks/useApi';
import StatCard from '../components/StatCard';
import LoadingSpinner from '../components/LoadingSpinner';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, AreaChart, Area } from 'recharts';

const formatCurrency = (v) => {
    if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + Number(v).toFixed(0);
};

const CustomTooltip = ({ active, payload, label }) => {
    if (!active || !payload?.length) return null;
    return (
        <div className="bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-xs shadow-xl">
            <p className="text-zinc-400 mb-1">{label}</p>
            {payload.map((p, i) => (
                <p key={i} className="text-white font-medium">{p.name}: {typeof p.value === 'number' && p.name.includes('Revenue') ? formatCurrency(p.value) : p.value}</p>
            ))}
        </div>
    );
};

export default function OverviewPage({ onSelectEvent }) {
    const { data, loading } = useApi('/dashboard');

    if (loading) return <LoadingSpinner text="Loading dashboard..." />;
    if (!data) return null;

    const { summary, upcoming_events, completed_events } = data;

    const eventChartData = [...(upcoming_events || []), ...(completed_events || [])].map(e => ({
        name: e.name?.replace('UFC ', '').substring(0, 20),
        Sold: e.total_sold,
        Capacity: e.total_capacity,
        Revenue: parseFloat(e.total_revenue),
    }));

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
                    <p className="text-sm text-zinc-500 mt-1">Real-time overview across all events</p>
                </div>
                <div className="flex items-center gap-2 text-xs text-zinc-500">
                    <div className="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                    Live
                </div>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Total Revenue"
                    value={formatCurrency(summary.total_revenue)}
                    trend="+12.4%"
                    subtitle="vs prev period"
                    icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <StatCard
                    title="Tickets Sold"
                    value={summary.total_tickets_sold?.toLocaleString()}
                    subtitle={`${summary.avg_sellthrough}% sellthrough`}
                    icon="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"
                />
                <StatCard
                    title="Ad Spend"
                    value={formatCurrency(summary.total_ad_spend)}
                    subtitle={`ROAS: ${summary.overall_roas}x`}
                    trend={summary.overall_roas > 3 ? '+' : ''}
                    icon="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"
                />
                <StatCard
                    title="Active Alerts"
                    value={summary.active_alerts}
                    subtitle={`${summary.upcoming_events} upcoming events`}
                    icon="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                />
            </div>

            {/* Charts Row */}
            <div className="grid lg:grid-cols-2 gap-6">
                {/* Event Sales Chart */}
                <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <h3 className="text-sm font-semibold text-zinc-400 mb-4">Tickets Sold vs Capacity</h3>
                    <ResponsiveContainer width="100%" height={240}>
                        <BarChart data={eventChartData} barGap={2}>
                            <XAxis dataKey="name" tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                            <YAxis tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                            <Tooltip content={<CustomTooltip />} />
                            <Bar dataKey="Capacity" fill="#27272a" radius={[4, 4, 0, 0]} />
                            <Bar dataKey="Sold" fill="#38bdf8" radius={[4, 4, 0, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                {/* Revenue Chart */}
                <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <h3 className="text-sm font-semibold text-zinc-400 mb-4">Revenue by Event</h3>
                    <ResponsiveContainer width="100%" height={240}>
                        <AreaChart data={eventChartData}>
                            <defs>
                                <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#38bdf8" stopOpacity={0.3} />
                                    <stop offset="95%" stopColor="#38bdf8" stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <XAxis dataKey="name" tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                            <YAxis tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} tickFormatter={formatCurrency} />
                            <Tooltip content={<CustomTooltip />} />
                            <Area type="monotone" dataKey="Revenue" stroke="#38bdf8" fill="url(#revGrad)" strokeWidth={2} />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Events Table */}
            <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                <div className="px-5 py-4 border-b border-zinc-800">
                    <h3 className="text-sm font-semibold">Upcoming Events</h3>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-xs text-zinc-500 uppercase tracking-wider border-b border-zinc-800">
                                <th className="text-left px-5 py-3 font-medium">Event</th>
                                <th className="text-left px-5 py-3 font-medium">Venue</th>
                                <th className="text-right px-5 py-3 font-medium">Sold</th>
                                <th className="text-right px-5 py-3 font-medium">Sellthrough</th>
                                <th className="text-right px-5 py-3 font-medium">Revenue</th>
                                <th className="text-right px-5 py-3 font-medium">Days Out</th>
                                <th className="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800/50">
                            {(upcoming_events || []).map(event => (
                                <tr key={event.id} className="hover:bg-zinc-800/30 transition-colors cursor-pointer" onClick={() => onSelectEvent(event.id)}>
                                    <td className="px-5 py-3">
                                        <div className="font-medium">{event.name}</div>
                                        <div className="text-xs text-zinc-500">{event.city}, {event.state || event.country}</div>
                                    </td>
                                    <td className="px-5 py-3 text-zinc-400">{event.venue}</td>
                                    <td className="px-5 py-3 text-right font-medium">{event.total_sold?.toLocaleString()}</td>
                                    <td className="px-5 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <div className="w-16 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                                <div
                                                    className="h-full rounded-full bg-sky-400"
                                                    style={{ width: `${Math.min(100, (event.total_sold / event.total_capacity * 100))}%` }}
                                                />
                                            </div>
                                            <span className="text-xs font-medium">{event.total_capacity > 0 ? Math.round(event.total_sold / event.total_capacity * 100) : 0}%</span>
                                        </div>
                                    </td>
                                    <td className="px-5 py-3 text-right font-medium">{formatCurrency(event.total_revenue)}</td>
                                    <td className="px-5 py-3 text-right">
                                        <span className="text-xs px-2 py-1 rounded-full bg-sky-400/10 text-sky-400 font-medium">
                                            {Math.max(0, Math.ceil((new Date(event.event_date) - new Date()) / 86400000))}d
                                        </span>
                                    </td>
                                    <td className="px-5 py-3 text-right">
                                        <svg className="w-4 h-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                        </svg>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
