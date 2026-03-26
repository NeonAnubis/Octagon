import React, { useState } from 'react';
import { useApi } from '../hooks/useApi';
import StatCard from '../components/StatCard';
import LoadingSpinner from '../components/LoadingSpinner';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, AreaChart, Area } from 'recharts';

const formatCurrency = (v) => {
    if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + Number(v).toFixed(0);
};

const COLORS = ['#38bdf8', '#7dd3fc', '#bae6fd', '#e0f2fe', '#0ea5e9', '#0284c7'];

const sourceIcons = {
    meta: 'Meta',
    google: 'Google',
    klaviyo: 'Email',
    instagram: 'Instagram',
    twitter: 'X',
    youtube: 'YouTube',
};

export default function MarketingPage() {
    const [selectedSource, setSelectedSource] = useState(null);
    const { data, loading } = useApi('/marketing');
    const { data: campaigns, loading: campaignsLoading } = useApi('/marketing/campaigns');

    if (loading) return <LoadingSpinner text="Loading marketing intelligence..." />;
    if (!data) return null;

    const { channel_performance, attribution } = data;

    const totalSpend = channel_performance?.reduce((a, c) => a + c.total_spend, 0) || 0;
    const totalConversions = channel_performance?.reduce((a, c) => a + c.total_conversions, 0) || 0;
    const totalRevenue = channel_performance?.reduce((a, c) => a + c.total_revenue, 0) || 0;
    const blendedRoas = totalSpend > 0 ? (totalRevenue / totalSpend).toFixed(1) : 0;

    const pieData = (attribution || []).map(a => ({
        name: sourceIcons[a.source] || a.source,
        value: a.conversions,
    }));

    return (
        <div className="space-y-6">
            {/* Header */}
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Marketing Intelligence</h1>
                <p className="text-sm text-zinc-500 mt-1">Cross-channel performance and attribution</p>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Total Spend" value={formatCurrency(totalSpend)} icon="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                <StatCard title="Conversions" value={totalConversions.toLocaleString()} />
                <StatCard title="Attributed Revenue" value={formatCurrency(totalRevenue)} />
                <StatCard title="Blended ROAS" value={`${blendedRoas}x`} trend={blendedRoas > 3 ? '+' : ''} subtitle={blendedRoas > 3 ? 'Above target' : 'Below target'} />
            </div>

            <div className="grid lg:grid-cols-3 gap-6">
                {/* Channel Performance */}
                <div className="lg:col-span-2 p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <h3 className="text-sm font-semibold text-zinc-400 mb-4">Channel Performance</h3>
                    <ResponsiveContainer width="100%" height={280}>
                        <BarChart data={channel_performance || []} layout="vertical" barSize={20}>
                            <XAxis type="number" tick={{ fontSize: 10, fill: '#71717a' }} axisLine={false} tickLine={false} tickFormatter={formatCurrency} />
                            <YAxis type="category" dataKey="source" tick={{ fontSize: 11, fill: '#a1a1aa' }} axisLine={false} tickLine={false} width={70} />
                            <Tooltip
                                contentStyle={{ background: '#27272a', border: '1px solid #3f3f46', borderRadius: 8, fontSize: 12 }}
                                formatter={(v, name) => [name === 'total_spend' ? formatCurrency(v) : name === 'total_revenue' ? formatCurrency(v) : v.toLocaleString(), name.replace('total_', '').replace('_', ' ')]}
                            />
                            <Bar dataKey="total_spend" name="Spend" fill="#3f3f46" radius={[0, 4, 4, 0]} />
                            <Bar dataKey="total_revenue" name="Revenue" fill="#38bdf8" radius={[0, 4, 4, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                {/* Attribution Pie */}
                <div className="p-5 rounded-xl bg-zinc-900/50 border border-zinc-800">
                    <h3 className="text-sm font-semibold text-zinc-400 mb-4">Conversion Attribution</h3>
                    <ResponsiveContainer width="100%" height={200}>
                        <PieChart>
                            <Pie data={pieData} cx="50%" cy="50%" outerRadius={80} innerRadius={45} dataKey="value" paddingAngle={2}>
                                {pieData.map((_, i) => (
                                    <Cell key={i} fill={COLORS[i % COLORS.length]} />
                                ))}
                            </Pie>
                            <Tooltip
                                contentStyle={{ background: '#27272a', border: '1px solid #3f3f46', borderRadius: 8, fontSize: 12 }}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                    <div className="grid grid-cols-2 gap-2 mt-2">
                        {pieData.map((item, i) => (
                            <div key={item.name} className="flex items-center gap-2 text-xs">
                                <div className="w-2.5 h-2.5 rounded-sm" style={{ background: COLORS[i % COLORS.length] }}></div>
                                <span className="text-zinc-400">{item.name}</span>
                                <span className="ml-auto font-medium">{item.value.toLocaleString()}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Channel Details Table */}
            <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                <div className="px-5 py-4 border-b border-zinc-800">
                    <h3 className="text-sm font-semibold">Channel Breakdown</h3>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-xs text-zinc-500 uppercase tracking-wider border-b border-zinc-800">
                                <th className="text-left px-5 py-3 font-medium">Channel</th>
                                <th className="text-left px-5 py-3 font-medium">Type</th>
                                <th className="text-right px-5 py-3 font-medium">Campaigns</th>
                                <th className="text-right px-5 py-3 font-medium">Spend</th>
                                <th className="text-right px-5 py-3 font-medium">Impressions</th>
                                <th className="text-right px-5 py-3 font-medium">Clicks</th>
                                <th className="text-right px-5 py-3 font-medium">Conversions</th>
                                <th className="text-right px-5 py-3 font-medium">ROAS</th>
                                <th className="text-right px-5 py-3 font-medium">CPA</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800/50">
                            {(channel_performance || []).map(ch => (
                                <tr key={ch.source} className="hover:bg-zinc-800/30 transition-colors">
                                    <td className="px-5 py-3 font-medium">{sourceIcons[ch.source] || ch.source}</td>
                                    <td className="px-5 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full ${
                                            ch.type === 'paid' ? 'bg-sky-400/10 text-sky-400' :
                                            ch.type === 'email' ? 'bg-emerald-400/10 text-emerald-400' :
                                            'bg-zinc-800 text-zinc-400'
                                        }`}>{ch.type}</span>
                                    </td>
                                    <td className="px-5 py-3 text-right">{ch.campaigns}</td>
                                    <td className="px-5 py-3 text-right">{formatCurrency(ch.total_spend)}</td>
                                    <td className="px-5 py-3 text-right text-zinc-400">{ch.total_impressions?.toLocaleString()}</td>
                                    <td className="px-5 py-3 text-right text-zinc-400">{ch.total_clicks?.toLocaleString()}</td>
                                    <td className="px-5 py-3 text-right font-medium">{ch.total_conversions?.toLocaleString()}</td>
                                    <td className="px-5 py-3 text-right">
                                        <span className={`font-medium ${ch.roas >= 3 ? 'text-emerald-400' : ch.roas >= 2 ? 'text-sky-400' : 'text-amber-400'}`}>
                                            {ch.roas}x
                                        </span>
                                    </td>
                                    <td className="px-5 py-3 text-right text-zinc-400">{ch.cpa > 0 ? formatCurrency(ch.cpa) : '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
