<x-user-dashboard>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold md:mt-0">Edit Pengguna</h1>
    <div class="flex items-center gap-4">
      <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
        Kembali
      </a>
    </div>
  </div>

  @if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white rounded-xl shadow-sm p-6">
    <form method="POST" action="{{ route('admin.users.update', $targetUser->id) }}">
      @csrf
      @method('PUT')
      
      <div class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
          <input type="text" name="name" id="name" value="{{ old('name', $targetUser->name) }}" required
                 class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-babypink-500 focus:border-babypink-500">
          @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" name="email" id="email" value="{{ old('email', $targetUser->email) }}" required
                 class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-babypink-500 focus:border-babypink-500">
          @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="noHP" class="block text-sm font-medium text-gray-700">No HP</label>
          <input type="text" name="noHP" id="noHP" value="{{ old('noHP', $targetUser->noHP) }}"
                 class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-babypink-500 focus:border-babypink-500">
          @error('noHP')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
          <select name="role" id="role" required
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-babypink-500 focus:border-babypink-500">
            <option value="customer" {{ old('role', $targetUser->role) === 'customer' ? 'selected' : '' }}>Customer</option>
            <option value="admin" {{ old('role', $targetUser->role) === 'admin' ? 'selected' : '' }}>Admin</option>
          </select>
          @error('role')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex gap-4">
          <button type="submit" class="px-4 py-2 bg-babypink-500 text-white rounded-md hover:bg-babypink-600">
            Simpan Perubahan
          </button>
          <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
            Batal
          </a>
        </div>
      </div>
    </form>
  </div>
</x-user-dashboard>

