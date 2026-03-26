import React from 'react';

const statusColors = {
    sold_out: 'bg-sky-400 text-black',
    available: 'bg-emerald-400/20 text-emerald-400 border-emerald-400/30',
    soft: 'bg-amber-400/20 text-amber-400 border-amber-400/30',
    warning: 'bg-orange-400/20 text-orange-400 border-orange-400/30',
    critical: 'bg-red-400/20 text-red-400 border-red-400/30',
};

export default function SectionHeatmap({ sections, onSelect }) {
    const tiers = ['vip', 'floor', 'lower', 'upper'];

    const grouped = tiers.reduce((acc, tier) => {
        acc[tier] = (sections || []).filter(s => s.price_tier === tier);
        return acc;
    }, {});

    return (
        <div className="space-y-4">
            <h3 className="text-sm font-semibold text-zinc-400 uppercase tracking-wider">Section Heatmap</h3>
            <div className="space-y-3">
                {tiers.map(tier => (
                    grouped[tier]?.length > 0 && (
                        <div key={tier}>
                            <div className="text-xs text-zinc-500 mb-1.5 uppercase">{tier}</div>
                            <div className="flex flex-wrap gap-2">
                                {grouped[tier].map(section => (
                                    <button
                                        key={section.id}
                                        onClick={() => onSelect?.(section)}
                                        className={`px-3 py-2 rounded-lg text-xs font-medium border transition-all hover:scale-105 ${statusColors[section.status] || statusColors.available}`}
                                    >
                                        <div className="font-bold">{section.name}</div>
                                        <div className="opacity-80">{section.sellthrough}%</div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )
                ))}
            </div>

            {/* Legend */}
            <div className="flex flex-wrap gap-3 pt-2 border-t border-zinc-800">
                {Object.entries({ available: 'On Track', soft: 'Soft', warning: 'Warning', critical: 'Critical', sold_out: 'Sold Out' }).map(([k, v]) => (
                    <div key={k} className="flex items-center gap-1.5 text-xs text-zinc-500">
                        <div className={`w-2.5 h-2.5 rounded-sm ${statusColors[k].split(' ')[0]}`}></div>
                        {v}
                    </div>
                ))}
            </div>
        </div>
    );
}
