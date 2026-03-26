import React, { useState, useRef, useEffect } from 'react';
import { api, useApi } from '../hooks/useApi';
import LoadingSpinner from '../components/LoadingSpinner';

const quickPrompts = [
    'How are ticket sales trending across all events?',
    'Which marketing channel has the best ROAS?',
    'What sections need pricing action?',
    'Give me campaign timing recommendations',
    'Summarize the key risks right now',
];

export default function AiChatPage() {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [sessionId] = useState(() => 'session_' + Math.random().toString(36).substr(2, 9));
    const [selectedEventId, setSelectedEventId] = useState(null);
    const messagesEndRef = useRef(null);

    const { data: events } = useApi('/events');
    const { data: recommendations, loading: recsLoading } = useApi('/ai/recommendations');

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = async (text) => {
        if (!text.trim()) return;

        const userMsg = { role: 'user', content: text };
        setMessages(prev => [...prev, userMsg]);
        setInput('');
        setLoading(true);

        try {
            const res = await api.post('/ai/chat', {
                message: text,
                event_id: selectedEventId,
                session_id: sessionId,
            });

            setMessages(prev => [...prev, {
                role: 'assistant',
                content: res.data.message,
            }]);
        } catch (err) {
            setMessages(prev => [...prev, {
                role: 'assistant',
                content: 'Sorry, I encountered an error. Please try again.',
            }]);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        sendMessage(input);
    };

    const renderMarkdown = (text) => {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\n/g, '<br/><br/>')
            .replace(/\n/g, '<br/>');
    };

    return (
        <div className="space-y-6 max-w-5xl mx-auto">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">AI Assistant</h1>
                    <p className="text-sm text-zinc-500 mt-1">Ask questions about your sales and marketing data</p>
                </div>
                <select
                    value={selectedEventId || ''}
                    onChange={(e) => setSelectedEventId(e.target.value ? Number(e.target.value) : null)}
                    className="text-xs bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-300 focus:outline-none focus:border-sky-400"
                >
                    <option value="">All Events</option>
                    {(events || []).map(e => (
                        <option key={e.id} value={e.id}>{e.name}</option>
                    ))}
                </select>
            </div>

            <div className="grid lg:grid-cols-3 gap-6">
                {/* Chat Column */}
                <div className="lg:col-span-2 flex flex-col rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden" style={{ height: 'calc(100vh - 200px)' }}>
                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-5 space-y-4">
                        {messages.length === 0 && (
                            <div className="text-center py-16">
                                <div className="w-14 h-14 rounded-2xl bg-sky-400/10 flex items-center justify-center mx-auto mb-4">
                                    <svg className="w-7 h-7 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold mb-2">Octagon AI</h3>
                                <p className="text-sm text-zinc-500 max-w-sm mx-auto mb-6">
                                    Ask me anything about your ticket sales, marketing campaigns, or get strategic recommendations.
                                </p>
                                <div className="flex flex-wrap justify-center gap-2">
                                    {quickPrompts.map(prompt => (
                                        <button
                                            key={prompt}
                                            onClick={() => sendMessage(prompt)}
                                            className="text-xs px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700 transition-colors text-left"
                                        >
                                            {prompt}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {messages.map((msg, i) => (
                            <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-[80%] px-4 py-3 rounded-2xl text-sm leading-relaxed ${
                                    msg.role === 'user'
                                        ? 'bg-sky-400 text-black rounded-br-md'
                                        : 'bg-zinc-800 text-zinc-200 rounded-bl-md'
                                }`}>
                                    {msg.role === 'assistant' ? (
                                        <div dangerouslySetInnerHTML={{ __html: renderMarkdown(msg.content) }} />
                                    ) : msg.content}
                                </div>
                            </div>
                        ))}

                        {loading && (
                            <div className="flex justify-start">
                                <div className="bg-zinc-800 text-zinc-200 rounded-2xl rounded-bl-md px-4 py-3">
                                    <div className="flex items-center gap-1.5">
                                        <div className="w-2 h-2 bg-sky-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                        <div className="w-2 h-2 bg-sky-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                                        <div className="w-2 h-2 bg-sky-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <form onSubmit={handleSubmit} className="p-4 border-t border-zinc-800">
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                placeholder="Ask about sales, marketing, or pricing..."
                                className="flex-1 bg-zinc-800 border border-zinc-700 rounded-xl px-4 py-3 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-sky-400 transition-colors"
                                disabled={loading}
                            />
                            <button
                                type="submit"
                                disabled={loading || !input.trim()}
                                className="px-4 py-3 bg-sky-400 text-black rounded-xl font-medium text-sm hover:bg-sky-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                {/* Recommendations Sidebar */}
                <div className="space-y-4">
                    <div className="rounded-xl bg-zinc-900/50 border border-zinc-800 overflow-hidden">
                        <div className="px-4 py-3 border-b border-zinc-800 flex items-center gap-2">
                            <svg className="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <span className="text-sm font-semibold">AI Recommendations</span>
                        </div>

                        {recsLoading ? (
                            <div className="p-4">
                                <LoadingSpinner text="Generating..." />
                            </div>
                        ) : (
                            <div className="divide-y divide-zinc-800/50">
                                {(recommendations || []).map((rec, i) => (
                                    <div key={i} className="p-4 hover:bg-zinc-800/30 transition-colors">
                                        <div className="flex items-center gap-2 mb-1">
                                            <span className={`w-1.5 h-1.5 rounded-full ${
                                                rec.priority === 'high' ? 'bg-red-400' :
                                                rec.priority === 'medium' ? 'bg-amber-400' : 'bg-emerald-400'
                                            }`}></span>
                                            <span className="text-xs font-semibold">{rec.title}</span>
                                        </div>
                                        <p className="text-[11px] text-zinc-400 leading-relaxed">{rec.description}</p>
                                        <div className="flex items-center gap-2 mt-2">
                                            <span className="text-[10px] px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-500">{rec.category}</span>
                                            <span className={`text-[10px] px-1.5 py-0.5 rounded ${
                                                rec.priority === 'high' ? 'bg-red-400/10 text-red-400' :
                                                rec.priority === 'medium' ? 'bg-amber-400/10 text-amber-400' :
                                                'bg-emerald-400/10 text-emerald-400'
                                            }`}>{rec.priority}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
