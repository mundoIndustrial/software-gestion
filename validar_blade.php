<?php

$directory = __DIR__ . '/resources/views';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        
        // Contar @section y @endsection
        $sections = preg_match_all('/@section\s*\(/', $content);
        $endsections = preg_match_all('/@endsection/', $content);
        
        if ($sections !== $endsections) {
            echo "❌ " . str_replace(__DIR__, '', $file->getRealPath()) . 
                 " - @section: $sections, @endsection: $endsections\n";
        }
    }
}

echo "\n✅ Validación completada\n";
?>
