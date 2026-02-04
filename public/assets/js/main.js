import "./old_main"; // übergangsweise

if (loadedModules && loadedModules.length) {
    for (const module of loadedModules) {
        import(`./${module}`);
    }
}