<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/renderers_test.php';
require __DIR__ . '/validator_test.php';
require __DIR__ . '/repositories_test.php';
require __DIR__ . '/admin_test.php';
require __DIR__ . '/edge_cases_test.php';

runTest('Renderer und Uploads', static fn () => runRendererTests());
runTest('Rezeptvalidierung', static fn () => runValidatorTests());
runTest('Repositories und Bildverarbeitung', static fn () => runRepositoryTests());
runTest('Admin-Ansicht und LLM-Kopierbutton', static fn () => runAdminTests());
runTest('Upload-, Bild- und Pfadfehler', static fn () => runEdgeCaseTests());

fwrite(STDOUT, "[DONE] {$checks} Prüfungen erfolgreich.\n");
