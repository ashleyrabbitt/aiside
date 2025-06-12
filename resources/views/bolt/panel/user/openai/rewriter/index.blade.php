@php
    $voice_tones = ['Professional', 'Funny', 'Casual', 'Excited', 'Witty', 'Sarcastic', 'Feminine', 'Masculine', 'Bold', 'Dramatic', 'Grumpy', 'Secretive', 'other'];
@endphp

@extends('panel.layout.app', ['disable_tblr' => true, 'has_sidebar' => true, 'disable_default_sidebar' => true])
@section('title', __('AI ReWriter'))
@section('titlebar_subtitle', __('Effortlessly reshape and elevate your pre-existing content with a single click.'))

@section('content')
    <div class="py-10 lg:-mx-4 lg:min-h-full lg:bg-[rgba(247,247,249,1)] lg:dark:bg-white/[1%]">
        <div
            class="lqd-generator-wrap mx-auto grid grid-flow-row gap-y-8 lg:max-w-[680px] lg:grid-flow-col"
            data-generator-type="rewrite"
        >
            <div
                class="lqd-generator-options-container lg:fixed lg:bottom-[--body-padding] lg:end-[--body-padding] lg:top-[--body-padding] lg:z-5 lg:w-[--sidebar-width] lg:flex-col lg:flex-nowrap lg:justify-start lg:gap-8 lg:overflow-y-auto lg:rounded-e-2xl lg:border-s lg:border-border lg:bg-background lg:p-7">
                <div class="container flex flex-col gap-6 lg:h-full lg:max-w-none lg:p-0">
                    <h3 class="text-lg">
                        @if (view()->hasSection('titlebar_title'))
                            @yield('titlebar_title')
                        @elseif (view()->hasSection('title'))
                            @yield('title')
                        @endif
                    </h3>

                    <x-card
                        class="lqd-generator-remaining-credits"
                        size="sm"
                    >
                        <x-credit-list />
                    </x-card>

                    <x-card
                        class:body="flex flex-col"
                        class="lqd-generator-options-card flex grow"
                        variant="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.variant', 'solid') }}"
                        size="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.size', 'md') }}"
                        roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
                    >
                        <form
                            class="lqd-generator-form flex grow flex-col gap-y-5"
                            id="rewrite_content_form"
                            onsubmit="return sendOpenaiGeneratorForm();"
                            enctype="multipart/form-data"
                        >
                            <div class="flex flex-wrap justify-between gap-y-5">
                                <x-forms.input
                                    id="content_rewrite"
                                    size="lg"
                                    type="textarea"
                                    label="{{ __('Description') }}"
                                    containerClass="w-full"
                                    name="content_rewrite"
                                    rows="10"
                                    required
                                />

                                @if (setting('hide_tone_of_voice_option') != 1)
                                    <x-forms.input
                                        id="rewrite_mode"
                                        size="lg"
                                        type="select"
                                        label="{{ __('Mode') }}"
                                        containerClass="w-full"
                                        name="rewrite_mode"
                                        required
                                    >
                                        @foreach ($voice_tones as $tone)
                                            <option
                                                value="{{ $tone }}"
                                                @selected($setting->openai_default_tone_of_voice == $tone)
                                            >
                                                {{ __($tone) }}
                                            </option>
                                        @endforeach
                                    </x-forms.input>
                                    <x-forms.input
                                        class:container="hidden w-full md:w-[48%]"
                                        id="tone_of_voice_custom"
                                        containerClass="w-full"
                                        name="tone_of_voice_custom"
                                        type="text"
                                        label="{{ __('Enter custom tone') }}"
                                        switcher
                                    />
                                @endif

                                <x-forms.input
                                    id="language"
                                    size="lg"
                                    type="select"
                                    containerClass="w-full"
                                    label="{{ __('Output Language') }}"
                                    name="language"
                                    required
                                >
                                    @include('panel.user.openai.components.countries')
                                </x-forms.input>
                            </div>

                            <div class="sticky -bottom-7 -mx-7 flex grow items-end p-7 backdrop-blur-lg">
                                <x-button
                                    class="mt-2 w-full"
                                    id="openai_generator_button"
                                    tag="button"
                                    size="lg"
                                    type="submit"
                                >
                                    {{ __('Generate') }}
                                </x-button>
                            </div>
                        </form>
                    </x-card>
                </div>
            </div>

            <x-card
                class="w-full [&_.tox-edit-area__iframe]:!bg-transparent"
                id="workbook_textarea"
                variant="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.variant', 'solid') }}"
                size="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.size', 'md') }}"
                roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
            >
                <div class="flex flex-wrap items-center justify-between text-[13px]">
                    <div class="lqd-generator-form-wrap min-h-full w-full">
                        <form
                            class="workbook-form flex flex-col lg:[&_.tox-editor-header]:!rounded-t-none lg:[&_.tox-editor-header]:rounded-b-2xl lg:[&_.tox-editor-header]:!bg-background lg:[&_.tox-editor-header]:!px-4 lg:[&_.tox-editor-header]:!py-2 lg:[&_.tox-tinymce]:rounded-none"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3 sm:flex-nowrap lg:rounded-t-2xl lg:bg-background lg:px-4 lg:pt-3">
                                <x-forms.input
                                    class:container="grow"
                                    class="border-transparent px-0 font-serif text-2xl"
                                    id="workbook_title"
                                    placeholder="{{ __('Untitled Document...') }}"
                                />
                                <div class="lqd-generator-actions flex flex-wrap items-center gap-3 text-2xs lg:justify-end">
                                    <button
                                        class="lqd-regenerate-btn flex items-center gap-2 border-none shadow-none"
                                        id="btn_regenerate"
                                        type="submit"
                                        form="rewrite_content_form"
                                    >
                                        <x-tabler-arrows-right-left class="size-4" />
                                        {{ __('Regenerate') }}
                                    </button>
                                    <div class="flex">
                                        @include('panel.user.openai.components.workbook-actions', [
                                            'type' => 'text',
                                            'title' => __('AI ReWriter'),
                                            'is_generated_doc' => true,
                                        ])
                                    </div>
                                </div>
                            </div>
                            <x-forms.input
                                class="tinymce border-0 font-body"
                                id="default"
                                type="textarea"
                                rows="25"
                            />
                        </form>
                    </div>
                </div>
            </x-card>
        </div>
        <input
            id="guest_id"
            type="hidden"
            value="{{ $apiUrl }}"
        >
        <input
            id="guest_event_id"
            type="hidden"
            value="{{ $apikeyPart1 }}"
        >
        <input
            id="guest_look_id"
            type="hidden"
            value="{{ $apikeyPart2 }}"
        >
        <input
            id="guest_product_id"
            type="hidden"
            value="{{ $apikeyPart3 }}"
        >
    </div>
