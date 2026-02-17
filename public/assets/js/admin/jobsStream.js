export default class JobsStream {
    constructor(options = {}) {
        this.url = options.url || '/admin/api/events/jobs';
        this.onInitial = options.onInitial || function() {};
        this.onUpdate = options.onUpdate || function() {};
        this.onHeartbeat = options.onHeartbeat || function() {};
        this.onOpen = options.onOpen || function() {};
        this.onError = options.onError || function() {};
        this.es = null;
    }

    start() {
        if (this.es) return;
        this.es = new EventSource(this.url);

        this.es.addEventListener('open', (e) => {
            this.onOpen(e);
        });

        this.es.addEventListener('error', (e) => {
            this.onError(e);
        });

        this.es.addEventListener('initial', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.onInitial(data);
            } catch (err) {
                console.error('Failed to parse initial SSE payload', err);
            }
        });

        this.es.addEventListener('update', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.onUpdate(data);
            } catch (err) {
                console.error('Failed to parse update SSE payload', err);
            }
        });

        this.es.addEventListener('heartbeat', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.onHeartbeat(data);
            } catch (err) {
                this.onHeartbeat(null);
            }
        });
    }

    close() {
        if (!this.es) return;
        try { this.es.close(); } catch (e) {}
        this.es = null;
    }
}
