<?php

namespace App\Services\Integration;

use App\Models\Integration\WordPressConnection;
use App\Models\Integration\WordPressPublishHistory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordPressService
{
    protected WordPressConnection $connection;
    protected PendingRequest $client;
    protected string $apiBase;

    /**
     * Initialize the WordPress service with a connection.
     */
    public function __construct(WordPressConnection $connection)
    {
        $this->connection = $connection;
        $this->apiBase = rtrim($connection->site_url, '/') . '/wp-json/wp/v2';
        
        // Setup HTTP client based on auth type
        $this->setupClient();
    }

    /**
     * Setup the HTTP client based on authentication type
     */
    protected function setupClient(): void
    {
        $client = Http::baseUrl($this->apiBase)->timeout(30);

        switch ($this->connection->auth_type) {
            case 'app_password':
                $client = $client->withBasicAuth(
                    $this->connection->username,
                    $this->connection->decrypted_password_attribute
                );
                break;
                
            case 'oauth':
                if ($this->connection->isTokenExpired()) {
                    $this->refreshToken();
                }
                
                $client = $client->withToken($this->connection->decrypted_access_token_attribute);
                break;
                
            case 'basic':
                $client = $client->withBasicAuth(
                    $this->connection->username,
                    $this->connection->decrypted_password_attribute
                );
                break;
        }

        $this->client = $client;
    }

    /**
     * Refresh OAuth token if expired
     */
    protected function refreshToken(): bool
    {
        try {
            $response = Http::post($this->connection->site_url . '/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->connection->client_id,
                'client_secret' => $this->connection->decrypted_client_secret_attribute,
                'refresh_token' => $this->connection->decrypted_refresh_token_attribute,
            ]);

            $data = $response->json();
            
            if (!isset($data['access_token'])) {
                Log::error('Failed to refresh WordPress OAuth token', ['response' => $data]);
                return false;
            }

            $this->connection->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $this->connection->refresh_token,
                'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception refreshing WordPress OAuth token', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            return false;
        }
    }

    /**
     * Test the connection to WordPress
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('users/me');
            
            if ($response->successful()) {
                $userData = $response->json();
                
                $this->connection->update([
                    'last_connected_at' => now(),
                    'capabilities' => $userData['capabilities'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Successfully connected to WordPress site',
                    'user' => $userData
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to connect to WordPress: ' . $response->status(),
                'status' => $response->status()
            ];
            
        } catch (\Exception $e) {
            Log::error('WordPress connection test failed', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all categories from WordPress
     */
    public function getCategories(): array
    {
        try {
            $response = $this->client->get('categories', [
                'per_page' => 100,
                'orderby' => 'name',
                'order' => 'asc',
            ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'categories' => $response->json()
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $response->status(),
                'status' => $response->status()
            ];
            
        } catch (\Exception $e) {
            Log::error('WordPress categories fetch failed', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all tags from WordPress
     */
    public function getTags(): array
    {
        try {
            $response = $this->client->get('tags', [
                'per_page' => 100,
                'orderby' => 'name',
                'order' => 'asc',
            ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'tags' => $response->json()
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch tags: ' . $response->status(),
                'status' => $response->status()
            ];
            
        } catch (\Exception $e) {
            Log::error('WordPress tags fetch failed', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new tag in WordPress
     */
    public function createTag(string $name): ?array
    {
        try {
            $response = $this->client->post('tags', [
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Failed to create WordPress tag', [
                'status' => $response->status(),
                'response' => $response->body(),
                'name' => $name
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Exception creating WordPress tag', [
                'error' => $e->getMessage(),
                'name' => $name
            ]);
            
            return null;
        }
    }

    /**
     * Create a new category in WordPress
     */
    public function createCategory(string $name, ?int $parentId = null): ?array
    {
        try {
            $data = [
                'name' => $name,
                'slug' => Str::slug($name),
            ];
            
            if ($parentId) {
                $data['parent'] = $parentId;
            }
            
            $response = $this->client->post('categories', $data);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Failed to create WordPress category', [
                'status' => $response->status(),
                'response' => $response->body(),
                'name' => $name
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Exception creating WordPress category', [
                'error' => $e->getMessage(),
                'name' => $name
            ]);
            
            return null;
        }
    }

    /**
     * Upload media to WordPress
     */
    public function uploadMedia(string $filePath, ?string $title = null, ?string $alt = null): ?array
    {
        try {
            // Get file from storage or temp path
            if (Storage::exists($filePath)) {
                $fileContent = Storage::get($filePath);
                $fileName = basename($filePath);
            } else {
                // Assume it's a path to a real file
                if (!file_exists($filePath)) {
                    Log::error('File not found for WordPress upload', ['path' => $filePath]);
                    return null;
                }
                
                $fileContent = file_get_contents($filePath);
                $fileName = basename($filePath);
            }
            
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
            
            // WordPress requires a special endpoint for media uploads
            $url = rtrim($this->connection->site_url, '/') . '/wp-json/wp/v2/media';
            
            // Create a manual request for media upload
            $client = new Client();
            
            $options = [
                'headers' => [
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Content-Type' => $mimeType,
                ],
                'body' => $fileContent,
            ];
            
            // Add authorization based on auth type
            if ($this->connection->auth_type === 'app_password' || $this->connection->auth_type === 'basic') {
                $options['auth'] = [
                    $this->connection->username, 
                    $this->connection->decrypted_password_attribute
                ];
            } else {
                $options['headers']['Authorization'] = 'Bearer ' . $this->connection->decrypted_access_token_attribute;
            }
            
            $response = $client->post($url, $options);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $result = json_decode($response->getBody()->getContents(), true);
                
                // Update media title and alt text if provided
                if (($title || $alt) && isset($result['id'])) {
                    $this->updateMedia($result['id'], $title, $alt);
                }
                
                return $result;
            }
            
            Log::error('Failed to upload media to WordPress', [
                'status' => $response->getStatusCode(),
                'response' => $response->getBody()->getContents(),
            ]);
            
            return null;
            
        } catch (GuzzleException $e) {
            Log::error('Exception uploading media to WordPress', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            
            return null;
        }
    }

    /**
     * Update media metadata (title, alt text)
     */
    protected function updateMedia(int $mediaId, ?string $title = null, ?string $alt = null): bool
    {
        try {
            $data = [];
            
            if ($title) {
                $data['title'] = $title;
            }
            
            if ($alt) {
                $data['alt_text'] = $alt;
            }
            
            if (empty($data)) {
                return true;
            }
            
            $response = $this->client->post("media/{$mediaId}", $data);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('Exception updating WordPress media', [
                'error' => $e->getMessage(),
                'media_id' => $mediaId
            ]);
            
            return false;
        }
    }

    /**
     * Create a new post in WordPress
     */
    public function createPost(array $data, bool $publish = false): array
    {
        try {
            $postData = [
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => $publish ? 'publish' : 'draft',
            ];
            
            // Set categories if provided
            if (!empty($data['categories'])) {
                $postData['categories'] = $data['categories'];
            }
            
            // Set tags if provided
            if (!empty($data['tags'])) {
                $postData['tags'] = $data['tags'];
            }
            
            // Set featured media if provided
            if (!empty($data['featured_media'])) {
                $postData['featured_media'] = $data['featured_media'];
            }
            
            // Set excerpt if provided
            if (!empty($data['excerpt'])) {
                $postData['excerpt'] = $data['excerpt'];
            }
            
            // Set scheduled date if provided
            if (!empty($data['scheduled_date'])) {
                $postData['status'] = 'future';
                $postData['date'] = $data['scheduled_date'];
            }
            
            // Set slug if provided
            if (!empty($data['slug'])) {
                $postData['slug'] = $data['slug'];
            }
            
            // Set post format if provided
            if (!empty($data['format'])) {
                $postData['format'] = $data['format'];
            }
            
            // Create the post
            $response = $this->client->post('posts', $postData);
            
            if ($response->successful()) {
                $postData = $response->json();
                
                return [
                    'success' => true,
                    'post' => $postData,
                    'message' => $publish ? 'Post published successfully' : 'Draft created successfully',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create post: ' . $response->status(),
                'errors' => $response->json(),
            ];
            
        } catch (RequestException $e) {
            Log::error('WordPress create post failed', [
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? $e->response->body(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'errors' => $e->response->json() ?? ['error' => $e->getMessage()],
            ];
        } catch (\Exception $e) {
            Log::error('WordPress create post exception', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'errors' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Update an existing post in WordPress
     */
    public function updatePost(int $postId, array $data): array
    {
        try {
            $postData = [];
            
            // Only include fields that are provided
            if (isset($data['title'])) {
                $postData['title'] = $data['title'];
            }
            
            if (isset($data['content'])) {
                $postData['content'] = $data['content'];
            }
            
            if (isset($data['status'])) {
                $postData['status'] = $data['status'];
            }
            
            if (isset($data['categories'])) {
                $postData['categories'] = $data['categories'];
            }
            
            if (isset($data['tags'])) {
                $postData['tags'] = $data['tags'];
            }
            
            if (isset($data['featured_media'])) {
                $postData['featured_media'] = $data['featured_media'];
            }
            
            if (isset($data['excerpt'])) {
                $postData['excerpt'] = $data['excerpt'];
            }
            
            if (isset($data['scheduled_date'])) {
                $postData['status'] = 'future';
                $postData['date'] = $data['scheduled_date'];
            }
            
            // Update the post
            $response = $this->client->post("posts/{$postId}", $postData);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'post' => $response->json(),
                    'message' => 'Post updated successfully',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update post: ' . $response->status(),
                'errors' => $response->json(),
            ];
            
        } catch (\Exception $e) {
            Log::error('WordPress update post failed', [
                'error' => $e->getMessage(),
                'post_id' => $postId,
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get a preview URL for a post
     */
    public function getPreviewUrl(int $postId): ?string
    {
        try {
            $response = $this->client->get("posts/{$postId}/revisions");
            
            if ($response->successful()) {
                $revisions = $response->json();
                
                if (!empty($revisions)) {
                    $latestRevision = $revisions[0];
                    
                    // Generate a preview URL
                    return $this->connection->site_url . '?p=' . $postId . '&preview=true';
                }
            }
            
            // Fallback to standard preview
            return $this->connection->site_url . '?p=' . $postId . '&preview=true';
            
        } catch (\Exception $e) {
            Log::error('WordPress get preview URL failed', [
                'error' => $e->getMessage(),
                'post_id' => $postId,
                'connection_id' => $this->connection->id
            ]);
            
            return null;
        }
    }

    /**
     * Get a post by ID
     */
    public function getPost(int $postId): ?array
    {
        try {
            $response = $this->client->get("posts/{$postId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('WordPress get post failed', [
                'error' => $e->getMessage(),
                'post_id' => $postId,
                'connection_id' => $this->connection->id
            ]);
            
            return null;
        }
    }

    /**
     * Publish a draft post
     */
    public function publishPost(int $postId): array
    {
        return $this->updatePost($postId, ['status' => 'publish']);
    }

    /**
     * Schedule a post for future publication
     */
    public function schedulePost(int $postId, string $scheduledDate): array
    {
        return $this->updatePost($postId, [
            'status' => 'future',
            'scheduled_date' => $scheduledDate
        ]);
    }

    /**
     * Fetch post types available in the WordPress site
     */
    public function getPostTypes(): array
    {
        try {
            $response = $this->client->get('types');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'types' => $response->json()
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch post types: ' . $response->status(),
            ];
            
        } catch (\Exception $e) {
            Log::error('WordPress get post types failed', [
                'error' => $e->getMessage(),
                'connection_id' => $this->connection->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
}