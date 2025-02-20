<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    // Menampilkan semua bookmark pengguna beserta data pengguna pembuat cerita
    public function index()
    {
        // Mengambil semua bookmark pengguna beserta data cerita, pengguna pembuat cerita, dan kategori
        $bookmarks = Bookmark::with(['story.user', 'story.category', 'story.content_images'])->where('user_id', Auth::id())->get();
    
        // Mengembalikan respons JSON yang mencakup pesan dan data bookmark
        return response()->json([
            'message' => 'Bookmark berhasil diambil.',
            'data' => $bookmarks
        ]);
    }
    
    

    // Menyimpan bookmark baru
    public function store(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
        ]);

        $bookmark = Bookmark::create([
            'user_id' => Auth::id(),
            'story_id' => $request->story_id,
        ]);

        return response()->json([
            'message' => 'Bookmark berhasil ditambahkan.',
            'data' => $bookmark
        ], 201);
    }

    // Menghapus bookmark
    public function destroy($id)
    {
        $bookmark = Bookmark::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        $bookmark->delete();

        return response()->json([], 204);
    }
}
