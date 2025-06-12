<div class="py-3.5">
    <div
        class="lqd-tabs"
        x-data="{ activeIndex: 0 }"
    >
        <div class="lqd-tabs-triggers sticky top-3.5 z-10 mx-7 mb-3.5 flex gap-2 rounded-lg bg-border p-1.5 backdrop-blur-lg dark:bg-[#141F2D]">
            <x-button
                class="active grow justify-center rounded-lg bg-transparent text-center text-foreground hover:bg-heading-background dark:hover:bg-heading-foreground/5 [&.active]:bg-heading-background [&.active]:text-heading-foreground [&.active]:shadow-[0_1px_2px_rgba(0,0,0,0.06)] dark:[&.active]:bg-[#0F1824]"
                variant="none"
                href="#"
                ::class="{ 'active': activeIndex === 0 }"
                @click.prevent="activeIndex = 0"
            >
                @lang('Recent')
            </x-button>

            <x-button
                class="grow justify-center rounded-lg bg-transparent text-center text-foreground hover:bg-heading-background dark:hover:bg-heading-foreground/5 [&.active]:bg-heading-background [&.active]:text-heading-foreground [&.active]:shadow-[0_1px_2px_rgba(0,0,0,0.06)] dark:[&.active]:bg-[#0F1824]"
                variant="none"
                href="#"
                ::class="{ 'active': activeIndex === 1 }"
                @click.prevent="activeIndex = 1"
            >
                @lang('Favorites')
            </x-button>
        </div>

        <div class="lqd-tabs-contents">
            <div
                class="lqd-tab-content"
                :class="{ 'active': activeIndex === 0, 'hidden': activeIndex !== 0 }"
            >
                @foreach (Auth::user()->openai()->orderBy('updated_at', 'desc')->take(10)->get() as $entry)
                    @continue($entry->generator == null)

                    <div class="lqd-recent-item relative flex w-full gap-3 border-b px-7 py-5 text-xs transition-colors last:border-none hover:bg-foreground/5">
                        <x-lqd-icon
                            class="shrink-0 shadow-none"
                            size="lg"
                            style="background: transparent"
                        >
                            <span class="size-5 flex">
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <g clip-path="url(#clip0_35_3209)">
                                        <path
                                            class="fill-[#E3E3E3] dark:fill-[#212B36]"
                                            d="M23.4933 11.0169C23.0143 10.3739 22.2843 10.0049 21.4852 9.99194C21.4702 9.99194 21.4582 9.99994 21.4432 9.99994H10.0241C8.26612 9.99994 6.6891 11.1769 6.1901 12.8619L3.37207 22.7049C3.88508 22.8829 4.42708 22.9999 5.00009 22.9999H17.5582C19.7842 22.9999 21.7083 21.5679 22.3603 19.3929L23.8923 13.2769C24.1263 12.4949 23.9803 11.6719 23.4933 11.0169Z "
                                        />
                                        <path
                                            class="fill-[#CBCBCB] dark:fill-[#141F2D]"
                                            d="M0 18V5C0 2.794 1.79402 1 4.00004 1H6.00006C6.46406 1 6.92807 1.109 7.34207 1.316L10.4971 2.894C10.6351 2.963 10.7901 2.999 10.9441 2.999H16.0002C18.7572 2.999 21.0002 5.242 21.0002 7.999H10.0241C7.38607 7.999 5.02105 9.765 4.27004 12.302L1.59702 21.641C0.620006 20.728 0 19.439 0 18Z "
                                        />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_35_3209">
                                            <rect
                                                width="24"
                                                height="24"
                                                fill="white"
                                            />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                        </x-lqd-icon>
                        <span class="w-2/5 grow">
                            <span class="lqd-fav-temp-item-title mb-2.5 flex w-full flex-wrap items-center gap-x-2 gap-y-1 text-sm font-medium">
                                <span class="text-heading-foreground">
                                    {{ __($entry->generator->title) }}
                                </span>

                                <span class="lqd-fav-temp-item-date opacity-45 text-2xs italic">
                                    {{ $entry->created_at->diffForHumans() }}
                                </span>
                            </span>
                            <span class="lqd-fav-temp-item-desc block w-full">
                                {{ str()->words(__($entry->generator->description), 10) }}
                            </span>
                        </span>
                        <a
                            class="lqd-docs-item-overlay-link absolute left-0 top-0 z-[2] h-full w-full"
                            href="{{  (route('dashboard.user.openai.documents.single', $entry->slug)) }}"
                            title="{{ __('View and edit') }}"
                        ></a>
                    </div>
                @endforeach
            </div>

            <div
                class="lqd-tab-content hidden"
                :class="{ 'active': activeIndex === 0, 'hidden': activeIndex !== 1 }"
            >
                @foreach (\Illuminate\Support\Facades\Auth::user()->favoriteOpenai as $entry)
                    @php
                        $upgrade = false;
                        if ($entry->premium == 1 && $plan_type === 'regular') {
                            $upgrade = true;
                        }

						if ($upgrade) {
							$href =  (route('dashboard.user.payment.subscription'));
						} else if (isset($entry->slug) && in_array($entry->slug, [
							'ai_vision',
							'ai_ai_chat_image',
							'ai_code_generator',
							'ai_youtube',
							'ai_pdf',
						])) {
							$href =  (route('dashboard.user.openai.generator.workbook', $entry->slug));
						} else {
							$href =  (route('dashboard.user.openai.generator', $entry->slug));
						}
                    @endphp
                    @if ($upgrade || $entry->active == 1)
                        <a
                            class="lqd-fav-temp-item relative flex w-full flex-wrap gap-3 border-b px-7 py-5 text-xs transition-colors last:border-none hover:bg-foreground/5"
                            href="{{ $href }}"
                        >
                        @else
                            <p class="lqd-fav-temp-item relative flex w-full flex-wrap gap-3 border-b px-7 py-5 text-xs last:border-none">
                    @endif
                    <x-lqd-icon
                        class="shrink-0 shadow-none"
                        size="lg"
                        style="background: transparent"
                        active-badge
                        active-badge-condition="{{ $entry->active == 1 }}"
                    >
                        <span class="size-5 flex">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_35_3209)">
                                    <path
                                        class="fill-[#E3E3E3] dark:fill-[#212B36]"
                                        d="M23.4933 11.0169C23.0143 10.3739 22.2843 10.0049 21.4852 9.99194C21.4702 9.99194 21.4582 9.99994 21.4432 9.99994H10.0241C8.26612 9.99994 6.6891 11.1769 6.1901 12.8619L3.37207 22.7049C3.88508 22.8829 4.42708 22.9999 5.00009 22.9999H17.5582C19.7842 22.9999 21.7083 21.5679 22.3603 19.3929L23.8923 13.2769C24.1263 12.4949 23.9803 11.6719 23.4933 11.0169Z"
                                    />
                                    <path
                                        class="fill-[#CBCBCB] dark:fill-[#141F2D]"
                                        d="M0 18V5C0 2.794 1.79402 1 4.00004 1H6.00006C6.46406 1 6.92807 1.109 7.34207 1.316L10.4971 2.894C10.6351 2.963 10.7901 2.999 10.9441 2.999H16.0002C18.7572 2.999 21.0002 5.242 21.0002 7.999H10.0241C7.38607 7.999 5.02105 9.765 4.27004 12.302L1.59702 21.641C0.620006 20.728 0 19.439 0 18Z"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_35_3209">
                                        <rect
                                            width="24"
                                            height="24"
                                            fill="white"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>
                        </span>
                    </x-lqd-icon>
                    <span class="w-2/5 grow">
                        <span class="lqd-fav-temp-item-title mb-2.5 flex w-full flex-wrap items-center gap-x-2 gap-y-1 text-sm font-medium">
                            <span class="text-heading-foreground">
                                {{ __($entry->title) }}
                            </span>

                            <span class="lqd-fav-temp-item-date opacity-45 text-2xs italic">
                                {{ $entry->created_at->diffForHumans() }}
                            </span>
                        </span>
                        <span class="lqd-fav-temp-item-desc block w-full">
                            {{ str()->words(__($entry->description), 10) }}
                        </span>
                    </span>
                    @if ($upgrade)
                        <span class="absolute inset-0 flex items-center justify-center bg-background/50">
                            <x-badge
                                class="rounded-md py-1.5"
                                variant="info"
                            >
                                {{ __('Upgrade') }}
                            </x-badge>
                        </span>
                    @endif
                    @if ($upgrade || $entry->active == 1)
                        </a>
                    @else
                        </p>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
