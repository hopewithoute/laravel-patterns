<script>
export default {
  layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Code2, Terminal } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
  <Head title="API Documentation - TaskFlow" />
  
  <div class="min-h-screen bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative overflow-hidden flex flex-col">
    <div class="orb-amber top-[20%] right-[-5%] opacity-15"></div>

    <header class="container mx-auto px-6 py-10 relative z-10 max-w-5xl">
       <Link href="/" class="group inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-12">
          <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          Back to Home
       </Link>
       
       <div class="flex items-center gap-4 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center glow-amber-sm">
             <Code2 class="w-6 h-6 text-primary" />
          </div>
          <h1 class="font-display text-4xl sm:text-5xl tracking-tight">Developer <span class="italic text-gradient">API</span></h1>
       </div>
       <p class="text-muted-foreground text-lg font-light max-w-2xl">Integrate TaskFlow’s tactile engine into your own workflows with our high-performance REST API.</p>
    </header>

    <main class="container mx-auto px-6 py-12 relative z-10 max-w-5xl flex-1 grid md:grid-cols-3 gap-8 text-foreground">
      <!-- Sidebar Info -->
      <div class="md:col-span-1 space-y-6">
         <div class="glass p-6 rounded-2xl border border-border/40">
            <h3 class="font-display text-xl mb-3">Authentication</h3>
            <p class="text-sm text-muted-foreground">Bearer token required for all requests. Tokens can be generated in your dashboard settings.</p>
         </div>
         <div class="glass p-6 rounded-2xl border border-border/40">
            <h3 class="font-display text-xl mb-3">Rate Limits</h3>
            <p class="text-sm text-muted-foreground">1,000 requests per minute per workspace. Enterprise plans have higher limits.</p>
         </div>
      </div>

      <!-- Main Code Area -->
      <div class="md:col-span-2">
         <div class="glass-elevated rounded-3xl border border-border/50 overflow-hidden relative group">
            <div class="flex items-center justify-between px-6 py-4 bg-surface/50 border-b border-border/40">
               <div class="flex items-center gap-2">
                  <Terminal class="w-4 h-4 text-muted-foreground" />
                  <span class="text-xs font-mono text-muted-foreground uppercase tracking-widest">GET /v1/tasks</span>
               </div>
               <div class="flex gap-1.5">
                  <div class="w-2 h-2 rounded-full bg-rose-500/50"></div>
                  <div class="w-2 h-2 rounded-full bg-amber-500/50"></div>
                  <div class="w-2 h-2 rounded-full bg-emerald-500/50"></div>
               </div>
            </div>
            <div class="p-8 font-mono text-sm sm:text-base leading-relaxed overflow-x-auto whitespace-pre">
<span class="text-primary italic">curl</span> -X GET <span class="text-cyan-400">"https://api.taskflow.com/v1/tasks"</span> \
  -H <span class="text-cyan-400">"Authorization: Bearer YOUR_TOKEN"</span>

<span class="text-muted-foreground mt-4 block">// Response</span>
{
  <span class="text-amber-300">"data"</span>: [
    {
      <span class="text-amber-300">"id"</span>: <span class="text-cyan-300">"9b1deb4d-3b7d-4bad-9bdd"</span>,
      <span class="text-amber-300">"title"</span>: <span class="text-cyan-300">"Refactor Landing Layout"</span>,
      <span class="text-amber-300">"status"</span>: <span class="text-cyan-300">"in_progress"</span>
    }
  ]
}</div>
         </div>
         <p class="mt-6 text-center text-sm text-muted-foreground">Detailed documentation coming soon. Please reach out for early access.</p>
      </div>
    </main>

    <footer class="container mx-auto px-6 py-10 text-center text-xs text-muted-foreground font-mono">
       <p>© 2026 TaskFlow Inc. Programmable Tactile Systems.</p>
    </footer>
  </div>
</template>
