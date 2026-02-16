<?php

namespace Oobi\Laraberg\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Oobi\Laraberg\Services\OEmbedService;

class OEmbedController extends Controller
{
    public function show(Request $request, OEmbedService $oembed): ?array
    {
        try {
            return $oembed->parse(
                $request->get('url')
            );
        } catch (\Exception) {
            return [];
        }
    }
}
