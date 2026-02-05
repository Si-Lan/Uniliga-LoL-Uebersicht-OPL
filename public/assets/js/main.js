import "./components/searchBar"; // solange Suchleisten noch nicht modularisiert sind

if (loadedModules && loadedModules.length) {
    for (const module of loadedModules) {
        import(`./${module}`);
    }
}