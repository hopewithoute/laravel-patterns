<script>
export default {
  layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
  <Head title="Privacy Policy - TaskFlow" />
  
  <div class="min-h-screen bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative overflow-hidden flex flex-col">
    <div class="orb-cyan bottom-[-15%] right-[-10%] opacity-20"></div>

    <header class="container mx-auto px-6 py-10 relative z-10 max-w-4xl">
       <Link href="/" class="group inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-12">
          <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          Back to Home
       </Link>
       
       <div class="flex items-center gap-4 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center glow-cyan-sm">
             <ShieldCheck class="w-6 h-6 text-cyan-400" />
          </div>
          <h1 class="font-display text-4xl sm:text-5xl tracking-tight">Privacy <span class="italic text-gradient-cyan">Policy</span></h1>
       </div>
    </header>

    <main class="container mx-auto px-6 py-12 relative z-10 max-w-4xl flex-1">
      <div class="glass-elevated rounded-3xl p-8 sm:p-12 border border-border/50 relative overflow-hidden min-h-[400px]">
         <div class="absolute top-0 right-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
         
         <div class="prose prose-invert max-w-none space-y-8">
            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">1. Data Collection</h2>
               <p class="text-muted-foreground leading-relaxed">
                  We collect only the essential information needed to provide our tactile workspace experience. Your data is encrypted and stored using industry-standard protocols.
               </p>
            </section>

            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">2. Information Usage</h2>
               <p class="text-muted-foreground leading-relaxed">
                  TaskFlow uses your data to synchronize your tasks and team collaborations across devices. We never sell your personal information to third parties.
               </p>
            </section>
            
            <section>
               <h2 class="font-display text-2xl text-foreground mb-4">3. Security</h2>
               <p class="text-muted-foreground leading-relaxed">
                  We implement multi-tenant isolation at the database level to ensure your workspace remains private and secure.
               </p>
            </section>

            <div class="pt-12 border-t border-border/40 mt-12">
               <p class="text-xs font-mono text-muted-foreground">LAST UPDATED: APRIL 14, 2026</p>
            </div>
         </div>
      </div>
    </main>

    <footer class="container mx-auto px-6 py-10 text-center text-xs text-muted-foreground font-mono">
       <p>© 2026 TaskFlow Inc. Secure Tactile Systems.</p>
    </footer>
  </div>
</template>
