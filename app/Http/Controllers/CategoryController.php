<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Menampilkan semua kategori
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'message' => 'Kategori berhasil diambil.',
            'data' => $categories
        ]);
    }

    // Menyimpan kategori baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category
        ], 201);
    }

    // Menampilkan kategori berdasarkan ID
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json([
            'message' => 'Kategori berhasil ditemukan.',
            'data' => $category
        ]);
    }

    // Memperbarui kategori
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category
        ]);
    }

    // Menghapus kategori
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.'
        ], 204);
    }
}
