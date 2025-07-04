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
    } elseif ($item->type === 'voiceover' || $item->type === 'audio' || $item->type === 'isolator' || $item->type === 'image') {
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

<x-card
    class:body="static flex flex-col grow"
    data-filter="{{ $item_filters }}"
    @class([
        'lqd-generator-item group relative flex w-full p-0',
        'border-t-0 border-s-0 border-b border-e' =>
            Theme::getSetting('defaultVariations.card.variant', 'outline') ===
            'outline',
        'hidden' =>
            null !== request()->query('filter') &&
            !str()->contains($item_filters, request()->query('filter')),
    ])
    size="none"
    roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
    x-data="{}"
    ::class="{ 'hidden': $store.generatorsFilter.filter !== 'all' && ('{{ $item_filters }}').search($store.generatorsFilter.filter) < 0 }"
>
    <div class="px-8 pb-5 pt-8">
        <div class="flex items-center justify-between gap-2">
            <h4 class="relative mb-3.5 inline-block text-lg font-medium">
                {{ __($item->title) }}
                <span class="inline-block -translate-x-1 align-middle opacity-0 transition-all group-hover:translate-x-0 group-hover:!opacity-100 rtl:-scale-x-100">
                    <x-tabler-chevron-right class="size-5" />
                </span>
            </h4>

            <x-lqd-icon
                class="lqd-generator-item-icon mb-5 shrink-0 bg-transparent shadow-none group-hover:scale-110"
                size="none"
                style="--color: {{ $item->color }}"
                active-badge
                active-badge-condition="{{ $item->active == 1 }}"
            >
                <span
                    class="flex size-10 items-center justify-center transition-transform group-hover:scale-110"
                    style="color: var(--color)"
                >
                    @if ($item->image !== 'none')
                        {!! html_entity_decode($item->image) !!}
                    @endif
                </span>
            </x-lqd-icon>
        </div>

        <div class="lqd-generator-item-info">
            <p class="m-0">
                {{ __($item->description) }}
            </p>
        </div>
    </div>

    <div class="mt-auto flex justify-between gap-x-3.5 gap-y-2 border-t border-border px-8 py-6">
        <a
            class="flex items-center gap-1 text-xs text-heading-foreground"
            href="{{ $overlay_link_href }}"
        >
            @lang('Learn More')
            <x-tabler-chevron-right class="size-4" />
        </a>
        @if ($item->active == 1 && !$upgrade)
            <x-favorite-button
                id="{{ $item->id }}"
                is-favorite="{{ isFavorited($item->id) }}"
                update-url="/dashboard/user/openai/favorite"
            />
        @endif
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
