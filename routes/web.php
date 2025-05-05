<?php

use App\Models\File;
use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/products', function () {
    return view('product', ['products' => Product::paginate(10)]);
})->name('products');

Route::delete('/clear', function () {
    Product::truncate();

    $files = File::all();
    foreach ($files as $file) {
        if (Storage::disk('local')->exists($file->path)) {
            Storage::disk('local')->delete($file->path);
        }
    }

    File::truncate();
    Product::truncate();

    return back()->with('status', 'All products and files have been cleared.');
})->name('clear');
