<a
    class="lqd-skip-link pointer-events-none fixed start-7 top-7 z-[90] rounded-md bg-background px-3 py-1 text-lg opacity-0 shadow-xl focus-visible:pointer-events-auto focus-visible:opacity-100 focus-visible:outline-primary"
    href="#lqd-titlebar"
>
    {{ __('Skip to content') }}
</a>

<aside
    class="lqd-navbar max-lg:rounded-b-5 start-0 top-0 z-[99] w-[--navbar-width] shrink-0 overflow-hidden rounded-ee-navbar-ee rounded-es-navbar-es rounded-se-navbar-se rounded-ss-navbar-ss border-e border-navbar-border bg-navbar-background text-navbar font-medium text-navbar-foreground transition-all max-lg:invisible max-lg:absolute max-lg:left-0 max-lg:top-[65px] max-lg:z-[99] max-lg:max-h-[calc(85vh-2rem)] max-lg:min-h-0 max-lg:w-full max-lg:origin-top max-lg:-translate-y-2 max-lg:scale-95 max-lg:overflow-y-auto max-lg:bg-background max-lg:p-0 max-lg:opacity-0 max-lg:shadow-xl lg:fixed lg:bottom-[--body-padding] lg:top-[--body-padding] max-lg:[&.lqd-is-active]:visible max-lg:[&.lqd-is-active]:translate-y-0 max-lg:[&.lqd-is-active]:scale-100 max-lg:[&.lqd-is-active]:opacity-100"
    x-init
    :class="{ 'lqd-is-active': !$store.mobileNav.navCollapse }"
    @click.outside="$store.navbarShrink.toggle('shrink')"
    @mouseleave="$store.navbarShrink.toggle('shrink')"
