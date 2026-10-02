/**
 * Recorder — enregistrement audio via l'API MediaRecorder du navigateur.
 *
 * Gère : demande d'autorisation micro, démarrage/arrêt, chronomètre, aperçu,
 * nouvel enregistrement (pendant la phase autorisée) et erreurs d'accès.
 */
export class Recorder {
    constructor(options = {}) {
        this.maxSeconds = options.maxSeconds || 180;
        this.onTick = options.onTick || null;
        this.onStop = options.onStop || null;
        this.onError = options.onError || null;
        this.onStateChange = options.onStateChange || null;
        this.chunks = [];
        this.blob = null;
        this._stream = null;
        this._recorder = null;
        this._timer = null;
        this._elapsed = 0;
    }

    get supported() {
        return typeof navigator !== 'undefined'
            && navigator.mediaDevices
            && typeof navigator.mediaDevices.getUserMedia === 'function'
            && typeof window.MediaRecorder !== 'undefined';
    }

    async requestPermission() {
        if (!this.supported) {
            const err = { code: 'unsupported', message: 'Enregistrement audio non pris en charge par ce navigateur.' };
            if (this.onError) this.onError(err);
            throw err;
        }

        try {
            this._stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this._state('granted');
            return true;
        } catch (error) {
            const err = {
                code: error.name || 'denied',
                message: this._permissionMessage(error.name),
            };
            if (this.onError) this.onError(err);
            throw err;
        }
    }

    async start() {
        if (!this._stream) {
            await this.requestPermission();
        }

        this.chunks = [];
        this.blob = null;
        this._elapsed = 0;

        const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : 'audio/webm';

        this._recorder = new MediaRecorder(this._stream, { mimeType: mime });
        this._recorder.ondataavailable = (e) => { if (e.data.size > 0) this.chunks.push(e.data); };
        this._recorder.onstop = () => {
            this.blob = new Blob(this.chunks, { type: 'audio/webm' });
            if (this.onStop) this.onStop(this.blob, this._elapsed);
        };

        this._recorder.start();
        this._state('recording');
        this._startTimer();
    }

    stop() {
        if (this._recorder && this._recorder.state !== 'inactive') {
            this._recorder.stop();
        }
        this._clearTimer();
        this._state('stopped');
    }

    reset() {
        this.stop();
        this.blob = null;
        this.chunks = [];
        this._elapsed = 0;
    }

    release() {
        this._clearTimer();
        if (this._stream) {
            this._stream.getTracks().forEach((t) => t.stop());
            this._stream = null;
        }
    }

    /** Envoie l'enregistrement au serveur (FormData). */
    async upload(url, extra = {}) {
        if (!this.blob) throw new Error('Aucun enregistrement à envoyer.');

        const filename = `response_${Date.now()}.webm`;
        const form = new FormData();
        form.append('audio', this.blob, filename);
        Object.entries(extra).forEach(([k, v]) => form.append(k, v));

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: form,
        });

        return response.json();
    }

    _startTimer() {
        this._timer = setInterval(() => {
            this._elapsed += 1;
            if (this.onTick) this.onTick(this._elapsed, this.maxSeconds - this._elapsed);
            if (this._elapsed >= this.maxSeconds) {
                this.stop();
            }
        }, 1000);
    }

    _clearTimer() {
        clearInterval(this._timer);
        this._timer = null;
    }

    _state(state) {
        if (this.onStateChange) this.onStateChange(state);
    }

    _permissionMessage(name) {
        switch (name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return 'Accès au microphone refusé. Autorisez le microphone dans votre navigateur (icône à gauche de la barre d’adresse), puis réessayez.';
            case 'NotFoundError':
                return 'Aucun microphone détecté. Vérifiez qu’un microphone est connecté.';
            default:
                return 'Impossible d’accéder au microphone : ' + (name || 'erreur inconnue');
        }
    }
}