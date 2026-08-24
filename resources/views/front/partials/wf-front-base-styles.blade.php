<style>
    :root {
        --wf-navy: #0b1f3a;
        --wf-navy-deep: #071526;
        --wf-gold: #c9a227;
        --wf-gold-soft: #e8d48b;
        --wf-cream: #f7f4ee;
        --wf-ink: #1a2332;
        --wf-muted: #5c6675;
        --wf-line: #e6e2d9;
        --wf-white: #ffffff;
    }

    .wf-page {
        font-family: 'Poppins', system-ui, sans-serif;
        color: var(--wf-ink);
        background: var(--wf-white);
    }

    .wf-page h1,
    .wf-page h2,
    .wf-page h3 {
        letter-spacing: -0.02em;
    }

    .wf-nav {
        position: sticky;
        top: 0;
        z-index: 50;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--wf-line);
    }

    .wf-btn-navy {
        background: var(--wf-navy);
        color: #fff;
        border-radius: 999px;
        font-weight: 700;
        transition: background .2s ease, transform .2s ease;
    }

    .wf-btn-navy:hover {
        background: var(--wf-navy-deep);
        transform: translateY(-1px);
    }

    .wf-btn-ghost {
        border: 1.5px solid var(--wf-navy);
        color: var(--wf-navy);
        border-radius: 999px;
        font-weight: 700;
        background: #fff;
        transition: background .2s ease;
    }

    .wf-btn-ghost:hover {
        background: var(--wf-cream);
    }

    .wf-btn-gold {
        background: var(--wf-gold);
        color: var(--wf-navy-deep);
        border-radius: 999px;
        font-weight: 800;
        transition: filter .2s ease, transform .2s ease;
    }

    .wf-btn-gold:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
    }

    .wf-info-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        padding: 1.5rem;
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .wf-info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px -24px rgba(11, 31, 58, 0.35);
    }

    .wf-info-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(201, 162, 39, 0.14);
        color: var(--wf-gold);
        font-size: 1rem;
    }

    .wf-hero {
        position: relative;
        overflow: hidden;
    }

    .wf-hero > .wf-hero-inner {
        position: relative;
        z-index: 1;
    }

    .wf-section-deco {
        position: relative;
        overflow: hidden;
    }

    .wf-section-deco > .wf-section-inner {
        position: relative;
        z-index: 1;
    }

    .wf-deco {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }

    .wf-deco__blob,
    .wf-deco__ring,
    .wf-deco__dot,
    .wf-deco__sq,
    .wf-deco__tri {
        position: absolute;
        display: block;
    }

    .wf-deco__blob {
        border-radius: 40% 60% 55% 45% / 50% 40% 60% 50%;
        filter: blur(2px);
        opacity: 0.55;
        animation: wf-deco-float 9s ease-in-out infinite;
    }

    .wf-deco__blob--a {
        width: 18rem;
        height: 18rem;
        top: -5rem;
        right: -4rem;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.35), rgba(201, 162, 39, 0.05) 70%);
    }

    .wf-deco__blob--b {
        width: 14rem;
        height: 14rem;
        bottom: -4rem;
        left: -3rem;
        background: radial-gradient(circle at 60% 40%, rgba(11, 31, 58, 0.12), rgba(11, 31, 58, 0.02) 70%);
        animation-delay: -3s;
    }

    .wf-deco__ring {
        border-radius: 999px;
        border: 2px solid rgba(201, 162, 39, 0.28);
        animation: wf-deco-spin 22s linear infinite;
    }

    .wf-deco__ring--a {
        width: 9rem;
        height: 9rem;
        top: 18%;
        left: 6%;
        opacity: 0.7;
    }

    .wf-deco__ring--b {
        width: 5.5rem;
        height: 5.5rem;
        bottom: 16%;
        right: 10%;
        border-color: rgba(11, 31, 58, 0.12);
        animation-direction: reverse;
        animation-duration: 16s;
    }

    .wf-deco__dot {
        width: 0.65rem;
        height: 0.65rem;
        border-radius: 999px;
        background: var(--wf-gold);
        opacity: 0.55;
        animation: wf-deco-float 6s ease-in-out infinite;
    }

    .wf-deco__dot--a {
        top: 28%;
        right: 18%;
    }

    .wf-deco__dot--b {
        bottom: 30%;
        left: 22%;
        width: 0.45rem;
        height: 0.45rem;
        background: var(--wf-navy);
        opacity: 0.25;
        animation-delay: -2s;
    }

    .wf-deco__sq {
        width: 2.25rem;
        height: 2.25rem;
        border: 2px solid rgba(201, 162, 39, 0.35);
        border-radius: 0.45rem;
        transform: rotate(18deg);
        animation: wf-deco-float 7s ease-in-out infinite;
    }

    .wf-deco__sq--a {
        top: 22%;
        right: 8%;
    }

    .wf-deco__sq--b {
        bottom: 22%;
        left: 8%;
        width: 1.6rem;
        height: 1.6rem;
        border-color: rgba(11, 31, 58, 0.14);
        animation-delay: -1.5s;
    }

    .wf-deco__tri {
        width: 0;
        height: 0;
        border-left: 0.85rem solid transparent;
        border-right: 0.85rem solid transparent;
        border-bottom: 1.45rem solid rgba(201, 162, 39, 0.28);
        top: 62%;
        right: 22%;
        animation: wf-deco-float-alt 8s ease-in-out infinite;
    }

    .wf-cta-panel {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        background: var(--wf-navy);
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .wf-cta-panel .wf-deco__blob--a {
        opacity: 0.35;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.45), transparent 70%);
    }

    .wf-cta-panel .wf-deco__ring--a {
        border-color: rgba(255, 255, 255, 0.12);
        top: auto;
        bottom: -2rem;
        left: -1rem;
    }

    .wf-cta-panel .wf-deco__ring--b {
        border-color: rgba(201, 162, 39, 0.35);
        top: -1.5rem;
        right: -1rem;
        bottom: auto;
    }

    .wf-cta-panel > *:not(.wf-deco) {
        position: relative;
        z-index: 1;
    }

    @keyframes wf-deco-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    @keyframes wf-deco-float-alt {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(8px) rotate(8deg); }
    }

    @keyframes wf-deco-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @media (max-width: 640px) {
        .wf-deco__blob--a { width: 11rem; height: 11rem; }
        .wf-deco__blob--b { width: 9rem; height: 9rem; }
        .wf-deco__ring--a { width: 5.5rem; height: 5.5rem; left: 2%; }
        .wf-deco__sq--a,
        .wf-deco__tri { opacity: 0.55; }
    }

    [x-cloak] {
        display: none !important;
    }
</style>
