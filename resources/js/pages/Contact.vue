<script>
export default {
  layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Mail, MessageSquare, MapPin } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
  <Head title="Contact Us - TaskFlow" />
  
  <div class="min-h-screen bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative overflow-hidden flex flex-col">
    <div class="orb-cyan top-[-10%] right-[-10%] opacity-20"></div>

    <header class="container mx-auto px-6 py-10 relative z-10 max-w-5xl">
       <Link href="/" class="group inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-12">
          <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          Back to Home
       </Link>
       
       <div class="flex items-center gap-4 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center glow-cyan-sm">
             <MessageSquare class="w-6 h-6 text-cyan-400" />
          </div>
          <h1 class="font-display text-4xl sm:text-5xl tracking-tight">Get in <span class="italic text-gradient-cyan">Touch</span></h1>
       </div>
       <p class="text-muted-foreground text-lg font-light max-w-2xl">Have questions about our tactile workspace? Our team is here to help you build better.</p>
    </header>

    <main class="container mx-auto px-6 py-12 relative z-10 max-w-5xl flex-1 flex flex-col items-center">
      <div class="grid md:grid-cols-3 gap-8 w-full mb-16">
          <div class="glass-elevated p-8 rounded-3xl border border-border/50 text-center card-hover">
             <div class="w-12 h-12 rounded-xl bg-surface mx-auto mb-6 flex items-center justify-center border border-border/60">
                <Mail class="w-6 h-6 text-primary" />
             </div>
             <h3 class="font-display text-xl mb-2">Email</h3>
             <p class="text-muted-foreground text-sm mb-4">Response within 24 hours.</p>
             <a href="mailto:hello@taskflow.dev" class="text-primary hover:underline font-mono">hello@taskflow.dev</a>
          </div>

          <div class="glass-elevated p-8 rounded-3xl border border-border/50 text-center card-hover lg:scale-105">
             <div class="w-12 h-12 rounded-xl bg-surface mx-auto mb-6 flex items-center justify-center border border-border/60">
                <MessageSquare class="w-6 h-6 text-cyan-400" />
             </div>
             <h3 class="font-display text-xl mb-2">Support Chat</h3>
             <p class="text-muted-foreground text-sm mb-4">Available for Enterprise.</p>
             <button class="bg-foreground text-background px-6 py-2 rounded-full text-sm font-semibold hover:bg-primary transition-colors">Start Chat</button>
          </div>

          <div class="glass-elevated p-8 rounded-3xl border border-border/50 text-center card-hover">
             <div class="w-12 h-12 rounded-xl bg-surface mx-auto mb-6 flex items-center justify-center border border-border/60">
                <MapPin class="w-6 h-6 text-emerald-400" />
             </div>
             <h3 class="font-display text-xl mb-2">Studio</h3>
             <p class="text-muted-foreground text-sm mb-4">Digital-first operation.</p>
             <span class="text-emerald-400 font-mono text-sm uppercase">Global Remote</span>
          </div>
      </div>

      <div class="w-full max-w-2xl glass p-8 sm:p-12 rounded-[2rem] border border-border/40">
          <h2 class="font-display text-3xl mb-8 text-center italic text-gradient">Send a message</h2>
          <form class="space-y-6" @submit.prevent>
             <div class="grid sm:grid-cols-2 gap-6">
                <div class="space-y-2">
                   <label class="text-xs font-mono text-muted-foreground uppercase tracking-wider ml-1">Name</label>
                   <input type="text" placeholder="John Doe" class="w-full bg-surface-elevated border border-border/60 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
                <div class="space-y-2">
                   <label class="text-xs font-mono text-muted-foreground uppercase tracking-wider ml-1">Email</label>
                   <input type="email" placeholder="john@example.com" class="w-full bg-surface-elevated border border-border/60 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
             </div>
             <div class="space-y-2">
                <label class="text-xs font-mono text-muted-foreground uppercase tracking-wider ml-1">Message</label>
                <textarea rows="4" placeholder="How can we help?" class="w-full bg-surface-elevated border border-border/60 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all resize-none"></textarea>
             </div>
             <button class="w-full bg-primary text-primary-foreground py-4 rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Send Inquiry
             </button>
          </form>
      </div>
    </main>

    <footer class="container mx-auto px-6 py-10 text-center text-xs text-muted-foreground font-mono">
       <p>© 2026 TaskFlow Inc. Global Tactile Communications.</p>
    </footer>
  </div>
</template>
