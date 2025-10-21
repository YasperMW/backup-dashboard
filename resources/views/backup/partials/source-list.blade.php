<div id="source-directory-list-container">
<ul class="mb-4">
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
        $dirs = App\Models\BackupSourceDirectory::query()
            ->when(!$isAdmin, fn($q) => $q->where('user_id', auth()->id()))
            ->orderByDesc('created_at')
            ->get();
    @endphp
    @foreach($dirs as $dir)
        <li class="flex items-center justify-between py-1">
            <span>{{ $dir->path }}</span>
            @if($isAdmin || $dir->user_id === auth()->id())
                <form method="POST" action="{{ route('backup.deleteSourceDirectory', $dir->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
                </form>
            @endif
        </li>
    @endforeach
</ul>
</div>