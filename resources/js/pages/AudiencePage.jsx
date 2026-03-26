import React from 'react';
import { useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';

const formatCurrency = (v) => '$' + Number(v).toFixed(0);

export default function AudiencePage() {
    const { data, loading } = useApi('/marketing/audience');

    if (loading) return <LoadingSpinner text="Analyzing audience signals..." />;
    if (!data) return null;

    const { top_campaigns, underperformers, suggestions } = data;

    const typeIcons = {
        channel: 'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5',
        targeting: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
        geo: 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582',
        audience: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        lookalike: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
    };

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Audience Targeting</h1>
                <p className="text-sm text-zinc-500 mt-1">Signals from high-converting campaigns to inform future targeting</p>
            </div>

            {/* Campaign Comparison */}
            <div className="grid lg:grid-cols-2 gap-6">
                {/* Top Performers */}
                <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                    <div className="px-5 py-4 border-b border-zinc-800 flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-emerald-400"></div>
                        <h3 className="text-sm font-semibold">Top Performing Campaigns</h3>
                    </div>
                    <div className="divide-y divide-zinc-800/50">
                        {(top_campaigns || []).map((c, i) => (
                            <div key={i} className="px-5 py-3 flex items-center justify-between">
                                <div className="min-w-0 flex-1">
                                    <div className="text-sm font-medium truncate">{c.name}</div>
                                    <div className="text-xs text-zinc-500">{c.source} — {c.conversions} conversions</div>
                                </div>
                                <div className="flex items-center gap-3 ml-3 shrink-0">
                                    <span className="text-xs font-medium text-emerald-400">{c.roas}x ROAS</span>
                                    <span className="text-xs text-zinc-500">{formatCurrency(c.cpa)} CPA</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Underperformers */}
                <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                    <div className="px-5 py-4 border-b border-zinc-800 flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-red-400"></div>
                        <h3 className="text-sm font-semibold">Underperforming Campaigns</h3>
                    </div>
                    <div className="divide-y divide-zinc-800/50">
                        {(underperformers || []).map((c, i) => (
                            <div key={i} className="px-5 py-3 flex items-center justify-between">
                                <div className="min-w-0 flex-1">
                                    <div className="text-sm font-medium truncate">{c.name}</div>
                                    <div className="text-xs text-zinc-500">{c.source} — {c.conversions} conversions</div>
                                </div>
                                <div className="flex items-center gap-3 ml-3 shrink-0">
                                    <span className="text-xs font-medium text-red-400">{c.roas}x ROAS</span>
                                    <span className="text-xs text-zinc-500">{c.cpa > 0 ? formatCurrency(c.cpa) + ' CPA' : 'No conv.'}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Targeting Suggestions */}
            <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                <div className="px-5 py-4 border-b border-zinc-800">
                    <h3 className="text-sm font-semibold">Targeting Recommendations</h3>
                    <p className="text-xs text-zinc-500 mt-1">Based on analysis of your highest and lowest performing campaigns</p>
                </div>
                <div className="divide-y divide-zinc-800/50">
                    {(suggestions || []).map((s, i) => (
                        <div key={i} className="p-5 flex items-start gap-4">
                            <div className="w-10 h-10 rounded-xl bg-sky-400/10 flex items-center justify-center shrink-0">
                                <svg className="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d={typeIcons[s.type] || typeIcons.audience} />
                                </svg>
                            </div>
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="text-xs px-2 py-0.5 rounded bg-zinc-800 text-zinc-400 uppercase font-medium">{s.type}</span>
                                    {s.source && <span className="text-xs text-sky-400">{s.source}</span>}
                                </div>
                                <p className="text-sm text-zinc-200 mb-1">{s.insight}</p>
                                <div className="text-xs text-sky-400/80 p-2 bg-sky-400/5 rounded-lg border border-sky-400/10 mt-2">
                                    <span className="font-semibold">Action:</span> {s.action}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
