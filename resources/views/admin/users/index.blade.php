<x-user-dashboard>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold md:mt-0">Manajemen Pengguna</h1>
    <div class="flex items-center gap-4">
      <x-notification-button :count="3" />
      <x-profile-dropdown username="Akun Saya" x-cloak />
    </div>
  </div>

  @if (session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
      {{ session('success') }}
    </div>
  @endif

  @if (session('error'))
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
      {{ session('error') }}
    </div>
  @endif

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No HP</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email Verified</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse ($users as $user)
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->noHP ?? '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                  {{ $user->role }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                @if($user->email_verified_at)
                  <span class="text-green-600">✓ Verified</span>
                @else
                  <span class="text-red-600">✗ Not Verified</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <a href="{{ route('admin.users.show', $user->id) }}" class="text-babypink-600 hover:text-babypink-900">Lihat</a>
                  <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                  @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada pengguna</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
      {{ $users->links() }}
    </div>
  </div>
</x-user-dashboard>

