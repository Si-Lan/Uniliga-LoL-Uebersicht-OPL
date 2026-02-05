const controllers = new Map();

export function getController(key) {
	return controllers.get(key) || null;
}
export function resetController(key) {
    abortController(key);
	controllers.set(key, new AbortController());
}
export function abortController(key) {
	const controller = controllers.get(key);
	if (controller && !controller.signal.aborted) {
		controller.abort();
	}
}
export function getControllerSignal(key) {
	const controller = controllers.get(key);
	return controller ? controller.signal : null;
}