@endsection

@php
    $lang_with_flags = [];
    foreach (LaravelLocalization::getSupportedLocales() as $lang => $properties) {
        $lang_with_flags[] = [
            'lang' => $lang,
            'name' => $properties['native'],
            'flag' => country2flag(substr($properties['regional'], strrpos($properties['regional'], '_') + 1)),
        ];
    }
@endphp
@push('script')
    <script>
        const lang_with_flags = @json($lang_with_flags);
    </script>
    <script src="{{ custom_theme_url('/assets/libs/beautify-html.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/ace/src-min-noconflict/ace.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/ace/src-min-noconflict/ext-language_tools.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/markdown-it.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/turndown.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/tinymce-theme-handler.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/format-string.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/openai_generator_workbook.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/wavesurfer/wavesurfer.js') }}"></script>
    <script>
        const stream_type = '{!! $settings_two->openai_default_stream_server !!}';
        const openai_model = '{{ $setting->openai_default_model }}';

        function sendOpenaiGeneratorForm(ev) {
            "use strict";
            $('#savedDiv').addClass('hidden');

            tinyMCE?.activeEditor?.setContent('');

            ev?.preventDefault();
            ev?.stopPropagation();
            const submitBtn = document.getElementById("openai_generator_button");
            const editArea = document.querySelector('.tox-edit-area');
            const typingTemplate = document.querySelector('#typing-template').content.cloneNode(true);
            const typingEl = typingTemplate.firstElementChild;
            Alpine.store('appLoadingIndicator').show();
            submitBtn.classList.add('lqd-form-submitting');
            submitBtn.disabled = true;

            if (editArea) {
                if (!editArea.querySelector('.lqd-typing')) {
                    editArea.appendChild(typingEl);
                } else {
                    editArea.querySelector('.lqd-typing')?.classList?.remove('lqd-is-hidden');
                }
            }

            var formData = new FormData();
            formData.append('post_type', 'ai_rewriter');
            formData.append('content_rewrite', $('#content_rewrite').val());

            formData.append('rewrite_mode', $("#rewrite_mode").val());
            formData.append('language', $("#language").val());

            $.ajax({
                type: "post",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                },
                url: "/dashboard/user/openai/generate",
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    const typingEl = document.querySelector('.tox-edit-area > .lqd-typing');

                    const message_no = data.message_id;
                    const creativity = data.creativity;
                    const maximum_length = parseInt(data.maximum_length);
                    const number_of_results = data.number_of_results;
                    const prompt = data.inputPrompt;
                    const openai_id = '1';
                    generate(message_no, creativity, maximum_length, number_of_results, prompt, openai_id);
                    setTimeout(function() {
                        $('#savedDiv').removeClass('hidden');
                    }, 1000);
                },
                error: function(data) {
                    if (data.responseJSON.errors) {
                        toastr.error(data.responseJSON.errors);
                    } else if (data.responseJSON.message) {
                        toastr.error(data.responseJSON.message);
                    }
                    submitBtn.classList.remove('lqd-form-submitting');
                    Alpine.store('appLoadingIndicator').hide();
                    document.querySelector('#workbook_regenerate')?.classList?.add('hidden');
                    submitBtn.disabled = false;
                    const editArea = document.querySelector('.tox-edit-area');
                    editArea.querySelector('.lqd-typing')?.classList?.add('lqd-is-hidden');
                }
            });
            return false;
        };

        const deleteButton = document.getElementById("workbook_delete");
        deleteButton?.addEventListener("click", clearWorkbookContent);

        function clearWorkbookContent() {
            const editor = tinyMCE.activeEditor;
            if (editor) {
                editor.setContent("");
            }
        }

        document.getElementById('tone_of_voice')?.addEventListener('change', function() {
            var customInput = document.getElementById('tone_of_voice_custom');
            if (this.value === 'other') {
                customInput.parentNode.classList.remove('hidden');
            } else {
                customInput.parentNode.classList.add('hidden');
            }
        });
    </script>
@endpush
