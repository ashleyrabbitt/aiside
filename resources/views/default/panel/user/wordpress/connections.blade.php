@extends('panel.layout.app')
@section('title', __('WordPress Connections'))

@section('content')
<div class="page-header">
    <div class="container-xl">
        <div class="row g-2 items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('Integrations') }}
                </div>
                <h2 class="page-title">
                    {{ __('WordPress Connections') }}
                </h2>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-new-connection">
                    {{ __('New Connection') }}
                </button>
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
                        <div id="wordpress-connections" class="wordpress-connections">
                            <div class="wordpress-connections-loading text-center py-5" v-if="loading">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2">{{ __('Loading connections...') }}</div>
                            </div>

                            <div class="wordpress-connections-empty text-center py-5" v-else-if="connections.length === 0">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
                                    </svg>
                                </div>
                                <h3>{{ __('No WordPress Connections') }}</h3>
                                <p class="text-muted">{{ __('Connect your WordPress sites to publish AI-generated content directly.') }}</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-new-connection">
                                    {{ __('Add Your First WordPress Site') }}
                                </button>
                            </div>

                            <div class="wordpress-connections-list" v-else>
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Site URL') }}</th>
                                                <th>{{ __('Auth Type') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Last Connected') }}</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="connection in connections" :key="connection.id">
                                                <td>@{{ connection.name }}</td>
                                                <td>
                                                    <a :href="connection.site_url" target="_blank">@{{ connection.site_url }}</a>
                                                </td>
                                                <td>
                                                    <span class="badge" :class="getAuthTypeBadgeClass(connection.auth_type)">
                                                        @{{ formatAuthType(connection.auth_type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge" :class="connection.is_active ? 'bg-success' : 'bg-danger'">
                                                        @{{ connection.is_active ? '{{ __('Active') }}' : '{{ __('Inactive') }}' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span v-if="connection.last_connected_at">
                                                        @{{ formatDate(connection.last_connected_at) }}
                                                    </span>
                                                    <span v-else class="text-muted">
                                                        {{ __('Never') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-list flex-nowrap">
                                                        <button class="btn btn-sm btn-secondary" @click="testConnection(connection)">
                                                            {{ __('Test') }}
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" @click="editConnection(connection)">
                                                            {{ __('Edit') }}
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" @click="confirmDelete(connection)">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Connection Modal -->
<div class="modal modal-blur fade" id="modal-new-connection" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('New WordPress Connection') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="wordpress-connection-form" class="wordpress-connection-form">
                    <form @submit.prevent="saveConnection">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Connection Name') }}</label>
                            <input type="text" class="form-control" v-model="form.name" :class="{'is-invalid': errors.name}" required>
                            <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                            <small class="form-hint">{{ __('A friendly name to identify this connection') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('WordPress Site URL') }}</label>
                            <input type="url" class="form-control" v-model="form.site_url" :class="{'is-invalid': errors.site_url}" placeholder="https://your-wordpress-site.com" required>
                            <div class="invalid-feedback" v-if="errors.site_url">@{{ errors.site_url }}</div>
                            <small class="form-hint">{{ __('Enter the full URL of your WordPress site') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('Authentication Type') }}</label>
                            <select class="form-select" v-model="form.auth_type" :class="{'is-invalid': errors.auth_type}" required>
                                <option value="app_password">{{ __('Application Password') }}</option>
                                <option value="basic">{{ __('Basic Authentication') }}</option>
                                <option value="oauth">{{ __('OAuth') }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="errors.auth_type">@{{ errors.auth_type }}</div>
                        </div>

                        <div class="mb-3" v-if="form.auth_type === 'app_password' || form.auth_type === 'basic'">
                            <label class="form-label required">{{ __('Username') }}</label>
                            <input type="text" class="form-control" v-model="form.username" :class="{'is-invalid': errors.username}" required>
                            <div class="invalid-feedback" v-if="errors.username">@{{ errors.username }}</div>
                        </div>

                        <div class="mb-3" v-if="form.auth_type === 'app_password' || form.auth_type === 'basic'">
                            <label class="form-label required">{{ __('Password') }}</label>
                            <input type="password" class="form-control" v-model="form.password" :class="{'is-invalid': errors.password}" required>
                            <div class="invalid-feedback" v-if="errors.password">@{{ errors.password }}</div>
                            <small class="form-hint" v-if="form.auth_type === 'app_password'">
                                {{ __('Use an Application Password generated in your WordPress admin under Users → Profile → Application Passwords') }}
                            </small>
                        </div>

                        <div v-if="form.auth_type === 'oauth'">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Client ID') }}</label>
                                <input type="text" class="form-control" v-model="form.client_id" :class="{'is-invalid': errors.client_id}" required>
                                <div class="invalid-feedback" v-if="errors.client_id">@{{ errors.client_id }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">{{ __('Client Secret') }}</label>
                                <input type="password" class="form-control" v-model="form.client_secret" :class="{'is-invalid': errors.client_secret}" required>
                                <div class="invalid-feedback" v-if="errors.client_secret">@{{ errors.client_secret }}</div>
                            </div>
                        </div>

                        <div class="alert alert-info" v-if="form.auth_type === 'app_password'">
                            <h4 class="alert-title">{{ __('Application Passwords') }}</h4>
                            <p>{{ __('For this connection method:') }}</p>
                            <ol class="mb-0">
                                <li>{{ __('Login to your WordPress admin panel') }}</li>
                                <li>{{ __('Go to Users → Profile → Application Passwords') }}</li>
                                <li>{{ __('Create a new Application Password for "AI Side Hustle"') }}</li>
                                <li>{{ __('Copy the generated password and paste it here') }}</li>
                            </ol>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary ms-auto" @click="saveConnection" :disabled="formSubmitting">
                    <span v-if="formSubmitting">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        {{ __('Saving...') }}
                    </span>
                    <span v-else>
                        {{ __('Save Connection') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Connection Modal -->
<div class="modal modal-blur fade" id="modal-edit-connection" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit WordPress Connection') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="wordpress-connection-edit-form" class="wordpress-connection-edit-form">
                    <form @submit.prevent="updateConnection">
                        <input type="hidden" v-model="editForm.id">
                        
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Connection Name') }}</label>
                            <input type="text" class="form-control" v-model="editForm.name" :class="{'is-invalid': errors.name}" required>
                            <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('WordPress Site URL') }}</label>
                            <input type="url" class="form-control" v-model="editForm.site_url" :class="{'is-invalid': errors.site_url}" placeholder="https://your-wordpress-site.com" required>
                            <div class="invalid-feedback" v-if="errors.site_url">@{{ errors.site_url }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('Authentication Type') }}</label>
                            <select class="form-select" v-model="editForm.auth_type" :class="{'is-invalid': errors.auth_type}" required>
                                <option value="app_password">{{ __('Application Password') }}</option>
                                <option value="basic">{{ __('Basic Authentication') }}</option>
                                <option value="oauth">{{ __('OAuth') }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="errors.auth_type">@{{ errors.auth_type }}</div>
                        </div>

                        <div class="mb-3" v-if="editForm.auth_type === 'app_password' || editForm.auth_type === 'basic'">
                            <label class="form-label required">{{ __('Username') }}</label>
                            <input type="text" class="form-control" v-model="editForm.username" :class="{'is-invalid': errors.username}" required>
                            <div class="invalid-feedback" v-if="errors.username">@{{ errors.username }}</div>
                        </div>

                        <div class="mb-3" v-if="editForm.auth_type === 'app_password' || editForm.auth_type === 'basic'">
                            <label class="form-label">{{ __('Password') }}</label>
                            <input type="password" class="form-control" v-model="editForm.password" :class="{'is-invalid': errors.password}" placeholder="{{ __('Leave empty to keep current password') }}">
                            <div class="invalid-feedback" v-if="errors.password">@{{ errors.password }}</div>
                            <small class="form-hint" v-if="editForm.auth_type === 'app_password'">
                                {{ __('Only enter a new password if you want to change it') }}
                            </small>
                        </div>

                        <div v-if="editForm.auth_type === 'oauth'">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Client ID') }}</label>
                                <input type="text" class="form-control" v-model="editForm.client_id" :class="{'is-invalid': errors.client_id}" required>
                                <div class="invalid-feedback" v-if="errors.client_id">@{{ errors.client_id }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Client Secret') }}</label>
                                <input type="password" class="form-control" v-model="editForm.client_secret" :class="{'is-invalid': errors.client_secret}" placeholder="{{ __('Leave empty to keep current secret') }}">
                                <div class="invalid-feedback" v-if="errors.client_secret">@{{ errors.client_secret }}</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" v-model="editForm.is_active">
                                <span class="form-check-label">{{ __('Active') }}</span>
                            </label>
                            <small class="form-hint">{{ __('Enable or disable this connection') }}</small>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary ms-auto" @click="updateConnection" :disabled="formSubmitting">
                    <span v-if="formSubmitting">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        {{ __('Saving...') }}
                    </span>
                    <span v-else>
                        {{ __('Update Connection') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="modal-delete-connection" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">{{ __('Are you sure?') }}</div>
                <div>{{ __('This will delete the WordPress connection from your account. This action cannot be undone.') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-danger ms-auto" @click="deleteConnection" :disabled="formSubmitting">
                    <span v-if="formSubmitting">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        {{ __('Deleting...') }}
                    </span>
                    <span v-else>
                        {{ __('Delete Connection') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const wordpressConnectionsApp = {
        data() {
            return {
                loading: true,
                connections: [],
                form: {
                    name: '',
                    site_url: '',
                    auth_type: 'app_password',
                    username: '',
                    password: '',
                    client_id: '',
                    client_secret: '',
                },
                editForm: {
                    id: null,
                    name: '',
                    site_url: '',
                    auth_type: 'app_password',
                    username: '',
                    password: '',
                    client_id: '',
                    client_secret: '',
                    is_active: true,
                },
                deleteId: null,
                errors: {},
                formSubmitting: false,
                newConnectionModal: null,
                editConnectionModal: null,
                deleteConnectionModal: null,
            }
        },
        mounted() {
            this.newConnectionModal = new bootstrap.Modal(document.getElementById('modal-new-connection'));
            this.editConnectionModal = new bootstrap.Modal(document.getElementById('modal-edit-connection'));
            this.deleteConnectionModal = new bootstrap.Modal(document.getElementById('modal-delete-connection'));
            this.loadConnections();
        },
        methods: {
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            },
            formatAuthType(type) {
                switch (type) {
                    case 'app_password': return 'App Password';
                    case 'basic': return 'Basic Auth';
                    case 'oauth': return 'OAuth';
                    default: return type;
                }
            },
            getAuthTypeBadgeClass(type) {
                switch (type) {
                    case 'app_password': return 'bg-blue';
                    case 'basic': return 'bg-yellow';
                    case 'oauth': return 'bg-green';
                    default: return 'bg-secondary';
                }
            },
            async loadConnections() {
                this.loading = true;
                try {
                    const response = await axios.get('{{ route('dashboard.user.wordpress.connections.index') }}');
                    this.connections = response.data.connections;
                } catch (error) {
                    console.error('Error loading connections:', error);
                    toastr.error('{{ __('Failed to load WordPress connections') }}');
                } finally {
                    this.loading = false;
                }
            },
            resetForm() {
                this.form = {
                    name: '',
                    site_url: '',
                    auth_type: 'app_password',
                    username: '',
                    password: '',
                    client_id: '',
                    client_secret: '',
                };
                this.errors = {};
            },
            async saveConnection() {
                this.formSubmitting = true;
                this.errors = {};
                
                try {
                    const response = await axios.post('{{ route('dashboard.user.wordpress.connections.store') }}', this.form);
                    
                    if (response.data.success) {
                        this.connections.unshift(response.data.connection);
                        this.newConnectionModal.hide();
                        this.resetForm();
                        toastr.success('{{ __('WordPress connection created successfully') }}');
                    } else {
                        toastr.error(response.data.message || '{{ __('Failed to create connection') }}');
                    }
                } catch (error) {
                    console.error('Error saving connection:', error);
                    
                    if (error.response && error.response.data && error.response.data.errors) {
                        this.errors = error.response.data.errors;
                    }
                    
                    toastr.error(error.response?.data?.message || '{{ __('Failed to create connection') }}');
                } finally {
                    this.formSubmitting = false;
                }
            },
            editConnection(connection) {
                this.editForm = {
                    id: connection.id,
                    name: connection.name,
                    site_url: connection.site_url,
                    auth_type: connection.auth_type,
                    username: connection.username,
                    password: '',  // Don't populate password for security
                    client_id: connection.client_id,
                    client_secret: '',  // Don't populate client secret for security
                    is_active: connection.is_active,
                };
                this.errors = {};
                this.editConnectionModal.show();
            },
            async updateConnection() {
                this.formSubmitting = true;
                this.errors = {};
                
                try {
                    const response = await axios.put(`{{ route('dashboard.user.wordpress.connections.index') }}/${this.editForm.id}`, this.editForm);
                    
                    if (response.data.success) {
                        const index = this.connections.findIndex(c => c.id === this.editForm.id);
                        if (index !== -1) {
                            this.connections[index] = response.data.connection;
                        }
                        this.editConnectionModal.hide();
                        toastr.success('{{ __('WordPress connection updated successfully') }}');
                    } else {
                        toastr.error(response.data.message || '{{ __('Failed to update connection') }}');
                    }
                } catch (error) {
                    console.error('Error updating connection:', error);
                    
                    if (error.response && error.response.data && error.response.data.errors) {
                        this.errors = error.response.data.errors;
                    }
                    
                    toastr.error(error.response?.data?.message || '{{ __('Failed to update connection') }}');
                } finally {
                    this.formSubmitting = false;
                }
            },
            confirmDelete(connection) {
                this.deleteId = connection.id;
                this.deleteConnectionModal.show();
            },
            async deleteConnection() {
                if (!this.deleteId) return;
                
                this.formSubmitting = true;
                
                try {
                    const response = await axios.delete(`{{ route('dashboard.user.wordpress.connections.index') }}/${this.deleteId}`);
                    
                    if (response.data.success) {
                        this.connections = this.connections.filter(c => c.id !== this.deleteId);
                        this.deleteConnectionModal.hide();
                        toastr.success('{{ __('WordPress connection deleted successfully') }}');
                    } else {
                        toastr.error(response.data.message || '{{ __('Failed to delete connection') }}');
                    }
                } catch (error) {
                    console.error('Error deleting connection:', error);
                    toastr.error(error.response?.data?.message || '{{ __('Failed to delete connection') }}');
                } finally {
                    this.formSubmitting = false;
                    this.deleteId = null;
                }
            },
            async testConnection(connection) {
                try {
                    const response = await axios.post(`{{ route('dashboard.user.wordpress.connections.index') }}/${connection.id}/test`);
                    
                    if (response.data.success) {
                        toastr.success('{{ __('Connection test successful') }}');
                        
                        // Update connection status if it changed
                        const index = this.connections.findIndex(c => c.id === connection.id);
                        if (index !== -1 && this.connections[index].is_active !== response.data.connection.is_active) {
                            this.connections[index].is_active = response.data.connection.is_active;
                            this.connections[index].last_connected_at = response.data.connection.last_connected_at;
                        }
                    } else {
                        toastr.error(response.data.message || '{{ __('Connection test failed') }}');
                    }
                } catch (error) {
                    console.error('Error testing connection:', error);
                    toastr.error(error.response?.data?.message || '{{ __('Connection test failed') }}');
                }
            }
        }
    };
    
    const wordpressConnections = Vue.createApp(wordpressConnectionsApp).mount('#wordpress-connections');
    const wordpressConnectionForm = Vue.createApp(wordpressConnectionsApp).mount('#wordpress-connection-form');
    const wordpressConnectionEditForm = Vue.createApp(wordpressConnectionsApp).mount('#wordpress-connection-edit-form');
</script>
@endsection