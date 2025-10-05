<?php

namespace Illuminate\Http\Response {
    /**
     * @method static \Illuminate\Http\JsonResponse jsonSuccess(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     * @method static \Illuminate\Http\JsonResponse jsonError(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     */
    class Response {}
}

namespace Illuminate\Support\Facades {
    /**
     * @method static \Illuminate\Http\JsonResponse jsonSuccess(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     * @method static \Illuminate\Http\JsonResponse jsonError(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     */
    class Response {}
}

namespace Illuminate\Contracts\Routing {
    /**
     * @method \Illuminate\Http\JsonResponse jsonSuccess(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     * @method \Illuminate\Http\JsonResponse jsonError(mixed $data)
     * @see \App\Providers\ResponseMacroServiceProvider::boot()
     */
    interface ResponseFactory {}
}
