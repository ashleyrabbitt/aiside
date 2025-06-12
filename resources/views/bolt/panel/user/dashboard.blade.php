@php
    $plan = Auth::user()->activePlan();
    $plan_type = 'regular';
    // $team = Auth::user()->getAttribute('team');
    $teamManager = Auth::user()->getAttribute('teamManager');

    if ($plan != null) {
        $plan_type = strtolower($plan->plan_type);
    }

    $titlebar_links = [
        [
            'label' => 'All',
            'link' => '#all',
        ],
        [
            'label' => 'AI Assistant',
            'link' => '#all',
        ],
        [
            'label' => 'Your Plan',
            'link' => '#plan',
        ],
        [
            'label' => 'Team Members',
            'link' => '#team',
        ],
        [
            'label' => 'Recent',
            'link' => '#recent',
        ],
        [
            'label' => 'Documents',
            'link' => '#documents',
        ],
        [
            'label' => 'Templates',
            'link' => '#templates',
        ],
        [
            'label' => 'Overview',
            'link' => '#all',
        ],
    ];

    $premium_features = \App\Models\OpenAIGenerator::query()
				->where('active', 1)
				->where('premium', 1)
				->get()->pluck('title')->toArray();
	$user_is_premium = false;
	$plan = auth()->user()?->relationPlan;
	if ($plan) {
		$planType = strtolower($plan->plan_type ?? 'all');
		if ($plan->plan_type === 'all' || $plan->plan_type === 'premium') {
			$user_is_premium = true;
		}
	}

    $style_string = '';

    if (setting('announcement_background_color')) {
        $style_string .= '.lqd-card.lqd-announcement-card { background-color: ' . setting('announcement_background_color') . ';}';
    }

    if (setting('announcement_background_image')) {
        $style_string .= '.lqd-card.lqd-announcement-card { background-image: url(' . setting('announcement_background_image') . '); }';
    }

    if (setting('announcement_background_color_dark')) {
        $style_string .= '.theme-dark .lqd-card.lqd-announcement-card { background-color: ' . setting('announcement_background_color_dark') . ';}';
    }

    if (setting('announcement_background_image_dark')) {
        $style_string .= '.theme-dark .lqd-card.lqd-announcement-card { background-image: url(' . setting('announcement_background_image_dark') . '); }';
    }
@endphp

@extends('panel.layout.app', ['disable_tblr' => true, 'has_sidebar' => true])
@section('title', __('Dashboard'))
@section('titlebar_title')
    {{ __('Welcome') }}, {{ auth()->user()->name }}.
@endsection

@if (filled($style_string))
    @push('css')
        <style>
            {{ $style_string }}
        </style>
    @endpush
@endif

