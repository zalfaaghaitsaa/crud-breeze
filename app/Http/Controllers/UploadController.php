<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UploadController extends Controller
{
    // Menampilkan daftar berkas milik pengguna
    public function index(): View
    {
        $uploads = Upload::where('user_id', Auth::id())
            ->latest()
            ->paginate(5);

        return view('uploads.index', compact('uploads'));
    }

    // Menampilkan form upload
    public function create(): View
    {
        return view('uploads.create');
    }

    // Menyimpan berkas baru
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        Upload::create([
            'user_id' => Auth::id(),
            'judul' => $request->judul,
            'keterangan' => $request->keterangan,
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
        ]);

        return redirect()
            ->route('uploads.index')
            ->with('success', 'Berkas berhasil diunggah');
    }

    // Menampilkan detail berkas
    public function show(Upload $upload): View
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        return view('uploads.show', compact('upload'));
    }

    // Menampilkan form edit
    public function edit(Upload $upload): View
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        return view('uploads.edit', compact('upload'));
    }

    // Memperbarui berkas
    public function update(
        Request $request,
        Upload $upload
    ): RedirectResponse {
        abort_if($upload->user_id !== Auth::id(), 403);

        $request->validate([
            'judul' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        $data = $request->only('judul', 'keterangan');

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('uploads', 'public');

            Storage::disk('public')->delete($upload->filepath);

            $data['filename'] = $file->getClientOriginalName();
            $data['filepath'] = $path;
        }

        $upload->update($data);

        return redirect()
            ->route('uploads.index')
            ->with('success', 'Berkas berhasil diperbarui');
    }

    // Menghapus berkas
    public function destroy(Upload $upload): RedirectResponse
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        Storage::disk('public')->delete($upload->filepath);
        $upload->delete();

        return redirect()
            ->route('uploads.index')
            ->with('success', 'Berkas berhasil dihapus');
    }
}
