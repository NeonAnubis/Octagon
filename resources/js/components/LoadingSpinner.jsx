import React from 'react';

export default function LoadingSpinner({ text = 'Loading...' }) {
    return (
        <div className="flex flex-col items-center justify-center py-20">
            <div className="w-8 h-8 border-2 border-sky-400/30 border-t-sky-400 rounded-full animate-spin mb-4"></div>
            <p className="text-sm text-zinc-500">{text}</p>
        </div>
    );
}
