<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>BabySpa - Dashboard</title>
  @vite('resources/css/app.css','resources/js/app.js')
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen flex">
    <!-- Sidebar (Desktop Only) -->
    <div class="hidden md:flex md:static left-0 top-0 bottom-0 w-64 bg-white border-r flex-col">
      <div class="flex items-center gap-4 px-6 py-5 border-b">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-babypink-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
        <span class="text-xl font-semibold">BabySpa</span>
      </div>
      <div class="flex-1 px-4 py-6 space-y-2">
        <a href="/dashboard" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('dashboard') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span class="font-medium">Beranda</span>
        </a>
        <a href="/reservasi" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('reservasi*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          <span class="font-medium">Reservasi</span>
        </a>
        <a href="/transaksi" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('transaksi*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
          <span class="font-medium">Transaksi</span>
        </a>
        <a href="/layanan" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('layanan*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package"><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/></svg>
          <span class="font-medium">Layanan</span>
        </a>
        
        @if(auth()->check() && auth()->user()->isAdmin())
          <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('admin/users*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span class="font-medium">Manajemen Pengguna</span>
          </a>
          <a href="{{ route('reservasi.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('reservasi*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
            <span class="font-medium">Reservasi (Service B)</span>
          </a>
          <a href="{{ route('booking.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('booking*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hand-coins"><path d="M12 15V9"/><path d="M10 12h6"/><path d="M7 7h1"/><path d="M14 6h1"/><path d="M7 3h8"/><path d="M5 3a2 2 0 0 0-2 2v2c0 1.1.9 2 2 2h2"/><path d="M17 4h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5.5l-3.72-2.68a2 2 0 0 0-1.11-.36H7a2 2 0 0 0-2 2 2 2 0 0 0 2 2h3.5"/><path d="M16 18h.01"/></svg>
            <span class="font-medium">Booking (Service C)</span>
          </a>
        @endif
        <a href="/settings" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->is('settings*') ? 'bg-babypink-50 text-babypink-600 font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
          <span class="font-medium">Pengaturan</span>
        </a>
      </div>
      
      <div class="p-4 mt-auto border-t">
        <div class="bg-gradient-to-br from-babypink-50 via-babyblue-50 to-babypink-50 p-5 rounded-xl shadow-sm">
          <h3 class="font-semibold text-sm text-gray-800">Perlu bantuan?</h3>
          <p class="text-sm text-gray-600 my-2">Silakan hubungi dukungan kami jika Anda memiliki pertanyaan.</p>
          <button class="w-full mt-3 py-2 px-4 rounded-md text-sm text-white font-medium bg-babypink-500 hover:bg-babypink-600 transition-colors duration-200">
            Hubungi Kami
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-4 md:p-6 ml-0 pt-6">
      {{ $slot }}
    </div>
  </div>

  <!-- Bottom Navigation Bar (Mobile Only) -->
  <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 md:hidden">
    <div class="grid grid-cols-4 h-16">
      <!-- Home -->
      <a href="/dashboard" class="nav-item pt-2 {{ request()->is('dashboard') ? 'active text-babypink-600 font-medium shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mb-1">
          <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span class="text-xs">Beranda</span>
      </a>

      <!-- Reservations -->
      <a href="/reservasi" class="nav-item pt-2 {{ request()->is('reservasi*') ? 'active text-babypink-600 font-medium shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mb-1">
          <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
          <line x1="16" x2="16" y1="2" y2="6"/>
          <line x1="8" x2="8" y1="2" y2="6"/>
          <line x1="3" x2="21" y1="10" y2="10"/>
        </svg>
        <span class="text-xs">Reservasi</span>
      </a>

      <!-- Transactions -->
      <a href="/transaksi" class="nav-item pt-2 {{ request()->is('transaksi*') ? 'active text-babypink-600 font-medium shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mb-1">
          <rect width="20" height="14" x="2" y="5" rx="2"/>
          <line x1="2" x2="22" y1="10" y2="10"/>
        </svg>
        <span class="text-xs">Transaksi</span>
      </a>

      <!-- Settings -->
      <a href="/settings" class="nav-item pt-2 {{ request()->is('settings*') ? 'active text-babypink-600 font-medium shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mb-1">
          <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <span class="text-xs">Pengaturan</span>
      </a>
    </div>
  </div>

  <style>
    .nav-item {
      @apply flex flex-col items-center justify-center py-2 px-3 text-xs font-medium transition-colors text-gray-500 hover:text-gray-700;
    }

    .nav-item svg {
      @apply h-5 w-5 mb-1;
    }

    .nav-item span {
      @apply text-center;
    }

    .nav-item.active {
      @apply text-babypink-600;
    }

    .nav-item.active svg {
      @apply text-babypink-600;
    }

    /* Add padding to main content to account for bottom nav on mobile */
    @media (max-width: 767px) {
      body {
        padding-bottom: 4rem;
      }
    }
  </style>
  @stack('scripts')
</body>
</html>