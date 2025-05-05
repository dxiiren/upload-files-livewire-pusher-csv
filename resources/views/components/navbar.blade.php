<nav class="bg-white shadow px-6 py-4 flex justify-between">
    <div class="text-lg font-bold">CSV Uploader</div>
    <div class="space-x-4">
        <a href="{{ route('home') }}"
           class="{{ request()->routeIs('home') ? 'text-black font-semibold underline' : 'text-blue-600 hover:underline' }}">
            Home
        </a>
        <a href="{{ route('products') }}"
           class="{{ request()->routeIs('products') ? 'text-black font-semibold underline' : 'text-blue-600 hover:underline' }}">
            Products
        </a>
    </div>
</nav>
