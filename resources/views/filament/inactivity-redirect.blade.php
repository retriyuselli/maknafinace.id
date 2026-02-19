<script>
    const t = {{ (int) config('session.lifetime') * 60 * 1000 }};
    let x;

    function s() {
        clearTimeout(x);
        x = setTimeout(() => {
            window.location.href = "{{ url('/') }}"
        }, t)
    } ['mousemove', 'keydown', 'scroll', 'click', 'touchstart', 'touchmove'].forEach(e => addEventListener(e, s, {
        passive: true
    }));
    s();
</script>
