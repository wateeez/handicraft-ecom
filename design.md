 --color-background: var(--background);
  --color-foreground: var(--foreground);
  --font-sans: var(--font-geist-sans);
  --font-mono: var(--font-geist-mono);
  --color-sidebar-ring: var(--sidebar-ring);
  --color-sidebar-border: var(--sidebar-border);
  --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
  --color-sidebar-accent: var(--sidebar-accent);
  --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
  --color-sidebar-primary: var(--sidebar-primary);
  --color-sidebar-foreground: var(--sidebar-foreground);
  --color-sidebar: var(--sidebar);
  --color-chart-5: var(--chart-5);
  --color-chart-4: var(--chart-4);
  --color-chart-3: var(--chart-3);
  --color-chart-2: var(--chart-2);
  --color-chart-1: var(--chart-1);
  --color-ring: var(--ring);
  --color-input: var(--input);
  --color-border: var(--border);
  --color-destructive: var(--destructive);
  --color-accent-foreground: var(--accent-foreground);
  --color-accent: var(--accent);
  --color-muted-foreground: var(--muted-foreground);
  --color-muted: var(--muted);
  --color-secondary-foreground: var(--secondary-foreground);
  --color-secondary: var(--secondary);
  --color-primary-foreground: var(--primary-foreground);
  --color-primary: var(--primary);
  --color-popover-foreground: var(--popover-foreground);
  --color-popover: var(--popover);
  --color-card-foreground: var(--card-foreground);
  --color-card: var(--card);
  --radius-sm: calc(var(--radius) - 4px);
  --radius-md: calc(var(--radius) - 2px);
  --radius-lg: var(--radius);
  --radius-xl: calc(var(--radius) + 4px);
}

/* Earthy, artisanal palette — terracotta, saffron, deep forest, cream */
:root {
  --radius: 0.625rem;
  --background: oklch(0.985 0.008 75);          /* warm cream */
  --foreground: oklch(0.22 0.02 40);            /* deep umber */
  --card: oklch(1 0.004 75);
  --card-foreground: oklch(0.22 0.02 40);
  --popover: oklch(1 0.004 75);
  --popover-foreground: oklch(0.22 0.02 40);
  --primary: oklch(0.45 0.13 32);               /* terracotta */
  --primary-foreground: oklch(0.985 0.008 75);
  --secondary: oklch(0.95 0.015 80);
  --secondary-foreground: oklch(0.30 0.02 40);
  --muted: oklch(0.95 0.012 75);
  --muted-foreground: oklch(0.50 0.02 50);
  --accent: oklch(0.90 0.04 80);                /* saffron tint */
  --accent-foreground: oklch(0.30 0.05 40);
  --destructive: oklch(0.55 0.22 27);
  --border: oklch(0.90 0.015 70);
  --input: oklch(0.92 0.015 70);
  --ring: oklch(0.55 0.10 35);
  --chart-1: oklch(0.55 0.13 32);   /* terracotta */
  --chart-2: oklch(0.55 0.10 140);  /* forest */
  --chart-3: oklch(0.70 0.13 75);   /* saffron */
  --chart-4: oklch(0.50 0.08 220);  /* teal */
  --chart-5: oklch(0.45 0.15 12);   /* ruby */
  --sidebar: oklch(0.985 0.008 75);
  --sidebar-foreground: oklch(0.22 0.02 40);
  --sidebar-primary: oklch(0.45 0.13 32);
  --sidebar-primary-foreground: oklch(0.985 0.008 75);
  --sidebar-accent: oklch(0.92 0.02 75);
  --sidebar-accent-foreground: oklch(0.30 0.02 40);
  --sidebar-border: oklch(0.90 0.015 70);
  --sidebar-ring: oklch(0.55 0.10 35);
}

