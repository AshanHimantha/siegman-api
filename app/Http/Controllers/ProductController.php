<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management APIs"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Get all products",
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of products"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with('category');

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $products = $query->get();
            
            // Add full URLs for image and catalog_pdf
            $products->each(function ($product) {
                if ($product->image) {
                    $product->image_url = asset('storage/' . $product->image);
                }
                if ($product->catalog_pdf) {
                    $product->catalog_pdf_url = asset('storage/' . $product->catalog_pdf);
                }
            });
            
            return ApiResponse::success($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve products: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve products');
        }
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "category_id"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="catalog_pdf", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product created"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
                'catalog_pdf' => 'nullable|file|mimes:pdf|max:10240',
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                try {
                    $imagePath = $request->file('image')->store('products/images', 'public');
                    $data['image'] = $imagePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload image', 500);
                }
            }

            // Handle catalog PDF upload
            if ($request->hasFile('catalog_pdf')) {
                try {
                    $pdfPath = $request->file('catalog_pdf')->store('products/catalogs', 'public');
                    $data['catalog_pdf'] = $pdfPath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload catalog PDF: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload catalog PDF', 500);
                }
            }

            $product = Product::create($data);
            $product->load('category');
            
            // Add full URLs to response
            if ($product->image) {
                $product->image_url = asset('storage/' . $product->image);
            }
            if ($product->catalog_pdf) {
                $product->catalog_pdf_url = asset('storage/' . $product->catalog_pdf);
            }
            
            return ApiResponse::created($product, 'Product created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error('Failed to create product: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to create product');
        }
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Get a specific product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product details"),
     *     @OA\Response(response=404, description="Product not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function show($id)
    {
        try {
            $product = Product::with('category')->find($id);

            if (!$product) {
                return ApiResponse::notFound('Product not found');
            }

            // Add full URLs
            if ($product->image) {
                $product->image_url = asset('storage/' . $product->image);
            }
            if ($product->catalog_pdf) {
                $product->catalog_pdf_url = asset('storage/' . $product->catalog_pdf);
            }

            return ApiResponse::success($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve product: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve product');
        }
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Replace/update a product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="catalog_pdf", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated"),
     *     @OA\Response(response=404, description="Product not found"),
     *     security={{"sanctum":{}}}
     * )
     * @OA\Patch(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Partially update a product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="catalog_pdf", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated"),
     *     @OA\Response(response=404, description="Product not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return ApiResponse::notFound('Product not found');
            }

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'category_id' => 'sometimes|required|exists:categories,id',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
                'catalog_pdf' => 'nullable|file|mimes:pdf|max:10240',
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                try {
                    // Delete old image if exists
                    if ($product->image && Storage::disk('public')->exists($product->image)) {
                        Storage::disk('public')->delete($product->image);
                    }
                    
                    $imagePath = $request->file('image')->store('products/images', 'public');
                    $data['image'] = $imagePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload image', 500);
                }
            }

            // Handle catalog PDF upload
            if ($request->hasFile('catalog_pdf')) {
                try {
                    // Delete old PDF if exists
                    if ($product->catalog_pdf && Storage::disk('public')->exists($product->catalog_pdf)) {
                        Storage::disk('public')->delete($product->catalog_pdf);
                    }
                    
                    $pdfPath = $request->file('catalog_pdf')->store('products/catalogs', 'public');
                    $data['catalog_pdf'] = $pdfPath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload catalog PDF: ' . $e->getMessage());
                    return ApiResponse::error('Failed to upload catalog PDF', 500);
                }
            }

            $product->update($data);
            $product->load('category');
            
            // Add full URLs to response
            if ($product->image) {
                $product->image_url = asset('storage/' . $product->image);
            }
            if ($product->catalog_pdf) {
                $product->catalog_pdf_url = asset('storage/' . $product->catalog_pdf);
            }
            
            return ApiResponse::success($product, 'Product updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error('Failed to update product: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to update product');
        }
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product deleted"),
     *     @OA\Response(response=404, description="Product not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return ApiResponse::notFound('Product not found');
            }

            // Delete image file if exists
            if ($product->image) {
                try {
                    if (Storage::disk('public')->exists($product->image)) {
                        Storage::disk('public')->delete($product->image);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete image: ' . $e->getMessage());
                    // Continue with product deletion
                }
            }

            // Delete catalog PDF if exists
            if ($product->catalog_pdf) {
                try {
                    if (Storage::disk('public')->exists($product->catalog_pdf)) {
                        Storage::disk('public')->delete($product->catalog_pdf);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete catalog PDF: ' . $e->getMessage());
                    // Continue with product deletion
                }
            }

            $product->delete();
            return ApiResponse::success(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to delete product');
        }
    }
}
