@php
    use App\Domains\Entity\EntityStats;
    $wordModels = EntityStats::word();
    $imageModels = EntityStats::image();

    $team = auth()->user()->getAttribute('team');
    $teamManager = auth()->user()->getAttribute('teamManager');
@endphp

@if ($team)
    <div class="flex flex-wrap items-center justify-between gap-y-4 text-base font-medium leading-normal">
        <div class="lg-w/5-12 w-full md:w-1/2">
            <h2 class="mb-[1em]">{{ __('Active Workspace:') }}</h2>
            <p class="mb-4 font-bold">
                {{ $teamManager->name . ' ' . $teamManager->surname }}
                <x-badge class="ms-2 text-2xs">
                    @lang('Team Manager')
                </x-badge>
            </p>

            @lang("You have the Team plan which has a remaining balance of <strong class='font-bold '>:word</strong> words and <strong class='font-bold '>:image</strong> images. You can contact your team manager if you need more credits.", ['word' => $wordModels->totalCredits(), 'image' => $imageModels->totalCredits()])
        </div>
        <div class="ms-auto w-full md:w-1/2">
            <div class="relative">
                <div
                    class="relative [&_.apexcharts-canvas]:mx-auto [&_.apexcharts-canvas]:max-w-full [&_.apexcharts-legend-text]:!m-0 [&_.apexcharts-legend-text]:!pe-2 [&_.apexcharts-legend-text]:ps-2 [&_.apexcharts-legend-text]:!text-foreground [&_.apexcharts-svg]:max-w-full"
                    id="chart-credit"
                ></div>
                <h3 class="group absolute left-1/2 top-[calc(50%-5px)] m-0 -translate-x-1/2 text-center text-xs font-normal">
                    <strong class="block text-[2em] font-semibold leading-none max-sm:text-[1.5em]">
                        @formatNumberShort($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
                        @if (!$wordModels->checkIfThereUnlimited())
                            <span
                                class="pointer-events-none invisible absolute bottom-full left-1/2 mb-1 -translate-x-1/2 translate-y-1 scale-90 rounded-md bg-heading-foreground/10 px-2 py-1 text-base leading-none text-heading-foreground opacity-0 blur-md backdrop-blur-lg transition-all group-hover:visible group-hover:translate-y-0 group-hover:scale-100 group-hover:opacity-100 group-hover:blur-0"
                            >
                                @formatNumber($wordModels->totalCredits())
                            </span>
                        @endif
                    </strong>
                    {{ __('Words') }}
                </h3>
            </div>
            <x-credit-list
                class="mt-4"
                showType="button"
                modal-trigger-pos="block"
                expanded-modal-trigger
                modal-trigger-variant="ghost-shadow"
            />
        </div>
    </div>
@else
    <h4 class="mb-8 flex items-center gap-4">
        <span class="h-px grow bg-border"></span>
        @lang('Your Plan')
        <span class="h-px grow bg-border"></span>
    </h4>

    <div class="relative mb-5">
        <div
            class="relative [&_.apexcharts-canvas]:mx-auto [&_.apexcharts-canvas]:max-w-full [&_.apexcharts-legend-text]:!m-0 [&_.apexcharts-legend-text]:!pe-2 [&_.apexcharts-legend-text]:ps-2 [&_.apexcharts-legend-text]:!text-foreground [&_.apexcharts-svg]:max-w-full"
            id="chart-credit"
        ></div>
    </div>

    <p class="mb-6 font-medium leading-relaxed text-heading-foreground/60">
        @if (auth()->user()->activePlan() !== null)
            {{ __('You have currently') }}
            <strong class="text-heading-foreground">{{ getSubscriptionName() }}</strong>
            {{ __('plan.') }}
            {{ __('Will refill automatically in') }} {{ getSubscriptionDaysLeft() }} {{ __('Days.') }}
            {{ checkIfTrial() === true ? __('You are in Trial time.') : '' }}
        @else
            {{ __('You have no subscription at the moment. Please select a subscription plan or a token pack.') }}
        @endif

        @if ($setting->feature_ai_image)
            {{ __('Total') }}
            <strong class="text-heading-foreground">
                @formatNumber($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
            </strong>
            {{ __('word and') }}
            <strong class="text-heading-foreground">
                @formatNumber($imageModels->checkIfThereUnlimited() ? __('Unlimited') : $imageModels->totalCredits())

            </strong>
            {{ __('image tokens left.') }}
        @else
            {{ __('Total') }}
            <strong class="text-heading-foreground">
                @formatNumber($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
            </strong>
            {{ __('tokens left.') }}
        @endif
    </p>

    <div class="flex flex-wrap items-center justify-center gap-4">
        <x-credit-list
            showType="button"
            modal-trigger-pos="block"
            expanded-modal-trigger
            modal-trigger-variant="ghost-shadow"
        />

        <x-button
            class="hover:bg-primary"
            data-name="{{ \App\Enums\Introduction::SELECT_PLAN }}"
            variant="ghost-shadow"
            href="{{ LaravelLocalization::localizeUrl(route('dashboard.user.payment.subscription')) }}"
        >
            <x-tabler-plus class="size-4" />
            {{ __('Select a Plan') }}
        </x-button>

        @if (getSubscriptionStatus())
            <x-button
                variant="danger"
                onclick="return confirm('Are you sure to cancel your plan? You will lose your remaining usage.');"
                href="{{ LaravelLocalization::localizeUrl(route('dashboard.user.payment.cancelActiveSubscription')) }}"
            >
                <x-tabler-circle-minus class="size-4" />
                {{ __('Cancel My Plan') }}
            </x-button>
        @endif
    </div>
@endif

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script>
        // @formatter:off
        document.addEventListener("DOMContentLoaded", function() {
            "use strict";

            @php
                if ($wordModels->checkIfThereUnlimited()) {
                    $remainingPercentage = 100;
                } elseif ($total_words === 0) {
                    $remainingPercentage = $wordModels->totalCredits();
                } else {
                    $remainingPercentage = round(($wordModels->totalCredits() / $total_words) * 100, 2);
                }
            @endphp

            const remainingPercentage = {{ $remainingPercentage }};
            const usedPercentage = 100 - remainingPercentage;
            const options = {
                series: [remainingPercentage],
                labels: [@json(__('Remaining')), @json(__('Used'))],
                colors: ['hsl(var(--gradient-from))', 'hsl(var(--heading-foreground)/5%)'],
                tooltip: {
                    style: {
                        color: '#ffffff',
                    },
                },
                chart: {
                    type: 'radialBar',
                    height: 250,
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    fontFamily: 'inherit',
                    markers: {
                        size: 10,
                    },
                },
                dataLabels: {},
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        hollow: {
                            margin: 0,
                            size: "70%"
                        },
                        track: {
                            show: true,
                            background: 'hsl(var(--heading-foreground)/5%)',
                        },
                        dataLabels: {
                            name: {
                                fontFamily: 'inherit',
                                fontSize: "16px",
                                fontWeight: 400,
                                color: 'hsl(var(--heading-foreground)/50%)',
                                offsetY: -10
                            },
                            value: {
                                fontFamily: 'inherit',
                                fontSize: '31px',
                                fontWeight: 700,
                                color: 'hsl(var(--heading-foreground))',
                                offsetY: -50
                            }
                        }
                    },
                },
                grid: {
                    padding: {
                        bottom: -100
                    }
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shade: "light",
                        type: "horizontal",
                        gradientToColors: ["hsl(var(--gradient-via))", "hsl(var(--gradient-to))"],
                        stops: [0, 50, 100]
                    }
                },
                stroke: {
                    lineCap: "round"
                },
                responsive: [{
                    breakpoint: 480,
                    options: {

                        grid: {
                            padding: {
                                bottom: -100
                            }
                        },
                    }
                }],
            };
            window.ApexCharts && (new ApexCharts(document.getElementById('chart-credit'), options)).render();
        });
        // @formatter:on
    </script>
@endpush
