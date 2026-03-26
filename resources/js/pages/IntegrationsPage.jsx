import React from 'react';
import { useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';

const sourceIcons = {
    ticketmaster: { color: 'text-sky-400', bg: 'bg-sky-400/10' },
    meta: { color: 'text-blue-400', bg: 'bg-blue-400/10' },
    google: { color: 'text-emerald-400', bg: 'bg-emerald-400/10' },
    klaviyo: { color: 'text-violet-400', bg: 'bg-violet-400/10' },
    instagram: { color: 'text-pink-400', bg: 'bg-pink-400/10' },
    twitter: { color: 'text-zinc-400', bg: 'bg-zinc-400/10' },
    youtube: { color: 'text-red-400', bg: 'bg-red-400/10' },
};

const typeLabels = {
    sales: { label: 'Sales Data', color: 'bg-sky-400/10 text-sky-400' },
    paid: { label: 'Paid Media', color: 'bg-violet-400/10 text-violet-400' },
    email: { label: 'Email', color: 'bg-emerald-400/10 text-emerald-400' },
    organic: { label: 'Organic Social', color: 'bg-amber-400/10 text-amber-400' },
};

export default function IntegrationsPage() {
    const { data, loading } = useApi('/integrations');

    if (loading) return <LoadingSpinner text="Loading integrations..." />;

    const integrations = data?.integrations || [];
    const configured = integrations.filter(i => i.configured);
    const pending = integrations.filter(i => !i.configured);

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Integrations</h1>
                <p className="text-sm text-zinc-500 mt-1">Connected data sources and their status</p>
            </div>

            {/* Summary */}
            <div className="flex gap-4">
                <div className="px-4 py-3 rounded-xl border border-emerald-400/20 bg-emerald-400/5">
                    <div className="text-2xl font-bold text-emerald-400">{configured.length}</div>
                    <div className="text-xs text-zinc-500">Connected</div>
                </div>
                <div className="px-4 py-3 rounded-xl border border-zinc-800 bg-zinc-900/50">
                    <div className="text-2xl font-bold text-zinc-400">{pending.length}</div>
                    <div className="text-xs text-zinc-500">Pending Setup</div>
                </div>
            </div>

            {/* Integration Cards */}
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                {integrations.map(integration => {
                    const iconStyle = sourceIcons[integration.source] || { color: 'text-zinc-400', bg: 'bg-zinc-400/10' };
                    const typeStyle = typeLabels[integration.type] || { label: integration.type, color: 'bg-zinc-800 text-zinc-400' };

                    return (
                        <div key={integration.source} className={`rounded-xl border overflow-hidden transition-colors ${
                            integration.configured ? 'border-zinc-700 hover:border-zinc-600' : 'border-zinc-800 opacity-60'
                        }`}>
                            <div className="p-5">
                                <div className="flex items-start justify-between mb-3">
                                    <div className={`w-10 h-10 rounded-xl ${iconStyle.bg} flex items-center justify-center`}>
                                        <span className={`text-lg font-bold ${iconStyle.color}`}>{integration.name[0]}</span>
                                    </div>
                                    <div className={`flex items-center gap-1.5 text-xs font-medium ${
                                        integration.configured ? 'text-emerald-400' : 'text-zinc-500'
                                    }`}>
                                        <div className={`w-2 h-2 rounded-full ${
                                            integration.configured ? 'bg-emerald-400 animate-pulse' : 'bg-zinc-600'
                                        }`}></div>
                                        {integration.configured ? 'Connected' : 'Not configured'}
                                    </div>
                                </div>

                                <h3 className="text-sm font-bold mb-1">{integration.name}</h3>
                                <p className="text-xs text-zinc-500 mb-3">{integration.description}</p>

                                <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${typeStyle.color}`}>
                                    {typeStyle.label}
                                </span>
                            </div>

                            {!integration.configured && (
                                <div className="px-5 py-3 border-t border-zinc-800 bg-zinc-900/30">
                                    <p className="text-[10px] text-zinc-500">
                                        Add <code className="text-sky-400">{integration.source.toUpperCase()}_API_KEY</code> to .env to connect
                                    </p>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Sync Commands Info */}
            <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 p-5">
                <h3 className="text-sm font-semibold mb-3">Automated Data Sync</h3>
                <p className="text-xs text-zinc-400 mb-4">Data from connected integrations is synced automatically on schedule:</p>
                <div className="space-y-2 text-xs">
                    <div className="flex items-center gap-3">
                        <code className="text-sky-400 bg-zinc-800 px-2 py-1 rounded">octagon:sync-ticketmaster</code>
                        <span className="text-zinc-500">Every 15 minutes — event sales and inventory</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <code className="text-sky-400 bg-zinc-800 px-2 py-1 rounded">octagon:sync-marketing</code>
                        <span className="text-zinc-500">Every hour — campaign metrics across all channels</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <code className="text-sky-400 bg-zinc-800 px-2 py-1 rounded">octagon:generate-alerts</code>
                        <span className="text-zinc-500">Every 30 minutes — soft sections, spend issues, velocity drops</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
