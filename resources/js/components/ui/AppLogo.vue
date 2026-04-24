<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" v-bind="$attrs">
        <defs>
            <linearGradient id="themeGlow" x1="0%" y1="100%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#f97316" />
                <!-- orange-500 -->
                <stop offset="100%" stop-color="#fbbf24" />
                <!-- amber-400 -->
            </linearGradient>
        </defs>

        <!-- Base Layer (Project Tasks / Data) -->
        <path
            d="M 50 72 L 82 82 L 50 92 L 18 82 Z"
            fill="currentColor"
            fill-opacity="0.25"
            stroke="currentColor"
            stroke-opacity="0.25"
            stroke-width="4"
            stroke-linejoin="round"
        />

        <!-- Mid Layer (Workflow / Pipeline) -->
        <path
            d="M 50 48 L 82 58 L 50 68 L 18 58 Z"
            fill="currentColor"
            stroke="currentColor"
            stroke-width="4"
            stroke-linejoin="round"
        />

        <!-- Top AI Sparkle (Intelligence engine driving the work) -->
        <path
            d="M 50 4 Q 50 24 74 24 Q 50 24 50 44 Q 50 24 26 24 Q 50 24 50 4 Z"
            fill="url(#themeGlow)"
        />
    </svg>
</template>

<script setup></script>
