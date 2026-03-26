import React, { useState } from 'react';
import Sidebar from './components/Sidebar';
import OverviewPage from './pages/OverviewPage';
import EventDetailPage from './pages/EventDetailPage';
import MarketingPage from './pages/MarketingPage';
import AlertsPage from './pages/AlertsPage';
import AiChatPage from './pages/AiChatPage';
import ForecastPage from './pages/ForecastPage';
import TimingPage from './pages/TimingPage';
import AudiencePage from './pages/AudiencePage';
import SpendPage from './pages/SpendPage';
import IntegrationsPage from './pages/IntegrationsPage';

const pages = {
    overview: OverviewPage,
    event: EventDetailPage,
    marketing: MarketingPage,
    timing: TimingPage,
    audience: AudiencePage,
    spend: SpendPage,
    forecast: ForecastPage,
    alerts: AlertsPage,
    ai: AiChatPage,
    integrations: IntegrationsPage,
};

export default function App() {
    const [currentPage, setCurrentPage] = useState('overview');
    const [selectedEventId, setSelectedEventId] = useState(null);
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navigate = (page, eventId = null) => {
        setCurrentPage(page);
        if (eventId) setSelectedEventId(eventId);
        setSidebarOpen(false);
    };

    const PageComponent = pages[currentPage] || OverviewPage;

    return (
        <div className="flex h-screen overflow-hidden bg-[#0a0a0a]">
            <Sidebar
                currentPage={currentPage}
                onNavigate={navigate}
                isOpen={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
            />

            <main className="flex-1 overflow-y-auto">
                {/* Mobile header */}
                <div className="lg:hidden sticky top-0 z-40 flex items-center gap-3 px-4 py-3 bg-[#0a0a0a]/95 backdrop-blur border-b border-zinc-800">
                    <button onClick={() => setSidebarOpen(true)} className="p-1.5 rounded-lg hover:bg-zinc-800">
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span className="text-sm font-semibold">Octagon</span>
                </div>

                <div className="p-4 md:p-6 lg:p-8">
                    <PageComponent
                        eventId={selectedEventId}
                        onNavigate={navigate}
                        onSelectEvent={(id) => navigate('event', id)}
                    />
                </div>
            </main>
        </div>
    );
}
