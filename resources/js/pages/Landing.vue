<script>
export default {
  layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, Activity, CheckCircle2, Shield, Zap } from 'lucide-vue-next'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import AppLogo from '@/components/ui/AppLogo.vue'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
  <Head title="Welcome to TaskFlow" />
  
  <div class="min-h-screen bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative overflow-hidden">
     <!-- Ambient Background Orbs -->
    <div class="orb-amber top-[-15%] left-[-10%] opacity-40"></div>
    <div class="orb-cyan bottom-[-15%] right-[-10%] opacity-30"></div>

    <main class="container mx-auto px-4 py-8 sm:py-16 sm:px-6 lg:px-8 relative z-10 max-w-7xl">
      <!-- Top Navigation -->
      <nav 
        class="flex items-center justify-between mb-12 sm:mb-16"
        v-motion
        :initial="{ opacity: 0, y: -20 }"
        :enter="{ opacity: 1, y: 0, transition: { duration: 600, type: 'spring', bounce: 0.2 } }"
      >
        <div class="flex items-center gap-3">
            <AppLogo class="h-10 w-10 text-foreground drop-shadow-md transition-transform duration-300 hover:scale-105" />
            <span class="font-display font-semibold text-2xl tracking-tight">TaskFlow<span class="text-primary italic">.</span></span>
        </div>
        <div class="flex items-center gap-3 sm:gap-6">
            <ThemeToggle />
            <Link v-if="$page.props.auth?.user" href="/dashboard" class="text-sm font-medium hover:text-primary transition-colors">
                Dashboard
            </Link>
            <template v-else>
                <Link href="/login" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors hidden sm:block delay-100 ease-out">
                    Log in
                </Link>
                <Link href="/register" class="text-sm font-medium bg-foreground text-background px-5 py-2.5 rounded-full hover:bg-primary hover:text-primary-foreground transition-all duration-300 shadow-[0_0_20px_-5px_hsl(var(--foreground)/0.3)] hover:scale-105">
                    Get Started
                </Link>
            </template>
        </div>
      </nav>

      <!-- Bento Grid Container -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-5 sm:gap-6 auto-rows-[minmax(200px,auto)]">
        
        <!-- Hero Cell (Spans 8 cols on desktop) -->
        <div 
          class="md:col-span-8 md:row-span-2 glass-elevated rounded-4xl p-8 sm:p-12 lg:p-14 relative overflow-hidden gradient-border card-hover"
          v-motion
          :initial="{ opacity: 0, x: -30 }"
          :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 100, type: 'spring' } }"
        >
          <!-- Decorative Right Gradient -->
          <div class="absolute right-0 top-0 w-1/2 h-full bg-linear-to-l from-primary/5 via-primary/5 to-transparent pointer-events-none"></div>
          
          <div class="section-badge section-badge-amber mb-8 w-fit">
             <span class="text-xs font-semibold tracking-widest uppercase text-amber-600 dark:text-amber-500">Tactile Productivity</span>
          </div>

          <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl mb-6 leading-[1.05] tracking-tight">
            Master Your Work.<br />
            <span class="text-gradient font-display italic pr-2">Without the Noise.</span>
          </h1>

          <p class="text-muted-foreground max-w-xl text-lg sm:text-xl md:text-2xl mb-12 font-sans font-light leading-relaxed">
            An artisanal approach to project management. Designed with deep Obsidian aesthetics and premium tactile feedback to keep you in flow.
          </p>

          <div class="flex flex-wrap items-center gap-4">
            <Link href="/register" class="inline-flex items-center justify-center rounded-full bg-primary text-primary-foreground px-8 py-3.5 font-medium hover:bg-primary/90 transition-all glow-amber group">
               Start Building
               <ArrowRight class="ml-2 w-5 h-5 group-hover:translate-x-1.5 transition-transform" />
            </Link>
            <Link href="/login" class="inline-flex items-center justify-center rounded-full border border-border bg-card/50 backdrop-blur-md px-8 py-3.5 font-medium hover:bg-surface transition-all">
               See Workspace
            </Link>
          </div>
        </div>

        <!-- Metric Cell 1 (Spans 4 cols) -->
        <div 
          class="md:col-span-4 glass rounded-4xl p-6 sm:p-8 flex flex-col justify-between gradient-border card-hover relative overflow-hidden group"
          v-motion
          :initial="{ opacity: 0, y: 30 }"
          :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 200, type: 'spring' } }"
        >
           <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2 group-hover:opacity-100 opacity-50 transition-opacity"></div>
           
           <div class="flex items-center justify-between relative z-10">
              <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center shadow-inner">
                 <Activity class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
              </div>
              <span class="text-xs font-mono font-semibold text-cyan-700 dark:text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 px-3 py-1.5 rounded-lg shadow-sm">
                 +24% Velocity
              </span>
           </div>
           
           <div class="mt-8 relative z-10">
              <div class="text-5xl sm:text-6xl font-display mb-2 text-foreground">1,204</div>
              <div class="text-sm sm:text-base text-muted-foreground font-medium">Tasks Completed This Sprint</div>
           </div>
           
           <div class="w-full h-2 bg-surface border border-border/50 rounded-full mt-6 overflow-hidden relative z-10">
               <div class="h-full bg-linear-to-r from-cyan-600 via-cyan-400 to-cyan-300 w-[78%] rounded-full shimmer"></div>
           </div>
        </div>

        <!-- Feature / Abstract UI Cell (Spans 4 cols) -->
        <div 
          class="md:col-span-4 glass-overlay rounded-4xl p-6 sm:p-8 relative overflow-hidden gradient-border card-hover group"
          v-motion
          :initial="{ opacity: 0, y: 30 }"
          :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 300, type: 'spring' } }"
        >
            <h3 class="font-display text-2xl sm:text-3xl mb-2 relative z-10 text-foreground">Fluid Kanban</h3>
            <p class="text-sm sm:text-base text-muted-foreground relative z-10 mb-8 max-w-50">Drag and drop with atomic state commitment.</p>
            
            <!-- Abstract Kanban UI -->
            <div class="flex gap-3 relative z-10 mt-auto">
                <!-- Column 1 -->
                <div class="flex-1 flex flex-col gap-3">
                    <div class="w-8 h-1.5 rounded-full bg-border/80"></div>
                    <div class="w-full h-16 rounded-xl bg-surface border border-border/60 group-hover:-translate-y-2 transition-transform duration-500 delay-75 shadow-sm"></div>
                    <div class="w-full h-24 rounded-xl bg-surface border border-border/60 group-hover:-translate-y-1 transition-transform duration-500 delay-150 shadow-sm relative overflow-hidden">
                       <div class="absolute top-2 left-2 w-4 h-4 rounded-full bg-primary/20"></div>
                       <div class="absolute bottom-2 right-2 w-1/2 h-1.5 rounded-full bg-border"></div>
                    </div>
                </div>
                <!-- Column 2 -->
                <div class="flex-1 flex flex-col gap-3">
                    <div class="w-8 h-1.5 rounded-full bg-border/80"></div>
                    <div class="w-full h-20 rounded-xl bg-surface border border-border/60 shadow-sm relative overflow-hidden">
                       <div class="absolute top-2 left-2 w-full flex gap-1 px-2">
                           <div class="w-3 h-3 rounded-full bg-cyan-500/50"></div>
                       </div>
                    </div>
                </div>
            </div>
            
            <!-- Ambient bottom glow -->
            <div class="absolute -bottom-16 -right-16 w-56 h-56 bg-primary/10 rounded-full blur-[3rem] group-hover:bg-primary/20 transition-colors duration-1000"></div>
        </div>

        <!-- Deep Integration Cell (Spans 8 cols) -->
        <div 
          class="md:col-span-8 glass rounded-4xl p-6 sm:p-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gradient-border card-hover relative overflow-hidden"
          v-motion
          :initial="{ opacity: 0, x: 30 }"
          :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 400, type: 'spring' } }"
        >
             <div class="absolute top-0 left-0 w-full h-px bg-linear-to-r from-transparent via-emerald-500/20 to-transparent"></div>
             
             <div class="relative z-10 max-w-lg mb-8 sm:mb-0">
                 <div class="section-badge section-badge-emerald mb-5">
                     <Shield class="w-4 h-4 text-emerald-600 dark:text-emerald-500" />
                     <span class="text-xs font-semibold tracking-widest uppercase text-emerald-600 dark:text-emerald-500">Enterprise Ready</span>
                 </div>
                 <h3 class="font-display text-3xl sm:text-4xl mb-4 text-foreground">Secure by design.</h3>
                 <p class="text-muted-foreground text-base sm:text-lg leading-relaxed">
                     Built on a robust Laravel backend with UUID schema, polymorphic relations, and strict multi-tenant data isolation. Every request is verified.
                 </p>
             </div>
             
             <!-- Decorative elements right side -->
             <div class="relative z-10 flex flex-col space-y-3 w-full sm:w-auto">
                <div 
                   class="flex items-center justify-between sm:justify-start gap-4 bg-surface-elevated pl-5 pr-4 py-3 rounded-2xl border border-border/80 shadow-md transform hover:scale-[1.02] transition-transform" 
                   v-motion 
                   :initial="{ x: 20, opacity: 0 }" 
                   :enter="{ x: 0, opacity: 1, transition: { delay: 600, type: 'spring' } }"
                >
                   <div class="flex items-center gap-3">
                       <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                       <span class="text-xs sm:text-sm font-mono font-medium text-emerald-700 dark:text-emerald-100">API_LATENCY</span>
                   </div>
                   <div class="flex items-center gap-2 bg-emerald-500/10 px-2 py-1 rounded">
                       <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400">12ms</span>
                       <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-500" />
                   </div>
                </div>
                
                <div 
                   class="flex items-center justify-between sm:justify-start gap-4 bg-surface-elevated pl-5 pr-4 py-3 rounded-2xl border border-border/80 shadow-md transform hover:scale-[1.02] transition-transform" 
                   v-motion 
                   :initial="{ x: 20, opacity: 0 }" 
                   :enter="{ x: 0, opacity: 1, transition: { delay: 700, type: 'spring' } }"
                >
                   <div class="flex items-center gap-3">
                       <div class="w-2.5 h-2.5 rounded-full bg-cyan-500 animate-pulse"></div>
                       <span class="text-xs sm:text-sm font-mono font-medium text-cyan-700 dark:text-cyan-100">CACHE_LAYER</span>
                   </div>
                   <div class="flex items-center gap-2 bg-cyan-500/10 px-2 py-1 rounded">
                       <span class="text-xs font-mono text-cyan-600 dark:text-cyan-400">SYNCED</span>
                       <CheckCircle2 class="w-4 h-4 text-cyan-600 dark:text-cyan-500" />
                   </div>
                </div>
             </div>
        </div>
        
      </div>
      
      <!-- Footer Details -->
      <footer 
         class="mt-20 pt-8 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-6 text-sm text-muted-foreground font-mono" 
         v-motion 
         :initial="{ opacity: 0 }" 
         :enter="{ opacity: 1, transition: { delay: 800, duration: 1000 } }"
      >
         <p>© 2026 TaskFlow Inc. <span class="hidden sm:inline">Crafted with Lunar Obsidian.</span></p>
         <div class="flex gap-6">
            <Link href="/terms" class="hover:text-foreground transition-colors">Terms</Link>
            <Link href="/privacy" class="hover:text-foreground transition-colors">Privacy</Link>
            <Link href="/api" class="hover:text-foreground transition-colors">API</Link>
            <Link href="/contact" class="hover:text-amber-500 transition-colors">Contact</Link>
         </div>
      </footer>
    </main>
  </div>
</template>

<style scoped>
/* Scoped overrides if necessary */
</style>
