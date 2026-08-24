<footer class="wf-footer pt-14 pb-8" style="background: var(--wf-navy); color: rgba(255,255,255,0.78);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-8 lg:gap-6">
            <div class="lg:col-span-2">
                <p class="text-2xl font-bold text-white tracking-wide">WOFINS</p>
                <p class="mt-3 text-sm leading-relaxed text-white/65 max-w-xs">
                    Wedding Organizer Financial Information System — kelola proyek, keuangan, HRIS, dan operasional dalam satu platform.
                </p>
                <div class="mt-5 flex items-center gap-3">
                    @foreach ([
                        ['fab fa-instagram', '#'],
                        ['fab fa-facebook-f', '#'],
                        ['fab fa-youtube', '#'],
                        ['fab fa-linkedin-in', '#'],
                    ] as $social)
                        <a href="{{ $social[1] }}" class="w-9 h-9 rounded-full border border-white/25 inline-flex items-center justify-center text-white/80 hover:bg-white/10 hover:text-white">
                            <i class="{{ $social[0] }} text-sm"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="font-bold text-white mb-3">Produk</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('fitur') }}" class="hover:text-white">Fitur</a></li>
                    <li><a href="{{ route('harga') }}" class="hover:text-white">Harga</a></li>
                    <li><a href="{{ route('docs.index') }}" class="hover:text-white">Docs</a></li>
                    <li><a href="{{ route('home') }}#testimoni" class="hover:text-white">Keamanan</a></li>
                </ul>
            </div>

            <div>
                <p class="font-bold text-white mb-3">Solusi</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}#keunggulan" class="hover:text-white">Untuk Owner</a></li>
                    <li><a href="{{ route('fitur') }}" class="hover:text-white">Untuk Finance</a></li>
                    <li><a href="{{ route('fitur') }}" class="hover:text-white">Untuk HRD</a></li>
                    <li><a href="{{ route('fitur') }}" class="hover:text-white">Untuk Tim Operasional</a></li>
                </ul>
            </div>

            <div>
                <p class="font-bold text-white mb-3">Perusahaan</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white">Tentang Kami</a></li>
                    <li><a href="{{ route('kontak') }}" class="hover:text-white">Kontak Kami</a></li>
                    <li><a href="{{ route('docs.index') }}" class="hover:text-white">Dokumentasi</a></li>
                </ul>
            </div>

            <div>
                <p class="font-bold text-white mb-3">Hubungi Kami</p>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-phone mt-0.5 text-[var(--wf-gold-soft,#e8d48b)]"></i>
                        <a href="https://wa.me/6281373183794" class="hover:text-white">+62 813-7318-3794</a>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-envelope mt-0.5 text-[var(--wf-gold-soft,#e8d48b)]"></i>
                        <a href="mailto:support@wofins.id" class="hover:text-white">support@wofins.id</a>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-globe mt-0.5 text-[var(--wf-gold-soft,#e8d48b)]"></i>
                        <span>wofins.id</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-location-dot mt-0.5 text-[var(--wf-gold-soft,#e8d48b)]"></i>
                        <span>Palembang, Indonesia</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/55">
            <p>© {{ now()->year }} Makna Kreatif Indonesia. All rights reserved.</p>
            <p>by Makna Finance</p>
        </div>
    </div>
</footer>
