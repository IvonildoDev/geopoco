// ...existing code...

// Desativar a busca por source maps que não existem
if (window.addEventListener) {
    window.addEventListener('error', function (e) {
        // Ignora erros de carregamento de source maps
        if (e && e.target && e.target.src && e.target.src.endsWith('.map')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);
}