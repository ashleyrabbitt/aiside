@php
    $disable_actions = $app_is_demo && (isset($category) && ($category->slug == 'ai_vision' || $category->slug == 'ai_pdf' || $category->slug == 'ai_chat_image'));
@endphp

<x-card
    class="chats-list-container flex h-[inherit] w-full shrink-0 grow-0 flex-col rounded-e-none border-e-0 max-md:absolute max-md:start-0 max-md:top-0 max-md:z-50 max-md:h-full max-md:overflow-hidden max-md:border-none max-md:bg-background/95 max-md:backdrop-blur-lg max-md:backdrop-saturate-150 max-md:transition-all max-md:duration-300 md:!flex"
    class:body="flex flex-col h-full gap-3.5"
    id="chats-list-container"
    size="none"
    ::class="{ 'active': mobileSidebarShow }"
>
    <div
        class="flex flex-col gap-3.5 max-lg:pe-3 max-lg:pt-3"
        x-data="{ searchVisible: false }"
        @keydown.window.escape="searchVisible = false"
    >
        <div class="flex gap-3">
            <div
                class="relative inline-grid h-16 w-[60px] shrink-0 place-items-center rounded-t-xl bg-surface-background p-3 after:absolute after:start-0 after:top-full after:h-3.5 after:w-full after:bg-surface-background">
                <div
                    class="absolute -bottom-3.5 start-full size-3 bg-surface-background after:absolute after:bottom-0 after:start-0 after:size-full after:rounded-es-full after:bg-background">
                </div>
                <x-button
                    class="size-[34px]"
                    variant="outline"
                    size="none"
                    href="#"
                    @click.prevent="searchVisible = !searchVisible"
                >
                    <x-tabler-search
                        class="size-4"
                        x-show="!searchVisible"
                    />
                    <x-tabler-x
                        class="size-4"
                        x-show="searchVisible"
                        x-cloak
                    />
                </x-button>
            </div>

            <div class="chats-new relative grow">
                <div
                    x-show="!searchVisible"
                    x-transition
                >
                    @if (view()->hasSection('chat_sidebar_actions'))
                        @yield('chat_sidebar_actions')
                    @else
                        @if (isset($category) && $category->slug == 'ai_pdf')
                            <input
                                id="selectDocInput"
                                type="file"
                                style="display: none;"
                                accept=".pdf, .csv, .docx, .xlsx, .xls"
                            />
                            <x-button
                                class="lqd-upload-doc-trigger relative z-20 flex h-16 w-full grow items-center justify-between rounded-xl bg-heading-foreground/5 px-[22px] py-3 text-sm font-semibold text-heading-foreground transition-all hover:translate-y-0 hover:bg-gradient-to-r hover:from-gradient-from hover:via-gradient-via hover:to-gradient-to hover:text-primary-foreground"
                                href="javascript:void(0);"
                                onclick="return $('#selectDocInput').click();"
                            >
                                {{ __('Upload Document') }}
                                <x-tabler-plus class="size-4" />
                            </x-button>
                        @else
                            <x-button
                                class="lqd-new-chat-trigger relative z-20 flex h-16 w-full grow items-center justify-between rounded-xl bg-heading-foreground/5 px-[22px] py-3 text-sm font-semibold text-heading-foreground transition-all hover:translate-y-0 hover:bg-gradient-to-r hover:from-gradient-from hover:via-gradient-via hover:to-gradient-to hover:text-primary-foreground"
                                href="javascript:void(0);"
                                onclick="{!! $disable_actions
                                    ? 'return toastr.info(\'{{ __('This feature is disabled in Demo version.') }}\')'
                                    : 'return startNewChat(\'{{ $category->id }}\', \'{{ LaravelLocalization::getCurrentLocale() }}\')' !!}"
                            >
                                {{ __('New Chat') }}
                                <x-tabler-plus class="size-4" />
                            </x-button>
                        @endif
                    @endif
                </div>

                <div
                    class="chats-search absolute inset-0"
                    x-cloak
                    x-show="searchVisible"
                    x-transition
                >
                    <form
                        class="chats-search-form relative"
                        action="#"
                    >
                        <x-forms.input
                            class="navbar-search-input peer h-16 !rounded-xl bg-heading-foreground/5 ps-10 !text-base"
                            id="chat_search_word"
                            data-category-id="{{ $category->id }}"
                            type="search"
                            onkeydown="return event.key != 'Enter';"
                            placeholder="{{ __('Search') }}"
                            aria-label="{{ __('Search in website') }}"
                            x-trap="searchVisible"
                        />
                        <x-tabler-search class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2" />
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div
        class="chats-list grow overflow-hidden"
        id="chat_sidebar_container"
    >
        @if (view()->hasSection('chat_sidebar_list'))
            @yield('chat_sidebar_list')
        @else
            @include('panel.user.openai_chat.components.chat_sidebar_list')
        @endif
    </div>
</x-card>
