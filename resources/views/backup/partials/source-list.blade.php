<div id="source-directory-list-container">
<ul class="mb-4">
    @foreach(App\Models\BackupSourceDirectory::all() as $dir)
        <li class="flex items-center justify-between py-1">
            <span>{{ $dir->path }}</span>
            <form method="POST" action="{{ route('backup.deleteSourceDirectory', $dir->id) }}" onsubmit="return confirm('Are you sure you want to remove this directory?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
            </form>
        </li>
    @endforeach
</ul>
</div> 