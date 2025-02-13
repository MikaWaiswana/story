<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\ContentImage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    // Menampilkan semua cerita  
    public function index(Request $request)
    {
        // Mengambil parameter pencarian dari query string (opsional)
        $search = $request->input('search');
    
        // Membangun query untuk mengambil cerita dengan pencarian dan pagination
        $stories = Story::with(['user', 'category', 'content_images'])
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%$search%")
                    ->orWhere('content', 'like', "%$search%")
                    ->orWhereHas('category', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    });
            })
            ->paginate(12); // Pagination 12 cerita per halaman
    
        // Jika tidak ada cerita yang ditemukan setelah pencarian
        if ($stories->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada cerita yang ditemukan.'
            ], 404);
        }
    
        return response()->json([
            'message' => 'Cerita berhasil ditemukan.',
            'data' => $stories
        ], 200);
    }
    


    // Menyimpan cerita baru  
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_images' => 'nullable|array|max:5', // Batasi maksimal 5 gambar  
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $story = Story::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'content' => $request->content,
        ]);

        // Menyimpan gambar jika ada    
        if ($request->hasFile('content_images')) {
            foreach ($request->file('content_images') as $image) {
                // Mengambil nama asli file  
                $originalName = $image->getClientOriginalName();

                // Menyimpan gambar dengan nama asli di folder 'images'  
                $path = $image->storeAs('content_images', $originalName, 'public');

                // Menyimpan gambar dengan story_id    
                $story->content_images()->create([
                    'path' => $path,
                    'story_id' => $story->id, // Menyimpan story_id    
                ]);
            }
        }

        return response()->json([
            'message' => 'Cerita berhasil ditambahkan.',
            'data' => $story->load('content_images')
        ], 201);
    }

    // Menampilkan cerita berdasarkan ID  
    public function show($id)
    {
        $story = Story::with(['user', 'category', 'content_images'])->findOrFail($id);
        Log::info($story);
        return response()->json([
            'message' => 'Cerita berhasil ditemukan.',
            'data' => $story
        ]);
    }

    public function getSimilarStories($id)
    {
        // Menemukan cerita berdasarkan ID
        $story = Story::findOrFail($id);

        // Mengambil cerita dengan kategori yang sama, tidak termasuk cerita yang sedang dilihat
        $similarStories = Story::with(['user', 'category', 'content_images'])
            ->where('category_id', $story->category_id)
            ->where('id', '!=', $story->id) // Menghindari cerita yang sama
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->paginate(3); // Batasi 3 cerita per halaman

        // Jika tidak ada cerita serupa
        if ($similarStories->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada cerita serupa.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita serupa berhasil ditemukan.',
            'data' => $similarStories
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_images' => 'nullable|array|max:5',
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'exists:content_images,id', // Validasi ID gambar yang akan dihapus
        ]);
    
        try {
            $story = Story::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Cerita tidak ditemukan.'], 404);
        }
    
        // Menghitung jumlah gambar yang ada setelah penghapusan
        $existingImagesCount = $story->content_images()->count();
    
        // Menghapus gambar yang dipilih berdasarkan ID
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images') as $imageId) {
                $image = ContentImage::find($imageId);
                if ($image && $image->story_id == $story->id) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                    $existingImagesCount--; // Kurangi jumlah gambar yang ada
                }
            }
        }
    
        // Validasi jumlah gambar setelah penghapusan
        $newImagesCount = $request->has('content_images') ? count($request->file('content_images')) : 0;
        if ($existingImagesCount + $newImagesCount > 5) {
            return response()->json(['message' => 'Jumlah gambar tidak boleh lebih dari 5.'], 400);
        }
    
        // Memperbarui cerita
        $story->update($request->only(['category_id', 'title', 'content']));
    
        // Menyimpan gambar baru jika ada    
        if ($request->hasFile('content_images')) {
            foreach ($request->file('content_images') as $image) {
                // Mengambil nama asli file  
                $originalName = $image->getClientOriginalName();
    
                // Menyimpan gambar dengan nama asli di folder 'images'  
                $path = $image->storeAs('content_images', $originalName, 'public');
    
                $story->content_images()->create(['path' => $path]);
            }
        }
    
        return response()->json([
            'message' => 'Cerita berhasil diperbarui.',
            'data' => $story->load('content_images')
        ], 200);
    }    


    // Menghapus cerita  
    public function destroy($id)
    {
        $story = Story::findOrFail($id);

        // Hapus semua gambar terkait  
        foreach ($story->content_images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        $story->delete();

        return response()->json([
            'message' => 'Cerita berhasil dihapus.'
        ], 204);
    }

    public function deleteImages(Request $request)
    {
        // Validasi input untuk memastikan bahwa 'image_ids' adalah array dan tidak kosong
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:content_images,id', // Pastikan setiap ID ada di tabel content_images
        ]);
    
        // Menghapus gambar berdasarkan ID yang diberikan
        foreach ($request->input('image_ids') as $imageId) {
            $contentImage = ContentImage::findOrFail($imageId);
    
            // Hapus file dari storage
            Storage::disk('public')->delete($contentImage->path);
            $contentImage->delete();
        }
    
        return response()->json([
            'message' => 'Gambar berhasil dihapus.'
        ], 204);
    }
    

    // Menampilkan cerita berdasarkan kategori ID
    public function getByCategoryId($categoryId)
    {
        // Mengambil semua cerita yang sesuai dengan kategori ID
        $stories = Story::with(['user', 'content_images'])
            ->where('category_id', $categoryId)
            ->get();

        // Memeriksa apakah cerita ditemukan
        if ($stories->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada cerita yang ditemukan untuk kategori ini.',
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita berhasil ditemukan.',
            'data' => $stories
        ], 200);
    }

    // Menampilkan cerita milik pengguna yang sedang login
    public function myStories()
    {
        $userId = Auth::id(); // Mengambil ID pengguna yang sedang login

        // Mengambil cerita milik pengguna yang sedang login
        $stories = Story::with(['category', 'content_images'])
            ->where('user_id', $userId)
            ->get();

        // Mengambil data pengguna berdasarkan ID
        $user = User::find($userId);

        // Jika pengguna tidak memiliki cerita
        if ($stories->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum memiliki cerita.',
                'user' => $user // Menambahkan data pengguna ke respons
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita milik Anda berhasil ditemukan.',
            'data' => $stories,
            'user' => $user // Menambahkan data pengguna ke respons
        ], 200);
    }

    // Mendapatkan cerita terbaru
    public function getNewestStory()
    {
        // Mengambil cerita terbaru berdasarkan waktu pembuatan
        $newestStory = Story::with(['user', 'category', 'content_images'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Jika tidak ada cerita dalam database
        if (!$newestStory) {
            return response()->json([
                'message' => 'Belum ada cerita yang tersedia.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita terbaru berhasil ditemukan.',
            'data' => $newestStory
        ], 200);
    }

    public function getLatestStories()
    {
        // Mengambil cerita terbaru berdasarkan waktu pembuatan dengan pagination
        $stories = Story::with(['user', 'category', 'content_images'])
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        // Jika tidak ada cerita dalam database
        if ($stories->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada cerita yang tersedia.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita terbaru berhasil ditemukan.',
            'data' => $stories
        ], 200);
    }

    public function getPopularStories()
    {
        // Mengambil cerita berdasarkan jumlah bookmark terbanyak
        $popularStories = Story::with(['user', 'category', 'content_images'])
            ->withCount('bookmarks') // Hitung jumlah bookmark untuk setiap cerita
            ->orderBy('bookmarks_count', 'desc') // Urutkan berdasarkan jumlah bookmark terbanyak
            ->paginate(12); // Pagination default 12 item per halaman

        // Jika tidak ada cerita dalam database
        if ($popularStories->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada cerita populer yang tersedia.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita populer berhasil ditemukan.',
            'data' => $popularStories
        ], 200);
    }

    public function getStoriesAZ()
    {
        // Mengambil cerita yang diurutkan berdasarkan judul (A-Z)
        $storiesAZ = Story::with(['user', 'category', 'content_images'])
            ->orderBy('title', 'asc') // Urutkan berdasarkan judul (A-Z)
            ->paginate(12); // Pagination default 12 item per halaman

        // Jika tidak ada cerita dalam database
        if ($storiesAZ->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada cerita yang tersedia.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita berhasil ditemukan.',
            'data' => $storiesAZ
        ], 200);
    }

    public function getStoriesZA()
    {
        // Mengambil cerita yang diurutkan berdasarkan judul (Z-A)
        $storiesZA = Story::with(['user', 'category', 'content_images'])
            ->orderBy('title', 'desc') // Urutkan berdasarkan judul (Z-A)
            ->paginate(12); // Pagination default 12 item per halaman

        // Jika tidak ada cerita dalam database
        if ($storiesZA->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada cerita yang tersedia.'
            ], 404);
        }

        return response()->json([
            'message' => 'Cerita berhasil ditemukan.',
            'data' => $storiesZA
        ], 200);
    }
}
