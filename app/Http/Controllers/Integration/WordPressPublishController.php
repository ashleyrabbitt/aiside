<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Integration\WordPressConnection;
use App\Models\Integration\WordPressPublishHistory;
use App\Models\UserOpenai;
use App\Services\Integration\WordPressService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WordPressPublishController extends Controller
{
    /**
     * Display publish history for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $history = WordPressPublishHistory::where('user_id', $request->user()->id)
            ->with(['wordpressConnection:id,name,site_url', 'userOpenai:id,title'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Create a new draft WordPress post from AI-generated content.
     */
    public function createDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wordpress_connection_id' => 'required|exists:wordpress_connections,id',
            'user_openai_id' => 'required|exists:user_openai,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'featured_image' => 'nullable|string',
            'featured_image_alt' => 'nullable|string',
            'slug' => 'nullable|string|max:255',
            'post_format' => 'nullable|string|in:standard,aside,chat,gallery,link,image,quote,status,video,audio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Check if the user owns the content and the WordPress connection
            $connection = WordPressConnection::findOrFail($data['wordpress_connection_id']);
            $contentItem = UserOpenai::findOrFail($data['user_openai_id']);

            if ($connection->user_id !== $request->user()->id || $contentItem->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Initialize WordPress service
            $service = new WordPressService($connection);

            // Process featured image if provided
            $featuredMediaId = null;
            if (!empty($data['featured_image'])) {
                $imagePath = $data['featured_image'];
                
                // Upload the image to WordPress
                $uploadResult = $service->uploadMedia(
                    $imagePath,
                    $data['title'],
                    $data['featured_image_alt'] ?? $data['title']
                );
                
                if ($uploadResult && isset($uploadResult['id'])) {
                    $featuredMediaId = $uploadResult['id'];
                }
            }
            
            // Prepare post data
            $postData = [
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'categories' => $data['categories'] ?? [],
                'tags' => $data['tags'] ?? [],
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'format' => $data['post_format'] ?? 'standard',
            ];
            
            if ($featuredMediaId) {
                $postData['featured_media'] = $featuredMediaId;
            }

            // Create the draft post
            $result = $service->createPost($postData, false);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 400);
            }

            // Record the publish history
            $history = new WordPressPublishHistory([
                'user_id' => $request->user()->id,
                'wordpress_connection_id' => $connection->id,
                'user_openai_id' => $contentItem->id,
                'wp_post_id' => $result['post']['id'],
                'title' => $data['title'],
                'status' => 'draft',
                'post_type' => 'post',
                'categories' => $data['categories'] ?? null,
                'tags' => $data['tags'] ?? null,
                'metadata' => [
                    'featured_media' => $featuredMediaId,
                    'excerpt' => $data['excerpt'] ?? null,
                    'slug' => $postData['slug'],
                    'format' => $postData['format']
                ],
                'permalink' => $result['post']['link'] ?? null
            ]);
            $history->save();

            // Get preview URL
            $previewUrl = $service->getPreviewUrl($result['post']['id']);

            return response()->json([
                'success' => true,
                'message' => 'Draft created successfully',
                'post' => $result['post'],
                'history_id' => $history->id,
                'preview_url' => $previewUrl
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create WordPress draft', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'connection_id' => $data['wordpress_connection_id'] ?? null,
                'content_id' => $data['user_openai_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a WordPress post from a draft.
     */
    public function publishDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'history_id' => 'required|exists:wordpress_publish_history,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the publish history record
            $history = WordPressPublishHistory::findOrFail($request->history_id);

            // Check if the user owns this history record
            if ($history->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if the post is already published
            if ($history->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is already published'
                ], 400);
            }

            // Get the WordPress connection
            $connection = $history->wordpressConnection;
            
            if (!$connection || !$connection->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'WordPress connection is not active'
                ], 400);
            }

            // Initialize WordPress service
            $service = new WordPressService($connection);

            // Publish the post
            $result = $service->publishPost($history->wp_post_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 400);
            }

            // Update the history record
            $history->status = 'published';
            $history->published_at = now();
            $history->permalink = $result['post']['link'] ?? $history->permalink;
            $history->save();

            return response()->json([
                'success' => true,
                'message' => 'Post published successfully',
                'post' => $result['post'],
                'permalink' => $history->permalink
            ]);
        } catch (Exception $e) {
            Log::error('Failed to publish WordPress post', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'history_id' => $request->history_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule a WordPress post for future publication.
     */
    public function schedulePost(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'history_id' => 'required|exists:wordpress_publish_history,id',
            'scheduled_date' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the publish history record
            $history = WordPressPublishHistory::findOrFail($request->history_id);

            // Check if the user owns this history record
            if ($history->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if the post is already published
            if ($history->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is already published'
                ], 400);
            }

            // Get the WordPress connection
            $connection = $history->wordpressConnection;
            
            if (!$connection || !$connection->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'WordPress connection is not active'
                ], 400);
            }

            // Initialize WordPress service
            $service = new WordPressService($connection);

            // Format the scheduled date for WordPress
            $scheduledDate = date('c', strtotime($request->scheduled_date));

            // Schedule the post
            $result = $service->schedulePost($history->wp_post_id, $scheduledDate);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 400);
            }

            // Update the history record
            $history->status = 'scheduled';
            $history->scheduled_for = $request->scheduled_date;
            $history->permalink = $result['post']['link'] ?? $history->permalink;
            $history->save();

            return response()->json([
                'success' => true,
                'message' => 'Post scheduled successfully',
                'post' => $result['post'],
                'scheduled_for' => $history->scheduled_for
            ]);
        } catch (Exception $e) {
            Log::error('Failed to schedule WordPress post', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'history_id' => $request->history_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a preview of a WordPress post.
     */
    public function getPreview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'history_id' => 'required|exists:wordpress_publish_history,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the publish history record
            $history = WordPressPublishHistory::findOrFail($request->history_id);

            // Check if the user owns this history record
            if ($history->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Get the WordPress connection
            $connection = $history->wordpressConnection;
            
            if (!$connection || !$connection->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'WordPress connection is not active'
                ], 400);
            }

            // Initialize WordPress service
            $service = new WordPressService($connection);

            // Get the preview URL
            $previewUrl = $service->getPreviewUrl($history->wp_post_id);

            if (!$previewUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate preview URL'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'preview_url' => $previewUrl
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get WordPress preview', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'history_id' => $request->history_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a WordPress draft post.
     */
    public function updateDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'history_id' => 'required|exists:wordpress_publish_history,id',
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'featured_image' => 'nullable|string',
            'featured_image_alt' => 'nullable|string',
            'slug' => 'nullable|string|max:255',
            'post_format' => 'nullable|string|in:standard,aside,chat,gallery,link,image,quote,status,video,audio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Get the publish history record
            $history = WordPressPublishHistory::findOrFail($data['history_id']);

            // Check if the user owns this history record
            if ($history->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if the post is already published
            if ($history->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is already published and cannot be updated as a draft'
                ], 400);
            }

            // Get the WordPress connection
            $connection = $history->wordpressConnection;
            
            if (!$connection || !$connection->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'WordPress connection is not active'
                ], 400);
            }

            // Initialize WordPress service
            $service = new WordPressService($connection);

            // Process featured image if provided
            $featuredMediaId = null;
            if (!empty($data['featured_image'])) {
                $imagePath = $data['featured_image'];
                
                // Upload the image to WordPress
                $uploadResult = $service->uploadMedia(
                    $imagePath,
                    $data['title'] ?? $history->title,
                    $data['featured_image_alt'] ?? ($data['title'] ?? $history->title)
                );
                
                if ($uploadResult && isset($uploadResult['id'])) {
                    $featuredMediaId = $uploadResult['id'];
                }
            }
            
            // Prepare post data
            $postData = [];
            
            if (isset($data['title'])) {
                $postData['title'] = $data['title'];
            }
            
            if (isset($data['content'])) {
                $postData['content'] = $data['content'];
            }
            
            if (isset($data['excerpt'])) {
                $postData['excerpt'] = $data['excerpt'];
            }
            
            if (isset($data['categories'])) {
                $postData['categories'] = $data['categories'];
            }
            
            if (isset($data['tags'])) {
                $postData['tags'] = $data['tags'];
            }
            
            if (isset($data['slug'])) {
                $postData['slug'] = $data['slug'];
            }
            
            if (isset($data['post_format'])) {
                $postData['format'] = $data['post_format'];
            }
            
            if ($featuredMediaId) {
                $postData['featured_media'] = $featuredMediaId;
            }

            // Update the post
            $result = $service->updatePost($history->wp_post_id, $postData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 400);
            }

            // Update the history record
            if (isset($data['title'])) {
                $history->title = $data['title'];
            }
            
            if (isset($data['categories'])) {
                $history->categories = $data['categories'];
            }
            
            if (isset($data['tags'])) {
                $history->tags = $data['tags'];
            }
            
            $metadata = $history->metadata ?? [];
            
            if ($featuredMediaId) {
                $metadata['featured_media'] = $featuredMediaId;
            }
            
            if (isset($data['excerpt'])) {
                $metadata['excerpt'] = $data['excerpt'];
            }
            
            if (isset($data['slug'])) {
                $metadata['slug'] = $data['slug'];
            }
            
            if (isset($data['post_format'])) {
                $metadata['format'] = $data['post_format'];
            }
            
            $history->metadata = $metadata;
            $history->permalink = $result['post']['link'] ?? $history->permalink;
            $history->save();

            // Get preview URL
            $previewUrl = $service->getPreviewUrl($history->wp_post_id);

            return response()->json([
                'success' => true,
                'message' => 'Draft updated successfully',
                'post' => $result['post'],
                'preview_url' => $previewUrl
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update WordPress draft', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'history_id' => $data['history_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Direct publish: Create and immediately publish a WordPress post.
     */
    public function directPublish(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wordpress_connection_id' => 'required|exists:wordpress_connections,id',
            'user_openai_id' => 'required|exists:user_openai,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'featured_image' => 'nullable|string',
            'featured_image_alt' => 'nullable|string',
            'slug' => 'nullable|string|max:255',
            'post_format' => 'nullable|string|in:standard,aside,chat,gallery,link,image,quote,status,video,audio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Transaction to ensure everything completes successfully
            return DB::transaction(function () use ($request, $data) {
                // Check if the user owns the content and the WordPress connection
                $connection = WordPressConnection::findOrFail($data['wordpress_connection_id']);
                $contentItem = UserOpenai::findOrFail($data['user_openai_id']);

                if ($connection->user_id !== $request->user()->id || $contentItem->user_id !== $request->user()->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 403);
                }

                // Initialize WordPress service
                $service = new WordPressService($connection);

                // Process featured image if provided
                $featuredMediaId = null;
                if (!empty($data['featured_image'])) {
                    $imagePath = $data['featured_image'];
                    
                    // Upload the image to WordPress
                    $uploadResult = $service->uploadMedia(
                        $imagePath,
                        $data['title'],
                        $data['featured_image_alt'] ?? $data['title']
                    );
                    
                    if ($uploadResult && isset($uploadResult['id'])) {
                        $featuredMediaId = $uploadResult['id'];
                    }
                }
                
                // Prepare post data
                $postData = [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'excerpt' => $data['excerpt'] ?? '',
                    'categories' => $data['categories'] ?? [],
                    'tags' => $data['tags'] ?? [],
                    'slug' => $data['slug'] ?? Str::slug($data['title']),
                    'format' => $data['post_format'] ?? 'standard',
                ];
                
                if ($featuredMediaId) {
                    $postData['featured_media'] = $featuredMediaId;
                }

                // Create and publish the post immediately
                $result = $service->createPost($postData, true);

                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'errors' => $result['errors'] ?? null
                    ], 400);
                }

                // Record the publish history
                $history = new WordPressPublishHistory([
                    'user_id' => $request->user()->id,
                    'wordpress_connection_id' => $connection->id,
                    'user_openai_id' => $contentItem->id,
                    'wp_post_id' => $result['post']['id'],
                    'title' => $data['title'],
                    'status' => 'published',
                    'post_type' => 'post',
                    'categories' => $data['categories'] ?? null,
                    'tags' => $data['tags'] ?? null,
                    'published_at' => now(),
                    'metadata' => [
                        'featured_media' => $featuredMediaId,
                        'excerpt' => $data['excerpt'] ?? null,
                        'slug' => $postData['slug'],
                        'format' => $postData['format']
                    ],
                    'permalink' => $result['post']['link'] ?? null
                ]);
                $history->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Post published successfully',
                    'post' => $result['post'],
                    'history_id' => $history->id,
                    'permalink' => $history->permalink
                ]);
            });
        } catch (Exception $e) {
            Log::error('Failed to direct publish WordPress post', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'connection_id' => $data['wordpress_connection_id'] ?? null,
                'content_id' => $data['user_openai_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish post: ' . $e->getMessage()
            ], 500);
        }
    }
}