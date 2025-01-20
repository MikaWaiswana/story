<?php  
  
namespace App\Http\Controllers;  
  
use App\Models\Story;  
use App\Models\ContentImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller  
{  
    // Menampilkan semua cerita  
    public function index()  
    {  
        $stories = Story::with(['user', 'category', 'content_images'])->get();  
        return response()->json([  
            'message' => 'Cerita berhasil diambil.',  
            'data' => $stories  
        ]);  
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
                $path = $image->storeAs('images', $originalName, 'public');      
                
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
        return response()->json([  
            'message' => 'Cerita berhasil ditemukan.',  
            'data' => $story  
        ]);  
    }  
  
    public function update(Request $request, $id)    
    {    
        $request->validate([    
            'category_id' => 'required|exists:categories,id',    
            'title' => 'required|string|max:255',    
            'content' => 'required|string',    
            'content_images' => 'nullable|array|max:5',    
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',    
        ]);    
    
        try {  
            $story = Story::findOrFail($id);    
        } catch (ModelNotFoundException $e) {  
            return response()->json(['message' => 'Cerita tidak ditemukan.'], 404);  
        }  
    
        $story->update($request->only(['category_id', 'title', 'content']));    
    
        // Menyimpan gambar baru jika ada    
        if ($request->hasFile('content_images')) {      
            // Hapus gambar lama jika perlu    
            foreach ($story->content_images as $image) {      
                Storage::disk('public')->delete($image->path);      
                $image->delete();      
            }    
        
            foreach ($request->file('content_images') as $image) {      
                // Mengambil nama asli file  
                $originalName = $image->getClientOriginalName();  
                
                // Menyimpan gambar dengan nama asli di folder 'images'  
                $path = $image->storeAs('images', $originalName, 'public');      
                
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
  
    // Menghapus gambar tertentu dari cerita  
    public function deleteImage($imageId)  
    {  
        $contentImage = ContentImage::findOrFail($imageId);  
          
        // Hapus file dari storage  
        Storage::disk('public')->delete($contentImage->path);  
        $contentImage->delete();  
  
        return response()->json([  
            'message' => 'Gambar berhasil dihapus.'  
        ], 204);  
    }  
}  
