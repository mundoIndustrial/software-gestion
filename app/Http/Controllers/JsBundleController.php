<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * JsBundleController
 * 
 * Concatenates multiple JS files into a single response to reduce HTTP requests.
 * ~120 individual requests → ~8 bundled requests = eliminates waterfall bottleneck.
 * 
 * Caching strategy:
 * - Disk cache in storage/framework/cache/js-bundles/
 * - Cache key includes bundle name + asset_version
 * - Browser caching via Cache-Control: immutable (1 year)
 * - Cache invalidated by changing ASSET_VERSION in .env
 */
class JsBundleController extends Controller
{
    public function serve(Request $request, string $name): Response
    {
        $bundles = config('js-bundles');

        if (!isset($bundles[$name])) {
            abort(404, "Bundle '{$name}' not found");
        }

        $files = $bundles[$name];
        $version = config('app.asset_version', '1.0.0');
        $cacheDir = storage_path('framework/cache/js-bundles');
        $cachePath = "{$cacheDir}/{$name}-{$version}.js";

        // Serve from disk cache if available
        if (file_exists($cachePath)) {
            return $this->jsResponse(file_get_contents($cachePath), $version);
        }

        // Concatenate all files in order
        $parts = ["/* Bundle: {$name} | v{$version} | " . count($files) . " files */"];
        $missing = [];

        foreach ($files as $file) {
            $path = public_path($file);
            if (file_exists($path)) {
                $parts[] = file_get_contents($path);
            } else {
                $missing[] = $file;
                $parts[] = "/* MISSING: {$file} */";
            }
        }

        $content = implode(";\n", $parts);

        // Write disk cache
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cachePath, $content);

        return $this->jsResponse($content, $version);
    }

    private function jsResponse(string $content, string $version): Response
    {
        return response($content, 200, [
            'Content-Type'  => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag'          => '"' . md5($version) . '"',
            'X-Bundle'      => 'concatenated',
        ]);
    }
}
