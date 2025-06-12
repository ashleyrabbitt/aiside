@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Banners'))
@section('titlebar_actions')
    <x-button
            onclick="{{ $app_is_demo ? 'return toastr.info(\'This feature is disabled in Demo version.\')' : '' }}"
            href="{{ $app_is_demo ? '' : LaravelLocalization::localizeUrl(route('dashboard.admin.onboarding-pro.banner.create')) }}"
    >
        <x-tabler-plus class="size-4"/>
        {{ __('Add Banner') }}
    </x-button>
@endsection

@section('content')
    <div class="py-10">
        <x-table class="table">
            <x-slot:head>
                <tr>
                    <th>
                        {{ __('Description') }}
                    </th>
                    <th>
                        {{ __('Status') }}
                    </th>
                    <th>
                        {{ __('Visibility') }}
                    </th>
                    <th>
                        {{ __('Created At') }}
                    </th>
                    <th class="text-end">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </x-slot:head>

            <x-slot:body
                    class="table-tbody align-middle text-heading-foreground"
            >
                @foreach ($banners as $entry)
                    <tr>
                        <td>
                            {{ \Illuminate\Support\Str::limit($entry->description, 50) }}
                        </td>
                        <td>
                            <x-badge
                                    class="text-2xs"
                                    variant="{{ $entry->status == 1 ? 'success' : 'danger' }}"
                            >
                                {{ $entry->status == 1 ? __('Active') : __('Passive') }}
                            </x-badge>
                        </td>
                        <td>
                            <x-badge
                                    class="text-2xs"
                                    variant="{{ $entry->permanent == 1 ? 'success' : 'danger' }}"
                            >
                                {{ $entry->permanent == 1 ? __('Permanent') : __('One Time') }}
                            </x-badge>
                        </td>
                        <td>
                            <p class="m-0">
                                {{ date('j.n.Y', strtotime($entry->created_at)) }}
                                <span class="block opacity-60">
                                    {{ date('H:i:s', strtotime($entry->created_at)) }}
                                </span>
                            </p>
                        </td>
                        <td class="whitespace-nowrap text-end">
                            @if ($app_is_demo)
                                <x-button
                                        class="size-9"
                                        variant="ghost-shadow"
                                        size="none"
                                        onclick="return toastr.info('This feature is disabled in Demo version.')"
                                        title="{{ __('Edit') }}"
                                >
                                    <x-tabler-pencil class="size-4"/>
                                </x-button>
                                <x-button
                                        class="size-9"
                                        variant="ghost-shadow"
                                        hover-variant="danger"
                                        size="none"
                                        onclick="return toastr.info('This feature is disabled in Demo version.')"
                                        title="{{ __('Delete') }}"
                                >
                                    <x-tabler-x class="size-4"/>
                                </x-button>
                            @else
                                <x-button
                                        class="size-9"
                                        variant="ghost-shadow"
                                        size="none"
                                        href="{{route('dashboard.admin.onboarding-pro.banner.edit',['id' =>$entry->id])}}"
                                        title="{{ __('Edit') }}"
                                >
                                    <x-tabler-pencil class="size-4"/>
                                </x-button>
                                <x-button
                                        class="size-9"
                                        variant="ghost-shadow"
                                        hover-variant="danger"
                                        size="none"
										href="{{route('dashboard.admin.onboarding-pro.banner.delete',['id' =>$entry->id])}}"
										title="{{ __('Delete') }}"
                                >
                                    <x-tabler-x class="size-4"/>
                                </x-button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>
        </x-table>
    </div>
@endsection

@push('script')
@endpush
