<style>
    [x-cloak] { display: none !important; }
    details summary::-webkit-details-marker { display: none; }
    details[open] > summary .details-chevron { transform: rotate(180deg); }

    /* Every animation only describes its START state, so if animation is off or
       fails the page simply shows the final state. */
    .fv-rise { animation: fv-rise .55s cubic-bezier(.2,.7,.2,1) backwards; animation-delay: calc(var(--i, 0) * 70ms); }
    @keyframes fv-rise { from { opacity: 0; transform: translateY(10px); } }

    .fv-draw { stroke-dasharray: 1; animation: fv-draw 1.1s cubic-bezier(.3,.6,.2,1) backwards; animation-delay: calc(var(--i, 0) * 120ms); }
    @keyframes fv-draw { from { stroke-dashoffset: 1; } }

    .fv-fade { animation: fv-fade .9s ease-out backwards; animation-delay: calc(.35s + var(--i, 0) * 80ms); }
    @keyframes fv-fade { from { opacity: 0; } }

    .fv-grow-y { transform-box: fill-box; transform-origin: 50% 100%; animation: fv-grow-y .7s cubic-bezier(.2,.8,.2,1) backwards; animation-delay: calc(var(--i, 0) * 45ms); }
    .fv-grow-yn { transform-box: fill-box; transform-origin: 50% 0%; animation: fv-grow-y .7s cubic-bezier(.2,.8,.2,1) backwards; animation-delay: calc(var(--i, 0) * 45ms); }
    @keyframes fv-grow-y { from { transform: scaleY(0); } }

    .fv-grow-x { transform-origin: 0 50%; animation: fv-grow-x .8s cubic-bezier(.2,.8,.2,1) backwards; animation-delay: calc(var(--i, 0) * 90ms + .15s); }
    @keyframes fv-grow-x { from { transform: scaleX(0); } }

    .fv-seg { animation: fv-seg 1s cubic-bezier(.3,.6,.2,1) backwards; animation-delay: calc(var(--i, 0) * 110ms); }
    @keyframes fv-seg { from { stroke-dasharray: 0 var(--c); } }

    .fv-card { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .fv-card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px -16px rgb(0 0 0 / .35); }
    .fv-bar { transition: opacity .15s ease, filter .15s ease; }
    .fv-bar:hover { opacity: .82; filter: saturate(1.15); }
    .fv-dot { transition: r .15s ease; }
    .fv-dot:hover { r: 6; }

    .fv-tab { position: relative; transition: color .15s ease; }
    .fv-tab::after { content: ""; position: absolute; left: .75rem; right: .75rem; bottom: -1px; height: 2px; border-radius: 2px; background: var(--primary); transform: scaleX(0); transition: transform .25s cubic-bezier(.2,.8,.2,1); }
    .fv-tab[aria-selected="true"] { color: var(--foreground); }
    .fv-tab[aria-selected="true"]::after { transform: scaleX(1); }

    @media (prefers-reduced-motion: reduce) {
        .fv-rise, .fv-draw, .fv-fade, .fv-grow-y, .fv-grow-yn, .fv-grow-x, .fv-seg { animation: none !important; }
        .fv-card, .fv-bar, .fv-dot, .fv-tab, .fv-tab::after { transition: none !important; }
        .fv-card:hover { transform: none; }
    }
</style>
