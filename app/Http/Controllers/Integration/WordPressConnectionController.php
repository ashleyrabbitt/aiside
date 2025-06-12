<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Integration\WordPressConnection;
use App\Services\Integration\WordPressService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WordPressConnectionController extends Controller
{
    /**
     * Display a listing of the user's WordPress connections.
     */
    public function index(Request $request): JsonResponse
    {
        $connections = $request->user()->wordpressConnections()
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'connections' => $connections
        ]);
    }

    /**
     * Store a new WordPress connection.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'site_url' => 'required|url',
            'auth_type' => ['required', Rule::in(['app_password', 'oauth', 'basic'])],
            'username' => 'required_unless:auth_type,oauth|string|max:255|nullable',
            'password' => 'required_unless:auth_type,oauth|string|nullable',
            'client_id' => 'required_if:auth_type,oauth|string|nullable',
            'client_secret' => 'required_if:auth_type,oauth|string|nullable',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $connection = new WordPressConnection($validator->validated());
            $connection->user_id = $request->user()->id;
            $connection->save();

            // Test the connection
            $service = new WordPressService($connection);
            $testResult = $service->testConnection();

            if (!$testResult['success']) {
                $connection->is_active = false;
                $connection->save();

                return response()->json([
                    'success' => false,
                    'message' => $testResult['message'],
                    'connection' => $connection
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'WordPress connection created successfully',
                'connection' => $connection,
                'test_result' => $testResult
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create WordPress connection', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified WordPress connection.
     */
    public function show(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'connection' => $connection
        ]);
    }

    /**
     * Update the specified WordPress connection.
     */
    public function update(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'site_url' => 'sometimes|url',
            'auth_type' => ['sometimes', Rule::in(['app_password', 'oauth', 'basic'])],
            'username' => 'sometimes|string|max:255|nullable',
            'password' => 'sometimes|string|nullable',
            'client_id' => 'sometimes|string|nullable',
            'client_secret' => 'sometimes|string|nullable',
            'access_token' => 'sometimes|string|nullable',
            'refresh_token' => 'sometimes|string|nullable',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $connection->update($validator->validated());

            // Test the connection if crucial details have changed
            $credentialsChanged = $request->has('site_url') || 
                                  $request->has('username') || 
                                  $request->has('password') || 
                                  $request->has('auth_type') || 
                                  $request->has('client_id') || 
                                  $request->has('client_secret') || 
                                  $request->has('access_token');
            
            if ($credentialsChanged) {
                $service = new WordPressService($connection);
                $testResult = $service->testConnection();

                if (!$testResult['success']) {
                    $connection->is_active = false;
                    $connection->save();

                    return response()->json([
                        'success' => false,
                        'message' => $testResult['message'],
                        'connection' => $connection
                    ], 200);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'WordPress connection updated successfully',
                'connection' => $connection
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update WordPress connection', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified WordPress connection.
     */
    public function destroy(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $connection->delete();

            return response()->json([
                'success' => true,
                'message' => 'WordPress connection deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete WordPress connection', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test the WordPress connection.
     */
    public function test(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $service = new WordPressService($connection);
            $result = $service->testConnection();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to test WordPress connection', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WordPress categories.
     */
    public function getCategories(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $service = new WordPressService($connection);
            $result = $service->getCategories();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WordPress categories', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WordPress tags.
     */
    public function getTags(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $service = new WordPressService($connection);
            $result = $service->getTags();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WordPress tags', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WordPress post types.
     */
    public function getPostTypes(Request $request, WordPressConnection $connection): JsonResponse
    {
        // Check if the user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $service = new WordPressService($connection);
            $result = $service->getPostTypes();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WordPress post types', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
}