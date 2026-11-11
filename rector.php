<?php

use Rector\Config\RectorConfig;

// withPhpSets() sem argumentos usa a menor versão do "require.php" do composer.json (5.6),
// então o Rector nunca aplica regras que exijam sintaxe acima do mínimo suportado.
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPhpSets();
