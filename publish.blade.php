@extends('panel.layout.app')
@section('title', __('Publish to WordPress'))

@section('content')
<div class="page-header">
    <div class="container-xl">
        <div class="row g-2 items-center">
            <div class="col">
                <a href="{{ route('dashboard.user.openai.documents.all') }}" class="page-pretitle">
                    {{ __('Back to Documents') }}
                </a>
                <h2 class="page-title">
                    {{ __('Publish to WordPress') }}
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row" id="wordpress-publisher">
            <!-- Document Information Column -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Content') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3" v-if="contentLoading">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">{{ __('Loading content...') }}</p>
                            </div>
                        </div>
                        <div class="mb-3" v-else-if="contentError">
                            <div class="empty">
                                <div class="empty-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z"></path>
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                                    </svg>
                                </div>
                                <p class="empty-title">{{ __('Error Loading Content') }}</p>
                                <p class="empty-subtitle text-muted">
                                    @{{ contentError }}
                                </p>
                                <div class="empty-action">
                                    <button type="button" class="btn btn-primary" @click="loadContent">
                                        {{ __('Try Again') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Title') }}</label>
                                <input type="text" class="form-control" v-model="form.title" :class="{'is-invalid': errors.title}" required>
                                <div class="invalid-feedback" v-if="errors.title">@{{ errors.title }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Content') }}</label>
                                <textarea class="form-control" v-model="form.content" rows="12" :class="{'is-invalid': errors.content}" required></textarea>
                                <div class="invalid-feedback" v-if="errors.content">@{{ errors.content }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">{{ __('Excerpt') }}</label>
                                <textarea class="form-control" v-model="form.excerpt" rows="3" :class="{'is-invalid': errors.excerpt}"></textarea>
                                <div class="invalid-feedback" v-if="errors.excerpt">@{{ errors.excerpt }}</div>
                                <small class="form-hint">{{ __('A short summary of your content (optional)') }}</small>
                            </div>
                            
                            <div class="mb-3" v-if="contentImages.length > 0">
                                <label class="form-label">{{ __('Featured Image') }}</label>
                                <div class="row row-cards">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row row-cards">
                                                    <div class="col-md-4" v-for="(image, index) in contentImages" :key="index">
                                                        <div class="card card-sm">
                                                            <a href="#" class="d-block" @click.prevent="selectFeaturedImage(image)" :class="{'selected-image': form.featured_image === image}">
                                                                <img :src="image" class="card-img-top" style="height: 150px; object-fit: cover;">
                                                            </a>
                                                            <div class="card-body p-2">
                                                                <div class="d-flex justify-content-center">
                                                                    <a href="#" class="btn btn-sm btn-primary" @click.prevent="selectFeaturedImage(image)">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                            <path d="M5 12l5 5l10 -10"></path>
                                                                        </svg>
                                                                        {{ __('Select') }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="form-hint">{{ __('Select an image to be used as the featured image for your WordPress post') }}</small>
                            </div>
                            
                            <div class="mb-3" v-if="form.featured_image">
                                <label class="form-label">{{ __('Featured Image Alt Text') }}</label>
                                <input type="text" class="form-control" v-model="form.featured_image_alt" :class="{'is-invalid': errors.featured_image_alt}">
                                <div class="invalid-feedback" v-if="errors.featured_image_alt">@{{ errors.featured_image_alt }}</div>
                                <small class="form-hint">{{ __('Alternative text for the featured image for accessibility and SEO') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Publishing Options Column -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Publishing Options') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3" v-if="connectionsLoading">
                            <div class="text-center p-3">
                                <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                <p class="mt-2">{{ __('Loading WordPress connections...') }}</p>
                            </div>
                        </div>
                        <div class="mb-3" v-else-if="connections.length === 0">
                            <div class="empty">
                                <div class="empty-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plug-connected" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 12l5 5l-1.5 1.5a3.536 3.536 0 1 1 -5 -5l1.5 -1.5z"></path>
                                        <path d="M17 12l-5 -5l1.5 -1.5a3.536 3.536 0 1 1 5 5l-1.5 1.5z"></path>
                                        <path d="M3 21l2.5 -2.5"></path>
                                        <path d="M18.5 5.5l2.5 -2.5"></path>
                                        <path d="M10 11l-2 2"></path>
                                        <path d="M13 14l2 -2"></path>
                                    </svg>
                                </div>
                                <p class="empty-title">{{ __('No WordPress Connections') }}</p>
                                <p class="empty-subtitle text-muted">
                                    {{ __('You need to connect to a WordPress site before you can publish content.') }}
                                </p>
                                <div class="empty-action">
                                    <a href="{{ route('dashboard.user.wordpress.connections.index') }}" class="btn btn-primary">
                                        {{ __('Manage WordPress Connections') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="publishError">
                            <div class="alert alert-danger" role="alert">
                                <h4 class="alert-title">{{ __('Publishing Error') }}</h4>
                                <div>@{{ publishError }}</div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('WordPress Site') }}</label>
                                <select class="form-select" v-model="form.wordpress_connection_id" :class="{'is-invalid': errors.wordpress_connection_id}" @change="loadCategoriesAndTags">
                                    <option value="">{{ __('Select a WordPress site...') }}</option>
                                    <option v-for="connection in activeConnections" :key="connection.id" :value="connection.id">@{{ connection.name }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="errors.wordpress_connection_id">@{{ errors.wordpress_connection_id }}</div>
                            </div>
                            
                            <div v-if="form.wordpress_connection_id">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Categories') }}</label>
                                    <div v-if="categoriesLoading" class="text-center p-2">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2">{{ __('Loading categories...') }}</span>
                                    </div>
                                    <div v-else-if="categories.length === 0" class="text-center p-2">
                                        <span class="text-muted">{{ __('No categories found') }}</span>
                                    </div>
                                    <div v-else>
                                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                            <label class="form-selectgroup-item" v-for="category in categories" :key="category.id">
                                                <input type="checkbox" :value="category.id" v-model="form.categories" class="form-selectgroup-input">
                                                <span class="form-selectgroup-label d-flex align-items-center p-3">
                                                    <span class="me-3">
                                                        <span class="form-selectgroup-check"></span>
                                                    </span>
                                                    <span class="form-selectgroup-label-content">
                                                        <span class="form-selectgroup-title strong mb-1">@{{ category.name }}</span>
                                                        <span class="d-block text-muted" v-if="category.description">@{{ category.description }}</span>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Tags') }}</label>
                                    <div v-if="tagsLoading" class="text-center p-2">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2">{{ __('Loading tags...') }}</span>
                                    </div>
                                    <select class="form-select" v-model="form.tags" multiple :class="{'is-invalid': errors.tags}">
                                        <option v-for="tag in tags" :key="tag.id" :value="tag.id">@{{ tag.name }}</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="errors.tags">@{{ errors.tags }}</div>
                                    <small class="form-hint">{{ __('Hold Ctrl/Cmd to select multiple tags') }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Slug') }}</label>
                                    <input type="text" class="form-control" v-model="form.slug" :class="{'is-invalid': errors.slug}">
                                    <div class="invalid-feedback" v-if="errors.slug">@{{ errors.slug }}</div>
                                    <small class="form-hint">{{ __('The last part of the URL (automatically generated if left empty)') }}</small>
                                </div>
                                
                                <div class="hr-text">{{ __('Publishing Options') }}</div>
                                
                                <div class="mb-3">
                                    <div class="form-selectgroup form-selectgroup-pills">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="publish-type" value="draft" class="form-selectgroup-input" v-model="publishType">
                                            <span class="form-selectgroup-label">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                    <path d="M16 5l3 3"></path>
                                                </svg>
                                                {{ __('Save as Draft') }}
                                            </span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="publish-type" value="publish" class="form-selectgroup-input" v-model="publishType">
                                            <span class="form-selectgroup-label">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                    <path d="M3.6 9h16.8"></path>
                                                    <path d="M3.6 15h16.8"></path>
                                                    <path d="M11.5 3a17 17 0 0 0 0 18"></path>
                                                    <path d="M12.5 3a17 17 0 0 1 0 18"></path>
                                                </svg>
                                                {{ __('Publish Now') }}
                                            </span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="publish-type" value="schedule" class="form-selectgroup-input" v-model="publishType">
                                            <span class="form-selectgroup-label">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-time me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4"></path>
                                                    <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                                    <path d="M15 3v4"></path>
                                                    <path d="M7 3v4"></path>
                                                    <path d="M3 11h16"></path>
                                                    <path d="M18 16.496v1.504l1 1"></path>
                                                </svg>
                                                {{ __('Schedule') }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" v-if="publishType === 'schedule'">
                                    <label class="form-label required">{{ __('Schedule Date') }}</label>
                                    <input type="datetime-local" class="form-control" v-model="form.scheduled_date" :min="minScheduleDate" :class="{'is-invalid': errors.scheduled_date}">
                                    <div class="invalid-feedback" v-if="errors.scheduled_date">@{{ errors.scheduled_date }}</div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="button" class="btn btn-primary w-100" @click="publishToWordPress" :disabled="publishing">
                                        <span v-if="publishing">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <span v-if="publishType === 'draft'">{{ __('Saving Draft...') }}</span>
                                            <span v-else-if="publishType === 'publish'">{{ __('Publishing...') }}</span>
                                            <span v-else-if="publishType === 'schedule'">{{ __('Scheduling...') }}</span>
                                        </span>
                                        <span v-else>
                                            <span v-if="publishType === 'draft'">{{ __('Save as Draft') }}</span>
                                            <span v-else-if="publishType === 'publish'">{{ __('Publish Now') }}</span>
                                            <span v-else-if="publishType === 'schedule'">{{ __('Schedule Post') }}</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview & Success Card -->
                <div class="card mb-3" v-if="publishSuccess">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('WordPress Post') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="mb-3">
                                <span class="avatar avatar-xl bg-green-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M5 12l5 5l10 -10"></path>
                                    </svg>
                                </span>
                            </div>
                            <h3 class="mb-1">@{{ publishSuccessTitle }}</h3>
                            <div class="text-muted mb-3">@{{ publishSuccessMessage }}</div>
                        </div>
                        
                        <div class="btn-list justify-content-center">
                            <a :href="previewUrl" class="btn btn-primary" target="_blank" v-if="previewUrl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                </svg>
                                {{ __('Preview Post') }}
                            </a>
                            <a :href="permalinkUrl" class="btn btn-success" target="_blank" v-if="permalinkUrl && publishType !== 'draft'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                    <path d="M3.6 9h16.8"></path>
                                    <path d="M3.6 15h16.8"></path>
                                    <path d="M11.5 3a17 17 0 0 0 0 18"></path>
                                    <path d="M12.5 3a17 17 0 0 1 0 18"></path>
                                </svg>
                                {{ __('View Published Post') }}
                            </a>
                            <a href="{{ route('dashboard.user.wordpress.connections.index') }}" class="btn">
                                {{ __('Manage Connections') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const wordpressPublisherApp = {
        data() {
            return {
                contentId: '{{ $contentId }}',
                contentLoading: true,
                contentError: null,
                contentImages: [],
                
                connectionsLoading: true,
                connections: [],
                
                categoriesLoading: false,
                categories: [],
                
                tagsLoading: false,
                tags: [],
                
                publishType: 'draft',
                publishing: false,
                publishError: null,
                publishSuccess: false,
                publishSuccessTitle: '',
                publishSuccessMessage: '',
                publishHistoryId: null,
                
                previewUrl: null,
                permalinkUrl: null,
                
                form: {
                    user_openai_id: '',
                    wordpress_connection_id: '',
                    title: '',
                    content: '',
                    excerpt: '',
                    categories: [],
                    tags: [],
                    featured_image: '',
                    featured_image_alt: '',
                    slug: '',
                    scheduled_date: this.getDefaultScheduleDate()
                },
                
                errors: {}
            }
        },
        computed: {
            activeConnections() {
                return this.connections.filter(connection => connection.is_active);
            },
            minScheduleDate() {
                const now = new Date();
                now.setMinutes(now.getMinutes() + 5); // At least 5 minutes in the future
                return now.toISOString().slice(0, 16);
            }
        },
        mounted() {
            this.loadContent();
            this.loadConnections();
        },
        methods: {
            getDefaultScheduleDate() {
                const date = new Date();
                date.setHours(date.getHours() + 1);
                return date.toISOString().slice(0, 16);
            },
            async loadContent() {
                this.contentLoading = true;
                this.contentError = null;
                
                try {
                    const response = await axios.get(`/dashboard/user/openai/documents/single/${this.contentId}`);
                    
                    if (response.data.openai) {
                        const content = response.data.openai;
                        
                        this.form.user_openai_id = content.id;
                        this.form.title = content.title || '';
                        this.form.content = content.content || '';
                        
                        // Extract images from storage URLs in content
                        this.extractImagesFromContent(content);
                    } else {
                        this.contentError = '{{ __("Content not found") }}';
                    }
                } catch (error) {
                    console.error('Error loading content:', error);
                    this.contentError = error.response?.data?.message || '{{ __("Failed to load content") }}';
                } finally {
                    this.contentLoading = false;
                }
            },
            extractImagesFromContent(content) {
                this.contentImages = [];
                
                // Check if the content has a storage URL for its output
                if (content.storage && content.storage.startsWith('/')) {
                    this.contentImages.push(content.storage);
                }
                
                // Check for image URLs in the actual content using regex
                const imgRegex = /<img[^>]+src="([^">]+)"/g;
                let match;
                
                while ((match = imgRegex.exec(content.content)) !== null) {
                    const imgUrl = match[1];
                    // Only include local storage URLs
                    if (imgUrl.startsWith('/')) {
                        this.contentImages.push(imgUrl);
                    }
                }
                
                // If images were found, set the first one as the featured image by default
                if (this.contentImages.length > 0) {
                    this.form.featured_image = this.contentImages[0];
                    this.form.featured_image_alt = content.title;
                }
            },
            selectFeaturedImage(image) {
                this.form.featured_image = image;
            },
            async loadConnections() {
                this.connectionsLoading = true;
                
                try {
                    const response = await axios.get('{{ route('dashboard.user.wordpress.connections.index') }}');
                    this.connections = response.data.connections;
                } catch (error) {
                    console.error('Error loading connections:', error);
                    toastr.error('{{ __('Failed to load WordPress connections') }}');
                } finally {
                    this.connectionsLoading = false;
                }
            },
            async loadCategoriesAndTags() {
                if (!this.form.wordpress_connection_id) return;
                
                this.loadCategories();
                this.loadTags();
            },
            async loadCategories() {
                this.categoriesLoading = true;
                this.categories = [];
                
                try {
                    const response = await axios.get(`/dashboard/user/wordpress/connections/${this.form.wordpress_connection_id}/categories`);
                    
                    if (response.data.success && response.data.categories) {
                        this.categories = response.data.categories;
                    }
                } catch (error) {
                    console.error('Error loading categories:', error);
                    toastr.error('{{ __('Failed to load WordPress categories') }}');
                } finally {
                    this.categoriesLoading = false;
                }
            },
            async loadTags() {
                this.tagsLoading = true;
                this.tags = [];
                
                try {
                    const response = await axios.get(`/dashboard/user/wordpress/connections/${this.form.wordpress_connection_id}/tags`);
                    
                    if (response.data.success && response.data.tags) {
                        this.tags = response.data.tags;
                    }
                } catch (error) {
                    console.error('Error loading tags:', error);
                    toastr.error('{{ __('Failed to load WordPress tags') }}');
                } finally {
                    this.tagsLoading = false;
                }
            },
            async publishToWordPress() {
                this.publishing = true;
                this.publishError = null;
                this.errors = {};
                
                try {
                    let response;
                    const formData = { ...this.form };
                    
                    if (this.publishType === 'draft') {
                        // Create a draft
                        response = await axios.post('{{ route('dashboard.user.wordpress.publish.draft') }}', formData);
                        
                        if (response.data.success) {
                            this.publishHistoryId = response.data.history_id;
                            this.previewUrl = response.data.preview_url;
                            this.publishSuccessTitle = '{{ __('Draft Saved') }}';
                            this.publishSuccessMessage = '{{ __('Your content has been saved as a draft in WordPress') }}';
                            this.publishSuccess = true;
                        }
                    } else if (this.publishType === 'publish') {
                        // Publish immediately
                        response = await axios.post('{{ route('dashboard.user.wordpress.publish.direct') }}', formData);
                        
                        if (response.data.success) {
                            this.publishHistoryId = response.data.history_id;
                            this.permalinkUrl = response.data.permalink;
                            this.publishSuccessTitle = '{{ __('Published Successfully') }}';
                            this.publishSuccessMessage = '{{ __('Your content has been published to WordPress') }}';
                            this.publishSuccess = true;
                        }
                    } else if (this.publishType === 'schedule') {
                        // Create a draft first
                        const draftResponse = await axios.post('{{ route('dashboard.user.wordpress.publish.draft') }}', formData);
                        
                        if (draftResponse.data.success) {
                            this.publishHistoryId = draftResponse.data.history_id;
                            
                            // Then schedule the draft
                            const scheduleResponse = await axios.post('{{ route('dashboard.user.wordpress.publish.schedule') }}', {
                                history_id: this.publishHistoryId,
                                scheduled_date: this.form.scheduled_date
                            });
                            
                            if (scheduleResponse.data.success) {
                                this.previewUrl = draftResponse.data.preview_url;
                                
                                const scheduledDate = new Date(this.form.scheduled_date);
                                const formattedDate = new Intl.DateTimeFormat('default', {
                                    dateStyle: 'medium',
                                    timeStyle: 'short'
                                }).format(scheduledDate);
                                
                                this.publishSuccessTitle = '{{ __('Post Scheduled') }}';
                                this.publishSuccessMessage = `{{ __('Your content will be published on') }} ${formattedDate}`;
                                this.publishSuccess = true;
                            } else {
                                this.publishError = scheduleResponse.data.message;
                            }
                        } else {
                            this.publishError = draftResponse.data.message;
                        }
                    }
                } catch (error) {
                    console.error('Error publishing to WordPress:', error);
                    
                    if (error.response && error.response.data && error.response.data.errors) {
                        this.errors = error.response.data.errors;
                    }
                    
                    this.publishError = error.response?.data?.message || '{{ __('Failed to publish to WordPress') }}';
                } finally {
                    this.publishing = false;
                }
            }
        }
    };
    
    const wordpressPublisher = Vue.createApp(wordpressPublisherApp).mount('#wordpress-publisher');
</script>

<style>
.selected-image {
    border: 3px solid #206bc4;
    border-radius: 0.25rem;
}
</style>
@endsection