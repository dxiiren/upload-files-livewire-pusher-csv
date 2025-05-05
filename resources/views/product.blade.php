@extends('layouts.app')

@section('content')

@if ($products->count())
    <form method="POST" action="{{ route('clear') }}"
          onsubmit="return confirm('Are you sure you want to delete all products and files?');"
          class="mb-4">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="btn bg-red-100 hover:bg-red-200 text-red-700 font-medium px-4 py-2 rounded">
            Clear All
        </button>
    </form>
@endif

@if (session('status'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-6 max-w-7xl mx-auto">
        {{ session('status') }}
    </div>
@endif


<div class="max-w-7xl mx-auto w-full mt-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Product List</h1>
    
        <div class="overflow-x-auto bg-white shadow-sm rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Unique Key</th>
                        <th class="px-6 py-3">Title</th>
                        <th class="px-6 py-3">Style</th>
                        <th class="px-6 py-3">Color</th>
                        <th class="px-6 py-3">Size</th>
                        <th class="px-6 py-3">Price (RM)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100 text-sm text-gray-700">
                    @forelse ($products as $product)
                    <tr>
                        <td class="px-6 py-4">{{ $product->unique_key }}</td>
                        <td class="px-6 py-4">{{ $product->product_title }}</td>
                        <td class="px-6 py-4">{{ $product->style ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $product->sanmar_mainframe_color ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $product->size ?? '-' }}</td>
                        <td class="px-6 py-4">{{ number_format($product->piece_price ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

