<script>
    (() => {
        const currentTheme = document.querySelector('body').getAttribute('data-theme');

        localStorage.setItem('lqdNavbarShrinked', true);
        localStorage.setItem(currentTheme + ':lqdFocusModeEnabled', true);

        {{-- layout broken when it is scrolled in the middle of the page and switching themes. window.scrollTo(0, 0) not working for some reason. --}}
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.add('overflow-hidden');

            setTimeout(() => {
                document.documentElement.classList.remove('overflow-hidden');
            }, 100);
        })
    })();
</script>

@if (Route::currentRouteName() === 'dashboard.user.generator.index')
    <script>
        document.body.classList.add('lqd-page-generator-v2');
    </script>
@endif
