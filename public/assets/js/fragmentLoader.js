async function fragmentLoader(fragment, signal = null, onError = null, warnOnlyOnError = false){
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
		.then(response => {
			response.css.forEach(href => {
				if (!document.querySelector(`link[href="${href}"]`)) {
					const link = document.createElement('link');
					link.rel = 'stylesheet';
					link.href = href;
					document.head.appendChild(link);
				}
			})
			response.js.forEach(src => {
				if (!document.querySelector(`script[src="${src}"]`)) {
					const script = document.createElement('script');
					script.src = src;
					script.defer = true;
					document.body.appendChild(script);
				}
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