@extends('layouts.app')

@section('title', 'WOFINS — Wedding Organizer Financial Information System')

@push('styles')
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
            --font-display: 'Poppins', system-ui, sans-serif;
            --font-body: 'Poppins', system-ui, sans-serif;
        }

        .wf-page {
            font-family: var(--font-body);
            color: var(--wf-ink);
            background: var(--wf-white);
        }

        .wf-page h1,
        .wf-page h2,
        .wf-page h3,
        .wf-display {
            font-family: var(--font-display);
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
            transition: background .2s ease, color .2s ease;
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

        .wf-btn-outline-light {
            border: 1.5px solid rgba(255, 255, 255, 0.85);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            transition: background .2s ease;
        }

        .wf-btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .wf-check {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.15);
            color: var(--wf-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .wf-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 60% at 90% 20%, rgba(201, 162, 39, 0.12), transparent 55%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
        }

        .wf-hero-visual {
            position: relative;
            min-height: 400px;
            isolation: isolate;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Hero constellation — beda dari cards Empat Langkah */
        .wf-hero-constellation {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            max-width: 480px;
            margin-inline: auto;
            animation: wf-hero-fade 0.85s ease-out both;
        }

        .wf-hero-constellation svg.wf-hero-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .wf-hero-constellation .wf-arc {
            fill: none;
            stroke: rgba(11, 31, 58, 0.12);
            stroke-width: 1.5;
        }

        .wf-hero-constellation .wf-arc-gold {
            stroke: rgba(201, 162, 39, 0.55);
            stroke-dasharray: 12 18;
            animation: wf-dash-orbit 14s linear infinite;
        }

        .wf-hero-constellation .wf-arc-navy {
            stroke: rgba(11, 31, 58, 0.28);
            stroke-dasharray: 8 14;
            animation: wf-dash-orbit 20s linear infinite reverse;
        }

        .wf-hero-constellation .wf-link {
            fill: none;
            stroke: rgba(11, 31, 58, 0.22);
            stroke-width: 1.5;
            stroke-dasharray: 6 8;
            animation: wf-dash-flow 3.5s linear infinite;
        }

        .wf-hero-constellation .wf-link-gold {
            stroke: rgba(201, 162, 39, 0.65);
            stroke-dasharray: 4 10;
            animation: wf-dash-flow 2.8s linear infinite reverse;
        }

        .wf-hero-constellation .wf-wave {
            fill: none;
            stroke: var(--wf-navy);
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 280;
            stroke-dashoffset: 280;
            animation: wf-draw-wave 4.5s ease-in-out infinite;
        }

        .wf-hero-constellation .wf-wave-soft {
            fill: none;
            stroke: rgba(201, 162, 39, 0.45);
            stroke-width: 1.75;
            stroke-linecap: round;
            stroke-dasharray: 260;
            stroke-dashoffset: 260;
            animation: wf-draw-wave 4.5s ease-in-out 0.4s infinite;
        }

        .wf-hero-node {
            position: absolute;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            transform: translate(-50%, -50%);
        }

        .wf-hero-node .n-core {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            background: var(--wf-navy);
            box-shadow: 0 12px 24px -10px rgba(11, 31, 58, 0.45);
            animation: wf-node-breathe 3.8s ease-in-out infinite;
        }

        .wf-hero-node.is-gold .n-core {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            animation-delay: 0.6s;
        }

        .wf-hero-node.is-outline .n-core {
            background: #fff;
            color: var(--wf-navy);
            border: 1.5px solid var(--wf-navy);
            animation-delay: 1.1s;
        }

        .wf-hero-node .n-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--wf-muted);
            white-space: nowrap;
        }

        .wf-hero-node.n-center {
            left: 50%;
            top: 50%;
        }

        .wf-hero-node.n-center .n-core {
            width: 4.25rem;
            height: 4.25rem;
            border-radius: 1.25rem;
            font-size: 1.15rem;
            background: linear-gradient(145deg, var(--wf-navy) 0%, #14335a 100%);
            animation: wf-node-breathe 4.2s ease-in-out infinite;
        }

        .wf-hero-node.n-tl { left: 18%; top: 22%; animation: wf-orbit-nudge 7s ease-in-out infinite; }
        .wf-hero-node.n-tr { left: 82%; top: 24%; animation: wf-orbit-nudge 6.2s ease-in-out 0.5s infinite; }
        .wf-hero-node.n-bl { left: 22%; top: 78%; animation: wf-orbit-nudge 6.8s ease-in-out 1s infinite; }
        .wf-hero-node.n-br { left: 78%; top: 76%; animation: wf-orbit-nudge 7.4s ease-in-out 1.4s infinite; }

        .wf-hero-pulse {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 4.5rem;
            height: 4.5rem;
            margin: -2.25rem 0 0 -2.25rem;
            border-radius: 1.35rem;
            border: 1.5px solid rgba(201, 162, 39, 0.55);
            animation: wf-pulse-ring 3s ease-out infinite;
            pointer-events: none;
            z-index: 1;
        }

        .wf-hero-pulse.p2 {
            animation-delay: 1.5s;
            border-color: rgba(11, 31, 58, 0.25);
        }

        .wf-hero-spark {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 2px;
            background: var(--wf-gold);
            opacity: 0.7;
            animation: wf-spark-drift 9s linear infinite;
        }

        .wf-hero-spark.s1 { left: 12%; top: 48%; animation-duration: 8s; }
        .wf-hero-spark.s2 { left: 88%; top: 52%; background: var(--wf-navy); animation-duration: 10s; animation-delay: -3s; }
        .wf-hero-spark.s3 { left: 50%; top: 10%; width: 6px; height: 6px; animation-duration: 11s; animation-delay: -5s; }

        @keyframes wf-dash-orbit {
            to { stroke-dashoffset: -120; }
        }

        @keyframes wf-dash-flow {
            to { stroke-dashoffset: -80; }
        }

        @keyframes wf-draw-wave {
            0% { stroke-dashoffset: 280; opacity: 0.35; }
            35%, 65% { stroke-dashoffset: 0; opacity: 1; }
            100% { stroke-dashoffset: -280; opacity: 0.35; }
        }

        @keyframes wf-node-breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.06); }
        }

        @keyframes wf-orbit-nudge {
            0%, 100% { transform: translate(-50%, -50%) translate(0, 0); }
            50% { transform: translate(-50%, -50%) translate(0, -7px); }
        }

        @keyframes wf-pulse-ring {
            0% { transform: scale(0.85); opacity: 0.7; }
            100% { transform: scale(2.4); opacity: 0; }
        }

        @keyframes wf-spark-drift {
            0% { transform: translate(0, 0) rotate(0deg); opacity: 0; }
            15% { opacity: 0.8; }
            50% { transform: translate(18px, -28px) rotate(120deg); opacity: 0.55; }
            100% { transform: translate(-12px, 20px) rotate(280deg); opacity: 0; }
        }

        .wf-anim {
            animation: wf-hero-in 0.7s ease-out both;
        }

        .wf-anim-d1 { animation-delay: 0.05s; }
        .wf-anim-d2 { animation-delay: 0.12s; }
        .wf-anim-d3 { animation-delay: 0.2s; }
        .wf-anim-d4 { animation-delay: 0.28s; }
        .wf-anim-d5 { animation-delay: 0.36s; }

        .wf-hero-list li {
            animation: wf-hero-in 0.55s ease-out both;
        }

        .wf-hero-list li:nth-child(1) { animation-delay: 0.28s; }
        .wf-hero-list li:nth-child(2) { animation-delay: 0.36s; }
        .wf-hero-list li:nth-child(3) { animation-delay: 0.44s; }
        .wf-hero-list li:nth-child(4) { animation-delay: 0.52s; }

        @keyframes wf-hero-in {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes wf-hero-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes wf-hero-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes wf-hero-float-alt {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @media (prefers-reduced-motion: reduce) {
            .wf-hero-constellation,
            .wf-hero-constellation *,
            .wf-hero-node,
            .wf-hero-node .n-core,
            .wf-hero-pulse,
            .wf-hero-spark,
            .wf-anim,
            .wf-hero-list li,
            .wf-steps-visual *,
            .wf-steps-visual .wf-shape {
                animation: none !important;
            }

            .wf-hero-constellation .wf-wave,
            .wf-hero-constellation .wf-wave-soft {
                stroke-dashoffset: 0;
                opacity: 1;
            }
        }

        /* Empat Langkah — animated shapes */
        .wf-steps-visual {
            position: relative;
            min-height: 320px;
            height: 100%;
            border-radius: 1.25rem;
            overflow: hidden;
            border: 1px solid var(--wf-line);
            background:
                radial-gradient(ellipse 70% 55% at 85% 15%, rgba(201, 162, 39, 0.14), transparent 50%),
                radial-gradient(ellipse 55% 45% at 10% 90%, rgba(11, 31, 58, 0.06), transparent 55%),
                linear-gradient(145deg, #ffffff 0%, var(--wf-cream) 100%);
            box-shadow: 0 22px 48px -28px rgba(11, 31, 58, 0.35);
        }

        .wf-steps-visual .wf-shape {
            position: absolute;
            will-change: transform, opacity;
        }

        .wf-steps-visual .s-ring {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 2px solid rgba(11, 31, 58, 0.12);
            top: 12%;
            right: 10%;
            animation: wf-shape-spin 18s linear infinite;
        }

        .wf-steps-visual .s-ring::after {
            content: '';
            position: absolute;
            inset: 18px;
            border-radius: 50%;
            border: 2px dashed rgba(201, 162, 39, 0.45);
            animation: wf-shape-spin 12s linear infinite reverse;
        }

        .wf-steps-visual .s-orb {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--wf-navy) 0%, #14335a 100%);
            top: 18%;
            right: 18%;
            box-shadow: 0 14px 28px -12px rgba(11, 31, 58, 0.45);
            animation: wf-shape-float 5s ease-in-out infinite;
        }

        .wf-steps-visual .s-orb span {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 1.35rem;
        }

        .wf-steps-visual .s-card {
            width: 42%;
            min-width: 140px;
            border-radius: 1rem;
            background: #fff;
            border: 1px solid var(--wf-line);
            box-shadow: 0 12px 28px -16px rgba(11, 31, 58, 0.3);
            padding: 0.9rem 1rem;
        }

        .wf-steps-visual .s-card-a {
            left: 8%;
            top: 16%;
            animation: wf-shape-float 6s ease-in-out 0.2s infinite;
        }

        .wf-steps-visual .s-card-b {
            left: 18%;
            bottom: 14%;
            animation: wf-shape-float-alt 5.5s ease-in-out 0.5s infinite;
        }

        .wf-steps-visual .s-card-c {
            right: 8%;
            bottom: 18%;
            width: 38%;
            animation: wf-shape-float 6.5s ease-in-out 0.8s infinite;
        }

        .wf-steps-visual .s-bars {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 56px;
            margin-top: 0.55rem;
        }

        .wf-steps-visual .s-bars i {
            display: block;
            width: 12px;
            border-radius: 4px 4px 2px 2px;
            background: var(--wf-navy);
            transform-origin: bottom;
            animation: wf-bar-grow 2.8s ease-in-out infinite;
        }

        .wf-steps-visual .s-bars i:nth-child(1) { height: 28%; background: rgba(11, 31, 58, 0.35); animation-delay: 0s; }
        .wf-steps-visual .s-bars i:nth-child(2) { height: 52%; background: rgba(11, 31, 58, 0.55); animation-delay: 0.15s; }
        .wf-steps-visual .s-bars i:nth-child(3) { height: 78%; background: var(--wf-gold); animation-delay: 0.3s; }
        .wf-steps-visual .s-bars i:nth-child(4) { height: 44%; background: rgba(11, 31, 58, 0.45); animation-delay: 0.45s; }
        .wf-steps-visual .s-bars i:nth-child(5) { height: 64%; background: var(--wf-navy); animation-delay: 0.6s; }

        .wf-steps-visual .s-line {
            height: 3px;
            width: 100%;
            border-radius: 999px;
            background: rgba(11, 31, 58, 0.08);
            overflow: hidden;
            margin-top: 0.45rem;
        }

        .wf-steps-visual .s-line span {
            display: block;
            height: 100%;
            width: 42%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--wf-gold), var(--wf-navy));
            animation: wf-line-slide 3.2s ease-in-out infinite;
        }

        .wf-steps-visual .s-dots {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.65rem;
        }

        .wf-steps-visual .s-dots b {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--wf-navy);
            animation: wf-dot-pulse 1.8s ease-in-out infinite;
        }

        .wf-steps-visual .s-dots b:nth-child(1) { animation-delay: 0s; background: var(--wf-navy); }
        .wf-steps-visual .s-dots b:nth-child(2) { animation-delay: 0.2s; background: var(--wf-gold); }
        .wf-steps-visual .s-dots b:nth-child(3) { animation-delay: 0.4s; background: rgba(11, 31, 58, 0.35); }

        .wf-steps-visual .s-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--wf-navy);
            letter-spacing: 0.02em;
        }

        .wf-steps-visual .s-chip em {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 0.4rem;
            background: rgba(11, 31, 58, 0.08);
            color: var(--wf-navy);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-style: normal;
            font-size: 0.65rem;
            font-weight: 800;
        }

        .wf-steps-visual .s-blob {
            width: 90px;
            height: 90px;
            border-radius: 36% 64% 58% 42% / 42% 38% 62% 58%;
            background: rgba(201, 162, 39, 0.18);
            left: 42%;
            top: 38%;
            animation: wf-blob-morph 8s ease-in-out infinite;
        }

        .wf-steps-visual .s-sq {
            width: 28px;
            height: 28px;
            border-radius: 0.45rem;
            background: var(--wf-gold);
            left: 48%;
            top: 22%;
            animation: wf-shape-spin 10s linear infinite, wf-shape-float 4s ease-in-out infinite;
            opacity: 0.85;
        }

        .wf-steps-visual .s-tri {
            width: 0;
            height: 0;
            border-left: 16px solid transparent;
            border-right: 16px solid transparent;
            border-bottom: 28px solid rgba(11, 31, 58, 0.18);
            left: 58%;
            bottom: 42%;
            animation: wf-shape-float-alt 4.5s ease-in-out 0.3s infinite;
        }

        @keyframes wf-shape-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes wf-shape-float-alt {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-8px) translateX(4px); }
        }

        @keyframes wf-shape-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes wf-bar-grow {
            0%, 100% { transform: scaleY(0.55); opacity: 0.7; }
            50% { transform: scaleY(1); opacity: 1; }
        }

        @keyframes wf-line-slide {
            0% { transform: translateX(-40%); }
            50% { transform: translateX(120%); }
            100% { transform: translateX(-40%); }
        }

        @keyframes wf-dot-pulse {
            0%, 100% { transform: scale(1); opacity: 0.55; }
            50% { transform: scale(1.25); opacity: 1; }
        }

        @keyframes wf-blob-morph {
            0%, 100% { border-radius: 36% 64% 58% 42% / 42% 38% 62% 58%; transform: translate(0, 0) rotate(0deg); }
            33% { border-radius: 58% 42% 36% 64% / 55% 48% 52% 45%; transform: translate(8px, -6px) rotate(8deg); }
            66% { border-radius: 42% 58% 64% 36% / 48% 62% 38% 52%; transform: translate(-6px, 8px) rotate(-6deg); }
        }

        .wf-card {
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.5rem;
            transition: transform .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .wf-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .wf-step {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: var(--wf-navy);
            color: #fff;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .wf-security {
            background: linear-gradient(135deg, var(--wf-navy) 0%, #14335a 100%);
            color: #fff;
        }

        .wf-cta {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 70% 80% at 85% 50%, rgba(201, 162, 39, 0.22), transparent 55%),
                radial-gradient(ellipse 50% 60% at 10% 80%, rgba(56, 120, 180, 0.2), transparent 50%),
                linear-gradient(135deg, #071526 0%, #0b1f3a 45%, #122a4a 100%);
        }

        .wf-cta-shapes {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .wf-cta-shapes .ring {
            position: absolute;
            border-radius: 999px;
            border: 1px solid rgba(232, 212, 139, 0.22);
        }

        .wf-cta-shapes .blob {
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
        }

        .wf-cta-shapes .dot {
            position: absolute;
            border-radius: 999px;
            background: rgba(232, 212, 139, 0.55);
        }

        .wf-cta-panel {
            position: relative;
            z-index: 1;
            background: rgba(11, 31, 58, 0.88);
            border: 1px solid rgba(232, 212, 139, 0.28);
            border-radius: 1.5rem;
            max-width: 42rem;
            backdrop-filter: blur(6px);
        }

        @media (max-width: 768px) {
            .wf-hero-phone {
                width: 112px;
                right: 6%;
                bottom: -2%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        {{-- Hero --}}
        <section class="wf-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <p class="wf-anim wf-anim-d1 text-xs tracking-[0.2em] uppercase text-[var(--wf-gold)] font-bold mb-4">WOFINS · by Makna Finance</p>
                        <h1 class="wf-anim wf-anim-d2 text-4xl sm:text-5xl lg:text-[3.4rem] leading-[1.08] font-bold text-[var(--wf-navy)]">
                            Kelola Wedding Organizer Lebih Rapi dalam
                            <span class="text-[var(--wf-gold)]">Satu Platform</span>
                        </h1>
                        <p class="wf-anim wf-anim-d3 mt-5 text-base sm:text-lg text-[var(--wf-muted)] leading-relaxed max-w-xl">
                            WOFINS membantu Wedding Organizer mengelola proyek, vendor, keuangan, payroll, hingga operasional harian dalam satu sistem terintegrasi.
                        </p>

                        <ul class="wf-hero-list mt-7 space-y-3 text-sm sm:text-base text-[var(--wf-ink)]">
                            @foreach ([
                                'Kelola proyek wedding dari prospek hingga selesai.',
                                'Pantau arus kas perusahaan secara real-time.',
                                'Rekonsiliasi rekening koran lebih cepat.',
                                'Absensi GPS & payroll dalam satu sistem.',
                            ] as $point)
                                <li class="flex items-start gap-3">
                                    <span class="wf-check mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="wf-anim wf-anim-d5 mt-8 flex flex-col sm:flex-row gap-3 sm:items-center">
                            <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex items-center justify-center px-6 py-3.5 text-sm">
                                Jadwalkan Demo Gratis
                            </a>
                            <a href="{{ route('fitur') }}" class="inline-flex items-center justify-center gap-2 px-2 py-3 text-sm font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                                Lihat Fitur Lengkap
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="wf-hero-visual" aria-hidden="true">
                        <div class="wf-hero-constellation">
                            <span class="wf-hero-pulse"></span>
                            <span class="wf-hero-pulse p2"></span>
                            <span class="wf-hero-spark s1"></span>
                            <span class="wf-hero-spark s2"></span>
                            <span class="wf-hero-spark s3"></span>

                            <svg class="wf-hero-svg" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle class="wf-arc" cx="200" cy="200" r="148" />
                                <circle class="wf-arc wf-arc-gold" cx="200" cy="200" r="118" />
                                <circle class="wf-arc wf-arc-navy" cx="200" cy="200" r="88" />

                                {{-- Connection lines: center hub to modules --}}
                                <line class="wf-link" x1="200" y1="200" x2="72" y2="88" />
                                <line class="wf-link-gold" x1="200" y1="200" x2="328" y2="96" />
                                <line class="wf-link" x1="200" y1="200" x2="88" y2="312" />
                                <line class="wf-link-gold" x1="200" y1="200" x2="312" y2="304" />

                                {{-- Cashflow wave --}}
                                <path class="wf-wave-soft" d="M48 250 C 100 210, 140 290, 200 240 S 300 180, 352 210" />
                                <path class="wf-wave" d="M48 260 C 110 220, 150 300, 205 250 S 305 190, 352 220" />
                            </svg>

                            <div class="wf-hero-node n-center">
                                <div class="n-core"><i class="fa-solid fa-layer-group"></i></div>
                                <span class="n-label">WOFINS</span>
                            </div>
                            <div class="wf-hero-node n-tl is-gold">
                                <div class="n-core"><i class="fa-solid fa-ring"></i></div>
                                <span class="n-label">Proyek</span>
                            </div>
                            <div class="wf-hero-node n-tr">
                                <div class="n-core"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="n-label">Keuangan</span>
                            </div>
                            <div class="wf-hero-node n-bl is-outline">
                                <div class="n-core"><i class="fa-solid fa-user-check"></i></div>
                                <span class="n-label">Payroll</span>
                            </div>
                            <div class="wf-hero-node n-br is-gold">
                                <div class="n-core"><i class="fa-solid fa-building-columns"></i></div>
                                <span class="n-label">Bank</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="fitur" class="py-16 lg:py-22 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)]">Fitur Lengkap untuk Operasional Wedding Organizer</h2>
                    <p class="mt-3 text-[var(--wf-muted)]">Dari proyek dan keuangan hingga payroll — semuanya terhubung dalam satu alur kerja.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @php
                        $features = [
                            ['icon' => 'fa-ring', 'color' => 'bg-amber-50 text-amber-700', 'title' => 'Proyek Wedding', 'desc' => 'Kelola prospek, order, produk paket, simulasi, dan vendor dari satu tempat.'],
                            ['icon' => 'fa-chart-line', 'color' => 'bg-sky-50 text-sky-700', 'title' => 'Keuangan', 'desc' => 'Pantau pendapatan klien, pengeluaran proyek, dan laporan laba rugi secara real-time.'],
                            ['icon' => 'fa-building-columns', 'color' => 'bg-emerald-50 text-emerald-700', 'title' => 'Rekonsiliasi Rekening Koran', 'desc' => 'Cocokkan transaksi bank dengan sistem lebih cepat dan akurat.'],
                            ['icon' => 'fa-file-lines', 'color' => 'bg-violet-50 text-violet-700', 'title' => 'Nota Dinas Digital', 'desc' => 'Ajukan, setujui, dan arsipkan nota dinas beserta lampiran PDF.'],
                            ['icon' => 'fa-user-check', 'color' => 'bg-rose-50 text-rose-700', 'title' => 'Payroll', 'desc' => 'Master karyawan, perhitungan gaji, slip digital, dan PPh 21.'],
                            ['icon' => 'fa-wallet', 'color' => 'bg-indigo-50 text-indigo-700', 'title' => 'Payroll & Portal Karyawan', 'desc' => 'Kelola gaji, cuti, dan akses portal untuk karyawan tanpa masuk admin penuh.'],
                            ['icon' => 'fa-folder-open', 'color' => 'bg-teal-50 text-teal-700', 'title' => 'Dokumen & SOP', 'desc' => 'Simpan dokumen resmi, SOP, dan knowledge base perusahaan.'],
                            ['icon' => 'fa-shield-halved', 'color' => 'bg-slate-100 text-slate-700', 'title' => 'Hak Akses Berdasarkan Jabatan', 'desc' => 'Role & permission untuk owner, finance, HRD, AM, dan staff.'],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <article class="wf-card">
                            <div class="wf-icon {{ $feature['color'] }}">
                                <i class="fa-solid {{ $feature['icon'] }}"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--wf-navy)] mb-2">{{ $feature['title'] }}</h3>
                            <p class="text-sm text-[var(--wf-muted)] leading-relaxed">{{ $feature['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="cara-kerja" class="py-16 lg:py-20 bg-[var(--wf-cream)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] mb-8">Mulai Menggunakan WOFINS dalam Empat Langkah</h2>
                        <div class="space-y-6">
                            @foreach ([
                                ['Buat perusahaan & pengguna', 'Setup profil perusahaan, role, dan akun tim Anda.'],
                                ['Input proyek & keuangan', 'Masukkan order, vendor, pendapatan, dan pengeluaran.'],
                                ['Aktifkan payroll', 'Siapkan master karyawan dan komponen gaji tim.'],
                                ['Pantau laporan real-time', 'Ambil keputusan dari dashboard, cash flow, dan rekonsiliasi.'],
                            ] as $i => $step)
                                <div class="flex gap-4">
                                    <span class="wf-step">{{ $i + 1 }}</span>
                                    <div>
                                        <h3 class="font-bold text-[var(--wf-navy)]">{{ $step[0] }}</h3>
                                        <p class="text-sm text-[var(--wf-muted)] mt-1">{{ $step[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="wf-steps-visual" aria-hidden="true">
                        <div class="wf-shape s-blob"></div>
                        <div class="wf-shape s-ring"></div>
                        <div class="wf-shape s-orb"><span>4</span></div>
                        <div class="wf-shape s-sq"></div>
                        <div class="wf-shape s-tri"></div>

                        <div class="wf-shape s-card s-card-a">
                            <div class="s-chip"><em>1</em> Perusahaan</div>
                            <div class="s-dots" aria-hidden="true"><b></b><b></b><b></b></div>
                            <div class="s-line"><span></span></div>
                        </div>

                        <div class="wf-shape s-card s-card-b">
                            <div class="s-chip"><em>2</em> Keuangan</div>
                            <div class="s-bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                        </div>

                        <div class="wf-shape s-card s-card-c">
                            <div class="s-chip"><em>3</em> Absensi</div>
                            <div class="s-line" style="margin-top:0.75rem"><span style="width:58%;animation-duration:2.6s"></span></div>
                            <div class="s-dots" style="margin-top:0.75rem" aria-hidden="true"><b></b><b></b><b></b></div>
                            <p class="mt-3 text-[10px] font-bold tracking-wide text-[var(--wf-muted)] uppercase">Langkah 4 · Laporan</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Why + roles --}}
        <section id="keunggulan" class="py-16 lg:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] mb-8">Mengapa Memilih WOFINS?</h2>
                        <ul class="space-y-5">
                            @foreach ([
                                ['Dibangun khusus untuk WO', 'Bukan ERP generik — alur kerjanya cocok dengan operasional wedding organizer.'],
                                ['Data terpusat', 'Proyek, keuangan, HR, dan dokumen tidak lagi tercecer di spreadsheet.'],
                                ['Kurangi kerja manual', 'Approval, payroll, dan rekonsiliasi lebih cepat.'],
                                ['Monitoring real-time', 'Owner melihat kas, proyek, dan kehadiran tanpa menunggu laporan akhir bulan.'],
                                ['Siap berkembang', 'Skalakan dari tim kecil hingga multi-role dengan permission yang jelas.'],
                            ] as $item)
                                <li class="flex gap-3">
                                    <span class="wf-check mt-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-bold text-[var(--wf-navy)]">{{ $item[0] }}</p>
                                        <p class="text-sm text-[var(--wf-muted)] mt-0.5">{{ $item[1] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6 sm:p-8">
                        <h2 class="text-3xl font-bold text-[var(--wf-navy)] mb-6">Digunakan oleh Seluruh Tim</h2>
                        <div class="space-y-4">
                            @foreach ([
                                ['Owner', 'Pantau kinerja perusahaan, kas, dan kepatuhan operasional.'],
                                ['Account Manager', 'Kelola prospek, proyek, target, dan simulasi paket.'],
                                ['Finance', 'Kelola transaksi, piutang, dan rekonsiliasi rekening koran.'],
                                ['Event Manager & Staff', 'Jalankan operasional harian dan koordinasi proyek.'],
                                ['Finance', 'Kelola kas, rekonsiliasi, dan payroll.'],
                            ] as $role)
                                <div class="flex gap-3 items-start bg-white/80 rounded-xl p-4 border border-white">
                                    <div class="w-10 h-10 rounded-full bg-[var(--wf-navy)] text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ strtoupper(substr($role[0], 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-[var(--wf-navy)]">{{ $role[0] }}</p>
                                        <p class="text-sm text-[var(--wf-muted)]">{{ $role[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Security + testimonial --}}
        <section id="testimoni" class="wf-security py-14 lg:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                    <blockquote>
                        <p class="text-xl sm:text-2xl leading-relaxed font-medium text-white/95">
                            “Dengan WOFINS, proyek dan keuangan kami jauh lebih rapi. Absensi dan payroll juga tidak lagi dikelola terpisah-pisah.”
                        </p>
                        <footer class="mt-6 flex items-center gap-3">
                            <img src="{{ asset('images/placeholder_avatar.png') }}" alt="Rama Dhona Utama" class="w-12 h-12 rounded-full object-cover border-2 border-[var(--wf-gold)]" width="48" height="48" loading="lazy" decoding="async">
                            <div>
                                <p class="font-bold text-white">Rama Dhona Utama</p>
                                <p class="text-sm text-white/70">Makna Wedding & Event Planner</p>
                            </div>
                        </footer>
                    </blockquote>

                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold text-white mb-6">Data Bisnis Lebih Aman</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach ([
                                ['fa-user-lock', 'Role-based access'],
                                ['fa-clock-rotate-left', 'Activity history'],
                                ['fa-check-double', 'Approval workflows'],
                                ['fa-database', 'Centralized backup'],
                                ['fa-clipboard-list', 'Audit trail'],
                            ] as $sec)
                                <div class="rounded-xl bg-white/10 border border-white/15 p-4 text-center">
                                    <i class="fa-solid {{ $sec[0] }} text-[var(--wf-gold-soft)] text-xl mb-2"></i>
                                    <p class="text-xs font-semibold text-white/90 leading-snug">{{ $sec[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="py-16 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] text-center mb-10">FAQ</h2>
                <div class="space-y-3" x-data="{ open: 0 }">
                    @foreach ([
                        ['Apakah WOFINS khusus untuk wedding organizer?', 'Ya. Alur proyek, vendor, nota dinas, dan keuangan dirancang untuk operasional WO / EO, bukan ERP generik.'],
                        ['Apakah ada modul gaji?', 'Ada. Professional+ mencakup payroll dan master karyawan (Employee). Slip gaji digital siap dibagikan ke tim.'],
                        ['Bisakah rekonsiliasi rekening koran?', 'Bisa. Unggah rekening koran, cocokkan transaksi, dan unduh hasil rekonsiliasi.'],
                        ['Apakah bisa dibatasi per jabatan?', 'Bisa. Role & permission mengatur akses owner, finance, HRD, account manager, dan staff.'],
                        ['Bagaimana cara mencoba?', 'Jadwalkan demo gratis melalui halaman Kontak, atau masuk jika sudah memiliki akun.'],
                    ] as $i => $faq)
                        <div class="border border-[var(--wf-line)] rounded-xl overflow-hidden">
                            <button type="button" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-bold text-[var(--wf-navy)]" @click="open = open === {{ $i }} ? null : {{ $i }}">
                                <span>{{ $faq[0] }}</span>
                                <svg class="w-4 h-4 shrink-0 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open === {{ $i }}" x-cloak class="px-5 pb-4 text-sm text-[var(--wf-muted)] leading-relaxed">
                                {{ $faq[1] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="wf-cta py-16 lg:py-24">
            <div class="wf-cta-shapes" aria-hidden="true">
                <span class="blob" style="width:28rem;height:28rem;right:-6rem;top:-8rem;background:rgba(201,162,39,0.14);"></span>
                <span class="blob" style="width:22rem;height:22rem;right:18%;bottom:-8rem;background:rgba(70,140,200,0.16);"></span>
                <span class="blob" style="width:14rem;height:14rem;left:-4rem;top:20%;background:rgba(232,212,139,0.1);"></span>
                <span class="ring" style="width:20rem;height:20rem;right:8%;top:12%;"></span>
                <span class="ring" style="width:12rem;height:12rem;right:14%;top:22%;border-color:rgba(255,255,255,0.12);"></span>
                <span class="ring" style="width:8rem;height:8rem;left:42%;bottom:10%;border-color:rgba(201,162,39,0.3);"></span>
                <span class="dot" style="width:0.55rem;height:0.55rem;right:22%;top:28%;"></span>
                <span class="dot" style="width:0.4rem;height:0.4rem;right:30%;top:48%;opacity:0.7;"></span>
                <span class="dot" style="width:0.7rem;height:0.7rem;right:12%;bottom:30%;opacity:0.5;"></span>
                <span class="dot" style="width:0.35rem;height:0.35rem;left:48%;top:22%;opacity:0.6;"></span>
                <svg class="absolute right-0 top-0 h-full w-[58%] opacity-40" viewBox="0 0 600 400" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMaxYMid slice">
                    <path d="M420 40c60 30 110 90 120 160s-20 140-80 180" stroke="rgba(232,212,139,0.35)" stroke-width="1.5"/>
                    <path d="M480 20c50 40 90 110 95 180s-25 130-75 170" stroke="rgba(255,255,255,0.12)" stroke-width="1.25"/>
                    <circle cx="520" cy="120" r="48" fill="rgba(201,162,39,0.08)" stroke="rgba(232,212,139,0.25)"/>
                    <circle cx="460" cy="260" r="72" fill="rgba(56,120,180,0.08)" stroke="rgba(140,190,230,0.2)"/>
                    <circle cx="560" cy="300" r="18" fill="rgba(232,212,139,0.2)"/>
                    <rect x="390" y="150" width="56" height="56" rx="14" transform="rotate(18 418 178)" stroke="rgba(255,255,255,0.14)" fill="rgba(255,255,255,0.03)"/>
                </svg>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="wf-cta-panel p-8 sm:p-10 text-white">
                    <h2 class="text-3xl sm:text-4xl font-bold leading-tight">Saatnya Mengelola Wedding Organizer dengan Lebih Profesional</h2>
                    <p class="mt-4 text-white/80 max-w-xl">
                        Satukan proyek, keuangan, payroll, dan dokumen dalam satu platform. Ambil keputusan lebih cepat dengan data yang rapi.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('kontak') }}" class="wf-btn-gold inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Jadwalkan Demo Gratis
                        </a>
                        <a href="{{ route('kontak') }}" class="wf-btn-outline-light inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                            Konsultasikan Kebutuhan Anda
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer — sama dengan halaman front lain --}}
        @include('front.partials.wf-footer')

        {{-- WhatsApp --}}
        <div class="fixed bottom-6 right-6 z-50">
            <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin jadwalkan demo WOFINS.') }}"
                target="_blank" rel="noopener"
                class="group bg-[#25D366] hover:bg-[#1ebe57] text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center"
                aria-label="WhatsApp">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.087"/></svg>
            </a>
        </div>
    </div>
@endsection
