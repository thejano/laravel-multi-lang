---
title: Locale Middleware
---

# Locale Middleware

Laravel Multi-Lang ships with a `SetLocale` middleware that detects a user’s preferred language and applies it before your controllers run. This page shows how to install it and what data sources it inspects.

---

## What the middleware does

`TheJano\MultiLang\Middleware\SetLocale` inspects each incoming request in the following order:

1. **Route parameter** – e.g. `/ckb/posts`
2. **Query parameter** – e.g. `/posts?locale=ar`
3. **Session** – value stored under `session('locale')`
4. **Accept-Language header** – standard HTTP mechanism

The first match wins. Once a locale is set, Laravel Multi-Lang (and Laravel’s own translation system) use that locale for the remainder of the request.

---

## Installing the middleware

### Laravel 11+

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \TheJano\MultiLang\Middleware\SetLocale::class,
    ]);
});
```

### Laravel ≤10

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \TheJano\MultiLang\Middleware\SetLocale::class,
    ],
];
```

---

## Route parameter driven locales

```php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(\TheJano\MultiLang\Middleware\SetLocale::class)
    ->group(function () {
        Route::get('/{locale}/posts', function (Request $request, $locale) {
            // Locale already applied globally
            $posts = Post::withTranslations($locale)->latest()->get();

            return view('posts.index', compact('posts', 'locale'));
        })->where('locale', '[a-zA-Z_-]+');
    });
```

Visiting `/ckb/posts` sets the application locale to `ckb`. Any subsequent calls to `$post->title` or `trans_model($post, 'title')` automatically return Kurdish translations.

---

## Query string fallback

```php
Route::get('/posts', function (Request $request) {
    // /posts?locale=ar sets the locale for this request
    $locale = app()->getLocale();

    $posts = Post::withTranslations($locale)->paginate();

    return view('posts.index', compact('posts', 'locale'));
})->middleware(\TheJano\MultiLang\Middleware\SetLocale::class);
```

If the route doesn’t contain a locale segment, users can still switch languages by adding `?locale=...`. The middleware records the value in the session, so subsequent requests use the same locale automatically.

---

## Combining with sessions

```php
Route::post('/locale', function (Request $request) {
    session(['locale' => $request->input('locale')]);

    return back();
})->middleware('web');
```

After storing the user’s choice in the session, the middleware picks it up on the next request—even if the URL lacks a locale segment or query parameter.

---

## Tips

- Define a route constraint (`where('locale', '[a-z]{2}')`) that matches the locales you support to avoid 404 noise.
- Pair the middleware with the `supported_locales` setting in `config/app.php`—only values in that array should be accepted.
- When building SPAs or APIs, expose the detected locale (`app()->getLocale()`) so your frontend knows which translation bundle to load.

With locale detection in place, continue to [Caching & Performance](/guide/caching-performance.html) to make sure translated content stays fast.

