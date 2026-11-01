<?php
declare(strict_types=1);

function runEdgeCaseTests(): void
{
    $singleUpload = [
        'name' => 'recipe.yaml',
        'type' => 'application/yaml',
        'tmp_name' => '/tmp/recipe.yaml',
        'error' => UPLOAD_ERR_OK,
        'size' => 42,
    ];
    check(Recipe\UploadedFiles::normalize($singleUpload) === [$singleUpload], 'Einzelne Uploads bleiben als einzelne Dateieinträge erhalten.');

    $partialUpload = Recipe\UploadedFiles::normalize([
        'name' => ['recipe.yaml'],
        'error' => [UPLOAD_ERR_PARTIAL],
    ]);
    check($partialUpload[0]['name'] === 'recipe.yaml' && $partialUpload[0]['tmp_name'] === null && $partialUpload[0]['size'] === null, 'Fehlende Upload-Felder werden mit null ergänzt.');

    $imageDirectory = sys_get_temp_dir() . '/recipe-image-tests-' . bin2hex(random_bytes(6));
    $repositoryDirectory = sys_get_temp_dir() . '/recipe-repository-tests-' . bin2hex(random_bytes(6));
    $temporaryFiles = [];
    try {
        $imageRepository = new Recipe\ImageRepository($imageDirectory);
        $recipeRepository = new Recipe\RecipeRepository($repositoryDirectory);

        $emptyFile = tempnam(sys_get_temp_dir(), 'recipe-empty-test-');
        $temporaryFiles[] = $emptyFile;
        rejectsAny(static fn () => $imageRepository->save($emptyFile), 'Leere Bilddateien werden abgelehnt.');

        $textFile = tempnam(sys_get_temp_dir(), 'recipe-text-test-');
        $temporaryFiles[] = $textFile;
        file_put_contents($textFile, 'kein Bild');
        rejectsAny(static fn () => $imageRepository->save($textFile), 'Nicht unterstützte Bildformate werden abgelehnt.');

        $largeFile = tempnam(sys_get_temp_dir(), 'recipe-large-test-');
        $temporaryFiles[] = $largeFile;
        $handle = fopen($largeFile, 'wb');
        ftruncate($handle, 8388609);
        fclose($handle);
        rejectsAny(static fn () => $imageRepository->save($largeFile), 'Bilder über 8 MB werden abgelehnt.');

        file_put_contents($imageDirectory . '/not-an-image.txt', 'wird ignoriert');
        check($imageRepository->all() === [], 'Nicht veröffentlichte Dateien erscheinen nicht in der Bildliste.');

        rejectsAny(static fn () => $recipeRepository->find('../outside'), 'Ungültige Rezeptpfade werden abgelehnt.');
        rejectsAny(static fn () => $recipeRepository->find('recipe/child'), 'Verschachtelte Rezeptpfade werden abgelehnt.');
        rejects(static fn () => Recipe\RecipeValidator::slug(''), 'Leere Rezepttitel ergeben keinen Slug.');
        rejects(static fn () => Recipe\RecipeValidator::slug('!!!'), 'Titel ohne gültige Zeichen ergeben keinen Slug.');
    } finally {
        foreach ($temporaryFiles as $temporaryFile) {
            if (is_string($temporaryFile) && is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
        foreach ([$imageDirectory, $repositoryDirectory] as $directory) {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
