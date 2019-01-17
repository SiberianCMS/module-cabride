<?php

chdir(__DIR__ . "/../");

$locales = new DirectoryIterator("./resources/translations");
foreach ($locales as $locale) {
    if ($locale->isDir() && !$locale->isDot()) {
        $poFiles = new DirectoryIterator($locale->getPathname());
        foreach ($poFiles as $poFile) {
            if ($poFile->getExtension() === "po") {
                $source = $poFile->getPathname();
                $target = str_replace(".po", ".mo", $poFile->getPathname());
                echo "Compiling {$source} \n";
                exec("msgfmt -o {$target} {$source}");
            }
        }
    }
}