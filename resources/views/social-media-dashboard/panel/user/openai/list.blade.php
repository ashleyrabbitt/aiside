@php
    if (!function_exists('rgbToHsl')) {
        function rgbToHsl($r, $g, $b)
        {
            $r /= 255;
            $g /= 255;
            $b /= 255;

            $max = max($r, $g, $b);
            $min = min($r, $g, $b);

            $h = $s = $l = ($max + $min) / 2;

            if ($max == $min) {
                $h = $s = 0; // achromatic
            } else {
                $diff = $max - $min;
                $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);
                switch ($max) {
                    case $r:
                        $h = ($g - $b) / $diff + ($g < $b ? 6 : 0);
                        break;
                    case $g:
                        $h = ($b - $r) / $diff + 2;
                        break;
                    case $b:
                        $h = ($r - $g) / $diff + 4;
                        break;
                }
                $h /= 6;
            }

            return [$h, $s, $l];
        }
    }

    if (!function_exists('hslToRgb')) {
        function hslToRgb($h, $s, $l)
        {
            $r = $l;
            $g = $l;
            $b = $l;
            $v = $l <= 0.5 ? $l * (1.0 + $s) : $l + $s - $l * $s;
            if ($v > 0) {
                $m;
                $sv;
                $sextant;
                $fract;
                $vsf;
                $mid1;
                $mid2;

                $m = $l + $l - $v;
                $sv = ($v - $m) / $v;
                $h *= 6.0;
                $sextant = floor($h);
                $fract = $h - $sextant;
                $vsf = $v * $sv * $fract;
                $mid1 = $m + $vsf;
                $mid2 = $v - $vsf;

                switch ($sextant) {
                    case 0:
                        $r = $v;
                        $g = $mid1;
                        $b = $m;
                        break;
                    case 1:
                        $r = $mid2;
                        $g = $v;
                        $b = $m;
                        break;
                    case 2:
                        $r = $m;
                        $g = $v;
                        $b = $mid1;
                        break;
                    case 3:
                        $r = $m;
                        $g = $mid2;
                        $b = $v;
                        break;
                    case 4:
                        $r = $mid1;
                        $g = $m;
                        $b = $v;
                        break;
                    case 5:
                        $r = $v;
                        $g = $m;
                        $b = $mid2;
                        break;
                }
            }
            return [$r * 255, $g * 255, $b * 255];
        }
    }

    if (!function_exists('saturateColor')) {
        function saturateColor($color, $percent)
        {
            // Convert the hex color to RGB
            $color = str_replace('#', '', $color);
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));

            // Convert RGB to HSL
            [$h, $s, $l] = rgbToHsl($r, $g, $b);

            // Increase the saturation
            $s += $percent / 100;
            $s = max(0, min(1, $s));

            // Convert HSL back to RGB
            [$r, $g, $b] = hslToRgb($h, $s, $l);

            // Convert the RGB values back to hex
            $r = dechex(round($r));
            $g = dechex(round($g));
            $b = dechex(round($b));

            // Ensure each color component is two characters long
            $r = str_pad($r, 2, '0', STR_PAD_LEFT);
            $g = str_pad($g, 2, '0', STR_PAD_LEFT);
            $b = str_pad($b, 2, '0', STR_PAD_LEFT);

            // Concatenate the color components
            $saturatedColor = '#' . $r . $g . $b;

            return $saturatedColor;
        }
    }

    if (!function_exists('darkenColor')) {
        function darkenColor($color, $percent)
        {
            // Convert the hex color to RGB
            $color = str_replace('#', '', $color);
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));

            // Reduce the RGB values by the percentage
            $r = max(0, min(255, $r - ($r * $percent) / 100));
            $g = max(0, min(255, $g - ($g * $percent) / 100));
            $b = max(0, min(255, $b - ($b * $percent) / 100));

            // Convert the RGB values back to hex
            $r = dechex($r);
            $g = dechex($g);
            $b = dechex($b);

            // Ensure each color component is two characters long
            $r = str_pad($r, 2, '0', STR_PAD_LEFT);
            $g = str_pad($g, 2, '0', STR_PAD_LEFT);
            $b = str_pad($b, 2, '0', STR_PAD_LEFT);

            // Concatenate the color components
            $darkerColor = '#' . $r . $g . $b;

            return $darkerColor;
        }
    }
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Smart Templates'))
@section('titlebar_subtitle', __('Generate stunning social media content with ease using smart templates.'))