>
    <div
        class="lqd-navbar-inner -me-navbar-me h-full overflow-y-auto overscroll-contain pe-navbar-pe ps-navbar-ps lg:group-[&.navbar-shrinked]/body:flex lg:group-[&.navbar-shrinked]/body:flex-col lg:group-[&.navbar-shrinked]/body:items-center lg:group-[&.navbar-shrinked]/body:justify-between">
        <button
            class="lqd-navbar-expander group/expander relative !top-auto grid size-10 cursor-pointer place-items-center rounded-2xl border-0 p-0 text-white transition-all max-lg:hidden"
            x-init
            @click.prevent="$store.navbarShrink.toggle()"
        >
            <svg
                class="col-start-1 col-end-1 row-start-1 row-end-1 fill-black/25"
                width="39"
                height="39"
                viewBox="0 0 39 39"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M39 19.5C39 15.7625 38.8375 12.35 38.1875 10.4C37.5375 8.125 36.725 6.0125 34.775 4.0625C32.3375 1.7875 30.225 1.1375 26.975 0.4875C24.7 0.1625 21.9375 0 20.3125 0C19.825 0 19.3375 0 18.85 0C17.0625 0 14.4625 0.1625 12.025 0.4875C8.775 1.1375 6.5 1.95 4.225 4.0625C2.1125 6.0125 1.4625 8.125 0.8125 10.4C0.1625 12.35 0 15.7625 0 19.5C0 23.2375 0.1625 26.65 0.8125 28.6C1.4625 30.875 2.275 32.9875 4.225 34.9375C6.6625 37.2125 8.775 37.8625 12.025 38.5125C14.625 39 17.875 39 19.5 39C21.125 39 24.375 39 27.1375 38.5125C30.225 37.8625 32.5 37.2125 34.9375 34.9375C36.8875 33.15 37.7 31.0375 38.35 28.6C38.8375 26.65 39 23.2375 39 19.5Z"
                />
            </svg>
            <span class="col-start-1 col-end-1 row-start-1 row-end-1 inline-flex flex-col items-end gap-1.5">
                <x-tabler-grid-dots class="size-5 fill-current" />
                <span class="sr-only">
                    {{ __('Toggle navigation') }}
                </span>
            </span>
        </button>

        @php
            $items = app(\App\Services\Common\MenuService::class)->generate();
            $isAdmin = \Auth::user()?->isAdmin();
        @endphp

        <div class="hidden w-full grow flex-col items-center py-8 lg:group-[&.navbar-shrinked]/body:flex">
            @php
                $middle_nav_urls = app(\App\Services\Common\MenuService::class)->boltMenu();
                $bottom_nav_urls = ['support', 'settings', 'affiliates'];
                $middle_nav_items = [];
                $bottom_nav_items = [];

                foreach ($items as $key => $item) {
                    if (in_array($key, array_keys($middle_nav_urls))) {
                        $middle_nav_items[$key] = $item;
                    } elseif (in_array($key, $bottom_nav_urls)) {
                        $bottom_nav_items[$key] = $item;
                    }
                }
            @endphp
            <nav class="flex w-full grow flex-col">
                <ul class="lqd-navbar-ul-focus-middle flex flex-col gap-3.5">
                    @foreach ($middle_nav_items as $key => $item)
                        {{-- <style>
                            #{{ $key }} {
                                --background: {{ $middle_nav_urls[$key]['background'] }};
                                --foreground: {{ $middle_nav_urls[$key]['foreground'] }};
                            }
                        </style> --}}
                        @if (\App\Helpers\Classes\PlanHelper::planMenuCheck($userPlan, $key))
                            @if (data_get($item, 'is_admin'))
                                @if ($isAdmin)
                                    @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                        @if ($item['children_count'])
                                            @includeIf('default.components.navbar.partials.types.item-dropdown')
                                        @else
                                            @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                        @endif
                                    @endif
                                @endif
                            @else
                                @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                    @if ($item['children_count'])
                                        @includeIf('default.components.navbar.partials.types.item-dropdown')
                                    @else
                                        @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                    @endif
                                @endif
                            @endif
                        @endif
                    @endforeach
                </ul>

                <div class="mt-auto flex flex-col items-center">
                    <ul class="lqd-navbar-ul-focus-bottom flex w-full flex-col gap-3.5">
                        @foreach ($bottom_nav_items as $key => $item)
                            @if (\App\Helpers\Classes\PlanHelper::planMenuCheck($userPlan, $key))
                                @if (data_get($item, 'is_admin'))
                                    @if ($isAdmin)
                                        @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                            @if ($item['children_count'])
                                                @includeIf('default.components.navbar.partials.types.item-dropdown')
                                            @else
                                                @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                            @endif
                                        @endif
                                    @endif
                                @else
                                    @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                        @if ($item['children_count'])
                                            @includeIf('default.components.navbar.partials.types.item-dropdown')
                                        @else
                                            @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                        @endif
                                    @endif
                                @endif
                            @endif
                        @endforeach
                    </ul>
                </div>
            </nav>
        </div>

        <nav
            class="lqd-navbar-nav lg:group-[&.navbar-shrinked]/body:hidden"
            id="navbar-menu"
        >
            <ul class="lqd-navbar-ul">
                @foreach ($items as $key => $item)
                    @if (\App\Helpers\Classes\PlanHelper::planMenuCheck($userPlan, $key))
                        @if (data_get($item, 'is_admin'))
                            @if ($isAdmin)
                                @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                    @if ($item['children_count'])
                                        @includeIf('default.components.navbar.partials.types.item-dropdown')
                                    @else
                                        @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                    @endif
                                @endif
                            @endif
                        @else
                            @if (data_get($item, 'show_condition', true) && data_get($item, 'is_active'))
                                @if ($item['children_count'])
                                    @includeIf('default.components.navbar.partials.types.item-dropdown')
                                @else
                                    @includeIf('default.components.navbar.partials.types.' . $item['type'])
                                @endif
                            @endif
                        @endif
                    @endif
                @endforeach

                {{-- Admin menu items --}}
                @if ($isAdmin)
                    {{-- <x-navbar.item>
                        <x-navbar.link
                            label="{{ __('ChatBot') }}"
                            href="dashboard.chatbot.index"
                            icon="tabler-message-2-code"
                            active-condition="{{ activeRoute('dashboard.chatbot.*') }}"
                            new
                        />
                    </x-navbar.item> --}}

                    @if ($app_is_not_demo && setting('premium_support', true))
                        <x-navbar.item>
                            <x-navbar.link
                                label="{{ __('Premium Membership') }}"
                                href="#"
                                trigger-type="modal"
                            >
                                <x-slot:modal>
                                    @includeIf('premium-support.index')
                                </x-slot:modal>
                            </x-navbar.link>
                        </x-navbar.item>
                    @endif
                @endif

                <x-navbar.item>
                    <x-navbar.divider />
                </x-navbar.item>

                <x-navbar.item class="group-[&.navbar-shrinked]/body:hidden">
                    <x-navbar.label>
                        {{ __('Credits') }}
                    </x-navbar.label>
                </x-navbar.item>

                <x-navbar.item class="pb-navbar-link-pb pe-navbar-link-pe ps-navbar-link-ps pt-navbar-link-pt group-[&.navbar-shrinked]/body:hidden">
                    <x-credit-list />
                </x-navbar.item>

                @if ($setting->feature_affilates)
                    <x-navbar.item class="group-[&.navbar-shrinked]/body:hidden">
                        <x-navbar.divider />
                    </x-navbar.item>

                    <x-navbar.item class="group-[&.navbar-shrinked]/body:hidden">
                        <x-navbar.label>
                            {{ __('Affiliation') }}
                        </x-navbar.label>
                    </x-navbar.item>

                    <x-navbar.item class="pb-navbar-link-pb pe-navbar-link-pe ps-navbar-link-ps pt-navbar-link-pt group-[&.navbar-shrinked]/body:hidden">
                        <div
                            class="lqd-navbar-affiliation inline-block w-full rounded-xl border border-navbar-divider px-8 py-4 text-center text-2xs leading-tight transition-border">
                            <p class="m-0 mb-2 text-[20px] not-italic">🎁</p>
                            <p class="mb-4">{{ __('Invite your friend and get') }}
                                {{ $setting->affiliate_commission_percentage }}%
                                @if ($is_onetime_commission)
                                    {{ __('on their first purchase.') }}
                                @else
                                    {{ __('on all their purchases.') }}
                                @endif
                            </p>
                            <x-button
                                class="text-3xs"
                                href="{{ route('dashboard.user.affiliates.index') }}"
                                variant="ghost-shadow"
                            >
                                {{ __('Invite') }}
                            </x-button>
                        </div>
                    </x-navbar.item>
                @endif
            </ul>
        </nav>
    </div>
</aside>
