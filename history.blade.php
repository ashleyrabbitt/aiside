@extends('panel.layout.app')
@section('title', __('WordPress Publishing History'))

@section('content')
<div class="page-header">
    <div class="container-xl">
        <div class="row g-2 items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('Integrations') }}
                </div>
                <h2 class="page-title">
                    {{ __('WordPress Publishing History') }}
                </h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('dashboard.user.wordpress.connections') }}" class="btn btn-primary">
                    {{ __('Manage Connections') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div id="wordpress-history" class="wordpress-history">
                            <div class="wordpress-history-loading text-center py-5" v-if="loading">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2">{{ __('Loading publishing history...') }}</div>
                            </div>

                            <div class="wordpress-history-empty text-center py-5" v-else-if="history.data.length === 0">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                        <path d="M17 17h-10v-6a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v6z"></path>
                                        <path d="M10 5v4a2 2 0 1 0 4 0v-4"></path>
                                    </svg>
                                </div>
                                <h3>{{ __('No Publishing History') }}</h3>
                                <p class="text-muted">{{ __('You haven\'t published any content to WordPress yet.') }}</p>
                            </div>

                            <div class="wordpress-history-list" v-else>
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Title') }}</th>
                                                <th>{{ __('WordPress Site') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in history.data" :key="item.id">
                                                <td>@{{ item.title }}</td>
                                                <td>@{{ item.wordpress_connection?.name || 'Unknown' }}</td>
                                                <td>
                                                    <span class="badge" :class="getStatusBadgeClass(item.status)">
                                                        @{{ formatStatus(item.status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span v-if="item.published_at">
                                                        @{{ formatDate(item.published_at) }}
                                                    </span>
                                                    <span v-else-if="item.scheduled_for">
                                                        @{{ formatDate(item.scheduled_for) }}
                                                    </span>
                                                    <span v-else>
                                                        @{{ formatDate(item.created_at) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-list flex-nowrap">
                                                        <a :href="item.preview_url || item.permalink" class="btn btn-sm btn-primary" target="_blank" v-if="item.permalink">
                                                            {{ __('View') }}
                                                        </a>
                                                        <button class="btn btn-sm btn-secondary" @click="getPreview(item)" v-else-if="item.status === 'draft'">
                                                            {{ __('Preview') }}
                                                        </button>
                                                        <button class="btn btn-sm btn-success" @click="publishDraft(item)" v-if="item.status === 'draft'">
                                                            {{ __('Publish') }}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <div class="d-flex justify-content-center mt-4" v-if="history.last_page > 1">
                                    <ul class="pagination">
                                        <li class="page-item" :class="{ disabled: history.current_page === 1 }">
                                            <a class="page-link" href="#" @click.prevent="loadPage(history.current_page - 1)">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M15 6l-6 6l6 6"></path>
                                                </svg>
                                                {{ __('prev') }}
                                            </a>
                                        </li>
                                        <li class="page-item" v-for="page in getVisiblePages()" :key="page" :class="{ active: page === history.current_page }">
                                            <a class="page-link" href="#" @click.prevent="loadPage(page)">@{{ page }}</a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: history.current_page === history.last_page }">
                                            <a class="page-link" href="#" @click.prevent="loadPage(history.current_page + 1)">
                                                {{ __('next') }}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M9 6l6 6l-6 6"></path>
                                                </svg>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Publish Draft Confirmation Modal -->
<div class="modal modal-blur fade" id="modal-publish-draft" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">{{ __('Publish Draft') }}</div>
                <div>{{ __('Are you sure you want to publish this draft now?') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-success ms-auto" @click="confirmPublishDraft" :disabled="processing">
                    <span v-if="processing">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        {{ __('Publishing...') }}
                    </span>
                    <span v-else>
                        {{ __('Publish Now') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const wordpressHistoryApp = {
        data() {
            return {
                loading: true,
                processing: false,
                history: {
                    current_page: 1,
                    data: [],
                    last_page: 1,
                    per_page: 10,
                    total: 0
                },
                publishItem: null,
                publishDraftModal: null,
            }
        },
        mounted() {
            this.publishDraftModal = new bootstrap.Modal(document.getElementById('modal-publish-draft'));
            this.loadHistory();
        },
        methods: {
            formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleString();
            },
            formatStatus(status) {
                switch (status) {
                    case 'draft': return '{{ __('Draft') }}';
                    case 'published': return '{{ __('Published') }}';
                    case 'scheduled': return '{{ __('Scheduled') }}';
                    case 'failed': return '{{ __('Failed') }}';
                    default: return status;
                }
            },
            getStatusBadgeClass(status) {
                switch (status) {
                    case 'draft': return 'bg-blue';
                    case 'published': return 'bg-success';
                    case 'scheduled': return 'bg-purple';
                    case 'failed': return 'bg-danger';
                    default: return 'bg-secondary';
                }
            },
            async loadHistory(page = 1) {
                this.loading = true;
                
                try {
                    const response = await axios.get('{{ route('dashboard.user.wordpress.publish.history') }}', {
                        params: { page }
                    });
                    
                    this.history = response.data.history;
                } catch (error) {
                    console.error('Error loading publishing history:', error);
                    toastr.error('{{ __('Failed to load publishing history') }}');
                } finally {
                    this.loading = false;
                }
            },
            loadPage(page) {
                if (page < 1 || page > this.history.last_page) return;
                this.loadHistory(page);
            },
            getVisiblePages() {
                const totalPages = this.history.last_page;
                const currentPage = this.history.current_page;
                
                if (totalPages <= 7) {
                    return Array.from({ length: totalPages }, (_, i) => i + 1);
                }
                
                if (currentPage <= 4) {
                    return [1, 2, 3, 4, 5, '...', totalPages];
                }
                
                if (currentPage >= totalPages - 3) {
                    return [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
                }
                
                return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
            },
            async getPreview(item) {
                try {
                    const response = await axios.post('{{ route('dashboard.user.wordpress.publish.preview') }}', {
                        history_id: item.id
                    });
                    
                    if (response.data.success && response.data.preview_url) {
                        window.open(response.data.preview_url, '_blank');
                    } else {
                        toastr.error('{{ __('Failed to generate preview') }}');
                    }
                } catch (error) {
                    console.error('Error getting preview:', error);
                    toastr.error(error.response?.data?.message || '{{ __('Failed to generate preview') }}');
                }
            },
            publishDraft(item) {
                this.publishItem = item;
                this.publishDraftModal.show();
            },
            async confirmPublishDraft() {
                if (!this.publishItem) return;
                
                this.processing = true;
                
                try {
                    const response = await axios.post('{{ route('dashboard.user.wordpress.publish.publish-draft') }}', {
                        history_id: this.publishItem.id
                    });
                    
                    if (response.data.success) {
                        toastr.success('{{ __('Post published successfully') }}');
                        this.loadHistory(this.history.current_page);
                        
                        // If there's a permalink, open it in a new tab
                        if (response.data.permalink) {
                            window.open(response.data.permalink, '_blank');
                        }
                    } else {
                        toastr.error(response.data.message || '{{ __('Failed to publish post') }}');
                    }
                } catch (error) {
                    console.error('Error publishing draft:', error);
                    toastr.error(error.response?.data?.message || '{{ __('Failed to publish post') }}');
                } finally {
                    this.processing = false;
                    this.publishDraftModal.hide();
                    this.publishItem = null;
                }
            }
        }
    };
    
    const wordpressHistory = Vue.createApp(wordpressHistoryApp).mount('#wordpress-history');
</script>
@endsection