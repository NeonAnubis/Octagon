<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Octagon — AI-Powered Fight Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0a0a0a] text-white antialiased" x-data="landing()">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 border-b border-white/5 backdrop-blur-xl bg-[#0a0a0a]/80">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 relative">
                    <svg viewBox="0 0 32 32" fill="none" class="w-full h-full">
                        <polygon points="16,1 29.86,8.5 29.86,23.5 16,31 2.14,23.5 2.14,8.5" stroke="#38bdf8" stroke-width="1.5" fill="none"/>
                        <polygon points="16,5 26.66,10.5 26.66,21.5 16,27 5.34,21.5 5.34,10.5" stroke="#38bdf8" stroke-width="0.5" fill="none" opacity="0.4"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Octagon</span>
            </div>
            <div class="hidden md:flex items-center gap-8 text-sm text-zinc-400">
                <a href="#features" class="hover:text-white transition-colors">Features</a>
                <a href="#analytics" class="hover:text-white transition-colors">Analytics</a>
                <a href="#ai" class="hover:text-white transition-colors">AI Intelligence</a>
                <a href="/dashboard" class="px-4 py-2 bg-sky-400 text-black font-semibold rounded-lg hover:bg-sky-300 transition-colors">
                    Launch Dashboard
                </a>
            </div>
            <a href="/dashboard" class="md:hidden px-4 py-2 bg-sky-400 text-black font-semibold rounded-lg text-sm">
                Dashboard
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        <!-- Animated grid background -->
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(56,189,248,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(56,189,248,0.3) 1px, transparent 1px); background-size: 60px 60px;"></div>

        <!-- Octagon glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] opacity-20">
            <svg viewBox="0 0 600 600" fill="none" class="w-full h-full animate-pulse" style="animation-duration: 4s;">
                <polygon points="300,30 555,157 555,443 300,570 45,443 45,157"
                    stroke="#38bdf8" stroke-width="1" fill="none" opacity="0.4"/>
                <polygon points="300,80 510,185 510,415 300,520 90,415 90,185"
                    stroke="#38bdf8" stroke-width="0.5" fill="none" opacity="0.2"/>
            </svg>
        </div>

        <!-- Radial glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-sky-400/5 rounded-full blur-3xl"></div>

        <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 mb-8 border border-sky-400/20 rounded-full text-xs font-medium text-sky-400 bg-sky-400/5">
                <span class="w-1.5 h-1.5 bg-sky-400 rounded-full animate-pulse"></span>
                AI-Powered Analytics Platform
            </div>

            <h1 class="text-5xl md:text-7xl lg:text-8xl font-black tracking-tight leading-[0.9] mb-6">
                <span class="text-white">Every Seat.</span><br>
                <span class="text-white">Every Dollar.</span><br>
                <span class="bg-gradient-to-r from-sky-400 to-sky-200 bg-clip-text text-transparent">Every Decision.</span>
            </h1>

            <p class="text-lg md:text-xl text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Real-time ticket sales intelligence meets marketing attribution.
                Know what's selling, what's driving it, and what to do next — powered by AI.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/dashboard" class="group px-8 py-4 bg-sky-400 text-black font-bold rounded-xl hover:bg-sky-300 transition-all hover:shadow-lg hover:shadow-sky-400/20 text-lg">
                    Enter the Octagon
                    <span class="inline-block ml-2 group-hover:translate-x-1 transition-transform">&rarr;</span>
                </a>
                <a href="#features" class="px-8 py-4 border border-zinc-700 text-zinc-300 font-medium rounded-xl hover:border-zinc-500 hover:text-white transition-all text-lg">
                    See How It Works
                </a>
            </div>

            <!-- Stats bar -->
            <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto" x-data="{ shown: false }" x-intersect="shown = true">
                <div class="text-center" x-show="shown" x-transition.duration.500ms>
                    <div class="text-3xl md:text-4xl font-black text-white" x-data="{ val: 0 }" x-init="$nextTick(() => { let i = setInterval(() => { val += 247; if(val >= 12350) { val = 12350; clearInterval(i); }}, 30)})" x-text="val.toLocaleString()"></div>
                    <div class="text-xs text-zinc-500 mt-1 uppercase tracking-wider">Tickets Tracked</div>
                </div>
                <div class="text-center" x-show="shown" x-transition.delay.100ms.duration.500ms>
                    <div class="text-3xl md:text-4xl font-black text-white">$2.4M</div>
                    <div class="text-xs text-zinc-500 mt-1 uppercase tracking-wider">Revenue Analyzed</div>
                </div>
                <div class="text-center" x-show="shown" x-transition.delay.200ms.duration.500ms>
                    <div class="text-3xl md:text-4xl font-black text-white">6</div>
                    <div class="text-xs text-zinc-500 mt-1 uppercase tracking-wider">Active Events</div>
                </div>
                <div class="text-center" x-show="shown" x-transition.delay.300ms.duration.500ms>
                    <div class="text-3xl md:text-4xl font-black text-white">3.8x</div>
                    <div class="text-xs text-zinc-500 mt-1 uppercase tracking-wider">Avg ROAS</div>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20">
                <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-4">Two engines. One platform.</h2>
                <p class="text-zinc-400 text-lg max-w-xl mx-auto">Sales velocity meets marketing intelligence — connected in real time.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Sales Analytics Card -->
                <div class="group relative p-8 rounded-2xl border border-zinc-800 bg-zinc-900/50 hover:border-sky-400/30 transition-all duration-500">
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-sky-400/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative">
                        <div class="w-12 h-12 rounded-xl bg-sky-400/10 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Ticket Sales Analytics</h3>
                        <p class="text-zinc-400 mb-6">Real-time sellthrough tracking with predictive modeling and automated alerts.</p>
                        <ul class="space-y-3 text-sm text-zinc-400">
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Sales velocity by section, tier, and date
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Predictive sellthrough forecasting
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Automated soft-section detection
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Demand scoring by fight card & market
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                AI pricing recommendations
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Marketing Intelligence Card -->
                <div class="group relative p-8 rounded-2xl border border-zinc-800 bg-zinc-900/50 hover:border-sky-400/30 transition-all duration-500">
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-sky-400/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative">
                        <div class="w-12 h-12 rounded-xl bg-sky-400/10 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Marketing Intelligence</h3>
                        <p class="text-zinc-400 mb-6">Connect every channel to ticket sales. See what's working and what to do next.</p>
                        <ul class="space-y-3 text-sm text-zinc-400">
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Multi-touch attribution modeling
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Campaign timing optimization
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Audience targeting signals
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Spend optimization alerts
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-sky-400 rounded-full"></span>
                                Meta, Google, Klaviyo, Social integrations
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Analytics Preview Section -->
    <section id="analytics" class="py-32 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-sky-400/[0.02] to-transparent"></div>
        <div class="max-w-7xl mx-auto px-6 relative">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-4">Built for decisions, not dashboards.</h2>
                <p class="text-zinc-400 text-lg max-w-xl mx-auto">Every metric surfaces what to do, not just what happened.</p>
            </div>

            <!-- Mock Dashboard Preview -->
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 overflow-hidden shadow-2xl shadow-black/50">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-800">
                    <div class="w-3 h-3 rounded-full bg-zinc-700"></div>
                    <div class="w-3 h-3 rounded-full bg-zinc-700"></div>
                    <div class="w-3 h-3 rounded-full bg-zinc-700"></div>
                    <div class="ml-4 px-3 py-1 rounded bg-zinc-800 text-xs text-zinc-500">octagon.io/dashboard</div>
                </div>

                <div class="p-6 md:p-8">
                    <!-- Mini dashboard grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 rounded-xl bg-zinc-800/50 border border-zinc-700/50">
                            <div class="text-xs text-zinc-500 mb-1">Total Revenue</div>
                            <div class="text-xl font-bold">$2,847,320</div>
                            <div class="text-xs text-emerald-400 mt-1">+12.4% vs last event</div>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-800/50 border border-zinc-700/50">
                            <div class="text-xs text-zinc-500 mb-1">Sellthrough</div>
                            <div class="text-xl font-bold">73.2%</div>
                            <div class="text-xs text-sky-400 mt-1">14 days to event</div>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-800/50 border border-zinc-700/50">
                            <div class="text-xs text-zinc-500 mb-1">Ad Spend</div>
                            <div class="text-xl font-bold">$84,200</div>
                            <div class="text-xs text-emerald-400 mt-1">ROAS: 4.2x</div>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-800/50 border border-zinc-700/50">
                            <div class="text-xs text-zinc-500 mb-1">Active Alerts</div>
                            <div class="text-xl font-bold text-amber-400">3</div>
                            <div class="text-xs text-amber-400/70 mt-1">1 critical</div>
                        </div>
                    </div>

                    <!-- Chart area mock -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="md:col-span-2 p-4 rounded-xl bg-zinc-800/30 border border-zinc-700/30">
                            <div class="text-sm font-medium text-zinc-400 mb-4">Sellthrough Velocity</div>
                            <div class="flex items-end gap-1 h-32">
                                @for ($i = 0; $i < 28; $i++)
                                    @php $h = min(100, 15 + ($i * 2.5) + rand(0, 10)); @endphp
                                    <div class="flex-1 rounded-sm transition-all" style="height: {{ $h }}%; background: linear-gradient(to top, #0ea5e9, #38bdf8);opacity:{{ 0.4 + ($i/40) }}"></div>
                                @endfor
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-zinc-600">
                                <span>45 days out</span>
                                <span>Today</span>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-800/30 border border-zinc-700/30">
                            <div class="text-sm font-medium text-zinc-400 mb-4">Channel Attribution</div>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-xs mb-1"><span class="text-zinc-400">Meta Ads</span><span class="text-white font-medium">38%</span></div>
                                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden"><div class="h-full rounded-full bg-sky-400" style="width:38%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1"><span class="text-zinc-400">Google Ads</span><span class="text-white font-medium">28%</span></div>
                                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden"><div class="h-full rounded-full bg-sky-400/70" style="width:28%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1"><span class="text-zinc-400">Email</span><span class="text-white font-medium">22%</span></div>
                                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden"><div class="h-full rounded-full bg-sky-400/50" style="width:22%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1"><span class="text-zinc-400">Organic</span><span class="text-white font-medium">12%</span></div>
                                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden"><div class="h-full rounded-full bg-sky-400/30" style="width:12%"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Section -->
    <section id="ai" class="py-32 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 border border-sky-400/20 rounded-full text-xs font-medium text-sky-400 bg-sky-400/5">
                        Powered by AI
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-6">
                        Ask anything.<br>
                        <span class="bg-gradient-to-r from-sky-400 to-sky-200 bg-clip-text text-transparent">Get answers that move tickets.</span>
                    </h2>
                    <p class="text-zinc-400 text-lg mb-8 leading-relaxed">
                        The AI layer connects your sales data to your marketing data and speaks in plain English.
                        No SQL queries. No pivot tables. Just answers.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-5 h-5 rounded-full bg-sky-400/10 flex items-center justify-center mt-0.5 shrink-0">
                                <div class="w-1.5 h-1.5 bg-sky-400 rounded-full"></div>
                            </div>
                            <span class="text-zinc-300">"How is Section 200 tracking vs. the last Vegas PPV?"</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-5 h-5 rounded-full bg-sky-400/10 flex items-center justify-center mt-0.5 shrink-0">
                                <div class="w-1.5 h-1.5 bg-sky-400 rounded-full"></div>
                            </div>
                            <span class="text-zinc-300">"Which channel is driving the most conversions this week?"</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-5 h-5 rounded-full bg-sky-400/10 flex items-center justify-center mt-0.5 shrink-0">
                                <div class="w-1.5 h-1.5 bg-sky-400 rounded-full"></div>
                            </div>
                            <span class="text-zinc-300">"Should we drop prices on upper bowl or push more ad spend?"</span>
                        </div>
                    </div>
                </div>

                <!-- Chat Preview -->
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 overflow-hidden">
                    <div class="p-4 border-b border-zinc-800 flex items-center gap-2">
                        <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-zinc-400">Octagon AI</span>
                    </div>
                    <div class="p-6 space-y-4 text-sm">
                        <div class="flex justify-end">
                            <div class="px-4 py-2 rounded-2xl rounded-br-md bg-sky-400 text-black max-w-xs">
                                What should we do about the upper sections for UFC 310?
                            </div>
                        </div>
                        <div class="flex justify-start">
                            <div class="px-4 py-3 rounded-2xl rounded-bl-md bg-zinc-800 text-zinc-200 max-w-sm leading-relaxed">
                                <strong>UFC 310 Upper Sections:</strong> Currently at 42% sellthrough with 14 days out — that's 18% behind the historical average.
                                <br><br>
                                <strong>Recommendation:</strong> Deploy a 12% promo code via Klaviyo targeting previous Vegas attendees. Historical data shows this drives a 20% velocity spike in 48hrs.
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="px-4 py-2 rounded-2xl rounded-br-md bg-sky-400 text-black max-w-xs">
                                What about paid ads?
                            </div>
                        </div>
                        <div class="flex justify-start">
                            <div class="px-4 py-3 rounded-2xl rounded-bl-md bg-zinc-800 text-zinc-200 max-w-sm leading-relaxed">
                                Shift 15% of Meta prospecting budget to Google Search — brand terms are converting at 3.2x better ROAS. Also boost the weigh-in post on IG ($500) to capture the organic momentum.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integration Logos -->
    <section class="py-20 border-t border-zinc-800/50">
        <div class="max-w-7xl mx-auto px-6">
            <p class="text-center text-xs uppercase tracking-widest text-zinc-600 mb-10">Integrated Data Sources</p>
            <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6 text-zinc-500">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/></svg>
                    Meta Ads
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Google Ads
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    Klaviyo
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 01-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 017.8 2zm-.2 2A3.6 3.6 0 004 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 003.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6zm9.65 1.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zM12 7a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                    Instagram
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    X / Twitter
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    YouTube
                </div>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Ticketmaster
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-32 relative">
        <div class="absolute inset-0 bg-gradient-to-t from-sky-400/[0.03] to-transparent"></div>
        <div class="max-w-3xl mx-auto px-6 text-center relative">
            <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-6">
                Ready to see inside<br>
                <span class="bg-gradient-to-r from-sky-400 to-sky-200 bg-clip-text text-transparent">the Octagon?</span>
            </h2>
            <p class="text-zinc-400 text-lg mb-10">Every second of indecision is a seat unsold. Enter the dashboard and take control.</p>
            <a href="/dashboard" class="inline-flex items-center gap-2 px-8 py-4 bg-sky-400 text-black font-bold rounded-xl hover:bg-sky-300 transition-all hover:shadow-lg hover:shadow-sky-400/20 text-lg">
                Launch Dashboard
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-zinc-800/50 py-8">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 32 32" fill="none" class="w-5 h-5">
                    <polygon points="16,1 29.86,8.5 29.86,23.5 16,31 2.14,23.5 2.14,8.5" stroke="#38bdf8" stroke-width="1.5" fill="none"/>
                </svg>
                <span class="text-sm font-semibold">Octagon</span>
            </div>
            <p class="text-xs text-zinc-600">&copy; {{ date('Y') }} Octagon Analytics. Built for the fight business.</p>
        </div>
    </footer>

    <script>
        function landing() {
            return {};
        }
    </script>
</body>
</html>
