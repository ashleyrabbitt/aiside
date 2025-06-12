@php
    $current_url = url()->current();

    $base_class = 'lqd-titlebar pt-6 transition-colors';
    $container_base_class = 'lqd-titlebar-container flex flex-wrap items-center justify-between gap-y-4';
    $before_base_class = 'lqd-titlebar-before w-full lg:w-1/3 flex';
    $after_base_class = 'lqd-titlebar-after py-9 w-full';
    $pretitle_base_class = 'lqd-titlebar-pretitle text-xs text-foreground/70 m-0';
    $title_base_class = 'lqd-titlebar-title m-0';
    $subtitle_base_class = 'lqd-titlebar-subtitle m-0 text-lg w-auto shrink lg:max-w-[65%] text-center';
    $actions_base_class = 'lqd-titlebar-actions flex items-center gap-2 w-full lg:justify-end max-lg:hidden';
    $generator_link = route('dashboard.user.openai.list') === $current_url ? '#lqd-generators-filter-list' : LaravelLocalization::localizeUrl(route('dashboard.user.openai.list'));
    if (!$setting->feature_ai_writer) {
        $generator_link = route('dashboard.index');
    }
    $wide_container_px = Theme::getSetting('wideLayoutPaddingX', '');
    $has_title = true;
    $has_pretitle = true;
    $has_subtitle = view()->hasSection('titlebar_subtitle') && !blank(view()->getSection('titlebar_subtitle'));
    $titlebar_after_in_nav_col = $attributes->has('titlbar-after-place') && $attributes->get('titlbar-after-place') === 'col-nav';
    $title_section_name = '';
    $actions_empty = false;

    if (view()->hasSection('titlebar_title')) {
        $title_section_name = 'titlebar_title';
    } elseif (view()->hasSection('title')) {
        $title_section_name = 'title';
    }

    if ($attributes->has('title') && blank($attributes->get('title'))) {
        $has_title = false;
    }
    if ($attributes->has('pretitle') && blank($attributes->get('pretitle'))) {
        $has_pretitle = false;
    }

    if (!$attributes->get('layout-wide')) {
        $container_base_class .= ' container';
    } else {
        $container_base_class .= ' container-fluid';

        if (!empty($wide_container_px)) {
            $container_base_class .= ' ' . $wide_container_px;
        }
    }

    if (view()->hasSection('titlebar_actions') && blank(view()->getSection('titlebar_actions'))) {
        $actions_empty = true;
        $actions_base_class .= ' actions-empty';
    }
@endphp

<div
    id="lqd-titlebar"
    {{ $attributes->withoutTwMergeClasses()->twMerge($base_class, $attributes->get('class')) }}
>
    <div {{ $attributes->twMergeFor('container', $container_base_class) }}>
        <div class="flex w-full flex-wrap items-center justify-between rounded-2xl border pb-8 transition-all">

            <div class="lqd-titlebar-row group/titlebar-row flex min-h-24 w-full flex-wrap justify-between">
                @if (view()->hasSection('titlebar_before') || !empty($before))
                    <div {{ $attributes->twMergeFor('before', $before_base_class) }}>
                        @if (view()->hasSection('titlebar_before'))
                            @yield('titlebar_before')
                        @elseif (!empty($before))
                            {{ $before }}
                        @endif
                    </div>
                @else
                    <figure
                        class="-mb-3 flex w-full items-end justify-center px-4 pt-3 lg:w-3/12 xl:w-1/3"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ custom_theme_url('assets/img/robot-2.png') }}"
                            alt="{{ __('Robot') }}"
                            width="147"
                            height="85"
                        />
                    </figure>
                @endif

                <div class="flex w-full items-center justify-center px-4 text-center lg:w-6/12 xl:w-1/3">
                    @if ($has_title)
                        <h1 {{ $attributes->twMergeFor('title', $title_base_class, !$has_subtitle ? 'lg:-mb-8' : '') }}>
                            @yield($title_section_name)
                        </h1>
                    @endif
                </div>

                <div class="relative flex w-full items-start justify-end max-lg:hidden lg:w-3/12 xl:w-1/3">
                    <div class="relative z-2 inline-flex items-center justify-end py-4 ps-5 has-[.actions-empty]:hidden">
                        <x-shape-cutout-2
                            class="max-lg:hidden"
                            position="te"
                        />
                        <div class="relative z-1 grow">
                            @hasSection('titlebar_actions_before')
                                @yield('titlebar_actions_before')
                            @endif

                            @if (view()->hasSection('titlebar_actions'))
                                <div {{ $attributes->twMergeFor('actions', $actions_base_class) }}>
                                    @yield('titlebar_actions')
                                </div>
                            @elseif (!empty($actions))
                                <div {{ $attributes->twMergeFor('actions', $actions_base_class, $actions->attributes->get('class')) }}>
                                    {{ $actions }}
                                </div>
                            @else
                                <div {{ $attributes->twMergeFor('actions', $actions_base_class) }}>
                                    @if (request()->routeIs('dashboard.user.openai.documents.all') && !isset($currfolder))
                                        <x-modal
                                            title="{{ __('New Folder') }}"
                                            disable-modal="{{ $app_is_demo }}"
                                            disable-modal-message="{{ __('This feature is disabled in Demo version.') }}"
                                        >
                                            <x-slot:trigger
                                                class="grow basis-1/2"
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
                                            class="grow basis-1/2 whitespace-nowrap xl:min-w-40"
                                            variant="ghost-shadow"
                                            href="{{ LaravelLocalization::localizeUrl(route('dashboard.user.openai.documents.all')) }}"
                                        >
                                            {{ __('My Documents') }}
                                        </x-button>
                                    @endif
                                    <x-button
                                        class="grow basis-1/2 whitespace-nowrap xl:min-w-40"
                                        href="{{ $generator_link }}"
                                    >
                                        {{ __('New') }}
                                    </x-button>
                                </div>
                            @endif

                            @hasSection('titlebar_actions_after')
                                @yield('titlebar_actions_after')
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($has_subtitle)
                <div class="lqd-titlebar-row group/titlebar-row flex w-full items-center gap-4 max-lg:mt-4">
                    <span class="h-px grow bg-border transition-all"></span>
                    <p {{ $attributes->twMergeFor('subtitle', $subtitle_base_class) }}>
                        @yield('titlebar_subtitle')
                    </p>
                    <span class="h-px grow bg-border transition-all"></span>
                </div>
            @endif
        </div>
    </div>

    @php
        $status_titlebar_after = $titlebar_after_in_nav_col && (!$has_pretitle && !$has_subtitle) && (view()->hasSection('titlebar_after') || !empty($after));
    @endphp

    @if ($status_titlebar_after || (!$titlebar_after_in_nav_col && (($has_pretitle || $has_subtitle) && (view()->hasSection('titlebar_after') || !empty($after)))))
        <div {{ $attributes->twMergeFor('container', $container_base_class) }}>
            <div {{ $attributes->twMergeFor('after', $after_base_class) }}>
                @if (view()->hasSection('titlebar_after'))
                    @yield('titlebar_after')
                @elseif (!empty($after))
                    {{ $after }}
                @endif
            </div>
        </div>
    @endif
</div>
