<div
    class="lqd-sidebar w-full bg-background max-lg:rounded-b-2xl lg:fixed lg:bottom-[--body-padding] lg:end-[--body-padding] lg:top-[--body-padding] lg:z-5 lg:w-[--sidebar-width] lg:overflow-y-auto lg:rounded-e-2xl lg:border-s lg:border-border">
    @if (view()->hasSection('sidebar-content'))
        @yield('sidebar-content')
    @else
        @includeIf('panel.layout.sidebar-content')
    @endif
</div>
