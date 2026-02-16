<?php

namespace Oobi\Laraberg\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    /**
     * Serve the Laraberg JavaScript file.
     */
    public function js(): BinaryFileResponse|Response
    {
        return $this->pretendResponseIsFile(
            __DIR__.'/../../../public/js/laraberg.js',
            'application/javascript; charset=utf-8'
        );
    }

    /**
     * Serve a Laraberg JS chunk file (e.g. 510.laraberg.js).
     */
    public function jsChunk(string $chunk): BinaryFileResponse|Response
    {
        // Sanitize — only allow digits + ".laraberg.js"
        if (! preg_match('/^\d+\.laraberg\.js$/', $chunk)) {
            abort(404);
        }

        return $this->pretendResponseIsFile(
            __DIR__.'/../../../public/js/'.$chunk,
            'application/javascript; charset=utf-8'
        );
    }

    /**
     * Serve the Laraberg CSS file.
     */
    public function css(): BinaryFileResponse|Response
    {
        return $this->pretendResponseIsFile(
            __DIR__.'/../../../public/css/laraberg.css',
            'text/css; charset=utf-8'
        );
    }

    /**
     * Serve a file with proper caching headers.
     *
     * Modeled on Livewire's pretendResponseIsFile pattern.
     */
    protected function pretendResponseIsFile(string $file, string $contentType): BinaryFileResponse|Response
    {
        if (! file_exists($file)) {
            abort(404, 'Asset not found.');
        }

        $lastModified = filemtime($file);
        $expires = strtotime('+1 year');
        $cacheControl = 'public, max-age=31536000';

        if ($this->matchesCache($lastModified)) {
            return response('', 304, [
                'Expires' => $this->httpDate($expires),
                'Cache-Control' => $cacheControl,
            ]);
        }

        return response()->file($file, [
            'Content-Type' => $contentType,
            'Expires' => $this->httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => $this->httpDate($lastModified),
        ]);
    }

    /**
     * Check if the browser's cached version matches the file's last modified time.
     */
    protected function matchesCache(int $lastModified): bool
    {
        $ifModifiedSince = request()->header('if-modified-since');

        return $ifModifiedSince !== null && @strtotime($ifModifiedSince) === $lastModified;
    }

    /**
     * Format a timestamp as an HTTP date string.
     */
    protected function httpDate(int $timestamp): string
    {
        return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $timestamp));
    }
}