@section('titlebar_after')
    @php
        $filter_check = [];
        foreach ($list as $item) {
            if ($item->active != 1) {
                continue;
            }
            if ($item->filters) {
                foreach (explode(',', $item->filters) as $filter) {
                    $filter_check[] = $filter;
                }
            }
        }
        $filter_check = array_unique($filter_check);
    @endphp
    <ul
        class="lqd-filter-list mt-2 flex scroll-mt-6 flex-wrap items-center gap-x-4 gap-y-2 text-heading-foreground max-sm:gap-3"
        id="lqd-generators-filter-list"
    >
        <li>
            <x-button
                class="lqd-filter-btn inline-flex rounded-full px-2.5 py-0.5 text-2xs leading-tight transition-colors hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground/5"
                data-filter="all"
                tag="button"
                type="button"
                name="filter"
                variant="ghost"
                x-data="{}"
                ::class="$store.generatorsFilter.filter === 'all' && 'active'"
                @click="$store.generatorsFilter.changeFilter('all')"
            >
                {{ __('All') }}
            </x-button>
        </li>
        <li>
            <x-button
                class="lqd-filter-btn inline-flex rounded-full px-2.5 py-0.5 text-2xs leading-tight transition-colors hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground/5"
                data-filter="favorite"
                tag="button"
                type="button"
                name="filter"
                variant="ghost"
                x-data="{}"
                ::class="$store.generatorsFilter.filter === 'favorite' && 'active'"
                @click="$store.generatorsFilter.changeFilter('favorite')"
            >
                {{ __('Favorite') }}
            </x-button>
        </li>

        @foreach ($filters->sortBy('name') as $filter)
            @if (in_array($filter->name, $filter_check))
                <li>
                    <x-button
                        class="lqd-filter-btn inline-flex rounded-full px-2.5 py-0.5 text-2xs leading-tight transition-colors hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground/5"
                        data-filter="{{ $filter->name }}"
                        tag="button"
                        type="button"
                        name="filter"
                        variant="ghost"
                        x-data="{}"
                        ::class="$store.generatorsFilter.filter === '{{ $filter->name }}' && 'active'"
                        @click="$store.generatorsFilter.changeFilter('{{ $filter->name }}')"
                    >
                        {{ __(str()->ucfirst($filter->name)) }}
                    </x-button>
                </li>
            @endif
        @endforeach
    </ul>
@endsection

@section('content')
    <div
        class="lqd-generators-container"
        id="lqd-generators-container"
    >
        <div
            class="lqd-generators-list grid grid-cols-2 gap-6 lg:grid-cols-3 lg:gap-10 xl:grid-cols-4"
            id="lqd-generators-list"
        >
            <h4
                class="col-span-full m-0 hidden items-center gap-7"
                data-filter="favorite"
                x-data="generatorItem"
                :class="{ hidden: $store.generatorsFilter.filter !== 'favorite', flex: $store.generatorsFilter.filter === 'favorite' }"
            >
                {{ __('Favorite') }}
                <span class="h-px grow bg-border"></span>
            </h4>

            @foreach ($filters as $filter)
                <h4
                    class="col-span-full m-0 flex items-center gap-7"
                    data-filter="{{ $filter->name }}"
                    x-data="generatorItem"
                    :class="{ hidden: isHidden, flex: !isHidden }"
                >
                    {{ __(str()->ucfirst($filter->name)) }}
                    <span class="h-px grow bg-border"></span>
                </h4>

                @foreach ($list as $item)
                    @if ($item->active != 1 || str()->startsWith($item->slug, 'ai_') || !in_array($filter->name, explode(',', $item->filters)))
                        @continue
                    @endif
                    <x-generator-item :$item />
                @endforeach
            @endforeach
        </div>
    </div>

    <svg
        class="absolute size-0"
        width="0"
        height="0"
    >
        <defs>
            <linearGradient
                id="cutout-gradient"
                x1="0"
                y1="47"
                x2="90"
                y2="47"
                gradientUnits="userSpaceOnUse"
            >
                <stop stop-color="hsl(var(--gradient-via))" />
                <stop
                    offset="1"
                    stop-color="hsl(var(--gradient-to))"
                />
            </linearGradient>
        </defs>
    </svg>

    <svg
        class="absolute size-0"
        width="0"
        height="0"
    >
        <defs>
            <linearGradient
                id="icons-gradient-1"
                x1="0"
                y1="0"
                x2="1"
                y2="1"
            >
                <stop stop-color="#EB6434" />
                <stop
                    offset="0.5"
                    stop-color="#BB2D9F"
                />
                <stop
                    offset="1"
                    stop-color="#BB802D"
                />
            </linearGradient>
        </defs>
    </svg>
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/js/panel/openai_list.js') }}"></script>
@endpush