@section('content')
    <div class="lqd-titlebar-secondary mb-20 text-center lg:-mx-4">
        <div
            class="bg-cover bg-center px-4 pb-20 pt-8"
            style="background-image: url({{ custom_theme_url('assets/bg/titlebar-bg.jpg') }})"
        >
            <h1 class="m-0 flex items-center justify-center gap-3 text-center text-black">
                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M15.8877 11.0742C10.6901 11.9289 9.76613 13.0146 9.07312 19.3441C9.05002 19.552 8.74972 19.552 8.72662 19.3441C8.03361 13.0146 7.1096 11.952 1.91203 11.0742C1.70413 11.0511 1.70413 10.7508 1.91203 10.7277C7.1096 9.87298 8.03361 8.81036 8.72662 2.48088C8.74972 2.27298 9.05002 2.27298 9.07312 2.48088C9.76613 8.81036 10.6901 9.84988 15.8877 10.7277C16.0725 10.7508 16.0725 11.028 15.8877 11.0742Z"
                    />
                    <path
                        d="M18.1053 3.77447C16.4883 4.09787 16.0263 4.62918 15.7491 6.5234C15.726 6.73131 15.4256 6.73131 15.4025 6.5234C15.1253 4.62918 14.6633 4.09787 13.0463 3.75137C12.8615 3.70517 12.8615 3.45106 13.0463 3.40486C14.6402 3.08146 15.1253 2.55015 15.4025 0.655927C15.4256 0.448024 15.726 0.448024 15.7491 0.655927C16.0263 2.55015 16.4883 3.08146 18.1053 3.42796C18.2901 3.47416 18.2901 3.75137 18.1053 3.77447Z"
                    />
                </svg>
                @lang('Hey, How can I help you?')
            </h1>
        </div>

        <div class="container relative z-2 -mt-10 flex flex-col">
            <div
                class="mx-auto flex w-full max-w-[650px] items-center gap-5 rounded-2xl bg-background/40 px-4 py-3 shadow-[0_4px_10px_hsl(0_0%_0%/4%)] backdrop-blur-2xl dark:shadow-[0_4px_20px_rgba(255,255,255,0.05)]">
                <x-header-search
                    class="w-full [&_.header-search-border]:rounded-2xl"
                    class:input="bg-background border-none h-11 placeholder:text-heading-foreground rounded-xl text-foreground focus:text-heading-foreground"
                    size="lg"
                    in-content
                />
                <x-button
                    class="group text-[12px] font-medium text-heading-foreground"
                    variant="link"
                    href="{{ $setting->feature_ai_advanced_editor ?  (route('dashboard.user.generator.index')) :  (route('dashboard.user.openai.list')) }}"
                >
                    <span class="sr-only">
                        @lang('Create a Blank Document')
                    </span>
                    <span
                        class="inline-flex size-[30px] items-center justify-center rounded-full bg-background shadow transition-all group-hover:scale-110 group-hover:bg-heading-foreground group-hover:text-header-background"
                    >
                        <x-tabler-plus class="size-4" />
                    </span>
                </x-button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="flex flex-wrap justify-between gap-8 py-5">
            @if ($ongoingPayments != null)
                <div class="w-full">
                    @include('panel.user.finance.ongoingPayments')
                </div>
            @endif

            <div
                class="grid w-full grid-cols-1 gap-6 xl:grid-cols-2"
                id="all"
            >
                @if (setting('announcement_active', 0) && !auth()->user()->dash_notify_seen)
                    <div
                        class="lqd-announcement"
                        x-data="{ show: true }"
                        x-ref="announcement"
                    >
                        <script>
                            const announcementDismissed = localStorage.getItem("lqd-announcement-dismissed");
                            if (announcementDismissed) {
                                document.querySelector(".lqd-announcement").style.display = "none";
                            }
                        </script>

                        <x-card
                            class="lqd-announcement-card relative bg-cover bg-center"
                            class:body="pt-8"
                            size="none"
                            x-ref="announcementCard"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="px-10">
                                    <h3 class="mb-5 text-[21px]">
                                        @lang(setting('announcement_title', 'Welcome'))
                                    </h3>
                                    <p class="mb-8 text-base">
                                        @lang(setting('announcement_description', 'We are excited to have you here. Explore the marketplace to find the best AI models for your needs.'))
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <x-button
                                            class="font-medium"
                                            href="{{ setting('announcement_url', '#') }}"
                                        >
                                            <x-tabler-plus class="size-4" />
                                            {{ setting('announcement_button_text', 'Try it Now') }}
                                        </x-button>
                                        <x-button
                                            class="font-medium"
                                            href="javascript:void(0)"
                                            variant="ghost-shadow"
                                            hover-variant="danger"
                                            @click.prevent="{{ $app_is_demo ? 'toastr.info(\'This feature is disabled in Demo version.\')' : ' dismiss()' }}"
                                        >
                                            @lang('Dismiss')
                                        </x-button>
                                    </div>
                                </div>
                                <div class="w-full">
                                    @if (setting('announcement_image_dark'))
                                        <img
                                            class="announcement-img announcement-img-dark peer hidden shrink-0 dark:block"
                                            src="{{ setting('announcement_image_dark', '/upload/images/speaker.png') }}"
                                            alt="@lang(setting('announcement_title', 'Welcome to MagicAI!'))"
                                        >
                                    @endif
                                    <img
                                        class="announcement-img announcement-img-light shrink-0 dark:peer-[&.announcement-img-dark]:hidden"
                                        src="{{ setting('announcement_image', '/upload/images/speaker.png') }}"
                                        alt="@lang(setting('announcement_title', 'Welcome to MagicAI!'))"
                                    >
                                </div>
                            </div>
                        </x-card>
                    </div>
                @endif

                @if (!$user_is_premium)
                    <x-card
                        class="relative flex w-full flex-col justify-center bg-cover bg-top text-center"
                        class:body="flex flex-col only:grow-0 py-8 xl:px-20 static"
                    >
                        <figure
                            class="pointer-events-none absolute start-0 top-0 z-0 h-full w-full overflow-hidden"
                            aria-hidden="true"
                        >
                            <img
                                class="w-full blur-[3px]"
                                src="{{ custom_theme_url('/assets/img/bg/premium-card-bg.jpg') }}"
                                alt="{{ __('Premium Features') }}"
                                width="1244"
                                height="481"
                            />
                        </figure>
                        <div class="relative z-1 flex flex-col">
                            <h4 class="mb-5 text-lg">
                                @lang('Premium Advantages')
                            </h4>
                            <p class="mb-8 text-xs font-medium opacity-60">
                                @lang('Upgrade your plan to unlock new AI capabilities.')
                            </p>
                            <ul class="mb-11 space-y-4 self-center text-xs font-medium text-heading-foreground">
                                @foreach ($premium_features as $feature)
                                    <li class="flex items-center gap-3.5">
                                        <svg
                                            width="16"
                                            height="16"
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M2.09635 7.37072C1.80296 7.37154 1.51579 7.45542 1.26807 7.61264C1.02035 7.76986 0.822208 7.994 0.696564 8.25914C0.570919 8.52427 0.522908 8.81956 0.558084 9.11084C0.59326 9.40212 0.710186 9.67749 0.895335 9.9051L4.84228 14.7401C4.98301 14.9148 5.1634 15.0535 5.36847 15.1445C5.57353 15.2355 5.79736 15.2763 6.02136 15.2635C6.50043 15.2377 6.93295 14.9815 7.20871 14.5601L15.4075 1.35593C15.4089 1.35373 15.4103 1.35154 15.4117 1.34939C15.4886 1.23127 15.4637 0.997192 15.3049 0.850142C15.2613 0.809761 15.2099 0.778736 15.1538 0.75898C15.0977 0.739223 15.0382 0.731153 14.9789 0.735266C14.9196 0.739379 14.8618 0.755589 14.809 0.782896C14.7562 0.810204 14.7095 0.848031 14.6719 0.894048C14.669 0.897666 14.6659 0.90123 14.6628 0.904739L6.39421 10.247C6.36275 10.2826 6.32454 10.3115 6.28179 10.3322C6.23905 10.3528 6.19263 10.3648 6.14522 10.3674C6.09782 10.3699 6.05038 10.363 6.00565 10.3471C5.96093 10.3312 5.91982 10.3065 5.88471 10.2746L3.14051 7.77735C2.8555 7.51608 2.48299 7.37102 2.09635 7.37072Z"
                                                fill="url(#paint0_linear_9208_560_{{ $loop->index }})"
                                            />
                                            <defs>
                                                <linearGradient
                                                    id="paint0_linear_9208_560_{{ $loop->index }}"
                                                    x1="0.546875"
                                                    y1="3.69866"
                                                    x2="12.7738"
                                                    y2="14.7613"
                                                    gradientUnits="userSpaceOnUse"
                                                >
                                                    <stop stop-color="#82E2F4" />
                                                    <stop
                                                        offset="0.502"
                                                        stop-color="#8A8AED"
                                                    />
                                                    <stop
                                                        offset="1"
                                                        stop-color="#6977DE"
                                                    />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <x-button
                                class="py-5 text-[18px] font-bold shadow-[0_14px_44px_rgba(0,0,0,0.07)] hover:shadow-2xl hover:shadow-primary/30 dark:hover:bg-primary"
                                href="{{  (route('dashboard.user.payment.subscription')) }}"
                                variant="ghost-shadow"
                            >
                                <span
                                    class="bg-gradient-to-r from-[#82E2F4] via-[#8A8AED] to-[#6977DE] bg-clip-text text-transparent group-hover:from-white group-hover:via-white group-hover:to-white/80"
                                >
                                    @lang('Upgrade Your Plan')
                                </span>
                            </x-button>
                        </div>
                    </x-card>
                @endif

                <x-card
                    class="w-full"
                    id="plan"
                    size="lg"
                >
                    @include('panel.user.finance.subscriptionStatus')
                </x-card>

                @if (showTeamFunctionality())
                    <x-card
                        class="w-full"
                        id="team"
                        size="lg"
                    >
                        @if ($team)
                            <p class="mb-6 text-[21px]/[1.33em] font-medium text-heading-foreground lg:w-9/12">
                                @lang('Add your team members’ email address to start collaborating.')
                            </p>
                            <figure class="mb-8">
                                <img
                                    class="mx-auto"
                                    width="137"
                                    height="116"
                                    src="{{ custom_theme_url('assets/img/team/team.png') }}"
                                    alt="Team"
                                >
                            </figure>
                            <form
                                class="flex flex-col gap-3"
                                action="{{ route('dashboard.user.team.invitation.store', $team->id) }}"
                                method="post"
                            >
                                @csrf
                                <input
                                    type="hidden"
                                    name="team_id"
                                    value="{{ $team?->id }}"
                                >
                                <x-forms.input
                                    class="min-h-12 rounded"
                                    id="email"
                                    size="lg"
                                    type="email"
                                    name="email"
                                    placeholder="{{ __('Email address') }}"
                                    required
                                >
                                    <x-slot:icon>
                                        <x-tabler-mail class="absolute end-3 top-1/2 size-5 -translate-y-1/2" />
                                    </x-slot:icon>
                                </x-forms.input>
                                @if ($app_is_demo)
                                    <x-button onclick="return toastr.info('This feature is disabled in Demo version.')">
                                        @lang('Invite Friends')
                                    </x-button>
                                @else
                                    <x-button
                                        data-name="{{ \App\Enums\Introduction::AFFILIATE_SEND }}"
                                        type="submit"
                                    >
                                        @lang('Invite Friends')
                                    </x-button>
                                @endif
                            </form>
                        @else
                            <h3 class="mb-6 text-[21px]">
                                {{ __('How it Works') }}
                            </h3>

                            <ol class="mb-12 flex flex-col gap-4 text-heading-foreground">
                                <li>
                                    <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                                        1
                                    </span>
                                    {!! __('You <strong>send your invitation link</strong> to your friends.') !!}
                                </li>
                                <li>
                                    <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                                        2
                                    </span>
                                    {!! __('<strong>They subscribe</strong> to a paid plan by using your refferral link.') !!}
                                </li>
                                <li>
                                    <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                                        3
                                    </span>
                                    @if ($is_onetime_commission)
                                        {!! __('From their first purchase, you will begin <strong>earning one-time commissions</strong>.') !!}
                                    @else
                                        {!! __('From their first purchase, you will begin <strong>earning recurring commissions</strong>.') !!}
                                    @endif
                                </li>
                            </ol>

                            <form
                                class="flex flex-col gap-3"
                                id="send_invitation_form"
                                onsubmit="return sendInvitationForm();"
                            >
                                <x-forms.input
                                    class:label="text-heading-foreground"
                                    id="to_mail"
                                    label="{{ __('Affiliate Link') }}"
                                    size="sm"
                                    type="email"
                                    name="to_mail"
                                    placeholder="{{ __('Email address') }}"
                                    required
                                >
                                    <x-slot:icon>
                                        <x-tabler-mail class="absolute end-3 top-1/2 size-5 -translate-y-1/2" />
                                    </x-slot:icon>
                                </x-forms.input>

                                <x-button
                                    class="w-full rounded-xl"
                                    id="send_invitation_button"
                                    type="submit"
                                    form="send_invitation_form"
                                >
                                    {{ __('Send') }}
                                </x-button>
                            </form>
                        @endif
                    </x-card>
                @endif

                <div
                    class="grow basis-full md:basis-0"
                    id="documents"
                >
                    <x-card size="none">
                        <x-slot:head>
                            <h2 class="m-0">
                                {{ __('Documents') }}
                            </h2>
                        </x-slot:head>
                        @foreach (Auth::user()->openai()->with('generator')->take(4)->get() as $entry)
                            @if ($entry->generator != null)
                                <x-documents.item :$entry />
                            @endif
                        @endforeach
                    </x-card>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @includeFirst(['onboarding::include.introduction', 'panel.admin.onboarding.include.introduction', 'vendor.empty'])
    @includeFirst(['onboarding-pro::include.introduction', 'panel.admin.onboarding-pro.include.introduction', 'vendor.empty'])
    <script>
        function dismiss() {
            // localStorage.setItem('lqd-announcement-dismissed', true);
            document.querySelector(".lqd-announcement").style.display = "none";
            $.ajax({
                url: '{{ route('dashboard.user.dash_notify_seen') }}',
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    /* console.log(response); */
                }
            });
        }
    </script>
@endpush
