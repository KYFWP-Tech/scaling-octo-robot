<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ReflectionRequest;
use App\Http\Resources\ReflectionResource;
use App\Models\Reflection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Admin Reflection Management
 */
class ReflectionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Reflection::class)->only(['index']),
            new Middleware('can:show,reflection')->only(['show']),
            new Middleware('can:store,'.Reflection::class)->only(['store']),
            new Middleware('can:update,reflection')->only(['update']),
            new Middleware('can:destroy,reflection')->only(['destroy']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $reflections = Reflection::with('author:id,name,email,status')
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->when($request->has('date'), function ($query) use ($request) {
                $query->whereDate('date', $request->date);
            })
            ->orderByDesc('date')
            ->paginate();

        return ReflectionResource::collection($reflections);
    }

    public function show(Reflection $reflection)
    {
        return new ReflectionResource($reflection->loadMissing('author'));
    }

    public function store(ReflectionRequest $request)
    {
        $reflection = Reflection::create([
            'date' => $request->date,
            'title' => $request->string('title')->trim(),
            'content' => $request->string('content')->trim(),
            'author_id' => Auth::id(),
        ]);

        $this->forgetCache($reflection->date->toDateString());

        return new ReflectionResource($reflection->load('author'));
    }

    public function update(ReflectionRequest $request, Reflection $reflection)
    {
        $previousDate = $reflection->date->toDateString();

        $reflection->update([
            'date' => $request->date,
            'title' => $request->string('title')->trim(),
            'content' => $request->string('content')->trim(),
        ]);

        $this->forgetCache($previousDate);
        $this->forgetCache($reflection->date->toDateString());

        return new ReflectionResource($reflection->load('author'));
    }

    public function destroy(Reflection $reflection)
    {
        $date = $reflection->date->toDateString();

        $reflection->delete();

        $this->forgetCache($date);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function forgetCache(string $date): void
    {
        Cache::forget('reflections:'.$date);
    }
}
