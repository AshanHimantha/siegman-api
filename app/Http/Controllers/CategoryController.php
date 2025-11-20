<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Category management APIs"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Get all categories",
     *     @OA\Response(response=200, description="List of categories"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function index()
    {
        try {
            $categories = Category::all();
            
            // Add full image URLs
            $categories->each(function ($category) {
                if ($category->image) {
                    $category->image_url = asset('storage/' . $category->image);
                }
            });
            
            return ApiResponse::success($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve categories: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve categories');
        }
    }

    /**
     * @OA\Post(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Create a new category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255|unique:categories',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            ]);

            if ($request->hasFile('image')) {
                try {
                    $imagePath = $request->file('image')->store('categories', 'public');
                    $data['image'] = $imagePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload image', 500);
                }
            }

            $category = Category::create($data);
            
            // Add full image URL to response
            if ($category->image) {
                $category->image_url = asset('storage/' . $category->image);
            }
            
            return ApiResponse::created($category, 'Category created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error('Failed to create category: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to create category');
        }
    }

    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get a specific category",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function show($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return ApiResponse::notFound('Category not found');
            }

            // Add full image URL
            if ($category->image) {
                $category->image_url = asset('storage/' . $category->image);
            }

            return ApiResponse::success($category, 'Category retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve category: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve category');
        }
    }

    /**
     * @OA\Post(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update a category",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"PUT"})
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated"),
     *     @OA\Response(response=404, description="Category not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return ApiResponse::notFound('Category not found');
            }

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            ]);

            if ($request->hasFile('image')) {
                try {
                    // Delete old image if exists
                    if ($category->image && Storage::disk('public')->exists($category->image)) {
                        Storage::disk('public')->delete($category->image);
                    }
                    
                    $imagePath = $request->file('image')->store('categories', 'public');
                    $data['image'] = $imagePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload image', 500);
                }
            }

            $category->update($data);
            
            // Add full image URL to response
            if ($category->image) {
                $category->image_url = asset('storage/' . $category->image);
            }
            
            return ApiResponse::success($category, 'Category updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error('Failed to update category: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to update category');
        }
    }

    /**
     * @OA\Delete(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete a category",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category deleted"),
     *     @OA\Response(response=404, description="Category not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return ApiResponse::notFound('Category not found');
            }

            // Delete image file if exists
            if ($category->image) {
                try {
                    if (Storage::disk('public')->exists($category->image)) {
                        Storage::disk('public')->delete($category->image);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete image: ' . $e->getMessage());
                    // Continue with category deletion even if image deletion fails
                }
            }

            $category->delete();
            return ApiResponse::success(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete category: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to delete category');
        }
    }
}

