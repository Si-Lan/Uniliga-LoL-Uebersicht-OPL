export default async function fragmentLoader(fragment, signal = null, onError = null, warnOnlyOnError = false){
	let requestInit = {method: "GET"}
	if (signal !== null) {
		requestInit.signal = signal;
	}
	return fetch(`/ajax/fragment/${fragment}`, requestInit)
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				if (typeof onError === "function") {
					onError();
				}
				return {
					"html": "Fehler beim Laden der Daten",
					"css": [],
					"js": []
				}
			}
		})
		.then(async response => {
			const cssLoadPromises = [];

			response.css.forEach(href => {
				if (!document.querySelector(`link[href="${href}"]`)) {
					const link = document.createElement('link');
					link.rel = 'stylesheet';
					link.href = href;

					cssLoadPromises.push(new Promise(resolve => {
						link.addEventListener('load', resolve, {once: true});
						link.addEventListener('error', resolve, {once: true});
					}));

					document.head.appendChild(link);
				}
			})
			if (cssLoadPromises.length > 0) {
				await Promise.all(cssLoadPromises);
			}
			response.js.forEach(src => {
				import(`./${src}`);
			})
			return response.html;
		})
		.catch(error => {
			if (error.name === "AbortError") {
				console.log(error)
			} else if (warnOnlyOnError) {
				console.warn(error)
			} else {
				console.error(error)
			}
			if (typeof onError === "function") {
				onError();
			}
			return "Fehler beim Laden der Daten";
		})
}