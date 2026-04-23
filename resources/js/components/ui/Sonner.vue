<script setup>
import { Toaster } from 'vue-sonner'
import { useTheme } from '@/lib/theme'
import 'vue-sonner/style.css'

const { theme } = useTheme()
</script>

<template>
    <Toaster position="top-right" :expand="true" :theme="theme" rich-colors />
</template>
