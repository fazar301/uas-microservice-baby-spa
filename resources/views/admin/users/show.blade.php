<x-user-dashboard>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold md:mt-0">Detail Pengguna</h1>
    <div class="flex items-center gap-4">
      <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
        Kembali
      </a>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">ID</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->id }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Nama</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->name }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->email }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">No HP</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->noHP ?? '-' }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Role</label>
        <p class="mt-1">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $targetUser->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
            {{ $targetUser->role }}
          </span>
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email Verified</label>
        <p class="mt-1 text-sm text-gray-900">
          @if($targetUser->email_verified_at)
            <span class="text-green-600">✓ Verified pada {{ $targetUser->email_verified_at->format('d/m/Y H:i') }}</span>
          @else
            <span class="text-red-600">✗ Not Verified</span>
          @endif
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Dibuat</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->created_at->format('d/m/Y H:i') }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Diperbarui</label>
        <p class="mt-1 text-sm text-gray-900">{{ $targetUser->updated_at->format('d/m/Y H:i') }}</p>
      </div>
    </div>
    <div class="mt-6 flex gap-4">
      <a href="{{ route('admin.users.edit', $targetUser->id) }}" class="px-4 py-2 bg-babypink-500 text-white rounded-md hover:bg-babypink-600">
        Edit Pengguna
      </a>
      @if($targetUser->id !== auth()->id())
        <form action="{{ route('admin.users.destroy', $targetUser->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
            Hapus Pengguna
          </button>
        </form>
      @endif
    </div>
  </div>
</x-user-dashboard>

