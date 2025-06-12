<header
    class="lqd-header relative z-10 flex h-[--header-height] border-b border-header-border bg-header-background text-xs font-medium transition-colors max-lg:h-[65px]"
    x-data="{
        searchShow: false,
        setSearchShow(status) {
            if (status == null) {
                this.searchShow = !this.searchShow;
            } else {
                this.searchShow = status;
            }
    
            if (this.searchShow) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        }
    }"
    @keyup.esc.window="setSearchShow(false)"
>
    <div @class([
        'lqd-header-container flex w-full grow gap-2 px-4 max-lg:w-full max-lg:max-w-none',
        'container' => !$attributes->get('layout-wide'),
        'container-fluid' => $attributes->get('layout-wide'),
        Theme::getSetting('wideLayoutPaddingX', '') =>
            filled(Theme::getSetting('wideLayoutPaddingX', '')) &&
            $attributes->get('layout-wide'),
    ])>
        {{-- Title slot --}}
        @if ($title ?? false)
            <div class="header-title-container peer/title hidden items-center lg:flex">
                <h1 class="m-0 font-semibold">
                    {{ $title }}
                </h1>
            </div>
        @endif

        @includeFirst(['focus-mode::header', 'components.includes.ai-tools', 'vendor.empty'])

        <div class="header-actions-container flex grow gap-3.5 group-[&.focus-mode]/body:hidden max-lg:hidden max-lg:basis-2/3 max-lg:gap-2 lg:w-1/3">
            {{-- Action buttons --}}
            @if ($actions ?? false)
                {{ $actions }}
            @else
                <div class="flex items-center max-xl:gap-2 xl:gap-3">
                    <x-button
                        class="relative py-2 outline-2 outline-offset-0 max-xl:hidden lg:px-5"
                        variant="outline"
                        href="{{ route('dashboard.user.payment.subscription') }}"
                    >
                        <x-outline-glow class="[--outline-glow-w:2px]" />
                        <svg
                            width="19"
                            height="15"
                            viewBox="0 0 19 15"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M7.75 7L6 5.075L6.525 4.2M4.25 0.875H14.75L17.375 5.25L9.9375 13.5625C9.88047 13.6207 9.8124 13.6669 9.73728 13.6985C9.66215 13.7301 9.58149 13.7463 9.5 13.7463C9.41851 13.7463 9.33785 13.7301 9.26272 13.6985C9.1876 13.6669 9.11953 13.6207 9.0625 13.5625L1.625 5.25L4.25 0.875Z"
                            />
                        </svg>
                        <span class="max-lg:hidden">
                            {{ __('Upgrade') }}
                        </span>
                    </x-button>
                    {{-- @if (Auth::user()->isAdmin())
                        <x-button
							class="py-2.5 lg:px-5"
                            href="{{ route('dashboard.admin.index') }}"
                            variant="ghost-shadow"
                        >
                            {{ __('Admin Panel') }}
                        </x-button>
                    @endif

                    @if ($settings_two->liquid_license_type == 'Extended License')
                        @if ($subscription = getSubscription())
                            <x-button
                                class="max-xl:hidden py-2.5 lg:px-5"
                                href="{{ route('dashboard.user.payment.subscription') }}"
                                variant="ghost-shadow"
                            >
                                {{ $subscription?->plan?->name }} - {{ getSubscriptionDaysLeft() }}
                                {{ __('Days Left') }}
                            </x-button>
                        @else
                            <x-button
                                class="max-xl:hidden py-2.5 lg:px-5"
                                href="{{ route('dashboard.user.payment.subscription') }}"
                                variant="ghost-shadow"
                            >
                                {{ __('No Active Subscription') }}
                            </x-button>
                        @endif
                    @endif --}}
                </div>
            @endif
        </div>

        {{-- Mobile nav toggle and logo --}}
        <div class="mobile-nav-logo flex items-center justify-center gap-3 max-lg:-order-1 lg:w-1/3">
            <button
                class="lqd-mobile-nav-toggle flex size-10 items-center justify-center lg:hidden"
                type="button"
                x-init
                @click.prevent="$store.mobileNav.toggleNav()"
                :class="{ 'lqd-is-active': !$store.mobileNav.navCollapse }"
            >
                <span class="lqd-mobile-nav-toggle-icon relative h-[2px] w-5 rounded-xl bg-current"></span>
            </button>
            <x-header-logo />
        </div>

        <div class="header-actions-container relative flex grow items-center justify-end gap-3.5 max-lg:basis-2/3 max-lg:gap-1 lg:w-1/3">

            <x-button
                class="size-10 rounded-full border bg-transparent text-heading-foreground hover:translate-y-0 hover:shadow-none"
                size="none"
                variant="none"
                @click.prevent="setSearchShow()"
            >
                <x-tabler-search class="size-[18px]" />
            </x-button>

            {{-- Dark/light switch --}}
            @if (Theme::getSetting('dashboard.supportedColorSchemes') === 'all')
                <x-light-dark-switch class="size-10 rounded-full border" />
            @endif

            @includeFirst(['focus-mode::ai-tools-button', 'components.includes.ai-tools-button', 'vendor.empty'], ['class' => 'size-10 border rounded-full'])

            {{-- Notifications --}}
            @if (setting('notification_active', 0))
                <x-notifications class:trigger="size-10 border rounded-full" />
            @endif

            {{-- Language dropdown --}}
            @if (count(explode(',', $settings_two->languages)) > 1)
                <x-language-dropdown class:trigger="size-10 border rounded-full" />
            @endif

            {{-- Upgrade button on mobile --}}
            <x-button
                class="lqd-header-upgrade-btn flex size-10 items-center justify-center rounded-full border p-0 text-current dark:bg-white/[3%] lg:hidden"
                variant="link"
                href="{{ route('dashboard.user.payment.subscription') }}"
            >
                <x-tabler-bolt stroke-width="1.5" />
            </x-button>

            {{-- User menu --}}
            <x-user-dropdown class:trigger="size-10 border">
                <x-slot:trigger>
                    <x-tabler-user-circle
                        class="size-[22px]"
                        stroke-width="1.5"
                    />
                </x-slot:trigger>
            </x-user-dropdown>
        </div>

        {{-- Search form --}}
        <div
            class="header-search-container absolute start-0 top-0 z-10 flex h-full w-full items-center peer-[&.header-title-container]/title:grow peer-[&.header-title-container]/title:justify-center"
            x-cloak
            x-transition
            x-show="searchShow"
        >
            <x-header-search
                class="h-full !w-full"
                class:input="h-full w-full lg:ps-16 !rounded-none bg-background outline-none sm:text-base lg:pe-24"
                class:kbd="hidden"
                class:arrow="hidden"
                class:icon="start-6 size-5 text-heading-foreground"
                class:loader="end-24"
                class:input-wrap="h-full w-full"
                class:input-container="h-full w-full"
                x-ref="searchInput"
            />

            <x-button
                class="absolute end-10 top-1/2 size-10 -translate-y-1/2 rounded-full border bg-transparent text-heading-foreground hover:-translate-y-1/2 hover:shadow-none"
                size="none"
                variant="none"
                @click="searchShow = !searchShow"
            >
                <x-tabler-x class="size-[18px]" />
            </x-button>
        </div>
    </div>
</header>
