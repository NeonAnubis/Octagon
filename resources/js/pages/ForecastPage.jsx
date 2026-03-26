import React, { useState } from 'react';
import { useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, RadarChart, Radar, PolarGrid, PolarAngleAxis, PolarRadiusAxis } from 'recharts';

const formatCurrency = (v) => {
    if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + Number(v).toFixed(0);
};

export default function ForecastPage() {
    const { data: events, loading: eventsLoading } = useApi('/events');
    const [selectedId, setSelectedId] = useState(null);

    const upcomingEvents = (events || []).filter(e => e.status !== 'completed');
    const activeId = selectedId || upcomingEvents[0]?.id;

    const { data: forecast, loading: forecastLoading } = useApi(
        activeId ? `/events/${activeId}/forecast` : null,
        [activeId]
    );

    if (eventsLoading) return <LoadingSpinner text="Loading forecasts..." />;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Forecasting</h1>
                <p className="text-sm text-zinc-500 mt-1">Predictive sellthrough and demand modeling</p>
            </div>

            {/* Event Selector */}
            <div className="flex gap-2 overflow-x-auto pb-2">
                {upcomingEvents.map(event => (
                    <button
                        key={event.id}
                        onClick={() => setSelectedId(event.id)}
                        className={`shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                            event.id === activeId
                                ? 'bg-sky-400/10 text-sky-400 border border-sky-400/30'
                                : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700'
                        }`}
                    >
                        {event.name?.replace('UFC ', '')}
                    </button>
                ))}
            </div>

            {forecastLoading ? (
                <LoadingSpinner text="Generating forecast..." />
            ) : forecast ? (
                <>
                    {/* Summary Cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <div className="text-xs text-zinc-500 mb-1">Predicted Attendance</div>
                            <div className="text-2xl font-bold">{forecast.predicted_attendance?.toLocaleString()}</div>
                            <div className="text-xs text-zinc-500 mt-1">of {forecast.event?.total_capacity?.toLocaleString()}</div>
                        </div>
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <div className="text-xs text-zinc-500 mb-1">Predicted Revenue</div>
                            <div className="text-2xl font-bold">{formatCurrency(forecast.predicted_revenue)}</div>
                        </div>
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <div className="text-xs text-zinc-500 mb-1">Predicted Sellthrough</div>
                            <div className="text-2xl font-bold text-sky-400">{forecast.predicted_sellthrough}%</div>
                        </div>
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <div className="text-xs text-zinc-500 mb-1">Days to Event</div>
                            <div className="text-2xl font-bold">{forecast.days_to_event}</div>
                        </div>
                    </div>

                    <div className="grid lg:grid-cols-2 gap-6">
                        {/* Section Forecast Chart */}
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <h3 className="text-sm font-semibold text-zinc-400 mb-4">Section Forecast</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={forecast.sections || []} layout="vertical" barSize={16}>
                                    <XAxis type="number" tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} />
                                    <YAxis type="category" dataKey="section_name" tick={{ fontSize: 9, fill: '#a1a1aa' }} axisLine={false} tickLine={false} width={100} />
                                    <Tooltip
                                        contentStyle={{ background: '#27272a', border: '1px solid #3f3f46', borderRadius: 8, fontSize: 12 }}
                                    />
                                    <Bar dataKey="current_sold" name="Current" fill="#3f3f46" radius={[0, 4, 4, 0]} />
                                    <Bar dataKey="predicted_sold" name="Predicted" fill="#38bdf8" radius={[0, 4, 4, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Demand Radar */}
                        <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                            <h3 className="text-sm font-semibold text-zinc-400 mb-4">Demand Score by Section</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <RadarChart data={(forecast.sections || []).slice(0, 8).map(s => ({
                                    name: s.section_name?.replace('Section ', 'S'),
                                    demand: s.demand_score,
                                    sellthrough: s.predicted_sellthrough,
                                }))}>
                                    <PolarGrid stroke="#27272a" />
                                    <PolarAngleAxis dataKey="name" tick={{ fontSize: 9, fill: '#71717a' }} />
                                    <PolarRadiusAxis tick={{ fontSize: 8, fill: '#52525b' }} domain={[0, 100]} />
                                    <Radar name="Demand" dataKey="demand" stroke="#38bdf8" fill="#38bdf8" fillOpacity={0.15} strokeWidth={2} />
                                    <Radar name="Sellthrough" dataKey="sellthrough" stroke="#7dd3fc" fill="#7dd3fc" fillOpacity={0.05} strokeWidth={1} strokeDasharray="4 4" />
                                </RadarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Section Forecast Table */}
                    <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                        <div className="px-5 py-4 border-b border-zinc-800">
                            <h3 className="text-sm font-semibold">Section-Level Predictions</h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-xs text-zinc-500 uppercase tracking-wider border-b border-zinc-800">
                                        <th className="text-left px-5 py-3 font-medium">Section</th>
                                        <th className="text-right px-5 py-3 font-medium">Current</th>
                                        <th className="text-right px-5 py-3 font-medium">Predicted</th>
                                        <th className="text-right px-5 py-3 font-medium">Sellthrough</th>
                                        <th className="text-right px-5 py-3 font-medium">Revenue</th>
                                        <th className="text-right px-5 py-3 font-medium">Confidence</th>
                                        <th className="text-right px-5 py-3 font-medium">Demand</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800/50">
                                    {(forecast.sections || []).map(s => (
                                        <tr key={s.section_id} className="hover:bg-zinc-800/30">
                                            <td className="px-5 py-3 font-medium">{s.section_name}</td>
                                            <td className="px-5 py-3 text-right text-zinc-400">{s.current_sold}</td>
                                            <td className="px-5 py-3 text-right font-medium text-sky-400">{s.predicted_sold}</td>
                                            <td className="px-5 py-3 text-right">{s.predicted_sellthrough}%</td>
                                            <td className="px-5 py-3 text-right">{formatCurrency(s.predicted_revenue)}</td>
                                            <td className="px-5 py-3 text-right">
                                                <span className={`${s.confidence >= 80 ? 'text-emerald-400' : s.confidence >= 60 ? 'text-amber-400' : 'text-zinc-500'}`}>
                                                    {s.confidence}%
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <div className="w-12 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                                        <div className="h-full rounded-full bg-sky-400" style={{ width: `${s.demand_score}%` }} />
                                                    </div>
                                                    <span className="text-xs">{s.demand_score}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </>
            ) : (
                <div className="text-center py-20 text-zinc-500">Select an event to view forecasts</div>
            )}
        </div>
    );
}
