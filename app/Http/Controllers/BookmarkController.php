<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    // Menampilkan semua bookmark pengguna
    public function index()
    {
        $bookmarks = Bookmark::with(['story'])->where('user_id', Auth::id())->get();
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

        return response()->json([
            'message' => 'Bookmark berhasil dihapus.'
        ], 204);
    }
}
