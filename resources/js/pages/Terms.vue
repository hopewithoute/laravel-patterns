<script>
export default {
  layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, ScrollText } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
  <Head title="Terms of Service - TaskFlow" />
  
  <div class="min-h-screen bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative overflow-hidden flex flex-col">
    <div class="orb-amber top-[-15%] left-[-10%] opacity-20"></div>

    <header class="container mx-auto px-6 py-10 relative z-10 max-w-4xl">
       <Link href="/" class="group inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-12">
          <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          Back to Home
       </Link>
       
       <div class="flex items-center gap-4 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center glow-amber-sm">
             <ScrollText class="w-6 h-6 text-primary" />
          </div>
          <h1 class="font-display text-4xl sm:text-5xl tracking-tight">Terms of <span class="italic text-gradient">Service</span></h1>
       </div>
    </header>

    <main class="container mx-auto px-6 py-12 relative z-10 max-w-4xl flex-1">
      <div class="glass-elevated rounded-3xl p-8 sm:p-12 border border-border/50 relative overflow-hidden min-h-[400px]">
         <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
         
         <div class="prose prose-invert max-w-none space-y-8">
            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">1. Acceptance of Terms</h2>
               <p class="text-muted-foreground leading-relaxed">
                  Welcome to TaskFlow. By accessing or using our platform, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.
               </p>
            </section>

            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">2. User Responsibilities</h2>
               <p class="text-muted-foreground leading-relaxed">
                  You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.
               </p>
            </section>
            
            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">3. Premium Crafted Content</h2>
               <p class="text-muted-foreground leading-relaxed">
                  All design elements, including our tactile interactions and "Lunar Obsidian" aesthetic, are intellectual property of TaskFlow Inc.
               </p>
            </section>

            <div class="pt-12 border-t border-border/40 mt-12">
               <p class="text-xs font-mono text-muted-foreground">LAST UPDATED: APRIL 14, 2026</p>
            </div>
         </div>
      </div>
    </main>

    <footer class="container mx-auto px-6 py-10 text-center text-xs text-muted-foreground font-mono">
       <p>© 2026 TaskFlow Inc. Premium Tactile Systems.</p>
    </footer>
  </div>
</template>