.dark {
  --background: oklch(0.18 0.015 40);
  --foreground: oklch(0.96 0.01 75);
  --card: oklch(0.22 0.02 40);
  --card-foreground: oklch(0.96 0.01 75);
  --popover: oklch(0.22 0.02 40);
  --popover-foreground: oklch(0.96 0.01 75);
  --primary: oklch(0.70 0.14 35);                /* light terracotta */
  --primary-foreground: oklch(0.18 0.015 40);
  --secondary: oklch(0.28 0.02 40);
  --secondary-foreground: oklch(0.96 0.01 75);
  --muted: oklch(0.28 0.02 40);
  --muted-foreground: oklch(0.72 0.02 60);
  --accent: oklch(0.35 0.05 40);
  --accent-foreground: oklch(0.96 0.01 75);
  --destructive: oklch(0.70 0.19 22);
  --border: oklch(1 0 0 / 10%);
  --input: oklch(1 0 0 / 15%);
  --ring: oklch(0.60 0.10 35);
  --chart-1: oklch(0.65 0.15 35);
  --chart-2: oklch(0.65 0.12 140);
  --chart-3: oklch(0.75 0.15 75);
  --chart-4: oklch(0.60 0.10 220);
  --chart-5: oklch(0.55 0.17 12);
  --sidebar: oklch(0.22 0.02 40);
  --sidebar-foreground: oklch(0.96 0.01 75);
  --sidebar-primary: oklch(0.70 0.14 35);
  --sidebar-primary-foreground: oklch(0.18 0.015 40);
  --sidebar-accent: oklch(0.28 0.02 40);
  --sidebar-accent-foreground: oklch(0.96 0.01 75);
  --sidebar-border: oklch(1 0 0 / 10%);
  --sidebar-ring: oklch(0.60 0.10 35);
}

@layer base {
  * {
    @apply border-border outline-ring/50;
  }
  body {
    @apply bg-background text-foreground;
    font-feature-settings: "ss01", "cv11";
  }
}

/* Subtle paper-grain texture using a layered gradient */
.bg-paper {
  background-image:
    radial-gradient(at 0% 0%, oklch(0.95 0.03 70 / 0.5) 0px, transparent 50%),
    radial-gradient(at 100% 0%, oklch(0.94 0.04 85 / 0.4) 0px, transparent 50%),
    radial-gradient(at 50% 100%, oklch(0.93 0.02 60 / 0.3) 0px, transparent 50%);
}
.dark .bg-paper {
  background-image:
    radial-gradient(at 0% 0%, oklch(0.30 0.04 40 / 0.4) 0px, transparent 50%),
    radial-gradient(at 100% 0%, oklch(0.28 0.05 60 / 0.3) 0px, transparent 50%),
    radial-gradient(at 50% 100%, oklch(0.25 0.02 30 / 0.3) 0px, transparent 50%);
}

/* Custom scrollbar for long lists */
.custom-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb {
  background: oklch(0.80 0.02 60 / 0.6);
  border-radius: 9999px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: oklch(0.70 0.05 40 / 0.8);
}

