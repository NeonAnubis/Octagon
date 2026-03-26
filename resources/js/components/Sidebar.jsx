import React from 'react';

const navSections = [
    {
        label: 'Analytics',
        items: [
            { id: 'overview', label: 'Overview', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
            { id: 'forecast', label: 'Forecasting', icon: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941' },
        ],
    },
    {
        label: 'Marketing',
        items: [
            { id: 'marketing', label: 'Channels', icon: 'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605' },
            { id: 'timing', label: 'Timing', icon: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z' },
            { id: 'audience', label: 'Audience', icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z' },
            { id: 'spend', label: 'Spend', icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z' },
        ],
    },
    {
        label: 'Intelligence',
        items: [
            { id: 'alerts', label: 'Alerts', icon: 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0' },
            { id: 'ai', label: 'AI Assistant', icon: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z' },
        ],
    },
    {
        label: 'System',
        items: [
            { id: 'integrations', label: 'Integrations', icon: 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-1.02l4.5-4.5a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244' },
        ],
    },
];

export default function Sidebar({ currentPage, onNavigate, isOpen, onClose }) {
    return (
        <>
            {isOpen && (
                <div className="fixed inset-0 z-40 bg-black/60 lg:hidden" onClick={onClose} />
            )}

            <aside className={`
                fixed lg:static inset-y-0 left-0 z-50 w-64 bg-[#0a0a0a] border-r border-zinc-800
                transform transition-transform duration-200 ease-out
                ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
            `}>
                <div className="flex flex-col h-full">
                    {/* Logo */}
                    <div className="flex items-center gap-3 px-6 h-16 border-b border-zinc-800 shrink-0">
                        <svg viewBox="0 0 32 32" fill="none" className="w-7 h-7">
                            <polygon points="16,1 29.86,8.5 29.86,23.5 16,31 2.14,23.5 2.14,8.5" stroke="#38bdf8" strokeWidth="1.5" fill="none"/>
                            <polygon points="16,5 26.66,10.5 26.66,21.5 16,27 5.34,21.5 5.34,10.5" stroke="#38bdf8" strokeWidth="0.5" fill="none" opacity="0.4"/>
                        </svg>
                        <span className="text-lg font-bold tracking-tight">Octagon</span>
                    </div>

                    {/* Nav */}
                    <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-5">
                        {navSections.map(section => (
                            <div key={section.label}>
                                <div className="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">{section.label}</div>
                                <div className="space-y-0.5">
                                    {section.items.map(item => (
                                        <button
                                            key={item.id}
                                            onClick={() => onNavigate(item.id)}
                                            className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all ${
                                                currentPage === item.id
                                                    ? 'bg-sky-400/10 text-sky-400'
                                                    : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50'
                                            }`}
                                        >
                                            <svg className="w-4.5 h-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                <path strokeLinecap="round" strokeLinejoin="round" d={item.icon} />
                                            </svg>
                                            {item.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </nav>

                    {/* Status */}
                    <div className="px-4 py-4 border-t border-zinc-800 shrink-0">
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                            <div className="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                            All systems operational
                        </div>
                    </div>
                </div>
            </aside>
        </>
    );
}
