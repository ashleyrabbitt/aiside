@php
    $auth = Auth::user();
    $plan = $auth->activePlan();
    $plan_type = 'regular';
    $upgrade = false;
    $overlay_link_href = '';
    $overlay_link_label = 'Create Workbook';

    if ($plan != null) {
        $plan_type = strtolower($plan->plan_type);
    }

    if ($app_is_demo) {
        if ($item->premium == 1 && $plan_type === 'regular') {
            $upgrade = true;
        }
    } else {
        if (!$auth->isAdmin() && $item->premium == 1 && $plan_type === 'regular') {
            $upgrade = true;
        }
    }

    if ($upgrade) {
        $overlay_link_href = route('dashboard.user.payment.subscription');
        $overlay_link_label = 'Upgrade';
    } elseif ($item->type === 'text' || $item->type === 'code') {
        if ($item->slug === 'ai_article_wizard_generator') {
            $overlay_link_href = route('dashboard.user.openai.articlewizard.new');
        } else {
            $overlay_link_href = route('dashboard.user.openai.generator.workbook', $item->slug);
        }
    } elseif ($item->type === 'voiceover' || $item->type === 'audio' || $item->type === \App\Domains\Entity\Enums\EntityEnum::ISOLATOR->value || $item->type === 'image') {
        $overlay_link_href = route('dashboard.user.openai.generator', $item->slug);
        $overlay_link_label = 'Create';
    } else {
        $overlay_link_href = '#';
        $overlay_link_label = 'No Tokens Left';
    }

    $item_filters = $item->filters;

    if (isFavorited($item->id)) {
        $item_filters .= ',favorite';
    }
@endphp

<div
    class="lqd-generator-item group relative flex before:absolute before:-inset-[3px] before:rounded-2xl before:bg-gradient-to-r before:from-gradient-from before:via-gradient-via before:to-gradient-to before:opacity-0 before:transition-all hover:before:opacity-100"
    data-filter="{{ $item_filters }}"
    x-data="generatorItem"
    :class="{ hidden: isHidden, flex: !isHidden }"
    @favorite-toggled="updateDataFilter($event.detail.id, $event.detail.isFavorite)"
>
    <x-card
        class:body="static px-7 pt-7 pb-8"
        class="relative z-1 w-full grow rounded-[13px] bg-background transition-all group-hover:border-background"
        size="none"
        roundness="none"
    >
        @if ($item->active == 1 && !$upgrade)
            <div class="absolute end-0 top-0 inline-flex size-[72px] justify-end">
                <x-shape-cutout-2
                    class="transition-none before:absolute before:-bottom-3 before:-end-1.5 before:-top-1.5 before:start-0 before:z-0 before:bg-background group-hover:!border-transparent [&_.lqd-cutout-2-border-be]:w-[5px]"
                />
                <svg
                    class="absolute end-[-4px] top-[-4.5px] opacity-0 transition-all group-hover:opacity-100"
                    width="93"
                    height="92"
                    viewBox="0 0 91.5 92"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    stroke="url(#cutout-gradient)"
                    stroke-width="3"
                >
                    <path d="M91 92C91 82.0589 82.9411 74 73 74H36C26.0589 74 18 65.9411 18 56V20C18 10.0589 9.94112 2 0 2" />
                </svg>
                <x-favorite-button
                    class="size-[55px] rounded-full border !bg-transparent hover:!bg-primary"
                    id="{{ $item->id }}"
                    is-favorite="{{ isFavorited($item->id) }}"
                    update-url="/dashboard/user/openai/favorite"
                />
            </div>
        @endif

        <span class="lqd-generator-item-icon mb-3 inline-flex text-4xl/none transition-transform group-hover:scale-110 [&_svg]:h-auto [&_svg]:max-h-[1em] [&_svg]:w-[1em]">
            @if ($item->image !== 'none')
                {!! html_entity_decode($item->image) !!}
            @endif
        </span>

        <div class="lqd-generator-item-info">
            <h4 class="relative mb-3 inline-block text-lg lg:me-20">
                {{ __($item->title) }}
                <span
                    class="absolute start-[calc(100%+0.35rem)] top-1/2 inline-block -translate-x-1 -translate-y-1/2 align-bottom opacity-0 transition-all group-hover:translate-x-0 group-hover:!opacity-100 rtl:-scale-x-100"
                >
                    <x-tabler-chevron-right class="size-5" />
                </span>
            </h4>
            <p class="m-0 text-xs">
                {{ __($item->description) }}
            </p>
        </div>

        @if ($item->active == 1)
            <div @class([
                'absolute left-0 top-0 z-2 h-full w-full transition-all',
                'bg-background/75' => $upgrade || $overlay_link_href === '#',
            ])>
                <a
                    @class([
                        'absolute left-0 top-0 inline-block h-full w-full overflow-hidden',
                        'flex items-center justify-center font-medium' =>
                            $upgrade || $overlay_link_href === '#',
                        '-indent-[99999px]' => !$upgrade && $overlay_link_href !== '#',
                    ])
                    href="{{ $overlay_link_href }}"
                >
                    @if ($upgrade || $overlay_link_href === '#')
                        <span @class([
                            'inline-block rounded-md px-2 py-0.5',
                            'absolute end-4 top-4 bg-cyan-100 text-black' => $upgrade,
                            'bg-foreground text-background' => $overlay_link_href === '#',
                        ])>
                    @endif
                    {{ __($overlay_link_label) }}
                    @if ($upgrade)
                        </span>
                    @endif
                </a>
            </div>
        @endif
    </x-card>
</div>