/* Smooth gradient text */
.text-gradient-warm {
  background: linear-gradient(135deg, oklch(0.55 0.14 32) 0%, oklch(0.65 0.13 75) 50%, oklch(0.50 0.10 140) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.dark .text-gradient-warm {
  background: linear-gradient(135deg, oklch(0.70 0.15 35) 0%, oklch(0.75 0.15 75) 50%, oklch(0.65 0.12 140) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Decorative divider */
.divider-ornament {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: oklch(0.55 0.10 35 / 0.6);
}
.divider-ornament::before,
.divider-ornament::after {
  content: "";
  flex: 1;
  height: 1px;
  background: linear-gradient(to right, transparent, oklch(0.55 0.10 35 / 0.4), transparent);
}

/* Card hover lift */
.card-lift {
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease, border-color 0.35s ease;
}
.card-lift:hover {
  transform: translateY(-4px);
}

/* Shimmer sweep effect on hover for product cards */
@keyframes shimmer-sweep {
  0% { transform: translateX(-100%) skewX(-12deg); }
  100% { transform: translateX(200%) skewX(-12deg); }
}
.shimmer-sweep {
  position: relative;
  overflow: hidden;
}
.shimmer-sweep::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  transform: translateX(-100%) skewX(-12deg);
  pointer-events: none;
}
.shimmer-sweep:hover::after {
  animation: shimmer-sweep 0.8s ease;
}

/* Fade-in-up animation utility */
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
.shimmer {
  background: linear-gradient(90deg, oklch(0.90 0.02 70 / 0.4) 25%, oklch(0.95 0.02 75 / 0.7) 50%, oklch(0.90 0.02 70 / 0.4) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

/* Print-friendly invoice */
@media print {
  .no-print { display: none !important; }
  .print-only { display: block !important; }
  body { background: white; color: black; }
}
.print-only { display: none; }

/* Scale-in animation for modals */
@keyframes scale-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-scale-in {
  animation: scale-in 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Slide-in from right */
@keyframes slide-in-right {
  from { opacity: 0; transform: translateX(20px); }
  to { opacity: 1; transform: translateX(0); }
}
.animate-slide-in-right {
  animation: slide-in-right 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Pulse glow for badges */
@keyframes pulse-glow {
  0%, 100% { box-shadow: 0 0 0 0 oklch(0.55 0.13 32 / 0.4); }
  50% { box-shadow: 0 0 0 6px oklch(0.55 0.13 32 / 0); }
}
.animate-pulse-glow {
  animation: pulse-glow 2s ease-in-out infinite;
}

/* Smooth color transitions for dark mode */
body, .bg-paper, .text-gradient-warm, header, footer, card {
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

/* Improved focus visible */
*:focus-visible {
  outline: 2px solid var(--ring);
  outline-offset: 2px;
  border-radius: 2px;
}

/* Selection color */
::selection {
  background: oklch(0.55 0.13 32 / 0.25);
  color: var(--foreground);
}
.dark ::selection {
  background: oklch(0.70 0.15 35 / 0.3);
}

/* Stagger animation for list items */
@keyframes stagger-in {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.stagger-item {
  animation: stagger-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Floating bar animation */
@keyframes float-up {
  from { opacity: 0; transform: translateY(100%); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-float-up {
  animation: float-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Glow ring for active states */
.glow-ring {
  box-shadow: 0 0 0 2px var(--primary), 0 0 12px oklch(0.55 0.13 32 / 0.3);
}

/* Smooth skeleton loading */
.skeleton-pulse {
  background: linear-gradient(90deg, var(--muted) 25%, var(--accent) 50%, var(--muted) 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

/* Gradient border on hover */
.gradient-border-hover {
  position: relative;
  background: var(--card);
  border: 1px solid var(--border);
  transition: all 0.3s ease;
}
.gradient-border-hover::before {
  content: '';
  position: absolute;
  inset: -1px;
  border-radius: inherit;
  padding: 1px;
  background: linear-gradient(135deg, oklch(0.55 0.13 32), oklch(0.65 0.13 75), oklch(0.50 0.10 140));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.gradient-border-hover:hover::before {
  opacity: 1;
}

/* Text balance for headings */
h1, h2, h3 {
  text-wrap: balance;
}

/* Smooth image loading */
img {
  background-color: var(--muted);
}

/* Badge pulse for new/featured items */
@keyframes badge-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}
.badge-pulse {
  animation: badge-pulse 2s ease-in-out infinite;
}

/* Subtle dot pattern background */
.dot-pattern {
  background-image: radial-gradient(circle, oklch(0.55 0.10 35 / 0.08) 1px, transparent 1px);
  background-size: 20px 20px;
}
.dark .dot-pattern {
  background-image: radial-gradient(circle, oklch(0.70 0.15 35 / 0.06) 1px, transparent 1px);
}

/* Smooth number transition */
@keyframes number-pop {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
.number-pop {
  animation: number-pop 0.3s ease;
}

/* Notification card slide-in */
@keyframes slide-down {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-slide-down {
  animation: slide-down 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Pulse for notification badges */
@keyframes attention-pulse {
  0%, 100% { box-shadow: 0 0 0 0 currentColor; opacity: 1; }
  50% { box-shadow: 0 0 0 4px transparent; opacity: 0.8; }
}
.attention-pulse {
  animation: attention-pulse 2s ease-in-out infinite;
}

/* Glassmorphism effect */
.glass {
  background: oklch(1 0 0 / 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid oklch(1 0 0 / 0.2);
}
.dark .glass {
  background: oklch(0.22 0.02 40 / 0.7);
  border-color: oklch(1 0 0 / 0.08);
}

/* Smooth tab transitions */
[data-state="active"] {
  transition: all 0.2s ease;
}
