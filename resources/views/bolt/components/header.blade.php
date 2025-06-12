@php
    $current_url = url()->current();
    $generator_link = route('dashboard.user.openai.list') === $current_url ? '#lqd-generators-filter-list' :  (route('dashboard.user.openai.list'));
	if (! $setting->feature_ai_writer) {
		$generator_link = route('dashboard.index');
	}
@endphp

<header class="lqd-header relative flex h-[--header-height] border-b border-header-border bg-header-background text-xs font-medium transition-colors max-lg:h-[65px]">
    <div @class([
        'lqd-header-container flex w-full grow gap-2 px-4 max-lg:w-full max-lg:max-w-none',
        'container' => !$attributes->get('layout-wide'),
        'container-fluid' => $attributes->get('layout-wide'),
        Theme::getSetting('wideLayoutPaddingX', '') =>
            filled(Theme::getSetting('wideLayoutPaddingX', '')) &&
            $attributes->get('layout-wide'),
    ])>

        {{-- Mobile nav toggle and logo --}}
        <div class="mobile-nav-logo flex items-center gap-3 lg:hidden">
            <button
                class="lqd-mobile-nav-toggle flex size-10 items-center justify-center"
                type="button"
                x-init
                @click.prevent="$store.mobileNav.toggleNav()"
                :class="{ 'lqd-is-active': !$store.mobileNav.navCollapse }"
            >
                <span class="lqd-mobile-nav-toggle-icon relative h-[2px] w-5 rounded-xl bg-current"></span>
            </button>

            <x-header-logo />
        </div>

        {{-- Title slot --}}
        @if ($title ?? false)
            <div class="header-title-container peer/title hidden items-center lg:flex">
                <h1 class="m-0 font-semibold">
                    {{ $title }}
                </h1>
            </div>
        @endif

        {{-- Focus Mode Nav Trigger --}}
        <div class="header-focus-mode-nav-toggle-container me-3 hidden items-center gap-3 lg:group-[&.focus-mode]/body:flex">
            @if ($title ?? true)
                <x-header-logo />
            @endif

            <p class="m-0 flex items-center gap-3 text-[12px] text-heading-foreground">
                <svg
                    width="10"
                    height="8"
                    viewBox="0 0 10 8"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path
                        d="M3.70379 4L0.647461 0.933335L1.34996 0.230835L5.11913 4L1.34996 7.76917L0.647461 7.06667L3.70379 4ZM7.93713 4L4.88079 0.933335L5.58329 0.230835L9.35246 4L5.58329 7.76917L4.88079 7.06667L7.93713 4Z"
                    />
                </svg>
                @if (view()->hasSection('titlebar_title'))
                    @yield('titlebar_title')
                @elseif (view()->hasSection('title'))
                    @yield('title')
                @endif
            </p>
        </div>

        {{-- Search form --}}
        <div class="header-search-container flex items-center peer-[&.header-title-container]/title:grow peer-[&.header-title-container]/title:justify-center">
            <x-header-search />
        </div>

        <div class="header-actions-container flex grow justify-end gap-4 max-lg:basis-2/3 max-lg:gap-2">

            <div class="hidden items-center gap-2 xl:flex">
                @if (view()->hasSection('titlebar_actions'))
                    @yield('titlebar_actions')
                @elseif (!empty($actions))
                    <div class="lqd-header-actions flex flex-wrap items-center gap-2">
                        {{ $actions }}
                    </div>
                @else
                    <div class="lqd-header-actions flex flex-wrap items-center gap-2">
                        @if (request()->routeIs('dashboard.user.openai.documents.all') && !isset($currfolder))
                            <x-modal
                                title="{{ __('New Folder') }}"
                                disable-modal="{{ $app_is_demo }}"
                                disable-modal-message="{{ __('This feature is disabled in Demo version.') }}"
                            >
                                <x-slot:trigger
                                    variant="ghost-shadow"
                                >
                                    <x-tabler-plus class="size-4" />
                                    {{ __('New Folder') }}
                                </x-slot:trigger>
                                <x-slot:modal>
                                    @includeIf('panel.user.openai.components.modals.create-new-folder')
                                </x-slot:modal>
                            </x-modal>
                        @else
                            <x-button
                                variant="ghost-shadow"
                                href="{{  (route('dashboard.user.openai.documents.all')) }}"
                            >
                                {{ __('My Documents') }}
                            </x-button>
                        @endif
                        <x-button href="{{ $generator_link }}">
                            <x-tabler-plus class="size-4" />
                            {{ __('New') }}
                        </x-button>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4 max-lg:gap-2">
                {{-- Dark/light switch --}}
                <div class="lg:hidden">
                    @if (Theme::getSetting('dashboard.supportedColorSchemes') === 'all')
                        <x-light-dark-switch />
                    @endif
                </div>

                @if (setting('notification_active', 0))
                    {{-- Notifications --}}
                    <x-notifications />
                @endif

                {{-- Language dropdown --}}
                @if (count(explode(',', $settings_two->languages)) > 1)
                    <x-language-dropdown />
                @endif

                {{-- Upgrade button on mobile --}}
                <x-button
                    class="lqd-header-upgrade-btn flex size-10 items-center justify-center border p-0 text-current dark:bg-white/[3%] lg:hidden"
                    variant="link"
                    href="{{ route('dashboard.user.payment.subscription') }}"
                >
                    <x-tabler-bolt stroke-width="1.5" />
                </x-button>

                {{-- User menu --}}
                <x-user-dropdown />
            </div>
        </div>
    </div>
</header>
